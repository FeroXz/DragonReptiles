(function () {
    if (typeof document === 'undefined') {
        return;
    }

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

    function wrapTextarea(textarea) {
        if (textarea.dataset.richTextified) {
            return;
        }
        textarea.dataset.richTextified = 'true';

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

        textarea.style.display = 'none';
        textarea.parentNode.insertBefore(wrapper, textarea);
        wrapper.appendChild(toolbar);
        wrapper.appendChild(editor);
        wrapper.appendChild(textarea);

        const sync = () => {
            textarea.value = editor.innerHTML.trim();
        };

        editor.addEventListener('input', sync);
        editor.addEventListener('blur', sync);

        const form = textarea.closest('form');
        if (form) {
            form.addEventListener('submit', sync);
        }
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

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('textarea.rich-text').forEach(wrapTextarea);
        document.querySelectorAll('[data-animal-gene-picker]').forEach(initAnimalGenePicker);
        initSortables();
    });
})();

