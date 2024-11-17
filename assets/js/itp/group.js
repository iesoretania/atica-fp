$(function () {
    $("button#select_all").click(function () {
        $("input[name='program_group[currentStudentPrograms][]']").not(':disabled').prop('checked', true);
    });

    $("button#select_none").click(function () {
        $("input[name='program_group[currentStudentPrograms][]']").not(':disabled').prop('checked', false);
    });
});
