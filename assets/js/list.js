const debounce = require('lodash.debounce');
var last_value_q = "";
var last_value_f = "";
var last_value_mf = "";

var state_f = document.getElementById("filter");

var addCallBacks = function () {
    var mf = document.getElementsByName("mfilter[]");
    mf.forEach(function (e) { e.addEventListener('change', reload) });
    var not_selected = document.querySelectorAll('input[name="mfilter[]"]:not(:checked)');
    var mf_all = document.getElementById('all');
    if (mf_all) {
        mf_all.addEventListener('change', function (e) {
            mf.forEach(function (e2) { e2.checked = false; });
            reload();
        })
    }
}

function updateTable(url)
{
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
            $('div#mfilter_list').replaceWith(
                $(html).find('div#mfilter_list')
            );
            addCallBacks();
        },
        error: function () {
            window.location.replace(url);
        }
    });
}

var reload = function () {
    var url = window.location.href;
    var q = $('input#search').val();
    var f = '';
    var mf = '';
    if (state_f) {
        f = state_f.children().val();
    }

    var checkboxes = document.querySelectorAll('input[name="mfilter[]"]:checked');
    var selectedTags = '';

    checkboxes.forEach(function(e) {
        if (selectedTags !== '') {
            selectedTags = selectedTags + ',' + e.value;
        } else {
            selectedTags = e.value;
        }
    });
    mf = selectedTags;

    var all = document.getElementById('all');
    if (all) {
        var not_selected = document.querySelectorAll('input[name="mfilter[]"]:not(:checked)');
        console.log(not_selected.length);
        if (not_selected.length === 0) {
            all.checked = true;
            checkboxes.forEach(function(e) {
                e.checked = false;
            });
        }
    }

    if (q === last_value_q && f === last_value_f && mf === last_value_mf) {
        return;
    }
    last_value_q = q;
    last_value_f = f;
    last_value_mf = mf;

    // quitar parámetros
    url = url.replace(/(\?.*$)/,'');

    // codificar parámetros
    var param = '?';

    if (f && f != 0) {
        param = param + 'f=' + encodeURIComponent(f);
    }

    if (mf) {
        if (param !== '?') param = param + '&';
        param = param + 'mf=' + encodeURIComponent(mf);
    }

    if (q) {
        if (param !== '?') param = param + '&';
        param = param + 'q=' + encodeURIComponent(q);
    }
    updateTable(url + param);
};

var dynamicFormInit = function () {
    function itemChange()
    {
        var url = window.location.href;

        // quitar parámetros
        url = url.replace(/\/([0-9]*?)(&|$)/,'');
        url = url.replace(/\/([0-9]*?)(&|$)/,'');
        url = url.replace(/\?.*$/,'');

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

    addCallBacks();

    $("#search-clear").click(function () {
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
                    if ($(this).data("target")) {
                        window.open($(this).data("href"), $(this).data("target")) || window.location.assign($(this).data("href"));
                    } else {
                        window.location.assign($(this).data("href"));
                    }
                }
            })
            .on("click", ".clickable-row input[type='checkbox']", updateButton)
            .on("click", ".clickable-row a", function (ev) {
                item = ev.target;
                if ($(item).attr("href")) {
                    if ($(item).attr("target")) {
                        window.open($(item).attr("href"), $(item).data("target")) || window.location.assign($(item).attr("href"));
                    } else {
                        window.location.assign($(item).attr("href"));
                    }
                    return false;
                }
            })
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
