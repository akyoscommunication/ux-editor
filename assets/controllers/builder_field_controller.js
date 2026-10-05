import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'blueprint'];

    connect() {
        this.onDocument = (event) => {
            if (!event.detail || event.detail.input !== this.inputTarget.id) {
                return;
            }
            this.inputTarget.value = JSON.stringify(event.detail.document);
        };
        window.addEventListener('builder:document', this.onDocument);
        // JoliMedia fait `new Event('change')` sans bubbles. Le builder
        // n'écoute le formulaire qu'en phase de remontée, donc le chemin
        // choisi n'arrivait jamais dans le document enregistré.
        this.onMediaChange = (event) => {
            const input = event.target;
            const choice = input.closest('[data-component="media-choice"]');
            if (event.bubbles || !(input instanceof HTMLInputElement) || !choice) {
                return;
            }
            // JoliMedia insère l’aperçu à la main. Le morph du live component
            // le garde (nœud ajouté hors rendu) et ajoute aussi celui du serveur.
            choice.querySelector('[id^="joli-media-container"]')?.replaceChildren();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        };
        document.addEventListener('change', this.onMediaChange, true);
        // Le choix recharge l’inspecteur. L’ancienne modale reste sur body,
        // et le clic suivant ouvre la nouvelle, vide, encore dans le dialog.
        this.onMediaOpen = (event) => {
            // JoliMedia relance un clic qui ne remonte pas. On l'ignore,
            // sinon on efface la configuration juste après l'avoir posée.
            if (!event.bubbles) {
                return;
            }
            const edit = event.target.closest('[data-component="media-choice-edit"]');
            const choice = edit?.closest('[data-component="media-choice"]');
            if (!choice) {
                return;
            }
            const modalId = `modal-media-choice_${choice.dataset.mediaId}`;
            const modals = [...document.querySelectorAll('.modal-media-choice')].filter((modal) => modal.id === modalId);
            const fresh = modals.find((modal) => modal.parentElement !== document.body) ?? modals[0];
            modals.forEach((modal) => {
                if (modal !== fresh) {
                    modal.remove();
                }
            });
            if (fresh && fresh.parentElement !== document.body) {
                document.body.append(fresh);
            }
            const body = fresh?.querySelector('[data-component="media-choice-modal-body"]');
            if (body && body.childElementCount === 0) {
                delete choice.dataset.configured;
            }
            document.querySelectorAll('.modal-backdrop').forEach((node) => node.remove());
            document.body.classList.remove('modal-open');
        };
        document.addEventListener('click', this.onMediaOpen, true);
        // Bootstrap affiche la modale après le clic. On la sort du dialog
        // avant le backdrop, sinon overflow + z-index 1040 la coincent
        // dans l’inspecteur.
        this.onModalShow = (event) => {
            const modal = event.target;
            if (!(modal instanceof HTMLElement) || !modal.classList.contains('modal-media-choice')) {
                return;
            }
            if (modal.parentElement !== document.body) {
                document.body.append(modal);
            }
        };
        document.addEventListener('show.bs.modal', this.onModalShow);
    }

    disconnect() {
        window.removeEventListener('builder:document', this.onDocument);
        document.removeEventListener('change', this.onMediaChange, true);
        document.removeEventListener('click', this.onMediaOpen, true);
        document.removeEventListener('show.bs.modal', this.onModalShow);
        document.removeEventListener('keydown', this.onKeydown);
        this.dialog?.close();
    }

    open() {
        if (!this.dialog) {
            document.body.append(this.blueprintTarget.content);
            this.dialog = document.getElementById(`${this.inputTarget.id}-dialog`);
            this.onKeydown = (event) => {
                if (event.key !== 'Escape' || !this.dialog?.open || document.querySelector('.modal.show')) {
                    return;
                }
                this.dialog.close();
            };
            document.addEventListener('keydown', this.onKeydown);
        }
        // show() et pas showModal() : JoliMedia déplace sa modale sur body,
        // et le top layer de showModal() la masque.
        this.dialog?.show();
    }
}
