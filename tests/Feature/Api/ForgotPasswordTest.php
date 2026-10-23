<?php

namespace Tests\Feature\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ForgotPasswordTest extends ApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(now()->setDateTime(2026, 10, 15, 9, 0));
    }

    public function testCreatesResetTokenAndReturnsResetUrl()
    {
        // Hostmail addresses are masked by the User accessor, the raw one must still be used
        $email = 'a1' . config('default.hostmail');
        $this->createUser($this->organization, ['blok' => 'A-1', 'whatsapp_id' => '628111@c.us', 'email' => $email]);

        $this->actingAsBot();

        $response = $this->postJson('/api/forgot-password', [
            'whatsapp_id' => '628111@c.us',
            'blok_number' => 'A-1',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertMatchesRegularExpression(
            '#/password/reset/([A-Za-z0-9]{64})\?email=' . preg_quote(urlencode($email), '#') . '$#',
            $response->json('url')
        );

        preg_match('#/password/reset/([A-Za-z0-9]{64})#', $response->json('url'), $matches);
        $stored = DB::table('password_resets')->where('email', $email)->first();
        $this->assertTrue(Hash::check($matches[1], $stored->token));
    }

    public function testRequiresMatchingWhatsappIdAndBlok()
    {
        $this->createUser($this->organization, ['blok' => 'A-1', 'whatsapp_id' => '628111@c.us']);
        $this->createUser($this->createOrganization(), ['blok' => 'B-2', 'whatsapp_id' => '628222@c.us']);

        $this->actingAsBot();

        $this->postJson('/api/forgot-password', ['whatsapp_id' => '628111@c.us', 'blok_number' => 'A-2'])
            ->assertJson(['success' => false]);

        // Users of other organizations are invisible
        $this->postJson('/api/forgot-password', ['whatsapp_id' => '628222@c.us', 'blok_number' => 'B-2'])
            ->assertJson(['success' => false]);

        $this->assertDatabaseCount('password_resets', 0);
    }

    public function testRateLimitsRepeatedRequests()
    {
        $user = $this->createUser($this->organization, ['blok' => 'A-1', 'whatsapp_id' => '628111@c.us']);
        DB::table('password_resets')->insert([
            'email' => $user->getRawOriginal('email'),
            'token' => 'old-token',
            'created_at' => now()->subMinutes(5),
        ]);

        $this->actingAsBot();

        $this->postJson('/api/forgot-password', ['whatsapp_id' => '628111@c.us', 'blok_number' => 'A-1'])
            ->assertExactJson([
                'success' => false,
                'message' => 'Anda sudah meminta reset password. Silakan coba lagi dalam 10 menit.',
            ]);

        $this->assertDatabaseHas('password_resets', ['token' => 'old-token']);
    }

    public function testAllowsNewRequestAfterRateLimitWindow()
    {
        $user = $this->createUser($this->organization, ['blok' => 'A-1', 'whatsapp_id' => '628111@c.us']);
        DB::table('password_resets')->insert([
            'email' => $user->getRawOriginal('email'),
            'token' => 'old-token',
            'created_at' => now()->subMinutes(16),
        ]);

        $this->actingAsBot();

        $this->postJson('/api/forgot-password', ['whatsapp_id' => '628111@c.us', 'blok_number' => 'A-1'])
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('password_resets', 1);
        $this->assertDatabaseMissing('password_resets', ['token' => 'old-token']);
    }

    public function testValidatesPayload()
    {
        $this->actingAsBot();

        $this->postJson('/api/forgot-password')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['whatsapp_id', 'blok_number']);
    }
}
