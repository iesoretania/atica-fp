require('select2');
require('select2/dist/js/i18n/es');

$(document).ready(function() {
    $('select').select2({
        theme: "bootstrap",
        language: "es"
    });

    var item = $('#ticket_element');
    var anchor = $('#ticket_location');

    anchor.on('change', function () {
        var location = $("#ticket_location");
        var form = $(location).closest('form');
        var data = {};
        data[location.attr('name')] = location.val();
        item.next().remove();
        item.replaceWith('<div id="ticket_element"><span class="text-info"><i class="fas fa-spinner fa-pulse fa-3x"></i></span></div>');
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#ticket_element').replaceWith(
                    $(html).find('#ticket_element')
                );
                $('select#ticket_element').select2({
                    theme: "bootstrap"
                })
            },
            error: function () {
                item.replaceWith('<div id="ticket_element"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>')
            }
        });
    });
});
