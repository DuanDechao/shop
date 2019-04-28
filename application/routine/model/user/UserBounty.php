<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2018/02/28
 */

namespace app\routine\model\user;


use basic\ModelBasic;
use service\SystemConfigService;
use think\Model;

class UserBounty
{
    public static function giveUserRegisterBounty($uid, $spread_uid){
		$register_bounty = $spread_uid ? SystemConfigService::get('spread_register_user_bounty') : SystemConfigService::get('common_register_user_bounty');
		$spread_bounty = SystemConfigService::get('spread_user_bounty');
		$desc = $spread_uid ? '注册(推荐)获得'.floatval($register_bounty).'奖励金' : '注册(未推荐)获得'.floatval($register_bounty).'奖励金';
		$user = User::getUserInfo($uid);
		$spread_user = User::getUserInfo($spread_uid);
        ModelBasic::beginTrans();
        $res1 = UserBill::income('注册奖励金',$uid,'register_money','register', $register_bounty ,0 ,$user['register_money'], $desc);
		$res2 = User::bcInc($uid,'register_money',$register_bounty,'uid');
		$res3 = $spread_uid ? UserBill::income('推荐奖励金',$spread_uid,'spread_money','spread', $spread_bounty ,0 ,$spread_user['spread_money'], '推荐用户获得'.floatval($spread_bounty).'奖励金') : 1;
		$res4 = $spread_uid ? User::bcInc($spread_uid,'spread_money',$spread_bounty,'uid') : 1;
        $res = $res1 && $res2 && $res3 && $res4;
        ModelBasic::checkTrans($res);
        if($res)
            return $register_bounty;
        else
            return false;
    }
	
	public static function giveUserBindSpreaderBounty($uid, $spread_uid){
		$register_bounty = SystemConfigService::get('spread_register_user_bounty') - SystemConfigService::get('common_register_user_bounty');
		$register_bounty = $register_bounty < 0 ? 0 : $register_bounty;
		$spread_bounty = SystemConfigService::get('spread_user_bounty');
		$user = User::getUserInfo($uid);
		$spread_user = User::getUserInfo($spread_uid);
        ModelBasic::beginTrans();
        $res1 = UserBill::income('注册奖励金',$uid,'register_money','register', $register_bounty ,0 ,$user['register_money'], '注册(绑定)获得'.floatval($register_bounty).'奖励金');
		$res2 = User::bcInc($uid,'register_money',$register_bounty,'uid');
		$res3 = UserBill::income('推荐奖励金',$spread_uid,'spread_money','spread', $spread_bounty ,0 ,$spread_user['spread_money'], '推荐用户获得'.floatval($spread_bounty).'奖励金');
		$res4 = User::bcInc($spread_uid,'spread_money',$spread_bounty,'uid');
        $res = $res1 && $res2 && $res3 && $res4;
        ModelBasic::checkTrans($res);
        if($res)
            return $register_bounty;
        else
            return false;
    }
}
