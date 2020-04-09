<?php

namespace huijiewei\upload;

use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ImageCropAction extends Action
{
    /* @var $uploadDriver BaseUpload */
    public $uploadDriver = 'upload';

    public function run($policy)
    {
        \Yii::$app->getResponse()->format = Response::FORMAT_JSON;

        $result = $this->uploadDriver->crop(
            $policy,
            \Yii::$app->getRequest()->post('file', ''),
            \Yii::$app->getRequest()->post('x'),
            \Yii::$app->getRequest()->post('y'),
            \Yii::$app->getRequest()->post('w'),
            \Yii::$app->getRequest()->post('h'),
            \Yii::$app->getRequest()->post('size', null),
            $error
        );

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
