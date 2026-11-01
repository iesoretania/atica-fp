import { Controller } from '@hotwired/stimulus';

/*
 * Campos que dependen de otros (empresa → centro de trabajo; profesor → centro → convenios → alumnado).
 * Sustituye a los scripts de jQuery de wpt/agreement.js y wpt/visit.js.
 *
 * El servidor ya devuelve el formulario completo con los campos recalculados; este controlador envía los valores
 * indicados, recibe el HTML y sustituye en la página solo los campos «replace» (por id).
 *
 * Reglas (JSON en data-dependent-fields-rules-value), en orden de cascada:
 *   { "on": ["#visit_teacher"], "send": ["#visit_teacher", ...], "replace": ["#visit_workcenter", ...],
 *     "selectFirst": "#visit_workcenter", "sendChecked": "#visit_agreements" }
 *   - on: selectores de los campos que lanzan la regla.
 *   - send: campos cuyos valores se envían (los que son casillas/lista de casillas envían solo las marcadas).
 *   - replace: ids de los campos que se sustituyen por los del HTML recibido.
 *   - selectFirst: tras sustituir, se elige la primera opción real de ese selector y se lanza su regla.
 */
export default class extends Controller {
    static values = { url: String, rules: Array };

    connect() {
        this.listeners = [];
        this.rulesValue.forEach((rule, index) => {
            rule.on.forEach((selector) => {
                this.element.querySelectorAll(selector).forEach((el) => {
                    const handler = () => this.run(index);
                    el.addEventListener('change', handler);
                    this.listeners.push([el, handler]);
                });
            });
        });
    }

    disconnect() {
        this.listeners.forEach(([el, handler]) => el.removeEventListener('change', handler));
    }

    async run(index) {
        const rule = this.rulesValue[index];
        const form = this.element.tagName === 'FORM' ? this.element : this.element.closest('form');
        const body = new URLSearchParams();
        rule.send.forEach((selector) => {
            const el = this.element.querySelector(selector);
            if (!el) return;
            const name = el.getAttribute('name');
            if (name) {
                body.append(name, el.value);
            } else {
                // contenedor de casillas: solo las marcadas
                el.querySelectorAll('input:checked').forEach((box) => body.append(box.name, box.value));
            }
        });

        rule.replace.forEach((selector) => this.setState(selector, 'loading'));
        try {
            // sin URL indicada, se pide a la propia página (el servidor recalcula el formulario con los valores enviados)
            const response = await fetch(this.urlValue || form?.dataset.ajax || window.location.href, {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            });
            if (!response.ok) throw new Error(response.statusText);
            const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
            rule.replace.forEach((selector) => {
                const fresh = doc.querySelector(selector);
                const current = this.element.querySelector(selector);
                if (fresh && current) current.replaceWith(fresh);
            });
            this.connectAgain();
            if (rule.selectFirst) {
                const select = this.element.querySelector(rule.selectFirst);
                const option = select?.options[1];
                if (option) {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        } catch (e) {
            rule.replace.forEach((selector) => this.setState(selector, 'error'));
        }
    }

    // Los campos sustituidos son nodos nuevos: se vuelven a enlazar los que lanzan reglas
    connectAgain() {
        this.disconnect();
        this.connect();
    }

    setState(selector, state) {
        const el = this.element.querySelector(selector);
        if (!el) return;
        el.classList.toggle('opacity-50', state === 'loading');
        el.classList.toggle('ring-red-500', state === 'error');
        if (state === 'loading') el.setAttribute('disabled', 'disabled');
    }
}
