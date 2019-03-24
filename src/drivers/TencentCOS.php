<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-23
 * Time: 17:56
 */

namespace huijiewei\upload\drivers;

use huijiewei\upload\BaseUpload;
use yii\base\InvalidArgumentException;
use yii\base\UnknownMethodException;

class TencentCOS extends BaseUpload
{
    public $appId;
    public $secretId;
    public $secretKey;

    public $bucket;
    public $region;
    public $folder = '';

    public function init()
    {
        parent::init();

        if (empty($this->appId)) {
            throw new InvalidArgumentException('请先设置 appId 属性');
        }

        if (empty($this->secretId)) {
            throw new InvalidArgumentException('请先设置 secretId 属性');
        }

        if (empty($this->secretKey)) {
            throw new InvalidArgumentException('请先设置 secretKey 属性');
        }

        if (empty($this->bucket)) {
            throw new InvalidArgumentException('请先设置 bucket 属性');
        }

        if (empty($this->region)) {
            throw new InvalidArgumentException('请先设置 region 属性');
        }
    }

    public function build($fileSize, $fileTypes)
    {
        $url = 'https://' . $this->bucket . '.cos.' . $this->region . '.myqcloud.com/';

        $folder = rtrim($this->folder, '/') . '/' . date('Ym') . '/';

        $policy = [
            'expiration' => date('Y-m-d') . 'T' . date('H:i:s', time() + (60 * 20)) . 'Z',
            'conditions' => [
                ['content-length-range', 0, $fileSize],
                ['starts-with', '$key', $folder]
            ]
        ];

        $policy = json_encode($policy);

        $signTime = (string)(time() - 60) . ';' . (string)(time() + 60 * 20);

        $signKey = hash_hmac('sha1', $signTime, $this->secretKey);
        $signString = hash_hmac('sha1', $policy, $signKey);

        $signature = hash_hmac('sha1', $signString, $signKey);

        $params = [
            'key' => $folder . '${filename}',
            'policy' => $policy,
            'success_action_status' => 200,
            'q-sign-algorithm' => 'sha1',
            'q-ak' => $this->secretId,
            'q-key-time' => $signTime,
            'q-signature' => $signature,
        ];

        return [
            'url' => $url,
            'params' => $params,
            'headers' => [],
            'fieldName' => $this->paramName(),
            'responseKey' => 'key',
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