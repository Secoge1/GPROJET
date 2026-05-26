<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ChatbotConversationModel extends Model
{
    protected string $table = 'chatbot_conversations';

    public function findByUid(string $conversationUid): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE conversation_uid = ? LIMIT 1");
        $stmt->execute([$conversationUid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getOrCreate(string $conversationUid, ?int $utilisateurId = null, ?string $sessionId = null): array
    {
        $conv = $this->findByUid($conversationUid);
        if ($conv) {
            if ($utilisateurId !== null && (int) ($conv['utilisateur_id'] ?? 0) === 0) {
                $this->update((int) $conv['id'], ['utilisateur_id' => $utilisateurId]);
                $conv['utilisateur_id'] = (string) $utilisateurId;
            }
            return $conv;
        }
        $id = $this->insert([
            'conversation_uid' => $conversationUid,
            'utilisateur_id' => $utilisateurId,
            'session_id' => $sessionId,
            'context' => json_encode(new \stdClass()),
        ]);
        $row = $this->find($id);
        return $row ?? [];
    }

    public function updateContext(int $conversationId, array $context): void
    {
        $this->update($conversationId, ['context' => json_encode($context)]);
    }

    public function getContext(int $conversationId): array
    {
        $row = $this->find($conversationId);
        if (!$row || empty($row['context'])) {
            return [];
        }
        $decoded = json_decode($row['context'], true);
        return is_array($decoded) ? $decoded : [];
    }
}
