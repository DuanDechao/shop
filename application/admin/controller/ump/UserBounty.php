<?php

namespace app\admin\controller\ump;

use app\admin\controller\AuthController;
use service\UtilService as Util;
use app\admin\model\user\UserBounty AS UserBountyModel;
use app\admin\model\user\UserRebateApply AS UserRebateApplyModel;
use app\routine\model\user\UserRebateApply AS RoutineUserRebateApplyModel;
use think\Url;
use app\admin\model\user\UserBill;
use app\admin\model\user\User as UserModel;
use service\JsonService as Json;
use service\FormBuilder as Form;
use think\Request;
use service\SystemConfigService;

/**
 * 优惠券控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class UserBounty extends AuthController
{

    /**
     * @return mixed
     */
    public function index()
    {
        $this->assign([
            'is_layui'=>true,
            'year'=>getMonth('y'),
        ]);
        return $this->fetch();
    }

    /**
     * @return mixed
     */
    public function create()
    {
        $this->assign(['title'=>'添加优惠券','action'=>Url::build('save'),'rules'=>$this->rules()->getContent()]);
        return $this->fetch('public/common_form');
    }
    //异步获取奖励金列表
    public function getbountylist(){
        $where = Util::getMore([
            ['start_time',''],
            ['end_time',''],
            ['nickname',''],
            ['page',1],
            ['limit',10],
        ]);
        return Json::successlayui(UserBountyModel::getbountylist($where));
    }
    //导出Excel表格
    public function export(){
        $where = Util::getMore([
            ['start_time',''],
            ['end_time',''],
            ['nickname',''],
        ]);
        UserBountyModel::SaveExport($where);
    }
    //获取奖励金日志头部信息
    public function getuserbountybadgelist(){
        $where = Util::getMore([
            ['start_time',''],
            ['end_time',''],
            ['nickname',''],
        ]);
        return Json::successful(UserBountyModel::getUserbountyBadgelist($where));
    }

    public function rebate_bounty_apply(){
        $this->assign('apply_count',count(UserRebateApplyModel::where('state', 0)->select()->toArray()));
		$platforms = RoutineUserRebateApplyModel::getRebateApplyPlatforms();
		$this->assign('platforms', array_keys($platforms));
        return $this->fetch();
    }
	
	public function get_rebate_bounty_apply_list(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['order',''],
            ['order_id',''],
            ['state','0'],
            ['platform',''],
            ['apply_time',''],
        ]);
        return Json::successlayui(UserRebateApplyModel::getApplyList($where));
    }

	public function accept_rebate_apply($id)
	{
        if(!$id) return Json::fail('数据不存在!');
        $apply = UserRebateApplyModel::get($id)->toArray();
        if(!$apply) return Json::fail('数据不存在!');
		$res = UserRebateApplyModel::giveUserRebateBounty($id, $apply['uid'], $apply['rebate_price']);
        return Json::successful($res ? '成功':'失败');
	}

	public function refuse_rebate_apply($id)
	{
        $field = [
            Form::input('reason','理由')->type('textarea')
        ];
        $form = Form::create(Url::build('update_rebate_apply_info', array('id'=>$id)));
        $form->setMethod('post')->setTitle('确认')->components($field)->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
	}

	public function update_rebate_apply_info(Request $request, $id){
        $data = Util::postMore([
			['reason',''],
		], $request);
		$res = UserRebateApplyModel::where(['id' => $id])->update(['state' => 2, 'reason'=> $data['reason']]);
        return Json::successful($res ? '成功':'失败');
	}

}
