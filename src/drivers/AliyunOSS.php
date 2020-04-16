<?php

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
    public $styleDelimiter = '';

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

    public function upload($policy, $file, &$error)
    {
        throw new UnknownMethodException('方法未实现');
    }

    protected function option($identity, $size, $types, $thumbs, $cropper)
    {
        $url = 'https://' . $this->bucket . '.' . $this->endpoint;

        $directory = rtrim($this->folder, '/') . '/' . date('Ym') . '/';

        $policy = [
            'expiration' => date('Y-m-d') . 'T' . date('H:i:s', time() + (60 * 10)) . 'Z',
            'conditions' => [
                ['content-length-range', 0, $size],
                ['starts-with', '$key', $directory]
            ]
        ];

        $policy = base64_encode(json_encode($policy));

        $signature = base64_encode(hash_hmac('sha1', $policy, $this->accessKeySecret, true));

        $params = [
            'OSSAccessKeyId' => $this->accessKeyId,
            'key' => $directory . $identity . '_${filename}',
            'policy' => $policy,
            'signature' => $signature,
            'success_action_status' => 201
        ];

        $responseParse = 'var url = result.querySelector(\'PostResponse > Location\').textContent;';

        $thumbSizes = $this->buildThumbSizes($thumbs);

        if (empty($thumbSizes)) {
            $responseParse .= 'var thumbs = null;';
        } else {
            $responseParse .= 'var thumbs = [];';

            $styleDelimiter = empty($this->styleDelimiter) ? '?x-oss-process=style/' : $this->styleDelimiter;

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
            'params' => $params,
            'headers' => [],
            'timeout' => 9 * 60,
            'dataType' => 'xml',
            'paramName' => $this->paramName(),
            'responseParse' => $responseParse,
        ];
    }

    public function paramName()
    {
        return 'file';
    }
}
