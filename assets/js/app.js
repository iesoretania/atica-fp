import 'bootswatch/dist/yeti/bootstrap.min.css';
import 'select2/dist/css/select2.min.css';
import 'select2-theme-bootstrap4/dist/select2-bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/fontawesome.min.css';
import '@fortawesome/fontawesome-free/css/solid.min.css';
import '../css/app.css';

import $ from './jquery_global.js';
import 'bootstrap';
import './select2_es.js';

$(document).ready(function() {
    $('select:not(.ignore_select2)').select2({
        theme: "bootstrap",
        language: "es"
    });
    $('[data-toggle="popover"]').popover();
});

// El editor de texto enriquecido (Quill) solo se descarga en las páginas que lo usan
if (document.querySelector('textarea.rich-editor')) {
    import('./rich_editor.js');
}

var file = document.querySelector('.custom-file-input');

if (file) {
    file.addEventListener('change', function (e) {
        var fileName = e.target.files[0].name;
        var nextSibling = e.target.nextElementSibling
        nextSibling.innerText = fileName
    })
}
