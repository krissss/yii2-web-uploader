<?php

namespace kriss\webUploader\assets;

use yii\web\AssetBundle;

class QuickAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/assets/quick1.0';

    public $js = [
        'custom.js'
    ];
    public $css = [
        'custom.css'
    ];
    public $depends = [
        'yii\web\JqueryAsset',
        'kriss\webUploader\assets\BaseAsset',
    ];
}
