import { Controller } from '@hotwired/stimulus';
import {Plugins, Sortable} from '@shopify/draggable';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['editor', 'input', 'modal', 'draggableContainer', 'droppableContainer', 'sortableContainer', 'previewViewport', 'editPane', 'renderPane'];
    
    initialize() {
        this._liveQueue = Promise.resolve();
    }

    async connect() {
        while (!this.editorTarget.__component) {
            await new Promise((resolve) => setTimeout(resolve, 100));
        }

        this.editor = this.editorTarget.__component;
        this.eligibleDroppableContainers = this.droppableContainerTargets;

        this.inputTarget.dispatchEvent(new CustomEvent('change', { bubbles: true }));

        this._onEditorSave = (e) => {
            const detail = e.detail;
            const content = detail.content;

            if (detail.id === this.inputTarget.id) {
                this.inputTarget.value = JSON.stringify(content);
                this.inputTarget.dispatchEvent(new CustomEvent('change', { bubbles: true }));
            }
        };
        window.addEventListener('editor:save', this._onEditorSave);

        this._onRenderFinished = () => {
            if (this.eligibleDroppableContainers.length !== 1) {
                this.eligibleDroppableContainers = this.droppableContainerTargets;
            }
            this.initializeEligibleDroppableContainers();
            this._recreateSortable();
            this._restoreViewport();
            this._restoreEditMode();
        };
        this.editor.on('render:finished', this._onRenderFinished);

        this._recreateSortable();
        this._restoreViewport();
        this._restoreEditMode();
    }

    disconnect() {
        if (this.editor && this._onRenderFinished) {
            this.editor.off('render:finished', this._onRenderFinished);
        }
        if (this._onEditorSave) {
            window.removeEventListener('editor:save', this._onEditorSave);
        }
        this._destroySortable();
    }

    runSerial(fn) {
        const next = this._liveQueue.then(() => fn());
        this._liveQueue = next.catch(() => {});
        return next;
    }

    _destroySortable() {
        if (this._sortable) {
            this._sortable.destroy();
            this._sortable = null;
        }
    }

    _recreateSortable() {
        if (!this.editor) {
            return;
        }

        this._destroySortable();

        const containers = this.sortableContainerTargets;
        if (!containers.length) {
            return;
        }

        this._sortable = new Sortable(containers, {
            draggable: '*[sortable]',
            plugins: [Plugins.SortAnimation, Plugins.Snappable],
            handle: '*[sortable-handle]',
            mirror: {
                constrainDimensions: true,
            },
            sortAnimation: {
                duration: 200,
                easingFunction: 'ease-in-out',
            }
        });

        this._sortable.on('mirror:move', (event) => {
            const sourceElement = event.data.originalSource;

            if (this.draggableContainerTargets.includes(event.data.sourceContainer)) {
                const sourceRect = sourceElement.getBoundingClientRect();
                event.mirror.style.width = sourceRect.width + 'px';
                event.mirror.style.height = sourceRect.height + 'px';
                event.mirror.style.left = sourceRect.x + 'px';
                event.mirror.style.top = sourceRect.y + 'px';
            }
        });

        this._sortable.on('sortable:sort', (evt) => {
            const sourceContainer = evt.data.dragEvent.data.sourceContainer;
            const newContainer = evt.data.dragEvent.data.overContainer;

            if (!this.#canMakeAction(sourceContainer, newContainer)) {
                evt.cancel();
            }
        });

        this._sortable.on('sortable:stop', (evt) => {
            const element = evt.data.dragEvent.data.originalSource;
            const sourceContainer = evt.data.dragEvent.data.sourceContainer;
            const newContainer = evt.data.newContainer;

            if (this.#canMakeAction(sourceContainer, newContainer)) {
                const newIndex = evt.data.newIndex;
                const oldIndex = evt.data.oldIndex;

                if (sourceContainer !== newContainer) {
                    let keys = null;
                    if (newContainer.classList.contains('c-component-edit__children')) {
                        const parentComponent = newContainer.closest(".c-component-edit");
                        const parentComponent_component = parentComponent.__component;
                        keys = parentComponent_component?.keyOfComponent
                            ?? parentComponent_component?.valueStore?.props?.keyOfComponent
                            ?? parentComponent?.dataset?.keyOfComponent;
                    }

                    this.runSerial(() => this.editor.action('add', {'component': element.getAttribute('data-component'), 'order': newIndex, keys}));
                } else {
                    const component = element.__component;
                    const keyOfComponent = component?.keyOfComponent
                        ?? component?.valueStore?.props?.keyOfComponent
                        ?? element.dataset?.keyOfComponent;

                    const keys = this.#parentKeysForMove(keyOfComponent);
                    this.runSerial(() => this.editor.action('move', {'old' : oldIndex, 'new' : newIndex, keys}));
                }
            }
        });
    }
    
    open() {
        document.body.style.overflow = 'hidden';
        this.modalTarget.showModal();
    }
    
    close() {
        document.body.style.overflow = '';
        this.modalTarget.close();
    }

    setViewport(event) {
        const mode = event.params?.viewport;
        if (!mode || !this.hasPreviewViewportTarget) {
            return;
        }
        this._applyViewport(mode);
    }

    _restoreViewport() {
        if (!this.hasPreviewViewportTarget) {
            return;
        }
        let mode = 'desktop';
        try {
            const s = localStorage.getItem('ux-editor-viewport');
            if (s && ['desktop', 'laptop', 'ipad', 'iphone'].includes(s)) {
                mode = s;
            }
        } catch (_) {}
        this._applyViewport(mode);
    }

    _applyViewport(mode) {
        const modes = ['desktop', 'laptop', 'ipad', 'iphone'];
        if (!modes.includes(mode)) {
            return;
        }
        const pane = this.previewViewportTarget;
        modes.forEach((m) => pane.classList.remove(`ux-editor-preview--${m}`));
        pane.classList.add(`ux-editor-preview--${mode}`);
        try {
            localStorage.setItem('ux-editor-viewport', mode);
        } catch (_) {}
        this.element.querySelectorAll('[data-viewport]').forEach((btn) => {
            const pressed = btn.dataset.viewport === mode;
            btn.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            btn.classList.toggle('text-slate-300', pressed);
            btn.classList.toggle('text-slate-400', !pressed);
        });
    }

    setEditMode(event) {
        const mode = event.params?.mode;
        if (!mode || !this.hasEditPaneTarget || !this.hasRenderPaneTarget) {
            return;
        }
        this._applyEditMode(mode);
    }

    _restoreEditMode() {
        if (!this.hasEditPaneTarget || !this.hasRenderPaneTarget) {
            return;
        }
        let mode = 'edit';
        try {
            const s = localStorage.getItem('ux-editor-edit-mode');
            if (s === 'render' || s === 'edit') {
                mode = s;
            }
        } catch (_) {}
        this._applyEditMode(mode);
    }

    _applyEditMode(mode) {
        if (mode !== 'edit' && mode !== 'render') {
            return;
        }
        const edit = this.editPaneTarget;
        const render = this.renderPaneTarget;
        if (mode === 'edit') {
            edit.classList.remove('hidden');
            render.classList.add('hidden');
        } else {
            edit.classList.add('hidden');
            render.classList.remove('hidden');
        }
        try {
            localStorage.setItem('ux-editor-edit-mode', mode);
        } catch (_) {}
        this.element.querySelectorAll('[data-edit-mode]').forEach((btn) => {
            const pressed = btn.dataset.editMode === mode;
            btn.setAttribute('aria-pressed', pressed ? 'true' : 'false');
            btn.classList.toggle('text-slate-300', pressed);
            btn.classList.toggle('text-slate-400', !pressed);
        });
    }

    #parentKeysForMove(keyOfComponent) {
        const k = (keyOfComponent ?? '').toString().trim();
        if (k === '') {
            return '';
        }
        const parts = k.split('.');
        if (parts.length <= 1) {
            return '';
        }
        parts.pop();
        return parts.join('.');
    }

    #canMakeAction(sourceContainer, newContainer) {
        if (sourceContainer === newContainer && !this.draggableContainerTargets.includes(sourceContainer)) {
            return true;
        }

        if (!this.draggableContainerTargets.includes(sourceContainer) && (sourceContainer !== newContainer)) {
            return false;
        }
        
        if (!this.#isDroppableInEligibleContainer(newContainer)) {
            return false;
        }
        
        if (this.draggableContainerTargets.includes(sourceContainer) && this.draggableContainerTargets.includes(newContainer)) {
            return false;
        }
        
        return true;
    }
    
    #isDroppableInEligibleContainer(droppable) {
        const id = droppable.id;
        const eligibleDroppable = this.eligibleDroppableContainers.find((container) => container.id === id);
        return !!eligibleDroppable;
    }
    
    setSortable(evt) {
        const droppableContainer = evt.target.closest("[data-editor-target*='droppableContainer']");
        if (!droppableContainer) {
            return;
        }
        
        if (!this.#isDroppableInEligibleContainer(droppableContainer) || (this.#isDroppableInEligibleContainer(droppableContainer) && this.eligibleDroppableContainers.length !== 1)) {
            this.eligibleDroppableContainers = [droppableContainer];
        } else {
            this.eligibleDroppableContainers = this.droppableContainerTargets;
        }
        
        this.initializeEligibleDroppableContainers();
    }
    
    unsetSortable(evt) {
        this.eligibleDroppableContainers = this.droppableContainerTargets;
    }
    
    initializeEligibleDroppableContainers() {
        document.querySelectorAll('.droppable-container--active').forEach((container) => {
            container.classList.remove('droppable-container--active');
        });
        
        this.eligibleDroppableContainers.forEach((shadowContainer) => {
            const id = shadowContainer.id;
            const container = document.getElementById(id);
            
            if (container.classList.contains('c-component-edit__children')) {
                const parentComponent = container.closest(".c-component-edit");
                parentComponent.classList.add('droppable-container--active');
            }
        });
    }
    
    save() {
        this.runSerial(() => {
            const edits = this.element.querySelectorAll('.c-component-edit');
            edits.forEach((edit) => {
                const component = edit.__component;
                const componentPendingFiles = component.elementDriver.controller.pendingFiles;
                const keyOfComponent = component?.keyOfComponent
                    ?? component?.valueStore?.props?.keyOfComponent
                    ?? edit.dataset?.keyOfComponent;
                
                if (componentPendingFiles) {
                    for (const [key, file] of Object.entries(componentPendingFiles)) {
                        const newKey = key.replace('component', `component[${keyOfComponent}]`);
                        this.editor.files(newKey, file);
                    }
                }
            });
            
            return this.editor.action('save');
        });
    }
}
