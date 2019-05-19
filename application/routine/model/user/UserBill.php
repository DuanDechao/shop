<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/30
 */

namespace app\routine\model\user;


use basic\ModelBasic;
use traits\ModelTrait;

class UserBill extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    public static function income($title,$uid,$category,$type,$number,$link_id = 0,$balance = 0,$mark = '',$status = 1, $alias_id = 0)
    {
        $pm = 1;
        return self::set(compact('title','uid','link_id','category','type','number','balance','mark','status','pm', 'alias_id'));
    }

    public static function expend($title,$uid,$category,$type,$number,$link_id = 0,$balance = 0,$mark = '',$status = 1, $alias_id = 0)
    {
        $pm = 0;
        return self::set(compact('title','uid','link_id','category','type','number','balance','mark','status','pm', 'alias_id'));
    }

	public static function sumPrice($uid, $alias_id, $category)
	{
		return self::where('uid', $uid)->where('alias_id',$alias_id)->where('pm', 1)->where('category', $category)->value('sum(number)');

	}

}
