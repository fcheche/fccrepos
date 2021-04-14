<?php

namespace app\admin\controller;
use app\admin\logic\RefundLogic;
use app\admin\logic\KdniaoLogic;
use app\common\logic\PlaceOrder;
use app\common\model\Order as OrderModel;
use app\common\logic\Pay;
use app\common\model\OrderGoods;
use app\common\logic\OrderLogic;
use app\common\util\TpshopException;
use think\AjaxPage;
use think\Page;
use think\Db;

class Orderwx extends Base {
    public  $order_status;
    public  $goods_type;
    /**
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
        if (empty(session('admin_id'))) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        }
        C('TOKEN_ON',false); // 关闭表单令牌验证
        $this->order_status = C('ORDER_STATUS');
        $this->goods_type = 4;// 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
        // 订单状态  类型
        $this->assign('order_status',$this->order_status);
        $this->assign('goods_type',$this->goods_type);
    }
   
    
    /**
     *罚单
     */
    public function fadan_list(){
       
        $this->assign('order_status',6);
        return $this->fetch('order/fadan_list');
    }


      /**
     *商标转让订单首页
     */
    public function index(){
        return $this->fetch('order/index');
    }


  
    /**
     *商标转让完成订单首页
     */
    public function end_order(){
       
        $this->assign('order_status',4);
        return $this->fetch('order/end_order');
    }

    /**
     * 退单列表
     */
    public function return_list(){
        return $this->fetch('order/return_list');
    }

}
