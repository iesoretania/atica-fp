require('../css/app.scss');

const $ = require('jquery');

require('bootstrap');
require('select2');


$(document).ready(function() {
    $('[data-toggle="popover"]').popover();
});

$('select').select2({
    theme: "bootstrap",
    language: "es"
});
