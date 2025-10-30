(function () {
    if (typeof document === 'undefined') {
        return;
    }

    const componentCatalogState = {
        items: [],
        categories: [],
        promise: null,
    };
    let activeEditor = null;

    function loadComponentCatalog() {
        if (componentCatalogState.promise) {
            return componentCatalogState.promise;
        }
        componentCatalogState.promise = fetch('assets/nuxtui-catalog.json', { cache: 'no-store' })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then((data) => {
                if (!Array.isArray(data)) {
                    throw new Error('Unerwartetes Format');
                }
                componentCatalogState.items = data;
                componentCatalogState.categories = Array.from(
                    new Set(
                        data
                            .map((item) => (item.category || '').trim())
                            .filter(Boolean),
                    ),
                ).sort((a, b) => a.localeCompare(b, 'de'));
                return componentCatalogState.items;
            })
            .catch((error) => {
                console.error('Komponentenkatalog konnte nicht geladen werden.', error);
                componentCatalogState.items = [];
                componentCatalogState.categories = [];
                throw error;
            });
        return componentCatalogState.promise;
    }

    function sanitizeSnippet(html) {
        if (typeof html !== 'string' || html.trim() === '') {
            return '';
        }
        const template = document.createElement('template');
        template.innerHTML = html;
        const disallowed = new Set(['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'form']);
        const allowed = new Set([
            'class',
            'href',
            'target',
            'rel',
            'id',
            'role',
            'type',
            'value',
            'name',
            'placeholder',
            'required',
            'min',
            'max',
            'minlength',
            'maxlength',
            'rows',
            'cols',
            'title',
            'alt',
            'tabindex',
            'aria-label',
            'aria-hidden',
            'aria-live',
            'aria-expanded',
            'aria-controls',
            'aria-current',
            'data-nui-component',
            'data-state',
            'data-size',
            'data-orientation',
            'style',
            'src',
            'width',
            'height',
            'loading',
            'colspan',
            'rowspan',
            'scope',
        ]);
        const booleanAttributes = new Set(['open', 'selected', 'checked', 'disabled']);
        const nodesToRemove = [];
        const walker = document.createTreeWalker(template.content, NodeFilter.SHOW_ELEMENT, null);
        while (walker.nextNode()) {
            const element = walker.currentNode;
            const tag = element.tagName.toLowerCase();
            if (disallowed.has(tag)) {
                nodesToRemove.push(element);
                continue;
            }
            Array.from(element.attributes).forEach((attribute) => {
                const name = attribute.name.toLowerCase();
                if (name.startsWith('on')) {
                    element.removeAttribute(attribute.name);
                    return;
                }
                if ((name === 'href' || name === 'src') && /^javascript:/i.test(attribute.value)) {
                    element.removeAttribute(attribute.name);
                    return;
                }
                if (name.startsWith('data-') || name.startsWith('aria-')) {
                    return;
                }
                if (booleanAttributes.has(name)) {
                    return;
                }
                if (name === 'style') {
                    const styleValue = attribute.value || '';
                    if (/expression|url\s*\(|@import/i.test(styleValue)) {
                        element.removeAttribute(attribute.name);
                        return;
                    }
                    if (!/^[0-9a-zA-Z\s#:;.,%\-\(\)]+$/.test(styleValue)) {
                        element.removeAttribute(attribute.name);
                        return;
                    }
                    return;
                }
                if (!allowed.has(name)) {
                    element.removeAttribute(attribute.name);
                }
            });
        }
        nodesToRemove.forEach((node) => node.remove());
        return template.innerHTML.trim();
    }

    function performInsertAtCursor(editor, html) {
        if (!editor || typeof html !== 'string' || html.trim() === '') {
            return;
        }
        editor.focus({ preventScroll: false });
        const selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            editor.insertAdjacentHTML('beforeend', html);
            return;
        }
        const range = selection.getRangeAt(0);
        if (!editor.contains(range.commonAncestorContainer)) {
            editor.insertAdjacentHTML('beforeend', html);
            return;
        }
        document.execCommand('insertHTML', false, html);
    }

    function insertAtCursor(html) {
        const sanitized = sanitizeSnippet(html || '');
        performInsertAtCursor(activeEditor, sanitized);
    }

    window.insertAtCursor = insertAtCursor;

    function createButton(label, title, onClick) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'rich-text-btn';
        button.innerHTML = label;
        button.title = title;
        button.addEventListener('click', (event) => {
            event.preventDefault();
            onClick();
        });
        return button;
    }

    function createComponentCard(item, editor, sync) {
        const card = document.createElement('article');
        card.className = 'component-card';
        card.setAttribute('role', 'listitem');

        const header = document.createElement('header');
        header.className = 'component-card__header';

        const badge = document.createElement('span');
        badge.className = 'component-card__badge';
        badge.textContent = 'Nuxt UI';

        const title = document.createElement('h3');
        title.textContent = item.name || 'Komponente';

        const category = document.createElement('span');
        category.className = 'component-card__category';
        category.textContent = item.category || '';

        const description = document.createElement('p');
        description.className = 'component-card__description';
        description.textContent = item.description || '';

        header.appendChild(badge);
        header.appendChild(title);
        if (category.textContent) {
            header.appendChild(category);
        }
        if (description.textContent) {
            header.appendChild(description);
        }

        const preview = document.createElement('div');
        preview.className = 'component-card__preview';
        const sanitized = sanitizeSnippet(item.snippet || '');
        if (sanitized) {
            preview.innerHTML = sanitized;
        } else {
            preview.innerHTML = '<p class="component-card__empty">Keine Vorschau verfügbar.</p>';
        }

        const actions = document.createElement('div');
        actions.className = 'component-card__actions';

        const insertButton = document.createElement('button');
        insertButton.type = 'button';
        insertButton.className = 'btn btn-primary';
        insertButton.textContent = 'Einfügen';
        const hasSnippet = !!sanitized;
        insertButton.disabled = !hasSnippet;
        if (!hasSnippet) {
            insertButton.setAttribute('aria-disabled', 'true');
            insertButton.title = 'Für diese Komponente steht kein Snippet zur Verfügung.';
        } else {
            insertButton.removeAttribute('aria-disabled');
            insertButton.removeAttribute('title');
        }
        insertButton.addEventListener('click', () => {
            if (!hasSnippet) {
                return;
            }
            performInsertAtCursor(editor, sanitized);
            sync();
        });

        const codeButton = document.createElement('button');
        codeButton.type = 'button';
        codeButton.className = 'btn btn-tertiary';
        codeButton.textContent = 'Code ansehen';

        const codeBlock = document.createElement('pre');
        codeBlock.className = 'component-card__code';
        codeBlock.hidden = true;
        codeBlock.textContent = (item.snippet || '').trim();

        codeButton.addEventListener('click', () => {
            const visible = !codeBlock.hidden;
            codeBlock.hidden = visible;
            codeButton.textContent = visible ? 'Code ansehen' : 'Code verbergen';
        });

        actions.appendChild(insertButton);
        actions.appendChild(codeButton);

        card.appendChild(header);
        card.appendChild(preview);
        card.appendChild(actions);
        card.appendChild(codeBlock);

        return card;
    }

    function setupComponentGallery(container, editor, sync) {
        container.innerHTML = '';
        const gallery = document.createElement('div');
        gallery.className = 'component-gallery';

        const searchWrapper = document.createElement('div');
        searchWrapper.className = 'component-gallery__search';
        const searchInput = document.createElement('input');
        searchInput.type = 'search';
        searchInput.placeholder = 'Komponenten durchsuchen …';
        searchWrapper.appendChild(searchInput);

        const chips = document.createElement('div');
        chips.className = 'component-gallery__chips';

        const list = document.createElement('div');
        list.className = 'component-gallery__list';
        list.setAttribute('role', 'list');
        list.innerHTML = '<p class="component-gallery__loading">Komponenten werden geladen …</p>';

        gallery.appendChild(searchWrapper);
        gallery.appendChild(chips);
        gallery.appendChild(list);
        container.appendChild(gallery);

        const state = {
            query: '',
            category: 'all',
        };

        function renderChips() {
            chips.innerHTML = '';
            const categories = ['all', ...componentCatalogState.categories];
            categories.forEach((category) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `component-chip${state.category === category ? ' is-active' : ''}`;
                button.textContent = category === 'all' ? 'Alle' : category;
                button.addEventListener('click', () => {
                    state.category = category;
                    renderChips();
                    renderList();
                });
                chips.appendChild(button);
            });
        }

        function getFilteredItems() {
            const query = state.query.trim().toLowerCase();
            return componentCatalogState.items.filter((item) => {
                if (state.category !== 'all' && (item.category || '') !== state.category) {
                    return false;
                }
                if (!query) {
                    return true;
                }
                const haystack = [item.name, item.category, item.description]
                    .map((value) => (value || '').toLowerCase());
                return haystack.some((value) => value.includes(query));
            });
        }

        function renderList() {
            const items = getFilteredItems();
            list.innerHTML = '';
            if (!items.length) {
                list.innerHTML = '<p class="component-gallery__empty">Keine passenden Komponenten gefunden.</p>';
                return;
            }
            items.forEach((item) => {
                list.appendChild(createComponentCard(item, editor, sync));
            });
        }

        searchInput.addEventListener('input', () => {
            state.query = searchInput.value;
            renderList();
        });

        loadComponentCatalog()
            .then(() => {
                if (!componentCatalogState.items.length) {
                    list.innerHTML = '<p class="component-gallery__empty">Keine Komponenten im Katalog verfügbar.</p>';
                    return;
                }
                renderChips();
                renderList();
            })
            .catch(() => {
                list.innerHTML = '<p class="component-gallery__error">Katalog konnte nicht geladen werden.</p>';
            });
    }

    function wrapTextarea(textarea) {
        if (textarea.dataset.richTextified) {
            return;
        }
        textarea.dataset.richTextified = 'true';

        const shell = document.createElement('div');
        shell.className = 'rich-text-shell';

        const main = document.createElement('div');
        main.className = 'rich-text-main';

        const note = document.createElement('p');
        note.className = 'rich-text-note';
        note.textContent = 'Komponente auswählen, Vorschau prüfen, Einfügen klicken.';

        const wrapper = document.createElement('div');
        wrapper.className = 'rich-text-wrapper';

        const toolbar = document.createElement('div');
        toolbar.className = 'rich-text-toolbar';

        const editor = document.createElement('div');
        editor.className = 'rich-text-editor';
        editor.contentEditable = 'true';
        editor.innerHTML = textarea.value;

        const commands = [
            { label: '<strong>B</strong>', title: 'Fett', action: () => document.execCommand('bold', false) },
            { label: '<em>I</em>', title: 'Kursiv', action: () => document.execCommand('italic', false) },
            { label: '<u>U</u>', title: 'Unterstrichen', action: () => document.execCommand('underline', false) },
            { label: '&#8226;', title: 'Aufzählung', action: () => document.execCommand('insertUnorderedList', false) },
            { label: '&#35;', title: 'Nummerierung', action: () => document.execCommand('insertOrderedList', false) },
            { label: '&#128279;', title: 'Link einfügen', action: () => {
                const url = window.prompt('Link-Adresse (inkl. https://)');
                if (url) {
                    document.execCommand('createLink', false, url);
                }
            } },
            { label: '&#9003;', title: 'Formatierung löschen', action: () => document.execCommand('removeFormat', false) }
        ];

        commands.forEach((command) => toolbar.appendChild(createButton(command.label, command.title, command.action)));

        const sidebar = document.createElement('aside');
        sidebar.className = 'rich-text-components';

        const sidebarTitle = document.createElement('h2');
        sidebarTitle.className = 'rich-text-components__title';
        sidebarTitle.textContent = 'Komponenten';
        sidebar.appendChild(sidebarTitle);

        const galleryContainer = document.createElement('div');
        galleryContainer.className = 'component-gallery';
        galleryContainer.innerHTML = '<p class="component-gallery__loading">Komponenten werden geladen …</p>';
        sidebar.appendChild(galleryContainer);

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(shell, textarea);

        wrapper.appendChild(toolbar);
        wrapper.appendChild(editor);
        wrapper.appendChild(textarea);

        main.appendChild(note);
        main.appendChild(wrapper);
        shell.appendChild(main);
        shell.appendChild(sidebar);

        const sync = () => {
            textarea.value = editor.innerHTML.trim();
        };

        editor.addEventListener('input', sync);
        editor.addEventListener('focus', () => {
            activeEditor = editor;
        });
        editor.addEventListener('blur', () => {
            sync();
            if (activeEditor === editor) {
                activeEditor = null;
            }
        });

        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', sync);
        }

        setupComponentGallery(galleryContainer, editor, sync);
    }

    function normalizeGeneValue(value) {
        return value
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function initAnimalGenePicker(root) {
        let geneData = [];
        let selectedData = {};
        try {
            geneData = JSON.parse(root.getAttribute('data-gene-json') || '[]');
            selectedData = JSON.parse(root.getAttribute('data-selected-json') || '{}');
        } catch (error) {
            console.error('Genetikdaten konnten nicht geladen werden.', error);
            return;
        }
        if (!Array.isArray(geneData) || geneData.length === 0) {
            return;
        }

        const inputPrefix = root.getAttribute('data-input-prefix') || 'gene_states';
        const genesBySlug = new Map();
        geneData.forEach((gene) => {
            genesBySlug.set(gene.slug, gene);
        });

        const selections = new Map();
        Object.entries(selectedData || {}).forEach(([slug, state]) => {
            if (state) {
                selections.set(slug, state);
            }
        });

        const searchIndex = [];
        geneData.forEach((gene) => {
            (gene.states || []).forEach((state) => {
                const tokens = new Set();
                tokens.add(gene.name || '');
                tokens.add(state.label || '');
                if (state.key === 'heterozygous') {
                    tokens.add(`het ${gene.name}`);
                    tokens.add(`träger ${gene.name}`);
                    tokens.add(`traeger ${gene.name}`);
                } else {
                    tokens.add(`visual ${gene.name}`);
                    tokens.add(`super ${gene.name}`);
                }
                searchIndex.push({
                    geneSlug: gene.slug,
                    stateKey: state.key,
                    stateLabel: state.label,
                    geneName: gene.name,
                    tokens: Array.from(tokens)
                        .filter(Boolean)
                        .map((token) => normalizeGeneValue(token)),
                });
            });
        });

        const container = document.createElement('div');
        container.className = 'admin-gene-picker';
        const tags = document.createElement('div');
        tags.className = 'admin-gene-picker__tags';
        const inputRow = document.createElement('div');
        inputRow.className = 'admin-gene-picker__input';
        const input = document.createElement('input');
        input.type = 'text';
        input.placeholder = 'Gen oder Status wählen …';
        const clearButton = document.createElement('button');
        clearButton.type = 'button';
        clearButton.className = 'admin-gene-picker__clear';
        clearButton.textContent = 'Zurücksetzen';
        const suggestions = document.createElement('div');
        suggestions.className = 'admin-gene-picker__suggestions';
        suggestions.hidden = true;
        const hiddenInputs = document.createElement('div');
        hiddenInputs.style.display = 'none';

        inputRow.appendChild(input);
        inputRow.appendChild(clearButton);
        container.appendChild(tags);
        container.appendChild(inputRow);
        container.appendChild(suggestions);
        container.appendChild(hiddenInputs);

        root.innerHTML = '';
        root.appendChild(container);

        let isEnabled = !root.closest('[hidden]');

        function updateHiddenInputs() {
            hiddenInputs.innerHTML = '';
            if (!isEnabled) {
                return;
            }
            selections.forEach((stateKey, slug) => {
                const inputHidden = document.createElement('input');
                inputHidden.type = 'hidden';
                inputHidden.name = `${inputPrefix}[${slug}]`;
                inputHidden.value = stateKey;
                hiddenInputs.appendChild(inputHidden);
            });
        }

        function renderTags() {
            tags.innerHTML = '';
            selections.forEach((stateKey, slug) => {
                const gene = genesBySlug.get(slug);
                if (!gene) {
                    return;
                }
                const state = (gene.states || []).find((entry) => entry.key === stateKey);
                if (!state) {
                    return;
                }
                const tag = document.createElement('span');
                tag.className = 'gene-tag';
                const label = document.createElement('span');
                const geneNorm = normalizeGeneValue(gene.name || '');
                const stateNorm = normalizeGeneValue(state.label || '');
                if (geneNorm && geneNorm === stateNorm) {
                    label.textContent = state.label;
                } else {
                    label.textContent = `${gene.name}: ${state.label}`;
                }
                const remove = document.createElement('button');
                remove.type = 'button';
                remove.className = 'gene-tag__remove';
                remove.setAttribute('aria-label', `${gene.name} entfernen`);
                remove.textContent = '×';
                remove.addEventListener('click', () => {
                    selections.delete(slug);
                    renderTags();
                });
                tag.appendChild(label);
                tag.appendChild(remove);
                tags.appendChild(tag);
            });
            if (!tags.children.length) {
                const hint = document.createElement('p');
                hint.className = 'text-muted';
                hint.textContent = 'Keine Gene ausgewählt.';
                tags.appendChild(hint);
            }
            updateHiddenInputs();
        }

        function renderSuggestions(query) {
            const normalized = normalizeGeneValue(query || '');
            suggestions.innerHTML = '';
            if (!isEnabled) {
                suggestions.hidden = true;
                return;
            }
            const matches = searchIndex.filter((entry) => {
                if (selections.get(entry.geneSlug) === entry.stateKey) {
                    return false;
                }
                if (!normalized) {
                    return true;
                }
                return entry.tokens.some((token) => token.includes(normalized));
            });
            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'admin-gene-picker__empty';
                empty.textContent = normalized ? 'Keine passenden Einträge.' : 'Alle Varianten ausgewählt.';
                suggestions.appendChild(empty);
                suggestions.hidden = false;
                return;
            }
            matches.slice(0, 8).forEach((entry) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.innerHTML = `<strong>${entry.stateLabel}</strong><span>${entry.geneName}</span>`;
                button.addEventListener('click', () => {
                    selections.set(entry.geneSlug, entry.stateKey);
                    input.value = '';
                    suggestions.hidden = true;
                    suggestions.innerHTML = '';
                    renderTags();
                });
                suggestions.appendChild(button);
            });
            suggestions.hidden = false;
        }

        input.addEventListener('input', () => {
            const value = input.value.trim();
            if (!value) {
                renderSuggestions('');
            } else {
                renderSuggestions(value);
            }
        });

        input.addEventListener('focus', () => {
            if (!input.value.trim()) {
                renderSuggestions('');
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                const value = input.value.trim();
                const normalized = normalizeGeneValue(value);
                if (!normalized) {
                    suggestions.hidden = true;
                    return;
                }
                const match = searchIndex.find((entry) => {
                    if (selections.get(entry.geneSlug) === entry.stateKey) {
                        return false;
                    }
                    return entry.tokens.some((token) => token.includes(normalized));
                });
                if (match) {
                    selections.set(match.geneSlug, match.stateKey);
                    input.value = '';
                    suggestions.hidden = true;
                    suggestions.innerHTML = '';
                    renderTags();
                }
            }
        });

        clearButton.addEventListener('click', () => {
            if (!isEnabled) {
                return;
            }
            selections.clear();
            input.value = '';
            suggestions.hidden = true;
            suggestions.innerHTML = '';
            renderTags();
        });

        document.addEventListener('click', (event) => {
            if (!container.contains(event.target)) {
                suggestions.hidden = true;
            }
        });

        root.genePicker = {
            setEnabled(enabled) {
                isEnabled = !!enabled;
                input.disabled = !isEnabled;
                clearButton.disabled = !isEnabled;
                if (!isEnabled) {
                    suggestions.hidden = true;
                }
                updateHiddenInputs();
            },
        };

        input.disabled = !isEnabled;
        clearButton.disabled = !isEnabled;
        renderTags();
    }

    function updateLayoutInput(list) {
        const selector = list.getAttribute('data-sortable-input');
        if (!selector) {
            return;
        }
        const input = document.querySelector(selector) || list.querySelector(selector);
        if (!input) {
            return;
        }
        const layout = Array.from(list.querySelectorAll('.sortable-item')).map((item) => {
            const checkbox = item.querySelector('[data-section-enabled]');
            return {
                key: item.getAttribute('data-section-key'),
                enabled: checkbox ? checkbox.checked : true,
            };
        });
        input.value = JSON.stringify(layout);
    }

    function rebuildLayout(list, layout) {
        const itemsByKey = {};
        list.querySelectorAll('.sortable-item').forEach((item) => {
            itemsByKey[item.getAttribute('data-section-key')] = item;
        });
        layout.forEach((entry) => {
            const item = itemsByKey[entry.key];
            if (!item) {
                return;
            }
            const checkbox = item.querySelector('[data-section-enabled]');
            if (checkbox) {
                checkbox.checked = !!entry.enabled;
            }
            list.appendChild(item);
        });
        updateLayoutInput(list);
    }

    function initSortables() {
        document.querySelectorAll('[data-sortable-list]').forEach((list) => {
            let draggedItem = null;

            list.addEventListener('dragstart', (event) => {
                const item = event.target.closest('.sortable-item');
                if (!item) {
                    return;
                }
                draggedItem = item;
                item.classList.add('dragging');
                event.dataTransfer.effectAllowed = 'move';
            });

            list.addEventListener('dragover', (event) => {
                if (!draggedItem) {
                    return;
                }
                event.preventDefault();
                const target = event.target.closest('.sortable-item');
                if (!target || target === draggedItem) {
                    return;
                }
                const rect = target.getBoundingClientRect();
                const isAfter = (event.clientY - rect.top) > rect.height / 2;
                list.insertBefore(draggedItem, isAfter ? target.nextSibling : target);
            });

            list.addEventListener('dragend', () => {
                if (draggedItem) {
                    draggedItem.classList.remove('dragging');
                    draggedItem = null;
                    updateLayoutInput(list);
                }
            });

            list.querySelectorAll('[data-section-enabled]').forEach((checkbox) => {
                checkbox.addEventListener('change', () => updateLayoutInput(list));
            });

            const form = list.closest('form');
            if (form) {
                const resetButton = form.querySelector('[data-reset-layout]');
                if (resetButton) {
                    resetButton.addEventListener('click', (event) => {
                        event.preventDefault();
                        const hidden = form.querySelector('[data-sortable-input]');
                        if (!hidden) {
                            return;
                        }
                        try {
                            const defaults = JSON.parse(hidden.defaultValue || '[]');
                            rebuildLayout(list, defaults);
                        } catch (error) {
                            console.error('Layout konnte nicht zurückgesetzt werden', error);
                        }
                    });
                }
            }

            updateLayoutInput(list);
        });
    }

    function initMenuManager(root) {
        if (!root) {
            return;
        }
        const endpoint = root.getAttribute('data-menu-endpoint');
        if (!endpoint) {
            return;
        }
        let csrfToken = root.getAttribute('data-menu-csrf') || '';
        const modal = document.querySelector('[data-menu-modal]');
        if (!modal) {
            return;
        }
        const form = modal.querySelector('[data-menu-form]');
        const errorBox = modal.querySelector('[data-menu-error]');
        const title = modal.querySelector('#menu-modal-title');
        const closeButtons = modal.querySelectorAll('[data-menu-close]');
        const submitButton = modal.querySelector('[data-menu-submit]');
        const idField = form.querySelector('[data-menu-field="id"]');
        const labelField = form.querySelector('[data-menu-field="label"]');
        const pathField = form.querySelector('[data-menu-field="path"]');
        const iconField = form.querySelector('[data-menu-field="icon"]');
        const targetField = form.querySelector('[data-menu-field="target"]');
        const locationField = form.querySelector('[data-menu-field="location"]');
        const visibleField = form.querySelector('[data-menu-field="visible"]');

        const dragState = {
            row: null,
            location: null,
        };

        function updateCsrf(token) {
            if (token) {
                csrfToken = token;
                root.setAttribute('data-menu-csrf', token);
            }
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            })[char]);
        }

        function request(action, payload) {
            const options = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            };
            if (csrfToken) {
                options.headers['X-CSRF'] = csrfToken;
            }
            return fetch(`${endpoint}?action=${encodeURIComponent(action)}`, options).then(async (response) => {
                let data = {};
                try {
                    data = await response.json();
                } catch (error) {
                    data = {};
                }
                updateCsrf(data.csrf);
                if (!response.ok || data.ok === false) {
                    const error = new Error(data.error || 'Aktion fehlgeschlagen.');
                    error.details = data.details || null;
                    error.status = response.status;
                    throw error;
                }
                return data;
            });
        }

        function refreshEmptyState(location) {
            const tbody = root.querySelector(`tbody[data-menu-location="${location}"]`);
            if (!tbody) {
                return;
            }
            const emptyRow = tbody.querySelector(`[data-menu-empty="${location}"]`);
            if (!emptyRow) {
                return;
            }
            const hasRows = Boolean(tbody.querySelector('[data-menu-row]'));
            emptyRow.hidden = hasRows;
        }

        function buildRow(item) {
            const row = document.createElement('tr');
            row.className = 'menu-manager__row';
            row.setAttribute('data-menu-row', '');
            row.dataset.id = String(item.id);
            row.dataset.location = item.location || 'frontend';
            row.dataset.label = item.label || '';
            row.dataset.path = item.path || '';
            row.dataset.icon = item.icon || '';
            row.dataset.target = item.target || '_self';
            row.dataset.visible = item.visible ? '1' : '0';
            row.dataset.position = String(item.position ?? 0);
            row.innerHTML = `
                <td class="menu-manager__handle" data-menu-handle draggable="true" title="Zum Sortieren ziehen">⋮⋮</td>
                <td>
                    <strong>${escapeHtml(item.label)}</strong>
                    <div class="menu-manager__path">${escapeHtml(item.path)}</div>
                </td>
                <td>${escapeHtml(item.icon || '')}</td>
                <td>${escapeHtml(item.target || '_self')}</td>
                <td>${item.visible ? '<span class="badge">sichtbar</span>' : '<span class="badge badge-muted">ausgeblendet</span>'}</td>
                <td class="menu-manager__actions">
                    <button type="button" class="btn btn-tertiary" data-menu-toggle>${item.visible ? 'Verbergen' : 'Anzeigen'}</button>
                    <button type="button" class="btn btn-secondary" data-menu-edit>Bearbeiten</button>
                    <button type="button" class="btn btn-secondary" data-menu-delete>Entfernen</button>
                </td>
            `;
            return row;
        }

        function updateRow(row, item) {
            row.dataset.label = item.label || '';
            row.dataset.path = item.path || '';
            row.dataset.icon = item.icon || '';
            row.dataset.target = item.target || '_self';
            row.dataset.visible = item.visible ? '1' : '0';
            row.dataset.position = String(item.position ?? row.dataset.position || 0);
            row.dataset.location = item.location || row.dataset.location || 'frontend';
            const cells = row.querySelectorAll('td');
            const labelCell = cells[1];
            const iconCell = cells[2];
            const targetCell = cells[3];
            const statusCell = cells[4];
            if (labelCell) {
                const strong = labelCell.querySelector('strong');
                const path = labelCell.querySelector('.menu-manager__path');
                if (strong) {
                    strong.textContent = item.label || '';
                }
                if (path) {
                    path.textContent = item.path || '';
                }
            }
            if (iconCell) {
                iconCell.textContent = item.icon || '';
            }
            if (targetCell) {
                targetCell.textContent = item.target || '_self';
            }
            if (statusCell) {
                statusCell.innerHTML = item.visible ? '<span class="badge">sichtbar</span>' : '<span class="badge badge-muted">ausgeblendet</span>';
            }
            const toggleButton = row.querySelector('[data-menu-toggle]');
            if (toggleButton) {
                toggleButton.textContent = item.visible ? 'Verbergen' : 'Anzeigen';
            }
        }

        function bindRowEvents(row) {
            const toggleButton = row.querySelector('[data-menu-toggle]');
            const editButton = row.querySelector('[data-menu-edit]');
            const deleteButton = row.querySelector('[data-menu-delete]');
            const handle = row.querySelector('[data-menu-handle]');

            if (handle) {
                handle.addEventListener('dragstart', (event) => {
                    const targetRow = event.target.closest('[data-menu-row]');
                    if (!targetRow) {
                        return;
                    }
                    dragState.row = targetRow;
                    dragState.location = targetRow.dataset.location;
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', targetRow.dataset.id || '');
                    targetRow.classList.add('is-dragging');
                });
                handle.addEventListener('dragend', () => {
                    if (dragState.row) {
                        dragState.row.classList.remove('is-dragging');
                    }
                    dragState.row = null;
                    dragState.location = null;
                });
            }

            row.addEventListener('dragover', (event) => {
                if (!dragState.row || dragState.location !== row.dataset.location) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                const targetRect = row.getBoundingClientRect();
                const after = event.clientY > targetRect.top + targetRect.height / 2;
                const parent = row.parentElement;
                if (!parent) {
                    return;
                }
                if (after) {
                    parent.insertBefore(dragState.row, row.nextSibling);
                } else {
                    parent.insertBefore(dragState.row, row);
                }
            });

            row.addEventListener('drop', (event) => {
                if (!dragState.row || dragState.location !== row.dataset.location) {
                    return;
                }
                event.preventDefault();
                event.stopPropagation();
                finalizeReorder(dragState.location);
                dragState.row.classList.remove('is-dragging');
                dragState.row = null;
                dragState.location = null;
            });

            if (toggleButton) {
                toggleButton.addEventListener('click', () => {
                    const id = Number(row.dataset.id);
                    if (!id) {
                        return;
                    }
                    request('toggle', { id })
                        .then((response) => {
                            if (response.data) {
                                updateRow(row, response.data);
                            }
                        })
                        .catch((error) => {
                            window.alert(error.message || 'Status konnte nicht aktualisiert werden.');
                        });
                });
            }

            if (editButton) {
                editButton.addEventListener('click', () => {
                    const data = {
                        id: Number(row.dataset.id),
                        label: row.dataset.label || '',
                        path: row.dataset.path || '',
                        icon: row.dataset.icon || '',
                        target: row.dataset.target || '_self',
                        visible: Number(row.dataset.visible) === 1 ? 1 : 0,
                        location: row.dataset.location || 'frontend',
                    };
                    openModal(data);
                });
            }

            if (deleteButton) {
                deleteButton.addEventListener('click', () => {
                    const id = Number(row.dataset.id);
                    if (!id) {
                        return;
                    }
                    if (!window.confirm('Eintrag wirklich löschen?')) {
                        return;
                    }
                    request('delete', { id })
                        .then(() => {
                            const location = row.dataset.location || 'frontend';
                            row.remove();
                            refreshEmptyState(location);
                        })
                        .catch((error) => {
                            window.alert(error.message || 'Eintrag konnte nicht gelöscht werden.');
                        });
                });
            }
        }

        function bindSectionDnD(section) {
            const location = section.getAttribute('data-menu-location');
            if (!location) {
                return;
            }
            section.addEventListener('dragover', (event) => {
                if (!dragState.row || dragState.location !== location) {
                    return;
                }
                event.preventDefault();
                const targetRow = event.target.closest('[data-menu-row]');
                if (targetRow) {
                    return;
                }
                const headerRow = event.target.closest('.menu-manager__section');
                if (headerRow) {
                    const firstRow = section.querySelector('[data-menu-row]');
                    if (firstRow && firstRow !== dragState.row) {
                        section.insertBefore(dragState.row, firstRow);
                    }
                    return;
                }
                const rows = section.querySelectorAll('[data-menu-row]');
                const lastRow = rows.length ? rows[rows.length - 1] : null;
                if (lastRow && lastRow !== dragState.row) {
                    section.appendChild(dragState.row);
                } else if (!lastRow) {
                    section.appendChild(dragState.row);
                }
            });
            section.addEventListener('drop', (event) => {
                if (!dragState.row || dragState.location !== location) {
                    return;
                }
                event.preventDefault();
                finalizeReorder(location);
                dragState.row.classList.remove('is-dragging');
                dragState.row = null;
                dragState.location = null;
            });
        }

        function finalizeReorder(location) {
            const rows = Array.from(root.querySelectorAll(`[data-menu-row][data-location="${location}"]`));
            const payload = rows.map((row, index) => ({
                id: Number(row.dataset.id),
                position: index,
            }));
            request('reorder', payload)
                .then(() => {
                    rows.forEach((row, index) => {
                        row.dataset.position = String(index);
                    });
                })
                .catch((error) => {
                    window.alert(error.message || 'Sortierung konnte nicht gespeichert werden.');
                });
        }

        function openModal(item) {
            form.reset();
            errorBox.hidden = true;
            errorBox.textContent = '';
            if (item && item.id) {
                title.textContent = 'Menüeintrag bearbeiten';
                idField.value = String(item.id);
                labelField.value = item.label || '';
                pathField.value = item.path || '';
                iconField.value = item.icon || '';
                targetField.value = item.target || '_self';
                locationField.value = item.location || 'frontend';
                visibleField.checked = Number(item.visible) === 1;
            } else {
                title.textContent = 'Neuer Menüeintrag';
                idField.value = '';
                labelField.value = '';
                pathField.value = '';
                iconField.value = '';
                targetField.value = '_self';
                locationField.value = 'frontend';
                visibleField.checked = true;
            }
            modal.hidden = false;
            document.body.classList.add('menu-manager--modal-open');
            window.setTimeout(() => {
                labelField.focus();
            }, 0);
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('menu-manager--modal-open');
        }

        closeButtons.forEach((button) => {
            button.addEventListener('click', () => {
                closeModal();
            });
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            errorBox.hidden = true;
            errorBox.textContent = '';
            const payload = {
                label: labelField.value.trim(),
                path: pathField.value.trim(),
                icon: iconField.value.trim(),
                target: targetField.value,
                location: locationField.value,
                visible: visibleField.checked ? 1 : 0,
            };
            const isUpdate = Boolean(idField.value);
            if (isUpdate) {
                payload.id = Number(idField.value);
            }
            submitButton.disabled = true;
            request(isUpdate ? 'update' : 'create', payload)
                .then((response) => {
                    const item = response.data;
                    if (!item) {
                        throw new Error('Antwort ohne Daten.');
                    }
                    const location = item.location || 'frontend';
                    const existingRow = root.querySelector(`[data-menu-row][data-id="${item.id}"]`);
                    if (existingRow) {
                        const previousLocation = existingRow.dataset.location || 'frontend';
                        if (previousLocation !== location) {
                            const replacement = buildRow(item);
                            bindRowEvents(replacement);
                            const targetBody = root.querySelector(`tbody[data-menu-location="${location}"]`);
                            if (targetBody) {
                                targetBody.appendChild(replacement);
                            }
                            existingRow.remove();
                            refreshEmptyState(previousLocation);
                        } else {
                            updateRow(existingRow, item);
                        }
                        refreshEmptyState(location);
                    } else {
                        const newRow = buildRow(item);
                        bindRowEvents(newRow);
                        const targetBody = root.querySelector(`tbody[data-menu-location="${location}"]`);
                        if (targetBody) {
                            targetBody.appendChild(newRow);
                        }
                        refreshEmptyState(location);
                    }
                    closeModal();
                })
                .catch((error) => {
                    const details = error.details && typeof error.details === 'object' ? Object.values(error.details).join(' ') : '';
                    errorBox.textContent = details || error.message || 'Aktion fehlgeschlagen.';
                    errorBox.hidden = false;
                })
                .finally(() => {
                    submitButton.disabled = false;
                });
        });

        const newButton = root.querySelector('[data-menu-new]');
        if (newButton) {
            newButton.addEventListener('click', () => {
                openModal(null);
            });
        }

        Array.from(root.querySelectorAll('[data-menu-row]')).forEach((row) => {
            bindRowEvents(row);
        });

        Array.from(root.querySelectorAll('tbody[data-menu-location]')).forEach((section) => {
            bindSectionDnD(section);
        });

        refreshEmptyState('frontend');
        refreshEmptyState('admin');
    }

    // ==========================================
    // Component Editor Enhancement
    // ==========================================

    function createComponentEditModal() {
        const existingModal = document.getElementById('component-edit-modal');
        if (existingModal) {
            return existingModal;
        }

        const modal = document.createElement('div');
        modal.id = 'component-edit-modal';
        modal.className = 'nui-modal';
        modal.hidden = true;
        modal.innerHTML = `
            <div class="nui-modal__dialog space-y-4" style="max-width: 600px;">
                <header class="flex items-center justify-between">
                    <h3 class="nui-heading text-xl">Komponente bearbeiten</h3>
                    <button type="button" class="nui-button nui-button--ghost" data-close-edit-modal aria-label="Schließen">×</button>
                </header>
                <div id="component-edit-content" class="space-y-3">
                    <!-- Dynamic content -->
                </div>
                <footer class="flex justify-end gap-2">
                    <button type="button" class="nui-button nui-button--ghost" data-close-edit-modal>Abbrechen</button>
                    <button type="button" class="nui-button nui-button--primary" data-save-component>Speichern</button>
                </footer>
            </div>
        `;

        document.body.appendChild(modal);

        // Close handlers
        modal.querySelectorAll('[data-close-edit-modal]').forEach((btn) => {
            btn.addEventListener('click', () => {
                modal.hidden = true;
                document.body.classList.remove('component-editor--modal-open');
            });
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.hidden = true;
                document.body.classList.remove('component-editor--modal-open');
            }
        });

        return modal;
    }

    function findComponentDefinition(componentName) {
        return componentCatalogState.items.find((item) => item.name === componentName);
    }

    function openComponentEditor(element, editor, sync) {
        const componentName = element.getAttribute('data-nui-component');
        if (!componentName) {
            return;
        }

        const definition = findComponentDefinition(componentName);
        if (!definition || !definition.editable || !definition.params) {
            window.alert('Diese Komponente kann nicht bearbeitet werden.');
            return;
        }

        const modal = createComponentEditModal();
        const content = modal.querySelector('#component-edit-content');
        const saveButton = modal.querySelector('[data-save-component]');

        // Build form
        content.innerHTML = '';
        const form = document.createElement('form');
        form.className = 'space-y-3';

        Object.entries(definition.params).forEach(([key, value]) => {
            const fieldWrapper = document.createElement('label');
            fieldWrapper.className = 'nui-field';

            const label = document.createElement('span');
            label.className = 'nui-field__label';
            label.textContent = key.charAt(0).toUpperCase() + key.slice(1);

            fieldWrapper.appendChild(label);

            if (Array.isArray(value)) {
                // Dropdown for array options
                const select = document.createElement('select');
                select.className = 'nui-select';
                select.name = key;
                value.forEach((option) => {
                    const opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = option;
                    select.appendChild(opt);
                });

                // Try to find current value
                const currentAttr = element.getAttribute(`data-${key}`);
                if (currentAttr) {
                    select.value = currentAttr;
                } else {
                    const editableEl = element.querySelector(`[data-editable="${key}"]`);
                    if (editableEl) {
                        select.value = editableEl.textContent.trim();
                    }
                }

                fieldWrapper.appendChild(select);
            } else {
                // Text input for string values
                const input = document.createElement('input');
                input.className = 'nui-input';
                input.type = 'text';
                input.name = key;

                // Try to find current value
                const editableEl = element.querySelector(`[data-editable="${key}"]`);
                if (editableEl) {
                    input.value = editableEl.textContent.trim();
                } else {
                    input.value = value || '';
                }

                fieldWrapper.appendChild(input);
            }

            form.appendChild(fieldWrapper);
        });

        content.appendChild(form);

        // Save handler
        saveButton.onclick = () => {
            const formData = new FormData(form);
            const updates = {};
            formData.forEach((val, key) => {
                updates[key] = val;
            });

            // Update element
            Object.entries(updates).forEach(([key, value]) => {
                const editableEl = element.querySelector(`[data-editable="${key}"]`);
                if (editableEl) {
                    editableEl.textContent = value;
                }

                // Update data attributes for variants
                if (key === 'variant') {
                    element.setAttribute(`data-${key}`, value);
                    // Update class for alerts/toasts
                    if (componentName === 'UAlert' || componentName === 'UToast') {
                        element.className = element.className.replace(/nui-(alert|toast)--(info|success|warning|error)/, `nui-$1--${value}`);
                    }
                }

                // Special handling for progress bar
                if (key === 'percentage' && componentName === 'UProgress') {
                    const bar = element.querySelector('.nui-progress__bar');
                    if (bar) {
                        bar.style.width = value + '%';
                    }
                }
            });

            sync();
            modal.hidden = true;
            document.body.classList.remove('component-editor--modal-open');
        };

        modal.hidden = false;
        document.body.classList.add('component-editor--modal-open');
    }

    function enhanceEditorWithComponentHighlighting(editor, sync) {
        if (editor.dataset.componentEnhanced) {
            return;
        }
        editor.dataset.componentEnhanced = 'true';

        // Add hover effect for editable components
        editor.addEventListener('mouseover', (e) => {
            const component = e.target.closest('[data-nui-component]');
            if (component && component.getAttribute('data-editable') !== 'false') {
                const definition = findComponentDefinition(component.getAttribute('data-nui-component'));
                if (definition && definition.editable) {
                    component.classList.add('component-editable-hover');
                }
            }
        });

        editor.addEventListener('mouseout', (e) => {
            const component = e.target.closest('[data-nui-component]');
            if (component) {
                component.classList.remove('component-editable-hover');
            }
        });

        // Add click handler for editing
        editor.addEventListener('dblclick', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const component = e.target.closest('[data-nui-component]');
            if (component) {
                const definition = findComponentDefinition(component.getAttribute('data-nui-component'));
                if (definition && definition.editable) {
                    openComponentEditor(component, editor, sync);
                }
            }
        });

        // Add edit button overlay
        let editButton = null;

        editor.addEventListener('click', (e) => {
            const component = e.target.closest('[data-nui-component]');

            // Remove old button
            if (editButton && editButton.parentNode) {
                editButton.remove();
                editButton = null;
            }

            if (component && component.getAttribute('data-editable') !== 'false') {
                const definition = findComponentDefinition(component.getAttribute('data-nui-component'));
                if (definition && definition.editable) {
                    e.stopPropagation();

                    editButton = document.createElement('button');
                    editButton.type = 'button';
                    editButton.className = 'component-edit-button';
                    editButton.textContent = '✎ Bearbeiten';
                    editButton.title = 'Doppelklick auf die Komponente zum Bearbeiten';

                    editButton.addEventListener('click', (ev) => {
                        ev.preventDefault();
                        ev.stopPropagation();
                        openComponentEditor(component, editor, sync);
                        editButton.remove();
                        editButton = null;
                    });

                    component.style.position = 'relative';
                    component.appendChild(editButton);

                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        if (editButton && editButton.parentNode) {
                            editButton.remove();
                            editButton = null;
                        }
                    }, 5000);
                }
            }
        });
    }

    function updateWrapTextarea() {
        const originalWrap = wrapTextarea;
        wrapTextarea = function(textarea) {
            originalWrap(textarea);

            // Find the editor element
            const shell = textarea.parentNode;
            if (shell && shell.classList.contains('rich-text-shell')) {
                const editor = shell.querySelector('.rich-text-editor');
                if (editor) {
                    const sync = () => {
                        textarea.value = editor.innerHTML.trim();
                    };
                    enhanceEditorWithComponentHighlighting(editor, sync);
                }
            }
        };
    }

    updateWrapTextarea();

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('textarea.rich-text').forEach(wrapTextarea);
        document.querySelectorAll('[data-animal-gene-picker]').forEach(initAnimalGenePicker);
        initSortables();
        document.querySelectorAll('[data-menu-manager]').forEach(initMenuManager);
    });
})();

