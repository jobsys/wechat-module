<?php

namespace Modules\Wechat\Enums;

enum WechatSns: string
{

    case Work = 'work'; //企业微信
    case OfficialAccount = 'official'; //公众号;
    case Open = 'open'; //公开平台
    case Pay = 'pay'; //支付
    case MiniProgram = 'mini_program'; //小程序

}
