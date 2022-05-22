$(function ()
{
    function projectChange()
    {
        var form = $(this).closest('form');
        var data = {};
        workcenter = $("#contact_educational_tutor_report_workcenter");

        var checked = [];
        $("#contact_educational_tutor_report_projects input:checked").each(function () {
            checked.push($(this).val());
        });
        data['contact_educational_tutor_report[projects][]'] = checked;

        var next = workcenter.next();
        $('#contact_educational_tutor_report_workcenter').replaceWith('<div id="contact_educational_tutor_report_workcenter"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#contact_educational_tutor_report_workcenter').replaceWith(
                    $(html).find('#contact_educational_tutor_report_workcenter')
                );
                $('select#contact_educational_tutor_report_workcenter').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#contact_educational_tutor_report_workcenter').replaceWith('<div id="contact_educational_tutor_report_workcenter"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var projects = $("#contact_educational_tutor_report_projects");

    projects.change(projectChange);
});
