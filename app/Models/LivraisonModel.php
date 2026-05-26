<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * Livraisons de travaux : fichiers office ou liens vidéo externes envoyés par les experts.
 */
class LivraisonModel extends Model
{
    protected string $table = 'livraisons';

    /** Extensions autorisées pour l'upload direct (pas de vidéos). */
    public const EXT_AUTORISEES = [
        'pdf',
        'doc', 'docx',
        'xls', 'xlsx',
        'ppt', 'pptx',
        'mdb', 'accdb',
        'odt', 'ods', 'odp',
        'rtf', 'txt', 'csv',
        'zip', 'rar',
    ];

    public const MIME_MAP = [
        'pdf'   => 'application/pdf',
        'doc'   => 'application/msword',
        'docx'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'   => 'application/vnd.ms-excel',
        'xlsx'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'   => 'application/vnd.ms-powerpoint',
        'pptx'  => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'mdb'   => 'application/vnd.ms-access',
        'accdb' => 'application/vnd.ms-access',
        'odt'   => 'application/vnd.oasis.opendocument.text',
        'ods'   => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp'   => 'application/vnd.oasis.opendocument.presentation',
        'rtf'   => 'application/rtf',
        'txt'   => 'text/plain',
        'csv'   => 'text/csv',
        'zip'   => 'application/zip',
        'rar'   => 'application/x-rar-compressed',
    ];

    /** Taille max par fichier : 20 Mo */
    public const MAX_SIZE = 20 * 1024 * 1024;

    public function create(array $data): int
    {
        return $this->insert($data);
    }

    /** Toutes les livraisons d'une réservation, les plus récentes en premier. */
    public function getByReservation(int $reservationId): array
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, pe.titre as expert_titre, u.prenom as expert_prenom, u.nom as expert_nom
             FROM {$this->table} l
             JOIN profils_experts pe ON pe.id = l.expert_id
             JOIN utilisateurs    u  ON u.id  = pe.utilisateur_id
             WHERE l.reservation_id = ?
             ORDER BY l.created_at DESC"
        );
        $stmt->execute([$reservationId]);
        return $stmt->fetchAll();
    }

    /** Nombre de livraisons d'une réservation. */
    public function countByReservation(int $reservationId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE reservation_id = ?"
        );
        $stmt->execute([$reservationId]);
        return (int) $stmt->fetchColumn();
    }

    /** Récupère une livraison avec ses infos (réservation + expert + client). */
    public function findWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, r.client_id, pe.utilisateur_id as expert_user_id
             FROM {$this->table} l
             JOIN reservations    r  ON r.id  = l.reservation_id
             JOIN profils_experts pe ON pe.id = l.expert_id
             WHERE l.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
