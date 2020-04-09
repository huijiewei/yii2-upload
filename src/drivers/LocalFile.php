<?php

namespace huijiewei\upload\drivers;

use huijiewei\upload\BaseUpload;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper;
use yii\helpers\Url;
use yii\imagine\Image;

class LocalFile extends BaseUpload
{
    public $supportImageCrop = true;

    public $path;
    public $uploadAction = 'site/upload-file';
    public $cropAction = 'site/crop-image';
    public $policyKey = 'YII2';

    public function init()
    {
        parent::init();

        if (empty($this->path)) {
            throw new InvalidArgumentException('请先设置 path');
        }

        if (empty($this->uploadAction)) {
            throw new InvalidArgumentException('请先设置 uploadAction');
        }

        if (empty($this->cropAction)) {
            throw new InvalidArgumentException('请先设置 cropAction');
        }

        if (empty($this->policyKey)) {
            throw new InvalidArgumentException('请先设置 policyKey');
        }
    }

    public function upload($policy, $file, &$error)
    {
        $policy = $this->parsePolicy($policy);

        $identity = $policy['identity'];
        $size = $policy['size'];
        $types = $policy['types'];
        $thumbs = $policy['thumbs'];

        if ($file == null) {
            $error = '没有文件被上传';

            return false;
        }

        if ($file->getHasError()) {
            unlink($file->tempName);

            $error = $file->name . ' 上传失败，' . $this->getError($file->error);

            return false;
        }

        if ($file->size > $size) {
            unlink($file->tempName);

            $error = $file->name . ' 上传失败，文件大小超过 ' . \Yii::$app->getFormatter()->asShortSize($size) . ' 限制';

            return false;
        }

        if (!in_array($file->extension, $types)) {
            unlink($file->tempName);

            $error = $file->name . ' 上传失败，文件类型限制为 ' . implode(',', $types);

            return false;
        }

        $uploadUrl = Url::to('/' . ltrim($this->path, '/'), true);
        $uploadPath = \Yii::getAlias('@webroot/' . ltrim($this->path, '/'));

        if (!file_exists($uploadPath)) {
            unlink($file->tempName);

            $error = '上传文件夹 ' . $uploadPath . ' 不存在，请先建立上传文件夹';

            return false;
        }

        if (!is_writable($uploadPath)) {
            unlink($file->tempName);

            $error = '上传文件夹 ' . $uploadPath . ' 不可写，请先设置文件夹权限';

            return false;
        }

        $monthPath = $this->getMonthName();

        $uploadUrl = $uploadUrl . '/' . $monthPath;
        $uploadPath = $uploadPath . DIRECTORY_SEPARATOR . $monthPath;

        if (!FileHelper::createDirectory($uploadPath, 0755, true)) {
            unlink($file->tempName);

            $error = '文件上传失败，服务器创建目录出现错误';

            return false;
        }

        $fileExtension = $file->extension;

        $fileName = $identity . '_' . \Yii::$app->getSecurity()->generateRandomString(16) . '.' . $fileExtension;

        $fileUrl = $uploadUrl . '/' . $fileName;
        $filePath = $uploadPath . DIRECTORY_SEPARATOR . $fileName;

        if (!$file->saveAs($filePath, true)) {
            $error = '文件上传失败，未知错误';

            return false;
        }

        $result = [
            'original' => $fileUrl,
            'thumbs' => null,
        ];

        $thumbSizes = $this->buildThumbSizes($thumbs);

        if (!empty($thumbSizes)) {
            $result['thumbs'] = [];

            foreach ($thumbSizes as $thumbSize) {
                $thumbFileName = $identity . '_'
                    . \Yii::$app->getSecurity()->generateRandomString(16)
                    . '.' . $fileExtension;
                $thumbFilePath = $uploadPath . DIRECTORY_SEPARATOR . $thumbFileName;
                $thumbFileUrl = $uploadUrl . '/' . $thumbFileName;

                Image::resize($filePath, $thumbSize['width'], $thumbSize['height'])->save($thumbFilePath);

                $result['thumbs'][] = [
                    'thumb' => $thumbSize['name'],
                    'url' => $thumbFileUrl
                ];
            }
        }

        return $result;
    }

