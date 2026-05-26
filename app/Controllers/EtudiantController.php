<?php
/**
 * GLOBALO - Espace Étudiant
 * Gestion des exercices par matières universitaires (Afrique de l'Ouest)
 */

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Core\Security;
use App\Models\EtudiantModel;
use App\Models\ExerciceModel;
use App\Models\MatiereModel;
use App\Models\UtilisateurModel;
use App\Models\NotificationModel;
use App\Models\PortefeuilleModel;
use App\Models\ParametreModel;
use App\Models\PaiementModel;
use App\Models\RetraitProfModel;
use App\Models\ProfilProfesseurModel;
use App\Models\SessionProfesseurModel;
use App\Services\PayTechPaymentService;
use App\Services\IntouchPaymentService;
use App\Services\ExerciceClotureService;

class EtudiantController extends Controller
{
    private EtudiantModel  $etudiantModel;
    private ExerciceModel   $exerciceModel;
    private MatiereModel   $matiereModel;

    /** Préfixe URL selon le rôle : `/app` (mobile), `/professeur` ou `/etudiant`. */
    private function basePath(): string
    {
        if ($this->router->isApp()) {
            return '/app';
        }

        return Auth::role() === 'professeur' ? '/professeur' : '/etudiant';
    }

    /** Chemin relatif (routes `/app/*` plates pour le professeur). */
    private function spacePath(string $suffix = ''): string
    {
        if ($suffix !== '' && $suffix[0] !== '/') {
            $suffix = '/' . $suffix;
        }
        $bp = $this->basePath();
        if ($bp !== '/app') {
            return $bp . $suffix;
        }
        if (Auth::role() !== 'professeur') {
            return '/etudiant' . $suffix;
        }
        if ($suffix === '' || $suffix === '/') {
            return '/app/professeur';
        }
        $seg   = trim($suffix, '/');
        $first = explode('/', $seg)[0] ?? '';
        $flat  = ['exercices-disponibles', 'proposer-exercice', 'prendre-exercice', 'corriger', 'compte'];
        if (in_array($first, $flat, true)) {
            return '/app/' . $seg;
        }

        return '/professeur' . $suffix;
    }

    /** URL absolue sous l'espace étudiant / professeur. */
    private function spaceUrl(string $suffix = ''): string
    {
        return rtrim(BASE_URL ?? '', '/') . $this->spacePath($suffix);
    }

    private function etudiantRender(string $view, array $data = []): void
    {
        if (!isset($data['base_path'])) {
            $data['base_path'] = $this->basePath();
        }
        if (Auth::role() === 'professeur' && !isset($data['prof_base_path'])) {
            $data['prof_base_path'] = $data['base_path'];
        }
        $this->render($view, $data);
    }

    public function __construct(Router $router)
    {
        parent::__construct($router);
        Auth::requireRole('etudiant', 'professeur');

        $role = Auth::role();
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(BASE_URL ?? '', '/');
        if ($base !== '' && strpos($path, $base) === 0) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        $first = trim(explode('/', trim($path, '/'))[0] ?? '', '/');

        if ($role === 'professeur' && $first === 'etudiant') {
            header('Location: ' . $base . preg_replace('#^/etudiant#', '/professeur', $path, 1), true, 302);
            exit;
        }
        if ($role === 'etudiant' && $first === 'professeur') {
            header('Location: ' . $base . preg_replace('#^/professeur#', '/etudiant', $path, 1), true, 302);
            exit;
        }

        $this->etudiantModel = new EtudiantModel();
        $this->exerciceModel = new ExerciceModel();
        $this->matiereModel  = new MatiereModel();
    }

    /** Tableau de bord étudiant ou professeur. */
    public function index(): void
    {
        $userId   = (int) Auth::id();
        $role     = Auth::role();
        $profil   = $this->etudiantModel->getByUserId($userId);
        $stats    = $role === 'professeur' ? [] : $this->exerciceModel->getStats($userId);
        $recents  = $role === 'professeur' ? $this->exerciceModel->getEnChargeProfesseur($userId, 5) : $this->exerciceModel->getByEtudiant($userId, 5);
        $matieres = $this->etudiantModel->getMatieres($userId);

        // Pour les professeurs : compter les exercices ouverts disponibles
        $nbExercicesDisponibles = 0;
        if ($role === 'professeur') {
            try {
                $profilProf = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
                $profilId   = $profilProf ? (int) $profilProf['id'] : 0;
                $nbExercicesDisponibles = count($this->exerciceModel->getOuvertsPourProfesseur([], 50, $profilId));
            } catch (\Throwable $e) {
                $nbExercicesDisponibles = 0;
            }
        }

        $pageTitle = $role === 'professeur' ? 'Tableau de bord - Espace Professeur' : 'Tableau de bord - Espace Étudiant';

        $this->etudiantRender('index', [
            'pageTitle'                => $pageTitle,
            'navActive'                => $role === 'professeur' ? 'professeur' : 'etudiant',
            'user'                     => ['id' => $userId, 'role' => $role],
            'profil'                   => $profil,
            'stats'                    => $stats,
            'recents'                  => $recents,
            'matieres'                 => $matieres,
            'nb_exercices_disponibles' => $nbExercicesDisponibles,
        ]);
    }

    /** Liste & soumission d'exercices. */
    public function exercices(): void
    {
        $params = $this->router->getParams();
        if (($params[0] ?? '') === 'nouveau') {
            $this->nouvelExercice();
            return;
        }
        if (!empty($params[0]) && is_numeric($params[0])) {
            $this->detailExercice((int) $params[0]);
            return;
        }

        $userId    = (int) Auth::id();
        $matiereId = isset($_GET['matiere']) ? (int) $_GET['matiere'] : 0;
        $exercices = $matiereId
            ? $this->exerciceModel->getByEtudiantAndMatiere($userId, $matiereId)
            : $this->exerciceModel->getByEtudiant($userId);
        $matieres  = $this->matiereModel->getActives();
        $matiereCourante = $matiereId ? $this->matiereModel->find($matiereId) : null;

        $this->etudiantRender('exercices', [
            'pageTitle'        => 'Mes exercices - Espace Étudiant',
            'navActive'        => 'exercices',
            'user'             => ['id' => $userId, 'role' => 'etudiant'],
            'exercices'        => $exercices,
            'matieres'         => $matieres,
            'matiere_id'       => $matiereId,
            'matiere_courante' => $matiereCourante,
        ]);
    }

