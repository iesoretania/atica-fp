$(function () {
    function createSelectables()
    {
        $("button#pselect_all").click(function (e) {
            $("." + $(e.currentTarget).attr('data-toggle')).prop('checked', true);
        });

        $("button#pselect_none").click(function (e) {
            $("." + $(e.currentTarget).attr('data-toggle')).prop('checked', false);
        });

        var learningOutcomes = $("#activity_learningOutcomes input");
        learningOutcomes.change(learningOutcomesChange);
    }

    function prepareForm() {
        var form = $(this).closest('form');
        var data = {};

        data['activity[code]'] = $('#activity_code').val();
        data['activity[name]'] = $('#activity_name').val();
        data['activity[description]'] = $('#activity_description').val();
        var checked = [];
        $("#activity_subjects input:checked").each(function () {
            checked.push($(this).val());
        });
        data['activity[subjects][]'] = checked;

        checked = [];
        $("#activity_learningOutcomes input:checked").each(function () {
            checked.push($(this).val());
        });
        data['activity[learningOutcomes][]'] = checked;

        checked = [];
        $("#activity_criteria input:checked").each(function () {
            checked.push($(this).val());
        });
        data['activity[criteria][]'] = checked;
        return {form, data};
    }

    function subjectsChange()
    {
        var {form, data} = prepareForm.call(this);

        $('#activity_learningOutcomes').addClass('loading');
        $('#activity_criteria').addClass('loading');
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#activity_learningOutcomes').replaceWith(
                    $(html).find('#activity_learningOutcomes')
                );
                $('#activity_criteria').replaceWith(
                    $(html).find('#activity_criteria')
                );
                createSelectables();
            },
            error: function () {
                $('#activity_learningOutcomes').replaceWith('<div id="activity_learningOutcomes"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#activity_criteria').replaceWith('<div id="activity_criteria"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function learningOutcomesChange()
    {
        var {form, data} = prepareForm.call(this);

        $('#activity_criteria').addClass('loading');
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#activity_criteria').replaceWith(
                    $(html).find('#activity_criteria')
                );
                createSelectables();
            },
            error: function () {
                $('#activity_criteria').replaceWith('<div id="activity_criteria"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var subjects = $("#activity_subjects input");
    subjects.change(subjectsChange);
    createSelectables();
});
