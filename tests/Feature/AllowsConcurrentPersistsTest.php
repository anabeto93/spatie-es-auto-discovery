<?php

namespace Tests\Feature;

use Domain\App\AppAggregateRoot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Str;
use Spatie\EventSourcing\AggregateRoots\Exceptions\CouldNotPersistAggregate;

class AllowsConcurrentPersistsTest extends TestCase
{
    use RefreshDatabase;
    /**
     * @test
     */
    public function error_thrown_when_aggregate_version_already_taken()
    {
        $appId = Str::uuid()->toString(); // some sample app uuid
        // Given two concurrent requests for instance
        $req1 = AppAggregateRoot::retrieve($appId)->somethingHappened('Anything', now()->timestamp);
        $req2 = AppAggregateRoot::retrieve($appId)->anotherThingHappened('Something', now()->timestamp);

        // If for some reason, second request persists before the first request
        $req2->persist();

        $this->expectException(CouldNotPersistAggregate::class);
        
        // an error is thrown when attempting to persist the first request
        $req1->persist();
    }

    /**
     * @test
     */
    public function can_allow_concurrent_persists()
    {
        $appId = Str::uuid()->toString(); // some sample app uuid
        // Given two concurrent requests for instance
        $req1 = AppAggregateRoot::retrieve($appId)->somethingHappened('Anything', now()->timestamp);
        $req2 = AppAggregateRoot::retrieve($appId)->anotherThingHappened('Something', now()->timestamp);

        $req1 = $req1->anotherThingHappened('Slow Process', now()->timestamp);
        $req1 = $req1->somethingHappened('Another Slow Process', now()->timestamp);

        // If for whatever reason, second request which is faster persists first
        $req2->persist();

        // Then there should be no error thrown when asked to allow concurrent persists
        allowConcurrentPersists($req1);

        // Need to assert something for this test to persist, keeping things simple
        $this->assertTrue(true);
    }
}