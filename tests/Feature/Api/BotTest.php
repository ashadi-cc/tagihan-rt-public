<?php

namespace Tests\Feature\Api;

use App\Services\BotMonitorService;

class BotTest extends ApiTestCase
{
    public function testRecordsEventForTheBotUsersOrganization()
    {
        $this->mock(BotMonitorService::class)
            ->shouldReceive('recordEvent')
            ->once()
            ->with($this->organization->id, 'ready', 'Bot is ready', 'session-1', null)
            ->andReturn(null);

        $this->actingAsBot();

        $this->postJson('/api/bot', [
            'type' => 'ready',
            'content' => 'Bot is ready',
            'session_id' => 'session-1',
        ])
            ->assertOk()
            ->assertExactJson(['type' => 'status', 'content' => 'ok']);
    }

    public function testReturnsPendingCommand()
    {
        $this->mock(BotMonitorService::class)
            ->shouldReceive('recordEvent')
            ->once()
            ->andReturn('logout');

        $this->actingAsBot();

        $this->postJson('/api/bot', ['type' => 'heartbeat', 'content' => 'alive'])
            ->assertOk()
            ->assertExactJson(['type' => 'command', 'content' => 'logout']);
    }

    public function testValidatesPayload()
    {
        $this->mock(BotMonitorService::class)->shouldNotReceive('recordEvent');

        $this->actingAsBot();

        $this->postJson('/api/bot', ['type' => 'unknown'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['type', 'content']);
    }
}
