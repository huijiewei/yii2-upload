<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-24
 * Time: 14:57
 */

namespace huijiewei\upload;

use yii\base\Action;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class UploadAction extends Action
{
    /* @var $uploadDriver BaseUpload */
    public $uploadDriver = 'upload';

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

    public function run($policy)
    {
        \Yii::$app->getResponse()->format = Response::FORMAT_JSON;

        if (!\Yii::$app->getRequest()->getIsPost()) {
            throw new MethodNotAllowedHttpException('访问被拒绝');
        }

        $result = $this->uploadDriver->upload(
            $policy,
            UploadedFile::getInstanceByName($this->uploadDriver->paramName()),
            $error
        );

        if (!$result) {
            throw new ForbiddenHttpException($error);
        }

        return $result;
    }
}