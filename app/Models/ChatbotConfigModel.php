<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class ChatbotConfigModel extends Model
{
    protected string $table = 'chatbot_config';

    protected string $primaryKey = 'id';

    public function getByKey(string $cle): ?string
    {
        $stmt = $this->db->prepare("SELECT valeur FROM {$this->table} WHERE cle = ? LIMIT 1");
        $stmt->execute([$cle]);
        $v = $stmt->fetchColumn();
        return $v !== false ? (string) $v : null;
    }

    public function getSystemPrompt(): string
    {
        return $this->getByKey('system_prompt') ?: 'Tu es l\'assistant de GLOBALO. Réponds en français.';
    }

    public function getDefaultResponse(string $intent): ?string
    {
        return $this->getByKey('default_' . $intent);
    }

    public function getHelpContent(string $topic): ?string
    {
        return $this->getByKey('help_' . $topic);
    }

    public function setKey(string $cle, string $valeur): void
    {
        $stmt = $this->db->prepare("INSERT INTO {$this->table} (cle, valeur) VALUES (?, ?) ON DUPLICATE KEY UPDATE valeur = ?, updated_at = NOW()");
        $stmt->execute([$cle, $valeur, $valeur]);
    }

    public function getAllKeys(): array
    {
        $stmt = $this->db->query("SELECT cle, valeur FROM {$this->table}");
        $out = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $out[$row['cle']] = $row['valeur'];
        }
        return $out;
    }
}
