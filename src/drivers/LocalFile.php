<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-24
 * Time: 11:59
 */

namespace huijiewei\upload\drivers;

use huijiewei\upload\BaseUpload;
use Imagine\Image\Box;
use Imagine\Image\Point;
use yii\base\InvalidArgumentException;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\imagine\Image;

class LocalFile extends BaseUpload
{
    public $path;
    public $action = 'site/upload';
    public $cropAction = 'site/image-crop';
    public $policyKey = 'YII2';
    public $filenameHash = 'random'; //random/md5_file/original
    public $supportImageCrop = true;

    public function init()
    {
        parent::init();

        if (empty($this->path)) {
            throw new InvalidArgumentException('请先设置 path');
        }

        if (empty($this->action)) {
            throw new InvalidArgumentException('请先设置 action');
        }

        if (empty($this->policyKey)) {
            throw new InvalidArgumentException('请先设置 policyKey');
        }
    }

    public function build($fileSize, $fileTypes)
    {
        $data = Json::encode(['fs' => $fileSize, 'fts' => $fileTypes, 'ic' => $this->supportImageCrop]);

        $policy = base64_encode(\Yii::$app->getSecurity()->encryptByKey($data, $this->policyKey));

        return [
            'url' => Url::toRoute([$this->action, 'policy' => $policy], true),
            'cropUrl' => Url::toRoute([$this->cropAction, 'policy' => $policy], true),
            'params' => [],
            'headers' => [],
            'dataType' => 'json',
            'paramName' => $this->paramName(),
            'imageProcess' => '',
            'responseParse' => 'return result.url;',
        ];
    }

    public function paramName()
    {
        return 'file';
    }

    public function upload($policy, $file, &$error)
    {
        $policy = \Yii::$app->getSecurity()->decryptByKey(base64_decode($policy), $this->policyKey);

        $data = Json::decode($policy);

        if (!isset($data['fs']) || !isset($data['fts'])) {
            $error = '无效的上传策略';

            return false;
        }

        $fileSize = $data['fs'];
        $fileTypes = $data['fts'];

        if ($file == null) {
            $error = '没有文件被上传';

            return false;
        }

        if ($file->getHasError()) {
            unlink($file->tempName);

            $error = $file->name . ' 上传失败，' . $this->getError($file->error);

            return false;
        }

        if ($file->size > $fileSize) {
            unlink($file->tempName);

            $error = $file->name . ' 上传失败，文件大小超过 ' . \Yii::$app->getFormatter()->asShortSize($fileSize) . ' 限制';

            return false;
        }

        if (!in_array($file->extension, $fileTypes)) {
            unlink($file->tempName);

            $error = $file->name . ' 上传失败，文件类型限制为 ' . implode(',', $fileTypes);

            return false;
        }

        $fileUrl = \Yii::getAlias('@web/' . ltrim($this->path, '/'));
        $filePath = \Yii::getAlias('@webroot/' . ltrim($this->path, '/'));

        if (!file_exists($filePath)) {
            unlink($file->tempName);

            $error = '上传文件夹 ' . $filePath . ' 不存在，请先建立上传文件夹';

            return false;
        }

        if (!is_writable($filePath)) {
            unlink($file->tempName);

            $error = '上传文件夹 ' . $filePath . ' 不可写，请先设置文件夹权限';

            return false;
        }

        $monthPath = 'm' . date('Ym', strtotime('now'));

        $fileUrl = $fileUrl . '/' . $monthPath;
        $filePath = $filePath . DIRECTORY_SEPARATOR . $monthPath;

        if (!FileHelper::createDirectory($filePath, 0755, true)) {
            unlink($file->tempName);

            $error = '文件上传失败，服务器创建目录出现错误';

            return false;
        }

        switch ($this->filenameHash) {
            case 'md5_file':
                $fileNameHash = md5_file($file->tempName);
                break;
            case 'original':
                $fileNameHash = substr(md5_file($file->tempName), -8) . '_' . $file->getBaseName();
                break;
            case 'random':
            default:
                $fileNameHash = \Yii::$app->getSecurity()->generateRandomString();
                break;
        }

        $fileName = $fileNameHash . '.' . $file->extension;

        $fileUrl = $fileUrl . '/' . $fileName;
        $filePath = $filePath . DIRECTORY_SEPARATOR . $fileName;

        if (!$file->saveAs($filePath, true)) {
            $error = '文件上传失败，未知错误';

            return false;
        }

        return [
            'url' => $fileUrl,
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

    public function imageCrop($policy, $file, $size, $x, $y, $w, $h, &$error)
    {
        $policy = \Yii::$app->getSecurity()->decryptByKey(base64_decode($policy), $this->policyKey);

        $data = Json::decode($policy);

        if (!isset($data['ic'])) {
            $error = '无法切割图片';

            return false;
        }

        $fileUrl = \Yii::getAlias('@web/' . ltrim($this->path, '/'));
        $filePath = \Yii::getAlias('@webroot/' . ltrim($this->path, '/'));

        $fileRealPath = \Yii::getAlias('@webroot/' . ltrim($file, '/'));

        if (!file_exists($fileRealPath)) {
            $error = '要切割的图片文件不存在';

            return false;
        }

        $monthPath = 'm' . date('Ym', strtotime('now'));

        $fileUrl = $fileUrl . '/' . $monthPath;
        $filePath = $filePath . DIRECTORY_SEPARATOR . $monthPath;

        if (!FileHelper::createDirectory($filePath, 0755, true)) {
            unlink($file->tempName);

            $error = '文件上传失败，服务器创建目录出现错误';

            return false;
        }

        switch ($this->filenameHash) {
            case 'md5_file':
                $fileNameHash = md5_file($file->tempName);
                break;
            case 'original':
                $fileNameHash = substr(md5_file($file->tempName), -8) . '_' . $file->getBaseName();
                break;
            case 'random':
            default:
                $fileNameHash = \Yii::$app->getSecurity()->generateRandomString();
                break;
        }

        $fileName = pathinfo($file, PATHINFO_FILENAME);
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);

        $cropTemp = FileHelper::normalizePath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'crop_' . $fileName . '.' . $fileExtension);

        if ($cropTemp == false) {
            $error = '创建临时文件出错';

            return false;
        }

        $image = Image::getImagine()->open($fileRealPath);

        $image->crop(new Point($x, $y), new Box($w, $h));

        if ($size) {
            $image->resize(new Box($size[0] * 2, $size[1] * 2));
        }

        $image->save($cropTemp);

        $cropFileName = $fileNameHash . '.' . $fileExtension;

        $cropFileUrl = $fileUrl . '/' . $cropFileName;
        $cropRealPath = $filePath = $filePath . DIRECTORY_SEPARATOR . $cropFileName;

        if (!rename($cropTemp, $cropRealPath)) {
            $error = '文件保存失败，服务器发生了未知的错误';

            return false;
        }

        return [
            'url' => $cropFileUrl,
        ];
    }
}
