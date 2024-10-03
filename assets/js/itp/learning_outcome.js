$(function () {
    function createSelectables()
    {
        $("button#pselect_all").click(function (e) {
            $("." + $(e.currentTarget).attr('data-toggle')).prop('checked', true);
        });

        $("button#pselect_none").click(function (e) {
            $("." + $(e.currentTarget).attr('data-toggle')).prop('checked', false);
        });
    }

    function subjectsChange()
    {
        var form = $(this).closest('form');
        var data = {};

        data['program_grade[targetHours]'] = $('#program_grade_targetHours').val();
        var checked = [];
        $("#program_grade_subjects input:checked").each(function () {
            checked.push($(this).val());
        });
        data['program_grade[subjects][]'] = checked;

        $("#learning_outcomes input[type='radio']:checked").each(function () {
            data[$(this).attr('name')] = $(this).val();
        });

        //$('#learning_outcomes').replaceWith('<div id="learning_outcomes"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        $('#learning_outcomes').addClass('loading');
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#learning_outcomes').replaceWith(
                    $(html).find('#learning_outcomes')
                );
                createSelectables();
            },
            error: function () {
                $('#learning_outcomes').replaceWith('<div id="learning_outcomes"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var subjects = $("#program_grade_subjects input");
    subjects.change(subjectsChange);
    createSelectables();
});
