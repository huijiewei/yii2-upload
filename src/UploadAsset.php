<?php

namespace huijiewei\upload;

use yii\bootstrap\BootstrapAsset;
use yii\web\AssetBundle;

class UploadAsset extends AssetBundle
{
    public $sourcePath = '@huijiewei/upload/assets';

    public $js = [
        'js/notify.min.js',
        'js/upload-widget.js',
    ];

    public $css = [
        'css/upload-widget.css',
    ];
    public $depends = [
        BootstrapAsset::class,
        BlueimpFileUploadAsset::class,
    ];
}
