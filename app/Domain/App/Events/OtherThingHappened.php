<?php

namespace Domain\App\Events;

use Spatie\EventSourcing\StoredEvents\ShouldBeStored;

final class OtherThingHappened extends ShouldBeStored
{
    public function __construct(public string $id, public string $when)
    {
        //
    }
}