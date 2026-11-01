import { Controller } from '@hotwired/stimulus';

// Menú lateral: en pantallas pequeñas se muestra/oculta con el botón de la cabecera.
export default class extends Controller {
    static targets = ['panel', 'overlay'];

    toggle() {
        const open = this.panelTarget.classList.toggle('-translate-x-full');
        this.overlayTarget.classList.toggle('hidden', open);
        document.body.classList.toggle('overflow-hidden', !open);
    }

    close() {
        this.panelTarget.classList.add('-translate-x-full');
        this.overlayTarget.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}
