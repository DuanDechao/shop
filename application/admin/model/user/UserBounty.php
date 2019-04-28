<?php
/**
 * Created by PhpStorm.
 * User: liying
 * Date: 2018/7/20
 * Time: 18:08
 */

namespace app\admin\model\user;

use app\admin\model\user\User;
use app\admin\model\user\UserBill;
use traits\ModelTrait;
use basic\ModelBasic;
use service\PHPExcelService;


class UserBounty extends ModelBasic
{
    use ModelTrait;
    /*
     * 获取注册奖励金信息
     * */
    public static function  systemPage($where){
        $model= new UserBill();
        if($where['status']!='')UserBill::where('status',$where['status']);
         if($where['title']!='')UserBill::where('title','like',"%$where[status]%");
        $model->where('category','register_money')->select();
        return $model::page($model);
    }
   /*
    *
    * 异步获取奖励金信息
    * */
    public static function getbountylist($where){
        $list=self::setWhere($where, '')
            ->order('a.add_time desc')
            ->field(['a.*','b.nickname'])
            ->page((int)$where['page'],(int)$where['limit'])
            ->select()
            ->toArray();
         foreach ($list as $key=>$item){
             $list[$key]['add_time']=date('Y-m-d', $item['add_time']);
         }
        $count=self::setWhere($where, '')->field(['a.*','b.nickname'])->count();
        return ['count'=>$count,'data'=>$list];
    }
    //生成Excel表格并下载
    public static function SaveExport($where){
        $list=self::setWhere($where)->field(['a.*','b.nickname'])->select();
        $Export=[];
        foreach ($list as $key=>$item){
            $Export[]=[
                $item['id'],
                $item['title'],
                $item['balance'],
                $item['number'],
                $item['mark'],
                $item['nickname'],
                date('Y-m-d H:i:s',$item['add_time']),
            ];
        }
        PHPExcelService::setExcelHeader(['编号','标题','奖励金余量','明细数字','备注','用户微信昵称','添加时间'])
            ->setExcelTile('奖励金日志','奖励金日志'.time(),'生成时间：'.date('Y-m-d H:i:s',time()))
            ->setExcelContent($Export)
            ->ExcelSave();
    }
    public static function setWhere($where, $category){
        $model=UserBill::alias('a')->join('__USER__ b','a.uid=b.uid','left');
		if($category == ''){
			$model = $model->whereor('a.category', 'register_money')->whereor('a.category', 'spread_money')->whereor('a.category', 'distribution_money')->whereor('a.category','rebate_money');
		}
		else{
			$model = $model->where('a.category', $category);
		}
        $time['data']='';
        if($where['start_time']!='' && $where['end_time']!=''){
            $time['data']=$where['start_time'].' - '.$where['end_time'];
        }
        $model=self::getModelTime($time,$model,'a.add_time');
        if($where['nickname']!=''){
            $model=$model->where('b.nickname|b.uid','like',$where['nickname']);
        }
        return $model;
    }
    //获取积分头部信息
    public static function getUserbountyBadgelist($where){
        return [
            [
                'name'=>'总注册金',
                'field'=>'元',
                'count'=>self::setWhere($where, 'register_money')->sum('a.number'),
                'background_color'=>'layui-bg-blue',
            ],
            [
                'name'=>'总推荐金',
				'field'=>'元',
				'count'=>self::setWhere($where, 'spread_money')->sum('a.number'),
                'background_color'=>'layui-bg-cyan',
            ],
            [
                'name'=>'总分销金',
                'field'=>'元',
				'count'=>self::setWhere($where, 'distribution_money')->sum('a.number'),
                'background_color'=>'layui-bg-cyan',
            ],
            [
                'name'=>'总返利金',
                'field'=>'元',
				'count'=>self::setWhere($where, 'rebate_money')->sum('a.number'),
                'background_color'=>'layui-bg-cyan',
            ],
        ];
    }
}
