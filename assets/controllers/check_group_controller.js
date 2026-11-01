import { Controller } from '@hotwired/stimulus';

/*
 * Botón «Seleccionar todos» / «Quitar todos» de un bloque de casillas (p. ej. el alumnado de un grupo).
 * El botón (target «toggle») cambia su texto según el estado: si todas las casillas habilitadas están marcadas ofrece
 * quitarlas; si no, marcarlas todas.
 *
 * Valores: selectLabel y clearLabel (textos ya traducidos).
 */
export default class extends Controller {
    static targets = ['toggle'];
    static values = { selectLabel: String, clearLabel: String };

    connect() {
        this.onChange = () => this.refresh();
        this.element.addEventListener('change', this.onChange);
        this.refresh();
    }

    disconnect() {
        this.element.removeEventListener('change', this.onChange);
    }

    toggle() {
        const check = !this.allChecked();
        this.boxes().forEach((box) => {
            box.checked = check;
        });
        this.refresh();
        // el formulario o el componente que contenga las casillas debe enterarse del cambio
        this.boxes().forEach((box) => box.dispatchEvent(new Event('change', { bubbles: true })));
    }

    boxes() {
        return [...this.element.querySelectorAll('input[type="checkbox"]:not(:disabled)')];
    }

    allChecked() {
        const boxes = this.boxes();
        return boxes.length > 0 && boxes.every((box) => box.checked);
    }

    refresh() {
        if (!this.hasToggleTarget) return;
        this.toggleTarget.textContent = this.allChecked() ? this.clearLabelValue : this.selectLabelValue;
    }
}
