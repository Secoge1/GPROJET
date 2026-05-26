<?php
/**
 * GLOBALO - Authentification (inscription, connexion, mot de passe oublié)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Security;
use App\Core\Lang;
use App\Models\UtilisateurModel;
use App\Models\ParrainageModel;
use App\Models\CompetenceModel;
use App\Models\ProfilExpertModel;
use App\Services\MailerService;
use App\Services\SubscriptionService;
use App\Services\GoogleOAuthService;

class AuthController extends Controller
{
    private UtilisateurModel $userModel;

    public function __construct(Router $router)
    {
        parent::__construct($router);
        $this->userModel = new UtilisateurModel();
    }

    /**
     * GET /rejoindre — URL courte pour codes QR (affiches, flyers).
     * Redirige vers /auth/inscription en conservant la query string (?ref=, ?role=, etc.).
     */
    public function rejoindre(): void
    {
        $base = rtrim(BASE_URL ?? '', '/');
        $qs = isset($_SERVER['QUERY_STRING']) ? trim((string) $_SERVER['QUERY_STRING']) : '';
        $target = $base . '/auth/inscription' . ($qs !== '' ? '?' . $qs : '');
        $this->redirect($target);
    }

    public function inscription(): void
    {
        if (Auth::check()) {
            $this->redirectAfterLogin();
            return;
        }
        $errors = [];
        $refCode = isset($_GET['ref']) ? trim((string) $_GET['ref']) : '';
        if ($refCode) {
            $_SESSION['inscription_ref'] = $refCode;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ref']) && $_POST['ref'] !== '') {
            $refCode = trim((string) $_POST['ref']);
            $_SESSION['inscription_ref'] = $refCode;
        }
        $paysEligibles = ['Mali', "Côte d'Ivoire", 'Sénégal', 'Bénin', 'Niger'];
        $data = ['email' => '', 'nom' => '', 'prenom' => '', 'role' => 'client', 'pays' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paysPost = Security::sanitizeString($_POST['pays'] ?? '', 50);
            $data = [
                'email'             => Security::sanitizeEmail($_POST['email'] ?? ''),
                'nom'               => Security::sanitizeString($_POST['nom'] ?? '', 100),
                'prenom'            => Security::sanitizeString($_POST['prenom'] ?? '', 100),
                'role'              => in_array($_POST['role'] ?? '', ['client', 'expert', 'etudiant', 'professeur'], true) ? $_POST['role'] : 'client',
                'expert_bio'        => Security::sanitizeString($_POST['expert_bio'] ?? '', 1000),
                'pays'              => in_array($paysPost, $paysEligibles, true) ? $paysPost : '',
                'matieres_etudiant' => array_values(array_filter(
                    array_map('intval', $_POST['matieres_etudiant'] ?? []),
                    fn($v) => $v > 0
                )),
            ];
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (!Security::validateEmail($data['email'])) {
                $errors[] = 'Adresse email invalide.';
            } elseif ($this->userModel->findByEmail($data['email'])) {
                $errors[] = 'Cette adresse email est déjà utilisée.';
            }
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $errors[] = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
            if (empty($data['nom']) || empty($data['prenom'])) {
                $errors[] = 'Nom et prénom sont requis.';
            }
            if (empty($data['pays'])) {
                $errors[] = 'Veuillez sélectionner votre pays.';
            }
            if (in_array($data['role'], ['etudiant', 'professeur'], true)) {
                if (empty($data['matieres_etudiant'])) {
                    // Vérifier si des matières existent réellement avant de bloquer
                    $matieresExistent = false;
                    try {
                        $matieresExistent = !empty((new \App\Models\MatiereModel())->getActives());
                    } catch (\Throwable $e) {
                        // Table manquante : ne pas bloquer l'inscription, juste logger
                        error_log('[GLOBALO] matieres_universitaires inaccessible: ' . $e->getMessage() . ' — Exécutez database/migration_professeur_complet.sql');
                    }
                    if ($matieresExistent) {
                        $errors[] = 'Veuillez sélectionner au moins une matière (étudiant ou professeur).';
                    }
                }
            }

            if (empty($errors)) {
                $tokenVerif = bin2hex(random_bytes(32));
                $expireVerif = date('Y-m-d H:i:s', time() + 86400 * 2);
                $userId = $this->userModel->create([
                    'email' => $data['email'],
                    'mot_de_passe' => Security::hashPassword($password),
                    'role' => $data['role'],
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'pays' => $data['pays'],
                    'token_verification' => $tokenVerif,
                    'token_verification_expire' => $expireVerif,
                ]);
                $this->userModel->createPortefeuille($userId);
                Auth::login($userId, $data['role']);
                if ($data['role'] === 'etudiant' || $data['role'] === 'professeur') {
                    $matiereIds = array_values(array_filter(
                        array_map('intval', $_POST['matieres_etudiant'] ?? []),
                        fn($v) => $v > 0
                    ));
                    try {
                        $this->userModel->createProfilEtudiant($userId);
                        if (!empty($matiereIds)) {
                            $etudiantModel = new \App\Models\EtudiantModel();
                            $etudiantProfil = $etudiantModel->getByUserId($userId);
                            if ($etudiantProfil) {
                                $etudiantModel->setMatieres((int) $etudiantProfil['id'], $matiereIds);
                            }
                        }
                    } catch (\Throwable $ex) {
                        error_log('[GLOBALO] inscription etudiant/professeur profil: ' . $ex->getMessage() . ' — Exécutez database/migration_professeur_complet.sql');
                    }
                    if ($data['role'] === 'professeur') {
                        try {
                            $profProfModel = new \App\Models\ProfilProfesseurModel();
                            $profilProf    = $profProfModel->getOrCreateForUser($userId);
                            if ($profilProf && !empty($matiereIds)) {
                                $profProfModel->replaceMatieres((int) $profilProf['id'], $matiereIds);
                            }
                        } catch (\Throwable $ex) {
                            error_log('[GLOBALO] inscription professeur profil/matieres: ' . $ex->getMessage() . ' — Exécutez database/migration_professeur_complet.sql');
                        }
                    }
                }
                if ($data['role'] === 'expert') {
                    $expertProfilId = $this->userModel->createProfilExpert($userId);
                    $competenceId = (int) ($_POST['competence_id'] ?? 0);
                    $competenceNiveau = isset($_POST['competence_niveau']) ? trim((string) $_POST['competence_niveau']) : '';
                    $niveauxAllowed = ['debutant', 'intermediaire', 'avance', 'expert'];
                    if ($expertProfilId > 0 && $competenceId > 0) {
                        $niveau = in_array($competenceNiveau, $niveauxAllowed, true) ? $competenceNiveau : 'intermediaire';
                        (new ProfilExpertModel())->setCompetences($expertProfilId, [$competenceId], [$competenceId => $niveau]);
                    }
                    $competencesAutres = Security::sanitizeString($_POST['competences_autres'] ?? '', 255);
                    $expertBio = Security::sanitizeString($_POST['expert_bio'] ?? '', 1000);
                    $profilUpdates = [];
                    if ($competencesAutres !== '') {
                        $profilUpdates['competences_autres'] = $competencesAutres;
                    }
                    if ($expertBio !== '') {
                        $profilUpdates['description'] = $expertBio;
                    }
                    // Générer un slug SEO unique basé sur prénom + nom + id du profil
                    if ($expertProfilId > 0) {
                        $slugBase = ProfilExpertModel::slugify(trim(($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? '')) ?: 'expert');
                        $profilUpdates['slug'] = $slugBase . '-' . $expertProfilId;
                    }
                    if (!empty($profilUpdates) && $expertProfilId > 0) {
                        (new ProfilExpertModel())->update($expertProfilId, $profilUpdates);
                    }
                }
                if (!empty($_SESSION['inscription_ref'])) {
                    (new ParrainageModel())->registerFilleul($_SESSION['inscription_ref'], $userId);
                    unset($_SESSION['inscription_ref']);
                }
                $verifLink = rtrim(BASE_URL ?? '', '/') . '/auth/verifier?token=' . urlencode($tokenVerif);
                $mailer = new MailerService();
                try {
                    $emailSent = $mailer->sendVerificationEmail($data['email'], $verifLink);
                } catch (\Throwable $e) {
                    $emailSent = false;
                    error_log('[GLOBALO] sendVerificationEmail (inscription) : ' . $e->getMessage());
                }
                // Email de bienvenue personnalisé selon le rôle
                try {
                    $dashUrl = rtrim(BASE_URL ?? '', '/') . '/' . $data['role'];
                    $mailer->sendWelcomeEmail(
                        $data['email'],
                        $data['prenom'],
                        $data['nom'],
                        $data['role'],
                        $dashUrl
                    );
                } catch (\Throwable $e) {
                    error_log('[GLOBALO] sendWelcomeEmail (inscription) : ' . $e->getMessage());
                }
                $_SESSION['verification_link'] = $verifLink;
                $_SESSION['verify_email']      = $data['email'] ?? '';
                $_SESSION['email_sent']        = $emailSent;
                $_SESSION['resend_email']      = $data['email'] ?? '';

                $subscriptionService = new SubscriptionService();
                // Si un paiement réel est requis (provider checkout + prix > 0) → rediriger vers le checkout.
                // L'IPN créera l'abonnement après confirmation du paiement.
                // Dans tous les autres cas (plan gratuit ou provider non-payant) → ne rien créer ici ;
                // l'abonnement sera programmé automatiquement J+1 lors de la validation de l'email.
                if ($subscriptionService->needsPaymentRedirect($data['role'])) {
                    $provider = $subscriptionService->getProvider();
                    $subscriptionResult = $subscriptionService->souscrire($userId, $data['role'], $provider);
                    if (!empty($subscriptionResult['redirect'])) {
                        $this->redirect($subscriptionResult['redirect']);
                        return;
                    }
                }
                $this->redirect(rtrim(BASE_URL ?? '', '/') . '/auth/verification-envoyee');
                return;
            }
        }

        $competences = (new CompetenceModel())->getActives();
        $autreCompetenceId = null;
        foreach ($competences as $c) {
            if (isset($c['slug']) && strtolower((string) $c['slug']) === 'autres') {
                $autreCompetenceId = (int) $c['id'];
                break;
            }
        }
        $subscriptionService = new SubscriptionService();
        $this->render('inscription', [
            'pageTitle'                     => 'Inscription - GLOBALO',
            'navActive'                     => 'inscription',
            'errors'                        => $errors,
            'data'                          => $data,
            'ref'                           => $refCode ?? $_SESSION['inscription_ref'] ?? '',
            'pays_eligibles'                => $paysEligibles,
            'competences'                   => $competences,
            'autre_competence_id'           => $autreCompetenceId,
            'abonnement_plan_gratuit_actif' => $subscriptionService->isPlanGratuitActif(),
            'abonnement_prix_client_xof'     => $subscriptionService->getPrixClientXof(),
            'abonnement_prix_expert_xof'     => $subscriptionService->getPrixExpertXof(),
            'abonnement_prix_etudiant_xof'   => $subscriptionService->getPrixEtudiantXof(),
            'abonnement_prix_professeur_xof' => $subscriptionService->getPrixProfesseurXof(),
        ]);
    }

    public function connexion(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::dashboardUrl());
            return;
        }
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // ── Rate limiting : 5 tentatives / 15 min par IP ──────────────────
            if (Security::isRateLimited('login', 5, 900)) {
                $error = 'Trop de tentatives de connexion. Veuillez patienter 15 minutes avant de réessayer.';
            } else {
                $email    = Security::sanitizeEmail($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                $user = $this->userModel->findByEmail($email);
                if ($user && ($user['auth_provider'] ?? 'email') === 'google' && empty($user['mot_de_passe'])) {
                    $error = 'Ce compte a été créé via Google. Cliquez sur « Continuer avec Google » pour vous connecter.';
                } elseif ($user && Security::verifyPassword($password, (string) ($user['mot_de_passe'] ?? ''))) {
                    if (empty($user['actif'])) {
                        $error = 'Ce compte a été désactivé.';
                        Security::recordFailedAttempt('login');
                    } elseif (empty($user['email_verifie'])) {
                        $error = 'Votre adresse email n\'est pas encore vérifiée. Consultez votre boîte de réception (et les spams), ou renvoyez le lien ci-dessous.';
                        $_SESSION['connexion_email_non_verifie'] = $user['email'];
                    } else {
                        Security::clearRateLimit('login');
                        $this->userModel->updateDerniereConnexion($user['id']);
                        $role = Auth::normalizeRole((string) ($user['role'] ?? ''));
                        Auth::login((int) $user['id'], $role);
                        $this->redirectAfterLogin($role);
                        return;
                    }
                } else {
                    Security::recordFailedAttempt('login');
                    $remaining = Security::remainingAttempts('login', 5, 900);
                    $error = 'Email ou mot de passe incorrect.'
                        . ($remaining <= 2 ? " Attention : il vous reste {$remaining} tentative(s) avant blocage temporaire." : '');
                }
            }
        }

        $emailNonVerifie = $_SESSION['connexion_email_non_verifie'] ?? '';
        $this->render('connexion', [
            'pageTitle'          => 'Connexion - GLOBALO',
            'navActive'          => 'connexion',
            'error'              => $error,
            'email_non_verifie'  => $emailNonVerifie,
        ]);
    }

    public function deconnexion(): void
    {
        Auth::logout();
        $this->redirect(rtrim(BASE_URL ?? '', '/') . '/');
    }

    /** Change la langue (FR/EN) et redirige vers la page précédente ou l'accueil. */
    public function lang(): void
    {
        $params = $this->router->getParams();
        $code = isset($params[0]) ? strtolower(trim((string) $params[0])) : '';
        if (in_array($code, ['fr', 'en'], true)) {
            Lang::setLocale($code);
        }
        $base = rtrim(BASE_URL, '/');
        $back = $_SERVER['HTTP_REFERER'] ?? ($base . '/');
        // Ne faire confiance au referer que s'il pointe vers le même site (évite liens cassés en prod)
        $backHost = parse_url($back, PHP_URL_HOST);
        $baseHost = parse_url($base, PHP_URL_HOST);
        if ($backHost === null || $backHost !== $baseHost || strpos($back, $base) !== 0) {
            $back = $base . '/';
        }
        header('Location: ' . $back, true, 302);
        exit;
    }

    /** Bascule version bureau / mobile (multi-device) et redirige. */
    public function view(): void
    {
        $params = $this->router->getParams();
        $mode = isset($params[0]) ? strtolower(trim((string) $params[0])) : '';
        if (in_array($mode, ['desktop', 'mobile'], true)) {
            setcookie('view_mode', $mode, time() + 86400 * 365, '/');
        }
        $base = rtrim(BASE_URL, '/');
        $redirect = $mode === 'mobile' ? $base . '/app' : $base . '/';
        header('Location: ' . $redirect, true, 302);
        exit;
    }

    public function motDePasseOublie(): void
    {
        $message = '';
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Rate limiting : 3 demandes / 30 min par IP (anti-spam email)
            if (Security::isRateLimited('password_reset', 3, 1800)) {
                $error = 'Trop de demandes. Attendez 30 minutes avant de réessayer.';
                $this->render('mot_de_passe_oublie', ['pageTitle' => 'Mot de passe oublié - GLOBALO', 'message' => '', 'error' => $error]);
                return;
            }
            $email = Security::sanitizeEmail($_POST['email'] ?? '');
            $user = $this->userModel->findByEmail($email);
            if ($user) {
                $token = bin2hex(random_bytes(32));
                $this->userModel->setTokenReinitialisation($user['id'], $token);
                $resetLink = rtrim(BASE_URL ?? '', '/') . '/auth/reinitialiser?token=' . urlencode($token);
                $mailer = new MailerService();
                $subject = 'Réinitialisation de votre mot de passe - GLOBALO';
                $body  = "Bonjour,\n\n";
                $body .= "Vous avez demandé la réinitialisation de votre mot de passe.\n\n";
                $body .= "Cliquez sur le lien ci-dessous (valable 48h) :\n\n";
                $body .= $resetLink . "\n\n";
                $body .= "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.\n\n";
                $body .= "Cordialement,\nL'équipe GLOBALO";
                $sent = $mailer->send($email, $subject, $body);
                Security::recordFailedAttempt('password_reset');
                $message = 'Si un compte existe pour cette adresse, vous recevrez un email avec le lien de réinitialisation.';
                if (DEBUG) {
                    $message .= ' [DEV: ' . ($sent ? 'email envoyé — ' : 'email non envoyé (SMTP non configuré) — ') . $resetLink . ']';
                }
            } else {
                $message = 'Si un compte existe pour cette adresse, vous recevrez un email.';
            }
        }
        $this->render('mot_de_passe_oublie', [
            'pageTitle' => 'Mot de passe oublié - GLOBALO',
            'message' => $message,
            'error' => $error,
        ]);
    }

    public function verificationEnvoyee(): void
    {
        $link      = $_SESSION['verification_link'] ?? '';
        $email     = $_SESSION['verify_email']      ?? '';
        $emailSent = $_SESSION['email_sent']         ?? true;
        $resendMsg = $_SESSION['resend_message']     ?? '';
        unset(
            $_SESSION['verification_link'],
            $_SESSION['verify_email'],
            $_SESSION['email_sent'],
            $_SESSION['resend_message']
        );
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $this->render('verification_envoyee', [
            'pageTitle'         => 'Vérification envoyée - GLOBALO',
            'verification_link' => $link,
            'verify_email'      => $email,
            'email_sent'        => (bool) $emailSent,
            'resend_message'    => $resendMsg,
            'baseUrl'           => $baseUrl,
        ]);
    }

    public function renvoyerVerification(): void
    {
        $baseUrl = rtrim(BASE_URL ?? '', '/');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($baseUrl . '/auth/verification-envoyee');
            return;
        }

        // Rate limiting : 3 renvois / 15 min par IP
        if (Security::isRateLimited('resend_verif', 3, 900)) {
            $_SESSION['resend_message'] = 'error:Trop de tentatives. Attendez 15 minutes avant de réessayer.';
            $this->redirect($baseUrl . '/auth/verification-envoyee');
            return;
        }

        $email = Security::sanitizeEmail($_POST['email'] ?? ($_SESSION['resend_email'] ?? ''));
        unset($_SESSION['resend_email']);

        if ($email === '') {
            $_SESSION['resend_message'] = 'error:Adresse email manquante.';
            $this->redirect($baseUrl . '/auth/verification-envoyee');
            return;
        }

        $user = $this->userModel->findByEmail($email);

        // Réponse neutre si le compte n'existe pas (anti-énumération)
        if (!$user || !empty($user['email_verifie'])) {
            Security::recordFailedAttempt('resend_verif');
            $_SESSION['resend_message'] = 'success:Email de vérification renvoyé. Consultez votre boîte de réception (et vos spams).';
            $this->redirect($baseUrl . '/auth/verification-envoyee');
            return;
        }

        $tokenVerif  = bin2hex(random_bytes(32));
        $expireVerif = date('Y-m-d H:i:s', time() + 86400 * 2);
        $this->userModel->setTokenVerification((int) $user['id'], $tokenVerif, $expireVerif);

        $verifLink = $baseUrl . '/auth/verifier?token=' . urlencode($tokenVerif);
        $mailer    = new MailerService();
        try {
            $sent = $mailer->sendVerificationEmail($email, $verifLink);
        } catch (\Throwable $e) {
            $sent = false;
            error_log('[GLOBALO] sendVerificationEmail (renvoi) : ' . $e->getMessage());
        }

        Security::recordFailedAttempt('resend_verif');

        $_SESSION['verify_email']      = $email;
        $_SESSION['verification_link'] = $verifLink;
        $_SESSION['email_sent']        = $sent;
        if ($sent) {
            $_SESSION['resend_message'] = 'success:Email de vérification renvoyé avec succès à ' . $email . '. Consultez votre boîte de réception (et vos spams).';
        } else {
            $_SESSION['resend_message'] = 'error:L\'envoi a échoué. Vérifiez la configuration SMTP dans l\'administration du site.';
        }

        $this->redirect($baseUrl . '/auth/verification-envoyee');
    }

    public function verifier(): void
    {
        $token = $_GET['token'] ?? '';
        $error = '';
        $success = false;
        $startDate = null; // date de démarrage de l'abonnement programmé

        if ($token !== '') {
            $user = $this->userModel->findByTokenVerification($token);
            if ($user) {
                $this->userModel->setEmailVerifie((int) $user['id']);
                // Invalider le cache session du popup de vérification email
                unset($_SESSION['_email_verifie'], $_SESSION['_ev_user_email']);
                $success = true;

                // Programmer automatiquement l'abonnement gratuit pour le lendemain
                $userId = (int) $user['id'];
                $role   = $user['role'] ?? 'client';
                try {
                    $subscriptionService = new SubscriptionService();
                    $scheduled = $subscriptionService->planifierPourDemain($userId, $role);
                    if ($scheduled) {
                        $startDate = date('d/m/Y', strtotime('+1 day'));
                    }
                } catch (\Throwable $e) {
                    error_log('[Abonnement] planifierPourDemain échec: ' . $e->getMessage());
                }
            } else {
                $error = 'Lien invalide ou expiré.';
            }
        } else {
            $error = 'Token manquant.';
        }
        $this->render('verifier', [
            'pageTitle'  => 'Vérification email - GLOBALO',
            'error'      => $error,
            'success'    => $success,
            'startDate'  => $startDate,
        ]);
    }

    public function reinitialiser(): void
    {
        $token = $_GET['token'] ?? '';
        $error = '';
        $success = false;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token) {
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';
            if (strlen($password) < PASSWORD_MIN_LENGTH) {
                $error = 'Le mot de passe doit contenir au moins ' . PASSWORD_MIN_LENGTH . ' caractères.';
            } elseif ($password !== $confirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                if ($this->userModel->resetPasswordWithToken($token, Security::hashPassword($password))) {
                    $success = true;
                } else {
                    $error = 'Lien expiré ou invalide.';
                }
            }
        }
        $this->render('reinitialiser', [
            'pageTitle' => 'Nouveau mot de passe - GLOBALO',
            'token' => $token,
            'error' => $error,
            'success' => $success,
        ]);
    }

    /** Redirection post-connexion : espace du rôle (`/professeur`, `/client`, …) ou URL interne mémorisée. */
    private function redirectAfterLogin(?string $role = null): void
    {
        $next = $this->takePostLoginRedirect();
        if ($next !== null) {
            $this->redirect($next);
            return;
        }
        $this->redirect(Auth::dashboardUrl($role));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Google OAuth 2.0
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /auth/google — Redirige l'utilisateur vers Google Consent Screen. */
    public function google(): void
    {
        $service = new GoogleOAuthService();
        if (!$service->isConfigured()) {
            $_SESSION['flash_error'] = 'La connexion via Google n\'est pas encore configurée. Veuillez utiliser email + mot de passe.';
            $this->redirect(rtrim(BASE_URL, '/') . '/auth/connexion');
            return;
        }

        // Préserver le code de parrainage transmis via ?ref= ou déjà en session
        $refFromUrl = trim((string) ($_GET['ref'] ?? ''));
        if ($refFromUrl !== '') {
            $_SESSION['inscription_ref'] = $refFromUrl;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state']  = $state;
        $_SESSION['google_oauth_origin'] = $_SERVER['HTTP_REFERER'] ?? '';

        header('Location: ' . $service->getAuthUrl($state));
        exit;
    }

    /** GET /auth/google-callback — Google redirige ici avec code + state. */
    public function googleCallback(): void
    {
        $base    = rtrim(BASE_URL ?? '', '/');
        $service = new GoogleOAuthService();

        // ── Vérifications de sécurité ────────────────────────────────────────
        $state = $_GET['state'] ?? '';
        if (empty($state) || $state !== ($_SESSION['google_oauth_state'] ?? '')) {
            $_SESSION['flash_error'] = 'Erreur de sécurité OAuth (state invalide). Réessayez.';
            $this->redirect($base . '/auth/connexion');
            return;
        }
        unset($_SESSION['google_oauth_state']);

        if (!empty($_GET['error'])) {
            $_SESSION['flash_error'] = 'Connexion Google annulée.';
            $this->redirect($base . '/auth/connexion');
            return;
        }

        $code = $_GET['code'] ?? '';
        if ($code === '') {
            $_SESSION['flash_error'] = 'Code d\'autorisation manquant.';
            $this->redirect($base . '/auth/connexion');
            return;
        }

        // ── Échange du code contre un token ─────────────────────────────────
        $tokenData = $service->exchangeCode($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            $_SESSION['flash_error'] = 'Impossible d\'obtenir le token Google. Réessayez.';
            $this->redirect($base . '/auth/connexion');
            return;
        }

        $googleUser = $service->getUserInfo($tokenData['access_token']);
        if (!$googleUser || empty($googleUser['email'])) {
            $_SESSION['flash_error'] = 'Impossible de récupérer le profil Google. Réessayez.';
            $this->redirect($base . '/auth/connexion');
            return;
        }

        if (empty($googleUser['email_verified'])) {
            $_SESSION['flash_error'] = 'Votre adresse Gmail n\'est pas vérifiée. Veuillez la vérifier puis réessayer.';
            $this->redirect($base . '/auth/connexion');
            return;
        }

        // ── Chercher l'utilisateur en base ───────────────────────────────────
        $googleId = (string) ($googleUser['sub'] ?? '');
        $user     = $this->userModel->findByGoogleId($googleId);

        // Si pas trouvé par google_id, chercher par email (compte email existant)
        if (!$user) {
            $user = $this->userModel->findByEmail($googleUser['email']);
            if ($user) {
                // Associer le google_id au compte existant
                $this->userModel->attachGoogleId((int) $user['id'], $googleId);
            }
        }

        if ($user) {
            // ── Compte existant : connexion directe ──────────────────────────
            if (!(bool) ($user['actif'] ?? true)) {
                $_SESSION['flash_error'] = 'Votre compte est désactivé. Contactez le support.';
                $this->redirect($base . '/auth/connexion');
                return;
            }
            $role = Auth::normalizeRole((string) ($user['role'] ?? ''));
            Auth::login((int) $user['id'], $role);
            $this->userModel->updateDerniereConnexion((int) $user['id']);
            $this->redirectAfterLogin($role);
            return;
        }

        // ── Nouveau compte : stocker les données Google en session, choisir un rôle ──
        $_SESSION['google_pending'] = [
            'sub'          => $googleId,
            'email'        => $googleUser['email'],
            'given_name'   => $googleUser['given_name']  ?? '',
            'family_name'  => $googleUser['family_name'] ?? '',
            'name'         => $googleUser['name']        ?? '',
            'picture'      => $googleUser['picture']     ?? '',
        ];

        $this->redirect($base . '/auth/google-complet');
    }

    /** GET + POST /auth/google-complet — Choix du rôle et création du compte Google. */
    public function googleComplet(): void
    {
        $base = rtrim(BASE_URL ?? '', '/');

        // Données Google en session (obligatoire)
        $pending = $_SESSION['google_pending'] ?? null;
        if (!$pending) {
            $this->redirect($base . '/auth/inscription');
            return;
        }

        $errors = [];
        $paysEligibles = ['Mali', "Côte d'Ivoire", 'Sénégal', 'Bénin', 'Niger'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::validateCsrf();

            $role = $_POST['role'] ?? '';
            $pays = $_POST['pays'] ?? '';

            $rolesValides = ['client', 'expert', 'etudiant', 'professeur'];
            if (!in_array($role, $rolesValides, true)) {
                $errors[] = 'Veuillez choisir un rôle valide.';
            }
            if (!in_array($pays, $paysEligibles, true)) {
                $errors[] = 'Veuillez sélectionner un pays éligible.';
            }

            if (empty($errors)) {
                try {
                    $userId = $this->userModel->createFromGoogle($pending, $role, $pays);
                    $this->userModel->createPortefeuille($userId);

                    if ($role === 'expert') {
                        $this->userModel->createProfilExpert($userId);
                    } elseif (in_array($role, ['etudiant', 'professeur'], true)) {
                        $this->userModel->createProfilEtudiant($userId);
                        if ($role === 'professeur') {
                            (new \App\Models\ProfilProfesseurModel())->getOrCreateForUser($userId);
                        }
                    }

                    // Parrainage éventuel
                    if (!empty($_SESSION['inscription_ref'])) {
                        try {
                            (new ParrainageModel())->registerFilleul($_SESSION['inscription_ref'], $userId);
                        } catch (\Throwable $t) {
                            error_log('[Parrainage/Google] registerFilleul: ' . $t->getMessage());
                        }
                        unset($_SESSION['inscription_ref']);
                    }

                    unset($_SESSION['google_pending']);

                    // Programmer l'abonnement gratuit J+1 (Google = email déjà vérifié)
                    try {
                        (new SubscriptionService())->planifierPourDemain($userId, $role);
                    } catch (\Throwable $ex) {
                        error_log('[Abonnement/Google] planifierPourDemain: ' . $ex->getMessage());
                    }

                    $role = Auth::normalizeRole($role);
                    Auth::login($userId, $role);
                    // Email de bienvenue personnalisé (compte Google = email déjà vérifié)
                    try {
                        $dashUrl = rtrim(BASE_URL ?? '', '/') . '/' . $role;
                        (new MailerService())->sendWelcomeEmail(
                            $pending['email'],
                            $pending['given_name'] ?: $pending['name'],
                            $pending['family_name'] ?? '',
                            $role,
                            $dashUrl
                        );
                    } catch (\Throwable $e) {
                        error_log('[GLOBALO] sendWelcomeEmail (Google OAuth) : ' . $e->getMessage());
                    }
                    $_SESSION['flash_success'] = 'Bienvenue sur GLOBALO ! Votre compte Google a bien été créé.';
                    $this->redirectAfterLogin($role);
                    return;
                } catch (\Throwable $e) {
                    error_log('[Google OAuth] createFromGoogle: ' . $e->getMessage());
                    $errors[] = 'Une erreur est survenue lors de la création de votre compte. Réessayez.';
                }
            }
        }

        $this->render('google_complet', [
            'pageTitle'      => 'Finaliser votre inscription - GLOBALO',
            'pending'        => $pending,
            'errors'         => $errors,
            'pays_eligibles' => $paysEligibles,
        ]);
    }

    /**
     * URL interne stockée avant la page de connexion (redirection après auth).
     */
    private function takePostLoginRedirect(): ?string
    {
        $raw = $_SESSION['post_login_redirect'] ?? null;
        unset($_SESSION['post_login_redirect']);
        if (!is_string($raw)) {
            return null;
        }
        $url = trim($raw);
        if ($url === '' || strlen($url) > 2048) {
            return null;
        }
        $base = rtrim(BASE_URL ?? '', '/');
        if (strpos($url, $base) !== 0) {
            return null;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || $path === '/') {
            return null;
        }

        return $url;
    }
}