    /** Formulaire de soumission d'un exercice. */
    private function nouvelExercice(): void
    {
        $userId   = (int) Auth::id();
        $profil   = $this->etudiantModel->getByUserId($userId);
        $matieres = $this->matiereModel->getActives();
        $errors   = [];
        $data     = [
            'titre'             => '',
            'description'       => '',
            'matiere_id'        => '',
            'type_exercice'     => 'devoir',
            'niveau_difficulte' => 'moyen',
            'urgence'           => 'normale',
            'lien_ressource'    => '',
            'date_limite'       => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'titre'             => Security::sanitizeString($_POST['titre'] ?? '', 250),
                'description'       => Security::sanitizeString($_POST['description'] ?? '', 8000),
                'matiere_id'        => (int) ($_POST['matiere_id'] ?? 0) ?: null,
                'type_exercice'     => in_array($_POST['type_exercice'] ?? '', ['devoir','examen','tp','projet','dissertation','qcm','oral','autre'], true)
                                        ? $_POST['type_exercice'] : 'devoir',
                'niveau_difficulte' => in_array($_POST['niveau_difficulte'] ?? '', ['facile','moyen','difficile','tres_difficile'], true)
                                        ? $_POST['niveau_difficulte'] : 'moyen',
                'urgence'           => in_array($_POST['urgence'] ?? '', ['normale','urgent','tres_urgent'], true)
                                        ? $_POST['urgence'] : 'normale',
                'lien_ressource'    => trim($_POST['lien_ressource'] ?? ''),
                'date_limite'       => trim($_POST['date_limite'] ?? ''),
            ];

            if (empty($data['titre'])) {
                $errors[] = 'Le titre de l\'exercice est requis.';
            }
            if (empty($data['description'])) {
                $errors[] = 'La description / énoncé est requise.';
            }

            $lienRessource = null;
            if ($data['lien_ressource'] !== '') {
                if (filter_var($data['lien_ressource'], FILTER_VALIDATE_URL) && preg_match('/^https?:\/\//i', $data['lien_ressource'])) {
                    $lienRessource = Security::sanitizeString($data['lien_ressource'], 1000);
                } else {
                    $errors[] = 'Lien ressource invalide (doit commencer par https://).';
                }
            }

            $dateLimite = null;
            if (!empty($data['date_limite'])) {
                $dl = \DateTime::createFromFormat('Y-m-d', $data['date_limite']);
                $dateLimite = $dl ? $dl->format('Y-m-d 23:59:59') : null;
                if (!$dateLimite) {
                    $errors[] = 'Date limite invalide.';
                }
            }

            // Pièce jointe
            $fichierPath = null;
            $allowedMimes = [
                'application/pdf'                                                          => 'pdf',
                'application/msword'                                                       => 'doc',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'  => 'docx',
                'application/vnd.ms-excel'                                                 => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'        => 'xlsx',
                'image/jpeg'                                                               => 'jpg',
                'image/png'                                                                => 'png',
                'text/plain'                                                               => 'txt',
                'application/zip'                                                          => 'zip',
            ];
            $fichierErr = $_FILES['fichier']['error'] ?? UPLOAD_ERR_NO_FILE;
            $tmpFichier = null;
            $tmpExt     = null;
            if (!empty($_FILES['fichier']['name']) && $fichierErr !== UPLOAD_ERR_NO_FILE) {
                if ($fichierErr === UPLOAD_ERR_OK) {
                    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : null;
                    $mime  = $finfo ? finfo_file($finfo, $_FILES['fichier']['tmp_name']) : ($_FILES['fichier']['type'] ?? '');
                    if ($finfo) finfo_close($finfo);
                    $size  = (int) ($_FILES['fichier']['size'] ?? 0);
                    if ($size > 0 && $size <= 10 * 1024 * 1024 && isset($allowedMimes[$mime])) {
                        $tmpFichier = $_FILES['fichier']['tmp_name'];
                        $tmpExt     = $allowedMimes[$mime];
                    } else {
                        $errors[] = 'Pièce jointe : format non autorisé ou trop volumineux (max 10 Mo).';
                    }
                } else {
                    $errors[] = 'Erreur lors du téléchargement du fichier.';
                }
            }

            if (empty($errors)) {
                $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
                $newId = $this->exerciceModel->create([
                    'etudiant_id'       => $userId,
                    'matiere_id'        => $data['matiere_id'],
                    'titre'             => $data['titre'],
                    'description'       => $data['description'],
                    'type_exercice'     => $data['type_exercice'],
                    'niveau_difficulte' => $data['niveau_difficulte'],
                    'urgence'           => $data['urgence'],
                    'lien_ressource'    => $lienRessource,
                    'date_limite'       => $dateLimite,
                ]);

                if ($tmpFichier && $tmpExt && is_uploaded_file($tmpFichier)) {
                    $destDir = $uploadPath . DIRECTORY_SEPARATOR . 'exercices' . DIRECTORY_SEPARATOR . $newId;
                    if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
                    $dest = $destDir . DIRECTORY_SEPARATOR . 'enonce.' . $tmpExt;
                    if (move_uploaded_file($tmpFichier, $dest)) {
                        $this->exerciceModel->update($newId, ['fichier' => 'exercices/' . $newId . '/enonce.' . $tmpExt]);
                    }
                }

                $_SESSION['flash_success'] = 'Exercice soumis avec succès.';
                $this->redirect($this->spaceUrl('/exercices'));
                return;
            }
        }

