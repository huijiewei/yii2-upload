<?php

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
    public $styleDelimiter = '-';

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

    public function upload($policy, $file, &$error)
    {
        throw new UnknownMethodException('方法未实现');
    }

    protected function option($identity, $size, $types, $thumbs, $cropper)
    {
        $url = 'https://' . $this->uploadHost;

        $folder = rtrim($this->folder, '/') . '/' . date('Ym') . '/';

        $deadline = time() + (60 * 20);

        $policy = [
            'scope' => $this->bucket . ':' . $folder,
            'isPrefixalScope' => 1,
            'deadline' => $deadline,
            'fsizeLimit' => $size,
            'returnBody' => Json::encode([
                'key' => '$(key)',
                'url' => ($this->bucketHttps ? 'https' : 'http') . '://' . rtrim($this->bucketHost, '/') . '/$(key)',
                'hash' => '${etag}',
            ])
        ];

        $policy = $this->urlSafeBase64Encode(json_encode($policy));

        $signature = $this->urlSafeBase64Encode(hash_hmac('sha1', $policy, $this->secretKey, true));

        $params = [
            'key' => $folder . $identity . '_${filename}',
            'token' => $this->accessKey . ':' . $signature . ':' . $policy,
        ];

        $responseParse = 'var url = result.url;';

        $thumbSizes = $this->buildThumbSizes($thumbs);

        if (empty($thumbSizes)) {
            $responseParse .= 'var thumbs = null;';
        } else {
            $responseParse .= 'var thumbs = [];';

            $styleDelimiter = empty($this->styleDelimiter) ? '-' : $this->styleDelimiter;

            foreach ($thumbSizes as $thumbSize) {
                $responseParse .= 'thumbs.push({ thumb: "'
                    . $thumbSize['name']
                    . '", url: url + "'
                    . $styleDelimiter
                    . $thumbSize['name'] . '"});';
            }
        }

        $responseParse .= 'return { original: url, thumbs: thumbs };';

        return [
            'url' => $url,
            'timeout' => 19 * 60,
            'params' => $params,
            'headers' => [],
            'dataType' => 'json',
            'paramName' => $this->paramName(),
            'responseParse' => $responseParse,
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
}