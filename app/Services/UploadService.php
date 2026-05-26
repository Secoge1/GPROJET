<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GLOBALO - Upload de fichiers (pièces jointes messagerie, etc.)
 */
class UploadService
{
    private string $basePath;
    /** @var array<string> */
    private array $allowedExt;
    private int $maxSize;

    public function __construct()
    {
        $this->basePath = defined('UPLOAD_PATH') ? UPLOAD_PATH : (ROOT_PATH . '/uploads');
        $this->allowedExt = defined('ALLOWED_UPLOAD_EXT') ? ALLOWED_UPLOAD_EXT : ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip'];
        $this->maxSize = defined('MAX_UPLOAD_SIZE') ? MAX_UPLOAD_SIZE : (10 * 1024 * 1024);
    }

    /**
     * Enregistre un fichier uploadé dans un sous-dossier (ex: messages).
     * @return array{path: string, name: string, size: int, mime: string|null}|null En cas d'erreur retourne null.
     */
    public function store(string $subdir, array $file): ?array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) > $this->maxSize) {
            return null;
        }
        $name = $file['name'] ?? '';
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $this->allowedExt, true)) {
            return null;
        }
        $dir = $this->basePath . DIRECTORY_SEPARATOR . trim($subdir, '/\\');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($name, PATHINFO_FILENAME));
        $uniqueName = $safeName . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $dir . DIRECTORY_SEPARATOR . $uniqueName;
        if (!move_uploaded_file($file['tmp_name'] ?? '', $path)) {
            return null;
        }
        $relativePath = $subdir . '/' . $uniqueName;
        $mime = $file['type'] ?? null;
        if ($mime === '' || $mime === null) {
            $mime = null;
        }
        return [
            'path' => $relativePath,
            'name' => $name,
            'size' => (int) $file['size'],
            'mime' => $mime,
        ];
    }

    /**
     * Retourne l'URL publique (BASE_URL/uploads/…). Les fichiers sont sous UPLOAD_PATH (globalo/uploads/).
     */
    public function getPublicUrl(string $relativePath): string
    {
        $base = rtrim(BASE_URL, '/');
        return $base . '/uploads/' . ltrim(str_replace('\\', '/', $relativePath), '/');
    }

    public function getFullPath(string $relativePath): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    }
}
