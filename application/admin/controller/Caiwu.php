<?php
/**

 */
namespace app\admin\controller;

use think\AjaxPage;
use think\Page;
use think\Db;
use app\admin\logic\ArticleCatLogic;
use app\common\model\Users;
use app\admin\logic\UsersLogic;
use app\common\logic\OrderLogic;

class Caiwu extends Base {

    //充值记录
     public function recharge(){
        $timegap = urldecode(I('timegap'));
        $nickname = I('nickname');
        $pay_status=I('pay_status');
        $map = array();
        if($timegap){
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end = $gap[1];
            $map['ctime'] = array('between',array(strtotime($begin),strtotime($end)));
            $this->assign('begin',$begin);
            $this->assign('end',$end);
        }
        if($nickname){
            $map['nickname'] = array('like',"%$nickname%");
            $this->assign('nickname',$nickname);
        }  
        if($pay_status != 3 && $pay_status != 4 && $pay_status != ''){
            $map['pay_status'] = array('eq',"$pay_status");
            $this->assign('pay_status',$pay_status);
        }elseif($pay_status == 3){
            $map['pay_status'] = array('eq',0);
            $this->assign('pay_status',$pay_status);
        }else{
            $this->assign('pay_status',$pay_status);
        }

        $count = M('recharge')->where($map)->count();
   
        $where = $map;
        $where['pay_status'] = 1;
        $account = M('recharge')->where($where)->sum('account');
        // print_r(M('recharge')->getlastsql());die;
        $page = new Page($count);
        $lists  = M('recharge')->where($map)->order('ctime desc')->limit($page->firstRow.','.$page->listRows)->select();

        $this->assign('page',$page->show());
        $this->assign('account',$account);
        $this->assign('pager',$page);
        $this->assign('lists',$lists);

        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $this->assign('roleid', $roleid);
        return $this->fetch();
    }

    //所有会员
    public function index(){
        return $this->fetch();
    }

    /**
     * 会员列表
     */
    public function ajaxindex(){
        // 搜索条件   
        $condition = array();
        // $condition= ['reg_time'=>['between',"$this->begin,$this->end"]];
        $account = I('account');
        $pricetype = I('pricetype');

        $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = $admin_info['admin_id'];
        $user_name = M('admin')->where(['admin_id'=>$admin_id])->getField('user_name');
        $roleid = M('admin')->where(['admin_id'=>$admin_id])->getField('role_id');
        $this->assign('roleid',$roleid);
        if (empty($account)&&$roleid==17) {
           $condition['id']=0;
        }

        $account ? $condition['nickname|mobile|qq'] = ['like',"%$account%"] : false;
        if ($pricetype>0) {
          $condition['user_money'] = ['neq',0];
        }
        $condition['is_ceshi'] = ['neq',1];

        $sort_order = I('order_by').' '.I('sort');
               
        $count = M('users')->where($condition)->count();
        $user_money_sum = M('users')->where($condition)->sum('user_money');
        $this->assign('user_money_sum',$user_money_sum);

        $Page  = new AjaxPage($count,10);
        $userList = M('users')->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                       
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }


    public function Users(){
      
        return $this->fetch();
    }

    /**
     * 买家会员列表
     */
    public function ajaxusers(){

        // 搜索条件   
        $condition = array();
        // $condition= ['reg_time'=>['between',"$this->begin,$this->end"]];
        $account = I('account');

        $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = $admin_info['admin_id'];
        $user_name = M('admin')->where(['admin_id'=>$admin_id])->getField('user_name');
        $roleid = M('admin')->where(['admin_id'=>$admin_id])->getField('role_id');
        $this->assign('roleid',$roleid);
        if (empty($account)&&$roleid==17) {
           $condition['id']=0;
        }
        $users = Db::name('order_list')->select();
        foreach ($users as $key => $value) {
            $user_id_array = $user_id_array.','.$value['user_id'];
        }

        $condition = array(
                'user_id' => array('IN', $user_id_array),
            );

        $account ? $condition['nickname|mobile|qq'] = ['=',"$account"] : false;
      
        $sort_order = I('order_by').' '.I('sort');
            
        $count = M('users')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        $userList = M('users')->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
        $user_id_arr = get_arr_column($userList, 'user_id');
   
        // print_r($usersModel->getlastsql());     
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function sellers(){
        return $this->fetch();
    }

    /**
     * 卖家会员列表
     */
    public function ajaxsellers(){

        // 搜索条件   
        $condition = array();
        // $condition= ['reg_time'=>['between',"$this->begin,$this->end"]];
        $account = I('account');

        $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = $admin_info['admin_id'];
        $user_name = M('admin')->where(['admin_id'=>$admin_id])->getField('user_name');
        $roleid = M('admin')->where(['admin_id'=>$admin_id])->getField('role_id');
        $this->assign('roleid',$roleid);
        if (empty($account)&&$roleid==17) {
           $condition['id']=0;
        }

         $sellers = Db::name('order_list')->select();
        foreach ($sellers as $key => $value) {
            $sellers_array = $sellers_array.','.$value['sellername'];
        }

        $condition['nickname|mobile'] = array('IN', $sellers_array);

        $account ? $condition['nickname|mobile|qq'] = ['=',"$account"] : false;

        $sort_order = I('order_by').' '.I('sort');
               
        $count = M('users')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        $userList = M('users')->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
        $user_id_arr = get_arr_column($userList, 'user_id');

        // print_r($usersModel->getlastsql());
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }


     /**
     *订单明细
     */
    public function order_caiwu(){
        $admin_info = getAdminInfo(session('admin_id'));
        
        if (empty($admin_info['admin_id'])) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        }
        
            return $this->fetch();
    }

