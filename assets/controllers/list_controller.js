import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/*
 * Listados con filtro en vivo (sustituye a assets/js/list.js, que usaba jQuery).
 *
 * - Escribir en el buscador recarga la tabla (section#exchange) sin recargar la página.
 * - Clic en una fila con data-href abre ese enlace.
 * - La casilla «seleccionar todo» marca/desmarca las casillas .selectable.
 * - Los elementos con data-list-target="needsSelection" solo se activan si hay algo marcado.
 */
export default class extends Controller {
    static targets = ['search', 'selectAll', 'needsSelection', 'selectionBar'];

    connect() {
        this.timer = null;
        this.lastQuery = this.hasSearchTarget ? this.searchTarget.value : '';
        this.onClick = this.onClick.bind(this);
        this.onChange = this.onChange.bind(this);
        this.element.addEventListener('click', this.onClick);
        this.element.addEventListener('change', this.onChange);
        this.updateButtons();

        // en los listados que son Live Components, la tabla se repinta sin recargar la página
        if (this.element.hasAttribute('data-live-name-value')) {
            this.onRendered = () => this.updateButtons();
            getComponent(this.element).then((component) => {
                this.component = component;
                component.on('render:finished', this.onRendered);
            });
        }
    }

    disconnect() {
        this.element.removeEventListener('click', this.onClick);
        this.element.removeEventListener('change', this.onChange);
        this.component?.off('render:finished', this.onRendered);
        clearTimeout(this.timer);
    }

    search() {
        clearTimeout(this.timer);
        this.timer = setTimeout(() => this.reload(), 400);
    }

    clear() {
        if (!this.hasSearchTarget) return;
        this.searchTarget.value = '';
        this.searchTarget.focus();
        this.reload();
    }

    // Invierte la selección de todas las casillas .selectable (botón «seleccionar/deseleccionar»)
    toggle(event) {
        event.preventDefault();
        this.element.querySelectorAll('input[type="checkbox"].selectable:not(:disabled)').forEach((box) => {
            box.checked = !box.checked;
        });
        this.updateButtons();
    }

    // Cambio de elemento padre (p. ej. curso académico): cada opción lleva su URL en data-url
    navigate(event) {
        const option = event.target.selectedOptions[0];
        if (option && option.dataset.url) {
            window.location.assign(option.dataset.url);
        }
    }

    async reload() {
        const q = this.hasSearchTarget ? this.searchTarget.value.trim() : '';
        if (q === this.lastQuery) return;
        this.lastQuery = q;

        const url = new URL(window.location.href);
        url.search = '';
        if (q) url.searchParams.set('q', q);

        const section = this.element.querySelector('#exchange');
        if (section) section.classList.add('opacity-50');

        try {
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(response.statusText);
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            const fresh = doc.querySelector('#exchange');
            if (!fresh || !section) throw new Error('missing');
            section.replaceWith(fresh);
            this.updateButtons();
        } catch (e) {
            window.location.assign(url.toString());
        }
    }

    onClick(event) {
        const row = event.target.closest('.clickable-row');
        if (!row || !this.element.contains(row)) return;
        if (event.target.closest('a, button, input, label, select, textarea')) return;
        // si se ha seleccionado texto de la fila (para copiarlo), no se abre la ficha
        if (window.getSelection && window.getSelection().toString() !== '') return;
        if (row.dataset.href) {
            window.location.assign(row.dataset.href);
        }
    }

    onChange(event) {
        const target = event.target;
        if (target.matches('[data-list-target="selectAll"]')) {
            this.element.querySelectorAll('input[type="checkbox"].selectable:not(:disabled)').forEach((box) => {
                box.checked = target.checked;
            });
        }
        this.updateButtons();
    }

    updateButtons() {
        const count = this.element.querySelectorAll('input[type="checkbox"].selectable:checked').length;
        this.element.querySelectorAll('[data-list-target="needsSelection"]').forEach((button) => {
            button.disabled = count === 0;
        });

        // barra de acciones por lote: visible solo con filas marcadas, con el número de seleccionadas
        this.selectionBarTargets.forEach((bar) => {
            bar.hidden = count === 0;
            const label = bar.querySelector('[data-list-target="selectionCount"]');
            if (label && count > 0) {
                label.textContent = count === 1
                    ? bar.dataset.listOneLabelValue
                    : bar.dataset.listOtherLabelValue.replace('7', String(count)); // el texto se genera con 7 como plantilla
            }
        });
    }

    // Quita la marca de todas las filas
    clearSelection() {
        this.element.querySelectorAll('input[type="checkbox"].selectable:checked, [data-list-target="selectAll"]:checked').forEach((box) => {
            box.checked = false;
        });
        this.updateButtons();
    }
}
