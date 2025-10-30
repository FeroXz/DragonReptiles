<?php include __DIR__ . '/../partials/header.php'; ?>
<?php
    $allTags = [];
    foreach ($items as $galleryItem) {
        if (!empty($galleryItem['tags'])) {
            foreach (array_filter(array_map('trim', explode(',', $galleryItem['tags']))) as $tag) {
                if ($tag !== '') {
                    $allTags[] = $tag;
                }
            }
        }
    }
    $allTags = array_values(array_unique($allTags));
    sort($allTags, SORT_STRING | SORT_FLAG_CASE);
?>
<section class="gallery-shell">
    <div class="gallery-hero">
        <div class="gallery-hero__text">
            <p class="gallery-hero__eyebrow">Behind the scenes</p>
            <h1 class="gallery-hero__title">Galerie</h1>
            <p class="gallery-hero__lead">Einblicke in die Welt von Dragon Reptiles – besondere Momente aus Haltung, Pflege, Events und seltenen Morphen.</p>
        </div>
        <?php if (current_user() && is_authorized('can_manage_settings')): ?>
            <a href="<?= BASE_URL ?>/index.php?route=admin/gallery" class="gallery-admin-link">
                <span>Admin bearbeiten</span>
                <svg viewBox="0 0 24 24" aria-hidden="true" class="gallery-admin-link__icon"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($allTags)): ?>
        <div class="gallery-filter">
            <div class="gallery-filter__chips" role="tablist" aria-label="Galerie-Filter">
                <button type="button" class="gallery-filter__chip is-active" data-gallery-filter="" role="tab" aria-selected="true">Alle</button>
                <?php foreach ($allTags as $tag): ?>
                    <button type="button" class="gallery-filter__chip" data-gallery-filter="<?= htmlspecialchars($tag) ?>" role="tab" aria-selected="false">#<?= htmlspecialchars($tag) ?></button>
                <?php endforeach; ?>
            </div>
            <p class="gallery-filter__hint">Tippe auf eine Kategorie, um die Bildauswahl zu filtern.</p>
        </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
        <div class="gallery-masonry" data-gallery-grid>
            <?php foreach ($items as $item): ?>
                <?php
                    $tags = array_filter(array_map('trim', explode(',', $item['tags'] ?? '')));
                    $tagAttribute = htmlspecialchars(implode('|', $tags));
                ?>
                <article class="gallery-card" data-gallery-item data-gallery-tags="<?= $tagAttribute ?>">
                    <div class="gallery-card__media">
                        <?php if ($src = media_url($item['image_path'] ?? null)): ?>
                            <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
                        <?php endif; ?>
                    </div>
                    <div class="gallery-card__overlay">
                        <div class="gallery-card__meta">
                            <span class="gallery-card__date">
                                <?= date('d.m.Y', strtotime($item['created_at'])) ?>
                                <?php if (!empty($item['is_featured'])): ?>
                                    <span class="gallery-card__badge">Highlight</span>
                                <?php endif; ?>
                            </span>
                            <h2 class="gallery-card__title"><?= htmlspecialchars($item['title']) ?></h2>
                            <?php if (!empty($item['description'])): ?>
                                <div class="gallery-card__description">
                                    <?= render_rich_text($item['description']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($tags)): ?>
                            <ul class="gallery-card__tags" aria-label="Tags">
                                <?php foreach ($tags as $tag): ?>
                                    <li>#<?= htmlspecialchars($tag) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="gallery-empty">
            <p>Noch keine Galerie-Einträge vorhanden. Melde dich als Admin an, um neue Fotos hochzuladen.</p>
        </div>
    <?php endif; ?>

    <div class="gallery-bottom-card">
        <div class="gallery-bottom-card__inner">
            <div class="gallery-bottom-card__copy">
                <span class="gallery-bottom-card__eyebrow">Dragon Reptiles</span>
                <p class="gallery-bottom-card__text">Lust auf mehr Insights? Unsere Galerie wächst mit jeder Expedition und jedem erfolgreichen Zuchtprojekt.</p>
            </div>
            <a href="<?= BASE_URL ?>/index.php?route=adoption" class="gallery-bottom-card__cta">Kontakt aufnehmen</a>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterButtons = document.querySelectorAll('[data-gallery-filter]');
        const cards = document.querySelectorAll('[data-gallery-item]');

        if (!filterButtons.length || !cards.length) {
            return;
        }

        filterButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const filterValue = button.getAttribute('data-gallery-filter');

                filterButtons.forEach((chip) => {
                    const isActive = chip === button;
                    chip.classList.toggle('is-active', isActive);
                    chip.setAttribute('aria-selected', isActive ? 'true' : 'false');
                });

                cards.forEach((card) => {
                    const tags = (card.getAttribute('data-gallery-tags') || '').split('|').filter(Boolean);
                    const shouldShow = !filterValue || tags.includes(filterValue);
                    card.classList.toggle('is-hidden', !shouldShow);
                });
            });
        });
    });
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
