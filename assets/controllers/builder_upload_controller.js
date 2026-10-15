/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'file', 'preview'];
    static values = { url: String, token: String };

    async upload() {
        const file = this.fileTarget.files[0];
        if (!file) {
            return;
        }

        const body = new FormData();
        body.append('file', file);
        const response = await fetch(this.urlValue, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.tokenValue },
            body,
        });
        const data = await response.json();
        if (!response.ok) {
            window.alert(data.error || 'Envoi impossible.');
            return;
        }

        this.inputTarget.value = data.path;
        this.previewTarget.src = data.url || data.path;
        this.previewTarget.classList.remove('hidden');
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
        this.fileTarget.value = '';
    }
}
