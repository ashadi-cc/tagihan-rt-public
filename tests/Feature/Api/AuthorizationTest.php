<?php

namespace Tests\Feature\Api;

use PHPUnit\Framework\Attributes\DataProvider;

class AuthorizationTest extends ApiTestCase
{
    public static function endpoints(): array
    {
        return [
            ['POST', '/api/register-whatsapp-id'],
            ['GET', '/api/push-messages'],
            ['POST', '/api/forgot-password'],
            ['GET', '/api/organization'],
            ['POST', '/api/bot'],
            ['GET', '/api/ai/current-user'],
            ['GET', '/api/ai/users'],
            ['GET', '/api/ai/bills'],
            ['GET', '/api/ai/global-bills'],
            ['GET', '/api/ai/payments'],
            ['GET', '/api/ai/global-payments'],
            ['GET', '/api/ai/transactions'],
            ['GET', '/api/ai/inventory'],
            ['GET', '/api/ai/events'],
            ['GET', '/api/ai/tenants'],
            ['GET', '/api/ai/user-details'],
        ];
    }

    #[DataProvider('endpoints')]
    public function testRejectsUnauthenticatedRequests(string $method, string $uri)
    {
        $this->json($method, $uri)->assertUnauthorized();
    }

    #[DataProvider('endpoints')]
    public function testRejectsTokensWithoutAdminManageAbility(string $method, string $uri)
    {
        $this->actingAsBot(['read-only']);

        $this->json($method, $uri)->assertForbidden();
    }
}
