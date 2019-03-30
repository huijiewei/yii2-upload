<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-25
 * Time: 10:17
 */

namespace huijiewei\upload\drivers;

use huijiewei\upload\BaseUpload;
use yii\base\InvalidArgumentException;
use yii\base\UnknownMethodException;
use yii\helpers\Json;

class Qiniu extends BaseUpload
{
    public $accessKey;
    public $secretKey;
    public $bucket;
    public $folder = '';
    public $uploadHost = 'upload.qiniup.com';
    public $bucketHost = '';
    public $bucketHttps = false;

    public function init()
    {
        parent::init();

        if (empty($this->accessKey)) {
            throw new InvalidArgumentException('请先设置 accessKey 属性');
        }

        if (empty($this->secretKey)) {
            throw new InvalidArgumentException('请先设置 secretKey 属性');
        }

        if (empty($this->bucket)) {
            throw new InvalidArgumentException('请先设置 bucket 属性');
        }

        if (empty($this->bucketHost)) {
            throw new InvalidArgumentException('请先设置 bucketHost 属性');
        }
    }

    public function build($fileSize, $fileTypes)
    {
        $url = 'https://' . $this->uploadHost;

        $folder = rtrim($this->folder, '/') . '/' . date('Ym') . '/';

        $deadline = time() + (60 * 20);

        $policy = [
            'scope' => $this->bucket . ':' . $folder,
            'isPrefixalScope' => 1,
            'deadline' => $deadline,
            'fsizeLimit' => $fileSize,
            'returnBody' => Json::encode([
                'key' => '$(key)',
                'url' => ($this->bucketHttps ? 'https' : 'http') . '://' . rtrim($this->bucketHost, '/') . '/$(key)',
                'hash' => '${etag}',
            ])
        ];

        $policy = $this->urlSafeBase64Encode(json_encode($policy));

        $signature = $this->urlSafeBase64Encode(hash_hmac('sha1', $policy, $this->secretKey, true));

        $params = [
            'key' => $folder . '${filename}',
            'token' => $this->accessKey . ':' . $signature . ':' . $policy,
        ];

        return [
            'url' => $url,
            'params' => $params,
            'headers' => [],
            'dataType' => 'json',
            'paramName' => $this->paramName(),
            'imageProcess' => '-',
            'responseParse' => 'return result.url;',
        ];
    }

    private function urlSafeBase64Encode($data)
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($data));
    }

    public function paramName()
    {
        return 'file';
    }

    public function upload($policy, $file, &$error)
    {
        throw new UnknownMethodException('方法未实现');
    }
}