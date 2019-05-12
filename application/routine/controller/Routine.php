<?php
namespace app\routine\controller;
use behavior\routine\PaymentBehavior;
use service\HookService;
use service\RoutineNotify;
use think\Log;
use think\Request;
use service\UtilService as Util;
use app\routine\model\routine\RoutineServer;
use service\RoutineTemplateService;


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
	public function checkSignature()
	{
		if(Request::instance()->isGet()){
			$signature = $_GET["signature"];
			$timestamp = $_GET["timestamp"];
			$nonce = $_GET["nonce"];
		
			$token = 'fumibi2019';
			$tmpArr = array($token, $timestamp, $nonce);
			sort($tmpArr, SORT_STRING);
			$tmpStr = implode( $tmpArr );
			$tmpStr = sha1( $tmpStr );
		
			if ($tmpStr == $signature ) {
				return $_GET['echostr'];
			} else {
				return false;
			}
		}
		else{
			$requestData = Request::instance()->post();
            $data['touser'] =  $requestData['FromUserName'];//接收者（用户）的 openid
            $data['msgtype'] = "text"; //所需下发的模板消息的id
            $data['text']['content'] = "很高兴为你服务"; //点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
			RoutineTemplateService::sendCustomerMessage($data);
		}
	}
}


