$(function () {
    $("button#select_all").click(function () {
        $("input[name='company_program[programActivities][]']").prop('checked', true);
    });

    $("button#select_none").click(function () {
        $("input[name='company_program[programActivities][]']").prop('checked', false);
    });
});
