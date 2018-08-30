const debounce = require('lodash.debounce');
var last_value = "";
var reload = function() {
    var url = window.location.href;
    var q = $('input#search').val();
    if (q === last_value) {
        return;
    }
    last_value = q;
    // quitar parámetro
    url = url.replace(/(q=).*?(&|$)/,'$1$2');
    if (q) {
        url = url + '?q=' + encodeURIComponent(q);
    }
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
};

var dynamicFormInit = function() {
    $('input#search').on("change paste keyup", debounce(reload, 500));

    $("#search-clear").click(function(){
        $('input#search').val('');
        reload();
    });

    jQuery(document).ready(function($) {

        var updateButton = function() {
            $(".enable-on-items").prop('disabled', $("input[type='checkbox']:checked").length === 0);
        };

        $("section#exchange")
            .on("click", ".clickable-row", function(ev) {
                if (ev.target.type !== "checkbox") {
                    window.document.location = $(this).data("href");
                }
            })
            .on("click", ".clickable-row input[type='checkbox']", updateButton)
            .on("click", "#select", function(item) {
                $("input[type='checkbox'].selectable").prop('checked', item.currentTarget.checked);
                updateButton();
            });

        $("input[type='checkbox']#select").click(function(item) {
            $("input[type='checkbox'].selectable").prop('checked', item.currentTarget.checked);
            updateButton();
        });

        updateButton();
    });
};

dynamicFormInit();
