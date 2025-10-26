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
        const mobileToggle = document.querySelector('[data-mobile-nav-toggle]');
        const mobilePanel = document.querySelector('[data-mobile-nav-panel]');
        if (mobileToggle && mobilePanel) {
            mobileToggle.addEventListener('click', () => {
                mobilePanel.classList.toggle('hidden');
                const expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
                mobileToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            });
        }

        const navEntries = [];

        function setExpanded(entry, expanded) {
            if (!entry) {
                return;
            }
            if (expanded) {
                entry.dropdown.classList.add('open');
            } else {
                entry.dropdown.classList.remove('open');
            }
            entry.trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            if (entry.chevron) {
                entry.chevron.classList.toggle('rotate-180', expanded);
            }
        }

        function closeAll(exceptEntry) {
            navEntries.forEach((entry) => {
                if (!exceptEntry || entry !== exceptEntry) {
                    setExpanded(entry, false);
                }
            });
        }

        document.querySelectorAll('[data-nav-group]').forEach((group) => {
            const trigger = group.querySelector('[data-nav-trigger]');
            const dropdown = group.querySelector('.app-nav__dropdown');
            if (!trigger || !dropdown) {
                return;
            }
            const chevron = trigger.querySelector('[data-chevron]');
            const entry = { group, trigger, dropdown, chevron };
            navEntries.push(entry);

            trigger.setAttribute('aria-haspopup', 'true');
            trigger.setAttribute('aria-expanded', dropdown.classList.contains('open') ? 'true' : 'false');

            trigger.addEventListener('click', (event) => {
                if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
                    return;
                }

                if (!dropdown.classList.contains('open')) {
                    event.preventDefault();
                    closeAll(entry);
                    setExpanded(entry, true);
                } else {
                    closeAll();
                }
            });

            trigger.addEventListener('keydown', (event) => {
                if ((event.key === ' ' || event.key === 'Enter') && !dropdown.classList.contains('open')) {
                    event.preventDefault();
                    closeAll(entry);
                    setExpanded(entry, true);
                }
            });

            group.addEventListener('mouseleave', () => {
                setExpanded(entry, false);
            });

            group.addEventListener('focusout', (event) => {
                if (!group.contains(event.relatedTarget)) {
                    setExpanded(entry, false);
                }
            });

            group.addEventListener('keyup', (event) => {
                if (event.key === 'Escape') {
                    setExpanded(entry, false);
                    trigger.focus();
                }
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-nav-group]')) {
                closeAll();
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
