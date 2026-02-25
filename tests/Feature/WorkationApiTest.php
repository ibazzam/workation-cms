<?php

use Tests\TestCase;

class WorkationApiTest extends TestCase
{
    public function test_laravel_api_routes_are_decommissioned()
    {
        $response = $this->getJson('/api/workations');

        $response->assertStatus(410)
            ->assertJson([
                'code' => 'LARAVEL_API_DECOMMISSIONED',
            ]);
    }
}
