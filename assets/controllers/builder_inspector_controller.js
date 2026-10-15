/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['tab', 'panel'];

    show(event) {
        const index = this.tabTargets.indexOf(event.currentTarget);
        this.panelTargets.forEach((panel, i) => panel.classList.toggle('hidden', i !== index));
        this.tabTargets.forEach((tab, i) => {
            const active = i === index;
            tab.classList.toggle('bg-zinc-900', active);
            tab.classList.toggle('text-white', active);
            tab.classList.toggle('text-zinc-600', !active);
        });
    }
}
