<?php

namespace Modules\WeChat\Http\Controllers;


use EasyWeChat\OfficialAccount\Application;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Modules\WeChat\Services\WeChatService;

class WeChatController extends Controller
{

    public function serve(WeChatService $service)
    {
        Log::info('wechat request arrived.');

        $server = $service->getWorkApp()->getServer();

        $server->with(function ($message) {
            return "";
        });

        return $server->serve();
    }

    public function workRedirect(WeChatService $service)
    {
        Log::info('wechat work redirect.');
        $redirect_url = $service->getWorkApp()->getOAuth()->redirect(route('wechat.work.login'));
        return response()->redirectTo($redirect_url);
    }
}
