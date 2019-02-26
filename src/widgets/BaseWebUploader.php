<?php

namespace kriss\webUploader\widgets;

use kriss\webUploader\assets\BaseAsset;
use yii\helpers\Json;
use yii\widgets\InputWidget;

/**
 * @link http://fex.baidu.com/webuploader/
 */
class BaseWebUploader extends InputWidget
{
    /**
     * @link http://fex.baidu.com/webuploader/doc/index.html#WebUploader_Uploader_options
     * @var array|callable
     */
    public $pluginOptions;
    /**
     * @link http://fex.baidu.com/webuploader/doc/index.html#WebUploader_Uploader_events
     * @var array|callable
     */
    public $pluginEvents;

    /**
     * @var BaseAsset
     */
    protected $asset;

    public function init()
    {
        parent::init();
        $this->asset = BaseAsset::register($this->getView());
    }

    public function run()
    {
        parent::run();
        $this->registerPlugin();
        return $this->renderHtml();
    }

    protected function registerPlugin()
    {
        $view = $this->getView();
        $jsUploaderObj = $this->id . 'WebUploader';

        $options = $this->getPluginOptions();
        $options = $options ? Json::htmlEncode($options) : '';
        $js = "var {$jsUploaderObj} = new WebUploader.Uploader({$options});";
        $view->registerJs($js);

        $plugins = $this->getPluginEvents();
        if ($plugins) {
            $js = [];
            foreach ($plugins as $event => $handler) {
                $js[] = "{$jsUploaderObj}.on('{$event}', {$handler});";
            }
            $view->registerJs(implode("\n", $js));
        }
    }

    protected function renderHtml() {
        return '';
    }

    protected function getPluginOptions()
    {
        $options = [];
        if ($this->pluginOptions) {
            if (is_array($this->pluginOptions)) {
                $options = $this->pluginOptions;
            } elseif (is_callable($this->pluginOptions)) {
                $options = call_user_func($this->pluginOptions, $this);
            }
        }
        return $options;
    }

    protected function getPluginEvents()
    {
        $events = [];
        if ($this->pluginEvents) {
            if (is_array($this->pluginEvents)) {
                $events = $this->pluginEvents;
            } elseif (is_callable($this->pluginEvents)) {
                $events = call_user_func($this->pluginEvents, $this);
            }
        }
        return $events;
    }
}