    private function parsePolicy($policy)
    {
        $policies = explode(';', \Yii::$app->getSecurity()->decryptByKey($policy, $this->policyKey));

        if (count($policies) != 6) {
            throw new InvalidArgumentException('策略验证错误');
        }

        $timestamp = intval($policies[1]);

        if ($timestamp < time()) {
            throw new InvalidArgumentException('参数已过期');
        }

        return [
            'identity' => $policies[0],
            'size' => $policies[2],
            'types' => explode(',', $policies[3]),
            'thumbs' => empty($policies[4]) ? null : explode(',', $policies[4]),
            'cropper' => $policies[5],
        ];
    }

    private function getError($error)
    {
        switch ($error) {
            case UPLOAD_ERR_OK:
                return '上传成功';
            case UPLOAD_ERR_INI_SIZE:
                return '文件的大小超过了 php.ini 中 upload_max_filesize 选项限制的值';
            case UPLOAD_ERR_FORM_SIZE:
                return '文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
            case UPLOAD_ERR_PARTIAL:
                return '文件只有部分被上传';
            case UPLOAD_ERR_NO_FILE:
                return '没有文件被上传';
            case UPLOAD_ERR_NO_TMP_DIR:
                return '找不到临时文件夹';
            case UPLOAD_ERR_CANT_WRITE:
                return '文件写入失败';
            default:
                return '未知错误';
        }
    }

    private function getMonthName()
    {
        return 'm' . date('Ym', strtotime('now'));
    }

    public function crop($policy, $file, $x, $y, $w, $h, &$error)
    {
        $policy = $this->parsePolicy($policy);

        $identity = $policy['identity'];
        $thumbs = $policy['thumbs'];
        $cropper = $policy['cropper'];

        if (!$cropper) {
            $error = '无法切割图片';

            return false;
        }

        $uploadUrl = Url::to('/' . ltrim($this->path, '/'), true);
        $uploadPath = \Yii::getAlias('@webroot/' . ltrim($this->path, '/'));

        $filePath = \Yii::getAlias('@webroot/' . ltrim(parse_url($file, PHP_URL_PATH), '/'));

        if (!file_exists($filePath)) {
            $error = '要切割的图片文件不存在';

            return false;
        }

        $monthPath = $this->getMonthName();

        $uploadUrl = $uploadUrl . '/' . $monthPath;
        $uploadPath = $uploadPath . DIRECTORY_SEPARATOR . $monthPath;

        if (!FileHelper::createDirectory($uploadPath, 0755, true)) {
            unlink($file->tempName);

            $error = '文件上传失败，服务器创建目录出现错误';

            return false;
        }

        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $cropFileName = $identity . '_' . \Yii::$app->getSecurity()->generateRandomString(16) . '.' . $fileExtension;

        $cropFileUrl = $uploadUrl . '/' . $cropFileName;
        $cropFilePath = $uploadPath . DIRECTORY_SEPARATOR . $cropFileName;

        Image::crop($filePath, $w, $h, [$x, $y])->save($cropFilePath);

        $result = [
            'original' => $cropFileUrl,
            'thumbs' => null,
        ];

        $thumbSizes = $this->buildThumbSizes($thumbs);

        if (!empty($thumbSizes)) {
            $result['thumbs'] = [];

            foreach ($thumbSizes as $thumbSize) {
                $thumbFileName = $identity . '_'
                    . \Yii::$app->getSecurity()->generateRandomString(16) . '.'
                    . $fileExtension;
                $thumbFilePath = $uploadPath . DIRECTORY_SEPARATOR . $thumbFileName;
                $thumbFileUrl = $uploadUrl . '/' . $thumbFileName;

                Image::resize($cropFilePath, $thumbSize['width'], $thumbSize['height'])->save($thumbFilePath);

                $result['thumbs'][] = [
                    'thumb' => $thumbSize['name'],
                    'url' => $thumbFileUrl
                ];
            }
        }

        return $result;
    }

    protected function option($identity, $size, $types, $thumbs, $cropper)
    {
        $policies = [
            $identity,
            (time() + 10 * 60),
            $size,
            implode(',', $types),
            ($thumbs != null && !empty($thumbs) ? implode(',', $thumbs) : ''),
            $cropper
        ];

        $policy = \Yii::$app->getSecurity()->encryptByKey(implode(';', $policies), $this->policyKey);

        return [
            'url' => Url::toRoute([$this->uploadAction, 'policy' => $policy], true),
            'cropUrl' => Url::toRoute([$this->cropAction, 'policy' => $policy], true),
            'params' => [],
            'headers' => [],
            'dataType' => 'json',
            'paramName' => $this->paramName(),
            'responseParse' => 'return result;',
        ];
    }

    public function paramName()
    {
        return 'file';
    }
}
