$(function () {
    $("button#select_all").click(function () {
        $("input[name='agreement_enrollment[activities][]']").prop('checked', true);
    });

    $("button#select_none").click(function () {
        $("input[name='agreement_enrollment[activities][]']").prop('checked', false);
    });
});
