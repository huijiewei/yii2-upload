<?php


namespace huijiewei\upload;

use yii\bootstrap\BootstrapAsset;
use yii\web\AssetBundle;

class ImageCropAsset extends AssetBundle
{
    public $sourcePath = '@huijiewei/upload/assets';

    public $js = [
        'js/image-crop.js',
    ];

    public $css = [
        'css/image-crop.css',
    ];
    public $depends = [
        BootstrapAsset::class,
        JqueryCropperAsset::class,
    ];
}
