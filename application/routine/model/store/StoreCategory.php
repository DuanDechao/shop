<?php
/**
 *
 * @author: xaboy<365615158@qq.com>
 * @day: 2017/12/12
 */

namespace app\routine\model\store;


use basic\ModelBasic;

class StoreCategory extends ModelBasic
{
    public static function pidByCategory($pid,$field = '*',$limit = 0)
    {
        $model = self::where('pid',$pid)->where('is_show',1)->field($field);
        if($limit) $model->limit($limit);
        return $model->select();
    }

	public static function pidBySidList($pid)
	{
		return self::where('pid',$pid)->field('id,cate_name,pid,pic')->select();
	}

    public static function pidBySidList1($pid)
    {
        $cates = self::where('pid',$pid)->where('is_show',1)->field('id,cate_name,pid,pic')->select()->toArray();
		foreach($cates as $k => $v){
			$cates[$k]['child'] = self::pidBySidList($v['id']);
		}
		return $cates;
    }

    public static function cateIdByPid($cateId)
    {
        return self::where('id',$cateId)->value('pid');
    }

}
