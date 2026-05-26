<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Router;
use App\Core\Auth;
use App\Models\PieceJointeModel;
use App\Models\MessageModel;
use App\Models\ReservationModel;
use App\Models\LivraisonModel;

/**
 * Téléchargement de pièces jointes (vérification accès réservation).
 */
class FichierController extends Controller
{
    public function piece(): void
    {
        Auth::requireAuth();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id <= 0) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $pjModel = new PieceJointeModel();
        $pj = $pjModel->find($id);
        if (!$pj) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $msg = (new MessageModel())->find((int) $pj['message_id']);
        if (!$msg) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $reservation = (new ReservationModel())->find((int) $msg['reservation_id']);
        if (!$reservation) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $userId = Auth::id();
        $isClient = (int) $reservation['client_id'] === $userId;
        $profil = (new \App\Models\ProfilExpertModel())->getByIdPublic((int) $reservation['expert_id']);
        $isExpert = $profil && (int) $profil['utilisateur_id'] === $userId;
        if (!$isClient && !$isExpert) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
        $basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pj['chemin']);
        if (!is_file($fullPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $nom = $pj['nom_fichier'];
        $mime = $pj['type_mime'] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . str_replace('"', '\\"', $nom) . '"');
        header('Content-Length: ' . (string) (int) $pj['taille']);
        readfile($fullPath);
        exit;
    }

    /**
     * Téléchargement sécurisé d'un fichier livré par un expert.
     * URL : /fichier/livraison/{id}
     * Accès : client de la réservation OU expert qui a livré.
     */
    public function livraison(): void
    {
        Auth::requireAuth();
        $params = $this->router->getParams();
        $id = (int) ($params[0] ?? 0);
        if ($id <= 0) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $livModel = new LivraisonModel();
        $liv = $livModel->findWithDetails($id);
        if (!$liv || $liv['type'] !== 'fichier' || empty($liv['chemin'])) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $userId   = Auth::id();
        $isClient = (int) $liv['client_id'] === $userId;
        $isExpert = (int) $liv['expert_user_id'] === $userId;
        $isAdmin  = Auth::role() === 'admin';

        if (!$isClient && !$isExpert && !$isAdmin) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
        $fullPath = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $liv['chemin']);

        if (!is_file($fullPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $nom  = $liv['nom_fichier'] ?? basename($fullPath);
        $mime = $liv['type_mime']   ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . str_replace('"', '\\"', $nom) . '"');
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: private, no-cache');
        readfile($fullPath);
        exit;
    }

    /**
     * Logo personnalisé de la plateforme (uploadé en admin/parametres).
     * URL publique : /fichier/logo — pas d'auth.
     */
    public function logo(): void
    {
        $basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
        $logoPath = $basePath . DIRECTORY_SEPARATOR . 'logo.png';
        if (!is_file($logoPath)) {
            $logoPath = $basePath . DIRECTORY_SEPARATOR . 'logo.jpg';
        }
        if (!is_file($logoPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $mime = 'image/jpeg';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        } else {
            $mime = 'image/png';
        }
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        header('Content-Length: ' . (string) filesize($logoPath));
        readfile($logoPath);
        exit;
    }

    /**
     * Photo de profil utilisateur (avatar). Accès : admin ou l'utilisateur lui-même.
     * URL : /fichier/user-avatar/22 — utilisable depuis le web et l'app (même BASE_URL).
     */
    public function userAvatar(): void
    {
        Auth::requireAuth();
        $params = $this->router->getParams();
        $userId = (int) ($params[0] ?? 0);
        if ($userId <= 0) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $currentId = Auth::id();
        $isAdmin = Auth::role() === 'admin';
        if (!$isAdmin && $currentId !== $userId) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
        $user = (new \App\Models\UtilisateurModel())->find($userId);
        if (!$user) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
        $rawAvatar = $user['avatar'] ?? null;

        // Support des anciennes valeurs en base :
        // - parfois stocké avec préfixe "uploads/..."
        // - parfois stocké avec un slash initial "/..."
        // - parfois stocké seulement par "avatar.ext"
        $relative = $rawAvatar ? ltrim(str_replace(['\\'], '/', (string) $rawAvatar), '/') : '';
        if ($relative !== '' && (str_starts_with($relative, 'uploads/') || str_starts_with($relative, 'uploads\\'))) {
            $relative = substr($relative, strlen('uploads/'));
        }

        $fullPath = $relative !== ''
            ? $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative)
            : '';

        // Fallback : vérifier dans le dossier attendu users/{id}/
        if (!$fullPath || !is_file($fullPath)) {
            $userDir = $basePath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $userId;
            foreach (['avatar.png', 'avatar.jpg', 'avatar.jpeg', 'avatar.gif', 'avatar.webp'] as $candidate) {
                $candidatePath = $userDir . DIRECTORY_SEPARATOR . $candidate;
                if (is_file($candidatePath)) {
                    $fullPath = $candidatePath;
                    break;
                }
            }
        }

        if (!$fullPath || !is_file($fullPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if ($ext === 'jpg' || $ext === 'jpeg') {
            $mime = 'image/jpeg';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        } else {
            $mime = 'image/png';
        }
        header('Content-Type: ' . $mime);
        header('Cache-Control: private, max-age=3600');
        header('Content-Length: ' . (string) filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    /**
     * Pièce d'identité utilisateur. Accès : admin ou l'utilisateur lui-même.
     * URL : /fichier/user-piece/22 — utilisable depuis le web et l'app (même BASE_URL).
     */
    public function userPiece(): void
    {
        Auth::requireAuth();
        $params = $this->router->getParams();
        $userId = (int) ($params[0] ?? 0);
        if ($userId <= 0) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $currentId = Auth::id();
        $isAdmin = Auth::role() === 'admin';
        if (!$isAdmin && $currentId !== $userId) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
        $user = (new \App\Models\UtilisateurModel())->find($userId);
        if (!$user) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');

        $rawPiece = $user['piece_identite'] ?? null;
        $relative = $rawPiece ? ltrim(str_replace(['\\'], '/', (string) $rawPiece), '/') : '';
        if ($relative !== '' && (str_starts_with($relative, 'uploads/') || str_starts_with($relative, 'uploads\\'))) {
            $relative = substr($relative, strlen('uploads/'));
        }

        $fullPath = $relative !== ''
            ? $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative)
            : '';

        // Fallback : vérifier dans le dossier attendu users/{id}/
        if (!$fullPath || !is_file($fullPath)) {
            $userDir = $basePath . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $userId;
            foreach (['piece_identite.pdf', 'piece_identite.png', 'piece_identite.jpg', 'piece_identite.jpeg', 'piece_identite.gif', 'piece_identite.webp'] as $candidate) {
                $candidatePath = $userDir . DIRECTORY_SEPARATOR . $candidate;
                if (is_file($candidatePath)) {
                    $fullPath = $candidatePath;
                    break;
                }
            }
        }

        if (!$fullPath || !is_file($fullPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $mime = 'application/pdf';
        } elseif ($ext === 'jpg' || $ext === 'jpeg') {
            $mime = 'image/jpeg';
        } elseif ($ext === 'gif') {
            $mime = 'image/gif';
        } elseif ($ext === 'webp') {
            $mime = 'image/webp';
        } else {
            $mime = 'image/png';
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="piece_identite.' . $ext . '"');
        header('Cache-Control: private, max-age=3600');
        header('Content-Length: ' . (string) filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    /**
     * Sert les fichiers uploadés publiquement (avatars, photos de profil, etc.).
     * URL : /uploads/{chemin_relatif}  →  ce contrôleur via le routeur.
     *
     * Active uniquement quand le dossier public/uploads n'est pas résolu directement
     * par Apache (pas de symlink/jonction sur l'environnement courant).
     * Autorise uniquement les extensions image ; bloque tout path traversal.
     */
    public function serveUpload(): void
    {
        $params = $this->router->getParams();

        // Reconstruire le chemin relatif depuis les segments de l'URL
        // Ex. params = ['users', '12', 'avatar.jpg'] → 'users/12/avatar.jpg'
        $relative = implode('/', array_map(
            static fn(string $s): string => str_replace(['..', '\\', "\0"], '', $s),
            array_values($params)
        ));
        $relative = ltrim($relative, '/');

        // Sécurité : refuser les chemins vides, absolus ou avec traversal
        if ($relative === '' || strpos($relative, '..') !== false || $relative[0] === '/') {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }

        // Sécurité : seules les extensions image sont autorisées
        $ext = strtolower(pathinfo($relative, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($ext, $allowedExts, true)) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }

        $basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
        $fullPath = $basePath . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $relative);

        if (!is_file($fullPath)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($fullPath));
        header('Cache-Control: public, max-age=15552000'); // 6 mois
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', (int) filemtime($fullPath)) . ' GMT');
        readfile($fullPath);
        exit;
    }
}
