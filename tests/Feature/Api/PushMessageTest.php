<?php

namespace Tests\Feature\Api;

use App\Models\PushMessage;
use App\Services\BotMonitorService;

class PushMessageTest extends ApiTestCase
{
    public function testReturnsAndDeletesTheOldestMessageOfTheOrganization()
    {
        $otherOrganization = $this->createOrganization();

        $foreign = $this->createPushMessage($otherOrganization->id, 'foreign');
        $oldest = $this->createPushMessage($this->organization->id, 'first');
        $newest = $this->createPushMessage($this->organization->id, 'second');

        $this->mock(BotMonitorService::class)
            ->shouldReceive('recordEvent')
            ->once()
            ->withArgs(fn ($organizationId, $type, $content) => $organizationId === $this->organization->id
                && $type === 'log'
                && str_contains($content, 'first'));

        $this->actingAsBot();

        $this->getJson('/api/push-messages')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'message_type' => 'group',
                'recipient_id' => '12345@g.us',
                'message' => 'first',
            ]);

        $this->assertModelMissing($oldest);
        $this->assertModelExists($newest);
        $this->assertModelExists($foreign);
    }

    public function testReportsWhenQueueIsEmpty()
    {
        $this->createPushMessage($this->createOrganization()->id, 'foreign');

        $this->mock(BotMonitorService::class)->shouldNotReceive('recordEvent');

        $this->actingAsBot();

        $this->getJson('/api/push-messages')
            ->assertOk()
            ->assertExactJson([
                'success' => false,
                'message' => 'No push messages found.',
            ]);
    }

    private function createPushMessage(int $organizationId, string $content): PushMessage
    {
        return PushMessage::forceCreate([
            'organization_id' => $organizationId,
            'message_type' => 'group',
            'recipient_id' => '12345@g.us',
            'message_content' => $content,
        ]);
    }
}
