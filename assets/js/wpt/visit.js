$(function ()
{
    function teacherChange()
    {
        var form = $(this).closest('form');
        var data = {};

        teacher = $("#visit_teacher");
        workcenter = $("#visit_workcenter");
        agreements = $("#visit_agreements");
        studentEnrollments = $("#visit_studentEnrollments");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();

        var next = workcenter.next();
        $('#visit_workcenter').replaceWith('<div id="visit_workcenter"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = agreements.next();
        $('#visit_agreements').replaceWith('<div id="visit_agreements"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = studentEnrollments.next();
        $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#visit_workcenter').replaceWith(
                    $(html).find('#visit_workcenter')
                );
                $('select#visit_workcenter').select2({
                    theme: "bootstrap",
                    language: 'es'
                }).val($("#visit_workcenter option:nth-child(2)").val()).trigger('change.select2');
                workcenterChange();
            },
            error: function () {
                $('#visit_workcenter').replaceWith('<div id="visit_workcenter"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#visit_agreements').replaceWith('<div id="visit_agreements"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function workcenterChange()
    {
        var form = $(this).closest('form');
        var data = {};

        agreements = $("#visit_agreements");
        studentEnrollments = $("#visit_studentEnrollments");
        workcenter = $("#visit_workcenter");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();

        var next = agreements.next();
        $('#visit_agreements').replaceWith('<div id="visit_agreements"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = studentEnrollments.next();
        $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        console.log(form);
        $.ajax({
            url: form.attr('data-ajax'),
            type: 'post',
            data: data,
            success: function (html) {
                $('#visit_agreements').replaceWith(
                    $(html).find('#visit_agreements')
                );
                $('select#visit_agreements').select2({
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
                $('#visit_agreements input').change(agreementsChange);
            },
            error: function () {
                $('#visit_agreements').replaceWith('<div id="visit_agreements"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#visit_studentEnrollments').replaceWith('<div id="visit_studentEnrollments"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function agreementsChange()
    {
        var form = $(this).closest('form');
        var data = {};

        var studentEnrollments = $("#visit_studentEnrollments");

        data[date.attr('name')] = date.val();
        data[time.attr('name')] = time.val();
        data[teacher.attr('name')] = teacher.val();
        data[workcenter.attr('name')] = workcenter.val();
        var checked = [];
        $("#visit_agreements input:checked").each(function () {
            checked.push($(this).val());
        });
        data['visit[agreements][]'] = checked;

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
    var agreements = $("#visit_agreements");
    var studentEnrollments = $("#visit_studentEnrollments");
    var date = $("#visit_dateTime_date");
    var time = $("#visit_dateTime_time");

    teacher.change(teacherChange);
    $('#visit_workcenter input').change(workcenterChange);
    $('#visit_agreements input').change(agreementsChange);
});
