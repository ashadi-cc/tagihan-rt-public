<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BotMonitorService;
use Illuminate\Http\Request;
use App\Models\PushMessage;


class PushMessageController extends Controller
{
    public function __construct(private BotMonitorService $botMonitor)
    {
    }

    public function index(Request $request)
    {
        $organizationId = auth()->user()->organization_id;

        $pushMessage = PushMessage::where('organization_id', $organizationId)
            ->orderBy('id', 'asc')
            ->first();

        if ($pushMessage) {

            $data = [
                'success' => true,
                'message_type' => $pushMessage->message_type,
                'recipient_id' => $pushMessage->recipient_id,
                'message' => $pushMessage->message_content,
            ];

            $this->botMonitor->recordEvent(
                $organizationId,
                'log',
                "Push message '{$pushMessage->message_type}' untuk recipient {$pushMessage->recipient_id} telah dikirim: {$pushMessage->message_content}",
                null,
                null,
            );

            $pushMessage->delete();

            return response()->json($data);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No push messages found.',
            ]);
        }
    }
}