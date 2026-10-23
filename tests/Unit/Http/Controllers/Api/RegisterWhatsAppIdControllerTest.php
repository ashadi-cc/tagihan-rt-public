<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\RegisterWhatsAppIdController;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class RegisterWhatsAppIdControllerTest extends TestCase
{
    public function testRefusesRegistrationWhenDisabledForOrganization()
    {
        $user = (new User())->forceFill(['id' => 1, 'organization_id' => 7]);
        $user->setRelation('organization', new Organization(['whatsapp_enable_registration' => false]));
        auth()->setUser($user);

        // Even an otherwise empty request is answered before validation runs
        $response = (new RegisterWhatsAppIdController())->register(new Request());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertStringContainsString('registration is currently disabled', $data['message']);
    }
}
