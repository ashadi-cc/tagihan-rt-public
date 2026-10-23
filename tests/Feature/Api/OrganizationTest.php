<?php

namespace Tests\Feature\Api;

class OrganizationTest extends ApiTestCase
{
    public function testReturnsTheBotUsersOrganization()
    {
        config(['bot.whatsapp_member_group_id' => 'config-member']);

        $this->organization->forceFill([
            'whatsapp_admin_group_id' => 'admin-group@g.us',
            'whatsapp_enable_registration' => false,
        ])->save();

        $this->actingAsBot();

        $this->getJson('/api/organization')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'organization' => [
                    'id' => $this->organization->id,
                    'name' => 'RT 04',
                    'description' => 'RT 04 / RW 01',
                    'whatsapp_admin_group_id' => 'admin-group@g.us',
                    'whatsapp_member_group_id' => 'config-member',
                    'whatsapp_notif_startup_number' => config('bot.whatsapp_notif_startup_number'),
                    'whatsapp_enable_registration' => false,
                ],
            ]);
    }
}
