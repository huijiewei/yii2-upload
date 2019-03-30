<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-23
 * Time: 16:40
 */

namespace huijiewei\upload\drivers;

use huijiewei\upload\BaseUpload;
use yii\base\InvalidArgumentException;
use yii\base\UnknownMethodException;

class AliyunOSS extends BaseUpload
{
    public $accessKeyId;
    public $accessKeySecret;

    public $endpoint;
    public $bucket;
    public $folder = '';

    public function init()
    {
        parent::init();

        if (empty($this->accessKeyId)) {
            throw new InvalidArgumentException('请先设置 accessKeyId 属性');
        }

        if (empty($this->accessKeySecret)) {
            throw new InvalidArgumentException('请先设置 accessKeySecret 属性');
        }

        if (empty($this->endpoint)) {
            throw new InvalidArgumentException('请先设置 endpoint 属性');
        }

        if (empty($this->bucket)) {
            throw new InvalidArgumentException('请先设置 bucket 属性');
        }
    }

    public function build($fileSize, $fileTypes)
    {
        $url = 'https://' . $this->bucket . '.' . $this->endpoint;

        $folder = rtrim($this->folder, '/') . '/' . date('Ym') . '/';

        $policy = [
            'expiration' => date('Y-m-d') . 'T' . date('H:i:s', time() + (60 * 20)) . 'Z',
            'conditions' => [
                ['content-length-range', 0, $fileSize],
                ['starts-with', '$key', $folder]
            ]
        ];

        $policy = base64_encode(json_encode($policy));

        $signature = base64_encode(hash_hmac('sha1', $policy, $this->accessKeySecret, true));

        $params = [
            'OSSAccessKeyId' => $this->accessKeyId,
            'key' => $folder . '${filename}',
            'policy' => $policy,
            'signature' => $signature,
            'success_action_status' => 201
        ];

        return [
            'url' => $url,
            'params' => $params,
            'headers' => [],
            'dataType' => 'xml',
            'paramName' => $this->paramName(),
            'imageProcess' => '?x-oss-process=style/',
            'responseParse' => 'return result.querySelector(\'PostResponse > Location\').textContent;',
        ];
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