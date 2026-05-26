<?php
declare(strict_types=1);

namespace App\Services;

/**
 * GLOBALO - Envoi d'emails (vérification, notifications, admin).
 * Utilise SMTP si configuré, sinon mail() PHP en fallback.
 */
class MailerService
{
    private ?string $smtpHost  = null;
    private ?int    $smtpPort  = null;
    private ?string $smtpUser  = null;
    private ?string $smtpPass  = null;
    private bool    $smtpSecure = false;
    private string  $fromEmail  = 'noreply@globalo.fr';
    private string  $fromName   = 'GLOBALO';
    private string  $lastError  = '';

    public function __construct()
    {
        $this->smtpHost   = getenv('SMTP_HOST') ?: ($this->getParametre('smtp_host') ?: null);
        $smtpPortEnv      = getenv('SMTP_PORT');
        $this->smtpPort   = $smtpPortEnv ? (int) $smtpPortEnv : (int) ($this->getParametre('smtp_port') ?: 587);
        $this->smtpUser   = getenv('SMTP_USER') ?: ($this->getParametre('smtp_user') ?: null);
        $this->smtpPass   = getenv('SMTP_PASS') ?: ($this->getParametre('smtp_pass') ?: null);
        $smtpSecureRaw    = getenv('SMTP_SECURE') ?: ($this->getParametre('smtp_secure') ?: '');
        $this->smtpSecure = ($smtpSecureRaw === 'tls' || $smtpSecureRaw === '1');
        // Priorité : MAIL_FROM (.env) → mail_from (admin SMTP) → email contact plateforme
        $mailFromEnv = getenv('MAIL_FROM');
        if ($mailFromEnv !== false && $mailFromEnv !== '') {
            $this->fromEmail = $mailFromEnv;
        } else {
            $this->fromEmail = $this->getParametre('mail_from')
                ?: $this->getParametre('plateforme_email')
                ?: 'noreply@globalo.fr';
        }
        $this->fromName   = $this->getParametre('plateforme_nom') ?? 'GLOBALO';
    }

    private function getParametre(string $key): ?string
    {
        if (!class_exists(\App\Models\ParametreModel::class)) {
            return null;
        }
        $v = (new \App\Models\ParametreModel())->get($key, null);
        return $v !== null && $v !== '' ? (string) $v : null;
    }

