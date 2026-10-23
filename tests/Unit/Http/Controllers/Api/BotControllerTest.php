<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\BotController;
use App\Models\User;
use App\Services\BotMonitorService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class BotControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        auth()->setUser((new User())->forceFill(['id' => 1, 'organization_id' => 7]));
    }

    public function testReturnsStatusOkWhenNoCommandIsPending()
    {
        $botMonitor = Mockery::mock(BotMonitorService::class);
        $botMonitor->shouldReceive('recordEvent')
            ->once()
            ->with(7, 'heartbeat', 'alive', 'session-1', '2026-10-23T10:00:00Z')
            ->andReturn(null);

        $request = Request::create('/api/bot', 'POST', [
            'type' => 'heartbeat',
            'content' => 'alive',
            'session_id' => 'session-1',
            'timestamp' => '2026-10-23T10:00:00Z',
        ]);

        $response = (new BotController($botMonitor))->handle($request);

        $this->assertSame(['type' => 'status', 'content' => 'ok'], $response->getData(true));
    }

    public function testReturnsPendingCommand()
    {
        $botMonitor = Mockery::mock(BotMonitorService::class);
        $botMonitor->shouldReceive('recordEvent')
            ->once()
            ->with(7, 'log', 'hello', null, null)
            ->andReturn('restart');

        $request = Request::create('/api/bot', 'POST', [
            'type' => 'log',
            'content' => 'hello',
        ]);

        $response = (new BotController($botMonitor))->handle($request);

        $this->assertSame(['type' => 'command', 'content' => 'restart'], $response->getData(true));
    }

    public function testRejectsUnknownEventType()
    {
        $botMonitor = Mockery::mock(BotMonitorService::class);
        $botMonitor->shouldNotReceive('recordEvent');

        $request = Request::create('/api/bot', 'POST', [
            'type' => 'shutdown',
            'content' => 'bye',
        ]);

        $this->expectException(ValidationException::class);

        (new BotController($botMonitor))->handle($request);
    }

    public function testRequiresContent()
    {
        $botMonitor = Mockery::mock(BotMonitorService::class);
        $botMonitor->shouldNotReceive('recordEvent');

        $request = Request::create('/api/bot', 'POST', ['type' => 'log']);

        try {
            (new BotController($botMonitor))->handle($request);
            $this->fail('Expected a ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('content', $e->errors());
        }
    }
}