         /**
     * Ajax订单明细
     */
    public function ajaxorder_caiwu(){

        $begin = $this->begin;
        $end = $this->end;
        $wc_start_time = strtotime(I("wc_start_time"));
        $wc_end_time = strtotime(I("wc_end_time"));

        $condition['deleted'] = 0;//未删除
 
            // 搜索条件
        $condition['is_return'] = array('eq',0);//排除退款订单
        $condition['order_status'] = array('in',[1,2,3,4,6]);//未付款0  付定金1  付尾款2  一次性全款付3  完成4  退款5  罚单6  取消订单7
      
        $consignee = I('consignee');
        $xs_name = I('xs_name') ;
        $gs_name = I('gs_name') ;
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

        $order_sn = I('order_sn');
        $order_sn ? $condition['order_sn'] = ['like',"%$order_sn%"] : false;

        $goods_sn = I('goods_sn');
        $goods_sn ? $condition['goods_sn'] = ['like',"%$goods_sn%"] : false;

        $sellername = I('sellername');
        $sellername ? $condition['sellername'] = ['like',"%$sellername%"] : false;

        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }

        if($wc_start_time>0 && $wc_end_time>0){
            $condition['confirm_time'] = array('between',"$wc_start_time,$wc_end_time");
        }


        I('order_status') != '' ? $condition['order_status'] = I('order_status') : false;
        if (I('order_status')==2) {
            $condition['order_status'] = array('between',"2,3");
        }
  
        $sort_order = I('order_by','order_id').' '.I('sort','DESC');
       
        $count = Db::name('order_list')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();

        $orderLists = Db::name('order_list')->where($condition)->limit($Page->firstRow,$Page->listRows)->order($sort_order)->select();
// print_r(Db::name('order_list')->getlastsql());

        $yongjin=0;
        $shijiyeji=0;
        // $ddzijinall=0;
        $seller_count=0;
        $ddkaizhi=0;
        $wgkall=0;
        $order_amount_count=0;
         foreach ($orderLists as $ko => $valo) {
            $where['order_id']=$valo['order_id'];

            $valo['gs_name']=M('admin')->where(['admin_id'=>$valo['gs_adminid']])->getField('user_name');

            $valo['xs_name']=M('admin')->where(['admin_id'=>$valo['xs_adminid']])->getField('user_name');

            $valo['consignee']=M('users')->where(['user_id'=>$valo['user_id']])->getField('nickname');

            //已放款金额
            $fk_price = M('fangkuan')->where(array('order_id'=>$valo['order_id']))->sum('price');
            
            // 资金池
            // $valo['ddzijin']=$valo['total_amount']-$fk_price-$valo['kaizhi_price'];
            
            if ($valo['fadan_time']>0) {
                $valo['wfangkuan']=0;
                $valo['yongjin']=$valo['shiji_yeji'];
                $valo['seller_count']=0;
                $valo['order_amount_count']=0;
            }else{
                 // 未放款
                $valo['wfangkuan']=round(($valo['goods_price']*0.9-$valo['chajia_price']-$fk_price),2);
                // 佣金 利润 
                $valo['yongjin']=$valo['goods_price']*0.2+$valo['chajia_price']-$valo['youhui_price']-$valo['kaizhi_price']-$valo['coupon_price'];
                // 卖家到手
                $valo['seller_count']=$valo['goods_price']*0.9-$valo['chajia_price'];
                // 买家应付
                $valo['order_amount_count']=$valo['total_amount']-$valo['youhui_price']-$valo['coupon_price'];
            }
            
            $yongjin=$yongjin+$valo['yongjin'];
            $shijiyeji=$shijiyeji+$valo['shiji_yeji'];
            $ddkaizhi=$ddkaizhi+$valo['kaizhi_price'];
            $wgkall=$wgkall+$valo['wfangkuan'];
            $order_amount_count=$order_amount_count+$valo['order_amount_count'];
            // $ddzijinall=$ddzijinall+$valo['ddzijin'];
            $seller_count=$seller_count+$valo['seller_count'];

            $orderList[]=$valo;
        }
        
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');

        $CartLogic = new OrderLogic();
        $act_list = $CartLogic->act_list_select();//查看权限
        
        $this->assign('act_list',$act_list);
        $this->assign('roleid',$roleid);
        $this->assign('xs_adminid',session('admin_id'));
        $this->assign('orderList',$orderList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);


