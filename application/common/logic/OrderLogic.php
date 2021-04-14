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
 * Date: 2016-03-19
 */

namespace app\common\logic;

use app\common\logic\wechat\WechatUtil;
use app\common\model\Order as OrderModel;
use app\common\model\SpecGoodsPrice;
use think\Db;
/**
 * Class orderLogic
 * @package Common\Logic
 */
class OrderLogic
{
    protected $user_id=0;
    public function setUserId($user_id){
        $this->user_id=$user_id;
    }
	/**
	 * 取消订单
	 * @param $user_id|用户ID
	 * @param $order_id|订单ID
	 * @param string $action_note 操作备注
	 * @return array
	 */
	public function cancel_order($user_id,$order_id,$action_note='您取消了订单'){
		$order = M('order_list')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->find();
		//检查是否未支付订单 已支付联系客服处理退款
		if(empty($order))
			return array('status'=>-1,'msg'=>'订单不存在','result'=>'');
		if($order['order_status'] == 7){
			return array('status'=>-1,'msg'=>'该订单已取消','result'=>'');
		}
		//检查是否未支付的订单
		if($order['order_status'] > 0 || $order['order_status'] < 7){
			return array('status'=>-1,'msg'=>'支付状态或订单状态不允许','result'=>'');
		}
		
		if($order['coupon_price'] >0){
			$res = array('use_time'=>0,'status'=>0,'order_id'=>0);
			M('coupon_list')->where(array('order_id'=>$order_id,'uid'=>$user_id))->save($res);
		}

		$row = M('order_list')->where(array('order_id'=>$order_id,'user_id'=>$user_id))->save(array('order_status'=>7));
		
        $this->orderActionLog($order_id,$action_note,'取消订单');
		if(!$row)
			return array('status'=>-1,'msg'=>'操作失败','result'=>'');
		return array('status'=>1,'msg'=>'操作成功','result'=>'');

	}

	public function addReturnGoods($order,$tuikuanjine)
	{
		// $confirm_time_config = tpCache('shopping.auto_service_date');//后台设置多少天内可申请售后
		// $confirm_time = $confirm_time_config * 24 * 60 * 60;
		// if ((time() - $order['confirm_time']) > $confirm_time && !empty($order['confirm_time'])) {
		// 	return ['status'=>-1,'msg'=>'已经超过' . ($confirm_time_config ?: 0) . "天内退货时间"];
		// }
		$data['addtime'] = time();
		$data['user_id'] = $order['user_id'];
		$data['cat_id'] = $order['cat_id'];
		
		if($order['order_status'] > 0 && $order['order_status'] < 4){
            $where['order_id'] = $order['order_id'];
         	$fk_price = M('fangkuan')->where($where)->sum('price');//订单放款金额
            if($order['order_status']==1){
                $returnmoney = $order['paid_money']+$order['erci_price']-$fk_price;   //只支付了定金或二次付款
            }else{
                $returnmoney = $order['total_amount']-$fk_price;   //订单总额
            }

            if ($tuikuanjine) {
            	$data['refund_deposit'] = round($tuikuanjine,2);//自定义退款金额
            }else{
            	$data['refund_deposit'] = round($returnmoney,2);//该退余额支付部分 退款金额
            }
		}else{
			return ['status'=>-1,'msg'=>'订单状态不支持退款'];
		}

		if(!empty($data['id'])){
			$result = M('return_goods')->where(array('id'=>$data['id']))->save($data);
		}else{
			$result = M('return_goods')->add($data);
		}
	
		if($result){
			return ['status'=>1,'msg'=>'申请成功'];
		}
		return ['status'=>-1,'msg'=>'申请失败'];
	}
    
    /**
     * 上传退换货图片，兼容小程序
     * @return array
     */
    public function uploadReturnGoodsImg()
    {
        $return_imgs = '';
        if ($_FILES['return_imgs']['tmp_name']) {
			$files = request()->file("return_imgs");
            if (is_object($files)) {
                $files = [$files]; //可能是一张图片，小程序情况
            }
			$image_upload_limit_size = config('image_upload_limit_size');
			$validate = ['size'=>$image_upload_limit_size,'ext'=>'jpg,png,gif,jpeg'];
			$dir = UPLOAD_PATH.'return_goods/';
			if (!($_exists = file_exists($dir))){
				$isMk = mkdir($dir);
			}
			$parentDir = date('Ymd');
			foreach($files as $key => $file){
				$info = $file->rule($parentDir)->validate($validate)->move($dir, true);
				if($info){
					$filename = $info->getFilename();
					$new_name = '/'.$dir.$parentDir.'/'.$filename;
					$return_imgs[]= $new_name;
				}else{
                    return ['status' => -1, 'msg' => $file->getError()];//上传错误提示错误信息
				}
			}
			if (!empty($return_imgs)) {
				$return_imgs = implode(',', $return_imgs);// 上传的图片文件
			}
		}
        
        return ['status' => 1, 'msg' => '操作成功', 'result' => $return_imgs];
    }
	
