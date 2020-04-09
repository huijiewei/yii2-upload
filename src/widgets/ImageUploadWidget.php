<?php

namespace huijiewei\upload\widgets;

use huijiewei\upload\UploadWidget;

class ImageUploadWidget extends UploadWidget
{
    public $preview = [100, 100];
    public $label = '上传图片';
    public $fileTypes = ['jpg', 'jpeg', 'gif', 'png'];
}
