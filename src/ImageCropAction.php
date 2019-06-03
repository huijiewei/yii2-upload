<?php

namespace huijiewei\upload;

use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ImageCropAction extends Action
{
    /* @var $uploadDriver BaseUpload */
    public $uploadDriver = 'upload';

    public function run($policy, $file)
    {
        \Yii::$app->getResponse()->format = Response::FORMAT_JSON;

        $result = $this->uploadDriver->imageCrop(
            $policy,
            $file,
            \Yii::$app->getRequest()->get('size'),
            \Yii::$app->getRequest()->get('x'),
            \Yii::$app->getRequest()->get('y'),
            \Yii::$app->getRequest()->get('w'),
            \Yii::$app->getRequest()->get('h'),
            $error);

        if (!$result) {
            throw new ForbiddenHttpException($error);
        }

        return $result;
    }

    public function init()
    {
        parent::init();

        $this->controller->enableCsrfValidation = false;

        $this->initializeUploadDriver();
    }

    private function initializeUploadDriver()
    {
        if (is_string($this->uploadDriver)) {
            $this->uploadDriver = \Yii::$app->get($this->uploadDriver);
        }
    }
}
