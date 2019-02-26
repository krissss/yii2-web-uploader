<?php

namespace kriss\webUploader\actions;

use yii\base\DynamicModel;
use yii\web\UploadedFile;

class QuickUploadAction extends QuickBaseAction
{
    const MSG_UPLOAD_SAVE_ERROR = 'MSG_UPLOAD_SAVE_ERROR';

    /**
     * 上传文件的 file 参数名
     * @var string
     */
    public $fileParam = 'file';
    /**
     * 上传文件的验证规则
     * @var array
     */
    public $validationRules = [];
    /**
     * 文件名生成的方式，默认用 md5
     * @var callable
     */
    public $fileSaveNameCallback;
    /**
     * 文件保存的方法，默认用 UploadedFile::saveAs()
     * @var callable
     */
    public $saveFileCallback;

    public function run()
    {
        $uploadedFile = UploadedFile::getInstanceByName($this->fileParam);
        if ($uploadedFile->error == UPLOAD_ERR_OK) {
            $validationModel = DynamicModel::validateData(['file' => $uploadedFile], $this->validationRules);
            if (!$validationModel->hasErrors()) {
                try {
                    $isSuccess = $this->saveFile($uploadedFile);
                    if ($isSuccess) {
                        return $this->returnSuccess([
                            'url' => $this->getFileName($uploadedFile, $this->displayPath),
                        ]);
                    } else {
                        return $this->returnError($this->resolveErrorMessage(static::MSG_UPLOAD_SAVE_ERROR));
                    }
                } catch (\Exception $e) {
                    return $this->returnError($e->getMessage());
                }
            }
            return $this->returnError($validationModel->getFirstError('file'));
        } else {
            return $this->returnError($this->resolveErrorMessage($uploadedFile->error));
        }
    }

    /**
     * @param $uploadedFile UploadedFile
     * @return bool
     */
    protected function saveFile($uploadedFile)
    {
        $filename = $this->getFileName($uploadedFile, $this->savePath);
        if ($this->saveFileCallback && is_callable($this->saveFileCallback)) {
            return call_user_func($this->saveFileCallback, $filename, $uploadedFile, $this);
        }
        return $uploadedFile->saveAs($filename);
    }

    protected function defaultMessage()
    {
        return array_merge(parent::defaultMessage(), [
            static::MSG_UPLOAD_SAVE_ERROR => '上传的文件保存失败',
        ]);
    }

    /**
     * @var false|string
     */
    private $filename = false;

    /**
     * @param $uploadedFile UploadedFile
     * @param $basePath string
     * @return string
     * @throws \Exception
     */
    private function getFileName($uploadedFile, $basePath)
    {
        if ($this->filename === false) {
            if ($this->fileSaveNameCallback && is_callable($this->fileSaveNameCallback)) {
                $this->filename = call_user_func($this->fileSaveNameCallback, $uploadedFile, $this);
            } else {
                $this->filename = md5(microtime() + random_int(10000, 99999));
            }
            if (strpos('.', $this->filename) === false) {
                $this->filename .= '.' . $uploadedFile->getExtension();
            }
        }
        $filename = $this->getFullFilename($this->filename, $basePath);
        return $filename;
    }
}
