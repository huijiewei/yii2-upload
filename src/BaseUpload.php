<?php

namespace huijiewei\upload;

use yii\base\Component;
use yii\web\UploadedFile;

abstract class BaseUpload extends Component
{
    public $supportImageCrop = false;

    public function crop($policy, $file, $x, $y, $w, $h, $size, &$error)
    {
        $error = '本上传驱动不支持图片切割';

        return false;
    }

    /**
     * @param string $identity
     * @param integer $size
     * @param array $types
     * @param array|null $thumbs
     * @param bool $cropper
     * @return array
     */
    public function build($identity, $size, $types, $thumbs = null, $cropper = false)
    {
        $options = $this->option($identity, $size, $types, $thumbs, $cropper);

        $options['sizeLimit'] = $size;
        $options['typesLimit'] = $types;

        return $options;
    }

    abstract protected function option($identity, $size, $types, $thumbs, $cropper);

    /**
     * @param $policy string
     * @param $file UploadedFile
     * @param $error string
     * @return bool|array
     */
    abstract public function upload($policy, $file, &$error);

    abstract public function paramName();

    protected function buildThumbSizes($thumbs)
    {
        if ($thumbs == null) {
            return [];
        }

        if (!is_array($thumbs)) {
            return [];
        }

        if (empty($thumbs)) {
            return [];
        }

        $thumbSizes = [];

        foreach ($thumbs as $thumb) {
            if (!empty($thumb)) {
                $thumbSize = explode('x', $thumb);

                if (count($thumbSize) == 2) {
                    $thumbSizes[] = [
                        'name' => $thumb,
                        'width' => $thumbSize[0],
                        'height' => $thumbSize[1],
                    ];
                }
            }
        }

        return $thumbSizes;
    }
}
