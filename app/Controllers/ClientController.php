<?php
/**
 * GLOBALO - Espace Client (demandes, réservations, paiements)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\DemandeModel;
use App\Models\ReservationModel;
use App\Models\CompetenceModel;
use App\Models\ProfilExpertModel;
use App\Models\NotificationModel;
use App\Models\MissionUrgenceModel;
use App\Models\UtilisateurModel;
use App\Services\PaymentService;
use App\Services\PayTechPaymentService;
use App\Services\ExpertReservationRecommendation;
use App\Services\DemandeRecommendationService;
use App\Services\PropositionService;
use App\Services\DemandeClotureService;
use App\Models\DemandePropositionModel;
use App\Core\Security;

class ClientController extends Controller
{
    private DemandeModel $demandeModel;
    private ReservationModel $reservationModel;
    private CompetenceModel $competenceModel;
    private ProfilExpertModel $profilModel;
    private NotificationModel $notificationModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('client');
        $this->demandeModel = new DemandeModel();
        $this->reservationModel = new ReservationModel();
        $this->competenceModel = new CompetenceModel();
        $this->profilModel = new ProfilExpertModel();
        $this->notificationModel = new NotificationModel();
    }

    /** Préfixe URL : `/app` (mode app mobile) ou `/client` (desktop / production). */
    private function clientBasePath(): string
    {
        return $this->router->isApp() ? '/app' : '/client';
    }

    /** URL absolue sous l'espace client (`/client` ou `/app`). */
    private function clientUrl(string $path = ''): string
    {
        $base = rtrim(BASE_URL ?? '', '/');
        if ($path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }
        return $base . $this->clientBasePath() . $path;
    }

    private function clientRender(string $view, array $data = []): void
    {
        if (!isset($data['client_base_path'])) {
            $data['client_base_path'] = $this->clientBasePath();
        }
        $this->render($view, $data);
    }

    public function index(): void
    {
        $clientId = Auth::id();
        $userRow = (new UtilisateurModel())->find((int) $clientId);
        $demandes = $this->demandeModel->getByClient((int) $clientId, 5);
        $reservationsFull = $this->reservationModel->getByClient((int) $clientId);
        $reservations = array_slice($reservationsFull, 0, 5);
        $pendingPaymentReservations = array_values(array_filter(
            $reservationsFull,
            static fn($r) => ($r['statut'] ?? '') === 'acceptee'
        ));
        $referralLink = '';
        try {
            $referralLink = (new \App\Models\ParrainageModel())->getReferralLink((int) $clientId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] parrainage dashboard client: ' . $e->getMessage());
        }
        $avisRecus = [];
        try {
            $avisRecus = (new \App\Models\AvisClientModel())->getByClient((int) $clientId, 5);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] avis_clients dashboard client: ' . $e->getMessage());
        }
        $soldePortefeuille = (new \App\Models\PortefeuilleModel())->getSolde($clientId);
        $this->clientRender('index', [
            'pageTitle'         => 'Tableau de bord - GLOBALO',
            'navActive'         => 'client',
            'user'              => ['id' => $clientId, 'role' => 'client', 'prenom' => (is_array($userRow) && isset($userRow['prenom'])) ? $userRow['prenom'] : ''],
            'demandes'          => $demandes,
            'reservations'      => $reservations,
            'nb_reservations_total' => count($reservationsFull),
            'pending_payment_reservations' => $pendingPaymentReservations,
            'referral_link'     => $referralLink,
            'avis_recus'        => $avisRecus,
            'solde_portefeuille' => $soldePortefeuille,
        ]);
    }

    /** Mon compte : photo de profil et pièce d'identité. */
    public function compte(): void
    {
        $baseUrl = rtrim(BASE_URL, '/');
        $id = (int) Auth::id();
        $userModel = new UtilisateurModel();
        $userToEdit = $userModel->find($id);
        if (!$userToEdit) {
            $this->redirect($this->clientUrl());
            return;
        }
        $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
        $maxSize = defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (5 * 1024 * 1024);
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = ['updated_at' => date('Y-m-d H:i:s')];
            $phpMaxBytes = $this->phpUploadMaxBytes();

            // --- Avatar ---
            $avatarErr = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
            if (!empty($_FILES['avatar']['name']) && $avatarErr !== UPLOAD_ERR_NO_FILE) {
                if ($avatarErr === UPLOAD_ERR_OK) {
                    $allowedImg = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = $finfo ? finfo_file($finfo, $_FILES['avatar']['tmp_name']) : ($_FILES['avatar']['type'] ?? '');
                    if ($finfo) finfo_close($finfo);
                    $size = (int) ($_FILES['avatar']['size'] ?? 0);
                    if ($size > 0 && $size <= $maxSize && in_array($mime, $allowedImg, true)) {
                        if (in_array($mime, ['image/jpeg', 'image/jpg'], true)) { $ext = 'jpg'; }
                        elseif ($mime === 'image/gif')  { $ext = 'gif'; }
                        elseif ($mime === 'image/webp') { $ext = 'webp'; }
                        else { $ext = 'png'; }
                        $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                        if (!is_dir($userDir)) @mkdir($userDir, 0755, true);
                        $dest = $userDir . DIRECTORY_SEPARATOR . 'avatar.' . $ext;
                        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                            foreach (['avatar.png', 'avatar.jpg', 'avatar.jpeg', 'avatar.gif', 'avatar.webp'] as $f) {
                                $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                                if ($fpath !== $dest && is_file($fpath)) @unlink($fpath);
                            }
                            $data['avatar'] = 'users/' . $id . '/avatar.' . $ext;
                        } else {
                            $errors[] = 'Photo : impossible de sauvegarder le fichier (erreur serveur).';
                        }
                    } else {
                        $errors[] = 'Photo : format non autorisé (PNG, JPG, GIF, WebP) ou fichier trop volumineux (max 5 Mo).';
                    }
                } elseif ($avatarErr === UPLOAD_ERR_INI_SIZE || $avatarErr === UPLOAD_ERR_FORM_SIZE) {
                    $errors[] = 'Photo : fichier trop volumineux. Taille maximale : ' . $this->formatUploadBytes(min($maxSize, $phpMaxBytes)) . '.';
                } elseif ($avatarErr === UPLOAD_ERR_PARTIAL) {
                    $errors[] = 'Photo : le transfert a été interrompu, veuillez réessayer.';
                } elseif ($avatarErr !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = 'Photo : erreur lors du téléchargement (code ' . $avatarErr . ').';
                }
            }
            if (!empty($_POST['avatar_supprimer'])) {
                $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                foreach (['avatar.png', 'avatar.jpg', 'avatar.jpeg', 'avatar.gif', 'avatar.webp'] as $f) {
                    $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                    if (is_file($fpath)) @unlink($fpath);
                }
                $data['avatar'] = null;
            }

            // --- Pièce d'identité ---
            $pieceErr = $_FILES['piece_identite']['error'] ?? UPLOAD_ERR_NO_FILE;
            if (!empty($_FILES['piece_identite']['name']) && $pieceErr !== UPLOAD_ERR_NO_FILE) {
                if ($pieceErr === UPLOAD_ERR_OK) {
                    $allowedPiece = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime  = $finfo ? finfo_file($finfo, $_FILES['piece_identite']['tmp_name']) : ($_FILES['piece_identite']['type'] ?? '');
                    if ($finfo) finfo_close($finfo);
                    $size = (int) ($_FILES['piece_identite']['size'] ?? 0);
                    if ($size > 0 && $size <= $maxSize && in_array($mime, $allowedPiece, true)) {
                        if ($mime === 'application/pdf') { $ext = 'pdf'; }
                        elseif ($mime === 'image/jpeg' || $mime === 'image/jpg') { $ext = 'jpg'; }
                        elseif ($mime === 'image/gif') { $ext = 'gif'; }
                        elseif ($mime === 'image/webp') { $ext = 'webp'; }
                        else { $ext = 'png'; }
                        $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                        if (!is_dir($userDir)) @mkdir($userDir, 0755, true);
                        $dest = $userDir . DIRECTORY_SEPARATOR . 'piece_identite.' . $ext;
                        if (move_uploaded_file($_FILES['piece_identite']['tmp_name'], $dest)) {
                            foreach (['piece_identite.pdf', 'piece_identite.png', 'piece_identite.jpg', 'piece_identite.jpeg', 'piece_identite.gif', 'piece_identite.webp'] as $f) {
                                $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                                if ($fpath !== $dest && is_file($fpath)) @unlink($fpath);
                            }
                            $data['piece_identite'] = 'users/' . $id . '/piece_identite.' . $ext;
                        } else {
                            $errors[] = 'Pièce d\'identité : impossible de sauvegarder le fichier (erreur serveur).';
                        }
                    } else {
                        $errors[] = 'Pièce d\'identité : format non autorisé (PNG, JPG, PDF) ou fichier trop volumineux (max 5 Mo).';
                    }
                } elseif ($pieceErr === UPLOAD_ERR_INI_SIZE || $pieceErr === UPLOAD_ERR_FORM_SIZE) {
                    $errors[] = 'Pièce d\'identité : fichier trop volumineux. Taille maximale : ' . $this->formatUploadBytes(min($maxSize, $phpMaxBytes)) . '.';
                } elseif ($pieceErr === UPLOAD_ERR_PARTIAL) {
                    $errors[] = 'Pièce d\'identité : le transfert a été interrompu, veuillez réessayer.';
                } elseif ($pieceErr !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = 'Pièce d\'identité : erreur lors du téléchargement (code ' . $pieceErr . ').';
                }
            }
            if (!empty($_POST['piece_identite_supprimer'])) {
                $userDir = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $id;
                foreach (['piece_identite.pdf', 'piece_identite.png', 'piece_identite.jpg', 'piece_identite.jpeg', 'piece_identite.gif', 'piece_identite.webp'] as $f) {
                    $fpath = $userDir . DIRECTORY_SEPARATOR . $f;
                    if (is_file($fpath)) @unlink($fpath);
                }
                $data['piece_identite'] = null;
            }

            if (empty($errors)) {
                $userModel->update($id, $data);
                $_SESSION['flash_success'] = 'Votre compte a bien été mis à jour.';
                $this->redirect($this->clientUrl('/compte'));
                return;
            }
        }

        $this->clientRender('compte', [
            'pageTitle'       => 'Mon compte - GLOBALO',
            'navActive'       => 'compte',
            'user'            => ['id' => Auth::id(), 'role' => 'client'],
            'userToEdit'      => $userToEdit,
            'errors'          => $errors,
            'compteBackUrl'   => $this->clientUrl(),
            'compteFormAction' => $this->clientUrl('/compte'),
        ]);
    }

    /** Mode urgence : Besoin d'aide maintenant. Alerte les experts, premier qui accepte prend la mission. */
    public function urgence(): void
    {
        $params = $this->router->getParams();
        if (($params[0] ?? '') === 'attente' && isset($params[1]) && (int) $params[1] > 0) {
            $this->urgenceAttente((int) $params[1]);
            return;
        }

        $competences = $this->competenceModel->getActives();
        $errors = [];
        $data = ['titre' => '', 'description' => '', 'competence_id' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['titre'] = \App\Core\Security::sanitizeString($_POST['titre'] ?? '', 200);
            $data['description'] = \App\Core\Security::sanitizeString($_POST['description'] ?? '', 5000);
            $data['competence_id'] = (int) ($_POST['competence_id'] ?? 0) ?: null;
            if (empty($data['titre'])) {
                $errors[] = 'Le titre est requis.';
            }
            if (empty($errors)) {
                $demandeId = $this->demandeModel->create([
                    'client_id' => Auth::id(),
                    'titre' => $data['titre'],
                    'description' => $data['description'] ?: 'Besoin d\'aide immédiate (mode urgence).',
                    'duree_estimee_heures' => 1,
                    'competence_id' => $data['competence_id'],
                    'urgence' => 'tres_urgent',
                ]);
                $missionUrgenceModel = new MissionUrgenceModel();
                $missionUrgenceModel->createForDemande($demandeId);
                $experts = $this->profilModel->getListDisponibles($data['competence_id'] ?: null);
                foreach ($experts as $expert) {
                    $userId = (int) $expert['utilisateur_id'];
                    if ($userId) {
                        $this->notificationModel->create(
                            $userId,
                            'mission_urgence',
                            '⚡ Mission urgente',
                            "Un client a besoin d'aide maintenant : " . $data['titre'] . ". Premier à accepter obtient la mission.",
                            rtrim(BASE_URL, '/') . '/expert/urgences'
                        );
                    }
                }
                $this->redirect($this->clientUrl('/urgence/attente/' . $demandeId));
                return;
            }
        }

        $this->clientRender('urgence', [
            'pageTitle' => 'Besoin d\'aide maintenant - GLOBALO',
            'navActive' => 'urgence',
            'user'      => ['id' => Auth::id(), 'role' => 'client'],
            'competences' => $competences,
            'errors' => $errors,
            'data' => $data,
        ]);
    }

    private function urgenceAttente(int $demandeId): void
    {
        $demande = $this->demandeModel->find($demandeId);
        if (!$demande || (int) $demande['client_id'] !== Auth::id()) {
            $this->redirect($this->clientUrl());
            return;
        }
        $reservation = $this->reservationModel->getByDemandeId($demandeId);
        $mission = (new MissionUrgenceModel())->getByDemandeId($demandeId);
        $this->clientRender('urgence_attente', [
            'pageTitle' => 'En attente d\'un expert - GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'client'],
            'demande' => $demande,
            'reservation' => $reservation,
            'mission' => $mission,
        ]);
    }

    public function demandes(): void
    {
        $params = $this->router->getParams();
        if (($params[0] ?? '') === 'nouvelle') {
            $this->nouvelle();
            return;
        }
        $first = $params[0] ?? null;
        if ($first !== null && $first !== '' && is_numeric($first)) {
            $this->demandeDetail((int) $first);
            return;
        }
        $clientId = Auth::id();
        $userRow = (new UtilisateurModel())->find((int) $clientId);
        $demandes = $this->demandeModel->getByClient((int) $clientId);
        $this->clientRender('demandes', [
            'pageTitle'        => 'Mes demandes - GLOBALO',
            'navActive'        => 'demandes',
            'user'             => ['id' => $clientId, 'role' => 'client', 'prenom' => (is_array($userRow) && isset($userRow['prenom'])) ? $userRow['prenom'] : ''],
            'demandes'         => $demandes,
            'client_base_path' => $this->clientBasePath(),
        ]);
    }

    /** Détail d'une demande (client). */
    private function demandeDetail(int $demandeId): void
    {
        $demande = $this->demandeModel->find($demandeId);
        $bp      = $this->clientBasePath();
        if (!$demande || (int) $demande['client_id'] !== (int) Auth::id()) {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . $bp . '/demandes');
            return;
        }
        $reservation = $this->reservationModel->getByDemandeId($demandeId);
        $userRow = (new UtilisateurModel())->find((int) Auth::id());
        $base = rtrim(BASE_URL ?? '', '/');

        $showWelcome = false;
        if (!empty($_SESSION['_flash_demande_welcome']) && (int) $_SESSION['_flash_demande_welcome'] === $demandeId) {
            $showWelcome = true;
            unset($_SESSION['_flash_demande_welcome']);
        }

        $recommendations = null;
        if (($demande['statut'] ?? '') === 'ouverte') {
            try {
                $recommendations = (new DemandeRecommendationService($this->reservationModel))->build($demande, (int) Auth::id());
            } catch (\Throwable $e) {
                error_log('[DemandeRecommendationService] ' . $e->getMessage());
                $recommendations = null;
            }
        }

        $propositions = [];
        try {
            $propositions = (new DemandePropositionModel())->getByDemande($demandeId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] propositions demande: ' . $e->getMessage());
        }

        $clotureSvc = new DemandeClotureService();
        $this->clientRender('demande_detail', [
            'pageTitle'               => 'Détail demande - GLOBALO',
            'user'                    => ['id' => Auth::id(), 'role' => 'client', 'prenom' => (is_array($userRow) && isset($userRow['prenom'])) ? $userRow['prenom'] : ''],
            'demande'                 => $demande,
            'reservation'             => $reservation,
            'demandesListUrl'         => $base . $bp . '/demandes',
            'client_base_path'        => $bp,
            'demande_recommendations' => $recommendations,
            'demande_welcome_hint'    => $showWelcome,
            'propositions'            => $propositions,
            'can_choose_proposition'  => ($demande['statut'] ?? '') === 'ouverte' && !$reservation,
            'can_confirm_demande'     => $reservation ? $clotureSvc->peutConfirmerCloture($reservation, $demande) : false,
        ]);
    }

    /** Client confirme que sa demande est résolue (après prestation expert). */
    public function confirmerDemandeResolue(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . $this->clientBasePath() . '/demandes');
            return;
        }
        Security::validateCsrf();
        $reservationId = (int) ($this->router->getParams()[0] ?? 0);
        $result = (new DemandeClotureService())->confirmerParClient($reservationId, (int) Auth::id());
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        $bp = $this->clientBasePath();
        $base = rtrim(BASE_URL ?? '', '/');
        if (!empty($result['demande_id'])) {
            $this->redirect($base . $bp . '/demandes/' . (int) $result['demande_id']);
            return;
        }
        $this->redirect($base . $bp . '/reservations/' . ($reservationId ?: ''));
    }

    /** Client accepte une proposition d'expert. */
    public function accepterProposition(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . $this->clientBasePath() . '/demandes');
            return;
        }
        Security::validateCsrf();
        $propId = (int) ($this->router->getParams()[0] ?? 0);
        $result = (new PropositionService())->accepterPropositionDemande($propId, (int) Auth::id(), $this->router->isApp());
        $bp = $this->clientBasePath();
        $base = rtrim(BASE_URL ?? '', '/');
        if ($result['ok']) {
            $_SESSION['flash_success'] = $result['message'];
            if (!empty($result['reservation_id'])) {
                $this->redirect($base . $bp . '/reservations/' . (int) $result['reservation_id']);
                return;
            }
        } else {
            $_SESSION['flash_error'] = $result['message'];
        }
        $prop = (new DemandePropositionModel())->find($propId);
        $demandeId = $prop ? (int) $prop['demande_id'] : 0;
        $this->redirect($base . $bp . '/demandes/' . ($demandeId ?: ''));
    }

    /** Client refuse une proposition. */
    public function refuserProposition(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . $this->clientBasePath() . '/demandes');
            return;
        }
        Security::validateCsrf();
        $propId = (int) ($this->router->getParams()[0] ?? 0);
        $result = (new PropositionService())->refuserPropositionDemande($propId, (int) Auth::id());
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        $prop = (new DemandePropositionModel())->find($propId);
        $this->redirect(rtrim(BASE_URL ?? '', '/') . $this->clientBasePath() . '/demandes/' . ($prop ? (int) $prop['demande_id'] : ''));
    }

    /** Alias route app : `/app/nouvelle` → même écran que nouvelle demande. */
    public function nouvelleDemande(): void
    {
        $this->nouvelle();
    }

    public function nouvelle(): void
    {
        $competences = $this->competenceModel->getActives();
        $errors = [];
        $data = ['titre' => '', 'description' => '', 'duree_estimee_heures' => '1', 'competence_id' => '', 'urgence' => 'normale'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'titre' => \App\Core\Security::sanitizeString($_POST['titre'] ?? '', 200),
                'description' => \App\Core\Security::sanitizeString($_POST['description'] ?? '', 5000),
                'duree_estimee_heures' => (float) ($_POST['duree_estimee_heures'] ?? 1),
                'competence_id' => (int) ($_POST['competence_id'] ?? 0) ?: null,
                'urgence' => in_array($_POST['urgence'] ?? '', ['normale', 'urgent', 'tres_urgent']) ? $_POST['urgence'] : 'normale',
            ];
            if (empty($data['titre'])) {
                $errors[] = 'Le titre est requis.';
            }
            if ($data['duree_estimee_heures'] < 0.5 || $data['duree_estimee_heures'] > 8) {
                $errors[] = 'La durée doit être entre 0,5 et 8 heures.';
            }
            if (empty($errors)) {
                $demandeId = $this->demandeModel->create([
                    'client_id' => Auth::id(),
                    'titre' => $data['titre'],
                    'description' => $data['description'],
                    'duree_estimee_heures' => $data['duree_estimee_heures'],
                    'competence_id' => $data['competence_id'],
                    'urgence' => $data['urgence'],
                ]);

                // Notifier les experts/professeurs de la nouvelle demande (in-app + push)
                $notifTitre       = $data['titre'];
                $notifCompetence  = $data['competence_id'] ? (int) $data['competence_id'] : null;
                $notifDemandeId   = $demandeId;
                register_shutdown_function(static function () use ($notifDemandeId, $notifTitre, $notifCompetence): void {
                    try {
                        (new \App\Models\NotificationModel())
                            ->batchNotifyExpertsNouvelleDemandeInApp($notifDemandeId, $notifTitre, $notifCompetence);
                    } catch (\Throwable $e) { /* silencieux */ }
                    try {
                        (new \App\Services\PushNotificationService())
                            ->notifyNouvelleDemandeAuxExperts($notifDemandeId, $notifTitre, $notifCompetence);
                    } catch (\Throwable $e) { /* silencieux */ }
                });

                $_SESSION['_flash_demande_welcome'] = $demandeId;
                $this->redirect(rtrim(BASE_URL ?? '', '/') . $this->clientBasePath() . '/demandes/' . $demandeId);
                return;
            }
        }

        $this->clientRender('nouvelle_demande', [
            'pageTitle'   => 'Nouvelle demande - GLOBALO',
            'navActive'   => 'demandes',
            'user'        => ['id' => Auth::id(), 'role' => 'client'],
            'competences' => $competences,
            'errors'      => $errors,
            'data'        => $data,
        ]);
    }

    /** Réserver un expert pour une demande (créneaux, choix expert). */
    public function reserver(): void
    {
        $params    = $this->router->getParams();
        $demandeId = (int) ($params[0] ?? 0);
        $demande   = $demandeId ? $this->demandeModel->find($demandeId) : null;
        $bp        = $this->clientBasePath();
        $base      = rtrim(BASE_URL ?? '', '/');

        if (!$demande || (int) $demande['client_id'] !== Auth::id()) {
            $this->redirect($base . $bp . '/demandes');
            return;
        }
        if ($demande['statut'] !== 'ouverte') {
            $this->redirect($base . $bp . '/demandes');
            return;
        }
        $expertId = isset($_GET['expert']) ? (int) $_GET['expert'] : 0;
        $experts = $this->profilModel->getListDisponibles($demande['competence_id'] ?: null);
        $experts = ExpertReservationRecommendation::scoreAndSort($experts, $demande);

        $errors = [];
        $data   = ['expert_id' => $expertId, 'date_debut_prevue' => '', 'heure' => '14', 'minute' => '00'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data['expert_id'] = (int) ($_POST['expert_id'] ?? 0);
            $data['date_debut_prevue'] = \App\Core\Security::sanitizeString($_POST['date_debut_prevue'] ?? '', 10);
            if (!empty($_POST['heure_debut'])) {
                $t = trim((string) $_POST['heure_debut']);
                if (preg_match('/^(\d{1,2}):(\d{2})/', $t, $m)) {
                    $data['heure'] = (string) min(23, max(0, (int) $m[1]));
                    $data['minute'] = str_pad((string) min(59, max(0, (int) $m[2])), 2, '0', STR_PAD_LEFT);
                }
            } else {
                $data['heure'] = (string) ($_POST['heure'] ?? '14');
                $data['minute'] = str_pad((string) min(59, max(0, (int) ($_POST['minute'] ?? 0))), 2, '0', STR_PAD_LEFT);
            }
            $expertProfil = $data['expert_id'] ? $this->profilModel->getByIdPublic($data['expert_id']) : null;
            if (!$expertProfil || !$expertProfil['disponible']) {
                $errors[] = 'Expert invalide ou non disponible.';
            } elseif ((float) ($expertProfil['tarif_horaire'] ?? 0) <= 0) {
                $errors[] = 'Cet expert n’a pas encore défini de tarif horaire. Choisissez un autre profil ou contactez le support.';
            }
            $minPadded  = str_pad((string) (int) ($data['minute'] ?? '0'), 2, '0', STR_PAD_LEFT);
            $hourPadded = str_pad((string) min(23, max(0, (int) ($data['heure'] ?? 0))), 2, '0', STR_PAD_LEFT);
            $dateStr    = $data['date_debut_prevue'] . ' ' . $hourPadded . ':' . $minPadded . ':00';
            $dateDebut = \DateTime::createFromFormat('Y-m-d H:i:s', $dateStr);
            if (!$dateDebut || $dateDebut < new \DateTime()) {
                $errors[] = 'Date et heure de début invalides.';
            }
            if (empty($errors) && $expertProfil) {
                $duree = (float) $demande['duree_estimee_heures'];
                $tarif = (float) $expertProfil['tarif_horaire'];
                $montant = round($duree * $tarif, 2);
                $reservationId = $this->reservationModel->create([
                    'demande_id'        => $demandeId,
                    'expert_id'         => $expertProfil['id'],
                    'client_id'         => Auth::id(),
                    'date_debut_prevue' => $dateDebut->format('Y-m-d H:i:s'),
                    'duree_heures'      => $duree,
                    'tarif_horaire'     => $tarif,
                    'montant_total'     => $montant,
                ]);
                $expertUserId = (int) $expertProfil['utilisateur_id'];
                if ($expertUserId) {
                    $lienExpertRes = ($this->router->isApp() ? '/app/expert-reservations' : '/expert/reservations') . '?r=' . $reservationId;
                    $this->notificationModel->create($expertUserId, 'nouvelle_reservation', 'Nouvelle réservation', "Une réservation a été créée pour la demande : {$demande['titre']}", $lienExpertRes);
                }
                $this->redirect($base . $bp . '/reservations/' . $reservationId);
                return;
            }
        } elseif ($expertId > 0) {
            $expert = $this->profilModel->getByIdPublic($expertId);
            if (!$expert) {
                $data['expert_id'] = 0;
            } elseif ((float) ($expert['tarif_horaire'] ?? 0) <= 0) {
                $data['expert_id'] = 0;
            } else {
                $data['expert_id'] = (int) $expert['id'];
            }
        }

        $this->clientRender('reserver', [
            'pageTitle'        => 'Réserver un expert - GLOBALO',
            'navActive'        => 'demandes',
            'user'             => ['id' => Auth::id(), 'role' => 'client'],
            'demande'          => $demande,
            'experts'          => $experts,
            'errors'           => $errors,
            'data'             => $data,
            'client_base_path' => $bp,
        ]);
    }

    /** Détail réservation + paiement si acceptée. */
    public function reservations(): void
    {
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            $this->reservationDetail($id);
            return;
        }
        $uid = (int) Auth::id();
        $reservations = $this->reservationModel->getByClient($uid);
        $types = NotificationModel::typesReservationOuMission();
        $nbResNotifAvant = $this->notificationModel->countNonLuesByTypes($uid, $types);
        $unreadReservationIds = $this->notificationModel->getReservationIdsWithUnreadReservationNotifs($uid);
        $reservationNotifExtraHint = $nbResNotifAvant > 0 && count($unreadReservationIds) === 0;
        $this->notificationModel->marquerLuesParTypes($uid, $types);
        $this->clientRender('reservations', [
            'pageTitle'    => 'Mes réservations - GLOBALO',
            'navActive'    => 'reservations',
            'user'         => ['id' => Auth::id(), 'role' => 'client'],
            'reservations' => $reservations,
            'unreadReservationIds' => $unreadReservationIds,
            'reservationNotifExtraHint' => $reservationNotifExtraHint,
        ]);
    }

    /** Mes commandes (alias des réservations côté client). */
    public function commandes(): void
    {
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id > 0) {
            $this->reservationDetail($id);
            return;
        }
        $uid = (int) Auth::id();
        $reservations = $this->reservationModel->getByClient($uid);
        $types = NotificationModel::typesReservationOuMission();
        $nbResNotifAvant = $this->notificationModel->countNonLuesByTypes($uid, $types);
        $unreadReservationIds = $this->notificationModel->getReservationIdsWithUnreadReservationNotifs($uid);
        $reservationNotifExtraHint = $nbResNotifAvant > 0 && count($unreadReservationIds) === 0;
        $this->notificationModel->marquerLuesParTypes($uid, $types);
        $this->clientRender('commandes', [
            'pageTitle' => 'Mes commandes - GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'client'],
            'reservations' => $reservations,
            'unreadReservationIds' => $unreadReservationIds,
            'reservationNotifExtraHint' => $reservationNotifExtraHint,
        ]);
    }

    private function reservationDetail(int $id): void
    {
        $reservation = $this->reservationModel->find($id);
        if (!$reservation || (int)$reservation['client_id'] !== Auth::id()) {
            $this->redirect($this->clientUrl('/reservations'));
            return;
        }
        $this->notificationModel->marquerLuesReservationNotifsPourReservation((int) Auth::id(), $id);
        $demande = null;
        $demandeId = (int) ($reservation['demande_id'] ?? 0);
        if ($demandeId > 0) {
            $demande = $this->demandeModel->find($demandeId);
        }
        $clotureSvc = new DemandeClotureService();
        $this->clientRender('reservation_detail', [
            'pageTitle' => 'Réservation - GLOBALO',
            'user' => ['id' => Auth::id(), 'role' => 'client'],
            'reservation' => $reservation,
            'demande' => $demande,
            'client_base_path' => $this->clientBasePath(),
            'can_confirm_demande' => $demande ? $clotureSvc->peutConfirmerCloture($reservation, $demande) : false,
        ]);
    }

    /** Payer une réservation (portefeuille) → escrow plateforme, libération à fin de mission. */
    public function payer(): void
    {
        $params = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? 0);
        $reservation = $this->reservationModel->find($reservationId);
        if (!$reservation || (int)$reservation['client_id'] !== Auth::id()) {
            $this->redirect($this->clientUrl('/reservations'));
            return;
        }
        if ($reservation['statut'] !== 'acceptee') {
            $this->redirect($this->clientUrl('/reservations/' . $reservationId));
            return;
        }
        $portefeuilleModel = new \App\Models\PortefeuilleModel();
        $montant = (float) $reservation['montant_total'];
        $solde = $portefeuilleModel->getSolde(Auth::id());

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!\App\Core\Security::validateCsrf()) {
                $_SESSION['flash_error'] = 'Token de sécurité invalide. Veuillez réessayer.';
                $this->redirect($this->clientUrl('/payer/' . $reservationId));
                return;
            }
            try {
                $paymentService = new PaymentService();
                $result = $paymentService->processPaiementEscrow($reservationId, (int) Auth::id());
            } catch (\Throwable $e) {
                $result = ['ok' => false, 'error' => 'Système de paiement indisponible. Exécutez la migration database/migration_monetisation.sql.'];
            }
            if (!empty($result['ok'])) {
                $stmt = \App\Core\Database::getInstance()->prepare("SELECT utilisateur_id FROM profils_experts WHERE id = ?");
                $stmt->execute([$reservation['expert_id']]);
                $expertUserId = (int) $stmt->fetchColumn();
                $lienExpertMissions = ($this->router->isApp() ? '/app/missions' : '/expert/missions') . '?r=' . $reservationId;
                $this->notificationModel->create($expertUserId, 'paiement_recu', 'Paiement reçu', "Le client a payé la réservation #{$reservationId}. Le montant sera libéré à la fin de la mission.", $lienExpertMissions);
                $this->redirect($this->clientUrl('/reservations/' . $reservationId));
                return;
            }
            $_SESSION['flash_error'] = $result['error'] ?? 'Erreur de paiement.';
        }

        $paramModel = new \App\Models\ParametreModel();
        $devise = $paramModel->get('devise_plateforme', 'XOF');
        try {
            $commissionInfo = (new \App\Services\PaymentService())->calculerCommission((int) $reservation['expert_id'], $montant);
        } catch (\Throwable $e) {
            $pct = $paramModel->getCommissionPercent();
            $commissionInfo = ['commission_pourcent' => $pct, 'commission' => round($montant * $pct / 100, 2), 'montant_net' => round($montant - $montant * $pct / 100, 2)];
        }
        $intouchSvc = new \App\Services\IntouchPaymentService();
        $paytechSvc = new PayTechPaymentService();

        $this->clientRender('payer', [
            'pageTitle'             => 'Payer la réservation - GLOBALO',
            'navActive'             => 'reservations',
            'user'                  => ['id' => Auth::id(), 'role' => 'client'],
            'reservation'           => $reservation,
            'solde'                 => $solde,
            'montant'               => $montant,
            'devise'                => $devise,
            'commission_pourcent'   => $commissionInfo['commission_pourcent'],
            'commission'            => $commissionInfo['commission'],
            'montant_net_expert'    => $commissionInfo['montant_net'],
            'paytech_configured'    => $paytechSvc->isConfigured(),
            'touchpay_configured'   => $intouchSvc->isTouchpayWidgetConfigured(),
            'intouch_api_configured' => $intouchSvc->isConfigured(),
        ]);
    }

    /** Portefeuille client — dépôt via InTouch (/intouch/initier-depot). */
    public function portefeuille(): void
    {
        $portefeuilleModel = new \App\Models\PortefeuilleModel();
        $p    = $portefeuilleModel->getOrCreateForUser(Auth::id());
        $solde = (float) ($p['solde'] ?? 0);

        $transactions = (new \App\Models\PaiementModel())->getByClient(Auth::id(), 20);

        // Transactions InTouch de dépôt en attente de l'utilisateur courant
        $waveDepotsPending = [];
        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare("
                SELECT payment_id, amount, total_amount, status, transaction_code, created_at,
                       COALESCE(provider, '') AS provider, type
                FROM transactions
                WHERE user_id = ? AND type IN ('depot_portefeuille', 'paiement_session_touchpay', 'paiement_session_paytech')
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([Auth::id()]);
            $waveDepotsPending = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // table absente en dev
        }

        $intouchSvc = new \App\Services\IntouchPaymentService();
        $paytechSvc = new PayTechPaymentService();

        $this->clientRender('portefeuille', [
            'pageTitle'             => 'Mon portefeuille - GLOBALO',
            'navActive'             => 'portefeuille',
            'user'                  => ['id' => Auth::id(), 'role' => 'client'],
            'solde'                 => $solde,
            'transactions'          => $transactions,
            'wave_depots'           => $waveDepotsPending,
            'paytech_configured'    => $paytechSvc->isConfigured(),
            'intouch_api_configured' => $intouchSvc->isConfigured(),
            'touchpay_configured'   => $intouchSvc->isTouchpayWidgetConfigured(),
        ]);
    }

    /** Noter un expert après mission terminée. */
    public function noter(): void
    {
        $params = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? 0);
        $reservation = $this->reservationModel->find($reservationId);
        if (!$reservation || (int)$reservation['client_id'] !== Auth::id() || $reservation['statut'] !== 'terminee') {
            $this->redirect($this->clientUrl('/reservations'));
            return;
        }
        $avisModel = new \App\Models\AvisModel();
        if ($avisModel->existsForReservation($reservationId)) {
            $this->redirect($this->clientUrl('/reservations/' . $reservationId));
            return;
        }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $note = (int) ($_POST['note'] ?? 0);
            $commentaire = \App\Core\Security::sanitizeString($_POST['commentaire'] ?? '', 2000);
            if ($note < 1 || $note > 5) {
                $errors[] = 'Note entre 1 et 5.';
            } else {
                $avisModel->createForReservation($reservationId, Auth::id(), (int)$reservation['expert_id'], $note, $commentaire);
                $avisModel->updateExpertStats((int)$reservation['expert_id']);
                $titreSession = $reservation['demande_titre'] ?? $reservation['expert_titre'] ?? 'Session';
                $achievementId = (new \App\Models\SessionAchievementModel())->createForReservation($reservationId, (int)$reservation['expert_id'], (int)$reservation['client_id'], $titreSession, $note);
                if ($achievementId > 0) {
                    $this->redirect(rtrim(BASE_URL, '/') . '/share/achievement/' . $achievementId);
                } else {
                    $this->redirect($this->clientUrl('/reservations/' . $reservationId));
                }
                return;
            }
        }

        $this->clientRender('noter', [
            'pageTitle'   => 'Noter l\'expert - GLOBALO',
            'navActive'   => 'reservations',
            'user'        => ['id' => Auth::id(), 'role' => 'client'],
            'reservation' => $reservation,
            'errors'      => $errors,
        ]);
    }

    private function phpUploadMaxBytes(): int
    {
        $raw  = trim(ini_get('upload_max_filesize'));
        $unit = strtolower(substr($raw, -1));
        $val  = (int) $raw;
        if ($unit === 'g') {
            return $val * 1024 * 1024 * 1024;
        } elseif ($unit === 'm') {
            return $val * 1024 * 1024;
        } elseif ($unit === 'k') {
            return $val * 1024;
        }
        return $val;
    }

    private function formatUploadBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) return round($bytes / (1024 * 1024), 0) . ' Mo';
        if ($bytes >= 1024) return round($bytes / 1024, 0) . ' Ko';
        return $bytes . ' o';
    }
}
