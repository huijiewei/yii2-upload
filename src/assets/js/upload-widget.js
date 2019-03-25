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

        upload.fileupload(fileUploadOptions)
            .bind('fileuploadsubmit', function (e, data) {
                var formData = options.uploadFormData;

                data.formData = data.formData == undefined ? {} : data.formData;

                for (var k in formData) {
                    if (formData[k].toString().indexOf('${filename}') !== -1) {
                        var randomFileName = Math.random().toString(36).slice(-5) + '_' + data.files[0].name;
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

                var filename = url.split('/').pop().split('#').shift().split('?').shift();

                var processUrl = url + options.imageProcess;

                var widgetEmpty = widget.find('li:last');
                var widgetItem = null;

                if (options.multiple) {
                    widgetItem = widgetEmpty.clone();
                    widgetItem.append('<input type="hidden" name="' + options.inputName + '" value="">');
                    widgetEmpty.before(widgetItem);
                } else {
                    widgetItem = widgetEmpty;
                }

                if (options.preview) {
                    widgetItem.find('.upload-widget-item').empty().html('<img src="' + processUrl + '">')
                } else {
                    widgetItem.find('.upload-widget-item').html(filename)
                }

                widgetItem.find('input').val(processUrl);
                widgetItem.removeClass('upload-widget-empty');
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
