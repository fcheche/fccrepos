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
 * Date: 2015-12-11
 */
namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Db;
use think\Loader;
use app\common\logic\OrderLogic;

class Coupon extends Base {
    /**----------------------------------------------*/
     /*                看店卡控制器                  */
    /**----------------------------------------------*/
    /*
     * 看店卡类型列表
     */
    public function index(){
        //获取看店卡列表

    	$count =  M('coupon')->count();
    	$Page = new Page($count,10);
        $show = $Page->show();
        $lists = M('coupon')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists);
        $this->assign('pager',$Page);// 赋值分页输出
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
        return $this->fetch();
    }

    /*
     * 添加编辑一个看店卡类型
     */
    public function coupon_info(){
        $cid = I('get.id/d');
        if ($cid) {
            $coupon = M('coupon')->where(array('id' => $cid))->find();
            if (empty($coupon)) {
                $this->error('看店卡不存在');
            }
            $this->assign('coupon', $coupon);
        }
        return $this->fetch();
    }

    /**
     * 添加编辑看店卡
     */
    public function addEditCoupon()
    {
        $data = I('post.');
        if ($data['id']) {
            $row = Db::name('coupon')->where(array('id' => $data['id']))->save($data);
        }else{
            $data['add_time'] = time();
            $row = Db::name('coupon')->add($data);
        }
       
        
        if ($row !== false) {
            $this->ajaxReturn(['status' => 1, 'msg' => '编辑看店卡成功', 'result' => '']);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '编辑看店卡失败', 'result' => '']);
        }
    }

    
    /*
     * 删除看店卡类型
     */
    public function del_coupon(){
        //获取看店卡ID
        $cid = I('get.id/d');
        //查询是否存在看店卡
        $row = M('coupon')->where(array('id'=>$cid))->delete();
        if (!$row) {
            $this->ajaxReturn(['status' => 0, 'msg' => '看店卡不存在，删除失败']);
        }
        
        //删除此类型下的看店卡
        M('coupon_list')->where(array('cid'=>$cid))->delete();
        $this->ajaxReturn(['status' => 1, 'msg' => '删除成功']);
    }


    /*
     * 看店卡购买详细查看
     */
    public function coupon_list(){
        $user_id = I('user_id');
       
        if ($user_id) {
           $condition['uid'] = ['eq',$user_id];
        }
      
        //查询该看店卡的列表的数量
        $count = M('coupon_list')->where($condition)->count();
    	$Page = new Page($count,10);
    	$show = $Page->show();
        
        //查询该看店卡的列表
        $coupon_lists = Db::name('coupon_list')->where($condition)->limit($Page->firstRow,$Page->listRows)->order($sort_order)->select();
        foreach ($coupon_lists as $ko => $valo) {

            $valo['cname']=M('coupon')->where(['id' => $valo['cid']])->getField("name");

            $valo['goods']=M('goods')->where(['goods_id' => $valo['goods_id']])->find();
           
            $valo['nickname']=M('users')->where(['user_id'=>$valo['uid']])->getField('nickname');

            $valo['order_sn']=Db::name('order_list')->where(['order_id'=>$valo['order_id']])->getField('order_sn');

            $coupon_list[]=$valo;
        }
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);            	
    	$this->assign('page',$show);
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    
    /*
     * 删除一张看店卡
     */
    public function coupon_list_del(){
        //获取看店卡ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在看店卡
         $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }

    public function order(){
        $admin_info = getAdminInfo(session('admin_id'));
        
        if (empty($admin_info['admin_id'])) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        }
        return $this->fetch();
    }

    public function ajaxorder(){
        
        $begin = $this->begin;
        $end = $this->end;
        $condition['deleted'] = 0;//未删除

            // 搜索条件     
        $condition['fadan_time'] = array('eq',0);//排除罚单
        $condition['order_status'] = array('lt',5);//未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
      
        $keywords = I('keywords','','trim');
       
        $condition['goods_type'] = array('eq',111);//天猫1淘宝2 商标3专利4入驻5京东6企业7 活动111
        
        $keyType = I("key_type");
        $keywords = I('keywords','','trim');


        $consignee = I('consignee');
        $xs_name = I('xs_name') ;
        if ($consignee) {
           $users=M('users')->where(['nickname'=>['like',"%$consignee%"]])->getField('user_id',true);
           $condition['user_id'] = ['in',$users];
        }
        if ($xs_name) {
           $condition['xs_adminid'] = M('admin')->where(['user_name'=>$xs_name])->getField('admin_id');
        }
       
        $order_sn = ($keyType && $keyType == 'order_sn') ? $keywords : I('order_sn') ;
        $order_sn ? $condition['order_sn'] = ['like',"%$order_sn%"] : false;


        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
               
        $sort_order = 'order_id desc';
        
        $count = Db::name('order_list')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();

        $orderLists = Db::name('order_list')->where($condition)->limit($Page->firstRow,$Page->listRows)->order($sort_order)->select();
        foreach ($orderLists as $ko => $valo) {
            $where['order_id']=$valo['order_id'];
            $goods=M('order_price')->where($where)->find();
            $valo['goods_price']=$goods['goods_price'];
            $valo['shiji_yeji']=$goods['shiji_yeji'];
            $valo['goods_name']=M('coupon')->where(['id' => $valo['goods_id']])->getField("name");
            $valo['total_amount']=$goods['total_amount'];

            // 查看购买的看店卡使用次数 type1看店卡type2代金券
            $valo['used_kdk']=M('coupon_list')->where(['type' => 1,'status' => 1,'uid' => $valo['user_id']])->count();
            $valo['used_djq']=M('coupon_list')->where(['type' => 2,'status' => 1,'uid' => $valo['user_id']])->count();
            $valo['xs_name']=M('admin')->where(['admin_id'=>$valo['xs_adminid']])->getField('user_name');

            $valo['user_names']=M('users')->where(['user_id'=>$valo['user_id']])->getField('nickname');

            $orderList[]=$valo;
        }
        
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');

        $CartLogic = new OrderLogic();
        $act_list = $CartLogic->act_list_select();//查看权限
        $this->assign('act_list',$act_list);
        $this->assign('roleid',$roleid);

        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
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

        $coupon = Db::name('coupon')->where(['id'=>$order['goods_id']])->find();
       
        if(IS_POST)
        {
            
            $order['goods_type'] = I('goods_type');// 订单大类
            $order['admin_note'] = I('admin_note'); // 管理员备注
          
            $order['xs_adminid'] = I('xs_adminid');// 销售人
            $order['gs_adminid'] = I('xs_adminid');// 挂售人
            if ($order['xs_adminid']) {
                $xs_corps_id=Db::name('admin')->where("admin_id", $order['xs_adminid'])->getField("corps_id");
                $order['xs_corps_id'] = $xs_corps_id;// 销售战队
                $order['gs_corps_id'] = $xs_corps_id;// 挂售战队
            }
           
            $o = M('order_list')->where('order_id='.$order_id)->save($order);
           
            if ($o) {
                $CartLogic = new OrderLogic();
                $rec_id = $CartLogic->order_action_log($order_id,I('admin_note'),'修改订单',session('admin_id')); // 订单操作记录
            }
            
            if($rec_id){
                $this->success('修改成功',U('Admin/Ordertm/tianmao_order',array('order_id'=>$order_id)));
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
        $this->assign('coupon',$coupon);
        return $this->fetch();
    }
}