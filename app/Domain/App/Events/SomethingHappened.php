<?php

namespace Domain\App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class SomethingHappened extends ShouldBeStored
{
    public function __construct(public string $id, public string $when)
    {
        //
    }
}