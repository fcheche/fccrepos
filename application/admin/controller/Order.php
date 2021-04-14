<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * Author: 当燃
 * Date: 2015-09-09
 */
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

class Order extends Base {
    public  $order_status;
    /**
     * 初始化操作
     */
    public function _initialize() {
        parent::_initialize();
        C('TOKEN_ON',false); // 关闭表单令牌验证
        if (empty(session('admin_id'))) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        } 
        $this->order_status = C('ORDER_STATUS');
        // 订单 支付 发货状态
        $this->assign('order_status',$this->order_status);
    }

     // 使用红包
    public function order_voucher(){
            $id = I('post.order_id/d', 0);

            $where['order_id'] = $id;
            // 订单详情
            $order = M('order_list')->where($where)->find();
            if (!$order) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'订单不存在']);
            }

            if ($order['coupon_price']>0) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'该订单已使用过一次优惠，不能叠加使用']);
            }

            // 买家信息
            $user_id=$order['user_id'];
            $maijiaselect = Db::name('users')->where(['user_id'=>$user_id])->find();
            // 买家领取红包金额
            $usertel=$maijiaselect['mobile'];
            $maijiav = Db::name('voucher')->where(['tel'=>$usertel,'order_id'=>0])->find();
           
            if (!$maijiav) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'该订单买家没有可用红包']);
            }

            $time=time();
            $act_where['is_finished']=0;
            $act_where['start_time']=array('lt',$time);
            $act_where['end_time']=array('gt',$time);
            $act = M('goods_activity')->where($act_where)->find();
            if (!$act) {
               $this->ajaxReturn(['status'=>-3,'msg'=>'该活动已结束！']);
            }

            // 红包使用记录
            $data['order_id'] =$order['order_sn']; // 记录使用的订单号
            $row = M('voucher')->where(['tel'=>$usertel])->save($data);

            // 订单总价业绩调整
            $update['coupon_price'] = $maijiav['price'];//红包抵扣费用
            $row1 = M('order_list')->where(array('order_id'=>$id))->save($update);

            // 后台操作记录
            $add['log_time'] = time();
            $add['admin_id'] = session('admin_id');
            $add['log_info'] = $order['order_sn'].'订单使用红包';
            $row2 = M('admin_log')->add($add);

            if(!$row || !$row1 ||!$row2){
                $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
            }

            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
        
    }

     // 使用代金券
    public function order_coupon(){
            $id = I('post.order_id');//使用的订单id
            $coupon_order_id = I('post.coupon_order_id');//购买的代金券订单id

            $where['order_id'] = $id;
            // 订单详情
            $order = M('order_list')->where($where)->find();
            if (!$order) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'订单不存在']);
            }

            if ($order['coupon_price']>0) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'该订单已使用过一次优惠，不能叠加使用']);
            }

            $wherec['order_id'] = $coupon_order_id;
            // 订单详情 代金券
            $orderc = M('order_list')->where($wherec)->find();
            if (!$orderc) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'代金券不存在']);
            }

            // 买家信息
            $user_id=$order['user_id'];
            $maijiaselect = Db::name('users')->where(['user_id'=>$user_id])->find();
            // 买家领取代金券金额
            $usertel=$maijiaselect['mobile'];
            $coupon = M('coupon')->where(['id' => $orderc['goods_id']])->find();
           
            if (!$coupon) {
                $this->ajaxReturn(['status'=>-3,'msg'=>'该代金券无效']);
            }

             // 查看购买的抵用券是否使用 type1看店卡type2代金券 1已使用
            $used=M('coupon_list')->where(['type' => 2,'status' => 1,'uid' => $user_id,'order_id'=>$coupon_order_id])->find();

            if ($used) {
                $this->ajaxReturn(['status' => 0, 'msg' => '对不起，您的该代金券已经使用完，请购买后使用！', 'result' => '']);
            }

            // 代金券使用记录 type1看店卡type2代金券
            $row=Db::name('coupon_list')->add(array('cid'=>$coupon['id'],'type'=>2,'status' => 1,'uid'=>$user_id,'order_id'=>$coupon_order_id,'goods_id'=>$order['goods_id'],'use_time'=>time()));

            // 订单总价业绩调整
            $update['coupon_price'] = $coupon['money'];//红包抵扣费用
            $row1 = M('order_list')->where(array('order_id'=>$id))->save($update);

            // 后台操作记录
            $add['log_time'] = time();
            $add['admin_id'] = session('admin_id');
            $add['log_info'] = $order['order_sn'].'订单使用代金券';
            $row2 = M('admin_log')->add($add);

            if(!$row || !$row1 ||!$row2){
                $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
            }

            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
        
    }

     /**
     * 业绩首页
     */
    public function yeji(){
        $begin = $this->begin;
        $end = $this->end;

        //销售推广查询
        $xs_name = I('get.xs_name');
        $gs_name = I('get.gs_name');

        $this->assign('xs_name',$xs_name);
        if ($xs_names) {
            $condition['xs_adminid'] = M('admin')->where(['user_name'=>$xs_name])->getField('admin_id');
            session('xs_name', $xs_name);
            session('gs_name', null);
           // echo "$xs_names";
        }elseif (session('xs_name')) {
           $condition['xs_adminid'] = M('admin')->where(['user_name'=>session('xs_name')])->getField('admin_id');
        }

        if ($gs_name) {
            $condition['gs_adminid'] = M('admin')->where(['user_name'=>$gs_name])->getField('admin_id');
            session('gs_name', $gs_name);
            session('xs_name', null);
           // echo "$gs_names";
        }elseif (session('gs_name')) {
           $condition['gs_adminid'] = M('admin')->where(['user_name'=>session('gs_name')])->getField('admin_id');
        }

        $condition['order_status'] = array('lt',5);

        $count = Db::name('order_list')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();

        $orderLists = Db::name('order_list')->where($condition)->limit($Page->firstRow,$Page->listRows)->order('add_time desc')->select();

        foreach ($orderLists as $ko => $valo) {
           
            $valo['gs_name']=M('admin')->where(['admin_id'=>$valo['gs_adminid']])->getField('user_name');

            $valo['xs_name']=M('admin')->where(['admin_id'=>$valo['xs_adminid']])->getField('user_name');

            $valo['consignee']=M('users')->where(['user_id'=>$valo['user_id']])->getField('nickname');

            $orderList[]=$valo;
        }
        // die;
        $admin_info = getAdminInfo(session('admin_id'));
        
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
      
        $CartLogic = new OrderLogic();
        $act_list = $CartLogic->act_list_select();//查看权限
        $this->assign('act_list',$act_list);

        $this->assign('roleid',$roleid);
        $this->assign('user_name',$admin_info['user_name']);
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }


    // 业绩点添加
    public function yejidian(){
        $order_id=I('post.order_id');
        $xs_yejid=I('post.xs_yejid');
        $gs_yejid=I('post.gs_yejid');
        if ($xs_yejid!="") {
             $r = M('order_list')->where(array('order_id'=>$order_id))->save(array('xs_yejid'=>$xs_yejid));
        }
        if ($gs_yejid!="") {
             $r = M('order_list')->where(array('order_id'=>$order_id))->save(array('gs_yejid'=>$gs_yejid));
        }
        if ($r) {
           $this->ajaxReturn(array('status'=>1,'msg'=>'录入成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'录入失败'));
        }

    }


   // 提交销售人
    public function savexs_adminid(){
        $data['xs_adminid']=I('post.xs_adminid');
        $where['order_id']=I('post.order_id');

        if ($where['order_id']) {
            $order=M('order')->where($where)->save($data);
        }
        if ($order) {
           $this->ajaxReturn(array('status'=>1,'msg'=>'提交成功'));
        }else{
            $this->ajaxReturn(array('status'=>-1,'msg'=>'提交失败'));
        }
    }
    
     /**
     * 订单详情
     * @return mixed
     */
    public function fadan_detail(){

        $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = $admin_info['admin_id'];
        $roleid = M('admin')->where(['admin_id'=>$admin_id])->getField('role_id');

        $order_id = input('order_id', 0);
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();

        // 卖家信息
        $nickname=trim($order['sellername']);
        $sellerselect = Db::name('users')->where(['nickname'=>$nickname])->find();
        // print_r($sellerselect);
        // 买家信息
        $user_id=$order['user_id'];
        $maijiaselect = Db::name('users')->where(['user_id'=>$user_id])->find();
        if(empty($order)){
            $this->error('订单不存在或已被删除');
        }

         //已退款金额或者已放款
        // $where['userid']=$order['user_id'];
        $where['order_id']=$order_id;
        $fk_price = M('fangkuan')->where($where)->sum('price');

        $this->assign('fk_price', $fk_price);
        $this->assign('roleid', $roleid);
        $this->assign('order', $order);
        $this->assign('maijiaselect', $maijiaselect);
        $this->assign('sellerselect', $sellerselect);
        return $this->fetch();
    }

    /**
     * 订单详情
     * @return mixed
     */
    public function detail(){

        $admin_info = getAdminInfo(session('admin_id'));
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');

        $order_id = input('order_id', 0);
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        $good = Db::name('goods')->where(['goods_id'=>$order['goods_id']])->find();
        if(empty($order)){
            $this->error('订单不存在或已被删除');
        }
         // 销售人
        $xs_adminid=$order['xs_adminid'];
        $xs_admin = Db::name('admin')->where(['admin_id'=>$xs_adminid])->getField('user_name');
        $this->assign('xs_admin', $xs_admin);

        // 挂售人
        $gs_adminid=$order['gs_adminid'];
        $gs_admin = Db::name('admin')->where(['admin_id'=>$gs_adminid])->getField('user_name');
        $this->assign('gs_admin', $gs_admin);


        // 卖家信息
        $nickname=trim($order['sellername']);
        $sellerselect = Db::name('users')->where(['nickname'=>$nickname])->find();
        // print_r($sellerselect);
        // 买家信息
        $user_id=$order['user_id'];
        $maijiaselect = Db::name('users')->where(['user_id'=>$user_id])->find();
        

         //该订单当前已放款金额
        $where['order_id']=$order_id;
        $fk_price = M('fangkuan')->where($where)->sum('price');
        $this->assign('fk_price', $fk_price);
        $this->assign('admin_info', $admin_info);
        $this->assign('roleid', $roleid);
        $this->assign('order', $order);
        $this->assign('good', $good);
        $this->assign('maijiaselect', $maijiaselect);
        $this->assign('sellerselect', $sellerselect);
        return $this->fetch();
    }

     /**
     * Ajax首页
     */
    public function ajaxindex(){
        $begin = $this->begin;
        $end = $this->end;
  
        I('deleted') ? $deleted = I('deleted') : $deleted = 0; //未下架 
        $condition['deleted'] = $deleted;
        // 搜索条件
        if (I('order_status')) {
            $condition['order_status'] = I('order_status');
        }else{
            $condition['order_status'] = array('lt',5);//未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
        }

        if (I('order_status')==2) {
            $condition['order_status'] = array('between',"2,3");
        }

        if (I('order_status')==5) {
            $condition['is_return'] = 1;
        }

        if (I('order_status')==6) {
            $condition['fadan_time'] = array('gt',1);//罚单
        }else{
            $condition['fadan_time'] = array('eq',0);//排除罚单
        }

        if (I('fadan_stime')&&I('fadan_etime')) {
            $condition['fadan_time'] = array('between',"I('fadan_stime'),I('fadan_etime')");
        }
        
        $condition['goods_type'] = I("goods_type");// 类别编号 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
        
        $consignee = I('consignee');
        $xs_name = I('xs_name');
        $gs_name = I('gs_name');
        if ($consignee) {
           $users=M('users')->where(['nickname'=>['like',"%$consignee%"]])->getField('user_id',true);
           $condition['user_id'] = ['in',$users];
        }
        if ($xs_name) {
           $condition['xs_adminid'] = M('admin')->where(['user_name'=>$xs_name])->getField('admin_id');
        }
        if ($gs_name) {
           $condition['gs_adminid'] = M('admin')->where(['user_name'=>$gs_name])->getField('admin_id');
        }

        $goods_sn = I("goods_sn",'','trim');
        $order_sn = I('order_sn','','trim');
        
        $goods_sn ? $condition['goods_sn'] = ['like',"%$goods_sn%"] : false;
        $order_sn ? $condition['order_sn'] = ['like',"%$order_sn%"] : false;

        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
               
        $sort_order = I('order_by','order_id').' '.I('sort','DESC');
        // print_r($condition);
        $count = Db::name('order_list')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();

        $orderLists = Db::name('order_list')->where($condition)->limit($Page->firstRow,$Page->listRows)->order($sort_order)->select();
        $sumyeji = 0;
        foreach ($orderLists as $ko => $valo) {
            $sumyeji = $sumyeji+$valo['shiji_yeji'];
            $valo['gs_name']=M('admin')->where(['admin_id'=>$valo['gs_adminid']])->getField('user_name');
            $valo['xs_name']=M('admin')->where(['admin_id'=>$valo['xs_adminid']])->getField('user_name');
            $valo['user_names']=M('users')->where(['user_id'=>$valo['user_id']])->getField('nickname');
            $orderList[]=$valo;
        }
        
        $admin_info = getAdminInfo(session('admin_id'));$roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $CartLogic = new OrderLogic();
        $act_list = $CartLogic->act_list_select();//查看权限
        $this->assign('act_list',$act_list);
        $this->assign('admin_info',$admin_info);
        $this->assign('roleid',$roleid);

        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        $this->assign('sumyeji',$sumyeji);
        return $this->fetch();
    }

    /**
     * 获取订单操作记录
     */
    public function getOrderAction(){
        $order_id = input('order_id/d',0);
        $order_id <= 0 && $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！']);
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        // 获取操作记录
        $action_log = Db::name('order_action')->where(['order_id'=>$order_id])->order('log_time desc')->select();
        $admins = M("admin")->getField("admin_id,user_name", true);
        $user = M("users")->field('user_id,nickname')->where(['user_id'=>$order['user_id']])->find();
        //查找用户昵称
        foreach ($action_log as $k => $v){
            if ($v['action_user'] == 0){
                $action_log["$k"]['action_user_name'] = '用户:'.$user['nickname'];
            }else{
                $action_log["$k"]['action_user_name'] = '管理员:'.$admins[$v['action_user']];
            }
            $action_log["$k"]["log_time"] = date('Y-m-d H:i:s',$v['log_time']);
            $action_log["$k"]["order_status"] = $this->order_status[$v['order_status']];
            $action_log["$k"]["pay_status"] = $this->pay_status[$v['pay_status']];
            $action_log["$k"]["shipping_status"] = $this->shipping_status[$v['shipping_status']];
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！','data'=>$action_log]);
    }


    /**
     * 订单编辑
     * @return mixed
     */
    public function edit_order(){
        $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = $admin_info['admin_id'];
        $roleid = M('admin')->where(['admin_id'=>$admin_id])->getField('role_id');
        $this->assign('roleid', $roleid);
        $this->assign('admin_id', $admin_id);

        $order_id = I('order_id');
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if($order['order_status'] > 3){
            $this->error('交接中订单不允许编辑');
            exit;
        }

        $orderGoods = Db::name('goods')->where(['goods_id'=>$order['goods_id']])->find();
       
        if(IS_POST)
        {
            $order['sellername'] = I('sellername');// 收货人
            $order['goods_type'] = I('goods_type');// 订单大类
            $order['admin_note'] = I('admin_note'); // 管理员备注
          
            $order['xs_adminid'] = I('xs_adminid');// 销售人
            if ($order['xs_adminid']) {
                $xs_corps_id=Db::name('admin')->where("admin_id", $order['xs_adminid'])->getField("corps_id");
                $order['xs_corps_id'] = $xs_corps_id;// 销售战队
            }
            
            $order['gs_adminid'] = I('gs_adminid');// 挂售人
            if ($order['gs_adminid']) {
                $gs_corps_id=Db::name('admin')->where("admin_id", $order['gs_adminid'])->getField("corps_id");
                $order['gs_corps_id'] = $gs_corps_id;// 挂售战队
            }
      
            $o = M('order_list')->where('order_id='.$order_id)->save($order);
           
            if ($o) {
                $CartLogic = new OrderLogic();
                $rec_id = $CartLogic->order_action_log($order_id,I('admin_note'),'修改订单',session('admin_id')); // 订单操作记录
            }
            
            if($rec_id){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
            exit;
        }
       
        // 查询淘宝所有员工 分了战队的
        $admins = Db::name('admin')->where("leibie > 0")->select();

        // 买家信息
        $user_id=$order['user_id'];
        $maijiaselect = Db::name('users')->where(['user_id'=>$user_id])->find();

        $this->assign('maijiaselect',$maijiaselect);
        $this->assign('order',$order);
        $this->assign('admins', $admins);
        $this->assign('orderGoods',$orderGoods);
        return $this->fetch();
    }



    /**
     * 价钱修改
     */
    public function editprice($order_id){
        
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if(IS_POST){
            $update['youhui_price'] = I('post.youhui_price',0);//优惠
            $update['chajia_price'] = I('post.chajia_price',0);//差价
            $update['kaizhi_price'] =I('post.kaizhi_price',0);//开支费用调整
            $update['paid_money'] = I('post.paid_money');//调整订金价格

            $row = M('order_list')->where(array('order_id'=>$order_id))->save($update);
           
            
            if(!$row){
                $this->success('没有更新数据',U('Admin/Order/editprice',array('order_id'=>$order_id)));
            }else{
                // 计算业绩重新录入
                $this->updateshijiyeji($order_id);
                // 计算业绩重新录入
                $this->success('操作成功',U('Admin/Order/detail',array('order_id'=>$order_id)));
            }
        }
        $admin_info = getAdminInfo(session('admin_id'));
        $this->assign('admin_info',$admin_info);
        $this->assign('order',$order);
        return $this->fetch();
    }

    public function updateshijiyeji($order_id){
         // 计算业绩重新录入
            $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
            $fk_price = M('fangkuan')->where("order_id", $order_id)->sum('price');//总共放款金额
            // 订单状态 0未付 1定金 2尾款 3全款 4完成 7取消
            if ($order['order_status']==0) {
                $shiji_yeji = 0;
            }
            // 支付金额不足店铺20%的 ，按实际支付业绩计算，之外按总业绩计算
            if ($order['order_status']==1 && ($order['paid_money']+$order['erci_price'])<($order['goods_price']*0.2)) {
                $shiji_yeji = ($order['paid_money']+$order['erci_price'])*5;
            }elseif ($order['order_status']<5) {
                $shiji_yeji = ($order['goods_price']*0.2-$order['youhui_price']-$order['kaizhi_price']-$order['coupon_price']-$fk_price)*5;
            }
            return M('order_list')->where(array('order_id'=>$order_id))->save(['shiji_yeji'=>$shiji_yeji]);
          // 计算业绩重新录入
    }

    /**
     * 订单删除
     * @param int $id 订单id
     */
    public function delete_order(){
        $order_id = I('post.order_id/d',0);
    	$order = new \app\common\logic\Order($order_id);
        $order->setOrderById($order_id);
        try{
            $order->adminDelOrder();
            $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }


    /**
     * 订单打印
     * @param string $id
     * @return mixed
     */
    public function order_print($id=''){
        if($id){
            $order_id = $id;
        }else{
            $order_id = I('order_id');
        }
        $orderModel = new OrderModel();
        $orderObj = $orderModel::get(['order_id'=>$order_id]);
        $order =$orderObj->append(['full_address','orderGoods','delivery_method'])->toArray();
        $order['province'] = getRegionName($order['province']);
        $order['city'] = getRegionName($order['city']);
        $order['district'] = getRegionName($order['district']);
        $order['full_address'] = $order['province'].' '.$order['city'].' '.$order['district'].' '. $order['address'];
        if($id){
            return $order;
        }else{
            $shop = tpCache('shop_info');
            $this->assign('order',$order);
            $this->assign('shop',$shop);
            $template = I('template','picking');
            return $this->fetch($template);
        }
    }


    /**
     * 退款操作
     */
    public function return_info()
    {
        $return_id = I('id');
        $return_goods = M('return_goods')->where(['id'=> $return_id])->find();
        !$return_goods && $this->error('非法操作!');
        $user = M('users')->where(['user_id' => $return_goods['user_id']])->find();
        $order = M('order_list')->where(array('order_id'=>$return_goods['order_id']))->find();
        $order['goods'] = M('goods')->where(['goods_id' => $return_goods['goods_id']])->find();
       
        $this->assign('id',$return_id); // 用户
        $this->assign('user',$user); // 用户
        $this->assign('return_goods',$return_goods);// 退换货
        $this->assign('order',$order);//退货订单信息
        $this->assign('return_type',C('RETURN_TYPE'));//退货订单信息
        $this->assign('refund_status',C('REFUND_STATUS'));
        return $this->fetch();
    }


     // 放款买家操作
    public function fangkuan_user(){

            $order_id = I('post.order_id');
            $price = I('post.price');
            $beizhu = I('post.beizhu');

            $CartLogic = new OrderLogic();
            $res=$CartLogic->fangkuan($order_id,$price,$beizhu,2);//$type1放款 2退款
            if ($res['status'] == -1) {
                $this->ajaxReturn(['status'=>-3,'msg'=>$res['msg']]);
            }
            
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
    }
   

     // 放款卖家操作
    public function fangkuan_seller(){

            $CartLogic = new OrderLogic();
            $order_id = I('post.order_id');
            $price = I('post.price');
            $beizhu = I('post.beizhu');
            $fangkuan_type = I('post.fangkuan_type');

            // 如果是对开支放款，则单独对开支账户进行调整记录
            if ($fangkuan_type==1) {
                $res=$CartLogic->fangkuan($order_id,$price,$beizhu,1,666);//$type1放款 2退款 放款到id为666的账户
            }
            
            $res=$CartLogic->fangkuan($order_id,$price,$beizhu,1);//$type1放款 2退款
            if ($res['status'] == -1) {
                $this->ajaxReturn(['status'=>-3,'msg'=>$res['msg']]);
            }
          
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
    }


/**
     * 获取放款记录
     */
    public function fangkuanAction(){
        $order_id = input('order_id/d',0);
        $order_id <= 0 && $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！']);
        $where['order_id'] = $order_id;
        $order = M('order_list')->where($where)->find();

        $sellerwhere['nickname|mobile']=trim($order['sellername']);
        $seller = Db::name('users')->where($sellerwhere)->find();
        // 获取操作记录
        $action_log = Db::name('fangkuan')->where(['order_id'=>$order_id,'sellerid'=>$seller['user_id']])->order('add_time desc')->select();
      
        //时间格式化
        foreach ($action_log as $k => $v){
            $action_log["$k"]["add_time"] = date('Y-m-d H:i:s',$v['add_time']);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！','data'=>$action_log]);
    }

         /**
     * 获取退款记录
     */
    public function tuikuanAction(){
        $order_id = input('order_id/d',0);
        $order_id <= 0 && $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！']);
        $where['order_id'] = $order_id;
        $order = M('order_list')->where($where)->find();
        // 获取操作记录
        $action_log = Db::name('fangkuan')->where(['order_id'=>$order_id,'userid'=>$order['user_id']])->order('add_time desc')->select();
      
        //时间格式化
        foreach ($action_log as $k => $v){
            $action_log["$k"]["add_time"] = date('Y-m-d H:i:s',$v['add_time']);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！','data'=>$action_log]);
    }

      

     /**
     * 支付订单定金
     */
    public function order_pay(){

        $order_id=I('order_id'); //订单号
        $order_pay_status=I('order_pay_status'); //订单号
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在', 'result' => ''];
        }

        $CartLogic = new OrderLogic();
        $res=$CartLogic->pay_money($order_id,$order_pay_status);//未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
        if ($res['status'] == -1) {
            $this->ajaxReturn(['status' => -1, 'msg' => $res['msg'], 'result' => '']);
        }else{
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
        }
    }

    // 订单退款
    public function order_tuikuan()
    {

            $id = I('post.order_id');

            $where['order_id'] = $id;
            
            $order = M('order_list')->where($where)->find();

            if($order['order_status'] > 3){
                $this->ajaxReturn(['status'=>-1,'msg'=>'该订单不能退款']);
            }
            if($order['order_status'] == 0){
                    $this->ajaxReturn(['status'=>-1,'msg'=>'商家未确定付款，该订单暂不能退款']);
            }

            $admin_info = getAdminInfo(session('admin_id'));
            $data['describe'] = '管理员'.$admin_info['user_name'].'提交退款申请';
            $data['addtime'] = time();
            $data['user_id'] = $order['user_id'];
            $data['cat_id'] = $order['goods_type'];
            $data['reason'] = '后台退款';
            $data['order_id'] = $id;
            $data['order_sn'] = $order['order_sn'];
            $data['goods_id'] = $order['goods_id'];
            $data['goods_num'] = 1;

            $fk_price = M('fangkuan')->where($where)->sum('price');//订单放款金额
        
            if($order['order_status']==1){
                $data['refund_deposit'] = round($order['paid_money']+$order['erci_price']-$fk_price,2);//退款余额 支付订金
            }else{
                $data['refund_deposit'] = round($order['total_amount']-$order['youhui_price']-$order['coupon_price']-$fk_price,2);//该退余额支付部分 退款金额
            }
   
            $result = M('return_goods')->add($data);
            if ($result) {
                    if(!$row){
                        $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
                    }

                $CartLogic = new OrderLogic();
                $rec_id = $CartLogic->order_action_log($id,'操作订单退款','操作订单退款',session('admin_id')); // 订单操作记录
                $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
            }else{
                 $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
            }
    }


 /**
     * 支付订单定金
     */
    public function order_dingjin(){

        $order_id=I('order_id'); //订单号
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在', 'result' => ''];
        }

        $CartLogic = new OrderLogic();
        $res=$CartLogic->pay_money($order_id,1);//$order_status未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
        if ($res['status'] == -1) {
            $this->ajaxReturn(['status' => -1, 'msg' => $res['msg'], 'result' => '']);
        }else{
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Order/detail',['order_id'=>$order_id])]);
        }
    }

     // 支付尾款
    public function order_weikuan(){
           
        $order_id=I('post.order_id/d', 0); //订单号
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在', 'result' => ''];
        }

        $CartLogic = new OrderLogic();
        $res=$CartLogic->pay_money($order_id,2);//$order_status未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
        if ($res['status'] == -1) {
            $this->ajaxReturn(['status' => -1, 'msg' => $res['msg'], 'result' => '']);
        }else{
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Order/detail',['order_id'=>$order_id])]);
        }
          
    }

    // 支付全款
    public function order_quankuan(){
           
        $order_id=I('post.order_id/d', 0); //订单号
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在', 'result' => ''];
        }

        $CartLogic = new OrderLogic();
        $res=$CartLogic->pay_money($order_id,3);//$order_status未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
        if ($res['status'] == -1) {
            $this->ajaxReturn(['status' => -1, 'msg' => $res['msg'], 'result' => '']);
        }else{
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Order/detail',['order_id'=>$order_id])]);
        }
          
    }


    // 订单完成
    public function order_confirm(){
            $id = I('post.order_id/d', 0);

            $where['order_id'] = $id;
           
            $order = M('order_list')->where($where)->find();
           if (!$order) {
                $this->ajaxReturn(['status'=>-1,'msg'=>'订单不存在']);
            }
            if ($order['confirm_time']>0) {
                $data['confirm_time'] = $order['confirm_time']; // 完成确认时间
            }else{
                $data['confirm_time'] = time(); // 完成确认时间
            }
            $data['order_status'] = 4; // 已收货
            
            $row = M('order_list')->where(array('order_id'=>$id))->save($data);
            if(!$row){
                $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
            }

            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Order/index',['id'=>$id])]);
        
    }

    public function order_log(){
    	$order_sn = I('order_sn');
    	$condition = array();
        $begin = $this->begin;
        $end = $this->end;
        $condition['log_time'] = array('between',"$begin,$end");
        if($order_sn){   //搜索订单号
            $order_id_arr = Db::name('order')->where(['order_sn' => $order_sn])->getField('order_id',true);
            $order_ids =implode(',',$order_id_arr);
            $condition['order_id']=['in',$order_ids];
            $this->assign('order_sn',$order_sn);
        }

    	$admin_id = I('admin_id');
		if($admin_id >0 ){
			$condition['action_user'] = $admin_id;
		}
    	$count = M('order_action')->where($condition)->count();
    	$Page = new Page($count,20);

    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($begin.'_'.$end);
    	}
    	$show = $Page->show();
    	$list = M('order_action')->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $orderIds = [];
        foreach ($list as $log) {
            if (!$log['action_user']) {
                $orderIds[] = $log['order_id'];
            }
        }
        if ($orderIds) {
            $users = M("users")->alias('u')->join('__ORDER__ o', 'o.user_id = u.user_id')->getField('o.order_id,u.nickname');
        }
        $this->assign('users',$users);
    	$this->assign('list',$list);
    	$this->assign('pager',$Page);
    	$this->assign('page',$show);   	
    	$admin = M('admin')->getField('admin_id,user_name');
    	$this->assign('admin',$admin);    	
    	return $this->fetch();
    }


    /**
     * 导出订单
     */
    public function export_order()
    {
    	//搜索条件
        $order_status = I('order_status','');
        $order_ids = I('order_ids');
        $prom_type = I('prom_type'); //订单类型
        $keyType =   I("key_type");  //查找类型
        $keywords = I('keywords','','trim');
        $where= ['add_time'=>['between',"$this->begin,$this->end"]];
        if(!empty($keywords)){
            $keyType == 'mobile'   ? $where['mobile']  = $keywords : false;
            $keyType == 'order_sn' ? $where['order_sn'] = $keywords: false;
            $keyType == 'consignee' ? $where['consignee'] = $keywords: false;
        }
        $prom_type != '' ? $where['prom_type'] = $prom_type : $where['prom_type'] = ['lt',5];
        if($order_status>-1 && $order_status != ''){
            $where['order_status'] = $order_status;
        }
        if($order_ids){
            $where['order_id'] = ['in', $order_ids];
        }
        $orderList = Db::name('order')->field("*,FROM_UNIXTIME(add_time,'%Y-%m-%d') as create_time")->where($where)->order('order_id')->select();
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">订单编号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">日期</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货人</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">收货地址</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">电话</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">订单金额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">实际支付</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付方式</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">支付状态</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">发货状态</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">店铺数量</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">店铺信息</td>';
    	$strTable .= '</tr>';
	    if(is_array($orderList)){
	    	$region	= get_region_list();
	    	foreach($orderList as $k=>$val){
	    		$strTable .= '<tr>';
	    		$strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['order_sn'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].' </td>';	    		
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['consignee'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'."{$region[$val['province']]},{$region[$val['city']]},{$region[$val['district']]},{$region[$val['twon']]}{$val['address']}".' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['goods_price'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['order_amount'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_name'].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->pay_status[$val['pay_status']].'</td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$this->shipping_status[$val['shipping_status']].'</td>';
	    		$orderGoods = D('order_goods')->where('order_id='.$val['order_id'])->select();
	    		$strGoods="";
                $goods_num = 0;
	    		foreach($orderGoods as $goods){
                    $goods_num = $goods_num + $goods['goods_num'];
	    			$strGoods .= "店铺编号：".$goods['goods_sn']." 店铺名称：".$goods['goods_name'];
	    			if ($goods['spec_key_name'] != '') $strGoods .= " 规格：".$goods['spec_key_name'];
	    			$strGoods .= "<br />";
	    		}
	    		unset($orderGoods);
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$goods_num.' </td>';
	    		$strTable .= '<td style="text-align:left;font-size:12px;">'.$strGoods.' </td>';
	    		$strTable .= '</tr>';
	    	}
	    }
    	$strTable .='</table>';
    	unset($orderList);
    	downloadExcel($strTable,'order');
    	exit();
    }

    // 提交更新退款详情
    public function update_return_info(){

        $refund_deposit = I('post.refund_deposit');//退款金额
        $status = I('post.status');//审核意见 1审核通过 -1审核失败
        $is_on_sale = I('post.is_on_sale');//是否在售
        $remark = I('post.remark');//处理备注 退款描述
        $goods_id = I('post.goods_id');//商品
        $order_id = I('post.order_id');//订单
        $id = I('post.id');//退款申请id

        // 拒绝退款操作成功
        if ($status == -1) {
            $datas['is_return'] = 0; // 恢复订单正常状态
            $row = M('order_list')->where(array('order_id'=>$order_id))->save($datas);
            
            $post_data['status']=$status;
            $post_data['remark']=$remark;
            $result = M('return_goods')->where(['id'=>$id])->save($post_data);
            if ($result) {
                $this->ajaxReturn(['status'=>1,'msg'=>'订单退款申请已驳回！','url'=>'']);
            }else{
                $this->ajaxReturn(['status'=>-1,'msg'=>'操作失败','url'=>'']);
            }

        // 同意退款操作
        }else{
           // 修改状态
           $update['is_on_sale']=$is_on_sale;
           M("Goods")->where(['goods_id'=>$goods_id])->save($update);  //商品表
           // 修改退款状态
           $post_data['refund_deposit']=$refund_deposit;
           $post_data['status']=$status;
           $post_data['remark']=$remark;
           $result = M('return_goods')->where(['id'=>$id])->save($post_data);
           if ($result) {
                $this->ajaxReturn(['status'=>1,'msg'=>'操作成功！','url'=>'']);
            }else{
                $this->ajaxReturn(['status'=>-1,'msg'=>'操作失败','url'=>'']);
            }
        }
    }

     // 退款到用户余额
    public function return_money(){
        $id = I('id');
        $refund_deposit = I('refund_deposit');//退款金额

        $return_goods = M('return_goods')->where(['id'=>$id])->find();
        
        if ($return_goods['status']==5) {
           $this->ajaxReturn(['status'=>0,'msg'=>"请勿重复操作"]);
        }
        if(empty($return_goods)) $this->ajaxReturn(['status'=>0,'msg'=>"参数有误"]);

        $where['order_id'] = $return_goods['order_id'];
            
        $order = M('order_list')->where($where)->find();
        $fk_price = M('fangkuan')->where($where)->sum('price');//订单放款金额

        if($order['order_status']==1){
            $zuiduo_return_money = round($order['paid_money']+$order['erci_price']-$fk_price,2);//退款余额 支付订金
        }else{
            $zuiduo_return_money = round($order['total_amount']-$order['youhui_price']-$order['coupon_price']-$fk_price,2);//该退余额支付部分 退款金额
        }

        if ($refund_deposit>$zuiduo_return_money) {
            $this->ajaxReturn(['status'=>0,'msg'=>"退款金额超出范畴，请认真审核"]);
        }

        $post_data['status']=5;//退款完成状态
        $post_data['refund_type']=1;//退款类型
        $post_data['refund_time']=time();//退款时间
        $post_data['refund_deposit']=$refund_deposit;//退款完成状态
        $result = M('return_goods')->where(['id'=>$id])->save($post_data);

        $user_id=$return_goods['user_id'];
        $user_money=$refund_deposit;
        $order_id=$return_goods['order_id'];
        $order_sn=$return_goods['order_sn'];

        // 资金变动记录
        $account_log = array(
            'user_id'       => $user_id,
            'user_money'    => $user_money,
            'change_time'   => time(),
            'desc'   => '订单退款余额',
            'order_id' => $order_id,
            'order_sn' => $order_sn
        );
        $zhifujine=M('users')->where(array('user_id'=>$user_id))->setInc('user_money',$user_money);//增加余额支付的金额
        if($zhifujine){
            M('account_log')->add($account_log);
        }

        // 退款操作记录
        $data['addtime'] = time();
        $data['admin_id'] = session('admin_id');
        $data['money'] = $user_money;
        $data['type'] = 1;
        $data['log_type_id'] = $order_id;
        $data['user_id'] = $user_id;
        M('expense_log')->add($data);

        Db::name('order_list')->where(['order_id'=>$return_goods['order_id']])->save(['is_return'=>1]);//修改订单状态为作废，以后给6也行，不然统计销售额的时候会统计进去
     
        $this->ajaxReturn(['status'=>1,'msg'=>"操作成功"]);
    }

    /*
     * ajax 退款订单列表
     */
    public function ajax_return_list(){
        // 搜索条件
        $order_sn =  trim(I('order_sn'));
        $order_by = I('order_by','') ? I('order_by') : 'id';
        $sort_order = I('sort_order') ? I('sort_order') : 'desc';
        $status =  I('status');
        $where['goods_type'] = I('goods_type');
        if($order_sn){
            $where['order_sn'] =['like', '%'.$order_sn.'%'];
        }
        if($status != ''){
            $where['status'] = $status;
        }
        $count = M('return_goods')->where($where)->count();
        $Page  = new AjaxPage($count,13);
        $show = $Page->show();
        $list = M('return_goods')->where($where)->order("$order_by $sort_order")->limit("{$Page->firstRow},{$Page->listRows}")->select();
        $goods_id_arr = get_arr_column($list, 'goods_id');
        if(!empty($goods_id_arr)){
            $goods_list = M('goods')->where("goods_id in (".implode(',', $goods_id_arr).")")->getField('goods_id,goods_name');
        }
        $state = C('REFUND_STATUS');
        $return_type = C('RETURN_TYPE');
        $this->assign('adminid',session('admin_id'));
        $this->assign('state',$state);
        $this->assign('return_type',$return_type);
        $this->assign('goods_list',$goods_list);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }

    /**
     * 删除订单日志
     */
    public function delOrderLogo(){
        $ids = I('ids');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'url'  =>'']);
        $order_ids = rtrim($ids,",");
        $res = Db::name('order_action')->whereIn('order_id',$order_ids)->delete();
        if($res !== false){
            $this->ajaxReturn(['status' => 1,'msg' =>"删除成功！",'url'  =>'']);
        }else{
            $this->ajaxReturn(['status' => -1,'msg' =>"删除失败",'url'  =>'']);
        }
    }
}
