<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class RegisterWhatsAppIdController extends Controller
{
    public function register(Request $request)
    {
        $organization = auth()->user()->organization;

        if (!$organization->getWhatsappEnableRegistration()) {
            return response()->json([
                'success' => false,
                'message' => 'WhatsApp ID registration is currently disabled. Please contact the admin for assistance.',
            ]);
        }

        $request->validate([
            'blok_number' => 'required|string',
            'whatsapp_id' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        $organizationId = $organization->id;

        $user = User::where('organization_id', $organizationId)
            ->warga()
            ->where('blok', $request->blok_number)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User with the specified blok number not found.',
            ]);
        }

        // check if whatsapp_id is already registered
        if (User::where('organization_id', $organizationId)
            ->where('whatsapp_id', $request->whatsapp_id)
            ->where('id', '!=', $user->id)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'The WhatsApp ID is already registered with another user. Please use a different WhatsApp ID.',
            ]);
        }

        if (!$user->phone_number) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number is empty in our records. Please contact the admin to update your phone number before registering your WhatsApp ID.',
            ]);
        }

        if ($user->attempt_bot_register > 5) {
            return response()->json([
                'success' => false,
                'message' => 'You have exceeded the maximum number of registration attempts. Please contact the admin for further assistance.',
            ]);
        }

        $headerMesage = $user->organization->description;
        if ($user->whatsapp_id) {
            $user->attempt_bot_register = 6;
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Yth '. $user->name . ' '. $user->blok . ' '. $headerMesage . ' Maaf, WhatsApp ID Anda sudah terdaftar. Silakan hubungi admin jika anda ingin mengubah WhatsApp ID Anda.',
            ]);
        }

        if ($user->attempt_bot_register >= 5) {
            $user->attempt_bot_register += 1;
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Yth '. $user->name . ' '. $user->blok . ' '. $headerMesage . ' Maaf, Anda telah melebihi batas percobaan pendaftaran WhatsApp ID. Silakan hubungi admin untuk bantuan lebih lanjut.',  
            ]);
        }

        // check if phone number is equal to request phone number
        if ($user->phone_number != $request->phone_number) {
            $user->attempt_bot_register += 1;
            $user->save();
            return response()->json([
                'success' => true,
                'message' => 'Yth '. $user->name . ' '. $user->blok . ' '. $headerMesage . ' Maaf, nomor telepon yang anda cantumkan tidak sesuai dengan data kami. Silakan cantumkan nomor telepon yang sesuai dengan data kami.',
            ]);
        }

        $user->whatsapp_id = $request->whatsapp_id;
        $user->save();

        $message = "Yth $user->name $user->blok $headerMesage";
        $message .= "\nAnda telah berhasil mendaftarkan nomor whatsapp Anda ke dalam sistem kami.";
        $message .= "\nSelanjutnya Anda dapat mengakes layanan kami melalui link berikut: " . config('app.url');
        $message .= " \nDefault Password Anda adalah: $user->default_password Silakan ganti password Anda setelah login pertama kali.";
        $message .= "\nTerima kasih.";
        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}