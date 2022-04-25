$(function ()
{
    function workcenterChange()
    {
        var form = $(this).closest('form');
        var data = {};

        projects = $("#contact_projects");
        var studentEnrollments = $("#contact_studentEnrollments");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();

        var next = projects.next();
        $('#contact_projects').replaceWith('<div id="contact_projects"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = studentEnrollments.next();
        $('#contact_studentEnrollments').replaceWith('<div id="contact_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#contact_projects').replaceWith(
                    $(html).find('#contact_projects')
                );
                $('select#contact_projects').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#contact_studentEnrollments').replaceWith(
                    $(html).find('#contact_studentEnrollments')
                );
                $('select#contact_studentEnrollments').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#contact_projects input').change(projectsChange);
            },
            error: function () {
                $('#contact_projects').replaceWith('<div id="contact_projects"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#contact_studentEnrollments').replaceWith('<div id="contact_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function projectsChange()
    {
        var form = $(this).closest('form');
        var data = {};

        var studentEnrollments = $("#contact_studentEnrollments");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();
        var checked = [];
        $("#contact_projects input:checked").each(function () {
            checked.push($(this).val());
        });
        data['contact[projects][]'] = checked;

        var next = studentEnrollments.next();
        $('#contact_studentEnrollments').replaceWith('<div id="contact_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#contact_studentEnrollments').replaceWith(
                    $(html).find('#contact_studentEnrollments')
                );
                $('select#contact_studentEnrollments').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#contact_studentEnrollments').replaceWith('<div id="contact_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var teacher = $("#contact_teacher");
    var workcenter = $("#contact_workcenter");
    var projects = $("#contact_projects");
    var date = $("#contact_dateTime_date");
    var time = $("#contact_dateTime_time");

    date.change(workcenterChange);
    workcenter.change(workcenterChange);
    $('#contact_projects input').change(projectsChange);
});
