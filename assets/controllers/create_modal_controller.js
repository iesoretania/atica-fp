import { Controller } from '@hotwired/stimulus';

/*
 * Modal de alta rápida (nueva persona, nueva ruta...) sobre <dialog> (sustituye al modal de Bootstrap y a person.js).
 *
 * - Se abre cuando un selector con búsqueda remota emite «entity-select:new» y su elemento cumple el selector indicado.
 * - Envía el formulario del cuerpo por fetch. Si el servidor responde JSON {id, name}, selecciona el nuevo registro
 *   en el selector de origen y cierra; si responde HTML (errores de validación), lo vuelve a mostrar.
 */
export default class extends Controller {
    static targets = ['dialog', 'body'];
    static values = {
        selector: String,           // p. ej. "select.person"
        termField: String,          // id del campo que se rellena con lo escrito (identificador)
        termEmailField: String,     // id del campo que se rellena si lo escrito parece un correo
        focusField: String,         // id del campo que recibe el foco al abrir
    };

    connect() {
        this.onNew = (event) => {
            if (!event.detail.element.matches(this.selectorValue)) return;
            this.origin = event.detail.element;
            this.open(event.detail.term || '');
        };
        document.addEventListener('entity-select:new', this.onNew);
    }

    disconnect() {
        document.removeEventListener('entity-select:new', this.onNew);
    }

    open(term) {
        const form = this.bodyTarget.querySelector('form');
        if (form) {
            form.reset();
            const target = term.includes('@') ? this.termEmailFieldValue : this.termFieldValue;
            if (target && term) {
                const input = document.getElementById(target);
                if (input) input.value = term;
            }
        }
        this.dialogTarget.showModal();
        if (this.focusFieldValue) document.getElementById(this.focusFieldValue)?.focus();
    }

    close() {
        this.dialogTarget.close();
    }

    async save(event) {
        event.preventDefault();
        const form = this.bodyTarget.querySelector('form');
        if (!form) return;
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const type = response.headers.get('Content-Type') || '';
        if (type.includes('json')) {
            const result = await response.json();
            const controller = this.application.getControllerForElementAndIdentifier(this.origin, 'entity-select');
            controller?.select(result.id, result.name);
            this.close();
            // formulario limpio para el próximo alta
            const fresh = await fetch(form.action, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            this.bodyTarget.innerHTML = await fresh.text();
        } else {
            this.bodyTarget.innerHTML = await response.text();
        }
    }
}
