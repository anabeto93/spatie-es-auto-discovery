<?php

namespace Support\Domain\Traits;

use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

trait AllowsConcurrentPersists
{
    public function getAggregateRootVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function getReconstitutedAggregateVersion(): int
    {
        return $this->aggregateVersionAfterReconstitution;
    }

    public function setReconstitutedAggregateVersion(int $new_version): self|AggregateRoot
    {
        $this->aggregateVersionAfterReconstitution = $new_version;

        return $this;
    }

    public function setRecordedEvents(array $events): self|AggregateRoot
    {
        $this->recordedEvents = $events;

        return $this;
    }

    public function setAggregateRootVersion(int $version): self|AggregateRoot
    {
        $this->aggregateVersion = $version;

        return $this;
    }
}