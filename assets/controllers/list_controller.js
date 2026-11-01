import { Controller } from '@hotwired/stimulus';

/*
 * Listados con filtro en vivo (sustituye a assets/js/list.js, que usaba jQuery).
 *
 * - Escribir en el buscador recarga la tabla (section#exchange) sin recargar la página.
 * - Clic en una fila con data-href abre ese enlace.
 * - La casilla «seleccionar todo» marca/desmarca las casillas .selectable.
 * - Los elementos con data-list-target="needsSelection" solo se activan si hay algo marcado.
 */
export default class extends Controller {
    static targets = ['search', 'selectAll', 'needsSelection'];

    connect() {
        this.timer = null;
        this.lastQuery = this.hasSearchTarget ? this.searchTarget.value : '';
        this.onClick = this.onClick.bind(this);
        this.onChange = this.onChange.bind(this);
        this.element.addEventListener('click', this.onClick);
        this.element.addEventListener('change', this.onChange);
        this.updateButtons();
    }

    disconnect() {
        this.element.removeEventListener('click', this.onClick);
        this.element.removeEventListener('change', this.onChange);
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
        const any = this.element.querySelectorAll('input[type="checkbox"].selectable:checked').length > 0;
        this.element.querySelectorAll('[data-list-target="needsSelection"]').forEach((button) => {
            button.disabled = !any;
        });
    }
}
