$(function ()
{
    function workcenterChange()
    {
        var form = $(this).closest('form');
        var data = {};

        projects = $("#visit_projects");
        var studentEnrollments = $("#visit_studentEnrollments");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();

        var next = projects.next();
        $('#visit_projects').replaceWith('<div id="visit_projects"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = studentEnrollments.next();
        $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#visit_projects').replaceWith(
                    $(html).find('#visit_projects')
                );
                $('select#visit_projects').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#visit_studentEnrollments').replaceWith(
                    $(html).find('#visit_studentEnrollments')
                );
                $('select#visit_studentEnrollments').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#visit_projects input').change(projectsChange);
            },
            error: function () {
                $('#visit_projects').replaceWith('<div id="visit_projects"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function projectsChange()
    {
        var form = $(this).closest('form');
        var data = {};

        var studentEnrollments = $("#visit_studentEnrollments");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();
        var checked = [];
        $("#visit_projects input:checked").each(function () {
            checked.push($(this).val());
        });
        data['visit[projects][]'] = checked;

        var next = studentEnrollments.next();
        $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#visit_studentEnrollments').replaceWith(
                    $(html).find('#visit_studentEnrollments')
                );
                $('select#visit_studentEnrollments').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var teacher = $("#visit_teacher");
    var workcenter = $("#visit_workcenter");
    var projects = $("#visit_projects");
    var date = $("#visit_dateTime_date");
    var time = $("#visit_dateTime_time");

    date.change(workcenterChange);
    workcenter.change(workcenterChange);
    $('#visit_projects input').change(projectsChange);
});
