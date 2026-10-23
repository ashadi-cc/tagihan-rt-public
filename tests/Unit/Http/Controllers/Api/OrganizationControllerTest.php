<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\OrganizationController;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class OrganizationControllerTest extends TestCase
{
    public function testReturnsNotFoundWhenUserHasNoOrganization()
    {
        $user = (new User())->forceFill(['id' => 1, 'organization_id' => null]);
        $user->setRelation('organization', null);
        auth()->setUser($user);

        $response = (new OrganizationController())->getOrganization(new Request());

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'success' => false,
            'message' => 'Organization not found',
        ], $response->getData(true));
    }

    public function testReturnsOrganizationWithResolvedWhatsappSettings()
    {
        config([
            'bot.whatsapp_member_group_id' => 'config-member',
            'bot.whatsapp_notif_startup_number' => 'config-number',
        ]);

        $organization = (new Organization())->forceFill([
            'id' => 7,
            'name' => 'RT 04',
            'description' => 'RT 04 / RW 01',
            'whatsapp_admin_group_id' => 'org-admin',
            'whatsapp_enable_registration' => false,
        ]);

        $user = (new User())->forceFill(['id' => 1, 'organization_id' => 7]);
        $user->setRelation('organization', $organization);
        auth()->setUser($user);

        $response = (new OrganizationController())->getOrganization(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'success' => true,
            'organization' => [
                'id' => 7,
                'name' => 'RT 04',
                'description' => 'RT 04 / RW 01',
                'whatsapp_admin_group_id' => 'org-admin',
                'whatsapp_member_group_id' => 'config-member',
                'whatsapp_notif_startup_number' => 'config-number',
                'whatsapp_enable_registration' => false,
            ],
        ], $response->getData(true));
    }
}
