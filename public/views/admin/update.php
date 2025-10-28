<?php include __DIR__ . '/../partials/header.php'; ?>
<?php
$deployCsrfToken = csrf_token();
$defaultDryRunActive = !empty($defaultDeployDryRun);
$deployApiEndpoint = BASE_URL . '/admin/api/update.php';
?>
<style>
    .update-log-wrapper {
        border-radius: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.28);
        background: rgba(15, 23, 42, 0.45);
        overflow: hidden;
    }

    .update-log-viewer {
        margin: 0;
        padding: 1.25rem 1.5rem;
        max-height: 22rem;
        overflow-y: auto;
        font-family: "JetBrains Mono", "SFMono-Regular", Menlo, Consolas, monospace;
        font-size: 0.85rem;
        line-height: 1.55;
        color: rgba(226, 232, 240, 0.88);
        white-space: pre-wrap;
        word-break: break-word;
        background: transparent;
    }

    .update-log-viewer.is-empty {
        color: rgba(148, 163, 184, 0.7);
    }

    .update-log-viewer::-webkit-scrollbar {
        width: 10px;
    }

    .update-log-viewer::-webkit-scrollbar-track {
        background: rgba(15, 23, 42, 0.65);
    }

    .update-log-viewer::-webkit-scrollbar-thumb {
        background: rgba(127, 90, 240, 0.35);
        border-radius: 999px;
    }

    .badge--idle {
        background: rgba(148, 163, 184, 0.18);
        color: rgba(226, 232, 240, 0.9);
    }

    .badge--running {
        background: rgba(14, 165, 233, 0.28);
        color: #38bdf8;
    }

    .badge--success {
        background: rgba(16, 185, 129, 0.28);
        color: #4ade80;
    }

    .badge--failed {
        background: rgba(248, 113, 113, 0.32);
        color: #f87171;
    }

    .badge--canceled {
        background: rgba(251, 191, 36, 0.3);
        color: #fbbf24;
    }

    .update-feedback {
        min-height: 1.25rem;
        color: rgba(148, 163, 184, 0.9);
    }

    .update-feedback.is-error {
        color: #f87171;
    }
