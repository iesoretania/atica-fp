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

my_item = $("select#company_manager");

$(function () {
        my_item.on('change', function (e) {
            my_item = e.currentTarget;
            if (e.currentTarget.value === '0') {
                $('#new_person form').trigger('reset');
                $('#new_person').modal('show');
            }
        });
});

$('#create_person').on('click', function (e) {
    e.preventDefault();
    sendForm($('.modal-body').find('form'), function (response) {
        if (typeof response == "object") {
            $("select.person").select2('destroy');
            $("select.person")
                .append($('<option>', {value: response.id, text: response.name}));
            $("select.person").select2({
                theme: "bootstrap",
                language: "es"
            });

            $('#new_person').modal('hide');

            clearForm($('.modal-body').find('form'), function (response) {
                $('#new_person').find('.modal-body').html(response);
            });

            $(my_item).select2({
                theme: "bootstrap",
                language: "es"
            }).val(response.id);

            $(my_item).select2({
                theme: "bootstrap",
                language: "es"
            }).val(response.id);
        } else {
            $('#new_person').find('.modal-body').html(response);
        }
    });
});
