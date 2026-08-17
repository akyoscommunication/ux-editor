import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input'];

    async initialize() {
        while (!this.element.__component) {
            await new Promise((resolve) => setTimeout(resolve, 50));
        }
        this.editorEdit = this.element.__component;
    }

    connect() {
        this._syncTimeout = null;
        this._onFormInput = (e) => this.#onFormInput(e);
        this._onFormChange = (e) => this.#onFormChange(e);
        this._onFormBlur = (e) => this.#onFormBlur(e);
        this.element.addEventListener('input', this._onFormInput);
        this.element.addEventListener('change', this._onFormChange);
        this.element.addEventListener('focusout', this._onFormBlur);
    }

    disconnect() {
        this.element.removeEventListener('input', this._onFormInput);
        this.element.removeEventListener('change', this._onFormChange);
        this.element.removeEventListener('focusout', this._onFormBlur);
        clearTimeout(this._syncTimeout);
    }

    flushSync() {
        clearTimeout(this._syncTimeout);
        this._syncTimeout = null;
        return this.#syncNow();
    }

    #onFormInput(e) {
        const t = e.target;
        if (t.tagName === 'BUTTON' || t.closest('button')) {
            return;
        }
        if (t.type === 'file') {
            return;
        }
        this.#scheduleSync();
    }

    #onFormChange(e) {
        const t = e.target;
        if (t.type === 'file') {
            void this.#syncThenSave();
            return;
        }
        this.#scheduleSync();
    }

    #onFormBlur(e) {
        const t = e.target;
        if (!t.matches('input:not([type=file]), textarea, select')) {
            return;
        }
        if (!this.element.contains(t)) {
            return;
        }
        clearTimeout(this._syncTimeout);
        void this.#syncNow();
    }

    #getParentEditorController() {
        const host = this.element.closest('.ux-editor [data-controller]');
        if (!host || !this.application) {
            return null;
        }
        return this.application.getControllerForElementAndIdentifier(host, 'editor');
    }

    #scheduleSync() {
        if (!this.editorEdit) {
            return;
        }
        clearTimeout(this._syncTimeout);
        this._syncTimeout = setTimeout(() => {
            void this.#syncNow();
        }, 500);
    }

    #collectFormValues() {
        const form = this.element.querySelector('form');
        if (!form) {
            return {};
        }

        const formName = form.getAttribute('name') ?? '';
        const values = {};

        for (const [fullKey, value] of new FormData(form).entries()) {
            let path;

            if (formName !== '' && fullKey.startsWith(`${formName}[`)) {
                path = fullKey
                    .slice(formName.length + 1, -1)
                    .split('][');
            } else if (formName !== '' && fullKey === formName) {
                continue;
            } else {
                path = [fullKey];
            }

            this.#setDeep(values, path, value);
        }

        return values;
    }

    #setDeep(object, path, value) {
        let current = object;

        for (let i = 0; i < path.length - 1; i++) {
            const key = path[i];
            if (!(key in current) || typeof current[key] !== 'object' || current[key] === null) {
                current[key] = {};
            }
            current = current[key];
        }

        const lastKey = path[path.length - 1];
        const existing = current[lastKey];

        if (existing === undefined) {
            current[lastKey] = value;
            return;
        }

        if (Array.isArray(existing)) {
            existing.push(value);
            return;
        }

        current[lastKey] = [existing, value];
    }

    #syncNow() {
        if (!this.editorEdit) {
            return Promise.resolve();
        }
        const key = this.element.dataset.keyOfComponent;
        if (key === undefined || key === '') {
            return Promise.resolve();
        }
        const formValues = this.#collectFormValues();
        const parent = this.#getParentEditorController();
        const sync = () => this.editorEdit.action('sync', { key, formValues });
        if (parent && typeof parent.runSerial === 'function') {
            return parent.runSerial(sync);
        }
        return sync();
    }

    async #syncThenSave() {
        const key = this.element.dataset.keyOfComponent;
        const parent = this.#getParentEditorController();
        if (!this.editorEdit || key === undefined || key === '') {
            if (parent && typeof parent.save === 'function') {
                parent.save();
            }
            return;
        }
        try {
            await this.#syncNow();
        } catch (_) {
        }
        if (parent && typeof parent.save === 'function') {
            parent.save();
        }
    }

    toggleFields({ params: { name } }) {
        this.inputTargets.forEach((input) => {
            const filter = input.getAttribute('editor-edit-filter');

            if (filter === name) {
                input.classList.remove('hidden');
            } else {
                input.classList.add('hidden');
            }
        });
    }

    clearInput({ params: { name } }) {
        document.querySelectorAll(`#${name}`).forEach((input) => {
            input.value = '';
            input.dispatchEvent(new CustomEvent('change', {bubbles: true}));
        });
    }
}
