/**
 * Created by huijiewei on 5/26/15.
 */
(function ($) {
    "use strict";

    $.uploadWidget = function (id, options, uploadSucceed) {
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

        function addItem(url, preview, widget, inputName, uploadSucceed) {
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

            if (uploadSucceed && uploadSucceed != null && $.isFunction(uploadSucceed)) {
                uploadSucceed({file: filename, url: url});
            }
        }

        upload.fileupload(fileUploadOptions)
            .bind('fileuploadsubmit', function (e, data) {
                if (data.files[0]['size'] &&
                    data.files[0]['size'] > options.maxFileSize) {
                    button.notify(options.maxFileSizeMessage, 'error');

                    return false;
                }

                if (options.acceptFileTypes &&
                    data.files[0]['type'].length &&
                    !options.acceptFileTypes.test(data.files[0]['name'])) {
                    button.notify(options.acceptFileTypesMessage, 'error');

                    return false;
                }

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
                var result = options.responseParse(data.result);

                if (options.cropImageOptions) {
                    $.imageCrop(result.original, options.cropImageOptions, function (req) {
                        addItem(req.original, options.preview, widget, options.inputName, uploadSucceed)
                    });
                } else {
                    var url = result.original;

                    if (options.imageStyleName.length > 0 && Array.isArray(result.thumbs)) {
                        for (var t in result.thumbs) {
                            var thumb = result.thumbs[t];
                            if (thumb.thumb == options.imageStyleName) {
                                url = thumb.url
                            }
                        }
                    }

                    addItem(url, options.preview, widget, options.inputName, uploadSucceed)
                }
            })
            .bind('fileuploadfail', function (e, data) {
                button.notify(data.errorThrown, 'error');
            })
            .bind('fileuploadalways', function () {
                button.removeClass('disabled');
            });
    };
})(jQuery);
