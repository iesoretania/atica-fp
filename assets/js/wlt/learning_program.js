$(function ()
{
    function academicYearChange()
    {
        var form = $(this).closest('form');
        var data = {};

        training = $("#training");

        var next = training.next();

        data[academicYear.attr('name')] = academicYear.val();
        data[training.attr('name')] = training.val();
        $('#learning_program_training').replaceWith('<div id="learning_program_training"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#learning_program_training').replaceWith(
                    $(html).find('#learning_program_training')
                );
                $('select#learning_program_training').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#learning_program_training').change(trainingChange);
            },
            error: function () {
                $('#learning_program_training').replaceWith('<div id="learning_program_training"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    function trainingChange()
    {
        training = $("#learning_program_training");

        var form = $(this).closest('form');
        var data = {};
        data[academicYear.attr('name')] = academicYear.val();
        data[company.attr('name')] = company.val();
        data[training.attr('name')] = training.val();
        var activityRealizations = $('#learning_program_activityRealizations');
        var next = activityRealizations.next();
        activityRealizations.replaceWith('<div id="learning_program_activityRealizations"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('action'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#learning_program_activityRealizations').replaceWith(
                    $(html).find('#learning_program_activityRealizations')
                );
                $('select#learning_program_activityRealizations').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#learning_program_activityRealizations').replaceWith('<div id="agreement_activityRealizations"><span class="text-danger"><i class="fa fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    var academicYear = $("#learning_program_academicYear");
    var company = $("#learning_program_company");
    var training = $("#learning_program_training");

    academicYear.change(academicYearChange);
    training.change(trainingChange);
});
