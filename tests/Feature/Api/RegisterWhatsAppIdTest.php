<?php

namespace Tests\Feature\Api;

class RegisterWhatsAppIdTest extends ApiTestCase
{
    private const PAYLOAD = [
        'blok_number' => 'A-1',
        'whatsapp_id' => '628111@c.us',
        'phone_number' => '08111',
    ];

    public function testRegistersWhatsappIdWhenPhoneNumberMatches()
    {
        $user = $this->createUser($this->organization, [
            'name' => 'Budi',
            'blok' => 'A-1',
            'phone_number' => '08111',
            'default_password' => 'rahasia',
        ]);

        $this->actingAsBot();

        $response = $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertStringStartsWith('Yth Budi A-1 RT 04 / RW 01', $response->json('message'));
        $this->assertStringContainsString('berhasil mendaftarkan', $response->json('message'));
        $this->assertStringContainsString('rahasia', $response->json('message'));
        $this->assertSame('628111@c.us', $user->fresh()->whatsapp_id);
    }

    public function testIncrementsAttemptsWhenPhoneNumberDoesNotMatch()
    {
        $user = $this->createUser($this->organization, ['blok' => 'A-1', 'phone_number' => '08999']);

        $this->actingAsBot();

        $response = $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => true]);

        $this->assertStringContainsString('tidak sesuai dengan data kami', $response->json('message'));
        $user->refresh();
        $this->assertNull($user->whatsapp_id);
        $this->assertSame(1, $user->attempt_bot_register);
    }

    public function testFifthFailedAttemptLocksRegistration()
    {
        $user = $this->createUser($this->organization, [
            'blok' => 'A-1',
            'phone_number' => '08111',
            'attempt_bot_register' => 5,
        ]);

        $this->actingAsBot();

        $response = $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => true]);

        $this->assertStringContainsString('melebihi batas percobaan', $response->json('message'));
        $user->refresh();
        $this->assertNull($user->whatsapp_id);
        $this->assertSame(6, $user->attempt_bot_register);
    }

    public function testRejectsUserWhoExceededMaximumAttempts()
    {
        $this->createUser($this->organization, [
            'blok' => 'A-1',
            'phone_number' => '08111',
            'attempt_bot_register' => 6,
        ]);

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'exceeded the maximum number'));
    }

    public function testDoesNotOverwriteExistingWhatsappIdAndLocksFurtherAttempts()
    {
        $user = $this->createUser($this->organization, [
            'blok' => 'A-1',
            'phone_number' => '08111',
            'whatsapp_id' => '628000@c.us',
        ]);

        $this->actingAsBot();

        $response = $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => true]);

        $this->assertStringContainsString('sudah terdaftar', $response->json('message'));
        $user->refresh();
        $this->assertSame('628000@c.us', $user->whatsapp_id);
        $this->assertSame(6, $user->attempt_bot_register);
    }

    public function testRejectsWhatsappIdRegisteredToAnotherUser()
    {
        $this->createUser($this->organization, ['blok' => 'A-1', 'phone_number' => '08111']);
        $this->createUser($this->organization, ['blok' => 'A-2', 'whatsapp_id' => '628111@c.us']);

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'already registered with another user'));
    }

    public function testWhatsappIdOfAnotherOrganizationDoesNotConflict()
    {
        $user = $this->createUser($this->organization, ['blok' => 'A-1', 'phone_number' => '08111']);
        $this->createUser($this->createOrganization(), ['blok' => 'B-1', 'whatsapp_id' => '628111@c.us']);

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)->assertJson(['success' => true]);

        $this->assertSame('628111@c.us', $user->fresh()->whatsapp_id);
    }

    public function testRejectsBlokOfAnotherOrganization()
    {
        $this->createUser($this->createOrganization(), ['blok' => 'A-1', 'phone_number' => '08111']);

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertExactJson([
                'success' => false,
                'message' => 'User with the specified blok number not found.',
            ]);
    }

    public function testNeverMatchesTheAdminAccount()
    {
        $this->botUser->forceFill(['blok' => 'A-1', 'phone_number' => '08111'])->save();

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertExactJson([
                'success' => false,
                'message' => 'User with the specified blok number not found.',
            ]);
    }

    public function testRejectsUserWithoutPhoneNumber()
    {
        $this->createUser($this->organization, ['blok' => 'A-1', 'phone_number' => null]);

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'Phone number is empty'));
    }

    public function testRejectsWhenRegistrationIsDisabled()
    {
        $this->organization->forceFill(['whatsapp_enable_registration' => false])->save();
        $user = $this->createUser($this->organization, ['blok' => 'A-1', 'phone_number' => '08111']);

        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id', self::PAYLOAD)
            ->assertJson(['success' => false])
            ->assertJsonPath('message', fn ($message) => str_contains($message, 'currently disabled'));

        $this->assertNull($user->fresh()->whatsapp_id);
    }

    public function testValidatesPayload()
    {
        $this->actingAsBot();

        $this->postJson('/api/register-whatsapp-id')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['blok_number', 'whatsapp_id', 'phone_number']);
    }
}