    /**
     * 获取可申请退换货订单商品
     * @param $sale_t
     * @param $keywords
     * @param $user_id
     * @return array
     */
    public function getReturnGoodsIndex($sale_t, $keywords, $user_id)
    {
        if($keywords){
            $condition['order_sn'] = $keywords;
        }
		// $confirm_time_config = tpCache('shopping.auto_service_date');//后台设置多少天内可申请售后
		// $confirm_time = strtotime("-$confirm_time_config day");
		$condition['add_time'] = ['gt',$confirm_time];
    	$condition['user_id'] = $user_id;
    	// $condition['pay_status'] = 1;
    	// $condition['shipping_status'] = 1;
    	$condition['deleted'] = 0;
    	$count = M('order')->where($condition)->count();
    	$Page  = new \think\Page($count,10);
    	$show = $Page->show();
    	$order_list = M('order')->where($condition)->order('order_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
    	
    	foreach ($order_list as $k=>$v) {
            $order_list[$k] = set_btn_order_status($v);  // 添加属性  包括按钮显示属性 和 订单状态显示属性
            $data = M('order_goods')->where(['order_id'=>$v['order_id'],'is_send'=>['lt',2]])->select();
            if(!empty($data)){
                $order_list[$k]['goods_list'] = $data;
            }else{
                unset($order_list[$k]);  //除去没有可申请的订单
            }

    	}

        return [
            'order_list' => $order_list,
            'page' => $show
        ];
    }

    /**
     * 获取退货列表
     * @param $keywords
     * @param $addtime
     * @param $status
     * @param int $user_id
     * @return array
     */
    public function getReturnGoodsList($keywords, $addtime, $status, $user_id = 0)
	{
		if($keywords){
            $where['order_sn|goods_name'] = array('like',"%$keywords%");
    	}
    	if($status === '0' || !empty($status)){
            $where['status'] = $status;
    	}
    	if($addtime == 1){
            $where['addtime'] = array('gt',(time()-90*24*3600));
    	}
    	if($addtime == 2){
            $where['addtime'] = array('lt',(time()-90*24*3600));
    	}
    	$query = M('return_goods')->alias('r')->field('r.*,g.goods_name')
                ->join('__GOODS__ g', 'r.goods_id = g.goods_id', 'LEFT')
                ->where($where);
        $query2 = clone $query;
        $count = $query->count();
    	$page = new \think\Page($count,10);
    	$list = $query2->order("id desc")->limit($page->firstRow, $page->listRows)->select();
    	$goods_id_arr = get_arr_column($list, 'goods_id');
    	if(!empty($goods_id_arr)) {
            $goodsList = M('goods')->where("goods_id in (".  implode(',',$goods_id_arr).")")->getField('goods_id,goods_name');
        }
        
        return [
            'goodsList' => $goodsList,
            'return_list' => $list,
            'page' => $page->show()
        ];
	}
    
    /**
     * 记录取消订单
     * @param $user_id
     * @param $order_id
     * @param $user_note
     * @param $consignee
     * @param $mobile
     * @return array
     */
    public function recordRefundOrder($user_id, $order_id, $user_note, $consignee, $mobile)
    {
    	$order = Db::name('order')->where(['order_id' => $order_id, 'user_id' => $user_id])->find();
    	if (!$order) {
    		return ['status' => -1, 'msg' => '订单不存在'];
    	}
    	$order_return_num = Db::name('return_goods')->where(['order_id' => $order_id, 'user_id' => $user_id,'status'=>['neq',5]])->count();
    	if($order_return_num > 0){
    		return ['status' => -1, 'msg' => '该订单中有商品正在申请售后'];
    	}
    	$order_status = 3;//已取消
    	$order_info = ['order_status'=> $order_status];//订单状态
		if($mobile){
			$order_info['mobile'] = $mobile;
		}
		if($consignee){
			$order_info['consignee'] = $consignee;//收货人
		}
		if($user_note){
			$order_info['user_note'] = $user_note;//用户备注
			$data['action_note'] = $user_note;
		}
    
    	$result = Db::name('order')->where(['order_id' => $order_id])->update($order_info);
    	if (!$result) {
    		return ['status' => 0, 'msg' => '操作失败'];
    	}
        if($order['prom_type'] == 5){
            //活动订单可能要操作其他东西,目前只是虚拟订单才需要，以后根据业务做修改
            Db::name('vr_order_code')->where(['order_id' => $order_id])->update(['refund_lock'=>1]);
        }
        Db::name('rebate_log')->where(['order_id'=>$order_id])->save(array('status'=>4,'confirm_time'=>time()));
        $order = new \app\common\logic\Order();
		$order->setOrderById($order_id);
        $order->orderActionLog('','用户取消已付款订单');
    	return ['status' => 1, 'msg' => '提交成功'];
    }

  	
  	//权限查询
  	public function act_list_select(){
  		$roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
  		
        if ($roleid==1) {
            $detail="1,2,3,4,5,6,7,8,9,10,11,12,13,14,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,234,235,236,285,286,287";//系统 下架
        }else{
        	$detail = M('admin_role')->where(['role_id'=>$roleid])->getField('act_list');
        }
        $act_list = explode(',', $detail);
        return $act_list;
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
	 * 自动取消订单
	 */
	public function abolishOrder(){
		$set_time=1; //自动取消时间/天 默认1天
		$abolishtime = strtotime("-$set_time day");
		$order_where = [
				'user_id'      =>$this->user_id,
				'add_time'     =>['lt',$abolishtime],
				'order_status' => 0
		];
		$order = Db::name('order_list')->where($order_where)->getField('order_id',true);
		foreach($order as $key =>$value){
			$result = $this->cancel_order($this->user_id,$value);
		}
		return $result;
	}

	/**
	 * 添加预售商品订单
	 * @param $user_id
	 * @param $address_id
	 * @param $invoice_title
	 * @param $act_id
	 * @param $pre_sell_price
	 * @param $taxpayer
	 * @return array
	 */
	public function addPreSellOrder($user_id,$address_id,$invoice_title,$act_id,$pre_sell_price, $taxpayer="")
	{
		// 仿制灌水 1天只能下 50 单
		$order_count = M('Order')->where("user_id= $user_id and order_sn like '".date('Ymd')."%'")->count(); // 查找购物车商品总数量
		if($order_count >= 50){
			return array('status'=>-9,'msg'=>'一天只能下50个订单','result'=>'');
		}
		$address = M('UserAddress')->where(array('address_id' => $address_id))->find();
		$data = array(
				'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
				'user_id'          =>$user_id, // 用户id
				'consignee'        =>$address['consignee'], // 收货人
				'province'         =>$address['province'],//'省份id',
				'city'             =>$address['city'],//'城市id',
				'district'         =>$address['district'],//'县',
				'twon'             =>$address['twon'],// '街道',
				'address'          =>$address['address'],//'详细地址',
				'mobile'           =>$address['mobile'],//'手机',
				'zipcode'          =>$address['zipcode'],//'邮编',
				'email'            =>$address['email'],//'邮箱',
				'invoice_title'    =>$invoice_title, //'发票抬头',
				'taxpayer'    	   =>$taxpayer, //'纳税人识别号',
				'goods_price'      =>$pre_sell_price['cut_price'] * $pre_sell_price['goods_num'],//'商品价格',
				'total_amount'     =>$pre_sell_price['cut_price'] * $pre_sell_price['goods_num'],// 订单总额
				'add_time'         =>time(), // 下单时间
				'prom_type'        => 4,
				'prom_id'          => $act_id
		);
		if($pre_sell_price['deposit_price'] == 0){
			//无定金
			$data['order_amount'] = $pre_sell_price['cut_price'] * $pre_sell_price['goods_num'];//'应付款金额',
		}else{
			//有定金
			$data['order_amount'] = $pre_sell_price['deposit_price'] * $pre_sell_price['goods_num'];//'应付款金额',
		}
		$order_id = Db::name('order')->insertGetId($data);
//        M('goods_activity')->where(array('act_id'=>$act_id))->setInc('act_count',$pre_sell_price['goods_num']);
		if($order_id === false){
			return array('status'=>-8,'msg'=>'添加订单失败','result'=>NULL);
		}
        $order = new \app\common\logic\Order();
		$order->setOrderById($order_id);
        $order->orderActionLog('您提交了订单，请等待系统确认','提交订单',$user_id);
		$order = M('Order')->where("order_id = $order_id")->find();
		$goods_activity = M('goods_activity')->where(array('act_id'=>$act_id))->find();
		$goods = M('goods')->where(array('goods_id'=>$goods_activity['goods_id']))->find();
		$data2['order_id']           = $order_id; // 订单id
		$data2['goods_id']           = $goods['goods_id']; // 商品id
		$data2['goods_name']         = $goods['goods_name']; // 商品名称
		$data2['goods_sn']           = $goods['goods_sn']; // 商品货号
		$data2['goods_num']          = $pre_sell_price['goods_num']; // 购买数量
		$data2['final_price']        = $pre_sell_price['cut_price']; // 市场价
		$data2['goods_price']        = $goods['shop_price']; // 商品团价
		$data2['cost_price']         = $goods['cost_price']; // 成本价
		$data2['member_goods_price'] = $pre_sell_price['cut_price']; //预售价钱
		$data2['give_integral']      = $goods_activity['integral']; // 购买商品赠送积分
		$data2['prom_type']          = 4; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠 ,4 预售商品
		$data2['prom_id']    		 = $goods_activity['act_id'];
		Db::name('order_goods')->insert($data2);
		// 如果有微信公众号 则推送一条消息到微信
		$user = M('OauthUsers')->where(['user_id'=>$user_id , 'oauth'=>'weixin' , 'oauth_child'=>'mp'])->find();
		if ($user['oauth']== 'weixin') {
			$wx_content = "你刚刚下了一笔预售订单:{$order['order_sn']} 尽快支付,过期失效!";
            $wechat = new WechatUtil;
            $wechat->sendMsg($user['openid'], 'text', $wx_content);
		}
		return array('status'=>1,'msg'=>'提交订单成功','result'=>$order['order_sn']); // 返回新增的订单id
	}
	

	/**
	 * 获取订单 order_sn
	 * @return string
	 */
	public function get_order_sn()
	{
	    $order_sn = null;
	    // 保证不会有重复订单号存在
	    while(true){
	        $order_sn = date('YmdHis').rand(1000,9999); // 订单编号	        
	        $order_sn_count = M('order')->where("order_sn = ".$order_sn)->count();
	        $order_list_sn_count = M('order_list')->where("order_sn = ".$order_sn)->count();
	        if($order_sn_count == 0 && $order_list_sn_count == 0)
	            break;
	    }
	    
	    return $order_sn;
	}
	
	/**
     * 提交添加订单
     */
	public function add_order($user_id,$goods_id,$action_user){

		if (empty($user_id) or empty($goods_id)) {
            return ['status' => -1, 'msg' => '参数缺失', 'result' => ''];
        }
        $user = Db::name('users')->where(['user_id'=>$user_id])->find();
        if (!$user) {
            return ['status' => -1, 'msg' => '找不到客户信息', 'result' => ''];
        }

        // 当前产品下单时间
        $addwhere['user_id']=$user_id;//买家id
        $addwhere['goods_id']=$goods_id;//店铺id
        $addtime=M('order_list')->where($addwhere)->getField('add_time');

        if ($addtime) {
            $shicha=time()-$addtime;
            if ($addtime&&$shicha<60) {
            	return ['status' => -1, 'msg' => '一分钟内请勿重复下单', 'result' => ''];
            }
        }

        $goods = M('Goods')->where("goods_id", $goods_id)->find();
       
        if (!$goods) {
            return ['status' => -1, 'msg' => '找不到商品信息', 'result' => ''];
        }

        $add['order_sn'] = $this->get_order_sn(); // 订单编号
        $add['user_id'] = $user_id; // 买家id
        $add['goods_id'] = $goods_id; // 商品id
        $add['goods_sn'] = $goods['goods_sn']; // 商品编号
        $add['cat_id'] = $goods['cat_id']; // 所属行业类目
        $add['pingtai_id'] = $goods['pingtai_id']; // 所属平台
        $add['goods_type'] = $goods['goods_type']; // 类别编号 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
        $add['sellername'] = $goods['sellername']; // 卖家用户名

        if ($goods['admin_id']) {
            $add['gs_adminid'] = $goods['admin_id']; // 挂售人
        }else{
            $add['gs_adminid'] = 0; // 挂售人
        }
        if ($goods['corps_id']) {
            $add['gs_corps_id'] = $goods['corps_id']; // 所属战队
        }else{
            $add['gs_corps_id'] = 0; // 所属战队
        }
       
        $add['order_status'] = 0; // 订单状态
        $add['admin_note'] = '提交订单'; // 管理员备注
        $add['add_time'] = time(); // 下单时间
        $add['goods_price'] = $goods['shop_price']; // 商品金额
        $add['total_amount'] = $goods['shop_price']*1.1; // 订单总额
        $add['paid_money'] = $goods['shop_price']*0.2; // 定金
        $add['shiji_yeji'] = 0; // 实际业绩
        $order_id = M('order_list')->add($add);

        $this->order_action_log($order_id,"提交订单","提交订单",$action_user);//操作记录

        if ($order_id) {
            return ['status' => 1, 'msg' => '下单成功', 'result' => $order_id];
        }
	}

	 /**
     * 订单操作记录  新系统
     * @param $order_id
     * @param $action_note 备注
     * @param $status_desc 状态描述
     * @param $action_user
     * @return mixed
     */
    public function order_action_log($order_id,$action_note,$status_desc,$action_user=0){
        $order = Db::name('order_list')->where(['order_id'=>$order_id])->find();
        $data=[
            'order_id'      => $order_id,
            'action_user'   => $action_user,
            'action_note'   => $action_note,
            'order_status'  => $order['order_status'],
            'log_time'      => time(),
            'status_desc'   => $status_desc,
        ];
        return M('order_action')->add($data);//订单操作记录
    }



    //查询支付余额是否充足
    public function user_money_is($user_id,$pay_money){
    	$users = Db::name('users')->where(['user_id' => $user_id])->find();
    	if (!$users) {
    		return ['status' => -1, 'msg' => '会员账户错误', 'result' => ''];
    	}
    	if ($users['user_money'] < $pay_money) {
    		return ['status' => -1, 'msg' => '余额不足', 'result' => ''];
    	}
    }

    //消费记录 支付记录
    public function pay_log($user_id,$pay_money,$order_id,$order_sn,$desc){
    			// 消费记录登记
                $data['user_id'] = $user_id;
                $data['order_id'] = $order_id;
                $data['user_money'] = -$pay_money;
                $data['order_sn'] = $order_sn;
                $data['change_time'] = time();
                $data['desc'] = $desc;
                return M('account_log')->add($data);
    }

    //下架已售店铺
    public function no_on_sale($goods_id,$goods_type){
    			//店铺下架
                $goodsxiajia=M('Goods')->where("goods_id",$goods_id)->save(array('is_on_sale'=>'2'));//改为已售状态
                if (!$goodsxiajia) {
                    return ['status' => -1, 'msg' => '下架失败', 'result' => ''];
                }
    }
    
     //支付金额 修改订单状态 更新订单业绩
    public function pay_money($order_id,$order_status){

		        if (empty($order_id) or empty($order_status)) {
		            return ['status' => -1, 'msg' => '参数缺失', 'result' => ''];
		        }
		        
                //查找需要支付的订单
				$order = M('order_list')->where("order_id",$order_id)->find();
				if (!$order) {
				    return ['status' => -1, 'msg' => '订单不存在', 'result' => ''];
				}

				if (empty($order['user_id'])) {
					return ['status' => -1, 'msg' => '会员不存在', 'result' => ''];
				}

				//判断订单状态
				if ($order_status==1 && $order['order_status']>0) {
					return ['status' => -1, 'msg' => '该订单已付款', 'result' => ''];
				}
				if ($order_status==2 && $order['order_status']==0) {
					return ['status' => -1, 'msg' => '该订单还未付定金不能付尾款', 'result' => ''];
				}
				if ($order_status==2 && $order['order_status']>1) {
					return ['status' => -1, 'msg' => '该订单已付完全款', 'result' => ''];
				}
				if ($order_status==3 && $order['order_status']>0) {
					return ['status' => -1, 'msg' => '该订单已付款', 'result' => ''];
				}
				if ($order_status>3 || $order_status<1) {
					return ['status' => -1, 'msg' => '订单状态有误', 'result' => ''];
				}


				if ($order_status==1) {
					$pay_money=$order['paid_money'];
					$desc='定金支付';
				}elseif ($order_status==2) {
					$pay_money=$order['total_amount']-$order['paid_money']-$order['erci_price']-$order['youhui_price']-$order['coupon_price'];
					$desc='尾款支付';
				}elseif ($order_status==3) {
					$pay_money=$order_price['total_amount']-$order_price['youhui_price']-$order_price['coupon_price'];
					$desc='全款支付';
				}

				$usermoney=$this->user_money_is($order['user_id'],$pay_money); // 查询支付余额是否充足
				if ($usermoney) {
					return ['status' => -1, 'msg' => $usermoney['msg'], 'result' => ''];
				}
				
				if ($pay_money<0) {
					return ['status' => -1, 'msg' => '支付金额有误', 'result' => ''];
				}

				$zhifujine=M('users')->where(array('user_id'=>$order['user_id']))->setDec('user_money',$pay_money);//扣除余额支付的金额
                if (!$zhifujine) {
                	return ['status' => -1, 'msg' => '扣款失败', 'result' => ''];
                }

                $this->pay_log($order['user_id'],$pay_money,$order['order_id'],$order['order_sn'],$desc); // 生成支付记录
                $this->no_on_sale($order['goods_id'],$order['goods_type']); // 已售下架 // 天猫1淘宝2  商标3专利4入驻5京东6企业7
                $this->updateshijiyeji($order['order_id']);//计算更新业绩
                $orderupdate['order_status'] = $order_status;
				$update=M('order_list')->where("order_id", $order_id)->save($orderupdate);//支付状态修改
               
                if ($update) {
                	return ['status' => 1, 'msg' => '订单支付成功', 'result' => ''];
                }else{
                	return ['status' => -1, 'msg' => '订单支付失败', 'result' => ''];
                }
    }


    // 放款退款操作$type1放款 2退款
    public function fangkuan($order_id,$price,$beizhu,$type,$user_id){

            $where['order_id'] = $order_id;
            $order = M('order_list')->where($where)->find();

            if ($order['order_status']==1) {
            	$zuiduofangkuan=$order['paid_money']+$order['erci_price']-$order['kaizhi_price'];//最多放款
            }else{
            	$zuiduofangkuan=$order['goods_price']*0.9-$order['chajia_price']-$order['kaizhi_price'];//最多放款
            }
            //查询会员信息
            if ($type==1) {
            	$whereuser['nickname']=trim($order['sellername']);
            	$desc="订单放款";
            }elseif ($type==2) {
            	$whereuser['user_id']=$order['user_id'];
            	$desc="订单退款";
            }else{
            	return ['status'=>-1,'msg'=>'参数缺失'];
            }

            if ($user_id) {
	            $whereuser['user_id']=$user_id;// 如果是指定放款账户就转到该账户去
	            $desc="放款开支";

	            $zuiduofangkuan=$order['kaizhi_price'];//最多放款
	    	}

            $user = Db::name('users')->where($whereuser)->find();

            if (empty($user)) {
            	return ['status'=>-1,'msg'=>'会员不存在'];
            }

            // 退款只能罚单才能操作
            if ($type == 2 && $order['order_status'] !== 6) {
            	return ['status'=>-1,'msg'=>'该订单不是罚单，无法操作'];
            }
            // 放款只能是完成状态之前
            if ($type == 1 && $order['order_status'] > 3) {
            	return ['status'=>-1,'msg'=>'该订单无法放款'];
            }
            
             //该订单当前已放款金额
            $fk_price = M('fangkuan')->where($where)->sum('price');
            //该订单当前需要放款金额
            $needfangkuan=$price+$fk_price;//即将放款+之前放款总额

            
            if ($needfangkuan>$zuiduofangkuan) {
            	return ['status'=>-1,'msg'=>'金额不足，请核实后再操作'];
            }
                
            //新增可提现余额  放款
            $usermoneys=M('users')->where(array('user_id'=>$user['user_id']))->setInc('user_money',$price); 
 
            if ($usermoneys) {
            	$acclog=$this->pay_log($user['user_id'],-$price,$order_id,$order['order_sn'],$desc); // 生成支付记录
            }

             //操作者
             $admin_info = getAdminInfo(session('admin_id'));
             $czname = $admin_info['user_name'];

            if ($acclog) {
                //退款记录
                $data['order_id'] = $order_id; // 订单id
                $data['price'] = $price; // 放款金额
              
                if ($type==1) {
	            	$data['sellerid'] = $user['user_id']; // 卖家id
	                $data['sellername'] = $user['nickname']; // 卖家账号
	            }elseif ($type==2) {
	            	$data['userid'] = $user['user_id']; // 买家id
	                $data['username'] = $user['nickname']; // 买家账号
	            }
                $data['czname'] = $czname; // 操作者
                $data['beizhu'] = $beizhu; // 备注
                $data['add_time'] = time(); // 放款时间
                
                $row = M('fangkuan')->add($data);
            }
           
            if(!$row){
                return ['status'=>-1,'msg'=>'操作失败'];
            }
            $this->updateshijiyeji($order['order_id']);//计算更新业绩

            return ['status'=>1,'msg'=>'操作成功'];
    }

	
    /**
     * 批量订单操作记录
     * @param $order_id
     * @param $action_note 备注
     * @param $status_desc 状态描述
     * @param $action_user
     * @return mixed
     */
    public function orderActionLog($order_id,$action_note,$status_desc,$action_user=0){
        $order = Db::name('order')->where(['order_id'=>$order_id])->find();
        $data=[
            'order_id'      => $order_id,
            'action_user'   => $action_user,
            'action_note'   => $action_note,
            'order_status'  => $order['order_status'],
            'log_time'      => time(),
            'status_desc'   => $status_desc,
        ];
        return M('order_action')->add($data);//订单操作记录
    }

	/**
	 * 取消订单后改变库存，根据不同的规格，商品活动修改对应的库存
	 * @param $order
     * @param $rec_id|订单商品表id 如果有只返还订单某个商品的库存,没有返还整个订单
     */
    public function alterReturnGoodsInventory($order, $rec_id='')
	{
        if($order['prom_type'] == 6){
            if($order['teamActivity']['team_type']==2){  //抽奖团取消不用退库存
                return ;
            }
        }
        if($rec_id){
            $orderGoodsWhere['rec_id'] = $rec_id;
            $retunn_info = Db::name('return_goods')->where($orderGoodsWhere)->select(); //查找购买数量和购买规格
            $order_goods_prom = Db::name('order_goods')->where($orderGoodsWhere)->find(); //购买时参加的活动
            $order_goods = $retunn_info;
            $order_goods[0]['prom_type'] = $order_goods_prom['prom_type'];
            $order_goods[0]['prom_id'] = $order_goods_prom['prom_id'];
            $order_goods[0]['goods_name'] = $order_goods_prom['goods_name'];
            $order_goods[0]['spec_key_name'] = $order_goods_prom['spec_key_name'];
        }else{
            $orderGoodsWhere = ['order_id'=>$order['order_id']];
            $order_goods = Db::name('order_goods')->where($orderGoodsWhere)->select(); //查找购买数量和购买规格
        }
		foreach($order_goods as $key=>$val){
			if(!empty($val['spec_key'])){ // 先到规格表里面扣除数量
				$SpecGoodsPrice = new SpecGoodsPrice();
				$specGoodsPrice = $SpecGoodsPrice::get(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']]);
				if($specGoodsPrice){
					$specGoodsPrice->store_count = $specGoodsPrice->store_count + $val['goods_num'];
					$specGoodsPrice->save();//有规格则增加商品对应规格的库存
				}
			}
			update_stock_log($order['user_id'], $val['goods_num'], $val, $order['order_sn']);//库存日志
			Db::name('goods')->where(['goods_id' => $val['goods_id']])->setInc('store_count', $val['goods_num']);//增加商品库存
			Db::name('Goods')->where("goods_id", $val['goods_id'])->setDec('sales_sum', $val['goods_num']); // 减少商品销售量
			//更新活动商品购买量
			if ($val['prom_type'] == 1 || $val['prom_type'] == 2) {
				$GoodsPromFactory = new GoodsPromFactory();
				$goodsPromLogic = $GoodsPromFactory->makeModule($val, $specGoodsPrice);
				$prom = $goodsPromLogic->getPromModel();
				if ($prom['is_end'] == 0) {
					$tb = $val['prom_type'] == 1 ? 'flash_sale' : 'group_buy';
					M($tb)->where("id", $val['prom_id'])->setDec('buy_num', $val['goods_num']);
					M($tb)->where("id", $val['prom_id'])->setDec('order_num',1);
				}
			}
		}
	}

    /**
     * 修改订单兑换码
     * @param $order
     */
    public function alterOrderCode($order)
    {
        Db::name('vr_order_code')->where(['order_id'=>$order['order_id']])->save(['refund_lock'=>1]);
    }



/*****admin*****/
    /**
     * 根据商品型号获取商品
     * @param $goods_id_arr
     * @return array|bool
     */
    public function get_spec_goods($goods_id_arr){
        if(!is_array($goods_id_arr)) return false;
        foreach($goods_id_arr as $key => $val)
        {
            $arr = array();
            $goods = Db::name('goods')->where("goods_id = $key")->find();
            $arr['goods_id'] = $key; // 商品id
            $arr['goods_name'] = $goods['goods_name'];
            $arr['goods_sn'] = $goods['goods_sn'];
            $arr['market_price'] = $goods['market_price'];
            $arr['goods_price'] = $goods['shop_price'];
            $arr['cost_price'] = $goods['cost_price'];
            $arr['member_goods_price'] = $goods['shop_price'];
            foreach($val as $k => $v)
            {
                $arr['goods_num'] = $v['goods_num']; // 购买数量
                // 如果这商品有规格
                if($k != 'key')
                {
                    $arr['spec_key'] = $k;
                    $spec_goods = Db::name('spec_goods_price')->where("goods_id = $key and `key` = '{$k}'")->find();
                    $arr['spec_key_name'] = $spec_goods['key_name'];
                    $arr['member_goods_price'] = $arr['goods_price'] = $spec_goods['price'];
                    $arr['sku'] = $spec_goods['sku']; // 参考 sku  http://www.zhihu.com/question/19841574
                }
                $order_goods[] = $arr;
            }
        }
        return $order_goods;
    }


    /*
     * 获取订单商品总价格
     */
    public function getGoodsAmount($order_id){
        $sql = "SELECT SUM(goods_num * goods_price) AS goods_amount FROM __PREFIX__order_goods WHERE order_id = {$order_id}";
        $res = DB::query($sql);
        return $res[0]['goods_amount'];
    }

    /**
     * 得到发货单流水号
     */
    public function get_delivery_sn()
    {
//        /* 选择一个随机的方案 */send_http_status('310');
        mt_srand((double) microtime() * 1000000);
        return date('YmdHi') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }


    //管理员取消付款
    function order_pay_cancel($order_id)
    {
        //如果这笔订单已经取消付款过了
		$count = Db::name('order')->where(['order_id' => $order_id, 'pay_status' => 1])->count();   // 看看有没已经处理过这笔订单  支付宝返回不重复处理操作
        if($count == 0) return false;
        // 找出对应的订单
		$Order = new \app\common\logic\Order();
		$Order->setOrderById($order_id);
		$order = $Order->getOrder();
        // 增加对应商品的库存
        $orderGoodsArr = Db::name('OrderGoods')->where("order_id = $order_id")->select();
        foreach($orderGoodsArr as $key => $val)
        {
            if(!empty($val['spec_key']))// 有选择规格的商品
            {   // 先到规格表里面增加数量 再重新刷新一个 这件商品的总数量
                $SpecGoodsPrice = new \app\common\model\SpecGoodsPrice();
                $specGoodsPrice = $SpecGoodsPrice::get(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']]);
                $specGoodsPrice->where(['goods_id' => $val['goods_id'], 'key' => $val['spec_key']])->setDec('store_count', $val['goods_num']);
                refresh_stock($val['goods_id']);
            }else{
                $specGoodsPrice = null;
                Db::name('Goods')->where("goods_id = {$val['goods_id']}")->setInc('store_count',$val['goods_num']); // 增加商品总数量
            }
            Db::name('Goods')->where("goods_id = {$val['goods_id']}")->setDec('sales_sum',$val['goods_num']); // 减少商品销售量
            //更新活动商品购买量
            if ($val['prom_type'] == 1 || $val['prom_type'] == 2) {
                $GoodsPromFactory = new \app\common\logic\GoodsPromFactory();
                $goodsPromLogic = $GoodsPromFactory->makeModule($val, $specGoodsPrice);
                $prom = $goodsPromLogic->getPromModel();
                if ($prom['is_end'] == 0) {
                    $tb = $val['prom_type'] == 1 ? 'flash_sale' : 'group_buy';
                    Db::name($tb)->where("id", $val['prom_id'])->setInc('buy_num', $val['goods_num']);
                    Db::name($tb)->where("id", $val['prom_id'])->setInc('order_num');
                }
            }
        }
        // 根据order表查看消费记录 给他会员等级升级 修改他的折扣 和 总金额
        Db::name('order')->where("order_id=$order_id")->save(array('pay_status'=>0));
//        update_user_level($order['user_id']);
        $User =new \app\common\logic\User();
		$User->setUserById($order['user_id']);
        $User->updateUserLevel();
		$Order->orderActionLog('订单取消付款','用户取消已付款订单');
        //分销设置
        Db::name('rebate_log')->where("order_id = {$order['order_id']}")->save(array('status'=>0));
    }


    /**
     * 当订单里商品都退货完成，将订单状态改成关闭
     * @param $order_id
     * @return bool
     * @throws \think\Exception
     */
    function closeOrderByReturn($order_id)
    {
        $order_goods_list = Db::name('order_goods')->where(['order_id' => $order_id])->select();
        $order_goods_count = count($order_goods_list);
        $order_goods_return_count = 0;//退货个数
        for ($i = 0; $i < $order_goods_count; $i++) {
            if ($order_goods_list[$i]['is_send'] == 3) {
                $order_goods_return_count++;
            }
        }
        if ($order_goods_count == $order_goods_return_count) {
            $res = Db::name('order')->where(['order_id' => $order_id])->update(['order_status' => 5]);
            if(!$res){
                return false;
            }
        }
        return true;
    }

    /**
     * 退货，取消订单，处理优惠券
     * @param $return_info
     */
    public function disposereRurnOrderCoupon($return_info){
        $coupon_list = Db::name('coupon_list')->where(['uid'=>$return_info['user_id'],'order_id'=>$return_info['order_id']])->find();    //有没有关于这个商品的优惠券
        if(!empty($coupon_list)){
            $update_coupon_data = ['status'=>0,'use_time'=>0,'order_id'=>0];
            Db::name('coupon_list')->where(['id'=>$coupon_list['id'],'status'=>1])->save($update_coupon_data);//符合条件的，优惠券就退给他
        }
        //追回赠送优惠券,一般退款才会走这里
        $coupon_info = Db::name('coupon_list')->where(['uid'=>$return_info['user_id'],'get_order_id'=>$return_info['order_id']])->find();
        if(!empty($coupon_info)){
            if($coupon_info['status'] == 1) { //如果优惠券被使用,那么从退款里扣
                $coupon = Db::name('coupon')->where(array('id' => $coupon_info['cid']))->find();
                if ($return_info['refund_money'] > $coupon['money']) {
                    //退款金额大于优惠券金额，先从这里扣
                    $return_info['refund_money'] = $return_info['refund_money'] - $coupon['money'];
                    Db::name('return_goods')->where(['id' => $return_info['id']])->save(['refund_money' => $return_info['refund_money']]);
                }else{
                    $return_info['refund_deposit'] = $return_info['refund_deposit'] - $coupon['money'];
                    Db::name('return_goods')->where(['id' => $return_info['id']])->save(['refund_deposit' => $return_info['refund_deposit']]);
                }
            }else {
                Db::name('coupon_list')->where(array('id' => $coupon_info['id']))->delete();
                Db::name('coupon')->where(array('id' => $coupon_info['cid']))->setDec('send_num');
            }
        }
    }


    public function getRefundGoodsMoney($return_goods){
        $order_goods = Db::name('order_goods')->where(array('rec_id'=>$return_goods['rec_id']))->find();
        if($return_goods['is_receive'] == 1){
            if($order_goods['give_integral']>0){
                $user = get_user_info($return_goods['user_id']);
                if($order_goods['give_integral']>$user['pay_points']){
                    //积分被使用则从退款金额里扣
                    $return_goods['refund_money'] = $return_goods['refund_money'] - $order_goods['give_integral']/100;
                }
            }
            $coupon_info = Db::name('coupon_list')->where(array('uid'=>$return_goods['user_id'],'get_order_id'=>$return_goods['order_id']))->find();
            if(!empty($coupon_info)){
                if($coupon_info['status'] == 1) { //如果优惠券被使用,那么从退款里扣
                    $coupon = Db::name('coupon')->where(array('id' => $coupon_info['cid']))->find();
                    if ($return_goods['refund_money'] > $coupon['money']) {
                        $return_goods['refund_money'] = $return_goods['refund_money'] - $coupon['money'];//退款金额大于优惠券金额
                    }
                }
            }
        }
        return $return_goods['refund_money'];
    }



    //识别单号
    public function distinguishExpress(){
        require_once(PLUGIN_PATH . 'kdniao/kdniao.php');
        $kdniao = new \kdniao();
        $data['LogisticCode'] = I('invoice_no');
        $kdniao->getOrderTracesByJson(json_encode($data));
    }
}