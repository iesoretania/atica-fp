import { Controller } from '@hotwired/stimulus';

/*
 * Muestra u oculta el texto de un campo de contraseña.
 * Targets: input (el campo), eyeOpen (ojo: contraseña oculta), eyeClosed (ojo tachado: contraseña visible).
 * Acción: password-visibility#toggle en el botón.
 */
export default class extends Controller {
    static targets = ['input', 'eyeOpen', 'eyeClosed'];

    toggle(event) {
        const visible = this.inputTarget.type === 'password';
        this.inputTarget.type = visible ? 'text' : 'password';
        this.eyeOpenTarget.classList.toggle('hidden', visible);
        this.eyeClosedTarget.classList.toggle('hidden', !visible);
        event.currentTarget.setAttribute('aria-pressed', visible ? 'true' : 'false');
    }
}
