<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */
namespace app\mobile\controller;
use app\common\logic\CartLogic;
use app\common\logic\GoodsActivityLogic;
use app\common\logic\CouponLogic;
use app\common\logic\Integral;
use app\common\logic\OrderLogic;
use app\common\logic\Pay;
use app\common\logic\PlaceOrder;
use app\common\model\Combination;
use app\common\model\Goods;
use app\common\model\SpecGoodsPrice;
use app\common\util\TpshopException;
use app\common\model\Order as OrderModel;
use think\Db;
use think\Loader;
use think\Url;

class Cart extends MobileBase {

    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();
    /**
     * 析构流函数
     */
    public function  __construct() {
        parent::__construct();
        $this->cartLogic = new CartLogic();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id", $user['user_id'])->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];
            $this->assign('user', $user); //存储用户信息
            // 给用户计算会员价 登录前后不一样
            if ($user) {
                $discount = (empty((float)$user['discount'])) ? 1 : $user['discount'];
                if ($discount != 1) {
                    $c = Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->where('member_goods_price = goods_price')->count();
                    $c && Db::name('cart')->where(['user_id' => $user['user_id'], 'prom_type' => 0])->update(['member_goods_price' => ['exp', 'goods_price*' . $discount]]);
                }
            }
        }
    }

    public function index(){
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartList = $cartLogic->getCartList();//用户购物车
        $userCartGoodsTypeNum = $cartLogic->getUserCartGoodsTypeNum();//获取用户购物车商品总数
        $hot_goods = M('Goods')->where('is_hot=1 and is_on_sale=1')->limit(20)->cache(true,TPSHOP_CACHE_TIME)->select();
        $this->assign('hot_goods', $hot_goods);
        $this->assign('userCartGoodsTypeNum', $userCartGoodsTypeNum);
        $this->assign('cartList', $cartList);//购物车列表
        return $this->fetch();
    }

    // 订单退款
    public function order_tuikuan()
    {

        $id = I('post.order_id');
        $user_id = I('post.user_id');

        if($user_id){
            $where['user_id'] = $user_id;
        }else{
            $this->ajaxReturn(['status'=>-3,'msg'=>'找不到客户信息']);
        }

        if($id){
            $where['order_id'] = $id;
        }else{
            $this->ajaxReturn(['status'=>-3,'msg'=>'找不到订单信息']);
        }
        $order = M('order')->where($where)->find();
        if (!$order) {
           $this->ajaxReturn(['status'=>-3,'msg'=>'找不到订单信息']);
        }
        if($order['order_status'] > 1)
            $this->ajaxReturn(['status'=>-1,'msg'=>'该订单不能退款']);
        if($order['pay_status'] != 1){
            $this->ajaxReturn(['status'=>-1,'msg'=>'商家未确定付款，该订单暂不能退款']);
        }

        $order_goods = M('order_goods')->where(array('order_id'=>$id))->find();

        $data['addtime'] = time();
        $data['user_id'] = $order['user_id'];
        $data['cat_id'] = $order['catid0'];
        $data['reason'] = '客户申请退款';
        $data['order_id'] = $id;
        $data['order_sn'] = $order['order_sn'];
        $data['goods_id'] = $order_goods['goods_id'];
        $data['goods_num'] = 1;
        $data['rec_id'] = $order_goods['rec_id'];
        
            if($order['dingjin']==2){
                    // $data['refund_money'] = round($order['paid_money'],2); //退款金额
                    $data['refund_deposit'] = round($order['paid_money']+$order['erci_price'],2);//退款余额 支付订金
            }else{
                $data['refund_deposit'] = round($order['order_amount'],2);//该退余额支付部分 退款金额
            }
   
            $result = M('return_goods')->add($data);
            if ($result) {
                $datas['pay_status'] = 2; // 申请退款
                $row = M('order')->where(array('order_id'=>$id))->save($datas);
                    if(!$row){
                        $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
                    }
                    $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
            }else{
                 $this->ajaxReturn(['status'=>-3,'msg'=>'操作失败']);
            }
    }


    /**
     * 更新购物车，并返回计算结果
     */
    public function AsyncUpdateCart()
    {
        $cart = input('cart/a', []);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->AsyncUpdateCart($cart);
        $this->ajaxReturn($result);
    }

    /**
     *  购物车加减
     */
    public function changeNum(){
        $cart = input('cart/a',[]);
        if (empty($cart)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请选择要更改的商品', 'result' => '']);
        }
        $cartLogic = new CartLogic();
        $result = $cartLogic->changeNum($cart['id'],$cart['goods_num']);
        $this->ajaxReturn($result);
    }

    /**
     * 删除购物车商品
     */
    public function delete(){
        $cart_ids = input('cart_ids/a',[]);
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $result = $cartLogic->delete($cart_ids);
        if($result !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功','result'=>$result]);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'删除失败','result'=>$result]);
        }
    }

    /**
     * 查看个人订单列表
     */
    public function my_orderlist(){
        
        if (empty(I('post.user_id'))) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        //验证用户身份
        if (empty(I('post.username')) or empty(I('post.user_id'))) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        $usernames = I('post.username');
        $username=preg_replace('# #','',$usernames);
        $user_ids = I('post.user_id');
        $user_id=preg_replace('# #','',$user_ids);

        $condition['nickname'] = $username;
        $condition['user_id'] = $user_id;
        $admin = Db::name('users')->where($condition)->find();
        if (!$admin) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号或密码不正确','result'=>'']);
        }
        //验证用户身份结束
        $result = M('order')->alias('o')->join('__ORDER_GOODS__ g', 'g.order_id = o.order_id', 'LEFT')->field('o.order_id,o.order_sn,o.goods_sn,o.user_id,o.order_status,o.pay_status,o.dingjin,o.fadan_time,o.fadan_time,o.pay_time,o.catid0,o.goods_price,o.add_time,g.goods_id,g.goods_name')->where("o.user_id = $user_id")->order('pay_time','desc')->select(); 

        if($result !== false){
            $this->ajaxReturn(['status'=>1,'msg'=>'获取成功','result'=>$result]);
        }else{
            $this->ajaxReturn(['status'=>0,'msg'=>'获取失败','result'=>$result]);
        }
    }

     /**
     * 订单详情
     * @return mixed
     */
    public function order_info()
    {
        $id = I('id/d', 0);
        $user_id = I('user_id', 0);
        $Order = new OrderModel();
        $order = $Order::get(['order_id' => $id, 'user_id' => $user_id]);
        if (!$order) {
            $this->ajaxReturn(['status'=>0,'msg'=>'获取失败']);
        }

        $order_goods['goods_name'] = M('order_goods')->where("order_id", $id)->getField("goods_name");
        $order_goods['order_id'] = $id;
        $order_goods['goods_sn'] = $order['goods_sn'];
        $order_goods['catid0'] = $order['catid0'];
        $order_goods['goods_price'] = $order['goods_price'];
        $order_goods['order_amount'] = $order['order_amount'];
        $order_goods['add_time'] = $order['add_time'];
        $order_goods['dingjin'] = $order['dingjin'];
        $order_goods['paid_money'] = $order['paid_money'];
        $order_goods['order_status'] = $order['order_status'];
        $order_goods['order_sn'] = $order['order_sn'];
        $order_goods['bidbond'] = $order['bidbond'];
        $order_goods['techservices'] = $order['techservices'];
        $order_goods['shouxufei'] = $order['shouxufei'];
        //获取订单
        $this->ajaxReturn(['status'=>1,'msg'=>'获取成功','result'=>$order_goods]);
    }


    // 添加订单ajax
    public function add_order(){
        //验证用户身份
        if (empty(I('post.username')) or empty(I('post.user_id'))) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        $usernames = I('post.username');
        $username=preg_replace('# #','',$usernames);
        $user_id = I('post.user_id');
        $user_id=preg_replace('# #','',$user_id);

        $condition['nickname'] = $username;
        $condition['user_id'] = $user_id;
        $admin = Db::name('users')->where($condition)->find();
        if (!$admin) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号或密码不正确','result'=>'']);
        }
        //验证用户身份结束

        $post_goods_id=I('post.goods_id');
        $post_user_id=I('post.user_id');
        $post_goods_sn=I('post.goods_sn');
        if (empty($post_goods_id) or empty($post_user_id) or empty($post_goods_sn)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        if (!is_numeric($post_goods_id)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '店铺有误', 'result' => '']);
        }
        // 当前产品下单时间
        $addwhere['user_id']=$post_user_id;//买家id
        $addwhere['goods_sn']=$post_goods_sn;//店铺编号
        $addtime=M('order')->where($addwhere)->getField('add_time');
        $shicha=time()-$addtime;
        if ($addtime&&$shicha<60) {
            $this->ajaxReturn(['status' => -1, 'msg' => '一分钟内请勿重复下单', 'result' => '']);
        }

        $user = M('users')->where("user_id", $post_user_id)->find();
        if (!$user) {
            $this->ajaxReturn(['status' => -1, 'msg' => '找不到客户信息', 'result' => '']);
        }
        $goods = M('Goods')->where("goods_id", $post_goods_id)->find();
        if (!$goods) {
            $this->ajaxReturn(['status' => -1, 'msg' => '找不到店铺信息', 'result' => '']);
        }
        $add['user_id'] = $post_user_id; // 买家id
        $add['consignee'] = $user['nickname']; // 买家用户名
        $add['mobile'] = $user['mobile']; // 买家手机号

        $add['gs_name'] = $goods['guishu']; // 挂售人
        $corps_id=M('admin')->where("user_name",$goods['guishu'])->getField('corps_id');
        $zhuguan = M('admin_corps')->where("corps_id", $corps_id)->getField('zhuguan_name'); // 挂售主管
        if ($zhuguan) {
            $add['gs_zhuguan']=$zhuguan;
        }

        $CartLogic = new OrderLogic();
        $add['order_sn'] = $CartLogic->get_order_sn(); // 订单编号
        $add['goods_sn'] = $goods['goods_sn']; // 商品编号

        $add['catid0'] = $goods['catid0']; // 类别编号 581天猫582淘宝
        $add['cat_id'] = $goods['cat_id']; // 店铺类目
        $add['sellername'] = $goods['sellername']; // 卖家用户名
        $add['order_status'] = 0; // 订单状态

        $add['pay_status'] = 0; // 支付状态  0未支付1已支付
        $add['dingjin'] = 1; // 订金状态  0全额1未支付2订金3尾款
        $add['order_amount'] = $goods['shop_price']+$goods['bidbond']+$goods['techservices']+($goods['shop_price']*0.1);// 订单总额
        $add['total_amount'] = $goods['shop_price']+$goods['bidbond']+$goods['techservices']+($goods['shop_price']*0.1); // 订单总额
        $add['shiji_yeji'] = $goods['shop_price']; // 实际业绩
        $add['bidbond'] = $goods['bidbond']; // 消保金
        $add['techservices'] = $goods['techservices']; // 服务费
        $add['shouxufei'] = $goods['shop_price']*0.1; // 手续费
        $add['goods_price'] = $goods['shop_price']; // 店铺金额
        $add['add_time'] = time(); // 下单时间

        $order_id = M('order')->add($add);

        if ($order_id) {
            $data['order_id'] = $order_id; // 订单id
            $data['goods_id'] = $post_goods_id; // 商品id
            $data['goods_name'] = $goods['goods_name']; // 商品名称
            $data['goods_sn'] = $goods['goods_sn']; // 商品编号
            $data['goods_num'] = 1; // 商品数量    
            $data['final_price'] = $goods['shop_price']; // 商品价格
            $data['goods_price'] = $goods['shop_price']; // 商品价格
            $data['member_goods_price'] = $goods['shop_price']; // 商品价格
            $rec_id = M('order_goods')->add($data);
        }else{
            $this->ajaxReturn(['status' => -2, 'msg' => '下单错误', 'result' => '']);
        }

        if ($rec_id) {
            $this->ajaxReturn(['status' => 1, 'msg' => '下单成功', 'result' => $order_id]);
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '下单失败', 'result' => '']);
        }
    }

    //支付定金
    public function pay_dingjin(){

       //验证用户身份
        if (empty(I('post.username')) or empty(I('post.user_id'))) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        $usernames = I('post.username');
        $username=preg_replace('# #','',$usernames);
        $passwords = I('post.password');
        $password=preg_replace('# #','',$passwords);
        $user_id = I('post.user_id');
        $user_id=preg_replace('# #','',$user_id);

        $condition['nickname'] = $username;
        $condition['paypwd'] = encrypt($password);
        $condition['user_id'] = $user_id;
        $admin = Db::name('users')->where($condition)->find();
        if (!$admin) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号或密码不正确','result'=>'']);
        }
        //验证用户身份结束

        $post_order_id=I('post.order_id');
        $post_paid_money=I('post.paid_money');
        if (empty($post_order_id) or empty($post_paid_money)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        //查找需要支付的订单
        $order = M('order')->where("order_id",$post_order_id)->find();
        if (!$order) {
            $this->ajaxReturn(['status' => -1, 'msg' => '订单不存在', 'result' => '']);
        }

        // 查询余额够不够
        $userid = $order['user_id'];
        $users = Db::name('users')->where(['user_id' => $userid])->find();
        

          //查看余额是否够付定金
        if ($users['user_money'] > $post_paid_money || $users['user_money'] == $post_paid_money) {

                $zhifujine=M('users')->where(array('user_id'=>$users['user_id']))->setDec('user_money',$post_paid_money);//扣除余额支付的定金
                if (!$zhifujine) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '扣款失败', 'result' => $userid]);
                }
                $orderdata['dingjin'] = 2;//未支付尾款
                $orderdata['dingjin_time'] = time();//订金支付时间
                $orderdata['pay_status'] =1;//支付状态
                $orderdata['paid_money'] =$post_paid_money;//支付定金金额
                $orderdata['pay_name'] ='零钱支付';
                $orderdata['pay_code'] ='lingqian';
                // 需要支付的定金
                $yfdingjin=$post_paid_money;
                $paid_money=$order['goods_price']*0.2;//定金是店铺20%
                // 如果支付定金不及店铺金额20%  则业绩按照定金计算
                if ($paid_money>$yfdingjin) {
                    $orderdata['dj_yeji'] = $yfdingjin*5;
                }
                $zhifuzt=M('order')->where("order_id", $post_order_id)->save($orderdata);//支付信息修改
                //支付状态更新
                if (!$zhifuzt) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '订单状态修改失败', 'result' => $post_order_id]);
                }
                $goodsxiajia=M('goods')->where("goods_sn",$order['goods_sn'])->save(array('is_on_sale'=>'2'));//改为已售状态
                //店铺下架
                if (!$goodsxiajia) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '店铺下架失败', 'result' => $post_order_id]);
                }
                // 消费记录登记
                $data['user_id'] = $users['user_id'];
                $data['order_id'] = $post_order_id;
                $data['user_money'] = -$post_paid_money;
                $data['order_sn'] = $order['order_sn'];
                $data['change_time'] = time();
                $data['desc'] = '预付定金';
                $account_log = M('account_log')->add($data);
                if ($account_log) {
                   $this->ajaxReturn(['status' => 1, 'msg' => '定金支付成功', 'result' => $post_order_id]);
                }else{
                   $this->ajaxReturn(['status' => -1, 'msg' => '支付记录失败', 'result' => $post_order_id]);
                }
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '余额不足，定金支付失败', 'result' => '']);
        }
    }

    //支付尾款
    public function pay_weikuan(){

       //验证用户身份
        if (empty(I('post.username')) or empty(I('post.user_id'))) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        $usernames = I('post.username');
        $username=preg_replace('# #','',$usernames);
        $user_id = I('post.user_id');
        $user_id=preg_replace('# #','',$user_id);
        $passwords = I('post.password');
        $password=preg_replace('# #','',$passwords);

        $condition['paypwd'] = encrypt($password);
        $condition['nickname'] = $username;
        $condition['user_id'] = $user_id;
        $admin = Db::name('users')->where($condition)->find();
        if (!$admin) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号或密码不正确','result'=>'']);
        }
        //验证用户身份结束

        $post_order_id=I('post.order_id');
        if (empty($post_order_id)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        //查找需要支付的订单
        $order = M('order')->where("order_id", $post_order_id)->find();
        if (!$order) {
            $this->ajaxReturn(['status' => -1, 'msg' => '订单不存在', 'result' => '']);
        }

        // 查询余额够不够
        $userid = $order['user_id'];
        $users = Db::name('users')->where(['user_id' => $userid])->find();
        
        $weikuan=$order['order_amount']-$order['paid_money']-$order['erci_price'];

          //查看余额是否够付尾款
        if ($users['user_money'] > $weikuan || $users['user_money'] == $weikuan) {

                $zhifujine=M('users')->where(array('user_id'=>$users['user_id']))->setDec('user_money',$weikuan);//扣除余额支付的尾款
                if (!$zhifujine) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '扣款失败', 'result' => $userid]);
                }
                $orderdata['dingjin'] = 3;//尾款支付
                $orderdata['pay_status'] =1;//支付状态
                $orderdata['order_status'] =1;//订单状态
                $orderdata['pay_time'] = time(); //支付时间
                $orderdata['pay_name'] ='零钱支付';
                $orderdata['pay_code'] ='lingqian';
                $zhifuzt=M('order')->where("order_id", $post_order_id)->save($orderdata);//支付信息修改
                //支付状态更新
                if (!$zhifuzt) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '订单状态修改失败', 'result' => $post_order_id]);
                }

                $goodssave['is_on_sale']=2;
                $goodsxiajia=M('goods')->where("goods_sn",$order['goods_sn'])->save($goodssave);//改为已售状态
                //店铺下架
                // if (!$goodsxiajia) {
                //     $this->ajaxReturn(['status' => -1, 'msg' => '店铺下架失败', 'result' => $post_order_id]);
                // }
                // 消费记录登记
                $data['user_id'] = $users['user_id'];
                $data['order_id'] = $post_order_id;
                $data['user_money'] = -$weikuan;
                $data['order_sn'] = $order['order_sn'];
                $data['change_time'] = time();
                $data['desc'] = '支付尾款';
                $account_log = M('account_log')->add($data);
                if ($account_log) {
                   $this->ajaxReturn(['status' => 1, 'msg' => '尾款支付成功', 'result' => $post_order_id]);
                }else{
                   $this->ajaxReturn(['status' => -1, 'msg' => '支付记录失败', 'result' => $post_order_id]);
                }
        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '余额不足，尾款支付失败', 'result' => '']);
        }
    }

    //全款支付
    public function pay_all(){

        //验证用户身份
        if (empty(I('post.username')) or empty(I('post.user_id'))) {
            $this->ajaxReturn(['status' => -2, 'msg' => '参数缺失', 'result' => '']);
        }
        $usernames = I('post.username');
        $username=preg_replace('# #','',$usernames);
        $user_id = I('post.user_id');
        $user_id=preg_replace('# #','',$user_id);
        $passwords = I('post.password');
        $password=preg_replace('# #','',$passwords);

        $condition['paypwd'] = encrypt($password);
        $condition['nickname'] = $username;
        $condition['user_id'] = $user_id;
        $admin = Db::name('users')->where($condition)->find();
        if (!$admin) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号或密码不正确','result'=>'']);
        }
        //验证用户身份结束
        
        $post_order_id=I('post.order_id');
        if (empty($post_order_id)) {
            $this->ajaxReturn(['status' => -1, 'msg' => '参数缺失', 'result' => '']);
        }
        //查找需要支付的订单
        $order = M('order')->where("order_id", $post_order_id)->find();
        if (!$order) {
            $this->ajaxReturn(['status' => -1, 'msg' => '订单不存在', 'result' => '']);
        }

        // 查询余额够不够
        $userid = $order['user_id'];
        $users = Db::name('users')->where(['user_id' => $userid])->find();

          //查看余额是否够付全款
        if ($users['user_money'] > $order['order_amount'] || $users['user_money'] == $order['order_amount']) {

                $zhifujine=M('users')->where(array('user_id'=>$users['user_id']))->setDec('user_money',$order['order_amount']);//扣除余额支付的款
                if (!$zhifujine) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '扣款失败', 'result' => $userid]);
                }
                $orderdata['pay_status'] =1;//支付状态
                $orderdata['order_status'] =1;//订单状态
                $orderdata['pay_time'] = time(); //支付时间
                $orderdata['pay_name'] ='零钱支付';
                $orderdata['pay_code'] ='lingqian';
                $zhifuzt=M('order')->where("order_id", $post_order_id)->save($orderdata);//支付信息修改
                //支付状态更新
                if (!$zhifuzt) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '订单状态修改失败', 'result' => $post_order_id]);
                }
                $goodsxiajia=M('goods')->where("goods_sn",$order['goods_sn'])->save(array('is_on_sale'=>'2'));//改为已售状态
                //店铺下架
                if (!$goodsxiajia) {
                    $this->ajaxReturn(['status' => -1, 'msg' => '店铺下架失败', 'result' => $post_order_id]);
                }
                // 消费记录登记
                $data['user_id'] = $userid;
                $data['order_id'] = $post_order_id;
                $data['user_money'] = -$order['order_amount'];
                $data['order_sn'] = $order['order_sn'];
                $data['change_time'] = time();
                $data['desc'] = '全款支付';
                $account_log = M('account_log')->add($data);
                if ($account_log) {
                   $this->ajaxReturn(['status' => 1, 'msg' => '全款支付成功', 'result' => $post_order_id]);
                }else{
                   $this->ajaxReturn(['status' => -1, 'msg' => '支付记录失败', 'result' => $post_order_id]);
                }

        }else{
            $this->ajaxReturn(['status' => 0, 'msg' => '余额不足，全款支付失败', 'result' => '']);
        }
    }

    /**
     * 购物车第二步确定页面
     */
    public function cart2(){
        $goods_id = input("goods_id/d"); // 商品id
        $goods_num = input("goods_num/d");// 商品数量
        $item_id = input("item_id/d"); // 商品规格id
        $action = input("action/s"); // 行为
        if ($this->user_id == 0){
            $this->error('请先登录', U('Mobile/User/login'));
        }
        $cartLogic = new CartLogic();
        $couponLogic = new CouponLogic();
        $cartLogic->setUserId($this->user_id);
        //立即购买
        if($action == 'buy_now'){
            $cartLogic->setGoodsModel($goods_id);
            $cartLogic->setSpecGoodsPriceById($item_id);
            $cartLogic->setGoodsBuyNum($goods_num);
            $buyGoods = [];
            try{
                $buyGoods = $cartLogic->buyNow();
            }catch (TpshopException $t){
                $error = $t->getErrorArr();
                $this->error($error['msg']);
            }
            $cartList['cartList'][0] = $buyGoods;
            $cartGoodsTotalNum = $goods_num;
        }else{
            if ($cartLogic->getUserCartOrderCount() == 0){
                $this->error('你的购物车没有选中商品', 'Cart/index');
            }
            $cartList['cartList'] = $cartLogic->getCartList(1); // 获取用户选中的购物车商品
            $cartGoodsTotalNum = count($cartList['cartList']);
        }
        $cartGoodsList = get_arr_column($cartList['cartList'],'goods');
        $cartGoodsId = get_arr_column($cartGoodsList,'goods_id');
        $cartGoodsCatId = get_arr_column($cartGoodsList,'cat_id');
        $cartPriceInfo = $cartLogic->getCartPriceInfo($cartList['cartList']);  //初始化数据。商品总额/节约金额/商品总共数量
        $userCouponList = $couponLogic->getUserAbleCouponList($this->user_id, $cartGoodsId, $cartGoodsCatId);//用户可用的优惠券列表
        $cartList = array_merge($cartList,$cartPriceInfo);
        $userCartCouponList = $cartLogic->getCouponCartList($cartList, $userCouponList);
        $userCouponNum = $cartLogic->getUserCouponNumArr();
        $this->assign('userCartCouponList', $userCartCouponList);  //优惠券，用able判断是否可用
        $this->assign('userCouponNum', $userCouponNum);  //优惠券数量
        $this->assign('cartGoodsTotalNum', $cartGoodsTotalNum);
        $this->assign('cartList', $cartList['cartList']); // 购物车的商品
        $this->assign('cartPriceInfo', $cartPriceInfo);//商品优惠总价
        return $this->fetch();
    }


    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
        if($this->user_id == 0){
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态
        }
        $address_id = I("address_id/d"); //  收货地址id
        $invoice_title = I('invoice_title'); // 发票
        $taxpayer = I('taxpayer'); // 纳税人编号
        $coupon_id =  I("coupon_id/d"); //  优惠券id
        $pay_points =  I("pay_points/d",0); //  使用积分
        $user_money =  I("user_money/f",0); //  使用余额
        $user_note = trim(I('user_note'));   //买家留言
        $payPwd =  I("payPwd",''); // 支付密码
        $goods_id = input("goods_id/d"); // 商品id
        $goods_num = input("goods_num/d");// 商品数量
        $item_id = input("item_id/d"); // 商品规格id
        $action = input("action"); // 立即购买
        $shop_id = input('shop_id/d', 0);//自提点id
        $take_time = input('take_time/d');//自提时间
        $consignee = input('consignee/s');//自提点收货人
        $mobile = input('mobile/s');//自提点联系方式
        $data = input('request.');
        $cart_validate = Loader::validate('Cart');
        if (!$cart_validate->check($data)) {
            $error = $cart_validate->getError();
            $this->ajaxReturn(['status' => 0, 'msg' => $error[0], 'result' => '']);
        }
        $address = Db::name('user_address')->where("address_id", $address_id)->find();
        $cartLogic = new CartLogic();
        $pay = new Pay();
        try {
            $cartLogic->setUserId($this->user_id);
            $pay->setUserId($this->user_id);
            $pay->setShopById($shop_id);
            if ($action == 'buy_now') {
                $cartLogic->setGoodsModel($goods_id);
                $cartLogic->setSpecGoodsPriceById($item_id);
                $cartLogic->setGoodsBuyNum($goods_num);
                $buyGoods = $cartLogic->buyNow();
                $cartList[0] = $buyGoods;
                $pay->payGoodsList($cartList);
            } else {
                $userCartList = $cartLogic->getCartList(1);
                $cartLogic->checkStockCartList($userCartList);
                $pay->payCart($userCartList);
            }
            $pay->delivery($address['district']);
            $pay->orderPromotion();
            $pay->useCouponById($coupon_id);
            $pay->useUserMoney($user_money);
            $pay->usePayPoints($pay_points);
            // 提交订单
            if ($_REQUEST['act'] == 'submit_order') {
                $placeOrder = new PlaceOrder($pay);
                $placeOrder->setUserAddress($address);
                $placeOrder->setConsignee($consignee);
                $placeOrder->setMobile($mobile);
                $placeOrder->setInvoiceTitle($invoice_title);
                $placeOrder->setUserNote($user_note);
                $placeOrder->setTaxpayer($taxpayer);
                $placeOrder->setPayPsw($payPwd);
                $placeOrder->setTakeTime($take_time);
                $placeOrder->addNormalOrder();
                $cartLogic->clear();
                $order = $placeOrder->getOrder();
                $this->ajaxReturn(['status' => 1, 'msg' => '提交订单成功', 'result' => $order['order_sn']]);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '计算成功', 'result' => $pay->toArray()]);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }
    /*
     * 订单支付页面
     */
    public function cart4(){
        if(empty($this->user_id)){
            $this->redirect('User/login');
        }
        $order_id = I('order_id/d');
        $order_sn= I('order_sn/s','');
        $order_where = ['user_id'=>$this->user_id];
        if($order_sn)
        {
            $order_where['order_sn'] = $order_sn;
        }else{
            $order_where['order_id'] = $order_id;
        }
        $order = M('Order')->where($order_where)->find();
        empty($order) && $this->error('订单不存在！');
        if($order['order_status'] == 3){
            $this->error('该订单已取消',U("Mobile/Order/order_detail",array('id'=>$order['order_id'])));
        }
        if(empty($order) || empty($this->user_id)){
            $order_order_list = U("User/login");
            header("Location: $order_order_list");
            exit;
        }
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/Order/order_detail",array('id'=>$order['order_id']));
            header("Location: $order_detail_url");
            exit;
        }
        $orderGoodsPromType = M('order_goods')->where(['order_id'=>$order['order_id']])->getField('prom_type',true);
        //如果是预售订单，支付尾款
        if($order['pay_status'] == 2 && $order['prom_type'] == 4){
            $pre_sell_info = M('goods_activity')->where(array('act_id'=>$order['prom_id']))->find();
            $pre_sell_info = array_merge($pre_sell_info,unserialize($pre_sell_info['ext_info']));
            if($pre_sell_info['retainage_start'] > time()){
                $this->error('还未到支付尾款时间'.date('Y-m-d H:i:s',$pre_sell_info['retainage_start']));
            }
            if($pre_sell_info['retainage_end'] < time()){
                $this->error('对不起，该预售商品已过尾款支付时间'.date('Y-m-d H:i:s',$pre_sell_info['retainage_start']));
            }
        }
        $payment_where['type'] = 'payment';
        $no_cod_order_prom_type = ['4,5'];//预售订单，虚拟订单不支持货到付款
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            //微信浏览器
            if(in_array($order['prom_type'],$no_cod_order_prom_type) || in_array(1,$orderGoodsPromType) || $order['shop_id'] > 0){
                //预售订单和抢购不支持货到付款
                $payment_where['code'] = 'weixin';
            }else{
                $payment_where['code'] = array('in',array('weixin','cod'));
            }
        }else{
            if(in_array($order['prom_type'],$no_cod_order_prom_type) || in_array(1,$orderGoodsPromType) || $order['shop_id'] > 0){
                //预售订单和抢购不支持货到付款
                $payment_where['code'] = array('neq','cod');
            }
            $payment_where['scene'] = array('in',array('0','1'));
        }
        $payment_where['status'] = 1;
        //预售和抢购暂不支持货到付款
        $orderGoodsPromType = M('order_goods')->where(['order_id'=>$order['order_id']])->getField('prom_type',true);
        if($order['prom_type'] == 4 || in_array(1,$orderGoodsPromType)){
            $payment_where['code'] = array('neq','cod');
        }
        $paymentList = M('Plugin')->where($payment_where)->select();
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
            //判断当前浏览器显示支付方式
            if(($key == 'weixin' && !is_weixin()) || ($key == 'alipayMobile' && is_weixin())){
                unset($paymentList[$key]);
            }
            if($key == 'weixin' && is_weixin()){
                $paymentList[$key]['icon'] = 'app_'.$paymentList[$key]['icon'];
            }
        }

        $bank_img = include APP_PATH.'home/bank.php'; // 银行对应图片
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        return $this->fetch();
    }

    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id/d"); // 商品id
        $goods_num = I("goods_num/d");// 商品数量
        $item_id = I("item_id/d"); // 商品规格id
        if(empty($goods_id)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'请选择要购买的商品','result'=>'']);
        }
        if(empty($goods_num)){
            $this->ajaxReturn(['status'=>-1,'msg'=>'购买商品数量不能为0','result'=>'']);
        }
        $cartLogic = new CartLogic();
        $cartLogic->setUserId($this->user_id);
        $cartLogic->setGoodsModel($goods_id);
        $cartLogic->setSpecGoodsPriceById($item_id);
        $cartLogic->setGoodsBuyNum($goods_num);
        try {
            $cartLogic->addGoodsToCart();
            $this->ajaxReturn(['status' => 1, 'msg' => '加入购物车成功']);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    /**
     * ajax 将搭配购商品加入购物车
     */
    public function addCombination()
    {
        $combination_id = input('combination_id/d');//搭配购id
        $num = input('num/d');//套餐数量
        $combination_goods = input('combination_goods/a');//套餐里的商品
        if (empty($combination_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误']);
        }
        $cartLogic = new CartLogic();
        $combination = Combination::get(['combination_id' => $combination_id]);
        $cartLogic->setUserId($this->user_id);
        $cartLogic->setCombination($combination);
        $cartLogic->setGoodsBuyNum($num);
        try {
            $cartLogic->addCombinationToCart($combination_goods);
            $this->ajaxReturn(['status' => 1, 'msg' => '成功加入购物车']);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }

    /**
     * ajax 获取用户收货地址 用于购物车确认订单页面
     */
    public function ajaxAddress(){
        $regionList = get_region_list();
        $address_list = M('UserAddress')->where("user_id", $this->user_id)->select();
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('regionList', $regionList);
        $this->assign('address_list', $address_list);
        return $this->fetch('ajax_address');
    }

    /**
     * 预售商品下单流程
     */
    public function pre_sell_cart()
    {
        $act_id = I('act_id/d');
        $goods_num = I('goods_num/d');
        $address_id = I('address_id/d');
        if(empty($act_id)){
            $this->error('没有选择需要购买商品');
        }
        if(empty($goods_num)){
            $this->error('购买商品数量不能为0', U('Home/Activity/pre_sell', array('act_id' => $act_id)));
        }
        if($address_id){
            $address = M('user_address')->where("address_id", $address_id)->find();
        } else {
            $address = Db::name('user_address')->where(['user_id'=>$this->user_id])->order(['is_default'=>'desc'])->find();
        }
        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'pre_sell_cart','act_id'=>$act_id,'goods_num'=>$goods_num)));
            exit;
        }else{
            $this->assign('address',$address);
        }
        if($this->user_id == 0){
            $this->error('请先登录');
        }
        $pre_sell_info = M('goods_activity')->where(array('act_id' => $act_id, 'act_type' => 1))->find();
        if(empty($pre_sell_info)){
            $this->error('商品不存在或已下架',U('Home/Activity/pre_sell_list'));
        }
        $pre_sell_info = array_merge($pre_sell_info, unserialize($pre_sell_info['ext_info']));
        if ($pre_sell_info['act_count'] + $goods_num > $pre_sell_info['restrict_amount']) {
            $buy_num = $pre_sell_info['restrict_amount'] - $pre_sell_info['act_count'];
            $this->error('预售商品库存不足，还剩下' . $buy_num . '件', U('Home/Activity/pre_sell', array('id' => $act_id)));
        }
        $goodsActivityLogic = new GoodsActivityLogic();
        $pre_count_info = $goodsActivityLogic->getPreCountInfo($pre_sell_info['act_id'], $pre_sell_info['goods_id']);//预售商品的订购数量和订单数量
        $pre_sell_price['cut_price'] =$goodsActivityLogic->getPrePrice($pre_count_info['total_goods'], $pre_sell_info['price_ladder']);//预售商品价格
        $pre_sell_price['goods_num'] = $goods_num;
        $pre_sell_price['deposit_price'] = floatval($pre_sell_info['deposit']);
        // 提交订单
        if ($_REQUEST['act'] == 'submit_order') {
            $invoice_title = I('invoice_title'); // 发票
            $taxpayer  = I('taxpayer'); // 纳税识别号
            $address_id = I("address_id/d"); //  收货地址id
            if(empty($address_id)){
                exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
            }
            $orderLogic = new OrderLogic();
            $result = $orderLogic->addPreSellOrder($this->user_id, $address_id, $invoice_title, $act_id, $pre_sell_price,$taxpayer); // 添加订单
            exit(json_encode($result));
        }
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司
        $this->assign('pre_sell_info', $pre_sell_info);// 购物车的预售商品
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('pre_sell_price',$pre_sell_price);
        return $this->fetch();
    }

    /**
     * 兑换积分商品
     */
    public function buyIntegralGoods(){
        $goods_id = input('goods_id/d');
        $item_id = input('item_id/d');
        $goods_num = input('goods_num');
        $Integral = new Integral();
        $Integral->setUserById($this->user_id);
        $Integral->setGoodsById($goods_id);
        $Integral->setSpecGoodsPriceById($item_id);
        $Integral->setBuyNum($goods_num);
        try{
            $Integral->checkBuy();
            $url = U('Cart/integral', ['goods_id' => $goods_id, 'item_id' => $item_id, 'goods_num' => $goods_num]);
            $result = ['status' => 1, 'msg' => '购买成功', 'result' => ['url' => $url]];
            $this->ajaxReturn($result);
        }catch (TpshopException $t){
            $result = $t->getErrorArr();
            $this->ajaxReturn($result);
        }
    }

    /**
     *  积分商品结算页
     * @return mixed
     */
    public function integral(){
        $goods_id = input('goods_id/d');
        $item_id = input('item_id/d');
        $goods_num = input('goods_num/d');
        if(empty($this->user)){
            $this->error('请登录');
        }
        if(empty($goods_id)){
            $this->error('非法操作');
        }
        if(empty($goods_num)){
            $this->error('购买数不能为零');
        }
        $Goods = new Goods();
        $goods = $Goods->where(['goods_id'=>$goods_id])->find();
        if(empty($goods)){
            $this->error('该商品不存在');
        }
        if (empty($item_id)) {
            $goods_spec_list = SpecGoodsPrice::all(['goods_id' => $goods_id]);
            if (count($goods_spec_list) > 0) {
                $this->error('请传递规格参数');
            }
            $goods_price = $goods['shop_price'];
            //没有规格
        } else {
            //有规格
            $specGoodsPrice = SpecGoodsPrice::get(['item_id'=>$item_id,'goods_id'=>$goods_id]);
            if ($goods_num > $specGoodsPrice['store_count']) {
                $this->error('该商品规格库存不足，剩余' . $specGoodsPrice['store_count'] . '份');
            }
            $goods_price = $specGoodsPrice['price'];
            $this->assign('specGoodsPrice', $specGoodsPrice);
        }
        $point_rate = tpCache('shopping.point_rate');
        $backUrl = Url::build('Goods/goodsInfo',['id'=>$goods_id,'item_id'=>$item_id]);
        $this->assign('backUrl', $backUrl);
        $this->assign('point_rate', $point_rate);
        $this->assign('goods', $goods);
        $this->assign('goods_price', $goods_price);
        $this->assign('goods_num',$goods_num);
        return $this->fetch();
    }

    /**
     *  积分商品价格提交
     * @return mixed
     */
    public function integral2()
    {
        if ($this->user_id == 0) {
            $this->ajaxReturn(['status' => -100, 'msg' => "登录超时请重新登录!", 'result' => null]);
        }
        $goods_id = input('goods_id/d');
        $item_id = input('item_id/d');
        $goods_num = input('goods_num/d');
        $address_id = input("address_id/d"); //  收货地址id
        $user_note = input('user_note'); // 给卖家留言
        $invoice_title = input('invoice_title'); // 发票
        $taxpayer = input('taxpayer'); // 发票纳税人识别号
        $user_money = input("user_money/f", 0); //  使用余额
        $payPwd = input('payPwd');
        $shop_id = input('shop_id/d', 0);//自提点id
        $take_time = input('take_time/d');//自提时间
        $consignee = input('consignee/s');//自提点收货人
        $mobile = input('mobile/s');//自提点联系方式
        $integral = new Integral();
        $integral->setUserById($this->user_id);
        $integral->setShopById($shop_id);
        $integral->setGoodsById($goods_id);
        $integral->setBuyNum($goods_num);
        $integral->setSpecGoodsPriceById($item_id);
        $integral->setUserAddressById($address_id);
        $integral->useUserMoney($user_money);
        try {
            $integral->checkBuy();
            $pay = $integral->pay();
            // 提交订单
            if ($_REQUEST['act'] == 'submit_order') {
                $placeOrder = new PlaceOrder($pay);
                $placeOrder->setUserAddress($integral->getUserAddress());
                $placeOrder->setConsignee($consignee);
                $placeOrder->setMobile($mobile);
                $placeOrder->setInvoiceTitle($invoice_title);
                $placeOrder->setUserNote($user_note);
                $placeOrder->setTaxpayer($taxpayer);
                $placeOrder->setPayPsw($payPwd);
                $placeOrder->setTakeTime($take_time);
                $placeOrder->addNormalOrder();
                $order = $placeOrder->getOrder();
                $this->ajaxReturn(['status' => 1, 'msg' => '提交订单成功', 'result' => $order['order_id']]);
            }
            $this->ajaxReturn(['status' => 1, 'msg' => '计算成功', 'result' => $pay->toArray()]);
        } catch (TpshopException $t) {
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }
	
	 /**
     *  获取发票信息
     * @date2017/10/19 14:45
     */
    public function invoice(){

        $map = [];
        $map['user_id']=  $this->user_id;
        
        $field=[          
            'invoice_title',
            'taxpayer',
            'invoice_desc',	
        ];
        
        $info = M('user_extend')->field($field)->where($map)->find();
        if(empty($info)){
            $result=['status' => -1, 'msg' => 'N', 'result' =>''];
        }else{
            $result=['status' => 1, 'msg' => 'Y', 'result' => $info];
        }
        $this->ajaxReturn($result);            
    }
     /**
     *  保存发票信息
     * @date2017/10/19 14:45
     */
    public function save_invoice(){     
        
        if(IS_AJAX){
            
            //A.1获取发票信息
            $invoice_title = trim(I("invoice_title"));
            $taxpayer      = trim(I("taxpayer"));
            $invoice_desc  = trim(I("invoice_desc"));
            
            //B.1校验用户是否有历史发票记录
            $map            = [];
            $map['user_id'] =  $this->user_id;
            $info           = M('user_extend')->where($map)->find();
            
           //B.2发票信息
            $data=[];  
            $data['invoice_title'] = $invoice_title;
            $data['taxpayer']      = $taxpayer;  
            $data['invoice_desc']  = $invoice_desc;     
            
            //B.3发票抬头
            if($invoice_title=="个人"){
                $data['invoice_title'] ="个人";
                $data['taxpayer']      = "";
            }                              
            
            
            //是否存贮过发票信息
            if(empty($info)){   
                $data['user_id'] = $this->user_id;
                (M('user_extend')->add($data))?
                $status=1:$status=-1;                
             }else{
                (M('user_extend')->where($map)->save($data))?
                $status=1:$status=-1;                
            }            
            $result = ['status' => $status, 'msg' => '', 'result' =>''];           
            $this->ajaxReturn($result); 
            
        }      
    }
}
