(function () {
    if (typeof document === 'undefined') {
        return;
    }
    const root = document.querySelector('[data-genetic-selector]');
    const geneData = window.GENETIC_GENE_DATA || [];
    if (!root || !Array.isArray(geneData) || geneData.length === 0) {
        return;
    }

    const parentSelections = window.GENETIC_PARENT_SELECTIONS || { parent1: {}, parent2: {} };
    const errorPanel = root.querySelector('[data-form-error]');
    const genesById = new Map();
    const searchIndex = [];

    function normalize(value) {
        return value
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    geneData.forEach((gene) => {
        genesById.set(gene.id, gene);
        (gene.states || []).forEach((state) => {
            const tokens = Array.isArray(state.searchTokens) ? state.searchTokens.slice() : [];
            tokens.push(gene.name || '');
            if (gene.shorthand) {
                tokens.push(gene.shorthand);
            }
            const normalizedTokens = tokens
                .filter(Boolean)
                .map((token) => normalize(token));
            searchIndex.push({
                geneId: gene.id,
                stateKey: state.key,
                stateLabel: state.label,
                geneName: gene.name,
                display: `${state.label} – ${gene.name}`,
                tokens: Array.from(new Set(normalizedTokens)),
            });
        });
    });

    function findState(geneId, stateKey) {
        const gene = genesById.get(geneId);
        if (!gene) {
            return null;
        }
        return (gene.states || []).find((state) => state.key === stateKey) || null;
    }

    function buildSelectionMap(defaults) {
        const map = new Map();
        Object.entries(defaults || {}).forEach(([geneId, stateKey]) => {
            const numericId = Number(geneId);
            const state = findState(numericId, stateKey);
            if (state) {
                map.set(numericId, stateKey);
            }
        });
        return map;
    }

    const selections = {
        parent1: buildSelectionMap(parentSelections.parent1),
        parent2: buildSelectionMap(parentSelections.parent2),
    };

    function showError(message) {
        if (!errorPanel) {
            return;
        }
        errorPanel.textContent = message;
        errorPanel.hidden = false;
    }

    function clearError() {
        if (!errorPanel) {
            return;
        }
        errorPanel.textContent = '';
        errorPanel.hidden = true;
    }

    function renderTags(parentKey) {
        const container = root.querySelector(`[data-parent="${parentKey}"] [data-tag-container]`);
        const hiddenInputs = root.querySelector(`[data-parent="${parentKey}"] [data-hidden-inputs]`);
        if (!container || !hiddenInputs) {
            return;
        }
        container.innerHTML = '';
        hiddenInputs.innerHTML = '';
        const entries = Array.from(selections[parentKey].entries()).sort((a, b) => {
            const geneA = genesById.get(a[0]);
            const geneB = genesById.get(b[0]);
            const nameA = geneA ? geneA.name : '';
            const nameB = geneB ? geneB.name : '';
            return nameA.localeCompare(nameB, 'de');
        });

        entries.forEach(([geneId, stateKey]) => {
            const gene = genesById.get(geneId);
            const state = findState(geneId, stateKey);
            if (!gene || !state) {
                return;
            }
            const tag = document.createElement('span');
            tag.className = 'gene-tag';
            const label = document.createElement('span');
            label.textContent = `${gene.name}: ${state.label}`;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'gene-tag__remove';
            remove.setAttribute('aria-label', `${gene.name} entfernen`);
            remove.textContent = '×';
            remove.addEventListener('click', () => {
                selections[parentKey].delete(geneId);
                renderTags(parentKey);
                clearError();
            });
            tag.appendChild(label);
            tag.appendChild(remove);
            container.appendChild(tag);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `${parentKey}[${geneId}]`;
            input.value = stateKey;
            hiddenInputs.appendChild(input);
        });
        if (!container.children.length) {
            const emptyHint = document.createElement('p');
            emptyHint.className = 'text-muted';
            emptyHint.textContent = 'Keine Gene ausgewählt – wird als Wildtyp gewertet.';
            container.appendChild(emptyHint);
        }
    }

    function renderSuggestions(parentKey, suggestions, container, input, query) {
        container.innerHTML = '';
        if (!suggestions.length) {
            const empty = document.createElement('div');
            empty.className = 'gene-suggestion gene-suggestion--empty';
            empty.textContent = 'Keine passenden Einträge gefunden.';
            container.appendChild(empty);
            container.hidden = false;
            if (query) {
                showError('Keine Übereinstimmung gefunden. Bitte prüfen Sie die Schreibweise oder pflegen Sie das Gen im Adminbereich.');
            }
            return;
        }
        clearError();
        suggestions.slice(0, 8).forEach((entry) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'gene-suggestion';
            button.innerHTML = `<strong>${entry.stateLabel}</strong><span>${entry.geneName}</span>`;
            button.addEventListener('click', () => {
                selections[parentKey].set(entry.geneId, entry.stateKey);
                renderTags(parentKey);
                container.hidden = true;
                container.innerHTML = '';
                if (input) {
                    input.value = '';
                }
                clearError();
            });
            container.appendChild(button);
        });
        container.hidden = false;
    }

    function handleParent(parentKey) {
        const parentRoot = root.querySelector(`[data-parent="${parentKey}"]`);
        if (!parentRoot) {
            return;
        }
        const input = parentRoot.querySelector('[data-input]');
        const suggestionContainer = parentRoot.querySelector('[data-suggestions]');
        const clearButton = parentRoot.querySelector('[data-clear]');
        if (!input || !suggestionContainer) {
            return;
        }

        input.addEventListener('input', () => {
            const value = input.value.trim();
            const normalized = normalize(value);
            if (!normalized) {
                suggestionContainer.hidden = true;
                suggestionContainer.innerHTML = '';
                clearError();
                return;
            }
            const matches = searchIndex.filter((entry) => {
                if (selections[parentKey].get(entry.geneId) === entry.stateKey) {
                    return false;
                }
                return entry.tokens.some((token) => token.includes(normalized));
            });
            renderSuggestions(parentKey, matches, suggestionContainer, input, value);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                const value = input.value.trim();
                const normalized = normalize(value);
                if (!normalized) {
                    return;
                }
                const match = searchIndex.find((entry) => {
                    if (selections[parentKey].get(entry.geneId) === entry.stateKey) {
                        return false;
                    }
                    return entry.tokens.some((token) => token.includes(normalized));
                });
                if (match) {
                    event.preventDefault();
                    selections[parentKey].set(match.geneId, match.stateKey);
                    renderTags(parentKey);
                    suggestionContainer.hidden = true;
                    suggestionContainer.innerHTML = '';
                    input.value = '';
                    clearError();
                }
                if (!match) {
                    showError('Eingabe konnte keinem bekannten Gen zugeordnet werden. Bitte wählen Sie einen Vorschlag aus der Liste.');
                }
            }
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length === 0) {
                const suggestions = searchIndex.filter((entry) => selections[parentKey].get(entry.geneId) !== entry.stateKey);
                renderSuggestions(parentKey, suggestions, suggestionContainer, input, '');
            }
        });

        document.addEventListener('click', (event) => {
            if (!parentRoot.contains(event.target)) {
                suggestionContainer.hidden = true;
            }
        });

        clearButton?.addEventListener('click', () => {
            selections[parentKey].clear();
            renderTags(parentKey);
            suggestionContainer.hidden = true;
            suggestionContainer.innerHTML = '';
            if (input) {
                input.value = '';
            }
            clearError();
        });

        renderTags(parentKey);
    }

    handleParent('parent1');
    handleParent('parent2');

    root.addEventListener('submit', () => {
        clearError();
    });
})();
