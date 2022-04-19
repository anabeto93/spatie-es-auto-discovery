<?php

namespace Domain\App;

use Domain\App\Events\OtherThingHappened;
use Domain\App\Events\SomethingHappened;
use Spatie\EventSourcing\AggregateRoots\AggregateRoot;
use Support\Domain\Interfaces\CanAllowConcurrentPersists;
use Support\Domain\Traits\AllowsConcurrentPersists;

class AppAggregateRoot extends AggregateRoot implements CanAllowConcurrentPersists
{
    use AllowsConcurrentPersists;

    public function somethingHappened(string $what, string $when): self
    {
        // All other checks before persisting
        $this->recordThat(new SomethingHappened($what, $when));

        return $this;
    }

    public function anotherThingHappened(string $what_other_thing, string $when): self
    {
        // Some other checks before persisting
        $this->recordThat(new OtherThingHappened($what_other_thing, $when));

        return $this;
    }
}