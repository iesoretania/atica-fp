$(function () {
    function companyChange()
    {
        var workcenter = $("#student_program_workcenter");

        var form = $(this).closest('form');
        var data = {};

        $('form select').each(function () {
            data[$(this).attr('name')] = $(this).val();
        });
        $("form input[type='radio']:checked").each(function () {
            data[$(this).attr('name')] = $(this).val();
        });

        var next = workcenter.next();
        workcenter.replaceWith('<div id="student_program_workcenter"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
        next.remove();

        $.ajax({
            url: form.attr('data-ajax'),
            type: form.attr('method'),
            data: data,
            success: function (html) {
                $('#student_program_workcenter').replaceWith(
                    $(html).find('#student_program_workcenter')
                );
                $('select#student_program_workcenter').select2({
                    theme: "bootstrap",
                    language: 'es'
                }).val($("#student_program_workcenter option:nth-child(2)").val()).trigger('change.select2');
            },
            error: function () {
                $('#student_program_workcenter').replaceWith('<div id="student_program_workcenter"><span class="text-danger"><i class="fa fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    var company = $("#student_program_company");
    company.change(companyChange);
});
