<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BotMonitorService;
use Illuminate\Http\Request;

class BotServiceController extends Controller
{
    public function __construct(private BotMonitorService $botMonitor)
    {
    }

    public function index()
    {
        $organizationId = auth()->user()->organization_id;

        $state = $this->botMonitor->getState($organizationId);
        $status = $this->botMonitor->computeStatus($state);
        $logs = $this->botMonitor->getLogsAfter($organizationId, 0);

        return view('admin.bot_service.index', compact('state', 'status', 'logs'));
    }

    public function logs(Request $request)
    {
        $organizationId = auth()->user()->organization_id;
        $afterId = (int) $request->query('after_id', 0);

        $state = $this->botMonitor->getState($organizationId);

        return response()->json([
            'success' => true,
            'status' => $this->botMonitor->computeStatus($state),
            'state' => $state,
            'logs' => $this->botMonitor->getLogsAfter($organizationId, $afterId),
        ]);
    }

    public function command(Request $request)
    {
        $request->validate([
            'command' => 'required|string|in:restart,delete_session,list_group',
        ]);

        $organizationId = auth()->user()->organization_id;

        $this->botMonitor->queueCommand($organizationId, $request->input('command'));

        auth()->user()->logs()->create([
            'organization_id' => $organizationId,
            'username' => auth()->user()->name,
            'description' => "Mengirim command '{$request->input('command')}' ke Bot Service",
            'url_link' => '/admin/bot-service',
        ]);

        return response()->json(['success' => true]);
    }
}
