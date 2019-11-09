const debounce = require('lodash.debounce');
var last_value_q = "";
var last_value_f = "";

function updateTable(url) {
// obtener nueva tabla
    $('table#list').addClass('loading');
    $.ajax({
        url: url,
        type: 'GET',
        success: function (html) {
            $('table#list').removeClass('loading');
            $('div#table').replaceWith(
                $(html).find('div#table')
            );
        },
        error: function () {
            window.location.replace(url);
        }
    });
}

var reload = function(filter) {
    var url = window.location.href;
    var q = $('input#search').val();
    var f = 0;
    if (filter) {
        f = $(filter.currentTarget).children().val();
    }
    if (q === last_value_q && f === last_value_f) {
        return;
    }
    last_value_q = q;
    last_value_f = f;

    // quitar parámetros
    url = url.replace(/\/([0-9]*?)(&|$)/,'');
    url = url.replace(/(\?|&q=).*?(&|$)/,'');
    url = url.replace(/(\?f=).*?(&|$)/,'');

    // codificar parámetros
    if (f && f != 0) {
        url = url + '?f=' + encodeURIComponent(f);
        if (q) url = url + '&q=' + encodeURIComponent(q);
    }
    else if (q) {
        url = url + '?q=' + encodeURIComponent(q);
    }

    updateTable(url);
};
var dynamicFormInit = function () {

    function itemChange()
    {
        var url = window.location.href;

        // quitar parámetros
        url = url.replace(/\/([0-9]*?)(&|$)/,'');
        url = url.replace(/\/([0-9]*?)(&|$)/,'');
        url = url.replace(/(\?|&q=).*?(&|$)/,'');
        url = url.replace(/(\?f=).*?(&|$)/,'');

        url = url + '/' + item.val();
        $('input#search').val('');
        updateTable(url);
        if (history.pushState) {
            window.history.replaceState({}, document.title, url);
        } else {
            document.location.href = url;
        }
    }

    var item = $("select#item");
    item.change(itemChange);

    $('input#search').on("change paste keyup", debounce(reload, 500));
    var f = $("input[name='filter']");
    if (f) {
        f.parent().on('click', reload);
    }

    $("#search-clear").click(function (){
        $('input#search').val('');
        reload();
    });

    jQuery(document).ready(function ($) {

        var updateButton = function () {
            $(".enable-on-items").prop('disabled', $("input[type='checkbox']:checked").length === 0);
        };

        $("section#exchange")
            .on("click", ".clickable-row", function (ev) {
                if (ev.target.type !== "checkbox") {
                    window.document.location = $(this).data("href");
                }
            })
            .on("click", ".clickable-row input[type='checkbox']", updateButton)
            .on("click", "#select", function (item) {
                $("input[type='checkbox'].selectable").prop('checked', item.currentTarget.checked);
                updateButton();
            });

        $("body").on("click", "input[type='checkbox'].selectable", updateButton);

        $("input[type='checkbox']#select").click(function (item) {
            $("input[type='checkbox'].selectable").prop('checked', item.currentTarget.checked);
            updateButton();
        });

        $('#toggle').click(function () {
            $("input.selectable").each(function () {
                this.checked = !(this.checked);
            });
            updateButton();
            return false;
        });

        updateButton();
    });
};

dynamicFormInit();
