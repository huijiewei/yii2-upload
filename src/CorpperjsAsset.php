<?php


namespace huijiewei\upload;

use yii\web\AssetBundle;

class CorpperjsAsset extends AssetBundle
{
    public $sourcePath = '@npm/cropperjs/dist';

    public $js = [
        'cropper.min.js',
    ];

    public $css = [
        'cropper.min.css',
    ];
}