        $this->etudiantRender('nouvel_exercice', [
            'pageTitle' => 'Soumettre un exercice - Espace Étudiant',
            'navActive' => 'exercices',
            'user'      => ['id' => $userId, 'role' => 'etudiant'],
            'profil'    => $profil,
            'matieres'  => $matieres,
            'errors'    => $errors,
            'data'      => $data,
        ]);
    }

    /** Détail d'un exercice (étudiant). */
    private function detailExercice(int $id): void
    {
        $userId   = (int) Auth::id();
        $exercice = $this->exerciceModel->getByIdForEtudiant($id, $userId);
        if (!$exercice) {
            $this->redirect($this->spaceUrl('/exercices'));
            return;
        }
        $solde = (new PortefeuilleModel())->getSolde($userId);
        $propositions = [];
        try {
            $propositions = (new \App\Models\ExercicePropositionModel())->getByExercice($id);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] propositions exercice: ' . $e->getMessage());
        }
        $clotureSvc = new ExerciceClotureService();
        $this->etudiantRender('detail_exercice', [
            'pageTitle'    => 'Exercice - ' . Security::escape($exercice['titre']),
            'navActive'    => 'exercices',
            'user'         => ['id' => $userId, 'role' => Auth::role()],
            'exercice'     => $exercice,
            'solde_wallet' => $solde,
            'propositions' => $propositions,
            'can_choose_proposition' => ($exercice['statut'] ?? '') === 'ouvert' && empty($exercice['expert_id']),
            'can_confirm_exercice' => Auth::role() === 'etudiant' ? $clotureSvc->peutConfirmer($exercice) : false,
            'base_path'    => $this->basePath(),
        ]);
    }

    // =========================================================================
    // ESPACE PROFESSEUR — gestion des corrections
    // =========================================================================

    /** Liste des exercices disponibles pour le professeur connecté. */
    public function exercicesDisponibles(): void
    {
        $userId = (int) Auth::id();
        if (Auth::role() !== 'professeur') {
            $this->redirect($this->spaceUrl('/exercices'));
            return;
        }
        $profilProf = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
        $profilId   = $profilProf ? (int) $profilProf['id'] : 0;
        $profValide = $profilProf && !empty($profilProf['valide_par_admin']);

        // Tous les exercices ouverts sont visibles par tous les professeurs (sans filtre matières)
        $disponibles = $this->exerciceModel->getOuvertsPourProfesseur([], 50, $profilId);
        $enCharge    = $this->exerciceModel->getEnChargeProfesseur($userId);

        $this->etudiantRender('exercices_disponibles', [
            'pageTitle'    => 'Exercices disponibles',
            'navActive'    => 'exercices',
            'user'         => ['id' => $userId, 'role' => 'professeur'],
            'disponibles'  => $disponibles,
            'en_charge'    => $enCharge,
            'prof_valide'  => $profValide,
        ]);
    }

    /** Proposition de correction (professeur) sur un exercice ouvert. */
    public function proposerExercice(): void
    {
        $userId = (int) Auth::id();
        if (Auth::role() !== 'professeur') {
            $this->redirect($this->spaceUrl());
            return;
        }
        $profil = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
        if (!$profil || empty($profil['valide_par_admin'])) {
            $_SESSION['flash_error'] = 'Profil professeur non validé.';
            $this->redirect($this->spaceUrl('/exercices-disponibles'));
            return;
        }
        $profilId = (int) $profil['id'];
        $exerciceId = (int) ($this->router->getParams()[0] ?? 0);
        $matiereIds = (new ProfilProfesseurModel())->getMatiereIdsForProfil($profilId);
        $exercices = $this->exerciceModel->getOuvertsPourProfesseur($matiereIds, 30, $profilId);
        $exercice = null;
        foreach ($exercices as $ex) {
            if ((int) $ex['id'] === $exerciceId) {
                $exercice = $ex;
                break;
            }
        }
        $base = rtrim(BASE_URL ?? '', '/');
        $bp = $this->basePath();
        if (!$exercice) {
            $_SESSION['flash_error'] = 'Exercice introuvable ou non disponible.';
            $this->redirect($base . $bp . '/exercices-disponibles');
            return;
        }
        $propModel = new \App\Models\ExercicePropositionModel();
        $existante = $propModel->getByExerciceAndProfesseur($exerciceId, $profilId);
        $errors = [];
        $propData = [
            'presentation'  => (string) ($profil['titre'] ?? ''),
            'tarif_propose' => (string) ($profil['tarif_horaire'] ?? '5000'),
            'delai_jours'   => '3',
            'message'       => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existante) {
            Security::validateCsrf();
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
                $errors[] = 'La présentation est requise.';
            }
            if ($propData['message'] === '') {
                $errors[] = 'Le message est requis.';
            }
            if ($tarif < 500) {
                $errors[] = 'Tarif minimum 500 FCFA.';
            }
            if ($delai < 1 || $delai > 90) {
                $errors[] = 'Délai entre 1 et 90 jours.';
            }
            if (empty($errors)) {
                try {
                    $propModel->create([
                        'exercice_id'          => $exerciceId,
                        'profil_professeur_id' => $profilId,
                        'presentation'         => $propData['presentation'],
                        'message'              => $propData['message'],
                        'tarif_propose'        => $tarif,
                        'delai_jours'          => $delai,
                        'competences_cles'     => $propData['competences_cles'] !== '' ? $propData['competences_cles'] : null,
                    ]);
                    $etudiantId = (int) ($exercice['etudiant_id'] ?? 0);
                    if ($etudiantId > 0) {
                        (new \App\Models\NotificationModel())->create(
                            $etudiantId,
                            'nouvelle_proposition',
                            'Nouvelle proposition',
                            'Un professeur propose de corriger : ' . ($exercice['titre'] ?? ''),
                            $base . '/etudiant/exercices/' . $exerciceId
                        );
                    }
                    $_SESSION['flash_success'] = 'Proposition envoyée. Elle ne marque pas l\'exercice comme corrigé : l\'étudiant confirmera après réception de la correction.';
                    $this->redirect($base . $bp . '/exercices-disponibles');
                    return;
                } catch (\Throwable $e) {
                    error_log('[GLOBALO] proposition prof: ' . $e->getMessage());
                    $errors[] = 'Enregistrement impossible.';
                }
            }
        }

        $this->etudiantRender('proposer_exercice', [
            'pageTitle'             => 'Proposer une correction',
            'navActive'             => 'exercices',
            'user'                  => ['id' => $userId, 'role' => 'professeur'],
            'exercice'              => $exercice,
            'proposition_existante' => $existante,
            'prop_data'             => $propData,
            'errors'                => $errors,
        ]);
    }

    public function accepterPropositionExercice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || Auth::role() !== 'etudiant') {
            $this->redirect($this->spaceUrl('/exercices'));
            return;
        }
        Security::validateCsrf();
        $propId = (int) ($this->router->getParams()[0] ?? 0);
        $result = (new \App\Services\PropositionService())->accepterPropositionExercice(
            $propId,
            (int) Auth::id(),
            (Auth::role() === 'professeur' ? '/professeur' : '/etudiant')
        );
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        $prop = (new \App\Models\ExercicePropositionModel())->find($propId);
        $this->redirect($this->spaceUrl('/exercices/' . ($prop ? (int) $prop['exercice_id'] : '')));
    }

    public function refuserPropositionExercice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || Auth::role() !== 'etudiant') {
            $this->redirect($this->spaceUrl('/exercices'));
            return;
        }
        Security::validateCsrf();
        $propId = (int) ($this->router->getParams()[0] ?? 0);
        $result = (new \App\Services\PropositionService())->refuserPropositionExercice($propId, (int) Auth::id());
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        $prop = (new \App\Models\ExercicePropositionModel())->find($propId);
        $this->redirect($this->spaceUrl('/exercices/' . ($prop ? (int) $prop['exercice_id'] : '')));
    }

    /** L'étudiant confirme que son exercice est résolu (après correction reçue / débloquée). */
    public function confirmerExerciceResolu(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/etudiant/exercices');
            return;
        }
        if (Auth::role() !== 'etudiant') {
            $_SESSION['flash_error'] = 'Action réservée aux étudiants.';
            $this->redirect($this->spaceUrl());
            return;
        }
        Security::validateCsrf();
        $exerciceId = (int) ($this->router->getParams()[0] ?? 0);
        if ($exerciceId <= 0) {
            $_SESSION['flash_error'] = 'Exercice invalide.';
            $this->redirect(rtrim(BASE_URL ?? '', '/') . '/etudiant/exercices');
            return;
        }
        $result = (new ExerciceClotureService())->confirmerParEtudiant($exerciceId, (int) Auth::id());
        $_SESSION[$result['ok'] ? 'flash_success' : 'flash_error'] = $result['message'];
        $base = rtrim(BASE_URL ?? '', '/');
        $id = (int) ($result['exercice_id'] ?? $exerciceId);
        $this->redirect($base . '/etudiant/exercices/' . ($id > 0 ? $id : ''));
    }

    /** POST : Professeur prend en charge un exercice ouvert. */
    public function prendreExercice(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->spaceUrl('/exercices-disponibles'));
            return;
        }
        Security::validateCsrf();
        $userId = (int) Auth::id();
        if (Auth::role() !== 'professeur') {
            $this->redirect($this->spaceUrl());
            return;
        }

        // Même vérification que proposerExercice : profil doit être validé par l'admin
        $profil = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
        if (!$profil || empty($profil['valide_par_admin'])) {
            $_SESSION['flash_error'] = 'Votre profil doit être validé par un administrateur avant de pouvoir prendre en charge des exercices.';
            $this->redirect($this->spaceUrl('/exercices-disponibles'));
            return;
        }

        $exerciceId = (int) ($_POST['exercice_id'] ?? 0);
        if ($exerciceId <= 0 || !$this->exerciceModel->prendreEnCharge($exerciceId, $userId)) {
            $_SESSION['flash_error'] = 'Impossible de prendre cet exercice en charge (déjà assigné ou inexistant).';
            $this->redirect($this->spaceUrl('/exercices-disponibles'));
            return;
        }
        $_SESSION['flash_success'] = 'Exercice pris en charge. Soumettez votre correction.';
        $this->redirect($this->spaceUrl('/corriger/' . $exerciceId));
    }

    /** GET + POST : Formulaire de correction d'un exercice (professeur). */
    public function corriger(): void
    {
        $userId = (int) Auth::id();
        if (Auth::role() !== 'professeur') {
            $this->redirect($this->spaceUrl());
            return;
        }
        $params     = $this->router->getParams();
        $exerciceId = (int) ($params[0] ?? 0);
        $exercice   = $this->exerciceModel->getEnChargePourProfesseur($exerciceId, $userId);
        if (!$exercice) {
            $_SESSION['flash_error'] = 'Exercice introuvable ou non assigné à votre compte.';
            $this->redirect($this->spaceUrl('/exercices-disponibles'));
            return;
        }

        if (($exercice['statut'] ?? '') !== 'en_cours') {
            $_SESSION['flash_error'] = (($exercice['statut'] ?? '') === 'correction_livree')
                ? 'La correction a déjà été envoyée. En attente de validation par l\'étudiant.'
                : 'Cet exercice n\'est plus modifiable depuis cet écran.';
            $this->redirect($this->spaceUrl('/exercices-disponibles'));
            return;
        }

        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::validateCsrf();
            $solution    = Security::sanitizeString($_POST['solution']           ?? '', 10000);
            $commentaire = Security::sanitizeString($_POST['commentaire_expert'] ?? '', 3000);
            $noteRaw     = $_POST['note_finale'] ?? '';
            $note        = ($noteRaw !== '' && is_numeric($noteRaw))
                           ? min(20.0, max(0.0, (float) $noteRaw))
                           : null;

            if (empty($solution)) {
                $errors[] = 'La solution est obligatoire.';
            }

            if (empty($errors)) {
                $prixDefaut = (float) (new ParametreModel())->get('prix_correction_exercice_xof', '500');
                $ok = $this->exerciceModel->soumettreSolution(
                    $exerciceId, $userId, $solution, $commentaire, $note, $prixDefaut
                );
                if ($ok) {
                    try {
                        (new NotificationModel())->create(
                            (int) $exercice['etudiant_id'],
                            'correction',
                            'Correction disponible',
                            'La correction de « ' . ($exercice['titre'] ?? '') . ' » est prête. Consultez-la, payez si nécessaire, puis confirmez que votre demande est résolue.',
                            '/etudiant/exercices/' . $exerciceId
                        );
                    } catch (\Throwable $t) {}
                    $_SESSION['flash_success'] = 'Correction envoyée. L\'exercice ne sera marqué résolu qu\'après confirmation par l\'étudiant.';
                    $this->redirect($this->spaceUrl('/exercices-disponibles'));
                    return;
                }
                $errors[] = 'Erreur lors de l\'enregistrement. Réessayez.';
            }
        }

        $formData = [
            'solution'           => '',
            'commentaire_expert' => '',
            'note_finale'        => '',
        ];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
            $formData = [
                'solution'           => (string) ($_POST['solution'] ?? ''),
                'commentaire_expert' => (string) ($_POST['commentaire_expert'] ?? ''),
                'note_finale'        => (string) ($_POST['note_finale'] ?? ''),
            ];
        }

        $this->etudiantRender('corriger_exercice', [
            'pageTitle' => 'Corriger : ' . Security::escape($exercice['titre']),
            'navActive' => 'exercices',
            'user'      => ['id' => $userId, 'role' => 'professeur'],
            'exercice'  => $exercice,
            'errors'    => $errors,
            'form'      => $formData,
        ]);
    }

    /** POST : L'étudiant paye pour débloquer la correction (débit portefeuille atomique). */
    public function payerCorrection(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($this->spaceUrl('/exercices'));
            return;
        }
        Security::validateCsrf();

        $userId     = (int) Auth::id();
        $params     = $this->router->getParams();
        $exerciceId = (int) ($params[0] ?? ($_POST['exercice_id'] ?? 0));
        $baseUrl    = rtrim(BASE_URL, '/');

        $exercice = $this->exerciceModel->getByIdForEtudiant($exerciceId, $userId);
        if (!$exercice || ($exercice['paiement_statut'] ?? '') !== 'en_attente') {
            $_SESSION['flash_error'] = 'Exercice introuvable ou correction déjà payée.';
            $this->redirect($this->spaceUrl('/exercices'));
            return;
        }

        $prix        = (float) ($exercice['prix_correction'] ?? 500);
        $portefeuille = new PortefeuilleModel();

        if ($portefeuille->getSolde($userId) < $prix) {
            $manque = $prix - $portefeuille->getSolde($userId);
            $_SESSION['flash_error'] = sprintf(
                'Solde insuffisant. Il vous manque %.0f XOF. Rechargez votre portefeuille.',
                $manque
            );
            $this->redirect($this->spaceUrl('/exercices/' . $exerciceId));
            return;
        }

        $reference = 'CORR-' . $exerciceId . '-' . time();
        $db        = \App\Core\Database::getInstance();
        $db->beginTransaction();

        try {
            // Débit atomique (la requête inclut WHERE solde >= montant)
            if (!$portefeuille->debiter($userId, $prix, $reference)) {
                $db->rollBack();
                $_SESSION['flash_error'] = 'Solde insuffisant au moment du paiement. Réessayez.';
                $this->redirect($this->spaceUrl('/exercices/' . $exerciceId));
                return;
            }

            // Déblocage atomique (la requête inclut WHERE paiement_statut = 'en_attente')
            if (!$this->exerciceModel->marquerPaye($exerciceId, $reference)) {
                // Race condition : un autre paiement a déjà débloqué → rembourser et annuler
                $portefeuille->crediter($userId, $prix);
                $db->rollBack();
                $_SESSION['flash_error'] = 'Cette correction est déjà débloquée.';
                $this->redirect($this->spaceUrl('/exercices/' . $exerciceId));
                return;
            }

            $db->commit();
        } catch (\Throwable $t) {
            $db->rollBack();
            $portefeuille->crediter($userId, $prix); // remboursement de sécurité
            error_log('[GLOBALO] payerCorrection erreur DB: exercice=' . $exerciceId . ' ' . $t->getMessage());
            $_SESSION['flash_error'] = 'Erreur technique. Votre solde a été rétabli. Réessayez.';
            $this->redirect($this->spaceUrl('/exercices/' . $exerciceId));
            return;
        }

        // Créditer le professeur (hors transaction principale — non-bloquant)
        if (!empty($exercice['expert_id'])) {
            try {
                $commPct       = (float) (new ParametreModel())->get('commission_correction_pct', '20');
                $netProfesseur = round($prix * (1 - $commPct / 100), 2);
                $portefeuille->crediter((int) $exercice['expert_id'], $netProfesseur);
            } catch (\Throwable $t) {
                error_log('[GLOBALO] Crédit professeur échoué: exercice=' . $exerciceId
                    . ' expert_id=' . ($exercice['expert_id'] ?? 'N/A')
                    . ' montant=' . round($prix * (1 - ((float)(new ParametreModel())->get('commission_correction_pct','20')) / 100), 2)
                    . ' ref=' . $reference . ' err=' . $t->getMessage());
            }
        }

        $_SESSION['flash_success'] = 'Paiement de ' . number_format($prix, 0, ',', ' ') . ' XOF effectué. La correction est maintenant accessible.';
        $this->redirect($this->spaceUrl('/exercices/' . $exerciceId));
    }

    /** Réserver une session avec un professeur (étudiant uniquement). */
    public function reserverProfesseur(): void
    {
        if (Auth::role() !== 'etudiant') {
            $this->redirect($this->spaceUrl());
            return;
        }
        $params = $this->router->getParams();
        $profilId = (int) ($params[0] ?? 0);
        $baseUrl = rtrim(BASE_URL ?? '', '/');

        $profilModel = new ProfilProfesseurModel();
        $professeur = $profilId ? $profilModel->getByIdPublic($profilId) : null;
        if (!$professeur || empty($professeur['valide_par_admin']) || !$professeur['disponible']) {
            $_SESSION['flash_error'] = 'Professeur introuvable, non validé ou non disponible.';
            $this->redirect($this->router->isApp() ? $baseUrl . '/app/professeurs' : $baseUrl . '/professeurs');
            return;
        }

        $matieres = $this->matiereModel->getActives();
        $errors = [];
        $data = [
            'matiere_id'        => '',
            'date_debut_prevue' => date('Y-m-d'),
            'heure'             => '14',
            'minute'            => '00',
            'duree_heures'      => '1',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Security::validateCsrf();
            $data['matiere_id'] = (int) ($_POST['matiere_id'] ?? 0) ?: null;
            $data['date_debut_prevue'] = trim($_POST['date_debut_prevue'] ?? '');
            $data['heure'] = max(0, min(23, (int) ($_POST['heure'] ?? 14)));
            $data['minute'] = max(0, min(59, (int) ($_POST['minute'] ?? 0)));
            $data['duree_heures'] = max(0.5, min(4, (float) ($_POST['duree_heures'] ?? 1)));

            if (empty($data['date_debut_prevue']) || strtotime($data['date_debut_prevue']) < strtotime('today')) {
                $errors[] = 'Veuillez choisir une date valide (aujourd\'hui ou plus tard).';
            }

            $tarif = (float) $professeur['tarif_horaire'];
            if ($tarif <= 0) {
                $errors[] = 'Ce professeur n\'a pas encore défini son tarif horaire. Veuillez le contacter directement.';
            }

            if (empty($errors)) {
                $dateDebut = $data['date_debut_prevue'] . ' ' . sprintf('%02d:%02d:00', $data['heure'], $data['minute']);
                $montant = round($tarif * $data['duree_heures'], 2);

                $sessionModel = new SessionProfesseurModel();
                $sessionModel->create([
                    'etudiant_id'       => (int) Auth::id(),
                    'professeur_id'     => (int) $professeur['utilisateur_id'],
                    'matiere_id'        => $data['matiere_id'],
                    'date_debut_prevue' => $dateDebut,
                    'duree_heures'      => $data['duree_heures'],
                    'tarif_horaire'     => $tarif,
                    'montant_total'     => $montant,
                ]);

                try {
                    (new NotificationModel())->create(
                        (int) $professeur['utilisateur_id'],
                        'session_professeur',
                        'Nouvelle demande de session',
                        'Un étudiant souhaite réserver une session avec vous pour le ' . date('d/m/Y à H:i', strtotime($dateDebut)) . '.',
                        '/professeur'
                    );
                } catch (\Throwable $t) {}

                $_SESSION['flash_success'] = 'Demande de session envoyée au professeur. Vous serez notifié de sa réponse.';
                $this->redirect($baseUrl . '/etudiant');
                return;
            }
        }

        $pid = (int) ($professeur['id'] ?? 0);
        $listeProfsUrl = $this->router->isApp() ? $baseUrl . '/app/professeurs' : $baseUrl . '/professeurs';
        $retourProfUrl = $this->router->isApp() ? $baseUrl . '/app/professeurs/' . $pid : $baseUrl . '/professeurs/show/' . $pid;

        $this->etudiantRender('reserver_professeur', [
            'pageTitle'        => 'Réserver ' . Security::escape($professeur['titre'] ?? ''),
            'navActive'        => 'etudiant',
            'user'             => ['id' => Auth::id(), 'role' => 'etudiant'],
            'professeur'       => $professeur,
            'matieres'         => $matieres,
            'errors'           => $errors,
            'data'             => $data,
            'listeProfsUrl'    => $listeProfsUrl,
            'retourProfUrl'    => $retourProfUrl,
            'formReserverUrl'  => $this->router->isApp()
                ? $baseUrl . '/app/reserver-professeur/' . $pid
                : $baseUrl . '/etudiant/reserver-professeur/' . $pid,
        ]);
    }

    /** Gestion du profil étudiant et des matières maîtrisées. */
    public function profil(): void
    {
        $userId = (int) Auth::id();
        $role   = (string) Auth::role();

        $profil = [];
        $matieres = [];
        $mesMatieres = [];
        $mesMatiereIds = [];
        $profilProfesseur = [];
        $errors = [];

        // Charger la page sans planter si certaines tables / colonnes posent problème.
        try {
            $profil = $this->etudiantModel->getByUserId($userId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] profil: getByUserId failed userId=' . $userId . ' err=' . $e->getMessage());
            $profil = [];
        }

        if ($role === 'professeur') {
            try {
                $profilProfesseurModel = new ProfilProfesseurModel();
                $profilProfesseur = $profilProfesseurModel->getOrCreateForUser($userId) ?? [];
            } catch (\Throwable $e) {
                error_log('[GLOBALO] profil: getProfilProfesseur failed userId=' . $userId . ' err=' . $e->getMessage());
                $profilProfesseur = [];
            }
        }

        try {
            $matieres = $this->matiereModel->getActivesGrouped();
        } catch (\Throwable $e) {
            error_log('[GLOBALO] profil: getActivesGrouped failed userId=' . $userId . ' err=' . $e->getMessage());
            $matieres = [];
        }

        try {
            $mesMatieres = $this->etudiantModel->getMatieres($userId);
            $mesMatiereIds = array_column($mesMatieres, 'matiere_id');
        } catch (\Throwable $e) {
            error_log('[GLOBALO] profil: getMatieres failed userId=' . $userId . ' err=' . $e->getMessage());
            $mesMatieres = [];
            $mesMatiereIds = [];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::validateCsrf()) {
                $errors[] = 'Token de sécurité invalide.';
            } else {
                $niveauxAllowed = ['debutant', 'intermediaire', 'avance', 'expert'];
                $pays_ao = ['Mali','Côte d\'Ivoire','Sénégal','Bénin','Niger','Autre'];

                $profilData = [
                    'universite'        => Security::sanitizeString($_POST['universite'] ?? '', 200),
                    'pays'              => in_array($_POST['pays'] ?? '', $pays_ao, true) ? $_POST['pays'] : 'Autre',
                    'ville'             => Security::sanitizeString($_POST['ville'] ?? '', 100),
                    'filiere'           => Security::sanitizeString($_POST['filiere'] ?? '', 150),
                    'niveau_etude'      => in_array($_POST['niveau_etude'] ?? '', ['Licence 1','Licence 2','Licence 3','Master 1','Master 2','Doctorat','BTS','DUT','Autre'], true)
                                          ? $_POST['niveau_etude'] : 'Licence 1',
                    'bio'               => Security::sanitizeString($_POST['bio'] ?? '', 1000),
                ];

                try {
                    $this->etudiantModel->updateProfil($userId, $profilData);
                } catch (\Throwable $e) {
                    error_log('[GLOBALO] profil: updateProfil failed userId=' . $userId . ' err=' . $e->getMessage());
                    $errors[] = 'Erreur lors de la mise à jour du profil. Réessayez.';
                    goto renderProfil;
                }

                if ($role === 'professeur' && !empty($profilProfesseur['id'])) {
                    $tarifRaw = str_replace(',', '.', (string) ($_POST['tarif_horaire'] ?? '0'));
                    $tarif    = is_numeric($tarifRaw) ? (float) $tarifRaw : 0.0;
                    $tarif    = max(0.0, min(999999.0, $tarif));
                    try {
                        $profilProfesseurModel = new ProfilProfesseurModel();
                        $profUpdates = ['tarif_horaire' => $tarif];
                        if (!empty($profilProfesseur['valide_par_admin'])) {
                            $profUpdates['disponible'] = isset($_POST['disponible']) ? 1 : 0;
                        }
                        $profilProfesseurModel->updateProfil((int) $profilProfesseur['id'], $profUpdates);
                        $profilProfesseur['tarif_horaire'] = $tarif;
                        if (isset($profUpdates['disponible'])) {
                            $profilProfesseur['disponible'] = $profUpdates['disponible'];
                        }
                    } catch (\Throwable $e) {
                        error_log('[GLOBALO] profil: updateTarifHoraire failed userId=' . $userId . ' err=' . $e->getMessage());
                    }
                }

                // Mise à jour des matières maîtrisées
                $matiereIds = array_map('intval', $_POST['matieres'] ?? []);
                $niveaux    = [];
                $notes      = [];
                foreach ($matiereIds as $mid) {
                    $nk = 'niveau_' . $mid;
                    $nn = 'note_' . $mid;
                    $niveaux[$mid] = in_array($_POST[$nk] ?? '', $niveauxAllowed, true) ? $_POST[$nk] : 'intermediaire';
                    $notes[$mid]   = isset($_POST[$nn]) && is_numeric($_POST[$nn]) ? (float) $_POST[$nn] : null;
                }

                $profilId = $profil['id'] ?? 0;
                if ($profilId > 0) {
                    try {
                        $this->etudiantModel->setMatieres($profilId, $matiereIds, $niveaux, $notes);
                    } catch (\Throwable $e) {
                        error_log('[GLOBALO] profil: setMatieres failed userId=' . $userId . ' err=' . $e->getMessage());
                        $errors[] = 'Erreur lors de la mise à jour des matières. Réessayez.';
                        goto renderProfil;
                    }
                }

                // Synchroniser professeur_matieres (filtre des exercices disponibles)
                // Sans cette sync, les exercices étudiants restent invisibles pour le professeur
                if ($role === 'professeur' && !empty($profilProfesseur['id'])) {
                    try {
                        $profProfModelSync = new ProfilProfesseurModel();
                        $profProfModelSync->replaceMatieres((int) $profilProfesseur['id'], $matiereIds);
                    } catch (\Throwable $e) {
                        error_log('[GLOBALO] profil: replaceMatieres professeur failed userId=' . $userId . ' err=' . $e->getMessage());
                    }
                }

                $_SESSION['flash_success'] = 'Profil mis à jour avec succès.';
                $this->redirect($this->spaceUrl('/profil'));
                return;
            }
        }

        renderProfil:
        $this->etudiantRender('profil', [
            'pageTitle'          => $role === 'professeur' ? 'Mon profil professeur' : 'Mon profil étudiant',
            'navActive'          => 'profil',
            'user'               => ['id' => $userId, 'role' => $role],
            'profil'             => $profil,
            'profil_professeur'  => $profilProfesseur,
            'matieres'           => $matieres,
            'mes_matieres'       => $mesMatieres,
            'mes_matiere_ids'    => $mesMatiereIds,
            'errors'             => $errors,
        ]);
    }

    /**
     * Portefeuille étudiant/professeur — recharge via Wave et historique.
     * Partage la même vue mobile que le portefeuille client.
     */
    public function portefeuille(): void
    {
        $userId = (int) Auth::id();
        $role   = (string) (Auth::role() ?? '');

        $defaultWaveNumero = '+223 94 03 54 56';
        $solde             = 0.0;
        $transactions     = [];
        $waveDepotsPending = [];
        $waveNumero       = $defaultWaveNumero;

        // Sécuriser toute la page : si une requête échoue (DB, table manquante, etc),
        // on affiche quand même le portefeuille avec des valeurs par défaut.
        try {
            $portefeuilleModel = new PortefeuilleModel();
            $p = $portefeuilleModel->getOrCreateForUser($userId);
            $solde = (float) ($p['solde'] ?? 0);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] portefeuille: getSolde failed userId=' . $userId . ' err=' . $e->getMessage());
        }

        try {
            $db   = \App\Core\Database::getInstance();
            $stmt = $db->prepare(
                "SELECT payment_id, amount, total_amount, status, transaction_code, created_at,
                        COALESCE(provider, '') AS provider, type
                 FROM transactions
                 WHERE user_id = ? AND type IN ('depot_portefeuille', 'paiement_session_touchpay', 'paiement_session_paytech')
                 ORDER BY created_at DESC LIMIT 5"
            );
            $stmt->execute([$userId]);
            $waveDepotsPending = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // La table `transactions` est optionnelle (ou peut ne pas exister selon la DB).
            error_log('[GLOBALO] portefeuille: waveDepots query failed userId=' . $userId . ' err=' . $e->getMessage());
        }

        try {
            $waveNumero = (new ParametreModel())->get('wave_numero_marchand', $defaultWaveNumero);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] portefeuille: wave_numero fetch failed userId=' . $userId . ' err=' . $e->getMessage());
            $waveNumero = $defaultWaveNumero;
        }

        try {
            $transactions = (new PaiementModel())->getByClient($userId, 20);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] portefeuille: PaiementModel getByClient failed userId=' . $userId . ' err=' . $e->getMessage());
            $transactions = [];
        }

        $paytechConfigured = false;
        try {
            $paytechConfigured = (new PayTechPaymentService())->isConfigured();
        } catch (\Throwable $e) {
            error_log('[GLOBALO] portefeuille: PayTech init failed userId=' . $userId . ' err=' . $e->getMessage());
        }

        $intouchSvc = new IntouchPaymentService();
        $touchpayOk = false;
        $intouchApiOk = false;
        try {
            $touchpayOk = $intouchSvc->isTouchpayWidgetConfigured();
            $intouchApiOk = $intouchSvc->isConfigured();
        } catch (\Throwable $e) {
            error_log('[GLOBALO] portefeuille: Intouch init failed userId=' . $userId . ' err=' . $e->getMessage());
        }

        $this->etudiantRender('portefeuille', [
            'pageTitle'    => 'Mon portefeuille',
            'navActive'    => 'portefeuille',
            'user'         => ['id' => $userId, 'role' => $role],
            'solde'        => $solde,
            'transactions' => $transactions,
            'wave_depots'     => $waveDepotsPending,
            'wave_numero'     => $waveNumero,
            'paytech_configured' => $paytechConfigured,
            'touchpay_configured' => $touchpayOk,
            'intouch_api_configured' => $intouchApiOk,
        ]);
    }

    /** Catalogue des matières universitaires. */
    public function matieres(): void
    {
        $userId   = (int) Auth::id();
        $role     = (string) (Auth::role() ?? '');
        $grouped  = $this->matiereModel->getActivesGrouped();
        $mesMatieres = $this->etudiantModel->getMatieres($userId);
        $mesMatiereIds = array_column($mesMatieres, 'matiere_id');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::validateCsrf()) {
            $newIds = array_values(array_filter(array_map('intval', $_POST['matieres'] ?? []), fn($v) => $v > 0));
            $profil = $this->etudiantModel->getByUserId($userId);
            if ($profil) {
                try {
                    $this->etudiantModel->setMatieres((int) $profil['id'], $newIds);
                } catch (\Throwable $e) {
                    error_log('[GLOBALO] matieres: setMatieres failed userId=' . $userId . ' err=' . $e->getMessage());
                }
            }
            // Sync professeur_matieres pour que les exercices soient visibles
            if ($role === 'professeur') {
                try {
                    $profilProf = (new ProfilProfesseurModel())->getByUtilisateurId($userId);
                    if ($profilProf) {
                        (new ProfilProfesseurModel())->replaceMatieres((int) $profilProf['id'], $newIds);
                    }
                } catch (\Throwable $e) {
                    error_log('[GLOBALO] matieres: replaceMatieres professeur failed userId=' . $userId . ' err=' . $e->getMessage());
                }
            }
            $this->redirect($this->spaceUrl('/matieres'));
            return;
        }

        $this->etudiantRender('matieres', [
            'pageTitle'       => 'Matières universitaires',
            'navActive'       => 'matieres',
            'user'            => ['id' => $userId, 'role' => $role],
            'matieres'        => $grouped,
            'mes_matiere_ids' => $mesMatiereIds,
        ]);
    }

    /** Mon compte (avatar). */
    public function compte(): void
    {
        $baseUrl = rtrim(BASE_URL ?? '', '/');
        $userId = (int) Auth::id();
        $userModel = new UtilisateurModel();

        $userToEdit = [];
        try {
            $userToEdit = $userModel->find($userId) ?: [];
        } catch (\Throwable $e) {
            error_log('[GLOBALO] compte: find user failed userId=' . $userId . ' err=' . $e->getMessage());
            $userToEdit = [];
        }

        if (empty($userToEdit)) {
            // On évite le 500 : on affiche quand même la page avec un contenu vide.
            $userToEdit = [
                'id' => $userId,
                'prenom' => '',
                'nom' => '',
                'email' => '',
                'avatar' => null,
            ];
        }
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data    = ['updated_at' => date('Y-m-d H:i:s')];
            $maxSize = defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (5 * 1024 * 1024);
            $avatarErr = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
            if (!empty($_FILES['avatar']['name']) && $avatarErr === UPLOAD_ERR_OK) {
                $finfo  = finfo_open(FILEINFO_MIME_TYPE);
                $mime   = $finfo ? finfo_file($finfo, $_FILES['avatar']['tmp_name']) : '';
                if ($finfo) finfo_close($finfo);
                $size   = (int) ($_FILES['avatar']['size'] ?? 0);
                $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
                if ($size > 0 && $size <= $maxSize && in_array($mime, $allowed, true)) {
                    $ext = in_array($mime, ['image/jpeg', 'image/jpg'], true) ? 'jpg' : ($mime === 'image/webp' ? 'webp' : 'png');
                    $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads');
                    $userDir  = $uploadPath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $userId;
                    if (!is_dir($userDir)) @mkdir($userDir, 0755, true);
                    $dest = $userDir . DIRECTORY_SEPARATOR . 'avatar.' . $ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                        $data['avatar'] = 'users/' . $userId . '/avatar.' . $ext;
                    }
                } else {
                    $errors[] = 'Photo : format non autorisé (PNG, JPG, WebP) ou trop volumineux (max 5 Mo).';
                }
            }
            if (empty($errors)) {
                try {
                    $userModel->update($userId, $data);
                } catch (\Throwable $e) {
                    error_log('[GLOBALO] compte: update user failed userId=' . $userId . ' err=' . $e->getMessage());
                    $errors[] = 'Erreur lors de la mise à jour du compte. Réessayez.';
                }
                $_SESSION['flash_success'] = 'Compte mis à jour.';
                $this->redirect($this->spaceUrl('/compte'));
                return;
            }
        }

        $this->etudiantRender('compte', [
            'pageTitle'  => 'Mon compte - Espace ' . (Auth::role() === 'professeur' ? 'Professeur' : 'Étudiant'),
            'navActive'  => 'compte',
            'user'       => ['id' => $userId, 'role' => Auth::role()],
            'userToEdit' => $userToEdit,
            'errors'     => $errors,
        ]);
    }

    /**
     * Choix de l’opérateur Mobile Money avant le formulaire de retrait (professeurs).
     */
    public function retraitChoix(): void
    {
        if (Auth::role() !== 'professeur') {
            $this->redirect($this->spaceUrl('/portefeuille'));
            return;
        }
        $userId = (int) Auth::id();
        $solde  = 0.0;
        try {
            $solde = (float) (new PortefeuilleModel())->getSolde($userId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] retraitChoix: getSolde failed userId=' . $userId . ' err=' . $e->getMessage());
        }
        $this->etudiantRender('retrait_choix', [
            'pageTitle'   => 'Opérateur de retrait - GLOBALO',
            'navActive'   => 'retraitChoix',
            'user'        => ['id' => $userId, 'role' => 'professeur'],
            'solde'       => $solde,
            'prof_base_path' => $this->basePath(),
        ]);
    }

    /**
     * Demande de retrait Wave pour les professeurs.
     * Les étudiants voient la page mais le formulaire est désactivé
     * (seuls les professeurs ont un solde à retirer via cet espace).
     */
    public function retrait(): void
    {
        $userId = (int) Auth::id();
        $role   = (string) (Auth::role() ?? '');
        $base   = rtrim(BASE_URL ?? '', '/');
        $bp     = $this->basePath();
        $choixUrl = $base . $bp . '/retrait-choix';

        $solde            = 0.0;
        $demandes         = [];
        $errors           = [];
        $portefeuilleModel = null;
        $retraitModel      = null;

        // Charger la page sans planter si la DB ou certaines tables sont indisponibles.
        try {
            $portefeuilleModel = new PortefeuilleModel();
            $solde = (float) $portefeuilleModel->getSolde($userId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] retrait: getSolde failed userId=' . $userId . ' err=' . $e->getMessage());
        }

        try {
            $retraitModel = new RetraitProfModel();
            $demandes    = $retraitModel->getByUtilisateur($userId);
        } catch (\Throwable $e) {
            error_log('[GLOBALO] retrait: getByUtilisateur failed userId=' . $userId . ' err=' . $e->getMessage());
        }

        $operateur = strtoupper(trim((string) ($_POST['operateur'] ?? $_GET['operateur'] ?? '')));
        if ($role === 'professeur') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                if (!self::retraitOperateurValide($operateur)) {
                    $this->redirect($choixUrl);
                    return;
                }
            }
        } else {
            $operateur = '';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($role !== 'professeur') {
                $errors[] = 'Seuls les professeurs peuvent effectuer un retrait depuis cet espace.';
            } else {
                $devise      = 'XOF';
                try {
                    $devise = (new ParametreModel())->get('devise_plateforme', 'XOF');
                } catch (\Throwable $e) {
                    $devise = 'XOF';
                }
                $montant     = (float) ($_POST['montant'] ?? 0);
                $numeroWave  = Security::sanitizeString($_POST['numero_wave'] ?? '', 34);

                if (!self::retraitOperateurValide($operateur)) {
                    $errors[] = 'Opérateur Mobile Money invalide. Retournez à l’étape de choix d’opérateur.';
                } elseif ($montant < 500) {
                    $errors[] = 'Montant minimum 500 ' . $devise . '.';
                } elseif ($montant > $solde) {
                    $errors[] = 'Solde insuffisant.';
                } elseif (strlen($numeroWave) < 8) {
                    $errors[] = 'Numéro Mobile Money invalide (minimum 8 caractères avec indicatif pays).';
                } elseif (!$portefeuilleModel || !$retraitModel) {
                    $errors[] = 'Données indisponibles pour traiter le retrait. Réessayez.';
                } else {
                    $pdo = \App\Core\Database::getInstance();
                    try {
                        $pdo->beginTransaction();

                        $debitOk = $portefeuilleModel->debiter($userId, $montant);
                        if (!$debitOk) {
                            throw new \RuntimeException('Solde insuffisant.');
                        }

                        $numeroStocke = self::combineRetraitNumeroOperateur($operateur, $numeroWave);
                        $retraitModel->create($userId, $montant, $numeroStocke);

                        $pdo->commit();

                        $_SESSION['flash_success'] = 'Demande de retrait enregistrée. L\'administrateur la traitera sous 24–48h.';

                        $this->redirect($base . $bp . '/retrait?operateur=' . rawurlencode($operateur));
                        return;
                    } catch (\Throwable $ex) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        error_log('[EtudiantController::retrait] ' . $ex->getMessage());
                        $errors[] = 'Erreur lors du traitement du retrait. Veuillez réessayer.';
                    }
                }
            }
        }

        if ($portefeuilleModel) {
            try {
                $solde = (float) $portefeuilleModel->getSolde($userId);
            } catch (\Throwable $e) {
                error_log('[GLOBALO] retrait: recompute solde failed userId=' . $userId . ' err=' . $e->getMessage());
            }
        }
        if ($retraitModel) {
            try {
                $demandes = $retraitModel->getByUtilisateur($userId);
            } catch (\Throwable $e) {
                error_log('[GLOBALO] retrait: recompute demandes failed userId=' . $userId . ' err=' . $e->getMessage());
            }
        }

        $this->etudiantRender('retrait', [
            'pageTitle'      => 'Retrait Mobile Money - Espace Professeur',
            'navActive'      => 'retrait',
            'user'           => ['id' => $userId, 'role' => $role],
            'solde'          => $solde,
            'demandes'       => $demandes,
            'errors'         => $errors,
            'operateur'      => $operateur,
            'prof_base_path' => $bp,
        ]);
    }

    private static function retraitOperateurValide(string $operateur): bool
    {
        return in_array(strtoupper($operateur), ['ORANGE', 'MOOV', 'WAVE'], true);
    }

    private static function combineRetraitNumeroOperateur(string $operateur, string $numero): string
    {
        $numero = trim($numero);
        $op     = strtoupper($operateur);
        $combined = $op . '|' . $numero;
        return strlen($combined) <= 34 ? $combined : $numero;
    }
}
