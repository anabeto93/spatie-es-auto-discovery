<?php

namespace Support\Domain\Interfaces;

use Spatie\EventSourcing\AggregateRoots\AggregateRoot;

interface CanAllowConcurrentPersists
{
    public function getAggregateRootVersion(): int;

    public function getReconstitutedAggregateVersion(): int;

    public function setReconstitutedAggregateVersion(int $new_version): self|AggregateRoot;

    public function setRecordedEvents(array $events): self|AggregateRoot;

    public function setAggregateRootVersion(int $version): self|AggregateRoot;
}