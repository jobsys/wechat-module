<?php

namespace Modules\Wechat\Http\Controllers;


use EasyWeChat\OfficialAccount\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\Wechat\Services\WechatService;

class WechatController extends Controller
{

    public function serve(WechatService $service)
    {
        Log::info('wechat request arrived.');

        $server = $service->workApp()->getServer();

        $server->with(function ($message) {
            return "";
        });

        return $server->serve();
    }

    public function workRedirect(WechatService $service)
    {
        Log::info('wechat work redirect.');
        $redirect_url = $service->workApp()->getOAuth()->redirect(route('wechat.work.login'));
        return response()->redirectTo($redirect_url);
    }
}
