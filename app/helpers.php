<?php

if (!function_exists('allowConcurrentPersists')) {
    function allowConcurrentPersists(
        Spatie\EventSourcing\AggregateRoots\AggregateRoot $aggregateRoot,
        int $tries = 0,
        array $event_list = []
    ): \Spatie\EventSourcing\AggregateRoots\AggregateRoot {
        $events = count($event_list) > 0 ? $event_list : $aggregateRoot->getRecordedEvents();
        
        try {
            if ($tries === 0) {
                // don't want to do this multiple times, to improve performance
                $can_allow_concurrency = class_implements($aggregateRoot);

                if (!array_key_exists(Support\Domain\Interfaces\AllowConcurrentPersists::class, $can_allow_concurrency)) {
                    return $aggregateRoot->persist();
                }
            }
            $current = $aggregateRoot->getReconstitutedAggregateVersion();

            // if it is empty, don't bother
            if (count($events) === 0) {
                return $aggregateRoot->persist();
            }

            $all_versions = array_map(function ($e) {
                return $e->aggregateRootVersion();
            }, $events);
            $sorted_versions = $all_versions;
            sort($sorted_versions);

            $latest_event = config('event-sourcing.stored_event_model')::where('aggregate_uuid', $aggregateRoot->uuid())
                ->orderBy('aggregate_version', 'desc')
                ->first();

            // if it is sorted, first value should be enough to determine validity
            // if the least version is greater than the latest persisted version, persist
            if ($sorted_versions[0] > $latest_event->aggregate_version) {
                return $aggregateRoot->persist();
            }

            $tries = $tries + 1;// first increment

            if ($current > $latest_event->aggregate_version) {
                $increment = ($current - $sorted_versions[0]) + $tries;
            } else {
                $increment = ($latest_event->aggregate_version - $sorted_versions[0]) + $tries;
            }

            $new_root_version = $aggregateRoot->getAggregateRootVersion();

            foreach ($events as $i => $event) {
                $new_value = $event->metaData()['aggregate-root-version'] + $increment;
                if ($i == 0) {
                    $new_root_version = $new_value;
                }
                $events[$i]->setMetaData(array_merge($event->metaData(), ['aggregate-root-version' => $new_value]));
            }

            $aggregateRoot->setRecordedEvents($events);
            $aggregateRoot->setReconstitutedAggregateVersion($latest_event->aggregate_version);
            $aggregateRoot->setAggregateRootVersion($new_root_version);

            // try something very daring here
            if ($current % 150 == 0) {
                $aggregateRoot->snapshot(); // snapshot it at every 150 events
            }

            return $aggregateRoot->persist();
        } catch (\Spatie\EventSourcing\AggregateRoots\Exceptions\CouldNotPersistAggregate|\Illuminate\Database\QueryException $e) {
            ++$tries;

            if ($e instanceof \Illuminate\Database\QueryException) {
                $code = $e->errorInfo[1];
                
                if ($code == 23505 && $tries < 12) {
                    // since the higher number has already been taken
                $tries += 1; // tries is already incremented
                return allowConcurrentPersists($aggregateRoot, $tries, $events);
                }
            }

            $terminal_count = 6;
            if ($tries >= $terminal_count || $tries === 0) {
                throw $e;
            }

            if (\Illuminate\Support\Str::contains($e->getMessage(), ["events after version", "but version"])) {
                try {
                    $line = $e->getMessage();
                    $updated_version = intval(explode(" ", (explode("but version ", $line)[1]))[0]);
                    $aggregateRoot = $aggregateRoot->setReconstitutedAggregateVersion($updated_version);

                    return allowConcurrentPersists($aggregateRoot, $tries, $events);
                } catch (\Exception|\Throwable $e) {
                    // Do Nothing, not important
                }
            }

            return allowConcurrentPersists($aggregateRoot, $tries, $events);
        }
    }
}