/**
 * xoctFileuploader
 * @type {{init: xoctFileuploader.init}}
 */

mOxie.Mime.addMimeType("video/quicktime, mov");
var xoctFileuploader = {
    init: function () {
        mOxie.Mime.addMimeType("video/quicktime, mov");
        var xoctFileuploaderJS = new plupload.Uploader({
            cmd: '',
            runtimes: xoctFileuploaderSettings.runtimes,
            browse_button: xoctFileuploaderSettings.pick_button,
            url: xoctFileuploaderSettings.getUrl(),
            chunk_size: xoctFileuploaderSettings.chunk_size,
            unique_names: false,
            has_files: false,
            filters: {
                max_file_size: xoctFileuploaderSettings.max_file_size,
                /*mime_types: [
                 {title: "Video files", extensions: xoctFileuploaderSettings.supported_suffixes}
                 ]*/
                // ,mime_types: xoctFileuploaderSettings.mime_types
            },
            multi_selection: false,
            flash_swf_url: '../js/Moxie.swf',
            silverlight_xap_url: '../js/Moxie.xap',
            preinit: {
                Init: function (up, info) {
                    var self = this;
                    info.runtime == 'html5' ? xoctWaiter.init('percentage') : xoctWaiter.init();
                    $(document).ready(function () {
                        $('#form_xoct_event input[name="cmd[create]"]').click(function (e) {
                            e.preventDefault();
                            self.cmd = $(this).attr('name');
                            if (self.has_files) {
                                xoctWaiter.show();
                                self.start();
                            } else {
                                alert(xoctFileuploaderSettings.lng.msg_select);
                            }
                            return false;
                        });
                        $('#xoct_clear').click(function () {
                            self.splice();
                        });
                        $('#xoct_clear').hide();
                    });
                },
            },
            init: {
                /**
                 *
                 * @param up
                 * @param files
                 * @constructor
                 */
                UploadComplete: function (up, files) {
                    var self_file = {
                        target_name: '',
                        name: '',
                        size: '',
                    };
                    xoctWaiter.reinit('waiter');
                    xoctWaiter.show();
                    plupload.each(files, function (file) {
                        self_file = file;
                    });
                    console.log(self_file);
                    $('#postvar_temp_file').val(self_file.target_name);
                    $('#postvar_file_name').val(self_file.name);
                    $('#postvar_size').val(self_file.size);
                    $('#xoct_cmd').attr('name', this.cmd);
                    $('#xoct_cmd').val(1);
                    setTimeout(function () {
                        $('#form_' + xoctFileuploaderSettings.form_id).submit();
                    }, 300);
                },
                /**
                 *
                 * @param up
                 * @param files
                 * @constructor
                 */
                FilesAdded: function (up, files) {
                    // console.log(files);
                    function inArray(needle, haystack) {
                        var length = haystack.length;
                        for (var i = 0; i < length; i++) {
                            if (haystack[i] == needle) return true;
                        }
                        return false;
                    }

                    var self = this;
                    plupload.each(files, function (file) {
                        console.log(file);
                        if (!inArray(file.type, xoctFileuploaderSettings.mime_types_array) && !file.type.startsWith('video/')) {
                            alert(xoctFileuploaderSettings.lng.msg_not_supported)
                        } else {
                            self.has_files = true;
                            $('#pickfiles').hide();
                            $('#xoct_clear').show();
                            $('#xoct_progress').show();
                            $('#xoct_file').html(file.name);
                        }
                    });
                },

                /**
                 *
                 * @param up
                 * @param files
                 * @constructor
                 */
                FilesRemoved: function (up, files) {
                    plupload.each(files, function (file) {
                    });
                    $('#xoct_file').html('&nbsp;');
                    $('#xoct_clear').hide();
                    this.has_files = false;
                },
                /**
                 *
                 * @param up
                 * @param file
                 * @constructor
                 */
                UploadProgress: function (up, file) {
                    var percent = file.percent;
                    xoctWaiter.setPercentage(percent);
                }
            }
        });

        xoctFileuploaderJS.init();
    }
};

/**
 * xoctFileuploaderSettings
 * @type {{lng: {msg_select: string, msg_not_supported: string}, log: boolean, form_id: string, url: string, runtimes: string, pick_button: string, chunk_size: string, max_file_size: string, supported_suffixes: string, supported_suffixes_array:array,mime_types:string,mime_types_array:array}}
 */
var xoctFileuploaderSettings = {
    /**
     * called from PHP
     * @param json
     */
    initFromJSON: function (json) {
        var self = this;
        var input = JSON.parse(json);
        for (var attrname in input) {
            self[attrname] = input[attrname];
        }
        xoctFileuploader.init();
    },
    /**
     * replaces amp; with empty strings
     * @returns {string}
     */
    getUrl: function () {
        var replacer = new RegExp('amp;', 'g');
        console.log(this);
        return this.url.replace(replacer, '');
    }
};

