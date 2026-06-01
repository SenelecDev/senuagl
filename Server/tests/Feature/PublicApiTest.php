<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicApiTest extends TestCase
{
    public function test_health_endpoint_returns_successful_response(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
            ]);
    }

    public function test_test_endpoint_returns_success_payload(): void
    {
        $response = $this->getJson('/api/test');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'timestamp',
            ]);
    }
}
