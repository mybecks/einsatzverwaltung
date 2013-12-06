var $ = jQuery;

var deleteVehicleHandler = function(){
    $('.tab-images').click(function() {
        alert('Handler for .click() called.');
    });
};

var previewfile = function (file) {
    if (tests.filereader === true && acceptedTypes[file.type] === true) {
        var reader = new FileReader();
        reader.onload = function (event) {
            var image = new Image();
            image.src = event.target.result;
            image.width = 250; // a fake resize
            holder.appendChild(image);
        };
        reader.readAsDataURL(file);
    } else {
        holder.innerHTML += '<p>Uploaded ' + file.name + ' ' + (file.size ? (file.size/1024|0) + 'K' : '');
        console.log(file);
    }
};

var readfiles = function(files) {
    // debugger;
    var formData = tests.formdata ? new FormData() : null;
    for (var i = 0; i < files.length; i++) {
        if (tests.formdata) formData.append('file', files[i]);
        previewfile(files[i]);
    }

    // now post a new XHR request
    if (tests.formdata) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/inc/upload.inc.php');
        xhr.onload = function() {
        progress.value = progress.innerHTML = 100;
    };

    if (tests.progress) {
        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                var complete = (event.loaded / event.total * 100 | 0);
                progress.value = progress.innerHTML = complete;
            }
        };
    }
    xhr.send(formData);
    }
};

var ddFileUpload = function(){
    var holder = $('.holder');
    
    var tests = {
        filereader: typeof FileReader != 'undefined',
        dnd: 'draggable' in document.createElement('span'),
        formdata: !!window.FormData,
        progress: "upload" in new XMLHttpRequest()
    };
    
    var support = {
        filereader: $('#filereader'),
        formdata: $('#formdata'),
        progress: $('#progress')
    };
    
    var acceptedTypes = {
        'text/csv': true,
        'text/plain': true
    };
    
    var progress = $('#uploadprogress');
    var fileupload = $('#upload');

    "filereader formdata progress".split(' ').forEach(function (api) {
        if (tests[api] === false) {
            support[api].className = 'fail';
        } else {
            // FFS. I could have done el.hidden = true, but IE doesn't support
            // hidden, so I tried to create a polyfill that would extend the
            // Element.prototype, but then IE10 doesn't even give me access
            // to the Element object. Brilliant.
            support[api].className = 'hidden';
        }
    });

    if (tests.dnd) {
        holder.ondragover = function () { this.className = 'hover'; return false; };
        holder.ondragend = function () { this.className = ''; return false; };
        holder.ondrop = function (e) {
            this.className = '';
            e.preventDefault();
            readfiles(e.dataTransfer.files);
        };
    } else {
        fileupload.className = 'hidden';
        fileupload.querySelector('input').onchange = function () {
            readfiles(this.files);
        };
    }
};

var addVehicle = function(){

    $('.add-vehicle').click(function(){
        // "action=post-like&nonce="+ajax_var.nonce+"&post-like=&post_id="+1
        var data1 = {
            action: 'add_vehicle',
            nonce: ajax_var.nonce,
            id: 1
        };
        $.ajax({
            type: "post",
            url: ajax_var.url,
            data: data1,
            beforeSend: function(jqXHR, settings){
                console.log(jqXHR);
                console.log(settings);
            },
            success: function(data, textStatus, jqXHR){
                alert('success.');
            },
            error: function(jqXHR, textStatus, errorThrown){
                alert(errorThrown);
            }
    });

        return false;
    });
};

jQuery(document).ready(function($){
    deleteVehicleHandler();
    addVehicle();
    // ddFileUpload();
});
