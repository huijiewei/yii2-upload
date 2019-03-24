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
use yii\web\JsExpression;

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
        $host = $this->bucket . '.cos.' . $this->region . '.myqcloud.com';

        $url = 'https://' . $host . '/';

        $httpString = strtolower('POST') .
            "\n" . urldecode('/') .
            "\n\n" . 'host=' . $host . "\n";

        $folder = rtrim($this->folder, '/') . '/' . date('Ym') . '/';

        $signTime = (string)(time() - 60) . ';' . (string)(time() + 60 * 20);

        $httpString = sha1($httpString);

        $signString = "sha1\n$signTime\n$httpString\n";

        $signKey = hash_hmac('sha1', $signTime, $this->secretKey);

        $signature = hash_hmac('sha1', $signString, $signKey);

        $authorization = 'q-sign-algorithm=sha1&q-ak='
            . $this->secretId
            . '&q-sign-time=' . $signTime . '&q-key-time=' . $signTime . '&q-header-list=host&q-url-param-list=&q-signature=' . $signature;

        $params = [
            'key' => $folder . '${filename}',
            'success_action_status' => 201,
            'Signature' => $authorization,
        ];

        return [
            'url' => $url,
            'params' => $params,
            'headers' => [
                'Authorization' => $authorization,
            ],
            'dataType' => 'xml',
            'paramName' => $this->paramName(),
            'imageProcess' => '',
            'responseParse' => new JsExpression('function(result) { return result.querySelector(\'PostResponse > Location\').textContent; }'),
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