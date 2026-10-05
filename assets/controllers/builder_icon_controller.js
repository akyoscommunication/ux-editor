import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // options_as_html score les options pendant la construction de Tom Select,
    // avant que le contrôleur UX ait sa référence. La liste reste vide
    // jusqu’à un refresh. Plafond : liste locale, pas de pagination Ajax.
    refresh(event) {
        event.detail.tomSelect.refreshOptions(false);
    }
}
