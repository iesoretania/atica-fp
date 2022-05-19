$(function ()
{
    function workcenterChange()
    {
        var form = $(this).closest('form');
        var data = {};

        projects = $("#contact_educational_tutor_report_projects");

        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();

        var next = projects.next();
        $('#contact_educational_tutor_report_projects').replaceWith('<div id="contact_educational_tutor_report_projects"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#contact_educational_tutor_report_projects').replaceWith(
                    $(html).find('#contact_educational_tutor_report_projects')
                );
                $('select#contact_educational_tutor_report_projects').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#contact_educational_tutor_report_projects').replaceWith('<div id="contact_educational_tutor_report_projects"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var teacher = $("#contact_educational_tutor_report_teacher");
    var workcenter = $("#contact_educational_tutor_report_workcenter");
    var projects = $("#contact_educational_tutor_report_projects");

    workcenter.change(workcenterChange);
});
