<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-23
 * Time: 17:15
 */

namespace huijiewei\upload;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class UploadWidget extends InputWidget
{
    public $label = '上传文件';
    public $deleteLabel = '删除';

    public $preview = false;
    public $multiple = false;

    public $options = [];

    public $clientOptions = [];

    public $fileSize = 1024 * 1024;
    public $fileTypes = ['jpg', 'jpeg', 'png', 'gif', 'zip', 'doc', 'docx', 'pdf', 'xls', 'xlsx', 'ppt', 'pptx'];

    public $fileSizeMessage = '文件大小限制:';
    public $fileTypesMessage = '文件类型限制:';

    public $imageStyleName = '';

    public $cropImage = false;

    /* @var $uploadDriver BaseUpload */
    public $uploadDriver = 'upload';

    public function init()
    {
        parent::init();

        $this->initializeUploadDriver();

        UploadAsset::register($this->getView());

        if ($this->enableImageCrop()) {
            ImageCropAsset::register($this->getView());
        }

        $fileTypes = is_string($this->fileTypes) ? explode(',', $this->fileTypes) : $this->fileTypes;

        $uploadBuilds = $this->uploadDriver->build($this->fileSize, $this->fileTypes);

        $this->clientOptions = ArrayHelper::merge([
            'preview' => $this->preview ? true : false,
            'multiple' => $this->multiple,
            'inputName' => $this->name,
            'deleteLabel' => $this->deleteLabel,
            'filenameHash' => 'random', // random/original
            'maxFileSize' => $this->fileSize,
            'maxFileSizeMessage' => $this->fileSizeMessage . ' ' . round($this->fileSize / 1024, 0) . ' Kb',
            'acceptFileTypes' => !empty($fileTypes) ?
                new JsExpression('/(\.|\/)(' . implode('|', $fileTypes) . ')$/i') :
                null,
            'acceptFileTypesMessage' => $this->fileTypesMessage . ' ' . implode(',', $fileTypes),
            'imageProcess' => (!empty($this->imageStyleName) && isset($uploadBuilds['imageProcess']) && !empty($uploadBuilds['imageProcess']))
                ? ($uploadBuilds['imageProcess'] . $this->imageStyleName) : '',
            'responseParse' => new JsExpression('function (result) { ' . $uploadBuilds['responseParse'] . '}'),
            'uploadHeaders' => $uploadBuilds['headers'],
            'uploadFormData' => $uploadBuilds['params'],
            'cropImageOptions' => $this->enableImageCrop() ? array_merge($this->cropImage, ['url' => $uploadBuilds['cropUrl']]) : false,
            'fileUploadOptions' => [
                'url' => $uploadBuilds['url'],
                'dataType' => $uploadBuilds['dataType'],
                'paramName' => $uploadBuilds['paramName'],
                'singleFileUploads' => true,
            ],
        ], $this->clientOptions);

        $this->registerScript();
    }

    private function initializeUploadDriver()
    {
        if (is_string($this->uploadDriver)) {
            $this->uploadDriver = \Yii::$app->get($this->uploadDriver);
        }
    }

    public function enableImageCrop()
    {
        return $this->cropImage && $this->uploadDriver->supportImageCrop;
    }

    public function registerScript()
    {
        $clientOptions = Json::encode($this->clientOptions);

        $this->getView()->registerJs(
            "$.uploadWidget('$this->id',  $clientOptions)"
        );
    }

    public function run()
    {
        if ($this->multiple) {
            $current = $this->value == null ? [] : $this->value;
            $current[] = [];
        } else {
            $current = [];
            $current[] = $this->value;
        }

        $html = '<div class="upload-widget">';
        $html .= '<ul class="list-unstyled clearfix">';

        foreach ($current as $item) {
            $html .= '<li class="pull-left' . (empty($item) ? ' upload-widget-empty' : '') . '">';
            $html .= '<div class="upload-widget-item ' . ($this->preview ? 'upload-widget-image' : 'upload-widget-file') . '"';

            if ($this->preview) {
                $html .= ' style="width: ' . $this->preview[0] . 'px; height: ' . $this->preview[1] . 'px;"';
            }

            $html .= '>';

            if (!empty($item)) {
                if ($this->preview) {
                    $html .= '<img src="' . $item . '">';
                } else {
                    $fileName = pathinfo($item, PATHINFO_BASENAME);

                    $html .= $fileName;
                }
            }
            $html .= '</div>';
            $html .= '<span class="delete" title="' . $this->deleteLabel . '"></span>';

            if (!$this->multiple || !empty($item)) {
                $html .= '<input type="hidden" name="' . $this->name . '" value="' . $item . '">';
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        $html .= '<div class="btn btn-default btn-sm fileinput-button">';
        $html .= '<span style="top: 2px;" class="glyphicon glyphicon-cloud-upload"></span>';
        $html .= '&nbsp;<span>' . $this->label . '</span>';
        $html .= Html::fileInput($this->id, '', $this->options);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
