<?php
namespace app\routine\controller;
use behavior\routine\PaymentBehavior;
use service\HookService;
use service\RoutineNotify;
use think\Log;


/**
 * 小程序支付回调
 * Class Routine
 * @package app\routine\controller
 */
class Routine
{
    /**
     *   支付  异步回调
     */
    public function notify()
    {
        $result = RoutineNotify::notify();
		Log::record('PayNotify:'.var_export($result, true), 'info');
        if($result) HookService::listen('wechat_pay_success_'.strtolower($result['attach']),$result['out_trade_no'],$result,true,PaymentBehavior::class);
    }
}


