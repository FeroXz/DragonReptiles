    </div>
</main>
<footer class="app-footer">
    <div class="app-footer__inner">
        <div class="prose prose-invert max-w-none">
            <?= nl2br(htmlspecialchars($settings['footer_text'] ?? '')) ?>
        </div>
        <div class="app-footer__meta">
            <span>© <?= date('Y') ?> <?= htmlspecialchars($settings['site_title'] ?? APP_NAME) ?></span>
            <span aria-hidden="true">•</span>
            <span><?= htmlspecialchars(content_value($settings, 'footer_rights')) ?></span>
        </div>
    </div>
</footer>
<script>
    (function () {
        const overlay = document.querySelector('[data-menu-overlay]');
        if (!overlay) {
            return;
        }
        const overlayPanel = overlay.querySelector('.app-menu-overlay__panel');
        const toggles = document.querySelectorAll('[data-menu-toggle]');
        const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';
        let lastFocused = null;

        function handleFocusTrap(event) {
            if (!overlay.classList.contains('is-visible') || !overlayPanel) {
                return;
            }
            if (event.key === 'Escape') {
                event.preventDefault();
                setMenuState(false);
                return;
            }
            if (event.key !== 'Tab') {
                return;
            }
            const focusable = overlayPanel.querySelectorAll(focusableSelector);
            if (focusable.length === 0) {
                return;
            }
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey) {
                if (document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                }
            } else if (document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function setMenuState(open) {
            overlay.classList.toggle('is-visible', open);
            overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
            document.body.classList.toggle('menu-open', open);
            toggles.forEach((btn) => btn.setAttribute('aria-expanded', open ? 'true' : 'false'));
            if (open) {
                lastFocused = document.activeElement;
                requestAnimationFrame(() => {
                    overlayPanel?.focus();
                });
                document.addEventListener('keydown', handleFocusTrap);
            } else {
                document.removeEventListener('keydown', handleFocusTrap);
                if (lastFocused && typeof lastFocused.focus === 'function') {
                    lastFocused.focus();
                }
            }
        }

        toggles.forEach((button) => {
            button.addEventListener('click', () => {
                const nextState = !overlay.classList.contains('is-visible');
                setMenuState(nextState);
            });
        });

        overlay.addEventListener('click', (event) => {
            if (event.target === overlay || event.target.hasAttribute('data-menu-dismiss')) {
                setMenuState(false);
            }
        });
    })();
</script>
<?php if (($currentRoute ?? '') === 'genetics'): ?>
    <script src="<?= asset('genetics.js') ?>"></script>
<?php endif; ?>
<?php if (isset($currentRoute) && str_starts_with($currentRoute, 'admin/')): ?>
    <script src="<?= asset('admin.js') ?>"></script>
<?php endif; ?>
</body>
</html>
