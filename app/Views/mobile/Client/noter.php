<?php
$baseUrl     = rtrim(BASE_URL ?? '', '/');
$e           = fn($s) => \App\Core\Security::escape($s ?? '');
$csrfField   = \App\Core\Security::getCsrfField();
$reservation = $reservation ?? [];
$errors      = $errors ?? [];
$bp          = $client_base_path ?? '/client';
?>

<!-- En-tête -->
<div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem">
    <a href="<?= $baseUrl . $bp ?>/reservations/<?= (int)($reservation['id'] ?? 0) ?>" style="display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:var(--border);color:var(--text-muted);text-decoration:none;flex-shrink:0" aria-label="Retour">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
    </a>
    <div>
        <h1 style="margin:0;font-size:1.1rem;font-weight:700;color:var(--primary)">Noter l'expert</h1>
        <p style="margin:0;font-size:0.78rem;color:var(--text-muted)"><?= $e($reservation['demande_titre'] ?? '') ?></p>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:var(--radius);padding:0.85rem 1rem;margin-bottom:1rem">
    <?php foreach ($errors as $err): ?>
    <p style="margin:0 0 0.25rem;font-size:0.85rem;color:#dc2626">• <?= $e($err) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="background:var(--card-bg);border:1px solid var(--border);border-radius:var(--radius);padding:1.25rem;margin-bottom:1rem">
    <form method="post" action="<?= $baseUrl . $bp ?>/noter/<?= (int)($reservation['id'] ?? 0) ?>" class="form-mobile">
        <?= $csrfField ?>

        <!-- Étoiles -->
        <div style="margin-bottom:1.25rem">
            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.75rem;text-align:center">
                Votre note (1 à 5 étoiles) <span style="color:#dc2626">*</span>
            </label>
            <div style="display:flex;justify-content:center;gap:0.5rem" id="stars-container" role="group" aria-label="Note de 1 à 5 étoiles">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <button type="button" class="rating-star-mobile" data-value="<?= $i ?>"
                        style="font-size:2.5rem;background:none;border:none;cursor:pointer;color:#d1d5db;padding:0;line-height:1;transition:color 0.1s,transform 0.1s"
                        aria-label="<?= $i ?> étoile<?= $i > 1 ? 's' : '' ?>">★</button>
                <?php endfor; ?>
            </div>
            <input type="hidden" name="note" id="note" value="" required>
            <p style="text-align:center;margin:0.5rem 0 0;font-size:0.78rem;color:var(--text-muted)" id="note-label">Touchez une étoile</p>
        </div>

        <!-- Commentaire -->
        <div style="margin-bottom:1.25rem">
            <label style="display:block;font-size:0.85rem;font-weight:600;color:var(--text);margin-bottom:0.35rem">Commentaire (optionnel)</label>
            <textarea name="commentaire" rows="4"
                      placeholder="Décrivez votre expérience avec cet expert…"
                      style="display:block;width:100%;padding:0.75rem 1rem;font-size:16px;border:1.5px solid var(--border);border-radius:var(--radius);background:#fff;color:var(--text);font-family:var(--font);resize:vertical"></textarea>
        </div>

        <button type="submit" class="btn-mobile btn-primary" style="display:flex;align-items:center;justify-content:center;gap:0.5rem">
            ⭐ Envoyer mon avis
        </button>
    </form>
</div>

<script>
(function(){
    var stars  = document.querySelectorAll('.rating-star-mobile');
    var input  = document.getElementById('note');
    var label  = document.getElementById('note-label');
    var labels = ['','Très insuffisant','Insuffisant','Bien','Très bien','Excellent !'];

    function highlight(val) {
        stars.forEach(function(s, i) {
            var active = i < val;
            s.style.color  = active ? '#f59e0b' : '#d1d5db';
            s.style.transform = active ? 'scale(1.15)' : 'scale(1)';
        });
        if (label) label.textContent = val > 0 ? labels[val] + ' (' + val + '/5)' : 'Touchez une étoile';
    }

    stars.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var v = parseInt(btn.getAttribute('data-value'), 10);
            input.value = v;
            highlight(v);
        });
        btn.addEventListener('mouseover', function() {
            highlight(parseInt(btn.getAttribute('data-value'), 10));
        });
        btn.addEventListener('mouseout', function() {
            highlight(parseInt(input.value || '0', 10));
        });
    });
})();
</script>
