import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/*
 * Selector con búsqueda sobre las opciones que ya trae el <select> (no hay petición al servidor). El tema de formularios
 * (ui/form_theme.html.twig) lo aplica a todos los desplegables, de selección única y múltiple.
 * Con «multiple», cada opción lleva una casilla de verificación y la lista no se cierra al marcar.
 *
 * Dentro de un Live Component, al repintarse el campo se elimina el contenedor que dibuja Tom Select; un observador
 * detecta que ha desaparecido y vuelve a construirlo con las opciones nuevas.
 */
export default class extends Controller {
    static values = { placeholder: String };

    connect() {
        this.build();

        this.observer = new MutationObserver(() => {
            if (this.ts && !this.ts.wrapper.isConnected) this.rebuild();
        });
        this.observer.observe(this.element.parentNode, { childList: true });
    }

    build() {
        const multiple = this.element.multiple;
        // Tom Select copia las clases del <select> al contenedor: se quitan para que no dupliquen el borde del control
        this.originalClass = this.element.className;
        this.element.className = '';
        // con una opción vacía (campo no obligatorio o con texto «Seleccione…») se puede volver a dejar sin valor
        const hasEmptyOption = Array.from(this.element.options).some((option) => option.value === '');

        this.ts = new TomSelect(this.element, {
            maxItems: multiple ? null : 1,
            placeholder: this.placeholderValue || undefined,
            allowEmptyOption: !multiple && hasEmptyOption,
            plugins: multiple ? { remove_button: {} } : {},
            hideSelected: false,
            closeAfterSelect: !multiple,
            render: multiple ? {
                option: (data, escape) => `<div class="flex items-center gap-2">
                    <input type="checkbox" tabindex="-1" class="pointer-events-none size-4 rounded border-gray-300 text-brand-600" ${this.isSelected(data.value) ? 'checked' : ''}>
                    <span>${escape(data.text)}</span></div>`,
                no_results: () => '<div class="no-results">&hellip;</div>',
            } : {
                no_results: () => '<div class="no-results">&hellip;</div>',
            },
        });

        if (multiple) {
            // La casilla refleja el estado al marcar o desmarcar sin esperar a que se repinte la lista
            const refresh = () => this.ts.refreshOptions(false);
            this.ts.on('item_add', refresh);
            this.ts.on('item_remove', refresh);
        }
    }

    rebuild() {
        this.ts.destroy();
        this.ts = null;
        this.build();
    }

    isSelected(value) {
        return this.ts?.items.includes(String(value)) ?? Array.from(this.element.selectedOptions).some((o) => o.value === String(value));
    }

    disconnect() {
        this.observer?.disconnect();
        this.ts?.destroy();
        this.ts = null;
        if (this.originalClass !== undefined) this.element.className = this.originalClass;
    }
}
