$(function ()
{
    function projectChange()
    {
        var form = $(this).closest('form');
        var data = {};

        studentEnrollment = $("#agreement_studentEnrollment");
        company = $("#agreement_company");
        var workcenter = $("#agreement_workcenter");
        var activityRealizations = $("#agreement_activityRealizations");

        data[project.attr('name')] = project.val();
        data[studentEnrollment.attr('name')] = studentEnrollment.val();
        data[company.attr('name')] = company.val();
        data[workcenter.attr('name')] = workcenter.val();
        data[workTutor.attr('name')] = workTutor.val();

        var next = studentEnrollment.next();
        $('#agreement_studentEnrollment').replaceWith('<div id="agreement_studentEnrollment"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = company.next();
        $('#agreement_company').replaceWith('<div id="agreement_company"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = workcenter.next();
        $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = activityRealizations.next();
        $('#agreement_activityRealizations').replaceWith('<div id="agreement_activityRealizations"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#agreement_studentEnrollment').replaceWith(
                    $(html).find('#agreement_studentEnrollment')
                );
                $('select#agreement_studentEnrollment').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_company').replaceWith(
                    $(html).find('#agreement_company')
                );
                $('select#agreement_company').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_workcenter').replaceWith(
                    $(html).find('#agreement_workcenter')
                );
                $('select#agreement_workcenter').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_activityRealizations').replaceWith(
                    $(html).find('#agreement_activityRealizations')
                );
                $('select#agreement_activityRealizations').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_studentEnrollment').change(studentEnrollmentChange);
                $('#agreement_company').change(companyChange);
            },
            error: function () {
                $('#agreement_studentEnrollment').replaceWith('<div id="agreement_studentEnrollment"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#agreement_company').replaceWith('<div id="agreement_company"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#agreement_activityRealizations').replaceWith('<div id="agreement_activityRealizations"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function studentEnrollmentChange()
    {
        studentEnrollment = $("#agreement_studentEnrollment");
        company = $("#agreement_company");
        var workcenter = $("#agreement_workcenter");
        var activityRealizations = $("#agreement_activityRealizations");

        var form = $(this).closest('form');
        var data = {};
        data[project.attr('name')] = project.val();
        data[studentEnrollment.attr('name')] = studentEnrollment.val();
        data[company.attr('name')] = company.val();
        data[workcenter.attr('name')] = workcenter.val();
        data[workTutor.attr('name')] = workTutor.val();

        var next = company.next();
        company.replaceWith('<div id="agreement_company"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = workcenter.next();
        workcenter.replaceWith('<div id="agreement_workcenter"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = activityRealizations.next();
        activityRealizations.replaceWith('<div id="agreement_activityRealizations"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#agreement_company').replaceWith(
                    $(html).find('#agreement_company')
                );
                $('select#agreement_company').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_workcenter').replaceWith(
                    $(html).find('#agreement_workcenter')
                );
                $('select#agreement_workcenter').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_activityRealizations').replaceWith(
                    $(html).find('#agreement_activityRealizations')
                );
                $('select#agreement_activityRealizations').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_company').change(companyChange);
            },
            error: function () {
                $('#agreement_company').replaceWith('<div id="agreement_company"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>');
                $('#agreement_activityRealizations').replaceWith('<div id="agreement_activityRealizations"><span class="text-danger"><i class="fa fa-times-circle fa-3x"></i></span></div>');
            }
        });
    }

    function companyChange()
    {
        studentEnrollment = $("#agreement_studentEnrollment");
        company = $("#agreement_company");
        var workcenter = $("#agreement_workcenter");
        var activityRealizations = $("#agreement_activityRealizations");

        var form = $(this).closest('form');
        var data = {};
        data[project.attr('name')] = project.val();
        data[studentEnrollment.attr('name')] = studentEnrollment.val();
        data[company.attr('name')] = company.val();
        data[workcenter.attr('name')] = workcenter.val();
        data[workTutor.attr('name')] = workTutor.val();

        next = workcenter.next();
        workcenter.replaceWith('<div id="agreement_workcenter"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = activityRealizations.next();
        activityRealizations.replaceWith('<div id="agreement_activityRealizations"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();

        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#agreement_workcenter').replaceWith(
                    $(html).find('#agreement_workcenter')
                );
                $('select#agreement_workcenter').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
                $('#agreement_activityRealizations').replaceWith(
                    $(html).find('#agreement_activityRealizations')
                );
                $('select#agreement_activityRealizations').select2({
                    theme: "bootstrap",
                    language: 'es'
                });
            },
            error: function () {
                $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-danger"><i class="fa fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    var project = $("#agreement_project");
    var company = $("#agreement_company");
    var studentEnrollment = $("#agreement_studentEnrollment");
    var workTutor = $("#agreement_workTutor");

    project.change(projectChange);
    company.change(companyChange);
    studentEnrollment.change(studentEnrollmentChange);
});
