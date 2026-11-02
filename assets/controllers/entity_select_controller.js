import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/*
 * Selector con búsqueda remota para los campos RemoteEntityType (sustituye a select2entity + Select2, que usaban jQuery).
 *
 * Mantiene el contrato del servidor: GET url?q=...&field_name=...&class_type=... (+ page si hay scroll,
 * + parámetros de req_params y query_parameters) que devuelve [{id, text}] o {results: [...], more: bool}.
 *
 * Si un resultado trae id 0 (p. ej. «crear persona nueva»), no se selecciona: se emite el evento
 * «entity-select:new» con la búsqueda escrita para que lo atienda un modal de alta (controlador create-modal).
 */
export default class extends Controller {
    static values = {
        url: String,
        minLength: { type: Number, default: 0 },
        placeholder: String,
        fieldName: String,
        classType: String,
        scroll: Boolean,
        allowClear: Boolean,
        renderHtml: Boolean,
        queryParameters: Object,
        reqParams: Object,
    };

    connect() {
        const multiple = this.element.multiple;
        const plugins = {};
        if (multiple || this.allowClearValue) plugins.remove_button = {};

        this.lastQuery = '';
        this.ts = new TomSelect(this.element, {
            valueField: 'id',
            labelField: 'text',
            searchField: [],
            maxItems: multiple ? null : 1,
            placeholder: this.placeholderValue || undefined,
            loadThrottle: 300,
            plugins,
            // el servidor ya filtra: se muestran todos los resultados tal cual llegan
            score: () => () => 1,
            shouldLoad: (query) => query.length >= this.minLengthValue,
            load: (query, callback) => this.load(query, callback),
            render: this.renderHtmlValue ? {
                option: (data, escape) => `<div>${data.html ?? escape(data.text)}</div>`,
                item: (data, escape) => `<div>${escape(data.text)}</div>`,
            } : {
                no_results: () => '<div class="no-results">&hellip;</div>',
            },
            onItemAdd: (value) => {
                if (String(value) === '0') {
                    this.ts.removeItem(value, true);
                    this.ts.clearOptions();
                    this.ts.close();
                    this.element.dispatchEvent(new CustomEvent('entity-select:new', {
                        bubbles: true,
                        detail: { term: this.lastQuery, element: this.element },
                    }));
                }
            },
        });

        // Si cambia un campo del que depende este (req_params), se vacía la selección
        this.dependencies = Object.values(this.reqParamsValue || {}).map((name) => document.querySelector(`[name="${name}"]`)).filter(Boolean);
        this.onDependencyChange = () => this.ts.clear();
        this.dependencies.forEach((el) => el.addEventListener('change', this.onDependencyChange));
    }

    disconnect() {
        this.dependencies?.forEach((el) => el.removeEventListener('change', this.onDependencyChange));
        this.ts?.destroy();
    }

    // Añade una opción y la selecciona (la usa el modal de alta tras crear el registro)
    select(id, text) {
        this.ts.addOption({ id: String(id), text });
        this.ts.addItem(String(id));
    }

    async load(query, callback) {
        this.lastQuery = query;
        const params = new URLSearchParams({ q: query, field_name: this.fieldNameValue });
        if (this.classTypeValue) params.set('class_type', this.classTypeValue);
        if (this.scrollValue) params.set('page', '1');
        Object.entries(this.reqParamsValue || {}).forEach(([key, name]) => {
            const field = document.querySelector(`[name="${name}"]`);
            params.set(key, field ? field.value : '');
        });
        Object.entries(this.queryParametersValue || {}).forEach(([key, value]) => {
            if (!params.has(key)) params.set(key, value);
        });

        try {
            const response = await fetch(`${this.urlValue}${this.urlValue.includes('?') ? '&' : '?'}${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) throw new Error(response.statusText);
            const data = await response.json();
            const results = Array.isArray(data) ? data : (data.results ?? []);
            this.ts.clearOptions();
            callback(results.map((item) => ({ ...item, id: String(item.id) })));
        } catch (e) {
            callback();
        }
    }
}