    public function isSmtpConfigured(): bool
    {
        return $this->smtpHost !== null && $this->smtpHost !== '';
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getSmtpDebugInfo(): string
    {
        if (!$this->isSmtpConfigured()) {
            return 'SMTP non configuré — utilisation de mail() PHP (souvent bloqué en hébergement mutualisé). '
                 . 'Allez dans Admin → Paramètres → Configuration SMTP.';
        }
        return sprintf('SMTP configuré : %s:%d (TLS:%s, user:%s)',
            $this->smtpHost,
            $this->smtpPort ?? 587,
            $this->smtpSecure ? 'oui' : 'non',
            $this->smtpUser ? mb_substr($this->smtpUser, 0, 3) . '***' : '(vide)'
        );
    }

    // ──────────────────────────────────────────────────────────────
    // MÉTHODES PUBLIQUES
    // ──────────────────────────────────────────────────────────────

    /** Email de vérification d'inscription. */
    public function sendVerificationEmail(string $toEmail, string $verificationLink): bool
    {
        $subject = 'Vérifiez votre adresse email - ' . $this->fromName;
        $html    = $this->wrapHtml(
            'Activez votre compte ' . $this->fromName,
            '<p>Bonjour,</p>
             <p>Merci de vous être inscrit sur <strong>' . htmlspecialchars($this->fromName, ENT_QUOTES) . '</strong>.</p>
             <p>Pour activer votre compte, cliquez sur le bouton ci-dessous :</p>',
            $verificationLink,
            'Activer mon compte',
            '<p style="margin:16px 0 0;font-size:12px;line-height:1.5;color:#64748b;">Ce lien expire sous 48 heures. Si vous n\'êtes pas à l\'origine de cette inscription, ignorez ce message.</p>'
        );
        return $this->sendHtml($toEmail, $subject, $html);
    }

    /**
     * Email de bienvenue personnalisé selon le rôle de l'utilisateur.
     * Envoyé automatiquement après l'inscription (classique ou Google OAuth).
     *
     * @param string $toEmail      Adresse email du destinataire
     * @param string $prenom       Prénom du nouvel utilisateur
     * @param string $nom          Nom du nouvel utilisateur
     * @param string $role         Rôle : client | expert | etudiant | professeur | admin
     * @param string $dashboardUrl URL du tableau de bord (bouton CTA)
     */
    public function sendWelcomeEmail(
        string $toEmail,
        string $prenom,
        string $nom,
        string $role,
        string $dashboardUrl = ''
    ): bool {
        $base       = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $safeName   = htmlspecialchars($this->fromName, ENT_QUOTES);
        $safePrenom = htmlspecialchars($prenom, ENT_QUOTES);
        $year       = date('Y');

        $signatureImageUrl  = $this->getParametre('mail_signature_image_url') ?? '';
        $signatureImageHtml = $signatureImageUrl !== ''
            ? '<tr><td align="center" style="padding:16px 0 0;">
                 <img src="' . htmlspecialchars($signatureImageUrl, ENT_QUOTES) . '" alt="Signature ' . $safeName . '"
                      style="display:block;width:100%;max-width:380px;height:auto;border:0;outline:0;margin:0 auto;">
               </td></tr>'
            : '';

        // ── Configuration par rôle ──────────────────────────────────────────
        $configs = [
            'client' => [
                'subject'  => 'Bienvenue sur GLOBALO, ' . $prenom . ' !',
                'badge'    => 'Client',
                'accent'   => '#16a34a',
                'tagline'  => 'Trouvez l\'expert qu\'il vous faut',
                'btn_text' => 'Publier ma demande',
                'btn_url'  => $dashboardUrl ?: $base . '/client',
                'body'     =>
                    '<p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Merci pour votre inscription sur la plateforme <strong style="color:#0f172a;">GLOBALO</strong>.</p>'
                    . '<p style="margin:0 0 20px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Vous recherchez un expert pour vous accompagner rapidement dans une mission ou un besoin sp&eacute;cifique&nbsp;?</p>'
                    . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">'
                    . '<tr><td style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 10px 10px 0;padding:16px 20px;">'
                    . '<p style="margin:0;font-size:14px;line-height:1.7;color:#166534;">'
                    . 'Nous vous invitons d&egrave;s maintenant &agrave; <strong>publier votre demande</strong> sur la plateforme '
                    . 'afin qu\'un expert qualifi&eacute; puisse vous contacter et vous aider dans les meilleurs d&eacute;lais.</p>'
                    . '</td></tr></table>',
            ],
            'expert' => [
                'subject'  => 'Bienvenue sur GLOBALO, ' . $prenom . ' !',
                'badge'    => 'Expert',
                'accent'   => '#16a34a',
                'tagline'  => 'Développez votre activité avec GLOBALO',
                'btn_text' => 'Compléter mon profil',
                'btn_url'  => $dashboardUrl ?: $base . '/expert',
                'body'     =>
                    '<p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Merci pour votre inscription sur la plateforme <strong style="color:#0f172a;">GLOBALO</strong>.</p>'
                    . '<p style="margin:0 0 20px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Votre expertise est pr&eacute;cieuse. Des clients attendent d&eacute;j&agrave; un professionnel comme vous '
                    . 'pour les accompagner dans leurs missions.</p>'
                    . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">'
                    . '<tr><td style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 10px 10px 0;padding:16px 20px;">'
                    . '<p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#166534;">Pour d&eacute;marrer :</p>'
                    . '<ul style="margin:0;padding:0 0 0 18px;font-size:14px;line-height:1.85;color:#166534;">'
                    . '<li>Compl&eacute;tez votre profil avec votre photo et vos comp&eacute;tences</li>'
                    . '<li>D&eacute;finissez vos tarifs et vos disponibilit&eacute;s</li>'
                    . '<li>R&eacute;pondez aux demandes des clients</li>'
                    . '</ul></td></tr></table>',
            ],
            'etudiant' => [
                'subject'  => 'Bienvenue sur GLOBALO, ' . $prenom . ' !',
                'badge'    => 'Étudiant',
                'accent'   => '#16a34a',
                'tagline'  => 'Progressez avec les meilleurs enseignants',
                'btn_text' => 'Trouver un professeur',
                'btn_url'  => $dashboardUrl ?: $base . '/etudiant',
                'body'     =>
                    '<p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Merci pour votre inscription sur la plateforme <strong style="color:#0f172a;">GLOBALO</strong>.</p>'
                    . '<p style="margin:0 0 20px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Des professeurs qualifi&eacute;s sont disponibles pour vous accompagner dans vos apprentissages '
                    . 'et vous aider &agrave; progresser.</p>'
                    . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">'
                    . '<tr><td style="background:#f0fdf4;border-left:4px solid #16a34a;border-radius:0 10px 10px 0;padding:16px 20px;">'
                    . '<p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#166534;">Comment commencer :</p>'
                    . '<ul style="margin:0;padding:0 0 0 18px;font-size:14px;line-height:1.85;color:#166534;">'
                    . '<li>Parcourez les profils de nos professeurs</li>'
                    . '<li>R&eacute;servez une session selon vos disponibilit&eacute;s</li>'
                    . '<li>Apprenez et progressez &agrave; votre rythme</li>'
                    . '</ul></td></tr></table>',
            ],
            'professeur' => [
                'subject'  => 'Bienvenue sur GLOBALO, ' . $prenom . ' !',
                'badge'    => 'Professeur',
                'accent'   => '#7c3aed',
                'tagline'  => 'Partagez votre savoir, inspirez les étudiants',
                'btn_text' => 'Configurer mon profil',
                'btn_url'  => $dashboardUrl ?: $base . '/professeur',
                'body'     =>
                    '<p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Merci pour votre inscription sur la plateforme <strong style="color:#0f172a;">GLOBALO</strong>.</p>'
                    . '<p style="margin:0 0 20px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Votre savoir a de la valeur. Des &eacute;tudiants sont pr&ecirc;ts &agrave; b&eacute;n&eacute;ficier '
                    . 'de vos enseignements et de votre expertise p&eacute;dagogique.</p>'
                    . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">'
                    . '<tr><td style="background:#faf5ff;border-left:4px solid #7c3aed;border-radius:0 10px 10px 0;padding:16px 20px;">'
                    . '<p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#5b21b6;">Pour d&eacute;marrer :</p>'
                    . '<ul style="margin:0;padding:0 0 0 18px;font-size:14px;line-height:1.85;color:#5b21b6;">'
                    . '<li>Configurez votre profil et vos mati&egrave;res enseign&eacute;es</li>'
                    . '<li>D&eacute;finissez vos cr&eacute;neaux de disponibilit&eacute;</li>'
                    . '<li>Commencez &agrave; recevoir des demandes de sessions</li>'
                    . '</ul></td></tr></table>',
            ],
            'admin' => [
                'subject'  => 'Bienvenue sur GLOBALO – Espace Administrateur',
                'badge'    => 'Administrateur',
                'accent'   => '#0f172a',
                'tagline'  => 'Gérez et supervisez la plateforme GLOBALO',
                'btn_text' => 'Accéder à l\'administration',
                'btn_url'  => $dashboardUrl ?: $base . '/admin',
                'body'     =>
                    '<p style="margin:0 0 18px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Bienvenue dans l\'espace administrateur de la plateforme <strong style="color:#0f172a;">GLOBALO</strong>.</p>'
                    . '<p style="margin:0 0 20px;font-size:15px;line-height:1.75;color:#334155;">'
                    . 'Vous disposez d\'un acc&egrave;s complet &agrave; la gestion de la plateforme&nbsp;: '
                    . 'utilisateurs, contenus, param&egrave;tres et statistiques.</p>'
                    . '<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">'
                    . '<tr><td style="background:#f8fafc;border-left:4px solid #0f172a;border-radius:0 10px 10px 0;padding:16px 20px;">'
                    . '<p style="margin:0 0 10px;font-size:14px;font-weight:700;color:#0f172a;">Vos responsabilit&eacute;s :</p>'
                    . '<ul style="margin:0;padding:0 0 0 18px;font-size:14px;line-height:1.85;color:#475569;">'
                    . '<li>Supervision des utilisateurs et des comptes</li>'
                    . '<li>Validation des profils et des contenus</li>'
                    . '<li>Gestion des param&egrave;tres de la plateforme</li>'
                    . '</ul></td></tr></table>',
            ],
        ];

        $cfg     = $configs[$role] ?? $configs['client'];
        $accent  = $cfg['accent'];
        $badge   = htmlspecialchars($cfg['badge'], ENT_QUOTES);
        $tagline = htmlspecialchars($cfg['tagline'], ENT_QUOTES);
        $bodyHtml = $cfg['body'];
        $btnUrl  = htmlspecialchars($cfg['btn_url'], ENT_QUOTES);
        $btnText = htmlspecialchars($cfg['btn_text'], ENT_QUOTES);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>{$cfg['subject']}</title>
  <!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation"
       style="background:#f1f5f9;padding:32px 16px;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <tr>
    <td align="center">

      <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
             style="max-width:600px;background:#ffffff;border-radius:16px;overflow:hidden;
                    box-shadow:0 6px 32px rgba(0,0,0,.10);mso-table-lspace:0pt;mso-table-rspace:0pt;">

        <!-- ═══ HEADER GRADIENT ═══ -->
        <tr>
          <td style="background:linear-gradient(135deg,#d4a574 0%,#a8c48a 50%,#16a34a 100%);
                     padding:40px 32px 34px;text-align:center;">
            <h1 style="margin:0 0 8px;font-size:32px;font-weight:900;color:#ffffff;
                       letter-spacing:0.06em;mso-line-height-rule:exactly;">{$safeName}</h1>
            <p style="margin:0;font-size:14px;color:rgba(255,255,255,.88);font-weight:500;
                      mso-line-height-rule:exactly;">{$tagline}</p>
          </td>
        </tr>

        <!-- ═══ BADGE RÔLE ═══ -->
        <tr>
          <td style="background:#f8fafc;padding:14px 32px;text-align:center;border-bottom:1px solid #e2e8f0;">
            <span style="display:inline-block;background:{$accent};color:#ffffff;
                         font-size:11px;font-weight:700;padding:5px 20px;
                         border-radius:20px;letter-spacing:0.08em;text-transform:uppercase;">{$badge}</span>
          </td>
        </tr>

        <!-- ═══ CORPS ═══ -->
        <tr>
          <td style="padding:36px 40px 32px;color:#334155;font-size:15px;
                     line-height:1.7;mso-line-height-rule:exactly;">

            <!-- Salutation -->
            <p style="margin:0 0 22px;font-size:22px;font-weight:700;color:#0f172a;
                      mso-line-height-rule:exactly;">Bonjour {$safePrenom},</p>

            <!-- Corps spécifique au rôle -->
            {$bodyHtml}

            <!-- Bouton CTA -->
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
              <tr>
                <td align="center" style="padding:28px 0 24px;">
                  <a href="{$btnUrl}" target="_blank"
                     style="display:inline-block;background:{$accent};color:#ffffff;
                            font-size:15px;font-weight:700;text-decoration:none;
                            padding:14px 38px;border-radius:10px;
                            mso-padding-alt:14px 38px;">{$btnText}</a>
                </td>
              </tr>
            </table>

            <!-- Séparateur léger -->
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
              <tr><td style="border-top:1px solid #e2e8f0;font-size:0;line-height:0;padding:0;">&nbsp;</td></tr>
            </table>

            <!-- Signature -->
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
                   style="mso-table-lspace:0pt;mso-table-rspace:0pt;margin-top:20px;">
              <tr>
                <td style="padding:16px 0 0;">
                  <p style="margin:0 0 4px;font-size:14px;color:#64748b;
                             mso-line-height-rule:exactly;">Cordialement,</p>
                  <p style="margin:0;font-size:15px;color:#0f172a;
                             mso-line-height-rule:exactly;"><strong>L'&Eacute;quipe {$safeName}</strong></p>
                </td>
              </tr>
              {$signatureImageHtml}
            </table>

          </td>
        </tr>

        <!-- ═══ FOOTER ═══ -->
        <tr>
          <td style="background:#f8fafc;padding:20px 32px;text-align:center;border-top:1px solid #e2e8f0;">
            <p style="margin:0 0 6px;font-size:12px;line-height:1.5;color:#94a3b8;
                       mso-line-height-rule:exactly;">&copy; {$year} {$safeName} &middot; Tous droits r&eacute;serv&eacute;s</p>
            <p style="margin:0;font-size:11px;line-height:1.5;color:#cbd5e1;
                       mso-line-height-rule:exactly;">Vous recevez cet email suite &agrave; votre inscription sur {$safeName}.</p>
          </td>
        </tr>

      </table>

    </td>
  </tr>
</table>

</body>
</html>
HTML;

        return $this->sendHtml($toEmail, $cfg['subject'], $html);
    }

    /** Email de réinitialisation de mot de passe. */
    public function sendPasswordResetEmail(string $toEmail, string $resetLink): bool
    {
        $subject = 'Réinitialisation de votre mot de passe - ' . $this->fromName;
        $html    = $this->wrapHtml(
            'Réinitialisation du mot de passe',
            '<p>Bonjour,</p>
             <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
             <p>Cliquez sur le bouton ci-dessous (lien valable <strong>48 heures</strong>) :</p>',
            $resetLink,
            'Réinitialiser mon mot de passe',
            '<p style="margin:16px 0 0;font-size:12px;line-height:1.5;color:#64748b;">Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message. Votre mot de passe reste inchangé.</p>'
        );
        return $this->sendHtml($toEmail, $subject, $html);
    }

    /**
     * Email admin → utilisateur individuel.
     * @param string $toEmail  Email du destinataire
     * @param string $toName   Nom du destinataire (pour personnalisation)
     * @param string $subject  Objet du message
     * @param string $message  Corps du message (texte simple, converti en HTML)
     */
    public function sendAdminMail(
        string $toEmail,
        string $toName,
        string $subject,
        string $message
    ): bool {
        $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $html    = $this->wrapHtmlSimple(
            $subject,
            '<p style="margin:0 0 14px;font-size:14px;line-height:1.55;color:#334155;">Bonjour <strong style="color:#0f172a;">'
            . htmlspecialchars($toName, ENT_QUOTES) . '</strong>,</p>'
            . '<div style="margin:0;padding:16px 18px;background:#f8fafc;border-left:3px solid #16a34a;border-radius:0 8px 8px 0;'
            . 'font-size:14px;line-height:1.6;color:#475569;mso-line-height-rule:exactly;">' . $safeMsg . '</div>'
            . '<p style="margin:18px 0 0;font-size:12px;line-height:1.5;color:#94a3b8;">Message envoyé depuis '
            . htmlspecialchars($this->fromName, ENT_QUOTES) . '. En cas de question, répondez à cet email ou contactez le support.</p>'
        );
        return $this->sendHtml($toEmail, $subject, $html);
    }

    /**
     * Notification système (abonnement actif, paiement reçu, etc.).
     */
    public function sendNotification(
        string $toEmail,
        string $toName,
        string $subject,
        string $message,
        string $btnUrl  = '',
        string $btnText = 'Voir mon espace'
    ): bool {
        $safeMsg = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        $html    = $this->wrapHtml(
            $subject,
            '<p style="margin:0 0 14px;font-size:14px;line-height:1.55;color:#334155;">Bonjour <strong style="color:#0f172a;">'
            . htmlspecialchars($toName, ENT_QUOTES) . '</strong>,</p>
             <div style="margin:0;padding:16px 18px;background:#f8fafc;border-left:3px solid #16a34a;border-radius:0 8px 8px 0;'
            . 'font-size:14px;line-height:1.6;color:#475569;mso-line-height-rule:exactly;">' . $safeMsg . '</div>',
            $btnUrl,
            $btnText
        );
        return $this->sendHtml($toEmail, $subject, $html);
    }

    /**
     * Envoi HTML (multipart : HTML + fallback texte brut).
     */
    public function sendHtml(string $to, string $subject, string $htmlBody): bool
    {
        $plainText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $htmlBody));
        $plainText = preg_replace('/\n{3,}/', "\n\n", $plainText) ?? $plainText;
        $plainText = $this->normalizeTextForMail($plainText);

