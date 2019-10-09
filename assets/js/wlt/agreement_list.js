$(function () {
    function projectChange()
    {
        var url = window.location.href;

        // quitar parámetros
        url = url.replace(/\/([0-9]*?)(&|$)/,'');
        url = url.replace(/\/([0-9]*?)(&|$)/,'');
        url = url.replace(/(\?|&q=).*?(&|$)/,'');
        url = url.replace(/(\?f=).*?(&|$)/,'');

        url = url + '/' + project.val();
        $('input#search').val('');

        // obtener nueva tabla
        $('table#list').addClass('loading');
        $.ajax({
            url: url,
            type: 'GET',
            success: function(html) {
                $('table#list').removeClass('loading');
                $('div#table').replaceWith(
                    $(html).find('div#table')
                );
            },
            error: function() {
                window.location.replace(url);
            }
        });
    }

    var project = $("#project");

    project.change(projectChange);
});
