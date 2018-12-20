$(function ()
{
    function academicYearChange()
    {
        var form = $(this).closest('form');
        var data = {};

        training = $("#learning_program_import_training");
        var next = training.next();

        data[academicYear.attr('name')] = academicYear.val();
        $('#learning_program_import_training').replaceWith('<div id="learning_program_import_training"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#learning_program_import_training').replaceWith(
                    $(html).find('#learning_program_import_training')
                );
                $('select#learning_program_import_training').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#learning_program_import_training').replaceWith('<div id="learning_program_import_training"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    var academicYear = $("#learning_program_import_academicYear");

    academicYear.change(academicYearChange);
});
