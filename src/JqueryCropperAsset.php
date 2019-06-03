<?php


namespace huijiewei\upload;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class JqueryCropperAsset extends AssetBundle
{
    public $sourcePath = '@npm/jquery-cropper/dist';

    public $js = [
        'jquery-cropper.min.js',
    ];

    public $depends = [
        JqueryAsset::class,
        CorpperjsAsset::class,
    ];
}
