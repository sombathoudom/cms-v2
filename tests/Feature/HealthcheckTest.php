<?php

use Illuminate\Support\Facades\Redis;

it('returns ok health response', function () {
    Redis::shouldReceive('connection->ping')
        ->once()
        ->andReturn('PONG');

    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertJsonStructure(['status', 'uptime', 'db', 'redis']);
});

it('fails when redis is unavailable', function () {
    Redis::shouldReceive('connection->ping')
        ->once()
        ->andThrow(new \RuntimeException('Connection failed'));

    $response = $this->getJson('/health');

    $response->assertStatus(503)
        ->assertJson(["error" => ["code" => 'REDIS_UNAVAILABLE']]);
});
