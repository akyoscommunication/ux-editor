/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import Sortable from 'sortablejs';

export default class extends Controller {
    sortables = [];
    busy = false;

    async connect() {
        const root = this.element.closest('[data-controller~="live"]');
        this.component = await getComponent(root);
        this.refresh();
        this.afterRender = () => this.refresh();
        this.component.on('render:finished', this.afterRender);
        this.onKeydown = (event) => this.shortcut(event);
        document.addEventListener('keydown', this.onKeydown);
    }

    disconnect() {
        this.component?.off('render:finished', this.afterRender);
        document.removeEventListener('keydown', this.onKeydown);
        this.destroySortables();
    }

    shortcut(event) {
        if (!(event.metaKey || event.ctrlKey) || event.shiftKey || event.altKey || this.busy) {
            return;
        }
        const dialog = this.element.closest('dialog');
        if (!dialog?.open) {
            return;
        }
        if (event.target instanceof Element && event.target.closest('input, textarea, select, [contenteditable="true"]')) {
            return;
        }
        const key = event.key.toLowerCase();
        const id = this.component.getData('selectedId');
        if ('z' === key) {
            event.preventDefault();
            this.component.action('undo');
            return;
        }
        if (!id) {
            return;
        }
        if ('d' === key) {
            event.preventDefault();
            this.component.action('duplicate', { id });
        } else if ('w' === key) {
            event.preventDefault();
            this.component.action('remove', { id });
        } else if ('c' === key) {
            event.preventDefault();
            this.component.action('copy');
        } else if ('v' === key) {
            event.preventDefault();
            this.component.action('paste');
        }
    }

    filter(event) {
        const query = event.target.value.trim().toLowerCase();
        this.element.querySelectorAll('[data-library] [data-block-type]').forEach((item) => {
            const label = (item.dataset.label || '').toLowerCase();
            item.classList.toggle('hidden', query !== '' && !label.includes(query));
        });
    }

    refresh() {
        this.destroySortables();
        this.element.querySelectorAll('[data-canvas-root], [data-slot]').forEach((list) => {
            this.sortables.push(Sortable.create(list, {
                group: { name: 'builder-blocks', pull: true, put: true },
                handle: '[data-handle]',
                draggable: '[data-builder-item]',
                animation: 150,
                forceFallback: true,
                onAdd: (event) => this.onAdd(event),
                onUpdate: (event) => this.onUpdate(event),
            }));
        });
        this.element.querySelectorAll('[data-library]').forEach((list) => {
            this.sortables.push(Sortable.create(list, {
                group: { name: 'builder-blocks', pull: 'clone', put: false },
                sort: false,
                draggable: '[data-builder-item]',
                animation: 150,
                forceFallback: true,
            }));
        });
    }

    destroySortables() {
        this.sortables.forEach((sortable) => sortable.destroy());
        this.sortables = [];
    }

    destination(list, index) {
        return {
            index: index ?? 0,
            slot: list.dataset.slot ?? '',
            parentId: list.dataset.parentId ?? '',
        };
    }

    async onAdd(event) {
        if (this.busy) {
            return;
        }
        const payload = this.destination(event.to, event.newIndex);
        this.busy = true;
        try {
            if (event.from.hasAttribute('data-library')) {
                const type = event.item.dataset.blockType;
                event.item.remove();
                await this.component.action('insert', { type, ...payload });
                return;
            }
            await this.component.action('move', { id: event.item.dataset.blockId, ...payload });
        } finally {
            this.busy = false;
        }
    }

    async onUpdate(event) {
        if (this.busy || !event.item.dataset.blockId) {
            return;
        }
        this.busy = true;
        try {
            await this.component.action('move', {
                id: event.item.dataset.blockId,
                ...this.destination(event.to, event.newIndex),
            });
        } finally {
            this.busy = false;
        }
    }
}
