# own-php-framework
### 简介

这是一个高性能的php框架，并且实现了目前大部分主流的框架思想。先列举几个亮点：
+ 基于FastRoute的快速路由，这是我看lumem框架学到的，性能高，对于API路由管理方便，并可以映射到各个层，借此实现优雅的restful
+ di依赖注入。关于这个是目前很多主流框架都实现的一个思想，目的就是代码解耦。
+ 符合psr的风格
+ 提供类似于 Laravel 的middleware(Filters & Terminators)机制
+ 可扩充的服务和组件
+ 基于AMQP协议的rabbitMQ队列服务(Zhangyuan帮助完善了守护端进程)


安装方法
```composer
composer require baicaowei/own-php-framework:@dev
```

入口文件index.php
```php
<?php
require_once __DIR__.'/../vendor/autoload.php';
$settings = require __DIR__ . '/config.php';
$app = new \Baicaowei\App($settings);
require __DIR__ . '/dependencies.php';
require __DIR__ . '/routes.php';

$app->run();

 ?>
```

主配置文件config.php
```php
<?php

return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
    ],
];

?>
```

路由文件演示
```php
<?php
$app->get('/test/{id}', Baicaowei\Controller\AuthController::class . ':show');
?>
```

组件文件
```php

<?php
$container = $app->getContainer();
$container['db'] = function ($c) {
    $db = new Medoo\medoo([
    'database_type' => 'mysql',
    'database_name' => '',
    'server' => '',
    'username' => '',
    'password' => '',
    'charset' => 'utf8mb4',
    ]);
    return $db;
};
?>
```
目前框架并没有进行领域驱动设计，因为领域的四大模型在业务中，会牵一发而动全身，应该慎之又慎。比如如何划分Service层逻辑和domain层逻辑在有些业务场景下并不是那么明确。

还有目前对于代码里面我会加一些注释，方便理解。
