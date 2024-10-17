import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input'];

    async initialize() {
        this.editorEdit = this.element.__component;
    }
    
    connect() {
        // @TODO: c'est dégeux, à enlever et trouver une autre solution avec onUpdated
        let renderFinished = 0;
        // this.editorEdit.on('render:finished', (component) => {
        //     if (renderFinished === 2) {
        //         renderFinished = 0;
        //         return;
        //     }
        //
        //     if (renderFinished === 0) {
        //         this.editorEdit.action('sync');
        //     }
        //     renderFinished++;
        // })

        // init filter
        if (this.editorEdit) {
            // this.toggleFields({ params: { name: this.editorEdit.currentFieldsFilter } });

            // this.editorEdit.on('render:finished', (component) => {
            //     this.toggleFields({ params: { name: component.valueStore.props.currentFieldsFilter } });
            // })
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
}
