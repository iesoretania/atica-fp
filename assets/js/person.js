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

my_item = $("select.person");

$(function () {
        my_item.on('select2:select', function (e) {
            var data = e.params.data;
            if (data.id === 0) {
                $('#new_person form').trigger('reset');
                if (data.term.includes("@")) {
                    $('#new_person_userEmailAddress').val(data.term);
                } else {
                    $('#new_person_uniqueIdentifier').val(data.term);
                }
                $('#new_person').modal('show');
                $('input#new_person_firstName').focus();
            }
        });
});

$('#create_person').on('click', function (e) {
    e.preventDefault();
    sendForm($('.modal-body').find('form'), function (response) {
        if (typeof response == "object") {
            var newOption = new Option(response.name, response.id, true, true);
            $('select.person').append(newOption).trigger('change');

            $('#new_person').modal('hide');

            clearForm($('.modal-body').find('form'), function (response) {
                $('#new_person').find('.modal-body').html(response);
            });
        } else {
            $('#new_person').find('.modal-body').html(response);
        }
    });
});
