<?php
/**
 * Grille radio stylée Orange / Moov / Wave (logos dédiés).
 * Avant include : $baseUrl, optionnel $intouch_op_field_name (défaut operator), $intouch_op_default (ORANGE|MOOV|WAVE).
 */
$b = rtrim((string) ($baseUrl ?? (defined('BASE_URL') ? BASE_URL : '')), '/');
$esc = static fn(string $v): string => \App\Core\Security::escape($v);
$fname = isset($intouch_op_field_name) && $intouch_op_field_name !== '' ? (string) $intouch_op_field_name : 'operator';
$def = strtoupper(trim((string) ($intouch_op_default ?? 'ORANGE')));
if (!in_array($def, ['ORANGE', 'MOOV', 'WAVE'], true)) {
    $def = 'ORANGE';
}
$ops = [
    'ORANGE' => ['file' => 'orange-money.png', 'label' => 'Orange Money'],
    'MOOV'   => ['file' => 'moov-africa.png',  'label' => 'Moov Africa'],
    'WAVE'   => ['file' => 'wave.png',         'label' => 'Wave'],
];
$sfx = isset($intouch_op_suffix) && $intouch_op_suffix !== ''
    ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $intouch_op_suffix)
    : bin2hex(random_bytes(3));
?>
<fieldset class="intouch-op-picker">
    <legend class="intouch-op-picker__legend">Opérateur Mobile Money</legend>
    <div class="intouch-op-picker__grid" role="radiogroup" aria-label="Opérateur">
        <?php foreach ($ops as $val => $info):
            $checked = $def === $val;
            $id = 'intouch-op-' . strtolower($val) . '-' . $sfx;
            ?>
        <label class="intouch-op-card intouch-op-card--<?= strtolower($val) ?><?= $checked ? ' intouch-op-card--selected' : '' ?>">
            <input type="radio"
                   name="<?= $esc($fname) ?>"
                   value="<?= $esc($val) ?>"
                   class="intouch-op-card__input"
                   id="<?= $esc($id) ?>"
                   <?= $checked ? 'checked' : '' ?>
                   required>
            <span class="intouch-op-card__face">
                <img class="intouch-op-card__logo"
                     src="<?= $esc($b) ?>/assets/images/operators/<?= $esc($info['file']) ?>"
                     alt=""
                     width="120"
                     height="48"
                     loading="lazy">
                <span class="intouch-op-card__label"><?= $esc($info['label']) ?></span>
            </span>
        </label>
        <?php endforeach; ?>
    </div>
</fieldset>
<script>
(function () {
  document.querySelectorAll('.intouch-op-picker__grid').forEach(function (grid) {
    function sync() {
      grid.querySelectorAll('.intouch-op-card').forEach(function (c) {
        var r = c.querySelector('.intouch-op-card__input');
        c.classList.toggle('intouch-op-card--selected', r && r.checked);
      });
    }
    grid.querySelectorAll('.intouch-op-card__input').forEach(function (inp) {
      inp.addEventListener('change', sync);
    });
    sync();
  });
})();
</script>
