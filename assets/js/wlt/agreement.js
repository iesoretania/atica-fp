$(function ()
{
    function academicYearChange()
    {
        var form = $(this).closest('form');
        var data = {};

        company = $("#agreement_company");
        workcenter = $("#agreement_workcenter");

        var next = company.next();

        data[academicYear.attr('name')] = academicYear.val();
        data[company.attr('name')] = company.val();
        data[workcenter.attr('name')] = workcenter.val();
        $('#agreement_company').replaceWith('<div id="agreement_company"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        next = workcenter.next();
        $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('action'),
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
                $('#agreement_company').change(companyChange);
            },
            error: function () {
                $('#agreement_activities').replaceWith('<div id="agreement_activities"><span class="text-danger"><i class="fas fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    function companyChange()
    {
        company = $("#agreement_company");
        workcenter = $("#agreement_workcenter");

        var form = $(this).closest('form');
        var data = {};
        data[academicYear.attr('name')] = academicYear.val();
        data[company.attr('name')] = company.val();
        data[workcenter.attr('name')] = workcenter.val();
        var next = workcenter.next();
        workcenter.replaceWith('<div id="agreement_workcenter"><span class="text-info"><i class="fas fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();
        $.ajax({
            url: form.attr('action'),
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
            },
            error: function () {
                $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-danger"><i class="fa fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    var academicYear = $("#agreement_academicYear");
    var company = $("#agreement_company");
    var workcenter = $("#agreement_workcenter");

    academicYear.change(academicYearChange);
    company.change(companyChange);
});
