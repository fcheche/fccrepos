<?php
/**
订单管理
 */
namespace app\home\controller;
use app\common\logic\CartLogic;
use app\common\logic\OrderLogic;
use app\common\logic\CommentLogic;
use app\common\logic\UsersLogic;
use app\common\model\OrderGoods;
use app\common\util\TpshopException;
use app\common\model\Order as OrderModel;
use think\Db;
use think\Page;

class Order extends Base {

	public $user_id = 0;
    public $order_status = 0;
	public $user = array();

    public function _initialize() {      
        parent::_initialize();
        if(session('?user'))
        {
        	$user = session('user');             
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
        	$this->assign('user_id',$this->user_id);
        }else{
            redirect()->remember();
            $this->redirect('User/login');
        }
        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user',$navigate_user);        
    }


    /**
     * 提交订单的确认页面
     */
    public function addorder()
    {
        $goods_id = input("goods_id/d"); // 店铺id
        $this->order_status=I('order_status'); //传值 下单类型  定金1尾款2或者全款3
       
        if ($this->user_id == 0) {
            $this->error('请先登录', U('Home/User/login'));
        }
        
        $where['goods_id']=$goods_id;
        $where['is_on_sale']=1;
        $where['is_delete']=0;
        $goods = M('Goods')->where($where)->find();
        $goods['catname']=M("GoodsCategory")->where("id",$goods['cat_id'])->cache(true)->getField('name');
        $goods['pingtai'] = Db::name('goods_attribute')->where("attr_id", $goods['pingtai_id'])->cache(true)->getField('attr_name');
        if (!$goods) {
            $this->error('商品已下架');
        }
       
        $this->assign('goods', $goods);//店铺优惠总价
        return $this->fetch();
    }


      // 添加订单ajax
    public function do_addorder(){
        $post_goods_id=I('goods_id');
        $CartLogic = new OrderLogic();
        $addorder = $CartLogic->add_order($this->user_id,$post_goods_id,$this->user_id); // 订单编号
       if ($addorder['status']==1) {
             $this->success("下单成功", U('Home/Order/payorder',array('order_id'=>$addorder['result'])));
        }else{
            $this->error($addorder['msg']);
        }
    }

    
    /**
     * 支付订单
     */
    public function payorder(){
        $order_id=I('order_id'); //order_id
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        $order['order_status']=I('order_status'); //传值 下单类型  定金1尾款2或者全款3
        $this->assign('order', $order);//订单
        if (empty($order)) {
            $this->error('订单有误！');
        }
        $goods = M('Goods')->where("goods_id", $order['goods_id'])->find();
        $goods['catname']=M("GoodsCategory")->where("id",$goods['cat_id'])->cache(true)->getField('name');
        $goods['pingtai'] = Db::name('goods_attribute')->where("attr_id", $goods['pingtai_id'])->cache(true)->getField('attr_name');
        $this->assign('goods', $goods);//店铺优惠总价
        return $this->fetch();
    }

