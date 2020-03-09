function sendForm(form, callback)
{
    var values = {};
    $.each(form[0].elements, function (i, field) {
        if (field.name && (field.type !== 'radio' || field.checked === true)) {
            values[field.name] = field.value;
        }
    });
    $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: values,
        success: function (result) {
            callback(result);
        }
    });
}

function clearForm(form, callback)
{
    $.ajax({
        type: 'GET',
        url: form.attr('action'),
        success: function (result) {
            callback(result);
        }
    });
}

my_item = $("select.travel-route");

$(function () {
    my_item.on('select2:select', function (e) {
        var data = e.params.data;
        if (data.id === 0) {
            $('#new_travel_route form').trigger('reset');
            $('#new_travel_route').modal('show');
            $('#new_travel_route_description').focus();
        }
    });
});

$('#create_travelRoute').on('click', function (e) {
    e.preventDefault();
    sendForm($('.modal-body').find('form'), function (response) {
        if (typeof response == "object") {
            var newOption = new Option(response.name, response.id, true, true);
            $('select.travel-route').append(newOption).trigger('change');

            $('#new_travelRoute').modal('hide');

            clearForm($('.modal-body').find('form'), function (response) {
                $('#new_travel_route').find('.modal-body').html(response);
            });
        } else {
            $('#new_travel_route').find('.modal-body').html(response);
        }
    });
});
