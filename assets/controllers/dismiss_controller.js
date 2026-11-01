import { Controller } from '@hotwired/stimulus';

// Elimina el elemento (p. ej. un aviso) al pulsar el botón de cerrar.
export default class extends Controller {
    close() {
        this.element.remove();
    }
}
