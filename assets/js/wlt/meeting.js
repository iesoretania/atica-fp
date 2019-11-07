$(function ()
{
    function createdByChange()
    {
        var form = $(this).closest('form');
        var data = {};

        project = $("#meeting_project");
        var studentEnrollments = $("#meeting_studentEnrollments");
        var teachers = $("#meeting_teachers");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[createdBy.attr('name')] = createdBy.val();

        var next = project.next();
        $('#meeting_project').replaceWith('<div id="meeting_project"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = studentEnrollments.next();
        $('#meeting_studentEnrollments').replaceWith('<div id="meeting_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = teachers.next();
        $('#meeting_teachers').replaceWith('<div id="meeting_teachers"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#meeting_project').replaceWith(
                    $(html).find('#meeting_project')
                );
                $('select#meeting_project').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#meeting_studentEnrollments').replaceWith(
                    $(html).find('#meeting_studentEnrollments')
                );
                $('select#meeting_studentEnrollments').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#meeting_teachers').replaceWith(
                    $(html).find('#meeting_teachers')
                );
                $('select#meeting_teachers').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#meeting_project').change(projectChange);
            },
            error: function () {
                $('#meeting_project').replaceWith('<div id="meeting_project"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#meeting_studentEnrollments').replaceWith('<div id="meeting_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#meeting_teachers').replaceWith('<div id="meeting_teachers"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function projectChange()
    {
        var form = $(this).closest('form');
        var data = {};

        var studentEnrollments = $("#visit_studentEnrollments");

        project = $("#meeting_project");
        var studentEnrollments = $("#meeting_studentEnrollments");
        var teachers = $("#meeting_teachers");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[createdBy.attr('name')] = createdBy.val();
        data[project.attr('name')] = project.val();

        var next = studentEnrollments.next();
        $('#meeting_studentEnrollments').replaceWith('<div id="meeting_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = teachers.next();
        $('#meeting_teachers').replaceWith('<div id="meeting_teachers"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#meeting_studentEnrollments').replaceWith(
                    $(html).find('#meeting_studentEnrollments')
                );
                $('select#meeting_studentEnrollments').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#meeting_teachers').replaceWith(
                    $(html).find('#meeting_teachers')
                );
                $('select#meeting_teachers').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#meeting_studentEnrollments').replaceWith('<div id="meeting_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#meeting_teachers').replaceWith('<div id="meeting_teachers"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    var date = $("#meeting_dateTime_date");
    var time = $("#meeting_dateTime_time");
    var createdBy = $("#meeting_createdBy");
    var project = $("#meeting_project");

    date.change(createdByChange);
    createdBy.change(createdByChange);
    project.change(projectChange);
});
