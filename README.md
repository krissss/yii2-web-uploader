Yii2 WebUploader
================
webuploader for Yii2 http://fex.baidu.com/webuploader/

ScreenShot
------------
![Effect picture 1](preview/preview1.gif "Effect picture 1")  

Installation
------------

```
composer require kriss/yii2-webuploader
```

Usage
-----

### widgets

```php
<?php
use \kriss\webUploader\widgets\QuickWebUploader;

echo QuickWebUploader::widget([
    'fileNumLimit' => 5,
]);
// or
echo $form->field($model, 'file')->widget(QuickWebUploader::class, [
    'uploadUrl' => ['/file/upload'],
]);
?>
```

### actions
```php
<?php

namespace admin\controllers;

use yii\web\Controller;
use kriss\webUploader\actions\QuickDeleteAction;
use kriss\webUploader\actions\QuickUploadAction;

class FileController extends Controller
{
    public function actions()
    {
        return [
            'upload' => [
                'class' => QuickUploadAction::class,
                'savePath' => '@webroot/uploads',
                'displayPath' => '@web/uploads',
            ],
            'delete' => [
                'class' => QuickDeleteAction::class,
                'savePath' => '@webroot/uploads',
                'displayPath' => '@web/uploads',
            ],
        ];
    }
}
```

examples
--------

### only accept images

```php
<?php
// for client validate
// QuickWebUploader
[
    'pluginOptions' => [
        'accept' => [
            'extensions' => 'png,jpeg,jpg,gif',
            'mimeTypes' => 'image/*',
        ],
    ],
];

// for server validate
// QuickUploadAction
[
    'validationRules' => [
        ['file', 'file', 'extensions' => ['png', 'jpeg', 'jpg', 'gif'], 'mimeTypes' => 'image/*', 'maxSize' => 5*1024*1024]
    ],
];
```
