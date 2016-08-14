/**
 * Created by Степан on 13.08.2016.
 */
var global = {};
global.oldSelect = 0;
global.user = {};

function uploadInit() {
    var ul = $('#upload ul');

    $('#upload').fileupload({
        dropZone: $('#drop'),
        add: function (e, data) {
            var file = {};
            file.size = formatFileSize(data.files[0].size);
            file.name = data.files[0].name;

            var tpl = $(CTpl('upload-li-tpl', file));

            data.context = tpl.appendTo(ul);
            tpl.find('input').knob();
            tpl.find('span').click(function(){
                if(tpl.hasClass('working')){
                    jqXHR.abort();
                }
                tpl.fadeOut(function(){
                    tpl.remove();
                });
            });
            var jqXHR = data.submit();
        },
        progress: function(e, data){
            var progress = parseInt(data.loaded / data.total * 100, 10);
            data.context.find('input').val(progress).change();
            if(progress == 100) {
                data.context.removeClass('working');
            }
        },
        fail:function(e, data){
            data.context.addClass('error');
        },
        done:function () {
            listShow();
        }
    });

    $(document).on('drop dragover', function (e) {
        e.preventDefault();
    });

    function formatFileSize(bytes) {
        if (typeof bytes !== 'number') {
            return '';
        }
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        }
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        }
        return (bytes / 1024).toFixed(2) + ' KB';
    }
}

function registrateAction(form) {
    event.preventDefault();
    var data = $(form).serialize();
    console.log(ajax('registration', data));
    profileShow(true);
    return false;
}
function loginAction(form) {
    event.preventDefault();
    var data = $(form).serialize();
    console.log(ajax('login', data));
    $('#login').hide(200, function () {
        profileShow(true);
    });
    return false;
}
function logoutAction() {
    ajax('logout');
    $('#profile').hide(200, function () {
        profileShow(true);
    });
}
function bindControlAction(id) {
    $('#control-'+id+' img').click(function () {
        console.log(this.id);
        var file = fileInfo(id, this.id);
        $('#service').html(CTpl('detail-tpl', file));

        $('.detail-control img').removeClass('active');
        $('.detail-control #'+file.access).addClass('active');
        $('#detail-'+id).show();

        if(file.is_delete == 1) {
            $('#file-'+file.id).click(function () {
                listShow();
            });
        }
        bindControlAction(id);
    });
}

function profileShow(reindexList) {
    if(!!reindexList) {
        global.user = ajax('auth');
    }

    if(global.user.auth) {
        $('#service').html(CTpl('profile-tpl', global.user));
        $('#profile').show(200);
    } else {
        $('#service').html(CTpl('login-tpl'));
        $('#login').show(200);
    }
    if(!!reindexList) {
        listShow();
    }
}
function listShow() {
    var userImg = CTpl('user-img-tpl');
    var files = ajax('files');
    $('#list-container').html('');
    $.each(files, function (key, value) {
        if(global.user.auth) {
            value.userImg = (global.user.user_id == value.user_id)?userImg:'';
        } else {
            value.userImg = '';
        }
        $('#list-container').prepend(CTpl('list-item-tpl', value));
    });

    $("[id^=file]").click(function () {
        detail($(this).attr('id').split('-')[1]);
    })
}
function registrationShow() {
    $('#service').html(CTpl('registration-tpl'));
    $('#registration').show(200);
}

function fileInfo(id, action) {
    var request = {id: id};
    if (!!action) {
        request.action = action;
    }

    var file = ajax('file', request);

    if (global.user.auth && global.user.user_id == file.user_id) {
        file.control = '';
    } else {
        file.control = 'hide';
    }

    if (file.is_delete == 1) {
        file.status = '<i style="color: red; display: inline">Видалений</i>';
        file.access = 'delete';
        file.control = 'hide';
    } else if (file.is_private == 1) {
        file.status = 'Приватний';
        file.access = 'private';
    } else if (file.is_public == 1) {
        file.status = 'Публічний';
        file.access = 'public';
    } else {
        file.access = 'registered';
        file.status = 'Для зареєстрованих';
    }

    return file;
}
function detail(id) {
    if (global.oldSelect != id) {
        $('#file-' + id).attr("style", "background: rgba(200, 200, 200, 0.1);");
        $('#file-' + global.oldSelect).removeAttr("style");
        global.oldSelect = id;
        var file = fileInfo(id);
        $('#service').html(CTpl('detail-tpl', file));
        $('.detail-control #'+file.access).addClass('active');
        $('#detail-'+id).show(200);
        bindControlAction(id);
    }
    else {
        $('#file-' + id).removeAttr("style");
        $('#detail-'+id).hide( 200, function () {
            $("#service").html("");
            profileShow();
        });
        global.oldSelect = 0;
    }
}

function ajax(method, data) {
    var resp = '';
    $.ajax({
        async: false,
        type: 'POST',
        url: '/?ajax&method='+method,
        data: data,
        dataType: 'json',
        success: function (response) {
            resp = response;
        }
    });
    return JSON.parse(resp).data;
}
function CTpl(template, data) {
    var tpl = $("#"+template).html();
    if(!!data) {
        $.each(data, function (key, value) {
            tpl = tpl.replace(new RegExp('{{' + key + '}}', 'g'), value);
        });
    }
    return tpl;
}

$(document).ready(function(){
    uploadInit();
    profileShow(true);
});