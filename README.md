**WeChat** 微信模块

该模块是在 [overtrue/laravel-wechat](https://github.com/overtrue/laravel-wechat) 库的基础上进行了封装，提供微信公众号（待开发）、微信小程序、微信企业号、微信支付的开箱即用功能。

## 模块安装

```bash
composer require jobsys/wechat-module
```

### 依赖

+ PHP 依赖

   ```json5
   {
       "overtrue/laravel-wechat": "^7.2",          // 微信SDK
   }
   ```
+ JS 依赖 (无)

### 配置

#### 模块配置 `config/module.php`

```php
"WeChat" => [
     "route_prefix" => "manager",                                                   // 路由前缀
 ]
```

#### `overtrue/laravel-wechat` 配置

```bash
php artisan vendor:publish --provider="Overtrue\\LaravelWeChat\\ServiceProvider"
```

> 具体配置查看 [overtrue/laravel-wechat](https://github.com/overtrue/laravel-wechat) 以及 [easywechat](https://easywechat.com/)

## 模块功能

### 微信公众号(待开发)


### 微信小程序