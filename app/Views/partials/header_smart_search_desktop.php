<?php
$b = rtrim(BASE_URL ?? '', '/');
$hsp = $home_smart_search_placeholders ?? '';
$idSuffix = isset($header_search_suffix) ? (string) $header_search_suffix : 'hdr';
?>
<div class="header-smart-search-slot" id="header-smart-search-slot" aria-hidden="true">
    <div
        id="header-smart-search-<?= \App\Core\Security::escape($idSuffix) ?>"
        class="hero-smart-search hero-smart-search--compact-nav"
        data-smart-search
        data-smart-search-api="<?= $b ?>/api/search/suggest"
        data-smart-search-app="0"
        data-smart-search-placeholders="<?= $hsp ?>"
    >
        <form class="hero-smart-search__form js-smart-search-form" method="get" action="<?= $b ?>/experts" role="search" autocomplete="off">
            <div class="hero-smart-search__field-wrap">
                <label for="header-smart-search-q-<?= \App\Core\Security::escape($idSuffix) ?>" class="visually-hidden">Rechercher un expert, une compétence ou une matière</label>
                <input type="search" id="header-smart-search-q-<?= \App\Core\Security::escape($idSuffix) ?>" name="q" class="hero-smart-search__input js-smart-search-input" placeholder="Quel service recherchez-vous ?" autocomplete="off" aria-autocomplete="list" aria-expanded="false">
                <button type="submit" class="hero-smart-search__submit" aria-label="Lancer la recherche">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </button>
            </div>
            <div class="hero-smart-search__dropdown smart-search-dropdown js-smart-search-results" hidden role="listbox" aria-label="Suggestions"></div>
        </form>
    </div>
</div>
