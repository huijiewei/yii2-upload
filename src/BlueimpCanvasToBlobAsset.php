<?php

namespace huijiewei\upload;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

class BlueimpCanvasToBlobAsset extends AssetBundle
{
    public $sourcePath = '@npm/blueimp-canvas-to-blob';

    public $js = [
        'js/canvas-to-blob.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}
