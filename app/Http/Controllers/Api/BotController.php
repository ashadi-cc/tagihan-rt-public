<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BotMonitorService;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function __construct(private BotMonitorService $botMonitor)
    {
    }

    public function handle(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:log,login,error,ready,heartbeat',
            'content' => 'required|string',
            'session_id' => 'nullable|string',
            'timestamp' => 'nullable|string',
        ]);

        $organizationId = auth()->user()->organization_id;

        $command = $this->botMonitor->recordEvent(
            $organizationId,
            $request->input('type'),
            $request->input('content'),
            $request->input('session_id'),
            $request->input('timestamp'),
        );

        if ($command) {
            return response()->json(['type' => 'command', 'content' => $command]);
        }

        return response()->json(['type' => 'status', 'content' => 'ok']);
    }
}
