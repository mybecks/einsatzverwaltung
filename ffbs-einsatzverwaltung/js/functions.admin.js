var $ = jQuery;

var deleteVehicleHandler = function () {
    $('.tab-images').click(function () {
        alert('Handler for .click() called.');
    });
};

let addVehicle = function () {

    $('.add-vehicle').click(function () {
        let data = {
            description: $('#vehicle_description').val(),
            radioId: $('#vehicle_radio_id').val(),
            location: $('#vehicle_location').val(),
            mediaLink: $('#vehicle_media_link').val(),
        };
        let url = wpApiSettings.root + 'ffbs/v1/vehicles';
        $.ajax({
            type: 'POST',
            url: url,
            contentType: "application/json",
            data: JSON.stringify(data),
            dataType: 'json',
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
            },
            success: function (data, textStatus, XMLHttpRequest) {
                $('#message').show();

                $('.tab-vehicle').append('<tr>' +
                    '<td>' + data.radioId + '</td>' +
                    '<td>' + data.description + '</td>' +
                    '<td>' + data.location + '</td>' +
                    '<td><i class="fas fa-edit"></i></td>' +
                    '<td><i class="fas fa-trash-alt"></i></td>' +
                    '</tr>');
                $('#message').fadeOut(2000);
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                console.log(MLHttpRequest.status + ' ' + MLHttpRequest.responseText);
                $('#message').html(MLHttpRequest.status + ' ' + MLHttpRequest.responseText).show();
            }
        });
    });
};
jQuery(document).ready(function ($) {
    deleteVehicleHandler();
    addVehicle();
});
