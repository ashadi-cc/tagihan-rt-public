<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    private const RATE_LIMIT_MINUTES = 15;

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'whatsapp_id' => 'required|string',
            'blok_number'  => 'required|string',
        ]);

        $organizationId = auth()->user()->organization_id;

        $user = User::where('organization_id', $organizationId)
            ->where('whatsapp_id', $request->whatsapp_id)
            ->where('blok', $request->blok_number)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User with the specified WhatsApp ID and blok number not found.',
            ]);
        }

        // Get raw email bypassing accessor (which masks default hostmail emails)
        $rawEmail = DB::table('users')->where('id', $user->id)->value('email');

        // Rate limit: one request per 15 minutes
        $existing = DB::table('password_resets')
            ->where('email', $rawEmail)
            ->first();

        if ($existing) {
            $createdAt = \Carbon\Carbon::parse($existing->created_at);
            $minutesElapsed = $createdAt->diffInMinutes(now(), false);

            if ($minutesElapsed < self::RATE_LIMIT_MINUTES) {
                $waitMinutes = self::RATE_LIMIT_MINUTES - (int) $minutesElapsed;
                return response()->json([
                    'success' => false,
                    'message' => "Anda sudah meminta reset password. Silakan coba lagi dalam {$waitMinutes} menit.",
                ]);
            }
        }

        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['email' => $rawEmail],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $url = url('/password/reset/' . $token) . '?email=' . urlencode($rawEmail);

        return response()->json([
            'success' => true,
            'url' => $url,
        ]);
    }
}
