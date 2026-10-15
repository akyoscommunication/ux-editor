/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    forward(event) {
        if (event.target instanceof HTMLInputElement) {
            return;
        }
        this.element.querySelector('input')?.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
