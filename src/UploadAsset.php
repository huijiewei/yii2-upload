<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-23
 * Time: 17:16
 */

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
