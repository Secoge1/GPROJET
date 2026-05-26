<?php
$baseUrl = rtrim(BASE_URL ?? '', '/');
$row = $row ?? [];
$e = fn($s) => \App\Core\Security::escape($s ?? '');
$payload = json_decode((string)($row['payload'] ?? ''), true);
$subject = (string)($row['subject'] ?? ($payload['subject'] ?? ''));
$message = (string)($payload['message'] ?? '');
?>
<div class="page-admin page-admin-assistant-email-view">
    <header class="admin-tracking-hero">
        <a href="<?= $baseUrl ?>/admin/assistant-emails" class="admin-back-link" aria-label="Retour à la liste">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            Emails IA auto
        </a>
        <div class="admin-tracking-hero-content">
            <div class="admin-tracking-hero-icon" aria-hidden="true">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-6l-2 3-4-6-3 3H2"/><path d="M2 6h20v12H2z"/></svg>
            </div>
            <div class="admin-tracking-hero-text">
                <h1>Détail email IA #<?= (int)($row['id'] ?? 0) ?></h1>
                <p class="admin-tracking-hero-subtitle">Visualiser le contenu exact envoyé.</p>
            </div>
        </div>
    </header>

    <div class="admin-table-card admin-tracking-table-card">
        <div class="admin-table-card-header">
            <h2>Informations</h2>
        </div>
        <div style="padding:1rem 1.25rem;display:grid;grid-template-columns:repeat(2,minmax(220px,1fr));gap:.6rem 1rem;">
            <div><strong>Date :</strong> <?= $e($row['sent_at'] ?? '') ?></div>
            <div><strong>Statut :</strong> <?= $e($row['status'] ?? '') ?></div>
            <div><strong>Destinataire :</strong> <?= $e($row['recipient_name'] ?? '') ?></div>
            <div><strong>Email :</strong> <?= $e($row['recipient_email'] ?? '') ?></div>
            <div><strong>Raison :</strong> <?= $e($row['reason_code'] ?? '') ?></div>
            <div><strong>Sujet :</strong> <?= $e($subject) ?></div>
        </div>
    </div>

    <div class="admin-table-card admin-tracking-table-card">
        <div class="admin-table-card-header">
            <h2>Corps du mail</h2>
        </div>
        <div style="padding:1rem 1.25rem;">
            <pre style="white-space:pre-wrap;word-break:break-word;margin:0;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;"><?= $e($message) ?></pre>
        </div>
    </div>
</div>