</style>
<section class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
    <h1>Update-Manager</h1>
    <?php include __DIR__ . '/nav.php'; ?>
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success" role="status" aria-live="polite"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger" role="alert" aria-live="assertive"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>
    <div class="grid gap-6 lg:grid-cols-2" style="margin-top:1.5rem;">
        <article class="card">
            <header class="card-header">
                <h2 class="card-title">Aktuelle Version</h2>
                <p class="card-subtitle">Dein System läuft aktuell mit Version <strong><?= htmlspecialchars($currentVersion) ?></strong>.</p>
            </header>
            <div class="prose prose-invert max-w-none text-sm text-slate-300">
                <ol class="list-decimal space-y-3 pl-5">
                    <li>Nutze auf Wunsch „Repository-Update laden“, um automatisch die aktuelle Hauptversion aus dem Git-Repository herunterzuladen.</li>
                    <li>Alternativ lade ein eigenes ZIP-Paket hoch. Benutzer-Uploads sowie die SQLite-Datenbank bleiben dabei unangetastet.</li>
                    <li>Nach erfolgreicher Aktualisierung wird die Versionsnummer automatisch angehoben.</li>
                </ol>
                <p class="mt-4 text-xs text-slate-400">Hinweis: Benutzerinhalte in <code>uploads/</code> sowie die SQLite-Datenbank bleiben unverändert. Ein Backup vor größeren Updates ist dennoch empfohlen.</p>
            </div>
        </article>
        <article class="card">
            <header class="card-header">
                <h2 class="card-title">Paket hochladen</h2>
                <p class="card-subtitle">Akzeptiert werden ZIP-Archive mit der kompletten CMS-Struktur.</p>
            </header>
            <form method="post" enctype="multipart/form-data" class="flex flex-col gap-4">
                <?= csrf_field() ?>
                <label class="form-label">
                    <span>Update-Paket (ZIP)</span>
                    <input type="file" name="package" required accept="application/zip,.zip">
                </label>
                <button type="submit" class="btn btn-primary">Paket hochladen</button>
            </form>
        </article>
    </div>
    <article class="card" style="margin-top:1.5rem;">
        <header class="card-header">
            <h2 class="card-title">System-Update</h2>
            <p class="card-subtitle">Starte das Deploy-Skript auf dem Server, behalte den Fortschritt im Blick und breche bei Bedarf ab.</p>
        </header>
        <div
            class="flex flex-col gap-4"
            data-update-deploy
            data-api="<?= htmlspecialchars($deployApiEndpoint, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
            data-default-dry-run="<?= $defaultDryRunActive ? 'true' : 'false' ?>"
        >
            <input type="hidden" id="update-csrf" value="<?= htmlspecialchars($deployCsrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                <label class="form-label flex items-center gap-3" for="deploy-dry-run">
                    <input type="checkbox" id="deploy-dry-run" data-update-dry>
                    <span>Dry-Run aktivieren</span>
                </label>
                <label class="form-label md:w-48" for="deploy-pr">
                    <span>PR-Nummer (optional)</span>
                    <input type="number" id="deploy-pr" data-update-pr min="1" step="1" placeholder="z.&nbsp;B. 42">
                </label>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="btn btn-primary" id="deploy-start" data-update-start>Update starten</button>
                <button type="button" class="btn btn-secondary" id="deploy-cancel" data-update-cancel disabled>Abbrechen</button>
                <span class="badge badge--idle" id="deploy-status" data-update-status>Bereit</span>
            </div>
            <p class="text-sm update-feedback" id="deploy-feedback" data-update-feedback></p>
            <div class="update-log-wrapper">
                <pre class="update-log-viewer is-empty" id="deploy-log" data-update-log aria-live="polite">Noch keine Log-Ausgabe.</pre>
            </div>
        </div>
    </article>
    <article class="card" style="margin-top:1.5rem;">
        <header class="card-header">
            <h2 class="card-title">Direktes Repository-Update</h2>
            <p class="card-subtitle">Lade das aktuellste Paket aus dem konfigurierten Git-Repository und spiele es ohne Umwege ein.</p>
        </header>
        <form method="post" class="flex flex-col gap-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="fetch-repository">
            <p class="text-sm text-slate-300">
                Das Update berücksichtigt automatisch geschützte Bereiche wie <code>uploads/</code> und <code>storage/database.sqlite</code>. Eigene Inhalte bleiben dadurch erhalten.
            </p>
            <button type="submit" class="btn btn-secondary">Repository-Update laden</button>
        </form>
    </article>
    <article class="card" style="margin-top:1.5rem;">
        <header class="card-header">
            <h2 class="card-title">Protokollierte Update-Sitzungen</h2>
            <p class="card-subtitle">Zu jeder Installation wird der entpackte Inhalt im Verzeichnis <code>storage/updates</code> abgelegt.</p>
        </header>
        <?php if (!empty($availablePackages)): ?>
            <ul class="divide-y divide-white/5 text-sm text-slate-300">
                <?php foreach ($availablePackages as $path): ?>
                    <li class="flex items-center justify-between py-3">
                        <span><?= htmlspecialchars(basename($path)) ?></span>
                        <code class="text-xs text-slate-500"><?= htmlspecialchars($path) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-sm text-slate-400">Noch keine Update-Sitzungen gespeichert.</p>
        <?php endif; ?>
    </article>
