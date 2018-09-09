require('select2');
require('select2/dist/js/i18n/es');

$(document).ready(function() {
    $('select').select2({
        theme: "bootstrap",
        language: "es"
    });

    var anchor = $('#element_template');

    anchor.on('change', function () {
        if (anchor.val() !== '') {
            var name = $("#element_name");
            var description = $("#element_description");
            var old_name = name.clone();
            var old_description = description.clone();
            name.replaceWith('<div id="element_name"><span class="text-info"><i class="fas fa-spinner fa-pulse fa-3x"></i></span></div>');
            description.replaceWith('<div id="element_description"><span class="text-info"><i class="fas fa-spinner fa-pulse fa-3x"></i></span></div>');
            var link = $('a#template-link').attr('href');
            link = link.replace('0', anchor.val());
            $.ajax({
                url: link,
                type: 'GET',
                success: function (html) {
                    $("#element_name").replaceWith(old_name);
                    $("#element_name").val($(html).find('#element_template_name').val());
                    $("#element_description").replaceWith(old_description);
                    $("#element_description").val($(html).find('#element_template_description').val());
                },
                error: function () {
                    $("#element_name").replaceWith(old_name);
                    $("#element_description").replaceWith(old_description);
                }
            });
        }
    });
});
