import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/*
 * Guarda en la URL el estado de un listado que es un Live Component (filtro «q», página «page», orden «sort»/«dir» y
 * filtros propios «f_nombre»), para que recargar, compartir el enlace o volver con el botón «Atrás» del navegador
 * (desde una ficha, por ejemplo) deje el listado como estaba. Mismo patrón que document_tree_url_controller.js de
 * ÁTICA Calidad.
 *
 *  - Tras cada renderizado del componente, compara su estado con la URL:
 *      · si cambia la página, el orden o un filtro propio (o el texto pasa de vacío a tener contenido) añade una
 *        entrada al historial (pushState);
 *      · si solo se está editando el texto del filtro, reemplaza la entrada actual (replaceState), para no llenar el
 *        historial con una entrada por pulsación de tecla.
 *  - Al volver/avanzar (popstate) lee la URL y llama a la acción syncFromUrl del componente. Como después la URL
 *    ya coincide con el estado, no se añade ninguna entrada nueva.
 *  - Al conectar, normaliza la URL de entrada (p. ej. /listar/2) a la forma «base?q=...&page=...».
 *
 * Valor: baseUrl (ruta sin parámetros). Las claves de los filtros propios se leen de los controles data-model="filters.x"
 * del componente. Los componentes deben tener las propiedades q, page, sort, dir y filters y la acción syncFromUrl.
 */
const FILTER_PREFIX = 'f_';

export default class extends Controller {
    static values = { baseUrl: String };

    // claves de los filtros propios: las de los controles data-model="filters.clave" que dibuja el componente
    get filterKeys() {
        return [...this.element.querySelectorAll('[data-model^="filters."]')].map((el) => el.dataset.model.slice('filters.'.length));
    }

    async connect() {
        this.onRendered = () => this.pushState();
        this.onPopState = () => this.syncFromUrl();
        window.addEventListener('popstate', this.onPopState);

        // el componente Live no emite un evento DOM al repintar: se usa su hook «render:finished»
        this.component = await getComponent(this.element);
        this.component.on('render:finished', this.onRendered);
        this.normalize();
    }

    disconnect() {
        window.removeEventListener('popstate', this.onPopState);
        this.component?.off('render:finished', this.onRendered);
    }

    stateUrl(state) {
        const url = new URL(this.baseUrlValue || window.location.pathname, window.location.origin);
        if (state.q) url.searchParams.set('q', state.q);
        if (state.page > 1) url.searchParams.set('page', String(state.page));
        if (state.sort) {
            url.searchParams.set('sort', state.sort);
            if (state.dir === 'desc') url.searchParams.set('dir', 'desc');
        }
        Object.entries(state.filters).forEach(([key, value]) => {
            if (value !== '') url.searchParams.set(FILTER_PREFIX + key, value);
        });
        return url;
    }

    urlState() {
        const params = new URL(window.location.href).searchParams;
        const filters = {};
        this.filterKeys.forEach((key) => {
            const value = params.get(FILTER_PREFIX + key);
            if (value) filters[key] = value;
        });
        return {
            q: (params.get('q') ?? '').trim(),
            page: Math.max(1, parseInt(params.get('page') ?? '1', 10) || 1),
            sort: params.get('sort') ?? '',
            dir: params.get('dir') === 'desc' ? 'desc' : 'asc',
            filters,
        };
    }

    async currentState() {
        const component = await getComponent(this.element);
        const filters = {};
        const data = component.getData('filters') ?? {};
        this.filterKeys.forEach((key) => {
            // OJO: con un array vacío, data['shift'] sería Array.prototype.shift; solo cuentan las propiedades propias
            if (!Object.prototype.hasOwnProperty.call(data, key)) return;
            const value = data[key];
            if (value !== undefined && value !== null && String(value) !== '') filters[key] = String(value);
        });
        const sort = String(component.getData('sort') ?? '');
        return {
            q: String(component.getData('q') ?? '').trim(),
            page: Number(component.getData('page')) || 1,
            sort,
            dir: sort && component.getData('dir') === 'desc' ? 'desc' : 'asc',
            filters,
        };
    }

    async normalize() {
        const url = this.stateUrl(await this.currentState());
        if (url.href !== window.location.href) {
            window.history.replaceState(window.history.state, '', url);
        }
    }

    async pushState() {
        const state = await this.currentState();
        const url = this.stateUrl(state);
        if (url.href === window.location.href) return;

        const previous = this.urlState();
        // solo cambia el texto del filtro y ya había texto: se está escribiendo, se reemplaza la entrada
        const sameOther = state.page === previous.page && state.sort === previous.sort && state.dir === previous.dir
            && JSON.stringify(state.filters) === JSON.stringify(previous.filters);
        const typing = sameOther && previous.q !== '' && state.q !== '';
        if (typing) {
            window.history.replaceState(window.history.state, '', url);
        } else {
            window.history.pushState(window.history.state, '', url);
        }
    }

    async syncFromUrl() {
        const target = this.urlState();
        const component = await getComponent(this.element);
        component.action('syncFromUrl', target, 0);
    }
}
