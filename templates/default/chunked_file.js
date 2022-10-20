/**
 * This wraps Drozone.js for FileInputs
 *
 * @author Fabian Schmid <fs@studer-raimann.ch>
 */
Dropzone.autoDiscover = false;
var il = il || {};
il.UI = il.UI || {};
il.UI.Input = il.UI.Input || {};
(function ($, UI) {

  il.UI.Input.chunkedFile = (function ($) {

    var _default_settings = {
      upload_url: '',
      chunked_upload: false,
      chunk_size: 0,
      removal_url: '',
      info_url: '',
      file_identifier_key: 'file_id',
      max_files: 1,
      max_file_size: 1048576,
      max_file_size_text: 'File is too big ({{filesize}}MiB). Max file size: {{maxFilesize}}MiB',
      accepted_files: '',
      existing_file_ids: [],
      existing_files: [],
      get_file_info_async: true,
      dictInvalidFileType: 'Filetype not supported'
    };

    var debug = function (string) {
      // console.log(string);
    }


    var init = function (container_id, settings) {
      let replacer = new RegExp('amp;', 'g');
      settings = Object.assign(_default_settings, JSON.parse(settings));
      settings.upload_url = settings.upload_url.replace(replacer, '');
      settings.removal_url = settings.removal_url.replace(replacer, '');

      var container = '#' + container_id;
      var dropzone = container + ' .il-input-file-dropzone';
      var preview_template = $(container + ' .il-input-file-template').clone();
      $(container + ' .il-input-file-template').remove();
      var input_template = $(container + ' .input-template').clone();
      $(container + ' .input-template').remove();
      debug(settings);

      var myDropzone = new Dropzone(dropzone, {
        url: encodeURI(settings.upload_url),
        method: 'post',
        createImageThumbnails: true,
        maxFiles: settings.max_files,
        maxFilesize: settings.max_file_size,
        dictDefaultMessage: '',
        dictFileTooBig: settings.max_file_size_text,
        previewsContainer: container + ' .il-input-file-filelist',
        previewTemplate: preview_template.html(),
        clickable: container + ' .il-input-file-dropzone button',
        autoProcessQueue: true,
        uploadMultiple: false,
        parallelUploads: 1,
        chunking: settings.chunked_upload,
        chunkSize: settings.chunk_size,
        forceChunking: true,
        acceptedFiles: settings.accepted_files,
        dictInvalidFileType: settings.dictInvalidFileType,
        progress_storage: 0
      });

      myDropzone.on('uploadprogress', function (file, progress, bytesSent) {
        if (file.previewElement) {
          let progressElement = file.previewElement.querySelector(".progress");
          let progressBarElement = file.previewElement.querySelector(".progress-bar");
          let number = Math.round(progress);

          if (number === 100 && bytesSent < file.size) {
            return;
          }
          if (progressBarElement && progressBarElement) {
            progressElement.style.display = "block";
            if (number > this.progress_storage) {
              progressBarElement.textContent = number + "%";
              progressBarElement.style.width = number + "%";
            }
            if (number === 100) {
              progressElement.style.display = "none";
            }
          }
          this.progress_storage = number;
        }
      });


      myDropzone.on("maxfilesreached", function (file) {
        myDropzone.removeEventListeners();
        $(container + ' .il-input-file-dropzone button').attr("disabled", true);
      });

      myDropzone.on("addedfile", function (file) {
        if (myDropzone.options.maxFileSize < file.size) {
          $(file.previewElement).addClass('alert-danger');
          alert("Too big file");
          myDropzone.removeFile(file);
        }
      });

      var success = function (file, new_file_id) {
        var clone = input_template.clone();
        clone.val(new_file_id);
        clone.attr('data-file-id', new_file_id);

        file.file_id = new_file_id;
        $(container).append(clone);
      };

      var successFromResponse = function (files, _response) {
        let response;
        let json = JSON.parse('{}');
        if (_response !== '' && typeof _response === 'object') {
          response = _response;
        } else if (files.hasOwnProperty('xhr')) {
          response = files.xhr.response;
        } else {
          response = '{}';
        }

        try {
          debug('parsing repsonse');
          debug(response);
          if (typeof _response !== 'object') {
            json = JSON.parse(response);
          } else {
            json = response;
          }
        }
        catch (e) {
          debug(e);
          return;
        }
        if (json.hasOwnProperty(settings.file_identifier_key)) {
          var file_id = json[settings.file_identifier_key];
          debug('new file id');
          debug(file_id);
          success(files, file_id);
        }
      };
      var disableForm = function () {
        $(container).closest('form').find('button').each(function (e) {
          $(this).prop('disabled', true);
        });
      };
      var enableForm = function () {
        $(container).closest('form').find('button').each(function (e) {
          $(this).prop('disabled', false);
        });
      };

      var removeFileContainer = function (file_id) {
        $(container + ' *[data-file-id="' + file_id + '"]').remove();
      };


      myDropzone.on('sending', function () {
        debug('sending');
        disableForm();
      });

      myDropzone.on("removedfile", function (file) {
        debug("success");
        myDropzone.setupEventListeners();
        myDropzone._updateMaxFilesReachedClass();
        $(container + ' .il-input-file-dropzone button').attr("disabled", false);
        // remove input
        removeFileContainer(file.file_id);

        // Call removal-URL
        if (file.hasOwnProperty('is_existing') && file.is_existing === true) {
          disableForm();
          var data = {};
          data[settings.file_identifier_key] = file.file_id;
          $.get(settings.removal_url, data, function (response) {
            enableForm();
          });
        }
      });
      myDropzone.on("success", function (files, response) {
        debug(files);
        successFromResponse(files, response);
        enableForm();
      });
      myDropzone.on("errormultiple", function (files, response) {
        debug(files);
      });
      myDropzone.on("error", function (file, response) {
        debug(file);
        $(file.previewElement).addClass('alert-danger');
        enableForm();
      });

      // existing files
      var addExisting = function (mockFile, response) {
        mockFile.accepted = true;
        mockFile.is_existing = true;
        myDropzone.files.push(mockFile);
        myDropzone.emit("success", mockFile, response);
        myDropzone.emit("complete", mockFile);
        myDropzone.emit("addedfile", mockFile);
        myDropzone._updateMaxFilesReachedClass();
      };

      for (let i in settings.existing_file_ids) {
        let data = {};
        data[settings.file_identifier_key] = settings.existing_file_ids[i];
        $.get(settings.info_url, data, function (response) {
          debug(response);
          let mockFile = JSON.parse(response);
          if (mockFile.size > 0) {
            addExisting(mockFile, mockFile);
          }
        });
      }

    };

    return {
      init: init
    };

  })($);
})($, il.UI.Input);
