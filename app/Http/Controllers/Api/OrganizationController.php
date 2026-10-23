<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function getOrganization(Request $request)
    {
        $organization = auth()->user()->organization;

        if (!$organization) {
            return response()->json([
                'success' => false,
                'message' => 'Organization not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'description' => $organization->description,
                'whatsapp_admin_group_id' => $organization->getWhatsappAdminGroupId(),
                'whatsapp_member_group_id' => $organization->getWhatsappMemberGroupId(),
                'whatsapp_notif_startup_number' => $organization->getWhatsappNotifStartupNumber(),
                'whatsapp_enable_registration' => $organization->getWhatsappEnableRegistration(),
            ],
        ]);
    }
}
