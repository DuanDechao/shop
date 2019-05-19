<?php

namespace app\admin\controller\store;

use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use app\admin\model\store\StoreProductAttr;
use app\admin\model\store\StoreProductAttrResult;
use app\admin\model\store\StoreProductRelation;
use app\admin\model\system\SystemConfig;
use service\JsonService;
use traits\CurdControllerTrait;
use service\UtilService as Util;
use service\JsonService as Json;
use service\UploadService as Upload;
use think\Request;
use app\admin\model\store\StoreCategory as CategoryModel;
use app\admin\model\store\StoreProduct as ProductModel;
use think\Url;
use app\admin\model\ump\StoreSeckill as StoreSeckillModel;
use app\admin\model\order\StoreOrder as StoreOrderModel;
use app\admin\model\ump\StoreBargain as StoreBargainModel;
use app\admin\model\system\SystemAttachment;
use think\Log;
use think\Db;


/**
 * 产品管理
 * Class StoreProduct
 * @package app\admin\controller\store
 */
class StoreProduct extends AuthController
{

    use CurdControllerTrait;

    protected $bindModel = ProductModel::class;

    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {

        $type=$this->request->param('type');
        //获取分类
        $this->assign('cate',CategoryModel::getTierList());
        //出售中产品
        $onsale =  ProductModel::where(['is_show'=>1,'is_del'=>0])->count();
        //待上架产品
        $forsale =  ProductModel::where(['is_show'=>0,'is_del'=>0])->count();
        //仓库中产品
        $warehouse =  ProductModel::where(['is_del'=>0])->count();
        //已经售馨产品
        $outofstock = ProductModel::getModelObject()->where(ProductModel::setData(4))->count();
        //警戒库存
        $policeforce =ProductModel::getModelObject()->where(ProductModel::setData(5))->count();
        //回收站
        $recycle =  ProductModel::where(['is_del'=>1])->count();

        $this->assign(compact('type','onsale','forsale','warehouse','outofstock','policeforce','recycle'));
        return $this->fetch();
    }
    /**
     * 异步查找产品
     *
     * @return json
     */
    public function product_ist(){
        $where=Util::getMore([
            ['page',1],
            ['limit',20],
            ['store_name',''],
            ['cate_id',''],
            ['excel',0],
            ['type',$this->request->param('type')]
        ]);
        return JsonService::successlayui(ProductModel::ProductList($where));
    }
    /**
     * 设置单个产品上架|下架
     *
     * @return json
     */
    public function set_show($is_show='',$id=''){
        ($is_show=='' || $id=='') && JsonService::fail('缺少参数');
        $res=ProductModel::where(['id'=>$id])->update(['is_show'=>(int)$is_show]);
        if($res){
            return JsonService::successful($is_show==1 ? '上架成功':'下架成功');
        }else{
            return JsonService::fail($is_show==1 ? '上架失败':'下架失败');
        }
    }
    /**
     * 快速编辑
     *
     * @return json
     */
    public function set_product($field='',$id='',$value=''){
        $field=='' || $id=='' || $value=='' && JsonService::fail('缺少参数');
        if(ProductModel::where(['id'=>$id])->update([$field=>$value]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }
    /**
     * 设置批量产品上架
     *
     * @return json
     */
    public function product_show(){
        $post=Util::postMore([
            ['ids',[]]
        ]);
        if(empty($post['ids'])){
            return JsonService::fail('请选择需要上架的产品');
        }else{
            $res=ProductModel::where('id','in',$post['ids'])->update(['is_show'=>1]);
            if($res)
                return JsonService::successful('上架成功');
            else
                return JsonService::fail('上架失败');
        }
    }
    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
//        $this->assign(['title'=>'添加产品','action'=>Url::build('save'),'rules'=>$this->rules()->getContent()]);
//        return $this->fetch('public/common_form');
		$metalLabels = array();
		$metalPrices = Db::name('MetalPrice')->field('id, name')->select();
		foreach($metalPrices as $levelId => $metalPrice){
			array_push($metalLabels, ['value'=>$metalPrice['id'], 'label'=> $metalPrice['name']]);
		}
        $field = [
            Form::select('cate_id','产品分类')->setOptions(function(){
                $list = CategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name'],'disabled'=>$menu['pid']== 0];//,'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('store_name','产品名称')->col(Form::col(24)),
            Form::input('store_info','产品简介')->type('textarea'),
            Form::input('keyword','产品关键字')->placeholder('多个用英文状态下的逗号隔开'),
            Form::input('unit_name','产品单位','件'),
            Form::frameImageOne('image','产品主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')))->icon('image')->width('100%')->height('550px')->spin(0),
            Form::frameImages('slider_image','产品轮播图(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')))->maxLength(5)->icon('images')->width('100%')->height('550px')->spin(0),
			Form::select('price_type','定价类型')->options($metalLabels)->col(8),
            Form::number('price','产品售价')->min(0)->col(8),
            Form::number('ot_price','产品市场价')->min(0)->col(8),
            Form::number('metal_diff','金属差价(/g)')->min(0)->col(8),
            Form::number('ot_metal_diff','市场金属差价(/g)')->min(0)->col(8),
            Form::number('metal_weight','金器克数')->min(0)->col(8),
            Form::number('give_integral','赠送积分')->min(0)->precision(0)->col(8),
            Form::number('postage','邮费')->min(0)->col(Form::col(8)),
            Form::number('sales','销量')->min(0)->precision(0)->col(8),
            Form::number('ficti','虚拟销量')->min(0)->precision(0)->col(8),
            Form::number('stock','库存')->min(0)->precision(0)->col(8),
            Form::number('cost','产品成本价')->min(0)->col(8),
            Form::number('sort','排序')->col(8),
            Form::radio('is_show','产品状态',0)->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
            Form::radio('is_hot','热卖单品',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_benefit','促销单品',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_best','精品推荐',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_new','首发新品',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_postage','是否包邮',0)->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ];
        $form = Form::create(Url::build('save'));
        $form->setMethod('post')->setTitle('添加产品')->components($field)->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload()
    {
        $res = Upload::image('file','store/product/'.date('Ymd'));
        $thumbPath = Upload::thumb($res->dir);
        //产品图片上传记录
        $fileInfo = $res->fileInfo->getinfo();
        SystemAttachment::attachmentAdd($res->fileInfo->getSaveName(),$fileInfo['size'],$fileInfo['type'],$res->dir,$thumbPath,1);
        if($res->status == 200)
            return Json::successful('图片上传成功!',['name'=>$res->fileInfo->getSaveName(),'url'=>Upload::pathToUrl($thumbPath)]);
        else
            return Json::fail($res->error);
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        $data = Util::postMore([
            ['cate_id',[]],
            'store_name',
            'store_info',
            'keyword',
            ['unit_name','件'],
            ['image',[]],
            ['slider_image',[]],
            'postage',
			['price_type',[]],
            'ot_price',
			'price',
			'metal_diff',
			'ot_metal_diff',
			'metal_weight',
            'sort',
            'stock',
            'sales',
            'ficti',
            ['give_integral',0],
            ['is_show',0],
            ['cost',0],
            ['is_hot',0],
            ['is_benefit',0],
            ['is_best',0],
            ['is_new',0],
            ['mer_use',0],
            ['is_postage',0],
        ],$request);
        if(count($data['cate_id']) < 1) return Json::fail('请选择产品分类');
        if(count($data['price_type']) < 1) return Json::fail('请选择定价类型');
		$data['price_type'] = $data['price_type'][0];
        $data['cate_id'] = implode(',',$data['cate_id']);
        if(!$data['store_name']) return Json::fail('请输入产品名称');
//        if(!$data['store_info']) return Json::fail('请输入产品简介');
//        if(!$data['keyword']) return Json::fail('请输入产品关键字');
        if(count($data['image'])<1) return Json::fail('请上传产品图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传产品轮播图');
		if($data['price_type'] == 0){
			if($data['price'] == '' || $data['price'] < 0) return Json::fail('请输入产品售价');
			if($data['ot_price'] == '' || $data['ot_price'] < 0) return Json::fail('请输入产品市场价');
		}
		else{
			if($data['metal_diff'] == '' || $data['metal_diff'] < 0) return Json::fail('请输入金属差价');
			if($data['ot_metal_diff'] == '' || $data['ot_metal_diff'] < 0) return Json::fail('请输入市场金属差价');
			if($data['metal_weight'] == '' || $data['metal_weight'] < 0) return Json::fail('请输入金属克数');
		}
        if($data['postage'] == '' || $data['postage'] < 0) return Json::fail('请输入邮费');
        if($data['stock'] == '' || $data['stock'] < 0) return Json::fail('请输入库存');
        if($data['cost'] == '' || $data['ot_price'] < 0) return Json::fail('请输入产品成本价');
        if($data['sales'] == '' || $data['sales'] < 0) return Json::fail('请输入销量');
        if($data['give_integral'] < 0) return Json::fail('请输入赠送积分');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        $data['add_time'] = time();
        $data['description'] = '';
        ProductModel::set($data);
        return Json::successful('添加产品成功!');
    }


