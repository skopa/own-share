/**
 * Global variables
 */
var Viewer = function (app) {
    var self = this;
    this.service = $('#service');
    this.files = $('#list-container');

    var template = this.template = function (template, data) {
        var tpl = $("#" + template).html();
        if (!!data) {
            var parser = function (_data, prefix) {
                $.each(_data, function (key, value) {
                    if (typeof value === 'object' && value !== null) {
                        return parser(value, prefix + key + '.')
                    }
                    tpl = tpl.replace(new RegExp('{{' + prefix + key + '}}', 'g'), value);
                });
            };
            parser(data, '');
        }
        return tpl;
    };

    /*
     * Views section
     */

    /**
     * Show home service
     */
    this.home = function () {
        if (!!app.user) {
            return self.profile();
        } else {
            return self.login();
        }
    };

    /**
     * Show profile
     */
    this.profile = function () {
        self.service.html(template('profile-tpl', app.user));
        self.service.find('>div').animate({height: 'show'}, 200);
        app.listeners();
    };

    /**
     * Show login form
     */
    this.login = function () {
        self.service.html(template('login-tpl'));
        self.service.find('>div').animate({height: 'show'}, 200);
        app.listeners();
    };

    /**
     * Show registration form
     */
    this.registration = function () {
        self.service.html(template('registration-tpl'));
        self.service.find('>div').animate({height: 'show'}, 200);
        app.listeners();
    };

    /**
     * Show error
     */
    this.error = function (m) {
        console.error(m);
        $('#modal-container').html(template('modal-tpl', {text: 'test'}));
        $('#modal-container .modal-body').addClass('opened');
        $('#modal-container .modal-body').addClass('success');
        $('#modal-container .close').click(function () {
            $('#modal-container .modal-body').removeClass('opened');
        });
    };

    /**
     * Add file to list
     * @param {{user_id, control, id}} data
     */
    this.file = function (data) {
        data.control = app.canControl(data) ? '' : 'hide';
        self.files.prepend(template('list-item-tpl', data));
        app.listeners();
    };

    /**
     * Remove file
     * @param {string} id
     */
    this.deleteFile = function (id) {
        self.files.find("[data-id=" + id + "]").remove();
    };

    /**
     * Show details
     * @param data
     * @param toggle
     */
    this.details = function (data, toggle) {
        data.control = app.canControl(data) ? '' : 'hide';
        data.access = app.fileAccess(data);
        self.service.html(template('detail-tpl', data));
        if (!!toggle) {
            self.service.find('>div').show();
        } else {
            self.service.find('>div').animate({height: 'show'}, 200);
        }
        console.info(data);
        app.listeners();
    };
};

