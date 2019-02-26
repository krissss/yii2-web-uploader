<?php

namespace kriss\webUploader\actions;

use Yii;

class QuickDeleteAction extends QuickBaseAction
{
    const MSG_DELETE_ERROR = 'MSG_DELETE_ERROR';

    /**
     * 删除文件的方式，默认使用 unlink 删除本地文件
     * @var callable
     */
    public $deleteFileCallback;

    public function run() {
        $filename = Yii::$app->request->post('filename');
        try {
            $isSuccess = $this->deleteFile($filename);
            if ($isSuccess) {
                return $this->returnSuccess();
            } else {
                return $this->returnError($this->resolveErrorMessage(static::MSG_DELETE_ERROR));
            }
        } catch (\Exception $e) {
            return $this->returnError($e->getMessage());
        }
    }

    /**
     * @param $filename
     * @return bool
     */
    protected function deleteFile($filename)
    {
        $filename = $this->solveDisplay2SaveFilename($filename);
        if ($this->deleteFileCallback && is_callable($this->deleteFileCallback)) {
            return call_user_func($this->deleteFileCallback, $filename, $this);
        }
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    protected function defaultMessage()
    {
        return array_merge(parent::defaultMessage(), [
            static::MSG_DELETE_ERROR => '文件删除失败',
        ]);
    }
}
