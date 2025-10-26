<?php
    $linkBase = 'inline-flex w-full items-center justify-center gap-1 rounded-full border border-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-200 transition hover:border-brand-400 hover:bg-brand-500/10 hover:text-brand-100 lg:w-auto';
    $linkActive = 'inline-flex w-full items-center justify-center gap-1 rounded-full border border-brand-400 bg-brand-500/20 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-brand-100 shadow-glow lg:w-auto';
?>
<nav class="mt-6 flex flex-col gap-2 lg:flex-row lg:flex-wrap">
    <a href="<?= BASE_URL ?>/index.php?route=admin/dashboard" class="<?= $currentRoute === 'admin/dashboard' ? $linkActive : $linkBase ?>">Übersicht</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin/animals" class="<?= $currentRoute === 'admin/animals' ? $linkActive : $linkBase ?>">Tiere</a>
    <?php if (is_authorized('can_manage_animals')): ?>
        <a href="<?= BASE_URL ?>/index.php?route=admin/breeding" class="<?= $currentRoute === 'admin/breeding' ? $linkActive : $linkBase ?>">Zuchtplanung</a>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/index.php?route=admin/adoption" class="<?= $currentRoute === 'admin/adoption' ? $linkActive : $linkBase ?>">Tierabgabe</a>
    <a href="<?= BASE_URL ?>/index.php?route=admin/inquiries" class="<?= $currentRoute === 'admin/inquiries' ? $linkActive : $linkBase ?>">Anfragen</a>
    <?php if (is_authorized('can_manage_settings')): ?>
        <a href="<?= BASE_URL ?>/index.php?route=admin/pages" class="<?= $currentRoute === 'admin/pages' ? $linkActive : $linkBase ?>">Seiten</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/news" class="<?= $currentRoute === 'admin/news' ? $linkActive : $linkBase ?>">Neuigkeiten</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/care" class="<?= $currentRoute === 'admin/care' ? $linkActive : $linkBase ?>">Pflegeleitfaden</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/genetics" class="<?= $currentRoute === 'admin/genetics' ? $linkActive : $linkBase ?>">Genetik</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/gallery" class="<?= $currentRoute === 'admin/gallery' ? $linkActive : $linkBase ?>">Galerie</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/home-layout" class="<?= $currentRoute === 'admin/home-layout' ? $linkActive : $linkBase ?>">Startseite</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/settings" class="<?= $currentRoute === 'admin/settings' ? $linkActive : $linkBase ?>">Einstellungen</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/content" class="<?= $currentRoute === 'admin/content' ? $linkActive : $linkBase ?>">Texte</a>
        <a href="<?= BASE_URL ?>/index.php?route=admin/update" class="<?= $currentRoute === 'admin/update' ? $linkActive : $linkBase ?>">Updates</a>
    <?php endif; ?>
    <?php if (current_user()['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/index.php?route=admin/users" class="<?= $currentRoute === 'admin/users' ? $linkActive : $linkBase ?>">Benutzer</a>
    <?php endif; ?>
</nav>
