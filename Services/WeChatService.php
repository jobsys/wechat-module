<?php

namespace Modules\WeChat\Services;

use EasyWeChat\Kernel\Exceptions\BadResponseException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\Pay\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Starter\Services\BaseService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WeChatService extends BaseService
{
    /**
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public function getOfficialApp(): \EasyWeChat\OfficialAccount\Application
    {
        return app('easywechat.official_account');
    }


    /**
     * @return \EasyWeChat\Work\Application
     */
    public function getWorkApp(): \EasyWeChat\Work\Application
    {
        return app('easywechat.work');
    }


    /**
     * @return \EasyWeChat\MiniApp\Application
     */
    public function getMiniProgram(): \EasyWeChat\MiniApp\Application
    {
        /**
         * @var \EasyWeChat\MiniApp\Application
         */
        return app('easywechat.mini_app');
    }


    /**
     * @return Application
     */
    public function getPaymentApp(): Application
    {
        /**
         * @var Application $app
         */
        return app('easywechat.pay');
    }


    /**
     * 获取企业号登录用户信息
     * @return array
     */
    public function getWorkUser(): array
    {

        if (!$code = request('code')) {
            return [null, '无法获得企业微信授权码'];
        }

        $user = $this->getWorkApp()->getOAuth()->detailed()->userFromCode($code);

        return [$user, null];
    }


    /**
     * 创建订单
     * @see https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
     * @see https://github.com/wechatpay-apiv3/wechatpay-php
     * @param array $params
     * @return array
     */
    public function orderCreate(array $params = []): array
    {
        $api = $this->getPaymentApp()->getClient();

        $resp = $api->postJson('v3/pay/transactions/jsapi', [
            'appid' => config('easywechat.pay.default.app_id'),
            'mchid' => config('easywechat.pay.default.mch_id'),
            'description' => $params['description'],
            'out_trade_no' => $params['out_trade_no'],
            'notify_url' => config('easywechat.pay.default.notify_url'),
            'amount' => ['total' => config('app.env') === 'production' ? $params['amount'] : 1],
            'payer' => ['openid' => $params['openid']]
        ]);

        /*{
            prepay_id": "wx26112221580621e9b071c00d9e093b0000"
        }*/


        $result = $resp->toArray();

        Log::info('Payment Result: ' . json_encode($result));

        return $result;

    }


    /**
     * 查询订单
     * @param string $order_sn
     * @return array
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     */
    public function orderQuery(string $order_sn): array
    {
        $api = $this->getPaymentApp()->getClient();

        $mchid = config('easywechat.pay.default.mch_id');

        $resp = $api->get("v3/pay/transactions/out-trade-no/$order_sn?mchid=$mchid");

        return $resp->toArray();

    }

    /**
     * 生成前端 JS 支付参数
     * @param $prepay_id
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function orderGetJsPaymentConfig($prepay_id): array
    {

        $app = $this->getPaymentApp();
        $utils = $app->getUtils();

        return $utils->buildSdkConfig($prepay_id, config('easywechat.pay.default.app_id'));
    }


    /**
     * 小程序获取用户绑定的手机号
     * @param string $code
     * @return array
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function weappGetPhoneNumber(string $code): array
    {
        $response = $this->getMiniProgram()->getClient()->postJson('/wxa/business/getuserphonenumber', [
            'code' => $code,
        ]);

        $response = $response->toArray();

        if ($response['errcode'] == '0' && $response['errmsg'] == 'ok') {
            return [$response['phone_info']['phoneNumber'], null];//$response['result']['suggest'] === 'pass';
        } else {
            return [false, $response['errmsg']];
        }
    }


    /**
     * TODO test
     * 生成小程序码
     * @param $page_url
     * @param array $params
     * @param string $file_name
     * @param int $width
     * @return array
     */
    public function weappGenerateQrCode($page_url, array $params = [], string $file_name = '', int $width = 430): array
    {


        $storage = Storage::disk('public');

        if (!$storage->exists('wxacode')) {
            $result = $storage->makeDirectory('wxacode');
        }

        $query = http_build_query($params);

        try {
            $response = $this->getMiniProgram()->getClient()->postJson('/wxa/getwxacode', [
                'path' => $page_url . ($query ? '?' . $query : ''), //'pages/index/index'
                'width' => $width,
            ]);


            $file_path = 'wxacode/' . ($file_name ?: 'wxacode-' . Str::upper(Str::random())) . '.png';

            $storage_path = storage_path("app/public/{$file_path}");
            Log::info('storage_path: ' . $storage_path);
            $response->saveAs($storage_path);
            return [true, $file_path];
        } catch (\Throwable $e) {
            Log::info("generateMiniQrCode::" . $e->getMessage());
            return [false, '生成小程序码失败'];
        }
    }

    /**
     * TODO test
     * 生成无限制小程序码
     * @param $page_url
     * @param string $scene
     * @param int $width
     * @return array
     */
    public function weappGenerateUnlimitedQrCode($page_url, string $scene = 'checkout', int $width = 430): array
    {
        try {
            $response = $this->getMiniProgram()->getClient()->postJson('/wxa/getwxacodeunlimit', [
                'scene' => $scene,
                'page' => $page_url, //'pages/index/index'
                'width' => $width,
                'check_path' => false,
            ]);

            $file_path = storage_path("app/public/wxacode-{$scene}.png");
            $response->saveAs($file_path);

            return [true, "wxacode-{$scene}.png"];
        } catch (\Throwable $e) {
            Log::info("generateUnilimitMiniQrCode::" . $e->getMessage());
            return [false, '生成小程序码失败'];
            // 失败
        }
    }


    /**
     * 敏感信息检测
     * @param $open_id
     * @param $text
     * @param int $scene 场景枚举值（1 资料；2 评论；3 论坛；4 社交日志）
     * @return bool
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function weappCheckTextSecurity($open_id, $text, int $scene = 1): bool
    {
        $response = $this->getMiniProgram()->getClient()->postJson('/wxa/msg_sec_check', [
            'content' => $text,
            'version' => 2,
            'scene' => $scene,
            'openid' => $open_id
        ]);

        $response = $response->toArray();

        if ($response['errcode'] == '0' && $response['errmsg'] == 'ok') {
            return $response['result']['suggest'] === 'pass';
        } else {
            return false;
        }
    }


    /**
     *
     * TODO 腾讯服务器为异步检测，需要提供回调
     * 检测媒体合法性
     * @param $open_id
     * @param $media_url
     * @param int $media_type
     * @param int $scene
     * @return bool
     * @throws BadResponseException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function weappCheckMediaSecurity($open_id, $media_url, int $media_type = 1, int $scene = 1): bool
    {
        $response = $this->getMiniProgram()->getClient()->postJson('/wxa/media_check_async', [
            'media_url' => $media_url,
            'media_type' => $media_type,
            'version' => 2,
            'scene' => $scene,
            'openid' => $open_id
        ]);

        $response = $response->toArray();

        if ($response['errcode'] == '0' && $response['errmsg'] == 'ok') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * 发送企业微信通用消息。
     *
     * @param $open_id
     * @param $to_name
     * @param $subject
     * @param string $url
     * @param int $msg_id 已经保存在数据库的消息的唯一标识id。
     * @return bool
     */
    public function workSendMessage($open_id, $to_name, $subject, string $url = '', int $msg_id = 0): bool
    {


        //$open_id = config('app.env') !== 'production' ? config('conf.test_work_user_id') : $open_id;

        try {
            if (!str_contains($url, 'is_from_work')) {
                if (!str_contains($url, '?')) {
                    $url .= "?is_from_work=true";
                } else {
                    $url .= "&is_from_work=true";
                }
            }

            $response = $this->getWorkApp()->getClient()->postJson('cgi-bin/message/send', [
                'msgtype' => 'text',
                'touser' => $open_id,
                'agentid' => config('easywechat.work.default.agent_id'),
                'text' => [
                    'content' => "{$to_name}，您有一条新的通知\n"
                        . "通知内容: {$subject}\n"
                        . "通知时间: " . now()->format('Y年m月d日 H:i') . "\n"
                        . "<a href='{$url}'>点击查看更多详情</a>"
                ]
            ]);

            $response = $response->toArray();

            if ($response['errcode'] == '0' && $response['errmsg'] == 'ok') {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Log::error(__FUNCTION__ . ':' . $e->getMessage());
            return false;
        }
    }
}
