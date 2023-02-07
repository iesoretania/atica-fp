$(function () {
    function companyChange()
    {
        company = $("#agreement_company");
        var workcenter = $("#agreement_workcenter");

        var form = $(this).closest('form');
        var data = {};
        data[company.attr('name')] = company.val();
        data[workcenter.attr('name')] = workcenter.val();

        var next = workcenter.next();
        workcenter.replaceWith('<div id="agreement_workcenter"><span class="text-info"><i class="fa fa-circle-notch fa-spin fa-3x fa-fw"></i></span></div>');
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
                }).val($("#agreement_workcenter option:nth-child(2)").val()).trigger('change.select2');
            },
            error: function () {
                $('#agreement_workcenter').replaceWith('<div id="agreement_workcenter"><span class="text-danger"><i class="fa fa-times-circle fa-3x"></i></span></div>')
            }
        });
    }

    var company = $("#agreement_company");
    company.change(companyChange);

    $("button#select_all").click(function () {
        $("input[name='agreement[activities][]']").prop('checked', true);
    });

    $("button#select_none").click(function () {
        $("input[name='agreement[activities][]']").prop('checked', false);
    });
});