        $this->assign('seller_count',$seller_count);
        $this->assign('order_amount_count',$order_amount_count);
        $this->assign('wgkall',round($wgkall,2));
        $this->assign('bidbondall',$bidbondall);
        $this->assign('ddkaizhi',$ddkaizhi);
        $this->assign('ddzijinall',$ddzijinall);
        $this->assign('yongjin',$yongjin);
        $this->assign('shijiyeji',$shijiyeji);
        return $this->fetch();
    }


        public function kaizhilist(){
        $kaizhi =  M('Kaizhi'); 
        $res = $list = array();
        $p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
        $size = empty($_REQUEST['size']) ? 20 : $_REQUEST['size'];
        
        $where = " type=0 ";
        $keywords = trim(I('keywords'));
        $start_time = strtotime(I('start_time'));
        $end_time = strtotime(I('end_time'));
        if ($start_time && $end_time) {
          $where = "$where and (addtime < $end_time and addtime >= $start_time)" ;
        }
        
        $keywords && $where.=" and content like '%$keywords%' ";
        $cat_id = I('cat_id',0);
        $cat_id && $where.=" and cat_id = $cat_id ";
        $res = $kaizhi->where($where)->order('kz_id desc')->page("$p,$size")->select();
        $count = $kaizhi->where($where)->count();// 查询满足要求的总记录数
        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数
        //$page = $pager->show();//分页显示输出
        
        $cats = M('kaizhi_cat')->order('cat_id asc')->select();
        $this->assign('cats',$cats);
        if($res){
            foreach ($res as $val){
                $val['category'] = $cats[$val['cat_id']-1]['cat_name'];
                $val['addtime'] = date('Y-m-d H:i',$val['addtime']);                
                $list[] = $val;
            }
        }

        
        $this->assign('cat_id',$cat_id);
        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$pager);// 赋值分页输出        
        return $this->fetch('kaizhilist');
    }



    public function categoryList(){
        $cat_list = M('kaizhi_cat')->order('cat_id desc')->select();
        $this->assign('cat_list',$cat_list);
        return $this->fetch('categoryList');
    }

    public function category()
    {
        $act = I('get.act', 'add');
        $cat_id = I('get.cat_id/d');
        if ($cat_id) {
            $cat_info = M('kaizhi_cat')->where('cat_id=' . $cat_id)->find();
            $this->assign('cat_info', $cat_info);
        }
        $this->assign('act', $act);
        $this->assign('cat_select', $cats);
        return $this->fetch();
    }

     public function categoryHandle()
    {
        $data = I('post.');

        if ($data['act'] == 'add') {
            $r = M('kaizhi_cat')->add($data);
        } elseif ($data['act'] == 'edit') {
            $cat_info = M('kaizhi_cat')->where("cat_id",$data['cat_id'])->find();
            if($cat_info['cat_type'] == 1 && $data['parent_id'] > 1){
                $this->ajaxReturn(['status' => -1, 'msg' => '可更改系统预定义分类的上级分类']);
            }
            $r = M('kaizhi_cat')->where("cat_id",$data['cat_id'])->save($data);
        } elseif ($data['act'] == 'del') {
            // if($data['cat_id']<9){
            //  $this->ajaxReturn(['status' => -1, 'msg' => '系统默认分类不得删除']);
            // }
            // if (M('kaizhi_cat')->where('parent_id', $data['cat_id'])->count()>0)
            // {
            //     $this->ajaxReturn(['status' => -1, 'msg' => '还有子分类，不能删除']);
            // }
            if (M('article')->where('cat_id', $data['cat_id'])->count()>0)
            {
                $this->ajaxReturn(['status' => -1, 'msg' => '该分类下有明细，不允许删除，请先删除该分类下的明细信息']);
            }
            $r = M('kaizhi_cat')->where('cat_id', $data['cat_id'])->delete();
        }
        
        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        } 
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }
    
    


   
    
    public function kaizhi(){
        $ArticleCat = new ArticleCatLogic();
 		$act = I('GET.act','add');
        $info = array();
        $info['addtime'] = time();
        if(I('GET.kz_id')){
           $kz_id = I('GET.kz_id');
           $info = M('kaizhi')->where('kz_id='.$kz_id)->find();
        }
        $cats = M('kaizhi_cat')->where('1=1')->order('cat_id desc')->select();

        $this->assign('cat_select',$cats);
        $this->assign('act',$act);
        $this->assign('info',$info);
        return $this->fetch();
    }
    


   
    public function kaizhiHandle()
    {
        $data = I('post.');
        if ($data) {
          $data['addtime'] = strtotime($data['addtime']);
        if ($data['act'] == 'add') {
            $data['click'] = mt_rand(1000,1300);
        	$data['addtime'] = time(); 
            $r = M('kaizhi')->add($data);
        } elseif ($data['act'] == 'edit') {
            $r = M('kaizhi')->where('kz_id='.$data['kz_id'])->save($data);
        } elseif ($data['act'] == 'del') {
        	$r = M('kaizhi')->where('kz_id='.$data['kz_id'])->delete(); 	
        }
        
        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
            
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
         }
    }
    
    
}