var app = new function () {
    var self = this;
    var view = new Viewer(self);

    /**
     * @type {{id, email, username, is_admin, resources_quote_count}}
     */
    self.user = {};

    /**
     * Init application
     */
    this.init = function () {
        self.http();
        self.auth();
        self.upload();
        self.link();
    };

    /**
     * Control possibility check
     *
     * @param file
     * @returns {boolean}
     */
    this.canControl = function (file) {
        return (!!self.user && (file.user_id === self.user.id || self.user.is_admin === 1));
    };

    /**
     *
     * @param {{is_public, is_private}} data
     * @returns {{public: *}}
     */
    this.fileAccess = function (data) {
        return {
            public: data.is_public && !data.is_private ? 'active' : '',
            private: !data.is_public && data.is_private ? 'active' : '',
            registered: data.is_public && data.is_private ? 'active' : ''
        };
    };

    /**
     * Set-Up ajax
     */
    this.http = function () {
        $.ajaxSetup({
            beforeSend: function (xhr) {
                xhr.setRequestHeader("Api-Token", localStorage.getItem('Api-Token'));
                xhr.setRequestHeader("Accept", "application/json");
            }
        });
    };

    /**
     * Check user auth and load files
     */
    this.auth = function () {
        $.ajax({
            method: 'GET',
            url: '/api/profile',
            success: function (res) {
                self.user = res.data;
                view.profile();
            },
            error: function () {
                self.user = undefined;
                view.login();
            },
            complete: app.files
        });
    };

    /**
     * File upload
     */
    this.upload = function () {
        var upload = $('#upload');
        upload.fileupload({
            dropZone: upload.find('#drop'),
            add: function (e, data) {
                var loading = $(view.template('upload-li-tpl', {
                    size: formatFileSize(data.files[0].size),
                    name: data.files[0].name
                }));

                data.context = loading.appendTo($('#upload-list'));
                loading.find('input').knob();
                loading.find('span').click(function () {
                    if (loading.hasClass('working')) {
                        jqXHR.abort();
                    }
                    loading.fadeOut(function () {
                        loading.remove();
                    });
                });

                var jqXHR = data.submit();
            },
            progress: function (e, data) {
                var progress = (data.loaded / data.total * 100).toFixed(2);
                data.context.find('input').val(progress).change();
            },
            fail: function (e, data) {
                data.context.addClass('error');
            },
            done: function (e, data) {
                data.context.removeClass('working');
                view.file(data.result.data);
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
    };

    /**
     * Save link
     */
    this.link = function () {
        $('#share').submit(function (e) {
            e.preventDefault();
            var form = this;
            $.ajax({
                method: 'POST',
                url: form.action,
                data: {
                    link: form.link.value
                },
                success: function (res) {
                    document.getElementById('share').reset();
                    view.file(res.data);
                }
            });
        })
    };


    /**
     * Files get
     */
    this.files = function () {
        $.ajax({
            method: 'GET',
            url: '/api/resources',
            success: function (res) {
                $('#list-container').html('');
                res.data.forEach(view.file);
            }
        });
    };

    /**
     * Logout
     */
    this.logout = function () {
        localStorage.removeItem('Api-Token');
        self.user = undefined;
        self.http();
        self.files();
        view.login();
    };

    this.listeners = function () {
        /**
         * Login template listeners
         */
        var login = $('#login');
        login.find('form').off().submit(function (e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: '/api/login',
                data: {
                    username: this.elements.username.value,
                    password: this.elements.password.value
                },
                success: function (data) {
                    localStorage.setItem('Api-Token', data.api_token);
                    app.http();
                    app.auth();
                },
                error: function (data) {
                    alert(data);
                }
            });
        });
        login.find("[rel='registration']").off().click(function () {
            view.registration();
        });

        /**
         * Profile listeners
         */
        var profile = $('#profile');
        profile.find("[rel='logout']").off().click(function () {
            app.logout();
        });

        /**
         * Registration listeners
         */
        var registration = $('#registration');
        registration.find('form').off().submit(function (e) {
            e.preventDefault();
            $.ajax({
                method: 'POST',
                url: '/api/registration',
                data: {
                    email: this.elements.email.value,
                    username: this.elements.username.value,
                    password: this.elements.password.value,
                    password_confirmation: this.elements.password.value
                },
                success: function (data) {
                    console.info(data);
                    alert('successfully');
                    view.login();
                },
                error: function (data) {
                    alert(data);
                }
            });
        });
        registration.find("[rel='login']").off().click(function () {
            view.login();
        });

        /**
         * File listener
         */
        var item = $('.item');
        item.off().click(function () {
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
                view.home();
            } else {
                view.files.find('.item').removeClass('selected');
                this.classList.add('selected');
                $.ajax({
                    method: 'GET',
                    url: '/api/resources/' + this.dataset.id,
                    success: function (res) {
                        view.details(res.data);
                    }
                });
            }
        });

        /**
         * Details listeners
         */
        var detail = $('#detail');
        detail.find("[rel='back']").off().click(function () {
            view.files.find('.list-item').removeClass('selected');
            view.home();
        });
        detail.find("[rel='update']").off().click(function () {
            $.ajax({
                method: 'PUT',
                data: this.dataset,
                url: '/api/resources/' + this.parentElement.dataset.id,
                success: function (res) {
                    view.details(res.data, true);
                }
            });
        });
        detail.find("[rel='delete']").off().click(function () {
            var id = this.parentElement.dataset.id;
            $.ajax({
                method: 'DELETE',
                url: '/api/resources/' + id,
                success: function () {
                    view.deleteFile(id);
                    view.home();
                }
            });
        });
    }

}();

/**
 * Init on ready
 */
$(document).ready(app.init);