        if ($this->isSmtpConfigured()) {
            return $this->sendViaSmtpHtml($to, $subject, $htmlBody, $plainText);
        }
        $boundary = '=_' . md5(uniqid((string) mt_rand(), true));
        $plainQp  = quoted_printable_encode($plainText);
        $htmlQp   = quoted_printable_encode($htmlBody);
        $headers  = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= $plainQp . "\r\n\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body .= $htmlQp . "\r\n\r\n";
        $body .= "--{$boundary}--\r\n";

        return @mail($to, $this->encodeHeader($subject), $body, $headers);
    }

    /**
     * Envoi texte brut (legacy / fallback).
     */
    public function send(string $to, string $subject, string $body): bool
    {
        $body = $this->normalizeTextForMail($body);
        if ($this->isSmtpConfigured()) {
            return $this->sendViaSmtp($to, $subject, $body);
        }
        $headers  = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "Reply-To: {$this->fromEmail}\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        return @mail($to, $subject, $body, $headers);
    }

    // ──────────────────────────────────────────────────────────────
    // TEMPLATES HTML
    // ──────────────────────────────────────────────────────────────

    /**
     * Template HTML complet avec bouton CTA.
     */
    private function wrapHtml(
        string $title,
        string $bodyHtml,
        string $btnUrl,
        string $btnText,
        string $footerExtra = ''
    ): string {
        $logo  = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/assets/images/logo.png' : '';
        $name  = htmlspecialchars($this->fromName, ENT_QUOTES);
        $year  = date('Y');
        $signatureImageUrl = $this->getParametre('mail_signature_image_url') ?? '';
        $signatureImageHtml = $signatureImageUrl !== ''
            ? "<div style='margin:18px 0 6px;text-align:center;'>
                 <div style='display:inline-block;width:100%;max-width:400px;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;'>
                   <img src='" . htmlspecialchars($signatureImageUrl, ENT_QUOTES) . "' alt='Signature {$name}' style='display:block;width:100%;max-width:400px;height:auto;border:0;outline:none;text-decoration:none;'>
                 </div>
               </div>"
            : '';
        $btn   = $btnUrl !== ''
            ? "<div style='text-align:center;margin:24px 0;'>
                 <a href='" . htmlspecialchars($btnUrl, ENT_QUOTES) . "'
                    style='display:inline-block;padding:12px 24px;background:#16a34a;color:#ffffff;font-weight:600;font-size:14px;text-decoration:none;border-radius:8px;'>
                    " . htmlspecialchars($btnText, ENT_QUOTES) . "
                 </a>
               </div>"
            : '';
        return <<<HTML
<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f1f5f9;padding:24px 16px;">
  <tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
      <!-- Header -->
      <tr><td style="background:linear-gradient(135deg,#16a34a,#15803d);padding:20px 24px;text-align:center;">
        <span style="font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.02em;line-height:1.3;">{$name}</span>
      </td></tr>
      <!-- Body — px (les rem s'affichent trop grands sur plusieurs clients mail) -->
      <tr><td style="padding:24px 28px 20px;color:#334155;font-size:14px;line-height:1.6;mso-line-height-rule:exactly;">
        {$bodyHtml}
        {$btn}
        {$signatureImageHtml}
        {$footerExtra}
      </td></tr>
      <!-- Footer -->
      <tr><td style="background:#f8fafc;padding:16px 24px;text-align:center;border-top:1px solid #e2e8f0;">
        <p style="margin:0 0 6px;font-size:11px;line-height:1.5;color:#94a3b8;">© {$year} {$name} · Tous droits réservés</p>
        <p style="margin:0;font-size:11px;line-height:1.5;color:#cbd5e1;">Vous recevez cet email car vous êtes inscrit sur {$name}.</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>
HTML;
    }

    /** Template sans bouton CTA. */
    private function wrapHtmlSimple(string $title, string $bodyHtml, string $footerExtra = ''): string
    {
        return $this->wrapHtml($title, $bodyHtml, '', '', $footerExtra);
    }

    // ──────────────────────────────────────────────────────────────
    // SMTP
    // ──────────────────────────────────────────────────────────────

    private function sendViaSmtpHtml(string $to, string $subject, string $htmlBody, string $plainText): bool
    {
        $boundary  = '=_' . md5(uniqid((string) mt_rand(), true));
        $plainQp   = quoted_printable_encode($this->normalizeTextForMail($plainText));
        $htmlQp    = quoted_printable_encode($htmlBody);
        $msgId     = '<' . md5(uniqid((string) mt_rand(), true)) . '@' . $this->smtpEhloHostname() . '>';
        $dateRfc   = date('r');
        $data  = "Date: {$dateRfc}\r\n";
        $data .= "Message-ID: {$msgId}\r\n";
        $data .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $data .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $data .= "To: {$to}\r\n";
        $data .= "MIME-Version: 1.0\r\n";
        $data .= "X-Mailer: GLOBALO-Mailer/1.0\r\n";
        $data .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $data .= "\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $data .= $plainQp . "\r\n\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Type: text/html; charset=UTF-8\r\n";
        $data .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $data .= $htmlQp . "\r\n\r\n";
        $data .= "--{$boundary}--\r\n";
        $data .= "\r\n.\r\n";
        return $this->sendViaSmtpRaw($to, $subject, $this->enforceSmtpLineLength($data));
    }

    private function sendViaSmtp(string $to, string $subject, string $body): bool
    {
        $body    = $this->normalizeTextForMail($body);
        $msgId   = '<' . md5(uniqid((string) mt_rand(), true)) . '@' . $this->smtpEhloHostname() . '>';
        $dateRfc = date('r');
        $data  = "Date: {$dateRfc}\r\n";
        $data .= "Message-ID: {$msgId}\r\n";
        $data .= "Subject: " . $this->encodeHeader($subject) . "\r\n";
        $data .= "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $data .= "To: {$to}\r\n";
        $data .= "X-Mailer: GLOBALO-Mailer/1.0\r\n";
        $data .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $data .= "Content-Transfer-Encoding: quoted-printable\r\n";
        $data .= "\r\n" . quoted_printable_encode($body) . "\r\n.\r\n";
        return $this->sendViaSmtpRaw($to, $subject, $this->enforceSmtpLineLength($data));
    }

    /**
     * Identifiant pour EHLO : doit être un nom de domaine valide (RFC).
     * Derrière reverse-proxy, HTTP_HOST peut être absent ou trompeur — on retombe sur le domaine de l'expéditeur.
     */
    private function smtpEhloHostname(): string
    {
        $clean = static function (string $s): string {
            $s = trim($s);
            if ($s === '') {
                return '';
            }
            // Autorise labels ASCII pour EHLO (évite espaces, chemins d'URL, etc.)
            return (string) preg_replace('/[^a-zA-Z0-9.-]/', '', $s);
        };

        $fromEnv = getenv('SMTP_EHLO');
        if ($fromEnv !== false && ($fromEnv = $clean((string) $fromEnv)) !== '') {
            return $fromEnv;
        }

        $candidates = [];
        if (!empty($_SERVER['HTTP_HOST'])) {
            $h = (string) $_SERVER['HTTP_HOST'];
            $h = preg_replace('#:\d+$#', '', $h) ?? $h;
            $candidates[] = $clean(explode(':', $h, 2)[0]);
        }
        if (!empty($_SERVER['SERVER_NAME'])) {
            $candidates[] = $clean((string) $_SERVER['SERVER_NAME']);
        }
        foreach ($candidates as $c) {
            if ($c !== '' && strlen($c) <= 253) {
                return $c;
            }
        }

        if (strpos($this->fromEmail, '@') !== false) {
            $domain = substr(strrchr($this->fromEmail, '@'), 1) ?: '';
            $domain = $clean($domain);
            if ($domain !== '') {
                return $domain;
            }
        }

        if ($this->smtpHost) {
            $h = $clean((string) $this->smtpHost);
            if ($h !== '') {
                return $h;
            }
        }

        $hn = gethostname();
        if ($hn !== false && $hn !== '') {
            $h = $clean($hn);
            if ($h !== '') {
                return $h;
            }
        }

        return 'localhost';
    }

    private function sendViaSmtpRaw(string $to, string $subject, string $data): bool
    {
        if (str_ends_with($data, "\r\n.\r\n")) {
            $data = $this->applySmtpDotStuffing(substr($data, 0, -5)) . "\r\n.\r\n";
        }

        $host = $this->smtpHost;
        $port = $this->smtpPort ?: 587;
        $errno  = 0; $errstr = '';

        // Port 465 = SMTPS (TLS implicite dès la connexion).
        // Port 587 (ou autre) = STARTTLS (connexion claire puis négociation TLS).
        $implicitTls = ($this->smtpSecure && $port === 465);
        $startTls    = ($this->smtpSecure && !$implicitTls);

        // Contexte SSL : désactive la vérification du certificat du serveur mail
        // (nécessaire sur la plupart des hébergements mutualisés cPanel dont le
        // certificat mail ne correspond pas exactement au hostname).
        $sslContext = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]);

        $prefix = $implicitTls ? 'tls://' : '';
        $socket = @stream_socket_client(
            $prefix . $host . ':' . $port,
            $errno, $errstr, 15,
            STREAM_CLIENT_CONNECT,
            $sslContext
        );
        if (!$socket) {
            $this->lastError = "Connexion impossible à {$host}:{$port} — [{$errno}] {$errstr}";
            error_log("Mailer SMTP: connexion impossible {$host}:{$port} - [{$errno}] {$errstr}");
            return false;
        }

        // Timeout de lecture : évite que fgets() bloque indéfiniment si le serveur ne répond pas
        stream_set_timeout($socket, 10);

        $ehloId = $this->smtpEhloHostname();

        $this->smtpLine($socket, null);
        $this->smtpLine($socket, 'EHLO ' . $ehloId);

        if ($startTls) {
            $resp = $this->smtpLine($socket, 'STARTTLS');
            if (strpos($resp, '220') === false) {
                $this->lastError = "STARTTLS refusé par {$host} — réponse : {$resp}";
                error_log("Mailer SMTP: STARTTLS refusé par {$host} - {$resp}");
                fclose($socket);
                return false;
            }
            $crypto = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$crypto) {
                $this->lastError = "Négociation TLS échouée avec {$host}";
                error_log("Mailer SMTP: négociation TLS échouée avec {$host}");
                fclose($socket);
                return false;
            }
            // Re-salutation après TLS
            $this->smtpLine($socket, 'EHLO ' . $ehloId);
        }

        if ($this->smtpUser && $this->smtpPass) {
            $this->smtpLine($socket, 'AUTH LOGIN');
            $this->smtpLine($socket, base64_encode($this->smtpUser));
            $resp = $this->smtpLine($socket, base64_encode($this->smtpPass));
            if (strpos($resp, '235') === false) {
                $this->lastError = "Authentification SMTP échouée — vérifiez smtp_user / smtp_pass";
                error_log("Mailer SMTP: authentification échouée - {$resp}");
                fclose($socket);
                return false;
            }
        }

        $resp = $this->smtpLine($socket, 'MAIL FROM:<' . $this->fromEmail . '>');
        if (strpos($resp, '250') === false) {
            $clean = trim(str_replace(["\r", "\n"], ' ', $resp));
            $this->lastError = "Adresse expéditeur refusée (MAIL FROM:{$this->fromEmail}) — sur cPanel, mail_from doit correspondre au compte SMTP. Réponse : {$clean}";
            error_log('Mailer SMTP: MAIL FROM refusé — réponse : ' . $clean);
            fclose($socket);
            return false;
        }
        $resp = $this->smtpLine($socket, 'RCPT TO:<' . $to . '>');
        if (strpos($resp, '250') === false && strpos($resp, '251') === false) {
            $clean = trim(str_replace(["\r", "\n"], ' ', $resp));
            $this->lastError = "Destinataire refusé (RCPT TO:{$to}) — réponse : {$clean}";
            error_log('Mailer SMTP: RCPT TO refusé pour ' . $to . ' — réponse : ' . $clean);
            fclose($socket);
            return false;
        }
        $resp = $this->smtpLine($socket, 'DATA');
        if (strpos($resp, '354') === false) {
            $clean = trim(str_replace(["\r", "\n"], ' ', $resp));
            $this->lastError = "Commande DATA refusée — réponse : {$clean}";
            error_log('Mailer SMTP: commande DATA refusée — réponse : ' . $clean);
            fclose($socket);
            return false;
        }
        $this->smtpWrite($socket, $data);
        $resp = $this->smtpLine($socket, null);
        $ok   = strpos($resp, '250') !== false;
        if (!$ok) {
            $clean = trim(preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $resp)) ?? '');
            $this->lastError = "Envoi refusé par le serveur — réponse : {$clean}";
            error_log('Mailer SMTP: envoi DATA refusé ou incomplet — réponse serveur : ' . $clean);
        }
        $this->smtpLine($socket, 'QUIT');
        fclose($socket);
        return $ok;
    }

    /**
     * RFC 5321 : toute ligne du corps commençant par "." doit être préfixée par un "." supplémentaire.
     */
    private function applySmtpDotStuffing(string $body): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $body) ?: [];
        $out   = [];
        foreach ($lines as $line) {
            if ($line !== '' && $line[0] === '.') {
                $line = '.' . $line;
            }
            $out[] = $line;
        }
        return implode("\r\n", $out);
    }

    private function smtpLine($socket, ?string $line): string
    {
        if ($line !== null) { $this->smtpWrite($socket, $line . "\r\n"); }
        $r        = '';
        $deadline = microtime(true) + 10.0; // 10 secondes max

        while (microtime(true) < $deadline) {
            // Vérifier les octets non lus dans le buffer SSL (stream_select rate
            // les données déjà déchiffrées en mémoire sur les sockets TLS).
            $meta = stream_get_meta_data($socket);
            if (!empty($meta['unread_bytes'])) {
                $s = fgets($socket, 512);
            } else {
                // Attendre que le socket soit lisible (200 ms par tranche)
                $read = [$socket]; $w = $ex = null;
                $ready = @stream_select($read, $w, $ex, 0, 200000);
                if ($ready === false || $ready === 0) {
                    continue;
                }
                $s = fgets($socket, 512);
            }
            if ($s === false || $s === '') {
                break;
            }
            $r .= $s;
            if (strlen($s) >= 4 && $s[3] === ' ') {
                break;
            }
        }
        return $r;
    }

    private function smtpWrite($socket, string $data): void
    {
        $total   = strlen($data);
        $written = 0;
        while ($written < $total) {
            $chunk = fwrite($socket, substr($data, $written));
            if ($chunk === false || $chunk === 0) {
                break;
            }
            $written += $chunk;
        }
    }

    private function encodeHeader(string $s): string
    {
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }

    private function normalizeTextForMail(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $wrapped = array_map(static function (string $line): string {
            return wordwrap($line, 78, "\n", true);
        }, $lines);
        return str_replace("\n", "\r\n", implode("\n", $wrapped));
    }

    private function enforceSmtpLineLength(string $data, int $maxLen = 998): string
    {
        $lines = preg_split("/\r\n|\n|\r/", $data) ?: [];
        $out = [];
        foreach ($lines as $line) {
            if (strlen($line) <= $maxLen) {
                $out[] = $line;
                continue;
            }
            $chunks = str_split($line, $maxLen);
            foreach ($chunks as $chunk) {
                $out[] = $chunk;
            }
        }
        return implode("\r\n", $out);
    }
}
