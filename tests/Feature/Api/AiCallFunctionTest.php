<?php

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Inventory;
use App\Models\Organization;
use App\Models\Pembayaran;
use App\Models\RtTransaction;
use App\Models\Tagihan;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AiCallFunctionTest extends ApiTestCase
{
    protected Organization $otherOrganization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDateTime(2026, 10, 15, 9, 0));
        $this->otherOrganization = $this->createOrganization();
        $this->actingAsBot();
    }

    // ---- /api/ai/current-user ---------------------------------------------

    public function testCurrentUserLabelsResidentsAsWarga()
    {
        $this->createUser($this->organization, ['name' => 'Budi', 'blok' => 'A-1', 'whatsapp_id' => '628111@c.us', 'jabatan' => 'Ketua']);

        $this->getJson('/api/ai/current-user?whatsapp_id=628111@c.us')
            ->assertOk()
            ->assertJsonPath('data.name', 'Budi')
            ->assertJsonPath('data.blok', 'A-1')
            ->assertJsonPath('data.jabatan', 'Warga RT');
    }

    public function testCurrentUserKeepsAdminJabatanOrDefaultsToPengurus()
    {
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $this->createUser($this->organization, ['whatsapp_id' => '628111@c.us', 'jabatan' => 'Bendahara'])->assignRole('admin');
        $this->createUser($this->organization, ['whatsapp_id' => '628222@c.us', 'jabatan' => '  '])->assignRole('admin');

        $this->getJson('/api/ai/current-user?whatsapp_id=628111@c.us')->assertJsonPath('data.jabatan', 'Bendahara');
        $this->getJson('/api/ai/current-user?whatsapp_id=628222@c.us')->assertJsonPath('data.jabatan', 'Pengurus RT');
    }

    public function testCurrentUserIsScopedToOrganization()
    {
        $this->createUser($this->otherOrganization, ['whatsapp_id' => '628111@c.us']);

        $this->getJson('/api/ai/current-user?whatsapp_id=628111@c.us')
            ->assertNotFound()
            ->assertExactJson(['success' => false, 'message' => 'User not found']);
    }

    // ---- /api/ai/users ----------------------------------------------------

    public function testUsersFiltersByBlokAndNameAndExcludesAdmin()
    {
        $this->createUser($this->organization, ['name' => 'Budi Santoso', 'blok' => 'C-1']);
        $this->createUser($this->organization, ['name' => 'Siti Budiarti', 'blok' => 'C-2']);
        $this->createUser($this->organization, ['name' => 'Budi Lain', 'blok' => 'D-1']);
        $this->createUser($this->otherOrganization, ['name' => 'Budi Luar', 'blok' => 'C-3']);

        $this->getJson('/api/ai/users')
            ->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJsonMissing(['name' => $this->botUser->name]);

        // blok filter is case-insensitive (input is upper-cased)
        $response = $this->getJson('/api/ai/users?blok=c&name=budi')->assertJsonPath('count', 2);
        $this->assertEqualsCanonicalizing(['Budi Santoso', 'Siti Budiarti'], $response->json('data.*.name'));
    }

    // ---- /api/ai/bills ----------------------------------------------------

    public function testBillsReturnsOnlyOutstandingNonManualBills()
    {
        $user = $this->createUser($this->organization);

        $this->createTagihan($user, ['billing_name' => 'Iuran Oktober', 'billing_generated_date' => '2026-10-01', 'billing_amount' => 100000, 'payment_amount' => 25000]);
        $this->createTagihan($user, ['billing_name' => 'Iuran September', 'billing_generated_date' => '2026-09-01', 'billing_amount' => 100000]);
        $this->createTagihan($user, ['billing_name' => 'Lunas', 'billing_amount' => 100000, 'payment_amount' => 100000]);
        $this->createTagihan($user, ['billing_name' => 'Manual', 'billing_type' => 'manual']);
        $this->createTagihan($this->createUser($this->organization), ['billing_name' => 'Orang lain']);

        $this->getJson('/api/ai/bills?user_id=' . $user->id)
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('data.0.billing_name', 'Iuran Oktober')
            ->assertJsonPath('data.1.billing_name', 'Iuran September')
            ->assertJson(['summary' => ['total_unpaid' => 175000, 'total_bills' => 2]]);
    }

    public function testBillsCanBeFilteredByGeneratedDateAndLimited()
    {
        $user = $this->createUser($this->organization);
        $this->createTagihan($user, ['billing_generated_date' => '2026-10-01']);
        $this->createTagihan($user, ['billing_generated_date' => '2026-09-01']);
        $this->createTagihan($user, ['billing_generated_date' => '2026-08-01']);

        $this->getJson('/api/ai/bills?user_id=' . $user->id . '&billing_generated_date=2026-09-01')
            ->assertJsonPath('count', 1);

        $this->getJson('/api/ai/bills?user_id=' . $user->id . '&limit=2')
            ->assertJsonPath('count', 2);
    }

    public function testBillsIgnoresBillsOfOtherOrganizations()
    {
        $foreignUser = $this->createUser($this->otherOrganization);
        $this->createTagihan($foreignUser);

        $this->getJson('/api/ai/bills?user_id=' . $foreignUser->id)
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function testBillsRejectsUnknownUser()
    {
        $this->getJson('/api/ai/bills?user_id=999999')
            ->assertStatus(422)
            ->assertJsonPath('errors.user_id.0', fn ($message) => $message !== null);
    }

    // ---- /api/ai/global-bills ---------------------------------------------

    public function testGlobalBillsSplitsCurrentMonthAndArrearsPerUser()
    {
        $budi = $this->createUser($this->organization, ['name' => 'Budi']);
        $this->createTagihan($budi, ['billing_generated_date' => '2026-10-01', 'billing_amount' => 100000]);
        $this->createTagihan($budi, ['billing_generated_date' => '2026-09-01', 'billing_amount' => 100000, 'payment_amount' => 40000]);
        $this->createTagihan($budi, ['billing_type' => 'tunggakan_warga', 'billing_generated_date' => '2025-01-01', 'billing_amount' => 50000]);
        $this->createTagihan($budi, ['billing_type' => 'manual', 'billing_amount' => 999999]);
        $this->createTagihan($budi, ['billing_generated_date' => '2026-08-01', 'billing_amount' => 100000, 'payment_amount' => 100000]);

        $siti = $this->createUser($this->organization, ['name' => 'Siti']);
        $this->createTagihan($siti, ['billing_generated_date' => '2026-10-01', 'billing_amount' => 100000]);

        $this->createTagihan($this->createUser($this->otherOrganization), ['billing_amount' => 500000]);

        $this->getJson('/api/ai/global-bills')
            ->assertOk()
            ->assertJsonPath('count', 2)
            // Sorted by total unpaid, highest first
            ->assertJson([
                'data' => [
                    ['name' => 'Budi', 'current_month_tagihan' => 100000, 'tunggakan_total' => 110000, 'total_unpaid' => 210000],
                    ['name' => 'Siti', 'current_month_tagihan' => 100000, 'tunggakan_total' => 0, 'total_unpaid' => 100000],
                ],
                'summary' => [
                    'total_current_month' => 200000,
                    'total_tunggakan' => 110000,
                    'total_unpaid' => 310000,
                    'total_users' => 2,
                ],
            ]);
    }

    // ---- /api/ai/payments & /api/ai/global-payments -----------------------

    public function testPaymentsFiltersByStatusAndDateRange()
    {
        $user = $this->createUser($this->organization);
        $september = $this->createPembayaran($user, ['payment_date' => '2026-09-10', 'status' => 'pending', 'payment_amount' => 50000]);
        $october = $this->createPembayaran($user, ['payment_date' => '2026-10-10', 'status' => 'verified', 'payment_amount' => 100000]);
        $august = $this->createPembayaran($user, ['payment_date' => '2026-08-10', 'status' => 'rejected', 'payment_amount' => 25000]);
        $this->createPembayaran($this->createUser($this->organization), ['payment_amount' => 777]);

        $this->getJson('/api/ai/payments?user_id=' . $user->id)
            ->assertOk()
            ->assertJsonPath('data.*.id', [$october->id, $september->id, $august->id])
            ->assertJson(['summary' => [
                'total_payments' => 3,
                'total_amount' => 175000,
                'verified_count' => 1,
                'pending_count' => 1,
                'rejected_count' => 1,
            ]]);

        $this->getJson('/api/ai/payments?user_id=' . $user->id . '&status=pending')
            ->assertJsonPath('count', 1)
            ->assertJson(['summary' => ['total_amount' => 50000]]);

        $this->getJson('/api/ai/payments?user_id=' . $user->id . '&start_date=2026-09-01&end_date=2026-09-30')
            ->assertJsonPath('count', 1);
    }

    public function testGlobalPaymentsExcludesDepositUsageAndOtherOrganizations()
    {
        $this->createPembayaran($this->createUser($this->organization), ['payment_amount' => 100000]);
        $this->createPembayaran($this->createUser($this->organization), ['payment_amount' => 50000, 'status' => 'pending']);
        $this->createPembayaran($this->createUser($this->organization), ['payment_amount' => 30000, 'payment_type' => 'deposit_usage']);
        $this->createPembayaran($this->createUser($this->otherOrganization), ['payment_amount' => 10000]);

        $this->getJson('/api/ai/global-payments')
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJson(['summary' => ['total_amount' => 150000, 'verified_count' => 1, 'pending_count' => 1]]);

        $this->getJson('/api/ai/global-payments?status=verified')->assertJsonPath('count', 1);
    }

    // ---- /api/ai/transactions ---------------------------------------------

    public function testTransactionsFiltersByTypeAndSearch()
    {
        $this->createRtTransaction(['name' => 'Iuran warga', 'credit' => 300000, 'payment_date' => '2026-10-01']);
        $this->createRtTransaction(['name' => 'Beli lampu', 'description' => 'Lampu pos ronda', 'debit' => 50000, 'payment_date' => '2026-10-05']);
        $this->createRtTransaction(['name' => 'Kebersihan', 'debit' => 20000, 'payment_date' => '2026-09-05']);
        $this->createRtTransaction(['name' => 'Luar', 'credit' => 999999, 'organization_id' => $this->otherOrganization->id]);

        $this->getJson('/api/ai/transactions')
            ->assertOk()
            ->assertJsonPath('count', 3)
            ->assertJson(['summary' => ['total_income' => 300000, 'total_expense' => 70000, 'balance' => 230000]]);

        $this->getJson('/api/ai/transactions?type=income')->assertJsonPath('count', 1)->assertJsonPath('data.0.name', 'Iuran warga');
        $this->getJson('/api/ai/transactions?type=expense')->assertJsonPath('count', 2);
        $this->getJson('/api/ai/transactions?search=ronda')->assertJsonPath('count', 1)->assertJsonPath('data.0.name', 'Beli lampu');
    }

    public function testTransactionSummaryFollowsDateRangeButNotTypeOrSearch()
    {
        $this->createRtTransaction(['credit' => 300000, 'payment_date' => '2026-10-01']);
        $this->createRtTransaction(['debit' => 50000, 'payment_date' => '2026-10-05']);
        $this->createRtTransaction(['debit' => 20000, 'payment_date' => '2026-09-05']);

        $this->getJson('/api/ai/transactions?type=income&start_date=2026-10-01&end_date=2026-10-31')
            ->assertJsonPath('count', 1)
            ->assertJson(['summary' => ['total_income' => 300000, 'total_expense' => 50000, 'balance' => 250000]]);
    }

    // ---- /api/ai/inventory ------------------------------------------------

    public function testInventorySearchesAndFiltersByCondition()
    {
        $this->createInventory(['item_name' => 'Tenda', 'condition' => 'baik', 'stored_location' => 'Pos ronda']);
        $this->createInventory(['item_name' => 'Kursi', 'condition' => 'rusak', 'stored_location' => 'Pos ronda']);
        $this->createInventory(['item_name' => 'Meja', 'condition' => 'baik', 'stored_location' => 'Rumah ketua']);
        $this->createInventory(['item_name' => 'Asing', 'organization_id' => $this->otherOrganization->id]);

        $response = $this->getJson('/api/ai/inventory')->assertOk()->assertJsonPath('count', 3);
        $this->assertSame(['Kursi', 'Meja', 'Tenda'], $response->json('data.*.item_name'));

        $this->getJson('/api/ai/inventory?search=ronda')->assertJsonPath('count', 2);
        $this->getJson('/api/ai/inventory?search=ronda&condition=baik')
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.item_name', 'Tenda');
    }

    // ---- /api/ai/events ---------------------------------------------------

    public function testEventsCanBeLimitedToUpcoming()
    {
        $this->createEvent(['title' => 'Kerja bakti', 'event_date' => '2026-09-01']);
        $this->createEvent(['title' => '17 Agustus', 'event_date' => '2026-08-17']);
        $this->createEvent(['title' => 'Rapat warga', 'event_date' => '2026-11-01', 'location' => 'Balai warga']);
        $this->createEvent(['title' => 'Hari ini', 'event_date' => '2026-10-15']);
        $this->createEvent(['title' => 'Asing', 'event_date' => '2026-12-01', 'organization_id' => $this->otherOrganization->id]);

        $response = $this->getJson('/api/ai/events')->assertOk()->assertJsonPath('count', 4);
        $this->assertSame(['Rapat warga', 'Hari ini', 'Kerja bakti', '17 Agustus'], $response->json('data.*.title'));

        $response = $this->getJson('/api/ai/events?upcoming=1')->assertJsonPath('count', 2);
        $this->assertSame(['Hari ini', 'Rapat warga'], $response->json('data.*.title'));

        $this->getJson('/api/ai/events?search=balai')->assertJsonPath('count', 1);
        $this->getJson('/api/ai/events?start_date=2026-08-01&end_date=2026-09-30')->assertJsonPath('count', 2);
    }

    // ---- /api/ai/tenants --------------------------------------------------

    public function testTenantsSearchesAndFiltersByUnitNumber()
    {
        $this->createTenant(['name' => 'Andi', 'unit_number' => 'A-1', 'contact_info' => '0811']);
        $this->createTenant(['name' => 'Bayu', 'unit_number' => 'B-2', 'address' => 'Jl. Mawar']);
        $this->createTenant(['name' => 'Asing', 'unit_number' => 'A-1', 'organization_id' => $this->otherOrganization->id]);

        $this->getJson('/api/ai/tenants')->assertOk()->assertJsonPath('count', 2);
        $this->getJson('/api/ai/tenants?search=mawar')->assertJsonPath('count', 1)->assertJsonPath('data.0.name', 'Bayu');
        $this->getJson('/api/ai/tenants?unit_number=a-1')->assertJsonPath('count', 1)->assertJsonPath('data.0.name', 'Andi');
    }

    // ---- /api/ai/user-details ---------------------------------------------

    public function testUserDetailsReturnsBillingTotalsAndRecentPayments()
    {
        $user = $this->createUser($this->organization, ['name' => 'Budi', 'blok' => 'A-1', 'whatsapp_id' => '628111@c.us']);
        $this->createTagihan($user, ['billing_generated_date' => '2026-10-01', 'billing_amount' => 100000]);
        $this->createTagihan($user, ['billing_generated_date' => '2026-09-01', 'billing_amount' => 100000, 'payment_amount' => 40000]);
        for ($i = 1; $i <= 12; $i++) {
            $latest = $this->createPembayaran($user, ['payment_date' => "2026-09-{$i}"]);
        }

        $response = $this->getJson('/api/ai/user-details?whatsapp_id=628111@c.us')
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Budi')
            ->assertJsonPath('data.user.blok', 'A-1')
            ->assertJsonCount(10, 'data.recent_payments');

        $this->assertEquals(100000, $response->json('data.user.current_month_tagihan'));
        $this->assertEquals(60000, $response->json('data.user.tunggakan_total'));
        $this->assertSame($latest->id, $response->json('data.recent_payments.0.id'));
    }

    public function testUserDetailsIsScopedToOrganization()
    {
        $this->createUser($this->otherOrganization, ['whatsapp_id' => '628111@c.us']);

        $this->getJson('/api/ai/user-details?whatsapp_id=628111@c.us')->assertNotFound();
    }

    // ---- validation -------------------------------------------------------

    public function testEndpointsReturnValidationErrors()
    {
        $this->getJson('/api/ai/current-user')->assertStatus(422)->assertJsonPath('errors.whatsapp_id.0', fn ($m) => $m !== null);
        $this->getJson('/api/ai/global-bills?limit=101')->assertStatus(422)->assertJson(['success' => false, 'message' => 'Validation error']);
        $this->getJson('/api/ai/transactions?type=transfer')->assertStatus(422);
        $this->getJson('/api/ai/events?upcoming=maybe')->assertStatus(422);
    }

    // ---- helpers ----------------------------------------------------------

    private function createTagihan(User $user, array $attributes = []): Tagihan
    {
        return Tagihan::forceCreate(array_merge([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'billing_name' => 'Iuran bulanan',
            'billing_type' => 'rutin_perbulan',
            'billing_amount' => 100000,
            'payment_amount' => 0,
            'billing_generated_date' => '2026-10-01',
        ], $attributes));
    }

    private function createPembayaran(User $user, array $attributes = []): Pembayaran
    {
        return Pembayaran::forceCreate(array_merge([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'payment_type' => 'tagihan',
            'payment_amount' => 100000,
            'payment_date' => '2026-10-01',
            'status' => 'verified',
        ], $attributes));
    }

    private function createRtTransaction(array $attributes = []): RtTransaction
    {
        return RtTransaction::forceCreate(array_merge([
            'organization_id' => $this->organization->id,
            'billing_id' => 0,
            'name' => 'Transaksi',
            'payment_date' => '2026-10-01',
        ], $attributes));
    }

    private function createInventory(array $attributes = []): Inventory
    {
        return Inventory::forceCreate(array_merge([
            'organization_id' => $this->organization->id,
            'item_name' => 'Barang',
            'quantity' => 1,
        ], $attributes));
    }

    private function createEvent(array $attributes = []): Event
    {
        return Event::forceCreate(array_merge([
            'organization_id' => $this->organization->id,
            'title' => 'Acara',
            'event_date' => '2026-10-01',
        ], $attributes));
    }

    private function createTenant(array $attributes = []): Tenant
    {
        return Tenant::forceCreate(array_merge([
            'organization_id' => $this->organization->id,
            'name' => 'Penyewa',
        ], $attributes));
    }
}
