<?php
/**
 * GLOBALO - Espace Expert (profil, disponibilité, missions)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Security;
use App\Models\ProfilExpertModel;
use App\Models\UtilisateurModel;
use App\Models\ReservationModel;
use App\Models\CompetenceModel;
use App\Models\DemandeModel;
use App\Models\DemandeRetraitModel;
use App\Models\PortefeuilleModel;
use App\Models\PaiementModel;
use App\Models\NotificationModel;
use App\Models\MissionUrgenceModel;
use App\Models\ParrainageModel;
use App\Models\LivraisonModel;
use App\Models\DemandePropositionModel;
use App\Services\PayTechPaymentService;

class ExpertController extends Controller
{
    private ProfilExpertModel $profilModel;
    private ReservationModel $reservationModel;
    private CompetenceModel $competenceModel;
    private DemandeModel $demandeModel;
    private DemandeRetraitModel $retraitModel;
    private NotificationModel $notificationModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('expert');
        $this->profilModel = new ProfilExpertModel();
        $this->reservationModel = new ReservationModel();
        $this->competenceModel = new CompetenceModel();
        $this->demandeModel = new DemandeModel();
        $this->retraitModel = new DemandeRetraitModel();
        $this->notificationModel = new NotificationModel();

        $action = $router->getAction();
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        $allowedWithoutValidation = in_array($action, ['compte', 'enAttente'], true);
        if ($profil && empty($profil['valide_par_admin']) && !$allowedWithoutValidation) {
            $base = rtrim(BASE_URL ?? '', '/');
            $url = $this->router->isApp() ? $base . '/app/en-attente' : $base . '/expert/en-attente';
            header('Location: ' . $url);
            exit;
        }
    }

    public function index(): void
    {
        $userId  = (int) Auth::id();
        $profil  = $this->profilModel->getByUtilisateurId($userId);
        $profilId = $profil ? (int)$profil['id'] : 0;

        $missions      = $profilId ? $this->reservationModel->getByExpert($profilId, 5) : [];
        $referralLink  = (new ParrainageModel())->getReferralLink($userId);
        $utilisateur   = (new UtilisateurModel())->find($userId);

        // Statistiques pour les KPI cards
        $portefeuille  = new PortefeuilleModel();
        $paiementModel = new PaiementModel();
        $solde         = $portefeuille->getSolde($userId);
        $totalGains    = $profilId ? $paiementModel->getTotalGainsExpert($profilId) : 0.0;

        // Compteurs rapides
        $allReservations   = $profilId ? $this->reservationModel->getByExpert($profilId) : [];
        $nbEnAttente       = count(array_filter($allReservations, fn($r) => ($r['statut'] ?? '') === 'en_attente'));
        $nbEnCours         = count(array_filter($allReservations, fn($r) => ($r['statut'] ?? '') === 'en_cours'));
        $nbUrgences        = $profilId ? count((new MissionUrgenceModel())->getEnAttentePourExpert($profilId)) : 0;
        $nbDemandes        = $profilId ? count($this->demandeModel->getOuvertesPourExpert($profilId)) : 0;
        $nbNotifications   = $this->notificationModel->countNonLues($userId);

        $this->render('index', [
            'pageTitle'       => 'Tableau de bord Expert - GLOBALO',
            'navActive'       => 'expert',
            'user'            => [
                'id'     => $userId,
                'role'   => 'expert',
                'prenom' => $utilisateur['prenom'] ?? '',
                'nom'    => $utilisateur['nom']    ?? '',
            ],
            'utilisateur'     => $utilisateur,
            'profil'          => $profil,
            'missions'        => $missions,
            'referral_link'   => $referralLink,
            'solde'           => $solde,
            'totalGains'      => $totalGains,
            'nbEnAttente'     => $nbEnAttente,
            'nbEnCours'       => $nbEnCours,
            'nbUrgences'      => $nbUrgences,
            'nbDemandes'      => $nbDemandes,
            'nbNotifications' => $nbNotifications,
        ]);
    }

    public function missions(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        $missions = $profil ? $this->reservationModel->getByExpert($profil['id']) : [];
        $uid = (int) Auth::id();
        $types = NotificationModel::typesReservationOuMission();
        $nbResNotifAvant = $this->notificationModel->countNonLuesByTypes($uid, $types);
        $unreadReservationIds = $this->notificationModel->getReservationIdsWithUnreadReservationNotifs($uid);
        $reservationNotifExtraHint = $nbResNotifAvant > 0 && count($unreadReservationIds) === 0;
        $this->notificationModel->marquerLuesParTypes($uid, $types);
        $this->render('missions', [
            'pageTitle' => 'Mes missions - GLOBALO',
            'navActive' => 'missions',
            'user'      => ['id' => Auth::id(), 'role' => 'expert'],
            'missions'  => $missions,
            'unreadReservationIds' => $unreadReservationIds,
            'reservationNotifExtraHint' => $reservationNotifExtraHint,
        ]);
    }

    /**
     * Livraison de travaux : POST /expert/livrer/{reservationId}
     * L'expert dépose des fichiers (PDF/Word/Excel/Access/etc.) ou un lien vidéo externe.
     */
    public function livrer(): void
    {
        $params        = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? 0);
        if ($reservationId <= 0) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }

        $profil      = $this->profilModel->getByUtilisateurId((int) Auth::id());
        $reservation = $this->reservationModel->find($reservationId);

        if (!$profil || !$reservation || (int) $reservation['expert_id'] !== (int) $profil['id']) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }

        if (!in_array($reservation['statut'], ['en_cours', 'terminee', 'payee'], true)) {
            $_SESSION['flash_error'] = 'Vous ne pouvez livrer qu\'une mission en cours ou terminée.';
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }

        $livraisonModel = new LivraisonModel();
        $livraisons     = $livraisonModel->getByReservation($reservationId);
        $errors         = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $type        = ($_POST['type'] ?? 'fichier') === 'video' ? 'video' : 'fichier';
            $commentaire = Security::sanitizeString($_POST['commentaire'] ?? '', 1000);

            if ($type === 'video') {
                // ── Lien externe vidéo ───────────────────────────────────────
                $lien = trim($_POST['lien_externe'] ?? '');
                if (empty($lien)) {
                    $errors[] = 'Le lien externe est requis pour une livraison vidéo.';
                } elseif (!filter_var($lien, FILTER_VALIDATE_URL)) {
                    $errors[] = 'Le lien vidéo doit être une URL valide (WeTransfer, Smash, etc.).';
                } elseif (!preg_match('#^https?://#i', $lien)) {
                    $errors[] = 'Le lien doit commencer par https://';
                } else {
                    $livraisonModel->create([
                        'reservation_id' => $reservationId,
                        'expert_id'      => (int) $profil['id'],
                        'client_id'      => (int) $reservation['client_id'],
                        'type'           => 'video',
                        'lien_externe'   => $lien,
                        'commentaire'    => $commentaire ?: null,
                    ]);
                    $this->notifierClientLivraison($reservation, 'video');
                    $_SESSION['flash_ok'] = 'Lien vidéo livré avec succès.';
                    $this->redirect(rtrim(BASE_URL, '/') . '/expert/livrer/' . $reservationId);
                    return;
                }
            } else {
                // ── Fichiers office ──────────────────────────────────────────
                $files    = $_FILES['fichiers'] ?? [];
                $nbFich   = is_array($files['name'] ?? null) ? count($files['name']) : 0;
                $uploaded = 0;
                $errFich  = [];

                for ($i = 0; $i < $nbFich; $i++) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        $errFich[] = "Erreur d'upload pour « {$files['name'][$i]} ».";
                        continue;
                    }
                    $nomOriginal = (string) $files['name'][$i];
                    $ext         = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
                    $taille      = (int) $files['size'][$i];

                    if (!in_array($ext, LivraisonModel::EXT_AUTORISEES, true)) {
                        $extListe = implode(', ', LivraisonModel::EXT_AUTORISEES);
                        $errFich[] = "« {$nomOriginal} » : type non autorisé. Formats acceptés : {$extListe}. Pour les vidéos, utilisez un lien externe.";
                        continue;
                    }
                    if ($taille > LivraisonModel::MAX_SIZE) {
                        $errFich[] = "« {$nomOriginal } » dépasse 20 Mo.";
                        continue;
                    }

                    $sousDir  = 'livraisons' . DIRECTORY_SEPARATOR . $reservationId;
                    $uploadDir = (defined('UPLOAD_PATH') ? UPLOAD_PATH : ROOT_PATH . '/uploads')
                                 . DIRECTORY_SEPARATOR . $sousDir;
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $nomSecurise = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($nomOriginal, PATHINFO_FILENAME));
                    $nomFinal    = date('YmdHis') . '_' . $i . '_' . $nomSecurise . '.' . $ext;
                    $cheminFull  = $uploadDir . DIRECTORY_SEPARATOR . $nomFinal;
                    $cheminRelatif = $sousDir . DIRECTORY_SEPARATOR . $nomFinal;

                    if (!move_uploaded_file($files['tmp_name'][$i], $cheminFull)) {
                        $errFich[] = "Impossible de sauvegarder « {$nomOriginal} ».";
                        continue;
                    }

                    $mime = LivraisonModel::MIME_MAP[$ext] ?? 'application/octet-stream';
                    $livraisonModel->create([
                        'reservation_id' => $reservationId,
                        'expert_id'      => (int) $profil['id'],
                        'client_id'      => (int) $reservation['client_id'],
                        'type'           => 'fichier',
                        'nom_fichier'    => $nomOriginal,
                        'chemin'         => str_replace(DIRECTORY_SEPARATOR, '/', $cheminRelatif),
                        'taille'         => $taille,
                        'type_mime'      => $mime,
                        'commentaire'    => $commentaire ?: null,
                    ]);
                    $uploaded++;
                }

                if (!empty($errFich)) {
                    $errors = array_merge($errors, $errFich);
                }
                if ($uploaded > 0) {
                    $this->notifierClientLivraison($reservation, 'fichier', $uploaded);
                    if (empty($errors)) {
                        $_SESSION['flash_ok'] = $uploaded === 1
                            ? '1 fichier livré avec succès.'
                            : "{$uploaded} fichiers livrés avec succès.";
                        $this->redirect(rtrim(BASE_URL, '/') . '/expert/livrer/' . $reservationId);
                        return;
                    }
                } elseif (empty($errors)) {
                    $errors[] = 'Aucun fichier sélectionné.';
                }
            }
        }

        $this->render('livraison', [
            'pageTitle'   => 'Livrer le travail - GLOBALO',
            'navActive'   => 'reservations',
            'user'        => ['id' => Auth::id(), 'role' => 'expert'],
            'reservation' => $reservation,
            'profil'      => $profil,
            'livraisons'  => $livraisons,
            'errors'      => $errors,
            'flashOk'     => $_SESSION['flash_ok'] ?? null,
        ]);
        unset($_SESSION['flash_ok']);
    }

    private function notifierClientLivraison(array $reservation, string $type, int $nb = 1): void
    {
        $msg = $type === 'video'
            ? 'Votre expert a livré un lien vidéo pour votre mission.'
            : ($nb === 1 ? 'Votre expert a livré 1 fichier pour votre mission.' : "Votre expert a livré {$nb} fichiers pour votre mission.");
        $this->notificationModel->create(
            (int) $reservation['client_id'],
            'livraison_travail',
            '📦 Livraison reçue',
            $msg,
            rtrim(BASE_URL, '/') . '/client/reservations/' . (int) $reservation['id']
        );
    }

    /** Mes prestations (sessions terminées). */
    public function prestations(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        $prestations = $profil ? $this->reservationModel->getByExpertWithStatut($profil['id'], 'terminee') : [];
        $this->render('prestations', [
            'pageTitle'   => 'Mes prestations - GLOBALO',
            'navActive'   => 'missions',
            'user'        => ['id' => Auth::id(), 'role' => 'expert'],
            'prestations' => $prestations,
        ]);
    }

    public function profil(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        $competences = $this->competenceModel->getActives();
        $expertCompetences = $profil ? $this->profilModel->getCompetences($profil['id']) : [];
        $errors = [];
        $data = $profil ? [
            'titre' => $profil['titre'],
            'description' => $profil['description'] ?? '',
            'tarif_horaire' => $profil['tarif_horaire'],
            'disponible' => (bool) $profil['disponible'],
            'niveau_experience' => $profil['niveau_experience'] ?? 'intermediaire',
            'competences_autres' => $profil['competences_autres'] ?? '',
        ] : ['titre' => '', 'description' => '', 'tarif_horaire' => 0, 'disponible' => false, 'niveau_experience' => 'intermediaire', 'competences_autres' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'titre' => \App\Core\Security::sanitizeString($_POST['titre'] ?? '', 150),
                'description' => \App\Core\Security::sanitizeString($_POST['description'] ?? '', 5000),
                'tarif_horaire' => round((float) ($_POST['tarif_horaire'] ?? 0), 2),
                'disponible' => isset($_POST['disponible']),
                'niveau_experience' => in_array($_POST['niveau_experience'] ?? '', ['debutant', 'intermediaire', 'confirme', 'expert']) ? $_POST['niveau_experience'] : 'intermediaire',
                'competences_autres' => \App\Core\Security::sanitizeString($_POST['competences_autres'] ?? '', 255),
            ];
            $competencesIds = array_map('intval', array_filter($_POST['competences'] ?? []));
            if (empty($data['titre'])) {
                $errors[] = 'Le titre est requis.';
            }
            if ($data['tarif_horaire'] < 0) {
                $errors[] = 'Le tarif ne peut pas être négatif.';
            }
            if (empty($errors)) {
                if ($profil) {
                    $this->profilModel->update($profil['id'], $data);
                    $this->profilModel->setCompetences($profil['id'], $competencesIds);
                } else {
                    $data['utilisateur_id'] = Auth::id();
                    $data['valide'] = 0;
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $newId = $this->profilModel->insert($data);
                    if ($newId > 0) {
                        $this->profilModel->setCompetences($newId, $competencesIds);
                    }
                }
                $this->redirect(rtrim(BASE_URL ?? '', '/') . '/expert');
                return;
            }
        }

        $autreCompetenceId = null;
        foreach ($competences as $c) {
            if (isset($c['slug']) && strtolower($c['slug']) === 'autres') {
                $autreCompetenceId = (int) $c['id'];
                break;
            }
        }
        $utilisateur = (new UtilisateurModel())->find(Auth::id());
        $this->render('profil', [
            'pageTitle'           => 'Mon profil expert - GLOBALO',
            'navActive'           => 'profil',
            'user'                => ['id' => Auth::id(), 'role' => 'expert'],
            'profil'              => $profil,
            'utilisateur'         => $utilisateur,
            'competences'         => $competences,
            'expertCompetences'   => $expertCompetences,
            'autre_competence_id' => $autreCompetenceId,
            'errors'              => $errors,
            'data'                => $data,
        ]);
    }

    /** Page affichée tant que le profil expert n'a pas été validé par l'admin. */
    public function enAttente(): void
    {
        $userId = (int) Auth::id();
        $utilisateur = (new UtilisateurModel())->find($userId);
        $profil = $this->profilModel->getByUtilisateurId($userId);
        $this->render('en_attente', [
            'pageTitle' => 'Profil en cours de vérification - GLOBALO',
            'navActive' => '',
            'user'      => [
                'id'     => $userId,
                'role'   => 'expert',
                'prenom' => $utilisateur['prenom'] ?? '',
                'nom'    => $utilisateur['nom']    ?? '',
            ],
            'profil'    => $profil,
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
            $this->redirect($baseUrl . '/expert');
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
                    $maxLabel = $this->formatBytes(min($maxSize, $phpMaxBytes));
                    $errors[] = "Photo : fichier trop volumineux. Taille maximale autorisée : {$maxLabel}.";
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
                    $maxLabel = $this->formatBytes(min($maxSize, $phpMaxBytes));
                    $errors[] = "Pièce d'identité : fichier trop volumineux. Taille maximale autorisée : {$maxLabel}.";
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
                $this->redirect($baseUrl . '/expert/compte');
                return;
            }
        }

        $this->render('compte', [
            'pageTitle'        => 'Mon compte - GLOBALO',
            'navActive'        => 'compte',
            'user'             => ['id' => Auth::id(), 'role' => 'expert'],
            'userToEdit'       => $userToEdit,
            'errors'           => $errors,
            'compteBackUrl'    => $baseUrl . '/expert',
            'compteFormAction' => $baseUrl . '/expert/compte',
        ]);
    }

    /** Missions urgentes : premier qui accepte prend la mission. */
    public function urgences(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert');
            return;
        }
        $missionModel = new MissionUrgenceModel();
        $missions = $missionModel->getEnAttentePourExpert((int) $profil['id']);
        $this->notificationModel->marquerLuesParType((int) Auth::id(), 'mission_urgence');
        $this->render('urgences', [
            'pageTitle' => 'Missions urgentes - GLOBALO',
            'navActive' => 'urgences',
            'user'      => ['id' => Auth::id(), 'role' => 'expert'],
            'missions'  => $missions,
        ]);
    }

    /** Accepter une mission urgente (premier qui clique gagne). */
    public function urgenceAccept(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/urgences');
            return;
        }
        $params = $this->router->getParams();
        $demandeId = (int) ($params[0] ?? 0);
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil || $demandeId <= 0) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/urgences');
            return;
        }
        // Vérifier le tarif horaire AVANT d'acquérir la mission (pour ne pas bloquer les autres experts)
        $tarif = (float) $profil['tarif_horaire'];
        if ($tarif <= 0) {
            $_SESSION['flash_error'] = 'Définissez votre tarif horaire dans votre profil avant d\'accepter une mission urgente.';
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/profil');
            return;
        }

        $missionModel = new MissionUrgenceModel();
        $accepted = $missionModel->accepter($demandeId, (int) $profil['id']);
        if (!$accepted) {
            $_SESSION['flash_error'] = 'Cette mission a déjà été acceptée par un autre expert.';
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/urgences');
            return;
        }
        $demande = $this->demandeModel->find($demandeId);
        if (!$demande) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/urgences');
            return;
        }
        $duree   = max(0.5, (float) $demande['duree_estimee_heures']);
        $montant = round($duree * $tarif, 2);
        $dateDebut = (new \DateTime())->modify('+5 minutes')->format('Y-m-d H:i:s');
        $reservationId = $this->reservationModel->create([
            'demande_id' => $demandeId,
            'expert_id' => $profil['id'],
            'client_id' => (int) $demande['client_id'],
            'date_debut_prevue' => $dateDebut,
            'duree_heures' => $duree,
            'tarif_horaire' => $tarif,
            'montant_total' => $montant,
        ]);
        $this->reservationModel->updateStatut($reservationId, 'acceptee');
        $this->demandeModel->update($demandeId, ['statut' => 'en_cours']);
        $this->notificationModel->create(
            (int) $demande['client_id'],
            'expert_accepte_urgence',
            'Un expert a accepté votre demande urgente',
            $profil['titre'] . ' a accepté. Vous pouvez procéder au paiement.',
            rtrim(BASE_URL, '/') . '/client/reservations/' . $reservationId
        );
        $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
    }

    /** Demandes ouvertes correspondant à mes compétences. */
    public function demandes(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/profil');
            return;
        }
        $base = rtrim(BASE_URL ?? '', '/');
        $isApp = $this->router->isApp();
        $demandes = $this->demandeModel->getOuvertesPourExpert((int) $profil['id']);
        $this->render('demandes', [
            'pageTitle' => 'Demandes correspondantes - GLOBALO',
            'navActive' => 'demandes',
            'user'      => ['id' => Auth::id(), 'role' => 'expert'],
            'demandes'  => $demandes,
            'proposer_url_prefix' => $base . ($isApp ? '/app/proposer-demande/' : '/expert/proposer-demande/'),
        ]);
    }

    /** Formulaire + envoi d'une proposition sur une demande ouverte. */
    public function proposerDemande(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil || empty($profil['valide_par_admin'])) {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/expert/en-attente');
            return;
        }
        $profilId = (int) $profil['id'];
        $params = $this->router->getParams();
        $demandeId = (int) ($params[0] ?? 0);
        $demande = $demandeId ? $this->demandeModel->find($demandeId) : null;
        $base = rtrim(BASE_URL ?? '', '/');

        if (!$demande || ($demande['statut'] ?? '') !== 'ouverte') {
            $_SESSION['flash_error'] = 'Demande introuvable ou plus ouverte aux propositions.';
            $this->redirect($base . ($this->router->isApp() ? '/app/expert-demandes' : '/expert/demandes'));
            return;
        }
        if (!$this->demandeModel->estOuvertePourExpert($demandeId, $profilId)) {
            $_SESSION['flash_error'] = 'Cette demande ne correspond pas à votre profil ou n\'est plus disponible.';
            $this->redirect($base . ($this->router->isApp() ? '/app/expert-demandes' : '/expert/demandes'));
            return;
        }
        if (empty($profil['disponible'])) {
            $_SESSION['flash_error'] = 'Activez votre disponibilité pour proposer vos services.';
            $this->redirect($base . ($this->router->isApp() ? '/app/expert-demandes' : '/expert/demandes'));
            return;
        }

        $propModel = new DemandePropositionModel();
        $existante = $propModel->getByDemandeAndExpert($demandeId, $profilId);
        $competencesNoms = array_column($this->profilModel->getCompetencesNoms($profilId), 'nom');
        $errors = [];
        $propData = [
            'presentation'     => (string) ($profil['titre'] ?? ''),
            'tarif_propose'    => (string) (max(0, (float) ($profil['tarif_horaire'] ?? 0)) * max(0.5, (float) ($demande['duree_estimee_heures'] ?? 1))),
            'delai_jours'      => '3',
            'competences_cles' => implode(', ', $competencesNoms),
            'message'          => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existante) {
            if (!Security::validateCsrf()) {
                $errors[] = 'Session expirée. Rechargez la page.';
            } else {
                $propData = [
                    'presentation'     => Security::sanitizeString($_POST['presentation'] ?? '', 500),
                    'message'          => Security::sanitizeString($_POST['message'] ?? '', 5000),
                    'tarif_propose'    => (string) ($_POST['tarif_propose'] ?? '0'),
                    'delai_jours'      => (string) ($_POST['delai_jours'] ?? '3'),
                    'competences_cles' => Security::sanitizeString($_POST['competences_cles'] ?? '', 500),
                ];
                $tarif = is_numeric(str_replace(',', '.', $propData['tarif_propose']))
                    ? (float) str_replace(',', '.', $propData['tarif_propose']) : -1;
                $delai = (int) $propData['delai_jours'];

                if ($propData['presentation'] === '') {
                    $errors[] = 'La présentation courte est requise.';
                }
                if ($propData['message'] === '') {
                    $errors[] = 'Le message détaillé est requis.';
                }
                if ($tarif < 500) {
                    $errors[] = 'Le tarif proposé doit être d\'au moins 500 FCFA.';
                }
                if ($delai < 1 || $delai > 90) {
                    $errors[] = 'Le délai doit être entre 1 et 90 jours.';
                }

                if (empty($errors)) {
                    try {
                        $propModel->create([
                            'demande_id'       => $demandeId,
                            'expert_id'        => $profilId,
                            'presentation'     => $propData['presentation'],
                            'message'          => $propData['message'],
                            'tarif_propose'    => $tarif,
                            'delai_jours'      => $delai,
                            'competences_cles' => $propData['competences_cles'] !== '' ? $propData['competences_cles'] : null,
                        ]);
                        $clientId = (int) ($demande['client_id'] ?? 0);
                        if ($clientId > 0) {
                            $lien = $base . '/client/demandes/' . $demandeId;
                            $this->notificationModel->create(
                                $clientId,
                                'nouvelle_proposition',
                                'Nouvelle proposition reçue',
                                'Un expert a proposé ses services pour : ' . ($demande['titre'] ?? ''),
                                $lien
                            );
                        }
                        $_SESSION['flash_success'] = 'Proposition envoyée. Le client choisira s\'il souhaite travailler avec vous ; la demande reste ouverte tant qu\'il n\'a pas confirmé la résolution.';
                        $this->redirect($base . ($this->router->isApp() ? '/app/expert-demandes' : '/expert/demandes'));
                        return;
                    } catch (\Throwable $e) {
                        error_log('[GLOBALO] proposition expert: ' . $e->getMessage());
                        $errors[] = 'Impossible d\'enregistrer la proposition (peut-être déjà envoyée).';
                    }
                }
            }
        }

        $isApp = $this->router->isApp();
        $this->render('proposer_demande', [
            'pageTitle'             => 'Proposer mes services - GLOBALO',
            'navActive'             => 'demandes',
            'user'                  => ['id' => Auth::id(), 'role' => 'expert'],
            'demande'               => $demande,
            'proposition_existante' => $existante,
            'prop_data'             => $propData,
            'competences_noms'      => $competencesNoms,
            'errors'                => $errors,
            'demandes_list_url'     => $base . ($isApp ? '/app/expert-demandes' : '/expert/demandes'),
            'proposer_form_action'  => $base . ($isApp ? '/app/proposer-demande/' : '/expert/proposer-demande/') . $demandeId,
        ]);
    }

    /** Mes réservations (en attente, acceptées, etc.). */
    public function reservations(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert');
            return;
        }
        $statut = isset($_GET['statut']) ? (string) $_GET['statut'] : null;
        $reservations = $this->reservationModel->getByExpertWithStatut((int) $profil['id'], $statut);
        $uid = (int) Auth::id();
        $types = NotificationModel::typesReservationOuMission();
        $nbResNotifAvant = $this->notificationModel->countNonLuesByTypes($uid, $types);
        $unreadReservationIds = $this->notificationModel->getReservationIdsWithUnreadReservationNotifs($uid);
        $reservationNotifExtraHint = $nbResNotifAvant > 0 && count($unreadReservationIds) === 0;
        $this->notificationModel->marquerLuesParTypes($uid, $types);
        $this->render('reservations', [
            'pageTitle'    => 'Réservations - GLOBALO',
            'navActive'    => 'reservations',
            'user'         => ['id' => Auth::id(), 'role' => 'expert'],
            'reservations' => $reservations,
            'unreadReservationIds' => $unreadReservationIds,
            'reservationNotifExtraHint' => $reservationNotifExtraHint,
        ]);
    }

    public function accepter(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/expert/reservations');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $this->changerStatutReservation($id, 'acceptee', 'acceptee');
    }

    public function refuser(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/expert/reservations');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $this->changerStatutReservation($id, 'annulee', 'refusee');
    }

    private function changerStatutReservation(int $id, string $statut, string $notifType): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $reservation = $this->reservationModel->find($id);
        if (!$reservation || (int)$reservation['expert_id'] !== (int)$profil['id'] || $reservation['statut'] !== 'en_attente') {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $this->reservationModel->updateStatut($id, $statut);

        // Synchroniser le statut de la demande liée
        $demandeId = (int) ($reservation['demande_id'] ?? 0);
        if ($demandeId > 0) {
            if ($statut === 'acceptee') {
                $this->demandeModel->updateStatut($demandeId, 'en_cours');
            } elseif ($statut === 'annulee') {
                $this->demandeModel->updateStatut($demandeId, 'ouverte');
            }
        }

        $clientId = (int) $reservation['client_id'];
        $titre = $statut === 'acceptee' ? 'Réservation acceptée' : 'Réservation refusée';
        $msg = $statut === 'acceptee' ? 'L\'expert a accepté. Vous pouvez procéder au paiement.' : 'L\'expert a décliné la réservation.';
        $this->notificationModel->create($clientId, $notifType, $titre, $msg, '/client/reservations/' . $id);
        $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
    }

    /** Terminer une session (expert). */
    public function terminer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/expert/reservations');
            return;
        }
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $reservation = $this->reservationModel->find($id);
        if (!$reservation || (int)$reservation['expert_id'] !== (int)$profil['id'] || $reservation['statut'] !== 'en_cours') {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $this->reservationModel->updateStatut($id, 'terminee');

        // La demande reste « en cours » jusqu'à confirmation explicite du client.
        $release = (new \App\Services\PaymentService())->releaseToExpert($id);
        if (!$release['ok'] && ($release['error'] ?? '') !== 'Aucun paiement en escrow') {
            $_SESSION['flash_error'] = $release['error'] ?? 'Erreur libération paiement.';
        }
        $this->notificationModel->create(
            (int) $reservation['client_id'],
            'session_terminee',
            'Prestation terminée par l\'expert',
            'L\'expert a indiqué avoir terminé la prestation. Vérifiez le résultat puis confirmez que votre demande est résolue.',
            '/client/reservations/' . $id
        );
        $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
    }

    /** Noter un client après session terminée (étoiles + commentaire). */
    public function noterClient(): void
    {
        $params = $this->router->getParams();
        $reservationId = (int) ($params[0] ?? 0);
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $reservation = $this->reservationModel->find($reservationId);
        if (!$reservation || (int)$reservation['expert_id'] !== (int)$profil['id'] || $reservation['statut'] !== 'terminee') {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $avisClientModel = new \App\Models\AvisClientModel();
        if ($avisClientModel->existsForReservation($reservationId)) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
            return;
        }
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $note = (int) ($_POST['note'] ?? 0);
            $commentaire = \App\Core\Security::sanitizeString($_POST['commentaire'] ?? '', 2000);
            if ($note < 1 || $note > 5) {
                $errors[] = 'Choisissez une note entre 1 et 5 étoiles.';
            } else {
                $avisClientModel->createForReservation($reservationId, (int)$profil['id'], (int)$reservation['client_id'], $note, $commentaire);
                // Révocation automatique si le client reçoit 3 mauvais avis (note <= 2)
                if ($note <= 2) {
                    $nbMauvais = $avisClientModel->countBadByClient((int)$reservation['client_id'], 2);
                    if ($nbMauvais >= 3) {
                        (new \App\Models\UtilisateurModel())->revokeAccount((int)$reservation['client_id']);
                    }
                }
                $this->notificationModel->create((int)$reservation['client_id'], 'avis_client', 'Un expert vous a noté', 'L\'expert a laissé un avis sur votre collaboration.', '/client/reservations/' . $reservationId);
                $this->redirect(rtrim(BASE_URL, '/') . '/expert/reservations');
                return;
            }
        }

        $this->render('noter_client', [
            'pageTitle'   => 'Noter le client - GLOBALO',
            'navActive'   => 'reservations',
            'user'        => ['id' => Auth::id(), 'role' => 'expert'],
            'reservation' => $reservation,
            'errors'      => $errors,
        ]);
    }

    /** Revenus et historique. */
    public function revenus(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert');
            return;
        }
        $portefeuilleModel = new PortefeuilleModel();
        $paiementModel = new PaiementModel();
        $solde = $portefeuilleModel->getSolde(Auth::id());
        $totalGains = $paiementModel->getTotalGainsExpert((int) $profil['id']);
        $transactions = $paiementModel->getByExpert((int) $profil['id']);

        $waveDepotsPending = [];
        try {
            $uid = (int) Auth::id();
            $db  = \App\Core\Database::getInstance();
            $stmt = $db->prepare('
                SELECT payment_id, amount, total_amount, status, transaction_code, created_at,
                       COALESCE(provider, \'\') AS provider, type
                FROM transactions
                WHERE user_id = ? AND type IN (\'depot_portefeuille\', \'paiement_session_touchpay\', \'paiement_session_paytech\')
                ORDER BY created_at DESC LIMIT 5
            ');
            $stmt->execute([$uid]);
            $waveDepotsPending = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
        }

        $paytechConfigured = false;
        try {
            $paytechConfigured = (new PayTechPaymentService())->isConfigured();
        } catch (\Throwable $e) {
        }

        $mmFallbackDepositUrl = null;
        if (!$paytechConfigured) {
            try {
                $ix = new \App\Services\IntouchPaymentService();
                if ($ix->isTouchpayWidgetConfigured() || $ix->isConfigured()) {
                    $mmFallbackDepositUrl = rtrim(BASE_URL ?? '', '/') . '/intouch/touchpay-depot';
                }
            } catch (\Throwable $e) {
            }
        }

        $this->render('revenus', [
            'pageTitle'               => 'Revenus - GLOBALO',
            'navActive'               => 'revenus',
            'user'                    => ['id' => Auth::id(), 'role' => 'expert'],
            'solde'                   => $solde,
            'totalGains'              => $totalGains,
            'transactions'            => $transactions,
            'expert_path_prefix'      => $this->expertPathPrefix(),
            'wave_depots'             => $waveDepotsPending,
            'paytech_configured'      => $paytechConfigured,
            'mm_fallback_deposit_url' => $mmFallbackDepositUrl,
        ]);
    }

    /** Choix de l’opérateur Mobile Money avant le formulaire de retrait. */
    public function retraitChoix(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert');
            return;
        }
        $solde = (new PortefeuilleModel())->getSolde(Auth::id());
        $this->render('retrait_choix', [
            'pageTitle'          => 'Opérateur de retrait - GLOBALO',
            'navActive'          => 'retraitChoix',
            'user'               => ['id' => Auth::id(), 'role' => 'expert'],
            'solde'              => $solde,
            'expert_path_prefix' => $this->expertPathPrefix(),
        ]);
    }

    /** Demande de retrait (traitement manuel par l’administrateur). */
    public function retrait(): void
    {
        $profil = $this->profilModel->getByUtilisateurId((int) Auth::id());
        if (!$profil) {
            $this->redirect(rtrim(BASE_URL, '/') . '/expert');
            return;
        }
        $base    = rtrim(BASE_URL ?? '', '/');
        $prefix  = $this->expertPathPrefix();
        $choixUrl = $base . $prefix . '/retrait-choix';

        $portefeuilleModel = new PortefeuilleModel();
        $solde    = $portefeuilleModel->getSolde(Auth::id());
        $demandes = $this->retraitModel->getByExpert((int) $profil['id']);
        $errors   = [];

        $operateur = strtoupper(trim((string) ($_POST['operateur'] ?? $_GET['operateur'] ?? '')));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!self::retraitOperateurValide($operateur)) {
                $this->redirect($choixUrl);
                return;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $devise  = (new \App\Models\ParametreModel())->get('devise_plateforme', 'XOF');
            $montant = (float) ($_POST['montant'] ?? 0);
            $numeroMobileMoney = \App\Core\Security::sanitizeString($_POST['iban'] ?? '', 34);

            if (!self::retraitOperateurValide($operateur)) {
                $errors[] = 'Opérateur Mobile Money invalide. Retournez à l’étape de choix d’opérateur.';
            } elseif ($montant < 500) {
                $errors[] = 'Montant minimum 500 ' . $devise . '.';
            } elseif ($montant > $solde) {
                $errors[] = 'Solde insuffisant.';
            } elseif (strlen($numeroMobileMoney) < 8) {
                $errors[] = 'Numéro mobile money invalide.';
            } else {
                $ibanStocke = self::combineRetraitIban($operateur, $numeroMobileMoney);
                $pdo = \App\Core\Database::getInstance();
                try {
                    $pdo->beginTransaction();
                    $debitOk = $portefeuilleModel->debiter(Auth::id(), $montant);
                    if (!$debitOk) {
                        throw new \RuntimeException('Solde insuffisant.');
                    }

                    $this->retraitModel->create((int) $profil['id'], $montant, $ibanStocke);

                    (new \App\Models\PaiementModel())->create([
                        'reservation_id'    => null,
                        'client_id'         => (int) Auth::id(),
                        'expert_id'         => (int) $profil['id'],
                        'type'              => 'retrait',
                        'montant'           => $montant,
                        'statut'            => 'en_attente',
                        'reference_externe' => null,
                    ]);

                    $pdo->commit();

                    $_SESSION['flash_success'] = 'Demande de retrait enregistrée. L\'admin la traitera sous 24–48h.';
                    $this->redirect($base . $prefix . '/retrait?operateur=' . rawurlencode($operateur));
                    return;
                } catch (\Throwable $ex) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    error_log('[ExpertController::retrait] ' . $ex->getMessage());
                    $errors[] = 'Erreur lors du traitement du retrait. Veuillez réessayer.';
                }
            }
        }

        renderRetrait:
        $this->render('retrait', [
            'pageTitle'          => 'Demande de retrait - GLOBALO',
            'navActive'          => 'revenus',
            'user'               => ['id' => Auth::id(), 'role' => 'expert'],
            'solde'              => $solde,
            'demandes'           => $demandes,
            'errors'             => $errors,
            'operateur'          => $operateur,
            'expert_path_prefix' => $prefix,
        ]);
    }

    private function expertPathPrefix(): string
    {
        return $this->router->isApp() ? '/app' : '/expert';
    }

    private static function retraitOperateurValide(string $operateur): bool
    {
        return in_array(strtoupper($operateur), ['ORANGE', 'MOOV', 'WAVE'], true);
    }

    /** Enregistre opérateur + numéro dans le champ iban (max 34 car. en base). */
    private static function combineRetraitIban(string $operateur, string $numero): string
    {
        $numero = trim($numero);
        $op     = strtoupper($operateur);
        $combined = $op . '|' . $numero;
        return strlen($combined) <= 34 ? $combined : $numero;
    }

    /** Retourne la limite upload_max_filesize de PHP en octets. */
    private function phpUploadMaxBytes(): int
    {
        $raw = trim(ini_get('upload_max_filesize'));
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

    /** Formate une taille en octets en chaîne lisible (Mo). */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 0) . ' Mo';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 0) . ' Ko';
        }
        return $bytes . ' o';
    }
}
