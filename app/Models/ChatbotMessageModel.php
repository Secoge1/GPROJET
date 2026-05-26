<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ChatbotMessageModel extends Model
{
    protected string $table = 'chatbot_messages';

    public function add(int $conversationId, string $role, string $content, ?string $intent = null, ?array $payload = null): int
    {
        return $this->insert([
            'conversation_id' => $conversationId,
            'role' => $role,
            'content' => $content,
            'intent' => $intent,
            'payload' => $payload !== null ? json_encode($payload) : null,
        ]);
    }

    /** @return array<array{role: string, content: string, intent?: string}> */
    public function getHistory(int $conversationId, int $limit = 20): array
    {
        $stmt = $this->db->prepare(
            "SELECT role, content, intent FROM {$this->table} WHERE conversation_id = ? ORDER BY id DESC LIMIT " . (int) $limit
        );
        $stmt->execute([$conversationId]);
        $rows = array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC));
        foreach ($rows as &$r) {
            unset($r['intent']); // optional for prompt
        }
        return $rows;
    }
}
