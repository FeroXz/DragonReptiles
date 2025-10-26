<nav class="admin-nav" aria-label="Admin-Navigation">
    <a href="<?= BASE_URL ?>/index.php?route=admin/dashboard" class="admin-nav__link <?= $currentRoute === 'admin/dashboard' ? 'is-active' : '' ?>">
        <span class="admin-nav__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M4 12h4v8H4z" />
                <path d="M10 4h4v16h-4z" />
                <path d="M16 9h4v11h-4z" />
            </svg>
        </span>
        <span class="admin-nav__label">Übersicht</span>
    </a>
    <a href="<?= BASE_URL ?>/index.php?route=admin/animals" class="admin-nav__link <?= $currentRoute === 'admin/animals' ? 'is-active' : '' ?>">
        <span class="admin-nav__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M5 11c0-4.5 3-7 7-7s7 2.5 7 7" />
                <path d="M4 11h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z" />
                <path d="M9 15h6" />
            </svg>
        </span>
        <span class="admin-nav__label">Tiere</span>
    </a>
    <?php if (is_authorized('can_manage_animals')): ?>
        <a href="<?= BASE_URL ?>/index.php?route=admin/breeding" class="admin-nav__link <?= $currentRoute === 'admin/breeding' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M7 17a5 5 0 0 1 5-5" />
                    <path d="M17 7a5 5 0 0 1-5 5" />
                    <path d="M12 22v-9" />
                    <path d="M12 2v5" />
                </svg>
            </span>
            <span class="admin-nav__label">Zuchtplanung</span>
        </a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/index.php?route=admin/adoption" class="admin-nav__link <?= $currentRoute === 'admin/adoption' ? 'is-active' : '' ?>">
        <span class="admin-nav__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M12 21s-7-4.5-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 11c0 5.5-7 10-7 10z" />
            </svg>
        </span>
        <span class="admin-nav__label">Tierabgabe</span>
    </a>
    <a href="<?= BASE_URL ?>/index.php?route=admin/inquiries" class="admin-nav__link <?= $currentRoute === 'admin/inquiries' ? 'is-active' : '' ?>">
        <span class="admin-nav__icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                <path d="M21 15a2 2 0 0 1-2 2H9l-4 4V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2z" />
            </svg>
        </span>
        <span class="admin-nav__label">Anfragen</span>
    </a>
    <?php if (is_authorized('can_manage_settings')): ?>
        <a href="<?= BASE_URL ?>/index.php?route=admin/pages" class="admin-nav__link <?= $currentRoute === 'admin/pages' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M5 5h10l4 4v10a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" />
                    <path d="M15 5v4h4" />
                </svg>
            </span>
            <span class="admin-nav__label">Seiten</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/news" class="admin-nav__link <?= $currentRoute === 'admin/news' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M4 5h16" />
                    <path d="M4 11h16" />
                    <path d="M4 17h10" />
                </svg>
            </span>
            <span class="admin-nav__label">Neuigkeiten</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/care" class="admin-nav__link <?= $currentRoute === 'admin/care' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M12 6c-1.5-3-5-3-6 0-1.5 4 2 7 6 10 4-3 7.5-6 6-10-1-3-4.5-3-6 0z" />
                </svg>
            </span>
            <span class="admin-nav__label">Pflegeleitfaden</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/genetics" class="admin-nav__link <?= $currentRoute === 'admin/genetics' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M6 3c0 6 12 6 12 12" />
                    <path d="M6 9c0 6 12 6 12 12" />
                    <path d="M12 3v18" />
                </svg>
            </span>
            <span class="admin-nav__label">Genetik</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/gallery" class="admin-nav__link <?= $currentRoute === 'admin/gallery' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <rect x="3" y="5" width="18" height="14" rx="2" />
                    <path d="M3 15l4-4 3 3 5-5 6 6" />
                </svg>
            </span>
            <span class="admin-nav__label">Galerie</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/home-layout" class="admin-nav__link <?= $currentRoute === 'admin/home-layout' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M4 6h16" />
                    <path d="M4 12h10" />
                    <path d="M4 18h16" />
                </svg>
            </span>
            <span class="admin-nav__label">Startseite</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/settings" class="admin-nav__link <?= $currentRoute === 'admin/settings' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09A1.65 1.65 0 0 0 11 3.09V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51h.09a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                </svg>
            </span>
            <span class="admin-nav__label">Einstellungen</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/content" class="admin-nav__link <?= $currentRoute === 'admin/content' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M4 4h16v16H4z" />
                    <path d="M4 9h16" />
                    <path d="M9 4v16" />
                </svg>
            </span>
            <span class="admin-nav__label">Texte</span>
        </a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/update" class="admin-nav__link <?= $currentRoute === 'admin/update' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M4 4v6h6" />
                    <path d="M20 20v-6h-6" />
                    <path d="M20 4l-7 7a4 4 0 0 1-5.66 0L4 7" />
                </svg>
            </span>
            <span class="admin-nav__label">Updates</span>
        </a>
    <?php endif; ?>
    <?php if (current_user()['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/index.php?route=admin/users" class="admin-nav__link <?= $currentRoute === 'admin/users' ? 'is-active' : '' ?>">
            <span class="admin-nav__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                    <circle cx="9" cy="7" r="4" />
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                </svg>
            </span>
            <span class="admin-nav__label">Benutzer</span>
        </a>
    <?php endif; ?>
</nav>
