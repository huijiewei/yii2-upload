(function ($) {
  "use strict";

  $.imageCrop = function (file, options, callback) {
    var $modal = $('<div id="crop-image-modal" class="modal fade" role="dialog"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body"><div id="modal-crop-image-box"></div></div><div class="modal-footer"><button type="button" class="btn btn-primary">确定</button><button type="button" class="btn btn-default btn-sm" data-dismiss="modal">取消</button><input type="hidden" id="modal-crop-image-x" /><input type="hidden" id="modal-crop-image-y" /><input type="hidden" id="modal-crop-image-w" /><input type="hidden" id="modal-crop-image-h" /></div></div></div></div>').appendTo('body');

    $modal.find('.btn-primary').on('click', function () {
      $.ajax({
        url: options.url,
        data: {
          file: file,
          x: parseInt($("#modal-crop-image-x").val()),
          y: parseInt($("#modal-crop-image-y").val()),
          w: parseInt($("#modal-crop-image-w").val()),
          h: parseInt($("#modal-crop-image-h").val()),
          size: options.size
        },
        dataType: 'json'
      }).done(function (req) {
        $modal.modal('hide');
        callback(req);
      });
    });

    var $img = $('<img src="' + file + '">').appendTo("#modal-crop-image-box");

    $modal.on('hidden.bs.modal', function () {
      $img.cropper('destroy');
      $modal.remove();
    }).on('shown.bs.modal', function () {
      $img.cropper({
        crop: function (event) {
          $("#modal-crop-image-x").val(event.detail.x);
          $("#modal-crop-image-y").val(event.detail.y);
          $("#modal-crop-image-w").val(event.detail.width);
          $("#modal-crop-image-h").val(event.detail.height);
        },
        aspectRatio: options.ratio,
        zoomable: false,
        rotatable: false,
        movable: false,
        checkOrientation: false,
        autoCropArea: 0.6,
        minCropBoxWidth: options.size[0] * 2,
        minCropBoxHeight: options.size[1] * 2
      });
    }).modal({
      backdrop: 'static',
      keyboard: false,
      show: true
    });
  };
})(jQuery);
