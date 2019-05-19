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
		$register_bounty = SystemConfigService::get('common_register_user_bounty');
		$spread_bounty = SystemConfigService::get('spread_user_bounty');
		$desc = '注册获得'.floatval($register_bounty).'奖励金';
		$user = User::getUserInfo($uid);
		ModelBasic::beginTrans();
        $res = UserBill::income('注册金',$uid,'register_money','register', $register_bounty ,0 ,$user['register_money'], $desc, 1, $uid);
		$res = $res && User::bcInc($uid,'register_money',$register_bounty,'uid');
		if($spread_uid && $spread_uid != $uid){
			$spread_user = User::getUserInfo($spread_uid);
			$res = $res && UserBill::income('推荐金',$uid,'spread_money','spread', $spread_bounty ,0 ,$user['spread_money'], '获得'.floatval($spread_bounty).'推荐奖励金', 1, $spread_uid);
			$res = $res && User::bcInc($uid,'spread_money',$spread_bounty,'uid');
			$res = $res && UserBill::income('推荐金',$spread_uid,'spread_money','spread', $spread_bounty ,0 ,$spread_user['spread_money'], '获得'.floatval($spread_bounty).'推荐奖励金', 1, $uid);
			$res = $res && User::bcInc($spread_uid,'spread_money',$spread_bounty,'uid');
		}
        ModelBasic::checkTrans($res);
        if($res)
            return $register_bounty;
        else
            return false;
    }
	
	public static function giveUserBindSpreaderBounty($uid, $spread_uid){
		if($uid == $spread_uid){
			return false;
		}
		//$register_bounty = SystemConfigService::get('spread_register_user_bounty') - SystemConfigService::get('common_register_user_bounty');
		//$register_bounty = $register_bounty < 0 ? 0 : $register_bounty;
		$spread_bounty = SystemConfigService::get('spread_user_bounty');
		$user = User::getUserInfo($uid);
		$spread_user = User::getUserInfo($spread_uid);
        ModelBasic::beginTrans();
		$res = UserBill::income('推荐金',$spread_uid,'spread_money','spread', $spread_bounty ,0 ,$spread_user['spread_money'], '获得'.floatval($spread_bounty).'推荐奖励金', 1, $uid);
		$res = $res && User::bcInc($spread_uid,'spread_money',$spread_bounty,'uid');
		$res = $res && UserBill::income('推荐金',$uid,'spread_money','spread', $spread_bounty ,0 ,$user['spread_money'], '获得'.floatval($spread_bounty).'推荐奖励金', 1, $spread_uid);
		$res = $res && User::bcInc($uid,'spread_money',$spread_bounty,'uid');
        ModelBasic::checkTrans($res);
        if($res)
            return $register_bounty;
        else
            return false;
    }
}
