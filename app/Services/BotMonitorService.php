<?php

namespace App\Services;

class BotMonitorService
{
    /** Max log entries kept per organization; oldest entries are dropped on overflow. */
    private const MAX_LOG_ENTRIES = 500;

    /** heartbeat interval the bot uses by default (see BOT_MONITORING_API.md) */
    private const HEARTBEAT_INTERVAL_SECONDS = 180;

    private function orgDir(int $organizationId): string
    {
        return storage_path("app/bot/{$organizationId}");
    }

    private function logPath(int $organizationId): string
    {
        return $this->orgDir($organizationId) . '/log.json';
    }

    private function statePath(int $organizationId): string
    {
        return $this->orgDir($organizationId) . '/state.json';
    }

    private function ensureDir(int $organizationId): void
    {
        $dir = $this->orgDir($organizationId);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Read-modify-write a JSON file under an exclusive lock so concurrent
     * requests (e.g. a log event racing a command being queued) don't clobber
     * each other. Returns whatever the callback returns.
     */
    private function withLock(string $path, callable $callback)
    {
        $handle = fopen($path, 'c+');
        flock($handle, LOCK_EX);

        $contents = stream_get_contents($handle);
        $data = $contents !== '' ? json_decode($contents, true) : null;
        if (!is_array($data)) {
            $data = [];
        }

        $result = $callback($data);
        $newData = $result['data'];

        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($newData));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        return $result['return'] ?? null;
    }

    private function defaultState(): array
    {
        return [
            'next_id' => 1,
            'session_id' => null,
            'last_event_type' => null,
            'last_event_content' => null,
            'last_event_timestamp' => null,
            'last_seen_at' => null,
            'last_ready_at' => null,
            'last_login_at' => null,
            'latest_qr' => null,
            'latest_qr_at' => null,
            'pending_command' => null,
        ];
    }

    /**
     * Record an event reported by the bot and return the command (if any)
     * that should be sent back in the response. Consumes (clears) the
     * pending command atomically so it is only ever delivered once.
     */
    public function recordEvent(int $organizationId, string $type, string $content, ?string $sessionId, ?string $timestamp): ?string
    {
        $this->ensureDir($organizationId);
        $now = now()->toIso8601String();

        $command = $this->withLock($this->statePath($organizationId), function ($state) use ($organizationId, $type, $content, $sessionId, $timestamp, $now) {
            $state = array_merge($this->defaultState(), $state);

            $entryId = $state['next_id'];
            $state['next_id'] = $entryId + 1;
            $state['session_id'] = $sessionId ?: $state['session_id'];
            $state['last_event_type'] = $type;
            $state['last_event_content'] = $content;
            $state['last_event_timestamp'] = $timestamp;
            $state['last_seen_at'] = $now;

            if ($type === 'ready') {
                $state['last_ready_at'] = $now;
            }

            if ($type === 'login') {
                $state['last_login_at'] = $now;
                $state['latest_qr'] = $content;
                $state['latest_qr_at'] = $now;
            }

            $command = $state['pending_command'];
            $state['pending_command'] = null;

            $this->appendLog($organizationId, [
                'id' => $entryId,
                'type' => $type,
                'content' => $content,
                'session_id' => $sessionId,
                'timestamp' => $timestamp,
                'received_at' => $now,
            ]);

            return ['data' => $state, 'return' => $command];
        });

        return $command;
    }

    private function appendLog(int $organizationId, array $entry): void
    {
        $this->withLock($this->logPath($organizationId), function ($entries) use ($entry) {
            $entries[] = $entry;

            if (count($entries) > self::MAX_LOG_ENTRIES) {
                $entries = array_slice($entries, -self::MAX_LOG_ENTRIES);
            }

            return ['data' => $entries];
        });
    }

    public function queueCommand(int $organizationId, string $command): void
    {
        $this->ensureDir($organizationId);

        $this->withLock($this->statePath($organizationId), function ($state) use ($command) {
            $state = array_merge($this->defaultState(), $state);
            $state['pending_command'] = $command;

            return ['data' => $state];
        });
    }

    public function getState(int $organizationId): array
    {
        $path = $this->statePath($organizationId);
        if (!file_exists($path)) {
            return $this->defaultState();
        }

        $data = json_decode(file_get_contents($path), true);

        return array_merge($this->defaultState(), is_array($data) ? $data : []);
    }

    /**
     * Log entries with id > $afterId, oldest first. Used for ajax polling
     * so the client only pulls what it hasn't already seen.
     */
    public function getLogsAfter(int $organizationId, int $afterId = 0): array
    {
        $path = $this->logPath($organizationId);
        if (!file_exists($path)) {
            return [];
        }

        $entries = json_decode(file_get_contents($path), true);
        if (!is_array($entries)) {
            return [];
        }

        return array_values(array_filter($entries, fn ($entry) => ($entry['id'] ?? 0) > $afterId));
    }

    public function computeStatus(array $state): string
    {
        if (empty($state['last_event_type'])) {
            return 'unknown';
        }

        if ($state['last_event_type'] === 'error') {
            return 'error';
        }

        $waitingForLogin = $state['last_login_at']
            && (!$state['last_ready_at'] || $state['last_login_at'] > $state['last_ready_at']);

        if ($waitingForLogin) {
            return 'waiting_for_login';
        }

        if (in_array($state['last_event_type'], ['heartbeat', 'ready'], true) && $state['last_seen_at']) {
            $secondsSinceSeen = now()->diffInSeconds(\Carbon\Carbon::parse($state['last_seen_at']));
            if ($secondsSinceSeen <= self::HEARTBEAT_INTERVAL_SECONDS * 2) {
                return 'online';
            }
        }

        return 'offline';
    }
}
