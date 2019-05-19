<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/30
 */

namespace app\routine\model\user;


use basic\ModelBasic;
use traits\ModelTrait;
use think\Log;
use service\SystemConfigService;

class UserRebateApply extends ModelBasic
{
    use ModelTrait;

    //protected $insert = ['apply_time'];

    //protected function setApplyTimeAttr()
    //{
    //   return time();
   // }

    public static function applyRebate($uid, $order_id,$platform,$consume_price = 0, $rebate_price = 0)
    {
        //return self::set(compact('uid','order_id','platform','consume_price'));
		$insertData['uid'] = $uid;
		$insertData['order_id'] = $order_id;
		$insertData['platform'] = $platform;
		$insertData['consume_price'] = $consume_price;
		$insertData['rebate_price'] = $rebate_price;
		$insertData['apply_time'] = time();
		$insertData['state'] = 0;
        return self::set($insertData);
    }

	public static function getRebateApplyList($uid, $status, $platform, $page, $limit)
	{
		$model = UserRebateApply::where('uid', $uid);
		if($status == 0 || $status == 1 || $status == 2)
		{
			$model = $model->where('state', $status);
		}
		if($platform != '')
		{
			$model = $model->where('platform', $platform);
		}
		$res = $model->page((int)$page,(int)$limit)
			->order('apply_time DESC')->select()
            ->each(function ($item){
				$item['apply_time']=date('Y-m-d H:i:s',$item['apply_time']);
				if($item['state'] == 0)
					$item['state'] = '未审核';
				else if($item['state'] == 1)
					$item['state'] = '通过';
				else if($item['state'] == 2)
					$item['state'] = '不通过';
			})->toArray();
		return $res;
	}	

	public static function getRebateApplyPlatforms()
	{
		$applyPlatforms = SystemConfigService::get('rebate_apply_platform');
		$applyPlatforms = str_replace("\r\n", "\n", $applyPlatforms);
		$platforms = explode("\n", $applyPlatforms);
		$res = [];
		foreach($platforms as $platform)
		{
			$items = explode(",", $platform);
			if(count($items) == 2)
				$res[$items[0]] = $items[1];
		}
		return $res;
	}
}
