/**
 * Created by huijiewei on 5/26/15.
 */
(function ($) {
  "use strict";

  $.uploadWidget = function (id, options) {
    var upload = $('#' + id);
    var widget = upload.closest('.upload-widget');
    var button = upload.closest('.fileinput-button');

    widget.on('click', '.delete', function (event) {
      event.preventDefault();

      var li = $(this).closest('li');

      if (li.closest('ul').find('li').length === 1) {
        li.find('.upload-widget-item').empty();
        li.addClass('upload-widget-empty');
        li.find('input').val('');
      } else {
        li.remove();
      }
    });

    $.notify.defaults({
      autoHideDelay: 2000
    });

    var fileUploadOptions = options.fileUploadOptions;

    fileUploadOptions.add = function (e, data) {
      if (data.originalFiles[0]['size'] &&
        data.originalFiles[0]['size'] > options.maxFileSize) {
        button.notify(options.maxFileSizeMessage, 'error');

        return;
      }

      if (options.acceptFileTypes &&
        data.originalFiles[0]['type'].length &&
        !options.acceptFileTypes.test(data.originalFiles[0]['name'])) {
        button.notify(options.acceptFileTypesMessage, 'error');

        return;
      }

      data.submit();
    };

    function addItem(url, preview, widget, inputName) {
      var filename = url.split('/').pop().split('#').shift().split('?').shift();

      var widgetEmpty = widget.find('li:last');
      var widgetItem = null;

      if (options.multiple) {
        widgetItem = widgetEmpty.clone();
        widgetItem.append('<input type="hidden" name="' + inputName + '" value="">');
        widgetEmpty.before(widgetItem);
      } else {
        widgetItem = widgetEmpty;
      }

      if (preview) {
        widgetItem.find('.upload-widget-item').empty().html('<img src="' + url + '">')
      } else {
        widgetItem.find('.upload-widget-item').html(filename)
      }

      widgetItem.find('input').val(url);
      widgetItem.removeClass('upload-widget-empty');
    }

    upload.fileupload(fileUploadOptions)
      .bind('fileuploadsubmit', function (e, data) {
        var formData = options.uploadFormData;

        data.formData = data.formData == undefined ? {} : data.formData;

        for (var k in formData) {
          if (formData[k].toString().indexOf('${filename}') !== -1) {
            var randomFileName = '';

            if (options.filenameHash == 'original') {
              randomFileName = Math.random().toString(36).slice(-8) + '_' + data.files[0].name;
            } else {
              randomFileName = Math.random().toString(36).substring(2, 16) + '_' + Math.random().toString(36).substring(2, 16) + '.' + data.files[0].name.split('.').pop();
            }

            data.formData[k] = formData[k].toString().replace('${filename}', randomFileName);
          } else {
            data.formData[k] = formData[k];
          }
        }

        button.addClass('disabled');
      })
      .bind('fileuploadsend', function (e, data) {
        var headers = options.uploadHeaders;

        data.headers = data.headers == undefined ? {} : data.headers;

        for (var h in headers) {
          data.headers[h] = headers[h];
        }
      })
      .bind('fileuploaddone', function (e, data) {
        var url = options.responseParse(data.result);

        if (options.cropImageOptions) {
          $.imageCrop(url, options.cropImageOptions, function (req) {
            addItem(req.url, options.preview, widget, options.inputName)
          });
        } else {
          url += options.imageProcess;
          addItem(url, options.preview, widget, options.inputName)
        }
      })
      .bind('fileuploadfail', function (e, data) {
        console.log(data);
        button.notify(data.errorThrown, 'error');
      })
      .bind('fileuploadalways', function () {
        button.removeClass('disabled');
      });
  };
})(jQuery);
