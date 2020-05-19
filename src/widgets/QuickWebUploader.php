<?php

namespace kriss\webUploader\widgets;

use kriss\webUploader\assets\QuickAsset;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

class QuickWebUploader extends BaseWebUploader
{
    /**
     * 是否使用兼容 flash 模式
     * @var bool
     */
    public $useSwf = false;
    /**
     * 文件上传地址
     * @var array
     */
    public $uploadUrl = ['/file/upload'];
    /**
     * 文件删除地址
     * @var array
     */
    public $deleteUrl = ['/file/delete'];
    /**
     * 是否真的删除文件
     * @var bool
     */
    public $realDelete = true;
    /**
     * 文件列表的高度
     * @var int
     */
    public $height = 100;
    /**
     * 文件限制个数，会根据限制自动切换是否可以多选
     * @var int
     */
    public $fileNumLimit = 1;
    /**
     * 是否将文件切割成数组上传
     * @var bool
     */
    public $fileSplitToArray = false;
    /**
     * 文件路径分割符，在 $fileSplitToArray 为 false 时有效
     * @var string
     */
    public $fileExplodeBy = ',';
    /**
     * 单个文件大小
     * 5 * 1024 * 1024
     * @var integer
     */
    public $fileSingleSizeLimit = 5242880;
    /**
     * 图片文件的后缀，用来预览图片
     * @var string
     */
    public $imageExt = 'jpg,jpeg,png,bmp,gif,webp';
    /**
     * @var array
     */
    public $messageMap = [
        'MSG_UPLOAD_ERROR' => '上传失败',
        'Q_EXCEED_NUM_LIMIT' => '超出最大文件数，多余的会忽略',
        'Q_EXCEED_SIZE_LIMIT' => '超出所有文件大小的限制',
        'F_EXCEED_SIZE' => '超出单个文件大小的限制',
        'Q_TYPE_DENIED' => '上传文件类型不符',
    ];
    /**
     * 单行文本最大长度，超过该长度后中间会以省略号显示
     * 小于0时表示不限制
     * @var int
     */
    public $maxTextLength = 62;

    /**
     * @var string
     */
    private $pickBtnId;
    /**
     * @var string
     */
    private $hiddenInputContainerId;
    /**
     * @var string
     */
    private $hiddenInputName;
    /**
     * @var array
     */
    private $existFiles;

    public function init()
    {
        parent::init();

        $this->pickBtnId = $this->id . '-file-pick';
        $this->hiddenInputContainerId = $this->id . '-hidden-container';
        if ($this->hasModel()) {
            $this->existFiles = $this->model->{$this->attribute};
            $this->hiddenInputName = Html::getInputName($this->model, $this->attribute);
        } else {
            $this->existFiles = $this->value;
            $this->hiddenInputName = $this->name;
        }
        if (!$this->existFiles) {
            $this->existFiles = [];
        } else {
            if (is_string($this->existFiles)) {
                $this->existFiles = array_filter(explode($this->fileExplodeBy, $this->existFiles));
            }
        }
    }

    protected function registerPlugin()
    {
        //parent::registerPlugin();
        $view = $this->getView();
        QuickAsset::register($view);
        $options = Json::htmlEncode([
            'containerId' => '#' . $this->id,
            'pickBtnId' => '#' . $this->pickBtnId,
            'hiddenInputContainerId' => '#' . $this->hiddenInputContainerId,
            'hiddenInputName' => $this->hiddenInputName,
            'fileSplitToArray' => (int)$this->fileSplitToArray,
            'fileExplodeBy' => $this->fileExplodeBy,
            'deleteUrl' => Url::to($this->deleteUrl),
            'realDelete' => (int)$this->realDelete,
            'messageMap' => $this->messageMap,
            'maxTextLength' => $this->maxTextLength,
        ]);
        $pluginOptions = Json::htmlEncode($this->getPluginOptions());
        $pluginEvents = Json::htmlEncode($this->getPluginEvents());
        $view->registerJs("jQuery('#{$this->getId()}').yiiWebUploader($options, $pluginOptions, $pluginEvents);");
    }