    public function edit_content($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign([
            'content'=>ProductModel::where('id',$id)->value('description'),
            'field'=>'description',
            'action'=>Url::build('change_field',['id'=>$id,'field'=>'description'])
        ]);
        return $this->fetch('public/edit_content');
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
		$metalLabels = array();
		$metalPrices = Db::name('MetalPrice')->field('id, name')->select();
		foreach($metalPrices as $levelId => $metalPrice){
			array_push($metalLabels, ['value'=>$metalPrice['id'], 'label'=> $metalPrice['name']]);
		}
        $form = Form::create(Url::build('update',array('id'=>$id)),[
            Form::select('cate_id','产品分类',explode(',',$product->getData('cate_id')))->setOptions(function(){
                $list = CategoryModel::getTierList();
                $menus=[];
                foreach ($list as $menu){
                    $menus[] = ['value'=>$menu['id'],'label'=>$menu['html'].$menu['cate_name'],'disabled'=>$menu['pid']== 0];//,'disabled'=>$menu['pid']== 0];
                }
                return $menus;
            })->filterable(1)->multiple(1),
            Form::input('store_name','产品名称',$product->getData('store_name')),
            Form::input('store_info','产品简介',$product->getData('store_info'))->type('textarea'),
            Form::input('keyword','产品关键字',$product->getData('keyword'))->placeholder('多个用英文状态下的逗号隔开'),
            Form::input('unit_name','产品单位',$product->getData('unit_name')),
            Form::frameImageOne('image','产品主图片(305*305px)',Url::build('admin/widget.images/index',array('fodder'=>'image')),$product->getData('image'))->icon('image')->width('100%')->height('550px'),
            Form::frameImages('slider_image','产品轮播图(640*640px)',Url::build('admin/widget.images/index',array('fodder'=>'slider_image')),json_decode($product->getData('slider_image'),1))->maxLength(5)->icon('images')->width('100%')->height('550px'),
			Form::select('price_type','定价类型',$product->getData('price_type'))->options($metalLabels)->filterable(1)->multiple(1)->col(8),
            Form::number('price','产品售价',$product->getData('price'))->min(0)->precision(2)->col(8),
            Form::number('ot_price','产品市场价',$product->getData('ot_price'))->min(0)->col(8),
            Form::number('metal_diff','金属差价(/g)',$product->getData('metal_diff'))->min(0)->col(8),
            Form::number('ot_metal_diff','市场金属差价(/g)',$product->getData('ot_metal_diff'))->min(0)->col(8),
            Form::number('metal_weight','金器克数',$product->getData('metal_weight'))->min(0)->col(8),
            Form::number('give_integral','赠送积分',$product->getData('give_integral'))->min(0)->precision(0)->col(8),
            Form::number('postage','邮费',$product->getData('postage'))->min(0)->col(8),
            Form::number('sales','销量',$product->getData('sales'))->min(0)->precision(0)->col(8),
            Form::number('ficti','虚拟销量',$product->getData('ficti'))->min(0)->precision(0)->col(8),
            Form::number('stock','库存',ProductModel::getStock($id)>0?ProductModel::getStock($id):$product->getData('stock'))->min(0)->precision(0)->col(8),
            Form::number('cost','产品成本价',$product->getData('cost'))->min(0)->col(8),
            Form::number('sort','排序',$product->getData('sort'))->col(8),
            Form::radio('is_show','产品状态',$product->getData('is_show'))->options([['label'=>'上架','value'=>1],['label'=>'下架','value'=>0]])->col(8),
            Form::radio('is_hot','热卖单品',$product->getData('is_hot'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_benefit','促销单品',$product->getData('is_benefit'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_best','精品推荐',$product->getData('is_best'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_new','首发新品',$product->getData('is_new'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8),
            Form::radio('is_postage','是否包邮',$product->getData('is_postage'))->options([['label'=>'是','value'=>1],['label'=>'否','value'=>0]])->col(8)
        ]);
        $form->setMethod('post')->setTitle('编辑产品')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload();');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }



    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        $data = Util::postMore([
            ['cate_id',[]],
            'store_name',
            'store_info',
            'keyword',
            ['unit_name','件'],
            ['image',[]],
            ['slider_image',[]],
			'postage',
			['price_type',[]],
            'ot_price',
            'price',
			'metal_diff',
			'ot_metal_diff',
			'metal_weight',
            'sort',
            'stock',
            'sales',
            'ficti',
            ['give_integral',0],
            ['is_show',0],
            ['cost',0],
            ['is_hot',0],
            ['is_benefit',0],
            ['is_best',0],
            ['is_new',0],
            ['mer_use',0],
            ['is_postage',0],
        ],$request);
        if(count($data['cate_id']) < 1) return Json::fail('请选择产品分类');
        if(count($data['price_type']) < 1) return Json::fail('请选择定价类型');
		$data['price_type'] = $data['price_type'][0];
        $data['cate_id'] = implode(',',$data['cate_id']);
        if(!$data['store_name']) return Json::fail('请输入产品名称');
//        if(!$data['store_info']) return Json::fail('请输入产品简介');
//        if(!$data['keyword']) return Json::fail('请输入产品关键字');
        if(count($data['image'])<1) return Json::fail('请上传产品图片');
        if(count($data['slider_image'])<1) return Json::fail('请上传产品轮播图');
        if(count($data['slider_image'])>5) return Json::fail('轮播图最多5张图');
		if($data['price_type'] == 0){
			if($data['price'] == '' || $data['price'] < 0) return Json::fail('请输入产品售价');
			if($data['ot_price'] == '' || $data['ot_price'] < 0) return Json::fail('请输入产品市场价');
		}
		else{
			if($data['metal_diff'] == '' || $data['metal_diff'] < 0) return Json::fail('请输入金属差价');
			if($data['ot_metal_diff'] == '' || $data['ot_metal_diff'] < 0) return Json::fail('请输入市场金属差价');
			if($data['metal_weight'] == '' || $data['metal_weight'] < 0) return Json::fail('请输入金属克数');
		}
        if($data['postage'] == '' || $data['postage'] < 0) return Json::fail('请输入邮费');
        if($data['cost'] == '' || $data['cost'] < 0) return Json::fail('请输入产品成本价');
        if($data['stock'] == '' || $data['stock'] < 0) return Json::fail('请输入库存');
        if($data['sales'] == '' || $data['sales'] < 0) return Json::fail('请输入销量');
        if($data['give_integral'] < 0) return Json::fail('请输入赠送积分');
        $data['image'] = $data['image'][0];
        $data['slider_image'] = json_encode($data['slider_image']);
        ProductModel::edit($data,$id);
        return Json::successful('修改成功!');
    }

    public function attr($id)
    {
        if(!$id) return $this->failed('数据不存在!');
        $result = StoreProductAttrResult::getResult($id);
        $image = ProductModel::where('id',$id)->value('image');
        $this->assign(compact('id','result','product','image'));
        return $this->fetch();
    }
    /**
     * 生成属性
     * @param int $id
     */
    public function is_format_attr($id = 0){
        if(!$id) return Json::fail('产品不存在');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('产品不存在');
        $attrFormat = attrFormat($attr)[1];
        if(count($detail)){
            foreach ($attrFormat as $k=>$v){
                foreach ($detail as $kk=>$vv){
                    if($v['detail'] == $vv['detail']){
                        $attrFormat[$k]['price'] = $vv['price'];
                        $attrFormat[$k]['cost'] = isset($vv['cost']) ? $vv['cost'] : $product['cost'];
                        $attrFormat[$k]['sales'] = $vv['sales'];
                        $attrFormat[$k]['pic'] = $vv['pic'];
                        $attrFormat[$k]['check'] = false;
                        $attrFormat[$k]['metal_diff'] = $vv['metal_diff'];
                        $attrFormat[$k]['metal_weight'] = $vv['metal_weight'];
                        $attrFormat[$k]['price_type'] = $vv['price_type'];
                        break;
                    }else{
                        $attrFormat[$k]['cost'] = $product['cost'];
                        $attrFormat[$k]['price'] = '';
                        $attrFormat[$k]['sales'] = '';
                        $attrFormat[$k]['pic'] = $product['image'];
                        $attrFormat[$k]['check'] = true;
                        $attrFormat[$k]['metal_diff'] = $product['metal_diff'];
                        $attrFormat[$k]['metal_weight'] = $product['metal_weight'];
                        $attrFormat[$k]['price_type'] = $product['price_type'];
                    }
                }
            }
        }else{
            foreach ($attrFormat as $k=>$v){
                $attrFormat[$k]['cost'] = $product['cost'];
                $attrFormat[$k]['price'] = $product['price'];
                $attrFormat[$k]['sales'] = $product['stock'];
                $attrFormat[$k]['pic'] = $product['image'];
                $attrFormat[$k]['check'] = false;
                $attrFormat[$k]['metal_diff'] = $product['metal_diff'];
                $attrFormat[$k]['metal_weight'] = $product['metal_weight'];
                $attrFormat[$k]['price_type'] = $product['price_type'];
            }
        }
        return Json::successful($attrFormat);
    }

    public function set_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        list($attr,$detail) = Util::postMore([
            ['items',[]],
            ['attrs',[]]
        ],$this->request,true);
        $res = StoreProductAttr::createProductAttr($attr,$detail,$id);
        if($res)
            return $this->successful('编辑属性成功!');
        else
            return $this->failed(StoreProductAttr::getErrorInfo());
    }

    public function clear_attr($id)
    {
        if(!$id) return $this->failed('产品不存在!');
        if(false !== StoreProductAttr::clearProductAttr($id) && false !== StoreProductAttrResult::clearResult($id))
            return $this->successful('清空产品属性成功!');
        else
            return $this->failed(StoreProductAttr::getErrorInfo('清空产品属性失败!'));
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        if(!$id) return $this->failed('数据不存在');
        $data['is_del'] = 1;
        if(!ProductModel::edit($data,$id))
            return Json::fail(ProductModel::getErrorInfo('删除失败,请稍候再试!'));
        else
            return Json::successful('删除成功!');
    }




    /**
     * 点赞
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function collect($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(StoreProductRelation::getCollect($id));
        return $this->fetch();
    }

    /**
     * 收藏
     * @param $id
     * @return mixed|\think\response\Json|void
     */
    public function like($id){
        if(!$id) return $this->failed('数据不存在');
        $product = ProductModel::get($id);
        if(!$product) return Json::fail('数据不存在!');
        $this->assign(StoreProductRelation::getLike($id));
        return $this->fetch();
    }
    /**
     * 修改产品价格
     * @param Request $request
     */
    public function edit_product_price(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['price',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = ProductModel::edit(['price'=>$data['price']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }

    /**
     * 修改产品库存
     * @param Request $request
     */
    public function edit_product_stock(Request $request){
        $data = Util::postMore([
            ['id',0],
            ['stock',0],
        ],$request);
        if(!$data['id']) return Json::fail('参数错误');
        $res = ProductModel::edit(['stock'=>$data['stock']],$data['id']);
        if($res) return Json::successful('修改成功');
        else return Json::fail('修改失败');
    }



}
