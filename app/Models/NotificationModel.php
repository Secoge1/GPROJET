<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class NotificationModel extends Model
{
    protected string $table = 'notifications';

    public function create(int $utilisateurId, string $type, string $titre, string $contenu = '', string $lien = ''): int
    {
        $id = $this->insert([
            'utilisateur_id' => $utilisateurId,
            'type' => $type,
            'titre' => $titre,
            'contenu' => $contenu,
            'lien' => $lien,
        ]);
        if ($id > 0 && $utilisateurId > 0) {
            $uid = $utilisateurId;
            $t   = $titre;
            $c   = $contenu;
            $l   = $lien;
            $nid = $id;
            register_shutdown_function(static function () use ($uid, $t, $c, $l, $nid): void {
                try {
                    (new \App\Services\PushNotificationService())->notifyAfterInAppNotification($uid, $t, $c, $l, $nid);
                } catch (\Throwable $e) {
                    /* push ne doit pas impacter la requête principale */
                }
            });
        }

        return $id;
    }

    /**
     * Crée des notifications in-app en masse pour tous les experts/professeurs
     * concernés par une nouvelle demande (max 200).
     *
     * N'utilise PAS create() pour éviter 200 shutdowns push individuels —
     * le push groupé est géré séparément par PushNotificationService::notifyNouvelleDemandeAuxExperts().
     */
    public function batchNotifyExpertsNouvelleDemandeInApp(int $demandeId, string $titre, ?int $competenceId): int
    {
        try {
            $db = \App\Core\Database::getInstance();

            if ($competenceId && $competenceId > 0) {
                $stmt = $db->prepare("
                    SELECT DISTINCT u.id
                    FROM utilisateurs u
                    LEFT JOIN profils_experts pe ON pe.utilisateur_id = u.id
                    LEFT JOIN expert_competences ec
                           ON ec.expert_id = pe.id AND ec.competence_id = :cid
                    WHERE u.role IN ('expert', 'prestataire', 'professeur')
                      AND (ec.competence_id IS NOT NULL OR u.role = 'professeur')
                    LIMIT 200
                ");
                $stmt->execute([':cid' => $competenceId]);
            } else {
                $stmt = $db->prepare("
                    SELECT u.id
                    FROM utilisateurs u
                    WHERE u.role IN ('expert', 'prestataire', 'professeur')
                    LIMIT 200
                ");
                $stmt->execute();
            }

            $userIds = $stmt->fetchAll(\PDO::FETCH_COLUMN) ?: [];
            if (empty($userIds)) {
                return 0;
            }

            $lien    = '/expert/demandes';
            $type    = 'nouvelle_demande';
            $contenu = 'Une nouvelle mission est disponible : ' . mb_substr($titre, 0, 100);
            $now     = date('Y-m-d H:i:s');

            $placeholders = implode(',', array_fill(0, count($userIds), '(?,?,?,?,?,?)'));
            $values = [];
            foreach ($userIds as $uid) {
                array_push($values, (int) $uid, $type, mb_substr($titre, 0, 255), $contenu, $lien, $now);
            }

            $db->prepare(
                "INSERT INTO notifications (utilisateur_id, type, titre, contenu, lien, created_at)
                 VALUES {$placeholders}"
            )->execute($values);

            return count($userIds);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getNonLues(int $utilisateurId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$utilisateurId, $limit]);
        return $stmt->fetchAll();
    }

    public function getToutes(int $utilisateurId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE utilisateur_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$utilisateurId, $limit]);
        return $stmt->fetchAll();
    }

    public function marquerLues(int $utilisateurId): void
    {
        $this->db->prepare("UPDATE {$this->table} SET lu = 1 WHERE utilisateur_id = ?")->execute([$utilisateurId]);
    }

    public function marquerLuesParType(int $utilisateurId, string $type): void
    {
        $this->db->prepare("UPDATE {$this->table} SET lu = 1 WHERE utilisateur_id = ? AND lu = 0 AND type = ?")
            ->execute([$utilisateurId, $type]);
    }

    public function marquerLuesParTypes(int $utilisateurId, array $types): void
    {
        if ($types === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $params = array_merge([$utilisateurId], $types);
        $this->db->prepare(
            "UPDATE {$this->table} SET lu = 1 WHERE utilisateur_id = ? AND lu = 0 AND type IN ({$placeholders})"
        )->execute($params);
    }

    /** Marque les notifs « message chat » liées à une réservation (ouverture de la conversation). */
    public function marquerLuesMessageChatPourReservation(int $utilisateurId, int $reservationId): void
    {
        if ($reservationId < 1) {
            return;
        }
        $like = '%/conversation/' . $reservationId . '%';
        $this->db->prepare(
            "UPDATE {$this->table} SET lu = 1 WHERE utilisateur_id = ? AND lu = 0 AND type = 'message_chat' AND lien LIKE ?"
        )->execute([$utilisateurId, $like]);
    }

    /**
     * Tente d'extraire un reservation_id depuis le champ lien (messages, client, query ?r=).
     */
    public static function extractReservationIdFromLien(?string $lien): ?int
    {
        if ($lien === null || $lien === '') {
            return null;
        }
        if (preg_match('#/messages/conversation/(\d+)#', $lien, $m)) {
            return (int) $m[1];
        }
        if (preg_match('#/conversation/(\d+)#', $lien, $m)) {
            return (int) $m[1];
        }
        if (preg_match('#/client/reservations/(\d+)#', $lien, $m)) {
            return (int) $m[1];
        }
        if (preg_match('#[?&]r=(\d+)#', $lien, $m)) {
            return (int) $m[1];
        }
        if (preg_match('#/reservations/(\d+)(?:[^0-9]|$)#', $lien, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /** IDs de réservation ayant au moins une notif message non lue (pour pastilles dans la liste). */
    public function getReservationIdsWithUnreadMessages(int $utilisateurId): array
    {
        $stmt = $this->db->prepare(
            "SELECT lien FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0 AND type = 'message_chat'"
        );
        $stmt->execute([$utilisateurId]);
        $ids = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rid = self::extractReservationIdFromLien($row['lien'] ?? '');
            if ($rid !== null && $rid > 0) {
                $ids[$rid] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /** IDs de réservation pour notifs « mission / réservation » non lues (lien parseable). */
    public function getReservationIdsWithUnreadReservationNotifs(int $utilisateurId): array
    {
        $types = self::typesReservationOuMission();
        if ($types === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $params = array_merge([$utilisateurId], $types);
        $stmt = $this->db->prepare(
            "SELECT lien FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0 AND type IN ({$placeholders})"
        );
        $stmt->execute($params);
        $ids = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $rid = self::extractReservationIdFromLien($row['lien'] ?? '');
            if ($rid !== null && $rid > 0) {
                $ids[$rid] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    /** Marque les notifs mission/réservation liées à une réservation (aperçu du détail client). */
    public function marquerLuesReservationNotifsPourReservation(int $utilisateurId, int $reservationId): void
    {
        if ($reservationId < 1) {
            return;
        }
        $types = self::typesReservationOuMission();
        if ($types === []) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $likeClient = '%/client/reservations/' . $reservationId . '%';
        $likeRes = '%/reservations/' . $reservationId . '%';
        $likeR = '%r=' . $reservationId . '%';
        $params = array_merge(
            [$utilisateurId],
            $types,
            [$likeClient, $likeRes, $likeR]
        );
        $this->db->prepare(
            "UPDATE {$this->table} SET lu = 1 WHERE utilisateur_id = ? AND lu = 0 AND type IN ({$placeholders}) AND (lien LIKE ? OR lien LIKE ? OR lien LIKE ?)"
        )->execute($params);
    }

    public function countNonLues(int $utilisateurId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0");
        $stmt->execute([$utilisateurId]);
        return (int) $stmt->fetchColumn();
    }

    /** Notifications non lues pour un type précis (ex. message_chat). */
    public function countNonLuesByType(int $utilisateurId, string $type): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0 AND type = ?");
        $stmt->execute([$utilisateurId, $type]);
        return (int) $stmt->fetchColumn();
    }

    /** Notifications non lues parmi plusieurs types (réservations, missions, etc.). */
    public function countNonLuesByTypes(int $utilisateurId, array $types): int
    {
        if ($types === []) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $params = array_merge([$utilisateurId], $types);
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0 AND type IN ({$placeholders})"
        );
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Types comptés pour la pastille « réservations / missions » (hors messages chat). */
    public static function typesReservationOuMission(): array
    {
        return [
            'nouvelle_reservation', 'paiement_recu', 'session_terminee', 'acceptee', 'refusee',
            'livraison_travail', 'expert_accepte_urgence', 'avis_client', 'session_professeur', 'mission_urgence',
        ];
    }

    /** Dernière notification non lue (la plus récente), pour le polling (son / aperçu). */
    public function getLastNonLue(int $utilisateurId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, type, titre, contenu, lien FROM {$this->table} WHERE utilisateur_id = ? AND lu = 0 ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$utilisateurId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
