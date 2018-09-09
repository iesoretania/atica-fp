require('../css/app.scss');

const $ = require('jquery');

require('bootstrap');
require('select2');

$(document).ready(function() {
    $('select').select2({
        theme: "bootstrap",
        language: "es"
    });
    $('[data-toggle="popover"]').popover();
});
