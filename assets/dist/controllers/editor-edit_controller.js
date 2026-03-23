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
        this.element.addEventListener('input', this._onFormInput);
        this.element.addEventListener('change', this._onFormChange);
    }

    disconnect() {
        this.element.removeEventListener('input', this._onFormInput);
        this.element.removeEventListener('change', this._onFormChange);
        clearTimeout(this._syncTimeout);
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
            const key = this.element.dataset.keyOfComponent;
            if (key === undefined || key === '') {
                return;
            }
            const parent = this.#getParentEditorController();
            if (parent && typeof parent.runSerial === 'function') {
                parent.runSerial(() => this.editorEdit.action('sync', { key }));
            } else {
                this.editorEdit.action('sync', { key });
            }
        }, 500);
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
            if (parent && typeof parent.runSerial === 'function') {
                const p = parent.runSerial(() => this.editorEdit.action('sync', { key }));
                if (p && typeof p.then === 'function') {
                    await p;
                }
            } else {
                const p = this.editorEdit.action('sync', { key });
                if (p && typeof p.then === 'function') {
                    await p;
                }
            }
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
