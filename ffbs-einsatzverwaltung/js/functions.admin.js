var $ = jQuery;

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
                    '<td>' + data.status + '</td>' +
                    '<td><i class="fas fa-edit"></i></td>' +
                    '<td><i class="fas fa-trash-alt"></i></td>' +
                    '</tr>');
                $('#message').fadeOut(5000);
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                console.log(MLHttpRequest.status + ' ' + MLHttpRequest.responseText);
                $('#message').html(MLHttpRequest.status + ' ' + MLHttpRequest.responseText).show();
            }
        });
    });
};

let deleteVehicle = function () {

    $(".tab-vehicle").on("click", "#delete", function () {
        var tr = $(this).closest("tr");
        let radioId = tr[0].cells[0].outerText;
        let id = radioId.replace(' ', '').replace('/', '').toLowerCase();

        let url = wpApiSettings.root + 'ffbs/v1/vehicles/' + id;
        $.ajax({
            type: 'DELETE',
            url: url,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
            },
            success: function (data, textStatus, XMLHttpRequest) {
                $('#message').html(radioId + ' successfully removed').show();
                tr.remove();
                $('#message').fadeOut(5000);
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                console.log(MLHttpRequest.status + ' ' + MLHttpRequest.responseText);
                $('#message').html(MLHttpRequest.status + ' ' + MLHttpRequest.responseText).show();
            }
        });
    });
}

let autocompleteDestinations = function () {

    let destinations = [
        "Langenbrücken",
        "Mingolsheim",
        "Bad Schönborn",
        "Östringen",
        "Odenheim",
        "Tiefenbach",
        "Eichelberg",
        "Kraichtal",
        "Bruchsal",
        "Wiesental",
        "Waghäusel",
        "Kirrlach",
        "Kronau",
        "Unteröwisheim",
        "Oberöwisheim",
        "Weiher",
        "Ubstadt",
        "Stettfeld",
        "Zeutern"
    ];

    if ($('#einsatzort').length > 0) {
        $("#einsatzort").autocomplete({
            source: destinations
        });
    }


}

let validateVehicleInput = function () {

    if ($('#vehicle_radio_id').val() == '') {
        $('.add-vehicle').attr("disabled", true);
    }

    $('#vehicle_radio_id').on('input', function () {
        $('.add-vehicle').attr("disabled", false);
    });
}

let setEndDateEqStartDate = function () {

    $('#alarm_date').change(function () {
        $('#alarm_end_date').val($(this).val());
    });
}

let fillLinkToPostDropdown = function () {
    if ($('#article_post_id2').length > 0) {
        let url = wpApiSettings.root + 'wp/v2/posts/?filter[category_name]=einsaetze'

        // posts.get
    }
}

let addSetting = function () {
    $('.save-cat').click(function () {
        let data = {
            id: 'cat_id',
            value: $('#category option:selected').val(),
        };
        let url = wpApiSettings.root + 'ffbs/v1/settings';
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
                // $('#message').show();

                // $('.tab-vehicle').append('<tr>' +
                //     '<td>' + data.radioId + '</td>' +
                //     '<td>' + data.description + '</td>' +
                //     '<td>' + data.location + '</td>' +
                //     '<td>' + data.status + '</td>' +
                //     '<td><i class="fas fa-edit"></i></td>' +
                //     '<td><i class="fas fa-trash-alt"></i></td>' +
                //     '</tr>');
                // $('#message').fadeOut(5000);
            },
            error: function (MLHttpRequest, textStatus, errorThrown) {
                console.log(MLHttpRequest.status + ' ' + MLHttpRequest.responseText);
                // $('#message').html(MLHttpRequest.status + ' ' + MLHttpRequest.responseText).show();
            }
        });
    });
}

jQuery(document).ready(function ($) {
    // Vehicle Subpage
    addVehicle();
    deleteVehicle();
    validateVehicleInput();

    // Custom Post Box
    setEndDateEqStartDate();
    autocompleteDestinations();
    fillLinkToPostDropdown();

    // Settings Subpage
    addSetting();
});
