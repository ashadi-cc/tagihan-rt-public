<?php

namespace Tests\Unit\Models;

use App\Models\Organization;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    public function testWhatsappSettingsUseOrganizationValuesWhenSet()
    {
        config([
            'bot.whatsapp_admin_group_id' => 'config-admin',
            'bot.whatsapp_member_group_id' => 'config-member',
            'bot.whatsapp_notif_startup_number' => 'config-number',
        ]);

        $organization = new Organization([
            'whatsapp_admin_group_id' => 'org-admin',
            'whatsapp_member_group_id' => 'org-member',
            'whatsapp_notif_startup_number' => 'org-number',
        ]);

        $this->assertSame('org-admin', $organization->getWhatsappAdminGroupId());
        $this->assertSame('org-member', $organization->getWhatsappMemberGroupId());
        $this->assertSame('org-number', $organization->getWhatsappNotifStartupNumber());
    }

    public function testWhatsappSettingsFallBackToConfigWhenEmpty()
    {
        config([
            'bot.whatsapp_admin_group_id' => 'config-admin',
            'bot.whatsapp_member_group_id' => 'config-member',
            'bot.whatsapp_notif_startup_number' => 'config-number',
        ]);

        // Empty strings are treated the same as null
        $organization = new Organization([
            'whatsapp_admin_group_id' => '',
            'whatsapp_member_group_id' => null,
        ]);

        $this->assertSame('config-admin', $organization->getWhatsappAdminGroupId());
        $this->assertSame('config-member', $organization->getWhatsappMemberGroupId());
        $this->assertSame('config-number', $organization->getWhatsappNotifStartupNumber());
    }

    public function testEnableRegistrationFallsBackToConfigOnlyWhenNull()
    {
        config(['bot.whatsapp_enable_registration' => true]);

        $this->assertTrue((new Organization())->getWhatsappEnableRegistration());

        // An explicit false on the organization must win over the config default
        $organization = new Organization(['whatsapp_enable_registration' => false]);
        $this->assertFalse($organization->getWhatsappEnableRegistration());

        config(['bot.whatsapp_enable_registration' => false]);
        $organization = new Organization(['whatsapp_enable_registration' => 1]);
        $this->assertTrue($organization->getWhatsappEnableRegistration());
    }
}
