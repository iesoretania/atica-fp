import { Controller } from '@hotwired/stimulus';

// Botones «seleccionar todo» / «ninguno» para un grupo de casillas (las de dentro del elemento)
export default class extends Controller {
    all() {
        this.boxes().forEach((box) => { box.checked = true; });
    }

    none() {
        this.boxes().forEach((box) => { box.checked = false; });
    }

    boxes() {
        return this.element.querySelectorAll('input[type="checkbox"]:not(:disabled)');
    }
}
