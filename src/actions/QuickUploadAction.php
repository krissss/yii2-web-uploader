<?php

namespace kriss\webUploader\actions;

use yii\base\DynamicModel;
use yii\helpers\FileHelper;
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
     * @link https://www.yiiframework.com/doc/guide/2.0/en/input-validation#ad-hoc-validation
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
    /**
     * 是否创建文件夹
     * 对于将 savePath 设置为 '@webroot/uploads/' . date('Y-m-d') 时非常有用
     * @var bool
     */
    public $createDirection = true;
    /**
     * 可以解决上传的文件名中带中文的问题
     * @var bool
     */
    public $filenameCoverUtf82GBK = true;

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
        if ($this->createDirection) {
            FileHelper::createDirectory(dirname($filename));
        }
        if ($this->filenameCoverUtf82GBK) {
            $filename = iconv("UTF-8", "GBK", $filename);
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
                $this->filename = md5(microtime() + random_int(10000, 99999)) . $uploadedFile->getExtension();
            }
        }
        $filename = $this->getFullFilename($this->filename, $basePath);
        return $filename;
    }
}
