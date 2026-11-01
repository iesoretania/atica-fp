import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const EMPTY_CONTENT = '<p><br></p>';

/*
 * Editor de texto enriquecido (Quill) para los campos RichTextType. El HTML resultante se vuelca en el <textarea>
 * original, que sigue siendo el campo que se envía. Sin JavaScript queda un textarea normal.
 * Dentro de un Live Component el campo debe ir en un elemento con data-live-ignore (el editor crea su propio DOM).
 */
export default class extends Controller {
    connect() {
        const textarea = this.element;
        this.required = textarea.required;
        const rows = parseInt(textarea.getAttribute('rows'), 10) || 8;

        this.container = document.createElement('div');
        this.container.className = 'rich-editor-container';
        const editor = document.createElement('div');
        editor.style.minHeight = (rows * 1.5) + 'em';
        this.container.appendChild(editor);
        textarea.insertAdjacentElement('afterend', this.container);

        // El navegador no puede validar un campo oculto: la obligatoriedad se comprueba al enviar
        textarea.required = false;
        textarea.hidden = true;

        this.quill = new Quill(editor, {
            theme: 'snow',
            formats: ['header', 'bold', 'italic', 'underline', 'strike', 'blockquote', 'list', 'link'],
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
            },
        });

        const stored = textarea.value.trim();
        if (stored) {
            this.quill.clipboard.dangerouslyPasteHTML(stored);
        }

        this.sync = () => {
            const html = this.quill.root.innerHTML;
            textarea.value = html === EMPTY_CONTENT ? '' : html;
            this.container.classList.remove('is-invalid');
        };
        this.sync();
        this.quill.on('text-change', this.sync);

        this.form = textarea.closest('form');
        this.onSubmit = (event) => {
            this.sync();
            if (this.required && textarea.value === '') {
                event.preventDefault();
                this.container.classList.add('is-invalid');
                this.quill.focus();
            }
        };
        this.form?.addEventListener('submit', this.onSubmit);
    }

    disconnect() {
        this.form?.removeEventListener('submit', this.onSubmit);
        this.container?.remove();
        this.element.hidden = false;
    }
}
