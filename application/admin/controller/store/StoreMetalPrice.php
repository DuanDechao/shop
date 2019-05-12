<?php

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use app\admin\model\ump\StoreCouponIssue;
use app\admin\model\wechat\WechatUser as UserModel;
use service\FormBuilder as Form;
use service\JsonService;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UtilService;
use think\Request;
use app\admin\model\ump\StoreCoupon as CouponModel;
use think\Url;
use think\Db;

/**
 * 优惠券控制器
 * Class StoreCategory
 * @package app\admin\controller\system
 */
class StoreMetalPrice extends AuthController
{

    /**
     * @return mixed
     */
    public function index()
    {
		$list = Db::name('metalPrice')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
}
