$(function ()
{
    function studentEnrollmentChange()
    {
        studentEnrollment = $("#agreement_studentEnrollment");
        company = $("#agreement_company");
        var workcenter = $("#agreement_workcenter");
        var educationalTutor = $("#agreement_educationalTutor");
        var activityRealizations = $("#agreement_activityRealizations");

        var form = $(this).closest('form');
        var data = {};
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
        next = educationalTutor.next();
        $('#agreement_educationalTutor').replaceWith('<div id="agreement_educationalTutor"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
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
                $('#agreement_educationalTutor').replaceWith(
                    $(html).find('#agreement_educationalTutor')
                );
                $('select#agreement_educationalTutor').select2({
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

    var company = $("#agreement_company");
    var studentEnrollment = $("#agreement_studentEnrollment");
    var workTutor = $("#agreement_workTutor");

    company.change(companyChange);
    studentEnrollment.change(studentEnrollmentChange);
});
