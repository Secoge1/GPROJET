<?php
/**
 * GLOBALO - Back-office Administration
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Security;
use App\Core\Database;
use App\Models\UtilisateurModel;
use App\Models\ProfilExpertModel;
use App\Models\ProfilProfesseurModel;
use App\Models\MatiereModel;
use App\Models\ParametreModel;
use App\Models\ReservationModel;
use App\Models\PaiementModel;
use App\Models\CommissionConfigModel;
use App\Models\SoldePlateformeModel;
use App\Models\ChatbotConfigModel;
use App\Models\ParrainageModel;
use App\Models\DemandeRetraitModel;
use App\Models\RetraitProfModel;
use App\Models\AbonnementModel;
use App\Models\DemandeModel;
use App\Models\TransactionModel;
use App\Services\IntouchPaymentService;
use App\Services\SocialPublisherService;

class AdminController extends Controller
{
    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $userModel = new UtilisateurModel();
        $reservationModel = new ReservationModel();
        $paiementModel = new PaiementModel();
        $stats = [
            'total_utilisateurs' => $userModel->countAll(),
            'total_experts' => $this->countExperts(),
            'total_experts_valides' => $this->countExpertsValides(),
            'total_reservations' => $this->countReservations(),
            'total_demandes' => $this->countDemandes(),
            'total_commissions' => $this->getTotalCommissions(),
        ];
        try {
            $stats['total_professeurs'] = $userModel->countByRole('professeur');
            $stats['total_etudiants']   = $userModel->countByRole('etudiant');
            $stats['total_clients']     = $userModel->countByRole('client');
        } catch (\Throwable $e) {
            $stats['total_professeurs'] = 0;
            $stats['total_etudiants']   = 0;
            $stats['total_clients']     = 0;
        }
        $recentUsers = $userModel->getAllWithRole(null, 8);
        $recentReservations = $reservationModel->getRecentForAdmin(8);

        $paymentStats = [
            'paiements' => [],
            'transactions' => [],
            'retraits_experts' => 0,
            'retraits_professeurs' => 0,
        ];
        try {
            $paymentStats['paiements'] = $paiementModel->getAdminDashboardStats();
        } catch (\Throwable $e) {
            $paymentStats['paiements'] = [];
        }
        try {
            $txModel = new TransactionModel();
            $txStats = $txModel->getStats();
            $paymentStats['transactions'] = [
                'total' => (int) ($txStats['total'] ?? 0),
                'pending' => (int) ($txStats['pending'] ?? 0),
                'success' => (int) ($txStats['success'] ?? 0),
                'failed' => (int) ($txStats['failed'] ?? 0),
                'pending_sans_code' => $txModel->countPendingSansCode(),
                'pending_a_valider' => $txModel->countPendingAValider(),
                'total_collecte' => (float) ($txStats['total_collecte'] ?? 0),
                'total_commission' => (float) ($txStats['total_commission'] ?? 0),
            ];
        } catch (\Throwable $e) {
            $paymentStats['transactions'] = [];
        }
        try {
            $paymentStats['retraits_experts'] = (new DemandeRetraitModel())->countEnAttente();
        } catch (\Throwable $e) {
            $paymentStats['retraits_experts'] = 0;
        }
        try {
            $paymentStats['retraits_professeurs'] = (new RetraitProfModel())->countEnAttente();
        } catch (\Throwable $e) {
            $paymentStats['retraits_professeurs'] = 0;
        }

        $trackingModel = new \App\Models\UserTrackingModel();
        $visitorStats = [
            'today_views'    => $trackingModel->getTodayPageViews(),
            'today_unique'   => $trackingModel->getUniqueVisitorsToday(),
            'last7days'      => $trackingModel->getVisitsLast7Days(),
            'countries'      => $trackingModel->getCountryStats(30),
            'devices'        => $trackingModel->getDeviceAndBrowserStats(30),
        ];

        $this->render('index', [
            'pageTitle' => 'Administration - GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'recentReservations' => $recentReservations,
            'payment_stats' => $paymentStats,
            'visitor_stats' => $visitorStats,
            'migration_professeur_needed' => $this->checkMigrationProfesseur(),
        ]);
    }

    /** Tableau de bord Growth : trafic, SEO, conversions, parrainages. */
    public function growth(): void
    {
        // --- Parrainage ---
        $referralStats = ['total_parrainages' => 0, 'total_inscrits' => 0, 'total_recompenses' => 0];
        try {
            $referralStats = (new ParrainageModel())->getAdminStats();
        } catch (\Throwable $e) {}

        // --- Métriques principales ---
        $totalUsers        = 0;
        $totalReservations = 0;
        try {
            $totalUsers        = (new UtilisateurModel())->countAll();
            $totalReservations = $this->countReservations();
        } catch (\Throwable $e) {}

        // --- Paiements effectués ---
        $conversionsPaid = 0;
        $db = \App\Core\Database::getInstance();
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM paiements WHERE type = 'paiement_session' AND (statut = 'effectue' OR statut_escrow = 'libere')");
            $conversionsPaid = (int) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM paiements WHERE type = 'paiement_session' AND statut = 'effectue'");
                $conversionsPaid = (int) $stmt->fetchColumn();
            } catch (\Throwable $e2) {}
        }

        // --- Taux de conversion ---
        $tauxReservation = $totalUsers > 0 ? round($totalReservations / $totalUsers * 100, 1) : 0.0;
        $tauxPaiement    = $totalReservations > 0 ? round($conversionsPaid / $totalReservations * 100, 1) : 0.0;
        $tauxGlobal      = $totalUsers > 0 ? round($conversionsPaid / $totalUsers * 100, 1) : 0.0;

        // --- SEO page views (modèle unifié — 1 seule requête) ---
        $growthViewModel = new \App\Models\GrowthPageViewModel();
        $growthStats     = $growthViewModel->getTotalsByType();

        // --- Top vues — JOINs intégrés dans le modèle (plus de N+1) ---
        $topExpertViews = $growthViewModel->getViewsByExpert(10);
        $topJobViews    = $growthViewModel->getViewsByJob(10);
        $topBlogViews   = $growthViewModel->getViewsByBlog(10);

        $this->render('growth', [
            'pageTitle'            => 'Growth - Admin GLOBALO',
            'user'                 => ['id' => Auth::id(), 'role' => 'admin'],
            'total_utilisateurs'   => $totalUsers,
            'total_reservations'   => $totalReservations,
            'conversions_paiements'=> $conversionsPaid,
            'taux_reservation'     => $tauxReservation,
            'taux_paiement'        => $tauxPaiement,
            'taux_global'          => $tauxGlobal,
            'referral'             => $referralStats,
            'growth_stats'         => $growthStats,
            'top_expert_views'     => $topExpertViews,
            'top_job_views'        => $topJobViews,
            'top_blog_views'       => $topBlogViews,
            'ga_id'                => defined('GA_MEASUREMENT_ID') ? GA_MEASUREMENT_ID : '',
            'fb_pixel_id'          => defined('FB_PIXEL_ID') ? FB_PIXEL_ID : '',
            'linkedin_id'          => defined('LINKEDIN_PARTNER_ID') ? LINKEDIN_PARTNER_ID : '',
        ]);
    }

    /** Page Tracking utilisateurs : indicateurs et tendances. */
    /** Diagnostic géolocalisation — accessible sur /admin/geo-debug */
    public function geoDebug(): void
    {
        $rawIp   = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '?';
        $ip      = trim(explode(',', $rawIp)[0]);
        $cf      = $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null;
        $geoip   = $_SERVER['GEOIP_COUNTRY_CODE'] ?? null;
        $detected = \App\Models\UserTrackingModel::getCountryFromRequest();

        $info = [
            'IP brute (REMOTE_ADDR)'         => $_SERVER['REMOTE_ADDR'] ?? '—',
            'IP X-Forwarded-For'             => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '—',
            'IP utilisée pour le lookup'     => $ip,
            'CF-IPCountry (Cloudflare)'      => $cf ?? '— (absent)',
            'GEOIP_COUNTRY_CODE (Apache)'    => $geoip ?? '— (absent)',
            'Pays détecté (résultat final)'  => $detected ?? '— (aucun)',
            'Colonne pays dans DB'           => (new \App\Models\UserTrackingModel())->hasTrackingExtraColumnsPublic() ? 'OUI' : 'NON',
        ];

        header('Content-Type: text/plain; charset=UTF-8');
        foreach ($info as $k => $v) {
            echo str_pad($k, 40) . ' : ' . $v . "\n";
        }
        exit;
    }

    public function tracking(): void
    {
        $limit = min(500, max(50, (int) ($_GET['limit'] ?? 200)));
        $userIdFilter = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int) $_GET['user_id'] : null;
        $activities = [];
        try {
            $activities = (new \App\Models\UserTrackingModel())->getRecent($limit, $userIdFilter);
        } catch (\Throwable $e) {
            // Table user_tracking peut être absente si la migration n'est pas appliquée
        }
        $this->render('tracking', [
            'pageTitle' => 'Tracking utilisateurs - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'activities' => $activities,
            'limit' => $limit,
            'user_id_filter' => $userIdFilter,
        ]);
    }

    /** Supprimer une entrée de tracking (POST). */
    public function deleteTracking(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/tracking');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            try {
                (new \App\Models\UserTrackingModel())->delete($id);
                $_SESSION['flash_success'] = 'Entrée de tracking supprimée.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Impossible de supprimer l\'entrée.';
            }
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/tracking');
    }

    /** Supprimer plusieurs entrées de tracking (POST, ids[]). */
    public function deleteTrackingBulk(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/tracking');
            return;
        }
        $ids = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        $deleted = 0;
        if (!empty($ids)) {
            try {
                $deleted = (new \App\Models\UserTrackingModel())->deleteByIds($ids);
                $_SESSION['flash_success'] = $deleted > 0
                    ? $deleted . ' entrée(s) de tracking supprimée(s).'
                    : 'Aucune entrée à supprimer.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Impossible de supprimer les entrées sélectionnées.';
            }
        } else {
            $_SESSION['flash_error'] = 'Aucune entrée sélectionnée.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/tracking');
    }

    /** Journal des emails automatiques IA (pagination + actions). */
    public function assistantEmails(): void
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $model = new \App\Models\AssistantEmailEventModel();
        $model->ensureTable();
        $total = $model->countAll();
        $rows = $model->getPaginated($offset, $perPage);
        $pages = max(1, (int) ceil($total / $perPage));

        $this->render('assistant_emails', [
            'pageTitle' => 'Emails IA automatiques - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'rows' => $rows,
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
        ]);
    }

    /** Détail d'un email IA automatique envoyé. */
    public function viewAssistantEmail(): void
    {
        $params = $this->router->getParams();
        $id = (int)($params[0] ?? 0);
        $model = new \App\Models\AssistantEmailEventModel();
        $row = $id > 0 ? $model->findById($id) : null;
        if (!$row) {
            $_SESSION['flash_error'] = 'Email IA introuvable.';
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        $this->render('assistant_email_view', [
            'pageTitle' => 'Détail email IA - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'row' => $row,
        ]);
    }

    /** Déclenche manuellement une campagne d'emails IA. */
    public function runAssistantEmailsNow(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        try {
            $service = new \App\Services\ProactiveAssistantMailerService();
            $result = $service->run(false);
            $_SESSION['flash_success'] = "Campagne IA exécutée : {$result['sent']} envoyé(s), {$result['skipped']} ignoré(s), {$result['errors']} erreur(s).";
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = "Échec campagne IA : " . $e->getMessage();
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
    }

    /** Renvoyer un email IA depuis le journal. */
    public function resendAssistantEmail(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        $params = $this->router->getParams();
        $id = (int)($params[0] ?? 0);
        $model = new \App\Models\AssistantEmailEventModel();
        $row = $id > 0 ? $model->findById($id) : null;
        if (!$row) {
            $_SESSION['flash_error'] = 'Email IA introuvable.';
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        $payload = json_decode((string)($row['payload'] ?? ''), true);
        $subject = (string)($row['subject'] ?? ($payload['subject'] ?? 'Notification GLOBALO'));
        $message = (string)($payload['message'] ?? '');
        $to = (string)($row['recipient_email'] ?? '');
        $name = (string)($row['recipient_name'] ?? '');
        if ($to === '' || $message === '') {
            $_SESSION['flash_error'] = 'Impossible de renvoyer (données manquantes).';
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        $ok = (new \App\Services\MailerService())->sendNotification(
            $to,
            $name !== '' ? $name : 'Utilisateur',
            $subject,
            $message,
            rtrim(BASE_URL ?? '', '/') . '/auth/connexion',
            'Accéder à mon compte'
        );
        if ($ok) {
            $model->markResent($id);
            $_SESSION['flash_success'] = 'Email renvoyé avec succès.';
        } else {
            $_SESSION['flash_error'] = 'Échec du renvoi email.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
    }

    /** Marquer un email IA comme résolu. */
    public function resolveAssistantEmail(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        $params = $this->router->getParams();
        $id = (int)($params[0] ?? 0);
        if ($id > 0) {
            (new \App\Models\AssistantEmailEventModel())->markResolved($id);
            $_SESSION['flash_success'] = 'Entrée marquée comme résolue.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
    }

    /** Supprimer définitivement une entrée du journal email IA. */
    public function deleteAssistantEmail(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
            return;
        }
        $params = $this->router->getParams();
        $id = (int)($params[0] ?? 0);
        if ($id > 0) {
            try {
                $deleted = (new \App\Models\AssistantEmailEventModel())->deleteById($id);
                if ($deleted) {
                    $_SESSION['flash_success'] = 'Email IA supprimé du journal.';
                } else {
                    $_SESSION['flash_error'] = 'Entrée introuvable ou déjà supprimée.';
                }
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Impossible de supprimer cette entrée.';
            }
        } else {
            $_SESSION['flash_error'] = 'Identifiant invalide.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/assistant-emails');
    }

    /** Tableau de bord Revenus : commissions, période, experts actifs, missions. */
    public function revenus(): void
    {
        $periode = $_GET['periode'] ?? 'mois';
        $stats = $this->getRevenusStats($periode);
        $soldePlateforme = $this->getSoldePlateformeSiExiste();
        $this->render('revenus', [
            'pageTitle' => 'Revenus - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'stats' => $stats,
            'solde_plateforme' => $soldePlateforme,
            'periode' => $periode,
        ]);
    }

    public function users(): void
    {
        $userModel = new UtilisateurModel();
        $profilModel = new ProfilExpertModel();
        $roleFilter = isset($_GET['role']) && in_array($_GET['role'], ['client', 'expert', 'admin', 'professeur', 'etudiant'], true) ? $_GET['role'] : null;
        $users = $userModel->getAllWithRole($roleFilter, 500);
        foreach ($users as &$u) {
            if (($u['role'] ?? '') === 'expert') {
                $profil = $profilModel->getByUtilisateurId((int) $u['id']);
                $u['expert_profil_id'] = $profil ? (int) $profil['id'] : null;
                $u['expert_slug'] = $profil['slug'] ?? null;
            }
        }
        unset($u);
        $this->render('users', [
            'pageTitle' => 'Utilisateurs - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'users' => $users,
            'roleFilter' => $roleFilter,
        ]);
    }

    public function toggleActif(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id && $id !== Auth::id()) {
            $userModel = new UtilisateurModel();
            $u = $userModel->find($id);
            if ($u) {
                $userModel->update($id, ['actif' => (int)$u['actif'] ? 0 : 1]);
            }
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
    }

    /** Supprimer définitivement un utilisateur (POST). Impossible de se supprimer soi-même. */
    public function deleteUser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id === Auth::id()) {
            $_SESSION['flash_error'] = 'Vous ne pouvez pas supprimer votre propre compte.';
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
            return;
        }
        if ($id > 0) {
            try {
                $deleted = (new UtilisateurModel())->delete($id);
                if ($deleted) {
                    $_SESSION['flash_success'] = 'Utilisateur supprimé.';
                } else {
                    $_SESSION['flash_error'] = 'Impossible de supprimer cet utilisateur (données liées ou inexistant).';
                }
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Impossible de supprimer cet utilisateur (données liées). Utilisez « Désactiver » si besoin.';
            }
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
    }

    /** Modifier identifiants, mot de passe, photo de profil et pièce d'identité d'un utilisateur. */
    public function editUser(): void
    {
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $userModel = new UtilisateurModel();
        $userToEdit = $id ? $userModel->find($id) : null;
        if (!$userToEdit) {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/users');
            return;
        }
        $baseUrl = rtrim(BASE_URL, '/');
        $errors = [];
        $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
        $maxSize = defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (5 * 1024 * 1024); // 5 Mo
        $rolesValides = ['client', 'expert', 'etudiant', 'professeur', 'admin'];
        $oldRole     = (string) ($userToEdit['role'] ?? 'client');
        $newRole     = $oldRole;
        $roleChanged = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = Security::sanitizeEmail($_POST['email'] ?? '');
            $nom = Security::sanitizeString($_POST['nom'] ?? '', 100);
            $prenom = Security::sanitizeString($_POST['prenom'] ?? '', 100);
            $newPassword = $_POST['new_password'] ?? '';
            $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

            // Changement de type de compte
            $newRole = isset($_POST['role']) && in_array($_POST['role'], $rolesValides, true)
                ? $_POST['role']
                : $oldRole;
            $roleChanged = ($newRole !== $oldRole);
            if ($roleChanged && $id === (int) Auth::id()) {
                $errors[] = 'Vous ne pouvez pas modifier votre propre type de compte.';
                $roleChanged = false;
                $newRole = $oldRole;
            }
            if (!Security::validateEmail($email)) {
                $errors[] = 'Adresse email invalide.';
            } else {
                $existing = $userModel->findByEmail($email);
                if ($existing && (int)$existing['id'] !== $id) {
                    $errors[] = 'Cette adresse email est déjà utilisée par un autre compte.';
                }
            }
            if (trim($nom) === '' || trim($prenom) === '') {
                $errors[] = 'Nom et prénom sont requis.';
            }
            if ($newPassword !== '' || $newPasswordConfirm !== '') {
                if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    $errors[] = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
                } elseif ($newPassword !== $newPasswordConfirm) {
                    $errors[] = 'Les deux mots de passe ne correspondent pas.';
                }
            }
            if (empty($errors)) {
                $data = [
                    'email' => $email,
                    'nom' => trim($nom),
                    'prenom' => trim($prenom),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                if ($roleChanged) {
                    $data['role'] = $newRole;
                }
                if ($newPassword !== '') {
                    $data['mot_de_passe'] = Security::hashPassword($newPassword);
                }

                // Upload photo de profil (avatar)
                if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $allowedImg = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $_FILES['avatar']['tmp_name']) : ($_FILES['avatar']['type'] ?? '');
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    $size = (int) ($_FILES['avatar']['size'] ?? 0);
                    if ($size > 0 && $size <= $maxSize && in_array($mime, $allowedImg, true)) {
                        if (in_array($mime, ['image/jpeg', 'image/jpg'], true)) { $ext = 'jpg'; }
                        elseif ($mime === 'image/gif')  { $ext = 'gif'; }
                        elseif ($mime === 'image/webp') { $ext = 'webp'; }
                        else { $ext = 'png'; }
                        $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                        if (!is_dir($userDir)) {
                            @mkdir($userDir, 0755, true);
                        }
                        $dest = $userDir . DIRECTORY_SEPARATOR . 'avatar.' . $ext;
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                            foreach (['avatar.png', 'avatar.jpg', 'avatar.jpeg', 'avatar.gif', 'avatar.webp'] as $f) {
                                $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                                if ($fpath !== $dest && is_file($fpath)) {
                                    @unlink($fpath);
                                }
                            }
                            $data['avatar'] = 'users/' . $id . '/avatar.' . $ext;
                        }
                    }
                }
                if (!empty($_POST['avatar_supprimer'])) {
                    $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                    foreach (['avatar.png', 'avatar.jpg', 'avatar.jpeg', 'avatar.gif', 'avatar.webp'] as $f) {
                        $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                        if (is_file($fpath)) {
                            @unlink($fpath);
                        }
                    }
                    $data['avatar'] = null;
                }

                // Upload pièce d'identité (image ou PDF)
                if (!empty($_FILES['piece_identite']['name']) && $_FILES['piece_identite']['error'] === UPLOAD_ERR_OK) {
                    $allowedPiece = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = $finfo ? finfo_file($finfo, $_FILES['piece_identite']['tmp_name']) : ($_FILES['piece_identite']['type'] ?? '');
                    if ($finfo) {
                        finfo_close($finfo);
                    }
                    $size = (int) ($_FILES['piece_identite']['size'] ?? 0);
                    if ($size > 0 && $size <= $maxSize && in_array($mime, $allowedPiece, true)) {
                        if ($mime === 'application/pdf') {
                            $ext = 'pdf';
                        } elseif ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                            $ext = 'jpg';
                        } elseif ($mime === 'image/gif') {
                            $ext = 'gif';
                        } elseif ($mime === 'image/webp') {
                            $ext = 'webp';
                        } else {
                            $ext = 'png';
                        }
                        $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                        if (!is_dir($userDir)) {
                            @mkdir($userDir, 0755, true);
                        }
                        $dest = $userDir . DIRECTORY_SEPARATOR . 'piece_identite.' . $ext;
                        if (move_uploaded_file($_FILES['piece_identite']['tmp_name'], $dest)) {
                            foreach (['piece_identite.pdf', 'piece_identite.png', 'piece_identite.jpg', 'piece_identite.jpeg', 'piece_identite.gif', 'piece_identite.webp'] as $f) {
                                $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                                if ($fpath !== $dest && is_file($fpath)) {
                                    @unlink($fpath);
                                }
                            }
                            $data['piece_identite'] = 'users/' . $id . '/piece_identite.' . $ext;
                        }
                    }
                }
                if (!empty($_POST['piece_identite_supprimer'])) {
                    $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                    foreach (['piece_identite.pdf', 'piece_identite.png', 'piece_identite.jpg', 'piece_identite.jpeg', 'piece_identite.gif', 'piece_identite.webp'] as $f) {
                        $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                        if (is_file($fpath)) {
                            @unlink($fpath);
                        }
                    }
                    $data['piece_identite'] = null;
                }

                // Vérification pièce d'identité (uniquement si la migration a été appliquée)
                if (array_key_exists('piece_identite_verifie', $userToEdit)) {
                    $data['piece_identite_verifie'] = !empty($_POST['piece_identite_verifie']) ? 1 : 0;
                    $data['piece_identite_verif_at'] = !empty($_POST['piece_identite_verifie']) ? date('Y-m-d H:i:s') : null;
                    $data['piece_identite_rejet_raison'] = !empty($_POST['piece_identite_verifie']) ? null : Security::sanitizeString($_POST['piece_identite_rejet_raison'] ?? '', 500);
                }

                $userModel->update($id, $data);

                // Créer les profils manquants si le rôle a changé
                $profilCreationOk = true;
                if ($roleChanged) {
                    try {
                        if ($newRole === 'expert') {
                            $ep = (new ProfilExpertModel())->getByUtilisateurId($id);
                            if (!$ep) {
                                $userModel->createProfilExpert($id);
                            }
                        } elseif (in_array($newRole, ['etudiant', 'professeur'], true)) {
                            $etudProfil = (new \App\Models\EtudiantModel())->getByUserId($id);
                            if (!$etudProfil) {
                                $userModel->createProfilEtudiant($id);
                            }
                            if ($newRole === 'professeur') {
                                (new ProfilProfesseurModel())->getOrCreateForUser($id);
                            }
                        }
                        $userModel->createPortefeuille($id);
                    } catch (\Throwable $ex) {
                        $profilCreationOk = false;
                        error_log('[Admin] editUser role change – création profil : ' . $ex->getMessage());
                    }
                }

                $roleNoms = [
                    'client'     => 'Client',
                    'expert'     => 'Expert',
                    'etudiant'   => 'Étudiant',
                    'professeur' => 'Professeur',
                    'admin'      => 'Admin',
                ];
                if ($roleChanged) {
                    $libOld = $roleNoms[$oldRole] ?? ucfirst($oldRole);
                    $libNew = $roleNoms[$newRole] ?? ucfirst($newRole);
                    if ($profilCreationOk) {
                        $_SESSION['flash_success'] = "Type de compte changé avec succès : {$libOld} → {$libNew}. Les profils associés ont été initialisés.";
                    } else {
                        $_SESSION['flash_success'] = "Type de compte changé : {$libOld} → {$libNew}. Attention : l'initialisation du profil associé a échoué (voir logs).";
                    }
                } else {
                    $_SESSION['flash_success'] = 'Modifications enregistrées avec succès.';
                }
                $this->redirect($baseUrl . '/admin/edit-user/' . $id);
                return;
            }
            $userToEdit = array_merge($userToEdit, [
                'email' => $email,
                'nom' => $nom,
                'prenom' => $prenom,
                'role' => $newRole,
            ]);
        }
        $this->render('edit_user', [
            'pageTitle'    => 'Modifier l\'utilisateur - Admin GLOBALO',
            'user'         => ['id' => Auth::id(), 'role' => 'admin'],
            'userToEdit'   => $userToEdit,
            'errors'       => $errors,
            'rolesValides' => $rolesValides,
            'isSelf'       => ($id === (int) Auth::id()),
        ]);
    }

    /** Détail d'une réservation (bouton Voir depuis le tableau de bord). */
    public function reservation(): void
    {
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $reservation = $id ? (new ReservationModel())->find($id) : null;
        if (!$reservation) {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/revenus');
            return;
        }
        $this->render('reservation', [
            'pageTitle'    => 'Réservation #' . $id . ' - Admin GLOBALO',
            'user'         => ['id' => Auth::id(), 'role' => 'admin'],
            'reservation'  => $reservation,
            'adminSection' => 'revenus',
        ]);
    }

    public function experts(): void
    {
        $profilModel = new ProfilExpertModel();
        $experts = $profilModel->getAllForAdmin();
        $statutFilter = isset($_GET['statut']) && in_array($_GET['statut'], ['valide', 'non_valide'], true) ? $_GET['statut'] : null;
        if ($statutFilter === 'valide') {
            $experts = array_values(array_filter($experts, fn($e) => !empty($e['valide_par_admin'])));
        } elseif ($statutFilter === 'non_valide') {
            $experts = array_values(array_filter($experts, fn($e) => empty($e['valide_par_admin'])));
        }
        $this->render('experts', [
            'pageTitle' => 'Validation experts - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'experts' => $experts,
            'statutFilter' => $statutFilter,
        ]);
    }

    public function validerExpert(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/experts');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id) {
            (new ProfilExpertModel())->update($id, ['valide_par_admin' => 1, 'disponible' => 0]);
            \App\Services\PrestataireDisponibilitePromptService::resetRappelAfterValidation('expert', $id);
            $_SESSION['flash_success'] = 'Expert validé. Il sera invité à activer sa disponibilité à la prochaine connexion.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/experts');
    }

    /** Supprimer un profil expert (POST). */
    public function deleteExpert(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/experts');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            try {
                $deleted = (new ProfilExpertModel())->deleteExpertProfile($id);
                if ($deleted) {
                    $_SESSION['flash_success'] = 'Profil expert supprimé.';
                } else {
                    $_SESSION['flash_error'] = 'Impossible de supprimer ce profil expert.';
                }
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Erreur lors de la suppression du profil expert.';
            }
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/experts');
    }

    /** Retirer la validation d'un profil expert. */
    public function invaliderExpert(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/experts');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id) {
            (new ProfilExpertModel())->update($id, ['valide_par_admin' => 0]);
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/experts');
    }

    /** Gestion des professeurs : validation, disponibilité, matières (page dédiée d’édition). */
    public function professeurs(): void
    {
        $profilModel = new ProfilProfesseurModel();
        try {
            $professeurs = $profilModel->getAllForAdmin();
        } catch (\Throwable $e) {
            $professeurs = $profilModel->getAllForAdminBasic();
        }
        $statutFilter = isset($_GET['statut']) && in_array($_GET['statut'], ['valide', 'non_valide'], true) ? $_GET['statut'] : null;
        $dispoFilter = isset($_GET['disponible']) && in_array($_GET['disponible'], ['1', '0'], true) ? $_GET['disponible'] : null;
        if ($statutFilter === 'valide') {
            $professeurs = array_values(array_filter($professeurs, fn($p) => !empty($p['valide_par_admin'])));
        } elseif ($statutFilter === 'non_valide') {
            $professeurs = array_values(array_filter($professeurs, fn($p) => empty($p['valide_par_admin'])));
        }
        if ($dispoFilter === '1') {
            $professeurs = array_values(array_filter($professeurs, fn($p) => !empty($p['disponible'])));
        } elseif ($dispoFilter === '0') {
            $professeurs = array_values(array_filter($professeurs, fn($p) => empty($p['disponible'])));
        }
        // Exercices bloqués (en_cours depuis > 7 jours ou sans professeur assigné)
        $exercicesOrphelins = [];
        try {
            $exercicesOrphelins = (new \App\Models\ExerciceModel())->getOrphelins(7);
        } catch (\Throwable $e) {
            error_log('[Admin/Professeurs] getOrphelins: ' . $e->getMessage());
        }

        $this->render('professeurs', [
            'pageTitle'          => 'Professeurs - Admin GLOBALO',
            'user'               => ['id' => Auth::id(), 'role' => 'admin'],
            'professeurs'        => $professeurs,
            'statutFilter'       => $statutFilter,
            'dispoFilter'        => $dispoFilter,
            'exercicesOrphelins' => $exercicesOrphelins,
        ]);
    }

    /** Formulaire complet : titre, description, tarif, validation, disponibilité, matières. */
    public function editProfesseur(): void
    {
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $profilModel = new ProfilProfesseurModel();
        $prof = $id ? $profilModel->getByIdWithUser($id) : null;
        $baseAdmin = rtrim(BASE_URL ?? '', '/') . '/admin';

        if (!$prof) {
            $_SESSION['flash_error'] = 'Profil professeur introuvable.';
            $this->redirect($baseAdmin . '/professeurs');
            return;
        }

        $matiereModel = new MatiereModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::validateCsrf();
            $titre = trim((string) ($_POST['titre'] ?? ''));
            if ($titre === '') {
                $_SESSION['flash_error'] = 'Le titre affiché est obligatoire.';
                $this->redirect($baseAdmin . '/edit-professeur/' . $id);
                return;
            }
            $description = trim((string) ($_POST['description'] ?? ''));
            $tarifRaw = str_replace(',', '.', (string) ($_POST['tarif_horaire'] ?? '0'));
            $tarif = is_numeric($tarifRaw) ? (float) $tarifRaw : 0.0;
            $tarif = max(0.0, min(999999.0, $tarif));
            $disponible = !empty($_POST['disponible']) ? 1 : 0;
            $valide = !empty($_POST['valide_par_admin']) ? 1 : 0;

            $profilModel->updateProfil($id, [
                'titre' => $titre,
                'description' => $description !== '' ? $description : null,
                'tarif_horaire' => $tarif,
                'disponible' => $disponible,
                'valide_par_admin' => $valide,
            ]);

            $matiereIds = [];
            if (isset($_POST['matieres']) && is_array($_POST['matieres'])) {
                $matiereIds = array_map('intval', $_POST['matieres']);
            }
            try {
                $profilModel->replaceMatieres($id, $matiereIds);
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Profil enregistré, mais la table des matières (`professeur_matieres`) est introuvable ou inaccessible. Exécutez la migration SQL correspondante.';
                $this->redirect($baseAdmin . '/edit-professeur/' . $id);
                return;
            }

            $_SESSION['flash_success'] = 'Profil professeur mis à jour (texte, tarif, statuts et matières).';
            $this->redirect($baseAdmin . '/edit-professeur/' . $id);
            return;
        }

        $matieresGrouped = $matiereModel->getActivesGrouped();
        try {
            $matiereIdsSelected = $profilModel->getMatiereIdsForProfil($id);
        } catch (\Throwable $e) {
            $matiereIdsSelected = [];
        }

        $this->render('edit_professeur', [
            'pageTitle' => 'Gérer le professeur — Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'adminSection' => 'professeurs',
            'prof' => $prof,
            'matieresGrouped' => $matieresGrouped,
            'matiereIdsSelected' => $matiereIdsSelected,
        ]);
    }

    /** Bascule rapide disponible / indisponible (liste admin). */
    public function toggleDisponibiliteProfesseur(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $profilModel = new ProfilProfesseurModel();
        $prof = $id ? $profilModel->getByIdWithUser($id) : null;
        if ($prof) {
            $new = empty($prof['disponible']) ? 1 : 0;
            $profilModel->updateProfil($id, ['disponible' => $new]);
            $_SESSION['flash_success'] = $new
                ? 'Le professeur est marqué comme disponible pour les réservations.'
                : 'Le professeur est marqué comme indisponible (plus de réservation).';
        } else {
            $_SESSION['flash_error'] = 'Profil professeur introuvable.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
    }

    public function validerProfesseur(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id) {
            (new ProfilProfesseurModel())->updateProfil($id, ['valide_par_admin' => 1, 'disponible' => 0]);
            \App\Services\PrestataireDisponibilitePromptService::resetRappelAfterValidation('professeur', $id);
            $_SESSION['flash_success'] = 'Professeur validé. Il sera invité à activer sa disponibilité à la prochaine connexion.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
    }

    public function invaliderProfesseur(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id) {
            (new ProfilProfesseurModel())->updateProfil($id, ['valide_par_admin' => 0, 'disponible' => 0]);
            $_SESSION['flash_success'] = 'Validation retirée : le profil n’est plus public ; réservations désactivées.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
    }

    /**
     * Activer manuellement un abonnement pour un utilisateur (après paiement Wave reçu manuellement).
     * URL : /admin/activer-abonnement/{userId}
     * POST : type (client|expert|professeur|etudiant), duree_jours (optionnel)
     */
    public function activerAbonnement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
            return;
        }
        $params = $this->router->getParams();
        $userId = (int) ($params[0] ?? 0);
        $type = in_array($_POST['type'] ?? '', ['client', 'expert', 'professeur', 'etudiant'], true) ? $_POST['type'] : null;
        $dureeJours = (int) ($_POST['duree_jours'] ?? 30);
        $dureeJours = max(1, min(365, $dureeJours));

        if ($userId && $type) {
            $subscriptionService = new \App\Services\SubscriptionService();
            $subscriptionService->activerManuellement($userId, $type, $dureeJours);
            $_SESSION['flash_success'] = "Abonnement {$type} activé ({$dureeJours}j) pour l'utilisateur #{$userId}.";
        } else {
            $_SESSION['flash_error'] = 'Paramètres invalides.';
        }
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/users');
    }

    public function parametres(): void
    {
        $paramModel = new ParametreModel();
        $commissionConfig = new CommissionConfigModel();
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $commission = (float) ($_POST['commission_pourcent'] ?? 0);
            if ($commission >= 0 && $commission <= 100) {
                $paramModel->set('commission_pourcent', (string) $commission);
                try {
                    $commissionConfig->setDefaut($commission);
                } catch (\Throwable $e) {
                    // Table commission_config absente (migration non exécutée)
                }
            }
            $commissionPremium = (float) ($_POST['commission_premium_pourcent'] ?? 0);
            if ($commissionPremium >= 0 && $commissionPremium <= 100) {
                $paramModel->set('commission_premium_pourcent', (string) $commissionPremium);
            }
            $paramModel->set('plateforme_nom', Security::sanitizeString($_POST['plateforme_nom'] ?? 'GLOBALO', 100));
            $paramModel->set('plateforme_email', Security::sanitizeEmail($_POST['plateforme_email'] ?? ''));

            // SMTP
            $paramModel->set('smtp_host',   Security::sanitizeString($_POST['smtp_host'] ?? '', 255));
            $smtpPort = max(1, min(65535, (int) ($_POST['smtp_port'] ?? 587)));
            $paramModel->set('smtp_port',   (string) $smtpPort);
            $paramModel->set('smtp_user',   Security::sanitizeString($_POST['smtp_user'] ?? '', 255));
            if (isset($_POST['smtp_pass']) && $_POST['smtp_pass'] !== '') {
                $paramModel->set('smtp_pass', $_POST['smtp_pass']);
            }
            $smtpSecure = $_POST['smtp_secure'] ?? '';
            $paramModel->set('smtp_secure', in_array($smtpSecure, ['', 'tls', '1'], true) ? $smtpSecure : 'tls');
            $paramModel->set('mail_from',   Security::sanitizeEmail($_POST['mail_from'] ?? ''));
            $paramModel->set('devise_plateforme', Security::sanitizeString($_POST['devise_plateforme'] ?? 'XOF', 10));
            $paramModel->set('paiement_moyens', PAIEMENT_MOYEN_DEFAUT);
            // Commission plateforme sur les paiements d’abonnement InTouch (même clé BDD qu’historique « wave_commission_pct »)
            $mmCommPct = max(0, min(50, (float) ($_POST['mm_commission_pct'] ?? $_POST['wave_commission_pct'] ?? 0)));
            $paramModel->set('wave_commission_pct', (string) $mmCommPct);

            $paramModel->set('monetisation_mode', in_array($_POST['monetisation_mode'] ?? '', ['commission', 'abonnement'], true) ? $_POST['monetisation_mode'] : 'commission');
            $paramModel->set('abonnement_provider', in_array($_POST['abonnement_provider'] ?? '', ['gratuit', 'intouch', 'paytech'], true) ? $_POST['abonnement_provider'] : 'gratuit');
            $paramModel->set('abonnement_plan_gratuit_actif', !empty($_POST['abonnement_plan_gratuit_actif']) ? '1' : '0');
            $paramModel->set('abonnement_prix_client_xof',     (string) max(0, (int) ($_POST['abonnement_prix_client_xof']     ?? 0)));
            $paramModel->set('abonnement_prix_expert_xof',     (string) max(0, (int) ($_POST['abonnement_prix_expert_xof']     ?? 0)));
            $paramModel->set('abonnement_prix_professeur_xof', (string) max(0, (int) ($_POST['abonnement_prix_professeur_xof'] ?? 0)));
            $paramModel->set('abonnement_prix_etudiant_xof',   (string) max(0, (int) ($_POST['abonnement_prix_etudiant_xof']   ?? 0)));
            $paramModel->set('abonnement_duree_jours', (string) max(1, min(365, (int) ($_POST['abonnement_duree_jours'] ?? 30))));

            if (!empty($_POST['logo_supprimer'])) {
                $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
                foreach (['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.gif', 'logo.webp'] as $f) {
                    $fpath = $uploadPath . DIRECTORY_SEPARATOR . $f;
                    if (is_file($fpath)) {
                        @unlink($fpath);
                    }
                }
                $paramModel->set('logo_custom', '0');
            } elseif (!empty($_FILES['logo']['name']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
                $mime = $_FILES['logo']['type'] ?? '';
                $size = (int) ($_FILES['logo']['size'] ?? 0);
                $maxSize = defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (2 * 1024 * 1024);
                if ($size > 0 && $size <= $maxSize && in_array($mime, $allowed, true)) {
                    if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
                        $ext = 'jpg';
                    } elseif ($mime === 'image/gif') {
                        $ext = 'gif';
                    } elseif ($mime === 'image/webp') {
                        $ext = 'webp';
                    } else {
                        $ext = 'png';
                    }
                    $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
                    if (!is_dir($uploadPath)) {
                        @mkdir($uploadPath, 0755, true);
                    }
                    $dest = $uploadPath . DIRECTORY_SEPARATOR . 'logo.' . $ext;
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
                        foreach (['logo.png', 'logo.jpg', 'logo.jpeg', 'logo.gif', 'logo.webp'] as $f) {
                            $fpath = $uploadPath . DIRECTORY_SEPARATOR . $f;
                            if ($fpath !== $dest && is_file($fpath)) {
                                @unlink($fpath);
                            }
                        }
                        $paramModel->set('logo_custom', '1');
                    }
                }
            }

            $_SESSION['flash_success'] = 'Paramètres enregistrés avec succès.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/parametres');
            return;
        }
        $logoCustom    = $paramModel->get('logo_custom', '0');
        $flagFile      = ROOT_PATH . DIRECTORY_SEPARATOR . '.maintenance';
        $maintenanceOn = is_file($flagFile);
        $maintenanceMeta = [];
        if ($maintenanceOn) {
            $raw = @file_get_contents($flagFile);
            if ($raw) {
                $maintenanceMeta = @json_decode($raw, true) ?: [];
            }
        }
        $this->render('parametres', [
            'pageTitle' => 'Paramètres - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'maintenance_on'   => $maintenanceOn,
            'maintenance_meta' => $maintenanceMeta,
            'commission_pourcent' => $paramModel->getCommissionPercent(),
            'commission_premium_pourcent' => $paramModel->get('commission_premium_pourcent', '15'),
            'plateforme_nom' => $paramModel->get('plateforme_nom', 'GLOBALO'),
            'plateforme_email' => $paramModel->get('plateforme_email', ''),
            'devise_plateforme' => $paramModel->get('devise_plateforme', 'XOF'),
            'paiement_moyens' => PAIEMENT_MOYEN_DEFAUT,
            'logo_custom' => $logoCustom ?? $paramModel->get('logo_custom', '0'),
            'monetisation_mode' => $paramModel->get('monetisation_mode', defined('MONETISATION_MODE_DEFAULT') ? MONETISATION_MODE_DEFAULT : 'commission'),
            'abonnement_provider' => $paramModel->get('abonnement_provider', defined('ABONNEMENT_PROVIDER_DEFAULT') ? ABONNEMENT_PROVIDER_DEFAULT : 'gratuit'),
            'abonnement_plan_gratuit_actif' => $paramModel->get('abonnement_plan_gratuit_actif', '0'),
            'abonnement_prix_client_xof' => $paramModel->get('abonnement_prix_client_xof', defined('ABONNEMENT_PRIX_CLIENT_XOF') ? (string) ABONNEMENT_PRIX_CLIENT_XOF : '1000'),
            'abonnement_prix_expert_xof'     => $paramModel->get('abonnement_prix_expert_xof', defined('ABONNEMENT_PRIX_EXPERT_XOF') ? (string) ABONNEMENT_PRIX_EXPERT_XOF : '1500'),
            'abonnement_prix_professeur_xof' => $paramModel->get('abonnement_prix_professeur_xof', '1000'),
            'abonnement_prix_etudiant_xof'   => $paramModel->get('abonnement_prix_etudiant_xof', '500'),
            'abonnement_duree_jours' => $paramModel->get('abonnement_duree_jours', defined('ABONNEMENT_DUREE_JOURS') ? (string) ABONNEMENT_DUREE_JOURS : '30'),
            'mm_commission_pct'       => $paramModel->get('wave_commission_pct', '0'),
            'smtp_host'   => $paramModel->get('smtp_host', ''),
            'smtp_port'   => $paramModel->get('smtp_port', '587'),
            'smtp_user'   => $paramModel->get('smtp_user', ''),
            'smtp_pass'   => $paramModel->get('smtp_pass', '') !== '' ? $paramModel->get('smtp_pass', '') : '',
            'smtp_secure' => $paramModel->get('smtp_secure', 'tls'),
            'mail_from'   => $paramModel->get('mail_from', ''),
        ]);
    }

    /**
     * Activation / désactivation du mode maintenance.
     * POST /admin/maintenance  { action, on|off, message, eta, progress }
     */
    public function maintenance(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/parametres');
            return;
        }
        Security::validateCsrf();

        $flagFile = ROOT_PATH . DIRECTORY_SEPARATOR . '.maintenance';
        $toggle   = trim((string) ($_POST['maintenance_toggle'] ?? 'off'));
        $base     = rtrim(BASE_URL, '/');

        if ($toggle === 'on') {
            $action   = $_POST['maintenance_action']   ?? 'deploy';
            $message  = Security::sanitizeString($_POST['maintenance_message']  ?? '', 300);
            $eta      = trim((string) ($_POST['maintenance_eta']      ?? ''));
            $progress = max(0, min(100, (int) ($_POST['maintenance_progress'] ?? 10)));
            $contact  = Security::sanitizeEmail($_POST['maintenance_contact'] ?? 'admin@globalo.secogesarl.com');

            $allowed = ['deploy', 'migration', 'pays', 'patch', 'backup', 'config'];
            if (!in_array($action, $allowed, true)) {
                $action = 'deploy';
            }

            $payload = ['action' => $action, 'progress' => $progress, 'contact' => $contact];
            if ($message !== '') {
                $payload['message'] = $message;
            }
            if ($eta !== '') {
                $payload['eta'] = $eta;
            }

            if (file_put_contents($flagFile, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false) {
                $_SESSION['flash_success'] = '🔧 Mode maintenance activé — le site affiche la page de maintenance.';
            } else {
                $_SESSION['flash_error'] = 'Impossible de créer le fichier .maintenance — vérifiez les permissions.';
            }
        } else {
            if (is_file($flagFile)) {
                @unlink($flagFile);
            }
            $_SESSION['flash_success'] = '✅ Mode maintenance désactivé — le site est en ligne.';
        }

        $this->redirect($base . '/admin/parametres');
    }

    public function testSmtp(): void
    {
        if (!Auth::check() || Auth::role() !== 'admin') {
            $this->redirect(rtrim(BASE_URL, '/') . '/auth/connexion');
            return;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/parametres');
            return;
        }

        $toEmail = Security::sanitizeEmail($_POST['test_email'] ?? '');
        if ($toEmail === '') {
            $_SESSION['flash_error'] = 'Veuillez saisir une adresse email de test.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/parametres');
            return;
        }

        $mailer = new \App\Services\MailerService();

        if (!$mailer->isSmtpConfigured()) {
            $_SESSION['flash_error'] = 'SMTP non configuré : renseignez au moins le serveur SMTP (host) et sauvegardez.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/parametres');
            return;
        }

        $sent = $mailer->sendHtml(
            $toEmail,
            'Test SMTP — ' . (defined('BASE_URL') ? BASE_URL : 'GLOBALO'),
            '<p>Bonjour,</p><p>Ceci est un <strong>email de test</strong> envoyé depuis le panneau d\'administration GLOBALO.</p><p>Si vous recevez ce message, la configuration SMTP est correcte ✅</p>'
        );

        if ($sent) {
            $_SESSION['flash_success'] = "✅ Email de test envoyé avec succès à {$toEmail}. Vérifiez votre boîte de réception.";
        } else {
            $_SESSION['flash_error'] = "❌ Échec de l'envoi à {$toEmail}. Vérifiez les paramètres SMTP (host, port, identifiant, mot de passe, chiffrement) et consultez les logs PHP du serveur.";
        }

        $this->redirect(rtrim(BASE_URL, '/') . '/admin/parametres');
    }

    public function chatbot(): void
    {
        $paramModel  = new ParametreModel();
        $configModel = new ChatbotConfigModel();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Clé API + provider
            $paramModel->set('chatbot_openai_api_key', Security::sanitizeString($_POST['chatbot_openai_api_key'] ?? '', 500));
            $paramModel->set('chatbot_ai_provider', Security::sanitizeString($_POST['chatbot_ai_provider'] ?? 'openai', 20));
            $paramModel->set('chatbot_enabled', !empty($_POST['chatbot_enabled']) ? '1' : '0');
            $paramModel->set('chatbot_max_history_messages', (string) max(5, min(50, (int) ($_POST['chatbot_max_history_messages'] ?? 20))));
            $paramModel->set('mail_signature_image_url', Security::sanitizeString($_POST['mail_signature_image_url'] ?? '', 500));
            $keys = ['system_prompt', 'default_find_expert', 'default_create_task', 'help_payment', 'help_withdrawal', 'help_booking', 'help_commission'];
            $configSaved = true;
            try {
                foreach ($keys as $cle) {
                    if (array_key_exists($cle, $_POST)) {
                        $configModel->setKey($cle, Security::sanitizeString($_POST[$cle] ?? '', 10000));
                    }
                }
            } catch (\Throwable $e) {
                error_log('[Admin::chatbot] Erreur sauvegarde chatbot_config: ' . $e->getMessage());
                $configSaved = false;
            }
            if ($configSaved) {
                $_SESSION['flash_ok'] = 'Configuration chatbot enregistrée.';
            } else {
                $_SESSION['flash_error'] = 'Paramètres (API, activation) enregistrés, mais la configuration des réponses du chatbot n\'a pas pu être sauvegardée. Vérifiez que la table chatbot_config existe.';
            }
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/chatbot');
            return;
        }
        try {
            $config = $configModel->getAllKeys();
        } catch (\Throwable $e) {
            $config = [];
        }
        $this->render('chatbot', [
            'pageTitle'                   => 'Chatbot IA - Admin GLOBALO',
            'user'                        => ['id' => Auth::id(), 'role' => 'admin'],
            'chatbot_openai_api_key'      => $paramModel->get('chatbot_openai_api_key', ''),
            'chatbot_ai_provider'         => $paramModel->get('chatbot_ai_provider', 'openai'),
            'chatbot_enabled'             => $paramModel->get('chatbot_enabled', '1'),
            'chatbot_max_history_messages'=> $paramModel->get('chatbot_max_history_messages', '20'),
            'mail_signature_image_url'    => $paramModel->get('mail_signature_image_url', ''),
            'config'                      => $config,
        ]);
    }

    /** Configuration & déclenchement IA Réseaux Sociaux */
    public function social(): void
    {
        $paramModel = new ParametreModel();
        $historique = [];
        try {
            $historique = (new SocialPublisherService())->getHistorique(30);
        } catch (\Throwable $e) {
            error_log('[Admin::social] historique: ' . $e->getMessage());
        }
        $this->render('social', [
            'pageTitle'   => 'Publication Sociale IA - Admin GLOBALO',
            'user'        => ['id' => Auth::id(), 'role' => 'admin'],
            'adminSection'=> 'social',
            'config'      => $this->buildSocialConfig($paramModel),
            'historique'  => $historique,
        ]);
    }

    public function socialConfig(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/social');
            return;
        }
        $paramModel = new ParametreModel();
        $s = fn(string $k, string $d = '') => Security::sanitizeString($_POST[$k] ?? $d, 500);

        // IA
        $paramModel->set('social_ai_provider', $s('social_ai_provider', 'gemini'));
        $paramModel->set('social_ai_api_key',  $s('social_ai_api_key'));
        $paramModel->set('social_ton',         $s('social_ton', 'professionnel'));
        $paramModel->set('social_hashtags',    $s('social_hashtags', '#GLOBALO'));

        // Facebook
        $paramModel->set('social_fb_enabled', !empty($_POST['social_fb_enabled']) ? '1' : '0');
        $paramModel->set('social_fb_page_id', $s('social_fb_page_id'));
        $paramModel->set('social_fb_token',   $s('social_fb_token'));

        // LinkedIn
        $paramModel->set('social_li_enabled', !empty($_POST['social_li_enabled']) ? '1' : '0');
        $paramModel->set('social_li_org_id',  $s('social_li_org_id'));
        $paramModel->set('social_li_token',   $s('social_li_token'));

        // Planning
        $jours = array_filter(array_map('trim', $_POST['social_jours_actifs'] ?? []),
                              fn($j) => in_array($j, ['lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche'], true));
        $paramModel->set('social_jours_actifs', json_encode(array_values($jours)));
        $paramModel->set('social_heure_publication', (string) max(0, min(23, (int)($_POST['social_heure_publication'] ?? 9))));

        // Sujets
        $planning = [];
        foreach (['lundi','mercredi','vendredi','samedi'] as $j) {
            $sujet = Security::sanitizeString($_POST["social_sujet_{$j}"] ?? '', 300);
            if ($sujet !== '') $planning[$j] = $sujet;
        }
        $paramModel->set('social_planning', json_encode($planning));

        // Cron secret
        $paramModel->set('cron_secret', $s('cron_secret'));

        $_SESSION['flash_ok'] = 'Configuration réseaux sociaux enregistrée.';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/social');
    }

    private function buildSocialConfig(ParametreModel $p): array
    {
        $keys = ['social_ai_provider','social_ai_api_key','social_ton','social_hashtags',
                 'social_fb_enabled','social_fb_page_id','social_fb_token',
                 'social_li_enabled','social_li_org_id','social_li_token',
                 'social_jours_actifs','social_heure_publication','social_planning',
                 'cron_secret'];
        $config = [];
        foreach ($keys as $k) {
            $config[$k] = $p->get($k, '');
        }
        return $config;
    }

    public function signalements(): void
    {
        $signalements = $this->getSignalements();
        $statutFilter = isset($_GET['statut']) && in_array($_GET['statut'], ['nouveau', 'en_cours', 'traite', 'rejete'], true) ? $_GET['statut'] : null;
        if ($statutFilter) {
            $signalements = array_values(array_filter($signalements, fn($s) => ($s['statut'] ?? '') === $statutFilter));
        }
        $this->render('signalements', [
            'pageTitle' => 'Signalements - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'signalements' => $signalements,
            'statutFilter' => $statutFilter,
        ]);
    }

    /** Liste des demandes de retrait. */
    public function retraits(): void
    {
        $model = new DemandeRetraitModel();
        $statutFilter = isset($_GET['statut']) && in_array($_GET['statut'], ['en_attente', 'traitee', 'refusee'], true) ? $_GET['statut'] : null;
        $retraits = $model->getAllForAdmin();
        if ($statutFilter) {
            $retraits = array_values(array_filter($retraits, fn($r) => ($r['statut'] ?? '') === $statutFilter));
        }
        $this->render('retraits', [
            'pageTitle' => 'Retraits - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'retraits' => $retraits,
            'statutFilter' => $statutFilter,
        ]);
    }

    /** Approuver une demande de retrait (le virement Wave est effectué manuellement par l'admin). */
    public function approuverRetrait(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/retraits');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            (new DemandeRetraitModel())->updateStatut($id, 'traitee');
        }
        $_SESSION['flash_success'] = 'Retrait marqué comme traité.';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/retraits');
    }

    /** Rejeter une demande de retrait et recréditer le portefeuille de l'expert. */
    public function rejeterRetrait(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/retraits');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id <= 0) {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/retraits');
            return;
        }

        $retraitModel = new DemandeRetraitModel();
        $retrait = $retraitModel->find($id);

        if (!$retrait || ($retrait['statut'] ?? '') !== 'en_attente') {
            $_SESSION['flash_error'] = 'Demande introuvable ou déjà traitée.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/retraits');
            return;
        }

        $db = \App\Core\Database::getInstance();
        try {
            $db->beginTransaction();

            // Récupérer l'utilisateur_id de l'expert depuis son profil
            $stmt = $db->prepare("SELECT utilisateur_id FROM profils_experts WHERE id = ?");
            $stmt->execute([(int) $retrait['expert_id']]);
            $expertUserId = (int) $stmt->fetchColumn();

            if (!$expertUserId) {
                throw new \RuntimeException('Expert introuvable.');
            }

            // Recréditer le portefeuille de l'expert
            $portefeuille = new \App\Models\PortefeuilleModel();
            if (!$portefeuille->crediter($expertUserId, (float) $retrait['montant'])) {
                throw new \RuntimeException('Impossible de recréditer le portefeuille.');
            }

            $retraitModel->updateStatut($id, 'refusee');

            $db->commit();
            $_SESSION['flash_success'] = 'Retrait rejeté. Le portefeuille de l\'expert a été recrédité.';
        } catch (\Throwable $ex) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('[AdminController::rejeterRetrait] ' . $ex->getMessage());
            $_SESSION['flash_error'] = 'Erreur lors du rejet : ' . $ex->getMessage();
        }

        $this->redirect(rtrim(BASE_URL, '/') . '/admin/retraits');
    }

    /** Rembourser un client (libérer l'escrow vers le client en cas de litige/annulation). */
    public function rembourserReservation(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/revenus');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? 0);
        if ($reservationId <= 0) {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/revenus');
            return;
        }
        $result = (new \App\Services\PaymentService())->refund($reservationId);
        if ($result['ok']) {
            $_SESSION['flash_success'] = 'Remboursement effectué. Le client a été recrédité.';
        } else {
            $_SESSION['flash_error'] = $result['error'] ?? 'Impossible de rembourser.';
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/reservation/' . $reservationId);
    }

    /** Liste de tous les abonnements. */
    public function abonnements(): void
    {
        $model = new AbonnementModel();
        $statutFilter = isset($_GET['statut']) && in_array($_GET['statut'], ['actif', 'expire', 'annule', 'en_attente'], true) ? $_GET['statut'] : null;
        $typeFilter = isset($_GET['type']) && in_array($_GET['type'], ['client', 'expert', 'professeur', 'etudiant'], true) ? $_GET['type'] : null;
        $abonnements = $model->getAllForAdmin();
        $today = date('Y-m-d');
        // Filtre "en_attente" = abonnements actifs dont la date_debut est dans le futur
        if ($statutFilter === 'en_attente') {
            $abonnements = array_values(array_filter(
                $abonnements,
                fn($a) => ($a['statut'] ?? '') === 'actif' && ($a['date_debut'] ?? '') > $today
            ));
        } elseif ($statutFilter) {
            $abonnements = array_values(array_filter($abonnements, fn($a) => ($a['statut'] ?? '') === $statutFilter));
        }
        if ($typeFilter) {
            $abonnements = array_values(array_filter($abonnements, fn($a) => ($a['type'] ?? '') === $typeFilter));
        }
        $this->render('abonnements', [
            'pageTitle'      => 'Abonnements - Admin GLOBALO',
            'user'           => ['id' => Auth::id(), 'role' => 'admin'],
            'abonnements'    => $abonnements,
            'statutFilter'   => $statutFilter,
            'typeFilter'     => $typeFilter,
            'countScheduled' => $model->countScheduled(),
        ]);
    }

    /** Expirer manuellement un abonnement. */
    public function expireAbonnement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/abonnements');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            (new AbonnementModel())->expirer($id);
        }
        $_SESSION['flash_success'] = 'Abonnement expiré.';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/abonnements');
    }

    /** Expire en masse tous les abonnements dont la date_fin est dépassée. */
    public function expireAbonnementsOld(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/abonnements');
            return;
        }
        Security::validateCsrf();
        $nb = (new AbonnementModel())->expireOld();
        $_SESSION['flash_success'] = $nb > 0
            ? "{$nb} abonnement(s) périmé(s) expiré(s) avec succès."
            : 'Aucun abonnement périmé à expirer.';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/abonnements');
    }

    // ──────────────────────────────────────────────────────────────────
    // ENVOI EMAILS ADMIN
    // ──────────────────────────────────────────────────────────────────

    /**
     * GET  /admin/send-mail-user/{id}  → formulaire d'envoi individuel
     * POST /admin/send-mail-user/{id}  → envoie l'email
     */
    public function sendMailUser(): void
    {
        $params = $this->router->getParams();
        $userId = (int) ($params[0] ?? 0);
        if (!$userId) {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/users');
            return;
        }
        $userModel = new UtilisateurModel();
        $u = $userModel->find($userId);
        if (!$u) {
            $_SESSION['flash_error'] = 'Utilisateur introuvable.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/users');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::validateCsrf();
            $subject = Security::sanitizeString($_POST['subject'] ?? '', 200);
            $message = Security::sanitizeString($_POST['message'] ?? '', 10000);
            if ($subject === '' || $message === '') {
                $_SESSION['flash_error'] = 'Objet et message requis.';
            } else {
                $mailer  = new \App\Services\MailerService();
                $name    = trim(($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '')) ?: ($u['email'] ?? 'Utilisateur');
                $sent    = $mailer->sendAdminMail($u['email'], $name, $subject, $message);
                // Log en notification interne
                (new \App\Models\NotificationModel())->create(
                    $userId,
                    'admin_message',
                    $subject,
                    mb_substr($message, 0, 500),
                    ''
                );
                if ($sent) {
                    $_SESSION['flash_success'] = "Email envoyé à {$name} ({$u['email']}).";
                } else {
                    $smtpDetail = $mailer->getLastError() ?: $mailer->getSmtpDebugInfo();
                    $_SESSION['flash_error'] = "Notification interne créée mais l'email n'a pas pu être envoyé. Raison : {$smtpDetail} — Allez dans Paramètres → Configuration SMTP.";
                }
            }
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/send-mail-user/' . $userId);
            return;
        }

        $this->render('send_mail_user', [
            'pageTitle'  => 'Envoyer un email — Admin GLOBALO',
            'user'       => ['id' => Auth::id(), 'role' => 'admin'],
            'userToMail' => $u,
            'mailSignatureImageUrl' => (new \App\Models\ParametreModel())->get('mail_signature_image_url', ''),
        ]);
    }

    /**
     * GET  /admin/send-mail-group   → formulaire envoi groupé
     * POST /admin/send-mail-group   → envoie à un groupe de rôle
     */
    public function sendMailGroup(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::validateCsrf();
            $role    = Security::sanitizeString($_POST['role'] ?? 'all', 50);
            $subject = Security::sanitizeString($_POST['subject'] ?? '', 200);
            $message = Security::sanitizeString($_POST['message'] ?? '', 10000);

            if ($subject === '' || $message === '') {
                $_SESSION['flash_error'] = 'Objet et message requis.';
                $this->redirect(rtrim(BASE_URL, '/') . '/admin/send-mail-group');
                return;
            }

            $userModel = new UtilisateurModel();
            $validRoles = ['all', 'client', 'expert', 'etudiant', 'professeur'];
            if (!in_array($role, $validRoles, true)) {
                $role = 'all';
            }

            // Récupération des destinataires
            $pdo = \App\Core\Database::getInstance();
            if ($role === 'all') {
                $stmt = $pdo->prepare("SELECT id, prenom, nom, email FROM utilisateurs WHERE statut = 'actif' ORDER BY id ASC");
                $stmt->execute();
            } else {
                $stmt = $pdo->prepare("SELECT id, prenom, nom, email FROM utilisateurs WHERE role = ? AND statut = 'actif' ORDER BY id ASC");
                $stmt->execute([$role]);
            }
            $recipients = $stmt->fetchAll();

            $mailer = new \App\Services\MailerService();
            $notifModel = new \App\Models\NotificationModel();
            $sent = 0; $errors = 0;

            foreach ($recipients as $r) {
                $name = trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? '')) ?: ($r['email'] ?? 'Utilisateur');
                $ok   = $mailer->sendAdminMail($r['email'], $name, $subject, $message);
                // Notification interne systématique
                $notifModel->create((int) $r['id'], 'admin_message', $subject, mb_substr($message, 0, 500), '');
                if ($ok) { $sent++; } else { $errors++; }
            }

            $total = count($recipients);
            if ($total === 0) {
                $_SESSION['flash_error'] = 'Aucun utilisateur actif dans ce groupe.';
            } elseif ($errors === 0) {
                $_SESSION['flash_success'] = "Email envoyé à {$sent} destinataire(s).";
            } else {
                $_SESSION['flash_success'] = "Notifications internes créées pour {$total} utilisateur(s). {$sent} email(s) envoyé(s), {$errors} échec(s) SMTP.";
            }
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/send-mail-group');
            return;
        }

        $this->render('send_mail_group', [
            'pageTitle' => 'Email groupé — Admin GLOBALO',
            'user'      => ['id' => Auth::id(), 'role' => 'admin'],
        ]);
    }

    /** Liste de toutes les demandes clients. */
    public function demandes(): void
    {
        $model = new DemandeModel();
        $statutFilter = isset($_GET['statut']) && in_array($_GET['statut'], ['ouverte', 'en_cours', 'terminee', 'annulee'], true) ? $_GET['statut'] : null;
        $demandes = $model->getAllForAdmin();
        if ($statutFilter) {
            $demandes = array_values(array_filter($demandes, fn($d) => ($d['statut'] ?? '') === $statutFilter));
        }
        $this->render('demandes', [
            'pageTitle' => 'Demandes - Admin GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'admin'],
            'demandes' => $demandes,
            'statutFilter' => $statutFilter,
        ]);
    }

    /** Fermer/annuler une demande client. */
    public function fermerDemande(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            (new DemandeModel())->updateStatut($id, 'annulee');
        }
        $_SESSION['flash_success'] = 'Demande annulée.';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
    }

    /** Marquer une demande "En cours". */
    public function mettreEnCoursDemande(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            (new DemandeModel())->updateStatut($id, 'en_cours');
        }
        $_SESSION['flash_success'] = 'Demande marquée "En cours".';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
    }

    /** Marquer une demande "Terminée". */
    public function terminerDemande(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            (new DemandeModel())->updateStatut($id, 'terminee');
        }
        $_SESSION['flash_success'] = 'Demande marquée "Terminée".';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
    }

    /** Rouvrir une demande annulée ou terminée. */
    public function rouvrirDemande(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            (new DemandeModel())->updateStatut($id, 'ouverte');
        }
        $_SESSION['flash_success'] = 'Demande rouverte.';
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
    }

    /** Supprimer définitivement une demande (POST). */
    public function deleteDemande(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            try {
                $deleted = (new DemandeModel())->delete($id);
                if ($deleted) {
                    $_SESSION['flash_success'] = 'Demande supprimée.';
                } else {
                    $_SESSION['flash_error'] = 'Impossible de supprimer cette demande (inexistante ou déjà supprimée).';
                }
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Impossible de supprimer cette demande (réservations ou données liées).';
            }
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/demandes');
    }

    /** Renouveler/activer manuellement un abonnement depuis la page abonnements. */
    public function renouvelerAbonnement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/abonnements');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $userId = (int) ($params[0] ?? 0);
        $type = in_array($_POST['type'] ?? '', ['client', 'expert', 'professeur', 'etudiant'], true) ? $_POST['type'] : null;
        $dureeJours = max(1, min(365, (int) ($_POST['duree_jours'] ?? 30)));
        if ($userId && $type) {
            (new \App\Services\SubscriptionService())->activerManuellement($userId, $type, $dureeJours);
            $_SESSION['flash_success'] = "Abonnement {$type} renouvelé ({$dureeJours}j).";
        } else {
            $_SESSION['flash_error'] = 'Paramètres invalides.';
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/abonnements');
    }

    /**
     * GET /admin/wave-transactions — transactions Mobile Money (InTouch ; URL historique inchangée).
     */
    public function waveTransactions(): void
    {
        $txModel        = new TransactionModel();
        $statusFilter   = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'success', 'failed'], true)
            ? $_GET['status'] : null;
        $providerFilter = isset($_GET['provider']) && in_array($_GET['provider'], ['intouch', 'wave'], true)
            ? $_GET['provider'] : null;

        $transactions = $txModel->getAllForAdmin($statusFilter, $providerFilter, 300);
        $stats        = [];
        try {
            $stats = $txModel->getStats();
        } catch (\Throwable $e) {
        }

        $this->render('wave_transactions', [
            'pageTitle'       => 'Transactions InTouch — Admin GLOBALO',
            'user'            => ['id' => Auth::id(), 'role' => 'admin'],
            'transactions'    => $transactions,
            'stats'           => $stats,
            'status_filter'   => $statusFilter,
            'provider_filter' => $providerFilter,
        ]);
    }

    /**
     * POST /admin/wave-valider/{paymentId}
     */
    public function waveValider(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }
        Security::validateCsrf();

        $params    = $this->router->getParams();
        $paymentId = (string) ($params[0] ?? '');
        $notes     = trim((string) ($_POST['notes'] ?? ''));
        $adminId   = (int) Auth::id();

        if ($paymentId === '') {
            $_SESSION['flash_error'] = 'Identifiant de transaction manquant.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }

        $intouchService = new IntouchPaymentService();
        $result         = $intouchService->validateByAdmin($paymentId, $adminId, $notes);

        if ($result['ok']) {
            $abo = !empty($result['abonnement_active']) ? ' Abonnement activé.' : '';
            $_SESSION['flash_success'] = "Transaction {$paymentId} validée.{$abo}";
        } else {
            $_SESSION['flash_error'] = $result['error'] ?? 'Erreur lors de la validation.';
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
    }

    /**
     * POST /admin/wave-refuser/{paymentId}
     */
    public function waveRefuser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }
        Security::validateCsrf();

        $params    = $this->router->getParams();
        $paymentId = (string) ($params[0] ?? '');
        $notes     = trim((string) ($_POST['notes'] ?? ''));
        $adminId   = (int) Auth::id();

        if ($paymentId === '') {
            $_SESSION['flash_error'] = 'Identifiant de transaction manquant.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }

        $intouchService = new IntouchPaymentService();
        $ok             = $intouchService->refuseByAdmin($paymentId, $adminId, $notes);

        if ($ok) {
            $_SESSION['flash_success'] = "Transaction {$paymentId} refusée.";
        } else {
            $_SESSION['flash_error'] = 'Erreur lors du refus.';
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
    }

    /** POST /admin/wave-delete/{id} — supprimer une transaction (non validée de préférence). */
    public function waveDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            try {
                (new TransactionModel())->deleteById($id);
                $_SESSION['flash_success'] = 'Transaction supprimée.';
            } catch (\Throwable $e) {
                $_SESSION['flash_error'] = 'Impossible de supprimer la transaction.';
            }
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
    }

    /** POST /admin/wave-delete-bulk — supprimer plusieurs transactions sélectionnées. */
    public function waveDeleteBulk(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }
        Security::validateCsrf();
        $ids      = isset($_POST['ids']) && is_array($_POST['ids']) ? $_POST['ids'] : [];
        $forceAll = isset($_POST['force_all']) && $_POST['force_all'] === '1';
        if (empty($ids)) {
            $_SESSION['flash_error'] = 'Aucune transaction sélectionnée.';
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
            return;
        }
        try {
            $deleted = (new TransactionModel())->deleteByIds($ids, !$forceAll);
            $_SESSION['flash_success'] = $deleted > 0
                ? "{$deleted} transaction(s) supprimée(s)."
                : 'Aucune transaction supprimée (les transactions validées sont protégées).';
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erreur lors de la suppression.';
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/wave-transactions');
    }

    /** Mettre à jour le statut d'un signalement. */
    public function traiterSignalement(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/admin/signalements');
            return;
        }
        Security::validateCsrf();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $statut = isset($_POST['statut']) && in_array($_POST['statut'], ['nouveau', 'en_cours', 'traite', 'rejete'], true) ? $_POST['statut'] : null;
        if ($id > 0 && $statut) {
            try {
                \App\Core\Database::getInstance()
                    ->prepare("UPDATE signalements SET statut = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$statut, $id]);
            } catch (\Throwable $e) {}
        }
        $this->redirect(rtrim(BASE_URL, '/') . '/admin/signalements');
    }

    private function countExperts(): int
    {
        $stmt = \App\Core\Database::getInstance()->query("SELECT COUNT(*) FROM profils_experts");
        return (int) $stmt->fetchColumn();
    }

    private function countExpertsValides(): int
    {
        $stmt = \App\Core\Database::getInstance()->query("SELECT COUNT(*) FROM profils_experts WHERE valide_par_admin = 1");
        return (int) $stmt->fetchColumn();
    }

    private function countDemandes(): int
    {
        $stmt = \App\Core\Database::getInstance()->query("SELECT COUNT(*) FROM demandes_assistance");
        return (int) $stmt->fetchColumn();
    }

    private function countReservations(): int
    {
        $stmt = \App\Core\Database::getInstance()->query("SELECT COUNT(*) FROM reservations");
        return (int) $stmt->fetchColumn();
    }

    private function getSignalements(): array
    {
        $stmt = \App\Core\Database::getInstance()->query("
            SELECT s.*, CONCAT(TRIM(u.prenom), ' ', TRIM(u.nom)) AS signaleur_nom
            FROM signalements s
            JOIN utilisateurs u ON u.id = s.signaleur_id
            ORDER BY s.created_at DESC LIMIT 100
        ");
        return $stmt->fetchAll();
    }

    private function getTotalCommissions(): float
    {
        try {
            $stmt = \App\Core\Database::getInstance()->query("SELECT COALESCE(SUM(commission_plateforme), 0) FROM paiements WHERE type = 'paiement_session' AND (statut = 'effectue' OR statut_escrow = 'libere')");
            return (float) $stmt->fetchColumn();
        } catch (\Throwable $e) {
            $stmt = \App\Core\Database::getInstance()->query("SELECT COALESCE(SUM(commission_plateforme), 0) FROM paiements WHERE type = 'paiement_session' AND statut = 'effectue'");
            return (float) $stmt->fetchColumn();
        }
    }

    private function getRevenusStats(string $periode): array
    {
        $db = \App\Core\Database::getInstance();
        $dateCondition = "1=1";
        if ($periode === 'jour') {
            $dateCondition = "DATE(p.created_at) = CURDATE()";
        } elseif ($periode === 'semaine') {
            $dateCondition = "p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } else {
            $dateCondition = "p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
        $whereStatut = "p.type = 'paiement_session' AND (p.statut = 'effectue' OR p.statut = 'en_attente')";
        try {
            $db->query("SELECT statut_escrow FROM paiements LIMIT 1");
            $whereStatut = "p.type = 'paiement_session' AND (p.statut = 'effectue' OR p.statut_escrow = 'libere' OR p.statut = 'en_attente')";
        } catch (\Throwable $e) {
            // colonne statut_escrow absente (migration non exécutée)
        }
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(p.commission_plateforme), 0) as commissions,
                   COALESCE(SUM(p.montant), 0) as volume,
                   COUNT(*) as nb_transactions
            FROM paiements p
            WHERE {$whereStatut} AND {$dateCondition}
        ");
        $stmt->execute();
        $row = $stmt->fetch();
        $stats = [
            'commissions' => (float) ($row['commissions'] ?? 0),
            'volume' => (float) ($row['volume'] ?? 0),
            'nb_transactions' => (int) ($row['nb_transactions'] ?? 0),
        ];
        try {
            $stmt = $db->prepare("
                SELECT p.expert_id, pe.titre, pe.slug AS expert_slug, u.id AS expert_user_id,
                       u.prenom, u.nom,
                       SUM(p.commission_plateforme) as commissions_generees,
                       COUNT(*) as nb_missions
                FROM paiements p
                JOIN profils_experts pe ON pe.id = p.expert_id
                JOIN utilisateurs u ON u.id = pe.utilisateur_id
                WHERE p.type = 'paiement_session' AND {$dateCondition}
                GROUP BY p.expert_id, pe.titre, pe.slug, u.id, u.prenom, u.nom
                ORDER BY commissions_generees DESC LIMIT 10
            ");
            $stmt->execute();
            $stats['experts_actifs'] = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $stats['experts_actifs'] = [];
        }
        return $stats;
    }

    private function getSoldePlateformeSiExiste(): ?float
    {
        try {
            return (new SoldePlateformeModel())->getSolde();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /** Vérifie si les migrations professeur/étudiant ont été appliquées. */
    private function checkMigrationProfesseur(): array
    {
        $missing = [];
        $db = \App\Core\Database::getInstance();
        $tablesToCheck = [
            'matieres_universitaires' => 'Matières universitaires (inscription étudiant/professeur)',
            'profils_etudiants'       => 'Profils étudiants',
            'profils_professeurs'     => 'Profils professeurs',
            'professeur_matieres'     => 'Liaison professeur ↔ matières',
            'demandes_retrait_prof'   => 'Retraits professeurs',
        ];
        foreach ($tablesToCheck as $table => $label) {
            try {
                $db->query("SELECT 1 FROM `{$table}` LIMIT 1");
            } catch (\Throwable $e) {
                $missing[] = $label;
            }
        }
        // Vérifier que le role ENUM inclut 'professeur' et 'etudiant' via information_schema
        try {
            $stmt = $db->query(
                "SELECT COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'utilisateurs'
                   AND COLUMN_NAME = 'role'
                 LIMIT 1"
            );
            $colType = (string)($stmt->fetchColumn() ?? '');
            if (strpos($colType, "'professeur'") === false || strpos($colType, "'etudiant'") === false) {
                $missing[] = "ENUM role — ajouter 'etudiant' et 'professeur' dans utilisateurs.role";
            }
        } catch (\Throwable $e) {
            $missing[] = "ENUM role — impossible de vérifier (ajouter 'etudiant' et 'professeur')";
        }
        return $missing;
    }

    /**
     * Réinitialise un exercice bloqué (statut 'en_cours') vers 'ouvert'.
     * POST /admin/reset-exercice/{id}
     */
    public function resetExercice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/admin/professeurs');
            return;
        }
        Security::validateCsrf();
        $params     = $this->router->getParams();
        $exerciceId = (int) ($params[0] ?? 0);
        $base       = rtrim(BASE_URL ?? '', '/');

        if ($exerciceId <= 0) {
            $_SESSION['flash_error'] = 'ID exercice invalide.';
            $this->redirect($base . '/admin/professeurs');
            return;
        }

        $done = (new \App\Models\ExerciceModel())->resetVersOuvert($exerciceId);
        if ($done) {
            $_SESSION['flash_success'] = "Exercice #$exerciceId remis à l'état « ouvert » — visible par tous les professeurs.";
        } else {
            $_SESSION['flash_error'] = "Exercice #$exerciceId introuvable ou déjà ouvert.";
        }
        $this->redirect($base . '/admin/professeurs');
    }
}
