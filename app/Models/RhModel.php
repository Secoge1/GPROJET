<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Modèle du module RH avec IA intégrée
 * Colonnes réelles vérifiées sur schema.sql :
 *   utilisateurs       : avatar, actif, pays, created_at, role, nom, email
 *   profils_experts    : utilisateur_id, titre, description, tarif_horaire, valide_par_admin, disponible
 *   profils_professeurs: utilisateur_id, valide_par_admin, disponible
 *   user_tracking      : utilisateur_id, created_at
 */
class RhModel
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ──────────────────────────────────────────────────────────────────
    // STATISTIQUES GLOBALES
    // ──────────────────────────────────────────────────────────────────

    public function getStatsGlobales(): array
    {
        $stats = [];
        try {
            $roles = ['professeur', 'etudiant', 'client', 'expert'];
            foreach ($roles as $role) {
                $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE role = ?");
                $stmt->execute([$role]);
                $stats['total_' . $role . 's'] = (int) $stmt->fetchColumn();
            }
            // Alias sans 's' pour certains rôles
            $stats['total_experts']     = $stats['total_experts'];
            $stats['total_professeurs'] = $stats['total_professeurs'];
            $stats['total_etudiants']   = $stats['total_etudiants'];
            $stats['total_clients']     = $stats['total_clients'];

            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM utilisateurs WHERE MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())"
            );
            $stats['inscrits_ce_mois'] = (int) $stmt->fetchColumn();

            $stmt = $this->db->query(
                "SELECT COUNT(*) FROM utilisateurs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );
            $stats['inscrits_cette_semaine'] = (int) $stmt->fetchColumn();

            // Actifs 30j basé sur user_tracking (colonne : utilisateur_id)
            $stmt = $this->db->query(
                "SELECT COUNT(DISTINCT utilisateur_id) FROM user_tracking WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND utilisateur_id IS NOT NULL"
            );
            $stats['actifs_30j'] = (int) $stmt->fetchColumn();

        } catch (\Throwable $e) {
            $stats = array_merge([
                'total_professeurs' => 0, 'total_etudiants' => 0,
                'total_clients' => 0, 'total_experts' => 0,
                'inscrits_ce_mois' => 0, 'inscrits_cette_semaine' => 0, 'actifs_30j' => 0,
            ], $stats);
        }
        return $stats;
    }

    // ──────────────────────────────────────────────────────────────────
    // INSCRIPTIONS (Professeurs & Étudiants)
    // ──────────────────────────────────────────────────────────────────

    public function getInscriptionsRecentes(string $role, int $limit = 20): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT u.id,
                        CONCAT(COALESCE(u.prenom,''), ' ', COALESCE(u.nom,'')) AS nom,
                        u.prenom, u.nom AS nom_famille,
                        u.email, u.created_at, u.actif,
                        u.avatar AS photo,
                        COALESCE(u.pays, '') AS pays
                 FROM utilisateurs u
                 WHERE u.role = :role
                 ORDER BY u.created_at DESC
                 LIMIT :limit"
            );
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getStatsInscriptions(): array
    {
        $stats = [];
        try {
            $rows = $this->db->query(
                "SELECT role, COUNT(*) AS nb
                 FROM utilisateurs
                 WHERE role IN ('professeur','etudiant')
                   AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                 GROUP BY role"
            )->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $stats['semaine_' . $r['role']] = (int) $r['nb'];
            }

            // Utilise valide_par_admin (colonne réelle dans profils_professeurs)
            $stats['profs_valides'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM profils_professeurs WHERE valide_par_admin = 1"
            )->fetchColumn();

            $stats['profs_en_attente'] = (int) $this->db->query(
                "SELECT COUNT(*) FROM profils_professeurs WHERE valide_par_admin = 0"
            )->fetchColumn();

        } catch (\Throwable $e) {
            $stats = array_merge([
                'semaine_professeur' => 0, 'semaine_etudiant' => 0,
                'profs_valides' => 0, 'profs_en_attente' => 0,
            ], $stats);
        }
        return $stats;
    }

    // ──────────────────────────────────────────────────────────────────
    // PROFILS (Clients & Experts)
    // ──────────────────────────────────────────────────────────────────

    public function getProfilsAvecScore(string $role, int $limit = 20): array
    {
        try {
            if ($role === 'expert') {
                // Colonnes réelles : description, tarif_horaire, valide_par_admin, titre
                $stmt = $this->db->prepare(
                    "SELECT u.id, u.nom, u.email, u.avatar AS photo, COALESCE(u.pays,'') AS pays, u.created_at,
                            pe.titre AS specialite, pe.tarif_horaire AS tarif_heure, pe.valide_par_admin AS valide,
                            pe.description AS bio, pe.niveau_experience,
                            (
                                CASE WHEN u.avatar IS NOT NULL AND u.avatar != '' THEN 20 ELSE 0 END +
                                CASE WHEN pe.description IS NOT NULL AND LENGTH(pe.description) > 50 THEN 20 ELSE 0 END +
                                CASE WHEN pe.tarif_horaire > 0 THEN 20 ELSE 0 END +
                                CASE WHEN pe.titre IS NOT NULL AND pe.titre != '' THEN 20 ELSE 0 END +
                                CASE WHEN (SELECT COUNT(*) FROM expert_competences ec WHERE ec.expert_id = pe.id) > 0 THEN 20 ELSE 0 END
                            ) AS score_profil
                     FROM utilisateurs u
                     LEFT JOIN profils_experts pe ON pe.utilisateur_id = u.id
                     WHERE u.role = 'expert'
                     ORDER BY score_profil DESC, u.created_at DESC
                     LIMIT :limit"
                );
            } else {
                $stmt = $this->db->prepare(
                    "SELECT u.id, u.nom, u.email, u.avatar AS photo, COALESCE(u.pays,'') AS pays, u.created_at,
                            NULL AS specialite, NULL AS tarif_heure, 1 AS valide, NULL AS bio, NULL AS niveau_experience,
                            (
                                CASE WHEN u.avatar IS NOT NULL AND u.avatar != '' THEN 50 ELSE 0 END +
                                CASE WHEN u.pays IS NOT NULL AND u.pays != '' THEN 30 ELSE 0 END +
                                CASE WHEN u.nom IS NOT NULL AND LENGTH(u.nom) > 2 THEN 20 ELSE 0 END
                            ) AS score_profil
                     FROM utilisateurs u
                     WHERE u.role = 'client'
                     ORDER BY score_profil DESC, u.created_at DESC
                     LIMIT :limit"
                );
            }
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // MARKETING
    // ──────────────────────────────────────────────────────────────────

    public function getSegmentsMarketing(): array
    {
        $data = [];
        try {
            $data['par_pays'] = $this->db->query(
                "SELECT COALESCE(pays, 'Inconnu') AS pays, COUNT(*) AS nb
                 FROM utilisateurs
                 WHERE pays IS NOT NULL AND pays != ''
                 GROUP BY pays ORDER BY nb DESC LIMIT 10"
            )->fetchAll(\PDO::FETCH_ASSOC);

            $data['par_role'] = $this->db->query(
                "SELECT role, COUNT(*) AS nb FROM utilisateurs GROUP BY role ORDER BY nb DESC"
            )->fetchAll(\PDO::FETCH_ASSOC);

            $data['evolution'] = $this->db->query(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') AS mois, COUNT(*) AS nb
                 FROM utilisateurs
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                 GROUP BY mois ORDER BY mois ASC"
            )->fetchAll(\PDO::FETCH_ASSOC);

            $data['domaines_email'] = $this->db->query(
                "SELECT SUBSTRING_INDEX(email, '@', -1) AS domaine, COUNT(*) AS nb
                 FROM utilisateurs
                 GROUP BY domaine ORDER BY nb DESC LIMIT 5"
            )->fetchAll(\PDO::FETCH_ASSOC);

        } catch (\Throwable $e) {
            $data = array_merge(['par_pays' => [], 'par_role' => [], 'evolution' => [], 'domaines_email' => []], $data);
        }
        return $data;
    }

    public function getRecommandationsMarketing(): array
    {
        try {
            return $this->db->query(
                "SELECT * FROM rh_marketing_recommandations ORDER BY priorite DESC, created_at DESC LIMIT 20"
            )->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function saveRecommandation(array $data): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO rh_marketing_recommandations (segment, titre, description, action_cle, priorite, admin_id)
                 VALUES (:segment, :titre, :description, :action_cle, :priorite, :admin_id)"
            );
            return $stmt->execute($data);
        } catch (\Throwable $e) {
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // IA LOGS
    // ──────────────────────────────────────────────────────────────────

    public function saveIaLog(string $agentType, int $adminId, string $sessionId, string $role, string $message): bool
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO rh_ia_logs (agent_type, admin_id, session_id, role, message)
                 VALUES (:at, :ai, :si, :r, :m)"
            );
            return $stmt->execute([
                ':at' => $agentType,
                ':ai' => $adminId,
                ':si' => $sessionId,
                ':r'  => $role,
                ':m'  => $message,
            ]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function getIaHistory(string $sessionId, string $agentType): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT role, message FROM rh_ia_logs
                 WHERE session_id = :si AND agent_type = :at
                 ORDER BY created_at ASC LIMIT 30"
            );
            $stmt->execute([':si' => $sessionId, ':at' => $agentType]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // MANAGER — Données consolidées
    // ──────────────────────────────────────────────────────────────────

    public function getManagerDashboard(): array
    {
        $data = [];
        try {
            $data['stats']              = $this->getStatsGlobales();
            $data['inscriptions_stats'] = $this->getStatsInscriptions();
            $data['segments']           = $this->getSegmentsMarketing();

            $data['alertes'] = [];
            $profsEnAttente  = (int) ($data['inscriptions_stats']['profs_en_attente'] ?? 0);
            if ($profsEnAttente > 0) {
                $data['alertes'][] = ['type' => 'warning', 'message' => "{$profsEnAttente} professeur(s) en attente de validation"];
            }
            $inscSemaine = ((int) ($data['inscriptions_stats']['semaine_professeur'] ?? 0))
                         + ((int) ($data['inscriptions_stats']['semaine_etudiant']   ?? 0));
            if ($inscSemaine > 0) {
                $data['alertes'][] = ['type' => 'info', 'message' => "{$inscSemaine} nouvelle(s) inscription(s) cette semaine"];
            }

        } catch (\Throwable $e) {
            $data = ['stats' => [], 'inscriptions_stats' => [], 'segments' => [], 'alertes' => []];
        }
        return $data;
    }
}
