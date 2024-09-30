$(function ()
{
    $("button#select_all").click(function (e) {
        $("." + $(e.currentTarget).attr('data-toggle') + " input[type=checkbox]").prop('checked', true);
    });

    $("button#select_none").click(function (e) {
        $("." + $(e.currentTarget).attr('data-toggle') + " input[type=checkbox]").prop('checked', false);
    });
});