    protected function getPluginOptions()
    {
        $options = parent::getPluginOptions();
        if ($this->useSwf) {
            $options['swf'] = $this->asset->baseUrl . '/Uploader.swf';
        }

        $options = ArrayHelper::merge([
            'auto' => true,
            'server' => Url::to($this->uploadUrl),
            'pick' => [
                'multiple' => $this->fileNumLimit != 1,
            ],
            'fileNumLimit' => $this->fileNumLimit,
            'fileSingleSizeLimit' => $this->fileSingleSizeLimit,
        ], $options);
        return $options;
    }

    protected function getPluginEvents()
    {
        $events = parent::getPluginEvents();
        return $events;
    }

    protected function renderHtml()
    {
        $listItemBaseOption = [
            'class' => 'file-list-item',
            'style' => "height: {$this->height}px; min-width: {$this->height}px; line-height: {$this->height}px;"
        ];
        // 文件列表
        $listItem = $this->getExistFileListItems($listItemBaseOption);
        // 文件列表模版
        $listItemTemplateOption = $listItemBaseOption;
        Html::addCssClass($listItemTemplateOption, 'template');
        Html::addCssStyle($listItemTemplateOption, 'display: none;');
        $listItemTemplate = Html::tag('div', '<img src="" alt="">', $listItemTemplateOption);
        // 使用页面上的按钮代替pickBtn触发
        $listItemPickOption = $listItemBaseOption;
        Html::addCssClass($listItemPickOption, 'pick');
        if (count($this->existFiles) >= $this->fileNumLimit) {
            Html::addCssStyle($listItemPickOption, 'display: none;');
        }
        $listItemPickOption['id'] = $this->pickBtnId . '-trigger';
        $listItemPick = Html::tag('div', '+', $listItemPickOption);
        // webUploader 初始化出来的 fileInput
        $hiddenFileInput = Html::tag('div', '', ['id' => $this->pickBtnId, 'style' => 'display: none']);
        // 用于表单提交的input
        $hiddenInput = $this->renderInput();
        $html = <<<HTML
<div id="{$this->id}">
    <div class="file-list">
        {$listItem}
        {$listItemPick}
        {$listItemTemplate}
    </div>
    {$hiddenFileInput}
    {$hiddenInput}
</div>
HTML;
        return $html;
    }

    protected function getExistFileListItems($listItemBaseOption)
    {
        if (count($this->existFiles) <= 0) {
            return '';
        }
        $listItemOption = $listItemBaseOption;
        Html::addCssClass($listItemOption, 'complete');
        $listItemOption['data-origin'] = 1; // 增加该字段用于在移除后可以增加上传的数量
        $listItems = [];
        foreach ($this->existFiles as $filename) {
            $ext = substr(strrchr($filename, '.'), 1);
            if (strpos($this->imageExt, $ext) !== false) {
                $content = Html::img($filename);
            } else {
                $content = Html::tag('span', $this->cutMaxTextLength($filename), ['class' => 'text']);
            }
            $options = $listItemOption;
            $options['data-url'] = $filename;
            $listItems[] = Html::tag('div', $content, $options);
        }
        return implode("\n", $listItems);
    }

    protected function renderInput()
    {
        $html = [];
        $name = $this->hiddenInputName;
        $html[] = Html::beginTag('div', ['id' => $this->hiddenInputContainerId]);
        if ($this->fileSplitToArray) {
            $name .= '[]';
            foreach ($this->existFiles as $filename) {
                $html[] = Html::hiddenInput($name, $filename);
            }
        } else {
            $html[] = Html::hiddenInput($name, implode($this->fileExplodeBy, $this->existFiles));
        }
        $html[] = Html::endTag('div');
        return implode("\n", $html);
    }

    protected function cutMaxTextLength($str)
    {
        if ($this->maxTextLength > 0 && mb_strlen($str) > $this->maxTextLength) {
            $startPos = intval($this->maxTextLength / 2) - 3;
            return mb_substr($str, 0, $startPos) . '......' . mb_substr($str, -$startPos);
        }
        return $str;
    }
}
