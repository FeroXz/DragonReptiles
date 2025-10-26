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
        initSortables();
    });
})();