    /**
     * 执行支付订单
     */
    public function do_payorder(){
        $paypwd=I('paypwd'); //支付密码
        $order_id=I('order_id'); //订单号
        $order_status=I('order_status'); //传值 下单类型  定金1尾款2或者全款3
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        if (!$order) {
            return ['status' => -1, 'msg' => '订单不存在', 'result' => ''];
        }
    
        try{
            $userid = $order['user_id'];
            $users = Db::name('users')->where(['user_id' => $userid])->find();
            if ($paypwd) {
                if ($users['paypwd']!=encrypt($paypwd)) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '支付密码错误', 'result' => '']);
                }
            }else{
                $this->ajaxReturn(['status' => -1, 'msg' => '请输入支付密码', 'result' => '']);
            }

            $CartLogic = new OrderLogic();
            $res=$CartLogic->pay_money($order_id,$order_status);//$order_status未付款0  付定金1  付尾款2  一次性全款付3  完成4  取消订单7
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
        if ($res['status']==1) {
            $this->ajaxReturn(['status' => 1, 'msg' => $res['msg'], 'result' => '']);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => $res['msg'], 'result' => '']);
        }
    }

    /*
     * 订单列表
     */
    public function order_list(){
        $where = 'user_id= '.$this->user_id;
        //条件搜索
       if(I('get.type')=='0'){
           $where .= ' and order_status=0';
       }elseif(I('get.type')=='1'){
           $where .= ' and order_status=1';
       }elseif(I('get.type')=='2'){
           $where .= ' and (order_status=2 or order_status=3)';
       }elseif(I('get.type')=='4'){
           $where .= ' and order_status=4';
       }

       // print_r($where);
       // 搜索订单 根据商品名称 或者 订单编号
       $search_key = trim(I('search_key'));       
       if($search_key)
       {
          $where .= " and (order_sn like '%$search_key%')";
       }
       
        $count = Db::name('order_list')->where($where)->count();
        $Page = new Page($count,10);

        $show = $Page->show();
        $order_str = "order_id DESC";
        $order_list = Db::name('order_list')->order($order_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        $model = new UsersLogic();
        foreach($order_list as $k=>$v)
        {
            // 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
            $order_list[$k]['goods_name']=M('goods')->where(['goods_id' => $v['goods_id']])->getField("goods_name");
            $order_list[$k]['catname']=M("GoodsCategory")->where("id",$v['cat_id'])->cache(true)->getField('name');
            $order_list[$k]['pingtai'] = Db::name('goods_attribute')->where("attr_id", $v['pingtai_id'])->cache(true)->getField('attr_name');
        }
        // print_r(Db::name('order_list')->getlastsql());
        $this->assign('lists',$order_list);
        return $this->fetch();
    }

    /*
     * 订单详情
     */
    public function order_detail(){
        $id = input('id/d', 0);
        $order = Db::name('order_list')->where(['order_id'=>$id])->find();

        // 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
        $goods = Db::name("goods")->where(['goods_id'=>$order['goods_id']])->find();

        $goods['catname']=M("GoodsCategory")->where("id",$goods['cat_id'])->cache(true)->getField('name');
        $goods['pingtai'] = Db::name('goods_attribute')->where("attr_id", $goods['pingtai_id'])->cache(true)->getField('attr_name');
        
        if (!$order) {
            $this->error('没有获取到订单信息');
        }
       
        $this->assign('goods', $goods);
        $this->assign('order', $order);
        return $this->fetch();
    }

    /**
     *  点赞
     * @author dyr
     */
    public function ajaxZan()
    {
        $comment_id = I('post.comment_id/d');
        $user_id = $this->user_id;
        $comment_info = Db::name('comment')->where(array('comment_id' => $comment_id))->find();
        $comment_user_id_array = explode(',', $comment_info['zan_userid']);
        if (in_array($user_id, $comment_user_id_array)) {
            $result['success'] = 0;
        } else {
            array_push($comment_user_id_array, $user_id);
            $comment_user_id_string = implode(',', $comment_user_id_array);
            $comment_data['zan_num'] = $comment_info['zan_num'] + 1;
            $comment_data['zan_userid'] = $comment_user_id_string;
            Db::name('comment')->where(array('comment_id' => $comment_id))->save($comment_data);
            $result['success'] = 1;
        }
        exit(json_encode($result));
    }

    /**
     * 申请退款
     */
    public function return_goods()
    {
        $order_id = I('order_id',0);
        $return_goods = Db::name('return_goods')->where(array('order_id'=>$order_id))->find();
        if(!empty($return_goods))
        {
            $this->error('已经提交过退款申请!');
        }
        
        $order = Db::name('order_list')->where(array('order_id'=>$order_id,'user_id'=>$this->user_id))->find();

        $fk_price = M('fangkuan')->where(array('order_id'=>$order_id))->sum('price');//订单放款金额

        if($order['order_status']==1){
            $order['return_money'] = $order['paid_money']+$order['erci_price']-$fk_price;   //只支付了定金或二次付款
        }else{
            $order['return_money'] = $order['total_amount']-$fk_price;   //订单总额
        }
        $order['goods_name'] = M('goods')->where(['goods_id' => $order['goods_id']])->getField("goods_name");
        $order['pingtai_name'] = Db::name('goods_attribute')->where("attr_id", $order['pingtai_id'])->cache(true)->getField('attr_name');

        if(empty($order)){
            $this->error('非法操作');
        }
        if ($order['pay_status'] == 0) {
            $this->error('商家未确定付款，该订单暂不能退款!');
           //未支付
        }
        if($order['order_status']==2)
        {
            $this->error('订单已完成!',U('Order/order_detail',array('id'=>$order['order_id'])));
        }
        if($order['order_status']==3)
        {
            $this->error('订单已取消!',U('Order/order_detail',array('id'=>$order['order_id'])));
        }
        if($order['order_status']==5)
        {
            $this->error('订单已退款!',U('Order/order_detail',array('id'=>$order['order_id'])));
        }
        if(IS_POST)
        {
            $model = new OrderLogic();
            $res = $model->addReturnGoods($order);  //申请售后
            if($res['status']==1)$this->success($res['msg'],U('Order/order_list'));
            $this->error($res['msg']);
        }
       
    	$this->assign('order',$order);
        return $this->fetch();
    }
   
}