</section>
<script>
    (() => {
        const root = document.querySelector('[data-update-deploy]');
        if (!root) {
            return;
        }

        const apiBase = root.dataset.api || '/admin/api/update.php';
        const defaultDry = root.dataset.defaultDryRun === 'true';
        const csrfInput = document.getElementById('update-csrf');
        const dryRunToggle = root.querySelector('[data-update-dry]');
        const prInput = root.querySelector('[data-update-pr]');
        const startButton = root.querySelector('[data-update-start]');
        const cancelButton = root.querySelector('[data-update-cancel]');
        const statusBadge = root.querySelector('[data-update-status]');
        const logViewer = root.querySelector('[data-update-log]');
        const feedback = root.querySelector('[data-update-feedback]');

        let csrfToken = csrfInput ? csrfInput.value : '';
        let pollTimeout = null;

        if (dryRunToggle) {
            dryRunToggle.checked = defaultDry;
        }

        const STATUS_META = {
            idle: { label: 'Bereit', className: 'badge badge--idle' },
            running: { label: 'Läuft', className: 'badge badge--running' },
            success: { label: 'Erfolg', className: 'badge badge--success' },
            failed: { label: 'Fehler', className: 'badge badge--failed' },
            canceled: { label: 'Abgebrochen', className: 'badge badge--canceled' },
        };

        const schedulePoll = (delay) => {
            if (pollTimeout) {
                clearTimeout(pollTimeout);
            }
            pollTimeout = window.setTimeout(() => {
                pollStatus().catch(() => {
                    schedulePoll(5000);
                });
            }, delay);
        };

        const handleCsrf = (data) => {
            if (data && typeof data === 'object' && data.csrf) {
                csrfToken = data.csrf;
                if (csrfInput) {
                    csrfInput.value = csrfToken;
                }
            }
        };

        const setFeedback = (message, isError = false) => {
            if (!feedback) {
                return;
            }
            feedback.textContent = message || '';
            feedback.classList.toggle('is-error', Boolean(isError && message));
        };

        const updateControls = (isRunning) => {
            if (startButton) {
                startButton.disabled = isRunning;
            }
            if (cancelButton) {
                cancelButton.disabled = !isRunning;
            }
            if (dryRunToggle) {
                dryRunToggle.disabled = isRunning;
            }
            if (prInput) {
                prInput.disabled = isRunning;
            }
        };

        const applyStatus = (status) => {
            if (!statusBadge) {
                return;
            }
            const state = status && status.state ? status.state : 'idle';
            const meta = STATUS_META[state] || STATUS_META.idle;
            let label = meta.label;
            if (state === 'running' && status && status.pid) {
                label = `${meta.label} (PID ${status.pid})`;
            } else if (state !== 'running' && status && typeof status.exit_code === 'number') {
                label = `${meta.label} (Code ${status.exit_code})`;
            }
            statusBadge.textContent = label;
            statusBadge.className = meta.className;
        };

        const applyLog = (lines) => {
            if (!logViewer) {
                return;
            }
            if (!Array.isArray(lines) || lines.length === 0) {
                logViewer.textContent = 'Noch keine Log-Ausgabe.';
                logViewer.classList.add('is-empty');
                return;
            }
            logViewer.textContent = lines.join('\n');
            logViewer.classList.remove('is-empty');
            logViewer.scrollTop = logViewer.scrollHeight;
        };

        const fetchJson = async (url, options = {}) => {
            const response = await fetch(url, {
                credentials: 'same-origin',
                ...options,
            });
            const data = await response.json().catch(() => ({}));
            handleCsrf(data);
            if (!response.ok) {
                const message = data && data.error ? data.error : 'Aktion fehlgeschlagen.';
                throw new Error(message);
            }
            return data;
        };

        const startUpdate = async () => {
            if (!startButton) {
                return;
            }
            startButton.disabled = true;
            setFeedback('Starte Deploy …');
            const payload = {
                dry_run: dryRunToggle ? dryRunToggle.checked : false,
            };
            if (prInput && prInput.value.trim() !== '') {
                payload.pr = Number.parseInt(prInput.value, 10);
            }
            try {
                const data = await fetchJson(`${apiBase}?action=start`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF': csrfToken,
                    },
                    body: JSON.stringify(payload),
                });
                applyStatus(data.status || {});
                updateControls(true);
                setFeedback('Deploy wurde gestartet.');
                schedulePoll(250);
            } catch (error) {
                updateControls(false);
                setFeedback(error.message, true);
            }
        };

        const cancelUpdate = async () => {
            if (!cancelButton) {
                return;
            }
            cancelButton.disabled = true;
            setFeedback('Breche Deploy ab …');
            try {
                const data = await fetchJson(`${apiBase}?action=cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF': csrfToken,
                    },
                    body: JSON.stringify({}),
                });
                applyStatus(data.status || {});
                updateControls(false);
                setFeedback('Deploy wurde abgebrochen.');
                schedulePoll(1000);
            } catch (error) {
                setFeedback(error.message, true);
                schedulePoll(2000);
            }
        };

        async function pollStatus() {
            try {
                const data = await fetchJson(`${apiBase}?action=status`);
                const running = Boolean(data.running);
                updateControls(running);
                applyStatus(data.status || {});
                if (Array.isArray(data.tail)) {
                    applyLog(data.tail);
                }
                if (running) {
                    setFeedback('Deploy läuft …');
                    schedulePoll(1000);
                } else {
                    if (data.status && data.status.state === 'success') {
                        setFeedback('Deploy erfolgreich abgeschlossen.');
                    } else if (data.status && data.status.state === 'failed') {
                        setFeedback('Deploy fehlgeschlagen.', true);
                    } else if (data.status && data.status.state === 'canceled') {
                        setFeedback('Deploy wurde abgebrochen.');
                    } else {
                        setFeedback('');
                    }
                    schedulePoll(4000);
                }
            } catch (error) {
                setFeedback(error.message, true);
                schedulePoll(5000);
            }
        }

        if (startButton) {
            startButton.addEventListener('click', () => {
                startUpdate().catch((error) => {
                    setFeedback(error.message, true);
                    updateControls(false);
                });
            });
        }

        if (cancelButton) {
            cancelButton.addEventListener('click', () => {
                cancelUpdate().catch((error) => {
                    setFeedback(error.message, true);
                    schedulePoll(2000);
                });
            });
        }

        pollStatus().catch(() => {
            schedulePoll(5000);
        });
    })();
</script>
<?php include __DIR__ . '/../partials/footer.php'; ?>
