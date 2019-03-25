
# yii2-upload

`Yii2` 下的一个上传组件, 支持直传到第三方云存储。

## 安装

```sh
composer require huijiewei/yii2-upload
```

## 配置及使用

### 本地存储
```php
    // 在 Yii2 components 配置里面增加
    
    'upload' => [
        'class' => \huijiewei\upload\drivers\LocalFile::class,
        'path' => '文件上传的目录',
        'action' => 'site/upload'
    ],
    
    // 根据上面的 action 配置，在对应的控制器里面增加
    
    public function actions()
    {
        return [
            'upload' => [
                'class' => \huijiewei\upload\UploadAction::class,
            ]
        ];
    }
    
    // 使用 widgets 命名空间下的 widget
    
    <?= \huijiewei\upload\widgets\ImageUploadWidget::widget([
        'name' => 'image',
        'value' => '',
    ]) ?>
```

### 云存储
```php
    // 阿里云 OSS
    
    'upload' => [
        'class' => \huijiewei\upload\drivers\AliyunOSS::class,
        'accessKeyId' => '',
        'accessKeySecret' => '',
        'endpoint' => '',
        'bucket' => '',
        'folder' => ''
    ],
    
    // 腾讯云 COS
    
     'upload' => [
        'class' => \huijiewei\upload\drivers\TencentCOS::class,
        'appId' => '',
        'secretId' => '',
        'secretKey' => '',
        'bucket' => '',
        'region' => '',
        'folder' => '',
    ],
    
    // 七牛
    
     'upload' => [
        'class' => \huijiewei\upload\drivers\Qiniu::class,
        'accessKey' => '',
        'secretKey' => '',
        'bucket' => '',
        'folder' => '',
        'uploadHost' => '',
        'bucketHost' => '',
        'bucketHttps' => false,
    ],
```

### componentId 自定义

如果想要使用多个存储引擎，可以定义不同的 componentId

然后设置 widget 和 action 的 uploadDriver 属性为对应的 componentId 即可

## 直传到云存储
该组件支持直传到第三方云存储，实际上就是模拟了表单上传的方式。从流程上来说相比于传统的先上传到服务器，再从服务器传到云存储来说，少了一步转发。从架构上来说，原来的上传都统一走网站服务器，上传量过大时，瓶颈在网站服务器，可能需要扩容网站服务器。采用表单上传后，上传都是直接从客户端发送到云存储。上传量过大时，压力都在云存储上，由云存储来保障服务质量。

目前支持的第三方云储存：
`本地(LocalFile)` `腾讯云(TencentCOS)` `阿里云(AliyunOSS)`  `七牛(Qiniu)` 
> 其中的本地不算云存储，只是标识仍旧支持本地磁盘存储。


## 扩展
当然，你也可以拓展支持的云存储，继承 BaseUpload 并实现对应的方法即可

## License
MIT
