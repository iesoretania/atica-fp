require('../css/app.scss');

const $ = require('jquery');
global.$ = global.jQuery = $;

require('bootstrap');
require('select2');
require('select2/dist/js/i18n/es');

$(document).ready(function() {
    $('select').select2({
        theme: "bootstrap",
        language: "es"
    });
    $('[data-toggle="popover"]').popover();
});

document.querySelector('.custom-file-input').addEventListener('change',function(e){
    var fileName = e.target.files[0].name;
    var nextSibling = e.target.nextElementSibling
    nextSibling.innerText = fileName
})
