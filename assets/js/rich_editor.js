// Sustituye los <textarea class="rich-editor"> (RichTextType) por un editor Quill.
// El HTML resultante se vuelca en el textarea original, que sigue siendo el campo enviado.
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

const EMPTY_CONTENT = '<p><br></p>';

document.querySelectorAll('textarea.rich-editor').forEach(function (textarea) {
    const required = textarea.required;
    const rows = parseInt(textarea.getAttribute('rows'), 10) || 8;

    const container = document.createElement('div');
    container.className = 'rich-editor-container';
    const editor = document.createElement('div');
    editor.style.minHeight = (rows * 1.5) + 'em';
    container.appendChild(editor);
    textarea.insertAdjacentElement('afterend', container);

    // El navegador no puede validar un campo oculto: la obligatoriedad se comprueba al enviar
    textarea.required = false;
    textarea.hidden = true;

    const quill = new Quill(editor, {
        theme: 'snow',
        formats: ['header', 'bold', 'italic', 'underline', 'strike', 'blockquote', 'list', 'link'],
        modules: {
            toolbar: [
                [{header: [2, 3, false]}],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote'],
                [{list: 'ordered'}, {list: 'bullet'}],
                ['link'],
                ['clean']
            ]
        }
    });

    const stored = textarea.value.trim();
    if (stored) {
        quill.clipboard.dangerouslyPasteHTML(stored);
    }

    const sync = function () {
        const html = quill.root.innerHTML;
        textarea.value = html === EMPTY_CONTENT ? '' : html;
        container.classList.toggle('is-invalid', false);
    };
    sync();
    quill.on('text-change', sync);

    const form = textarea.closest('form');
    if (form) {
        form.addEventListener('submit', function (ev) {
            sync();
            if (required && textarea.value === '') {
                ev.preventDefault();
                container.classList.add('is-invalid');
                quill.focus();
            }
        });
    }
});
