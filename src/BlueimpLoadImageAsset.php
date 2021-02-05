<?php

namespace huijiewei\upload;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class BlueimpLoadImageAsset extends AssetBundle
{
    public $sourcePath = '@npm/blueimp-load-image';

    public $js = [
        'js/load-image.all.min.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}
