<?php

namespace kriss\webUploader\assets;

use yii\web\AssetBundle;

class BaseAsset extends AssetBundle
{
    public $sourcePath = '@npm/webuploader/dist';

    public $css = [
        //'webuploader.css'
    ];
    public $js = [
        'webuploader.min.js'
    ];
}
