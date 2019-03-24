<?php
/**
 * Created by PhpStorm.
 * User: huijiewei
 * Date: 2019-03-23
 * Time: 16:37
 */

namespace huijiewei\upload;

use yii\base\Component;
use yii\web\UploadedFile;

abstract class BaseUpload extends Component
{
    /**
     * @param $fileSize integer
     * @param $fileTypes array
     * @return array {
     *    url : string
     *    params : array
     *    headers : array
     *    dataType : string
     *    paramName : string
     *    imageProcess : string
     *    responseParse : JsExpression
     * }
     */
    abstract public function build($fileSize, $fileTypes);

    /**
     * @param $policy string
     * @param $file UploadedFile
     * @param $error string
     * @return bool|array
     */
    abstract public function upload($policy, $file, &$error);

    abstract public function paramName();
}