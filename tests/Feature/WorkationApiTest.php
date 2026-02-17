<?php

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Workation;

class WorkationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_workations()
    {
        Workation::factory()->count(3)->create();

        $response = $this->getJson('/api/workations');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_create_workation()
    {
        $payload = [
            'title' => 'Test Workation',
            'description' => 'A short description',
            'location' => 'Test City',
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-08',
            'price' => 999.99,
        ];

        $response = $this->postJson('/api/workations', $payload);

        $response->assertStatus(201)
            ->assertJsonFragment(['title' => 'Test Workation']);

        $this->assertDatabaseHas('workations', ['title' => 'Test Workation']);
    }
}
