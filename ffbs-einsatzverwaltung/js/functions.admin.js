var $ = jQuery;

var deleteVehicleHandler = function () {
    $('.tab-images').click(function () {
        alert('Handler for .click() called.');
    });
};

var addVehicle = function () {

    $('.add-vehicle').click(function () {
        var data = {
            action: 'add_vehicle',
            nonce: ajax_object.nonce,
            vehicle: {
                'description': $('#vehicle_description').val(),
                'radio_id': $('#vehicle_radio_id').val(),
                'location': $('#vehicle_location').val(),
            }
        };

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: data,
            success: function (data, textStatus, XMLHttpRequest) {
                $('#message').show();

                $('.tab-vehicle').append('<tr>' +
                    '<td>' + response.id + '</td>' +
                    '<td>' + response.description + '</td>' +
                    '<td>' + response.radio_id + '</td>' +
                    '<td>' + response.location + '</td>' +
                    '<td><i class="fas fa-edit"></i></td>' +
                    '<td><i class="fas fa-trash-alt"></i></td>' +
                    '</tr>');
                $('#message').fadeOut(2000);
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                console.log(errorThrown);
            }
        });
    });
};
jQuery(document).ready(function ($) {
    deleteVehicleHandler();
    addVehicle();
});
