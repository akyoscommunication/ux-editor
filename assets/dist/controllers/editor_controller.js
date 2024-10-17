import { Controller } from '@hotwired/stimulus';
import {Plugins, Sortable, Draggable, Droppable} from '@shopify/draggable';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['editor', 'input', 'modal', 'sortableItem', 'draggableContainer', 'droppableContainer', 'sortableContainer'];
    
    async initialize() {
        // while the editor is not loaded we can't do anything
        while (!this.editorTarget.__component) {
            await new Promise((resolve) => setTimeout(resolve, 100));
        }
        
        this.editor = this.editorTarget.__component;
        this.eligibleDroppableContainers = this.droppableContainerTargets;
        
        this.connect();
    }
    
    connect() {
        if (!this.editor) {
            return;
        }
        
        this.initializeEligibleDroppableContainers();
        
        // to initialize the value of the input with the value
        this.inputTarget.dispatchEvent(new CustomEvent('change', { bubbles: true }));
        
        
        this.editor.on('render:finished', () => {
            // is not selected
            if (this.eligibleDroppableContainers.length !== 1) {
                this.eligibleDroppableContainers = this.droppableContainerTargets;
            }
            this.initializeEligibleDroppableContainers();
        });

        window.addEventListener('editor:save', (e) => {
            const detail = e.detail;
            const content = detail.content;

            if (detail.id === this.inputTarget.id) {
                this.inputTarget.value = JSON.stringify(content);
                this.inputTarget.dispatchEvent(new CustomEvent('change', { bubbles: true }));
            }
        });
        
        const sortable = new Sortable(this.sortableContainerTargets, {
            draggable: '*[sortable]',
            plugins: [Plugins.SortAnimation, Plugins.Snappable],
            handle: '*[sortable-handle]',
            mirror: {
                constrainDimensions: true,
            },
            swapAnimation: {
                duration: 200,
                easingFunction: 'ease-in-out',
            }
        });
        
        // Utilisation du miroir déjà créé dans Draggable
        sortable.on('mirror:move', (event) => {
            // initialise height and width of the source element
            const sourceElement = event.data.originalSource;

            if (this.draggableContainerTargets.includes(event.data.sourceContainer)) {
                const sourceRect = sourceElement.getBoundingClientRect();
                event.mirror.style.width = sourceRect.width + 'px';
                event.mirror.style.height = sourceRect.height + 'px';
                // put mirror in center of the cursor translation
                event.mirror.style.left = sourceRect.x + 'px';
                event.mirror.style.top = sourceRect.y + 'px';
            }
        });
        
        sortable.on('sortable:sort', (evt) => {
            const sourceContainer = evt.data.dragEvent.data.sourceContainer;
            const newContainer = evt.data.dragEvent.data.overContainer;
            
            if (!this.#canMakeAction(sourceContainer, newContainer)) {
                evt.cancel();
            }
        })
        
        sortable.on('sortable:stop', (evt) => {
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
                        keys = parentComponent_component.key;
                    }
                    
                    this.editor.action('add', {'component': element.getAttribute('data-component'), 'order': newIndex, keys});
                } else {
                    const component = element.__component;
                    const keyOfComponent = component.keyOfComponent;

                    this.editor.action('move', {'old' : oldIndex, 'new' : newIndex, keys: keyOfComponent})
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
    
    #canMakeAction(sourceContainer, newContainer) {
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
        // find the closest droppable container in this.droppableContainerTargets
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
            // if has class .c-component-edit__children then add droppable--editable
            const id = shadowContainer.id;
            const container = document.getElementById(id);
            
            if (container.classList.contains('c-component-edit__children')) {
                const parentComponent = container.closest(".c-component-edit");
                parentComponent.classList.add('droppable-container--active');
            }
        });
    }
    
    save() {
        /** Handle pending files */
        const edits = this.element.querySelectorAll('.c-component-edit');
        edits.forEach((edit) => {
            const component = edit.__component;
            const componentPendingFiles = component.elementDriver.controller.pendingFiles;
            const keyOfComponent = component.keyOfComponent;
            
            if (componentPendingFiles) {
                for (const [key, file] of Object.entries(componentPendingFiles)) {
                    const newKey = key.replace('component', `component[${keyOfComponent}]`);
                    this.editor.files(newKey, file);
                }
            }
        });
        
        this.editor.action('save');
    }
}
