import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

/*
 * Guarda en la URL el estado (filtro «q» y página «page») de un listado que es un Live Component, para que
 * recargar, compartir el enlace o volver con el botón «Atrás» del navegador (desde una ficha, por ejemplo)
 * deje el listado como estaba. Mismo patrón que document_tree_url_controller.js de ÁTICA Calidad.
 *
 *  - Tras cada renderizado del componente, compara su estado con la URL:
 *      · si cambia la página (o el filtro pasa de vacío a tener texto) añade una entrada al historial (pushState);
 *      · si solo se está editando el filtro, reemplaza la entrada actual (replaceState), para no llenar el
 *        historial con una entrada por pulsación de tecla.
 *  - Al volver/avanzar (popstate) lee la URL y llama a la acción syncFromUrl del componente. Como después la URL
 *    ya coincide con el estado, no se añade ninguna entrada nueva.
 *  - Al conectar, normaliza la URL de entrada (p. ej. /listar/2) a la forma «base?q=...&page=...».
 *
 * Valores: baseUrl (ruta sin parámetros). Los componentes deben tener las propiedades q y page y la acción syncFromUrl.
 */
export default class extends Controller {
    static values = { baseUrl: String };

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

    stateUrl(q, page) {
        const url = new URL(this.baseUrlValue || window.location.pathname, window.location.origin);
        if (q) url.searchParams.set('q', q);
        if (page > 1) url.searchParams.set('page', String(page));
        return url;
    }

    urlState() {
        const params = new URL(window.location.href).searchParams;
        return { q: (params.get('q') ?? '').trim(), page: Math.max(1, parseInt(params.get('page') ?? '1', 10) || 1) };
    }

    async currentState() {
        const component = await getComponent(this.element);
        return { q: String(component.getData('q') ?? '').trim(), page: Number(component.getData('page')) || 1 };
    }

    async normalize() {
        const state = await this.currentState();
        const url = this.stateUrl(state.q, state.page);
        if (url.href !== window.location.href) {
            window.history.replaceState(window.history.state, '', url);
        }
    }

    async pushState() {
        const state = await this.currentState();
        const url = this.stateUrl(state.q, state.page);
        if (url.href === window.location.href) return;

        const previous = this.urlState();
        // solo cambia el texto del filtro y ya había filtro: se está escribiendo, se reemplaza la entrada
        const typing = state.page === previous.page && previous.q !== '' && state.q !== '';
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
