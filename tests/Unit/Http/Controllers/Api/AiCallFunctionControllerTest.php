<?php

namespace Tests\Unit\Http\Controllers\Api;

use App\Http\Controllers\Api\AiCallFunctionController;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the validation layer of the AI function call endpoints. Every case
 * here fails validation, so the controller returns before touching the database.
 */
class AiCallFunctionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        auth()->setUser((new User())->forceFill(['id' => 1, 'organization_id' => 7]));
    }

    public static function invalidRequests(): array
    {
        return [
            'current user without whatsapp_id' => ['getCurrentUser', [], 'whatsapp_id'],
            'user details without whatsapp_id' => ['getUserDetails', [], 'whatsapp_id'],
            'bills without user_id' => ['getUserBills', [], 'user_id'],
            'payments without user_id' => ['getUserPayments', [], 'user_id'],
            'global bills with invalid start_date' => ['getGlobalUserBills', ['start_date' => 'not-a-date'], 'start_date'],
            'global bills with limit above max' => ['getGlobalUserBills', ['limit' => 101], 'limit'],
            'global payments with unknown status' => ['getGlobalUserPayments', ['status' => 'paid'], 'status'],
            'global payments with limit below min' => ['getGlobalUserPayments', ['limit' => 0], 'limit'],
            'transactions with unknown type' => ['getTransactions', ['type' => 'transfer'], 'type'],
            'transactions with invalid end_date' => ['getTransactions', ['end_date' => '2026-13-45'], 'end_date'],
            'inventory with non-integer limit' => ['getInventoryItems', ['limit' => 'ten'], 'limit'],
            'events with non-boolean upcoming' => ['getEvents', ['upcoming' => 'maybe'], 'upcoming'],
            'tenants with limit above max' => ['getTenants', ['limit' => 500], 'limit'],
        ];
    }

    #[DataProvider('invalidRequests')]
    public function testReturnsValidationErrorResponse(string $method, array $params, string $invalidField)
    {
        $request = Request::create('/api/ai', 'GET', $params);

        $response = (new AiCallFunctionController())->{$method}($request);

        $this->assertSame(422, $response->getStatusCode());

        $data = $response->getData(true);
        $this->assertFalse($data['success']);
        $this->assertSame('Validation error', $data['message']);
        $this->assertArrayHasKey($invalidField, $data['errors']);
    }
}
