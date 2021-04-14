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
use app\admin\logic\OrderLogic;
use think\AjaxPage;
use think\console\command\make\Model;
use think\Page;
use think\Verify;
use think\Db;
use app\admin\logic\UsersLogic;
use app\common\model\Withdrawals;
use app\common\model\Users;
use think\Loader;

class User extends Base {

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
       
        $user_name = M('admin')->where(['admin_id'=>session('admin_id')])->getField('user_name');
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
       
        if (!in_array($roleid,[1]) && $account=='') {
           $condition['user_id'] =0;
        }

        $this->assign('act_list',$act_list);

        $account ? $condition['nickname|mobile|qq'] = ['=',"$account"] : false;

        $sort_order = I('order_by').' '.I('sort');
               
        $count = M('users')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        $userList = M('users')->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
                             
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }




    //冻结弹窗
    public function dongjie(){
        $user_id = I('user_id');
        $user_id <= 0 && $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！']);
         
        $where['user_id']=$user_id;
        $users = M('users')->where($where)->find();
       
        $this->assign('zuiduo_dongjie', $users['user_money']);
        $this->assign('user_id', $user_id);

         return $this->fetch();
    }

        //解冻弹窗
    public function jiedong(){
        $user_id = I('user_id');
        $user_id <= 0 && $this->ajaxReturn(['status'=>1,'msg'=>'参数错误！！']);
        $where['user_id']=$user_id;
        $users = M('users')->where($where)->find();
       
        $this->assign('zuiduo_jiedong', $users['dongjie_money']);
        $this->assign('user_id', $user_id);
        return $this->fetch();
    }


         // 解冻操作
    public function jiedongact(){

        $user_id = I('user_id');
        $money = I('post.money/d', 0);
        if ($user_id <= 0) {
           echo "<script>alert('参数错误！！');parent.window.location.reload();</script>";die;
        }
        if ($money <= 0) {
           echo "<script>alert('参数错误！！');parent.window.location.reload();</script>";die;
        }
        
        
            $where['user_id'] = $user_id;
            $users = M('users')->where($where)->find();
            if (empty($users['user_id'])) {
                echo "<script>alert('会员不存在，请联系管理员处理');parent.window.location.reload();</script>";die;
            }

            $zuiduo_jiedong=$users['dongjie_money'];
            if ($money>$zuiduo_jiedong) {
                echo "<script>alert('该用户最多解冻".$zuiduo_jiedong."元');parent.window.location.reload();</script>";die;
            }
            
                //操作者
            $admin_info = getAdminInfo(session('admin_id'));
            $czname = $admin_info['user_name'];

                // 解冻操作
            $adds['user_money']=$users['user_money']+$money;
            $adds['dongjie_money']=$users['dongjie_money']-$money;
            $row = M('users')->where(array('user_id'=>$user_id))->save($adds);

                //生成记录
                if ($row) {
                    $account_log = array(
                        'user_id'       => $user_id,
                        'user_money'    => $money,
                        'change_time'   => time(),
                        'desc'   => $czname."操作解冻该用户冻结金额"
                    );
                    M('account_log')->add($account_log);
                }

            if ($row) {
                echo "<script>alert('操作成功');parent.window.location.reload();</script>";
            }
            // $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Order/detail',['order_id'=>$id])]);
    }


         // 冻结操作
    public function dongjieact(){

        $user_id = I('user_id');
        $money = I('post.money/d', 0);
        if ($user_id <= 0) {
           echo "<script>alert('参数错误！！');parent.window.location.reload();</script>";die;
        }
        if ($money <= 0) {
           echo "<script>alert('参数错误！！');parent.window.location.reload();</script>";die;
        }
        
            $where['user_id'] = $user_id;
            $users = M('users')->where($where)->find();
            if (empty($users['user_id'])) {
                echo "<script>alert('会员不存在，请联系管理员处理');parent.window.location.reload();</script>";die;
            }

            $zuiduo_dongjie=$users['user_money'];
            if ($money>$zuiduo_dongjie) {
                echo "<script>alert('该用户最多冻结".$zuiduo_dongjie."元');parent.window.location.reload();</script>";die;
            }
            
                //操作者
            $admin_info = getAdminInfo(session('admin_id'));
            $czname = $admin_info['user_name'];

                // 冻结操作
            $adds['user_money']=$users['user_money']-$money;
            $adds['dongjie_money']=$users['dongjie_money']+$money;
            $row = M('users')->where(array('user_id'=>$user_id))->save($adds);

                //生成记录
                if ($row) {
                    $account_log = array(
                        'user_id'       => $user_id,
                        'user_money'    => -$money,
                        'change_time'   => time(),
                        'desc'   => $czname."操作冻结该用户余额"
                    );
                    M('account_log')->add($account_log);
                }

            if ($row) {
                echo "<script>alert('操作成功');parent.window.location.reload();</script>";
            }
    }

    /**
     * 会员详细信息查看
     */
    public function detail(){
        $uid = I('get.id');
        $user = D('users')->where(array('user_id'=>$uid))->find();
        if(!$user)
            exit($this->error('会员不存在'));
        if(IS_POST){
            //  会员信息编辑
            $password = I('post.password');
            $password2 = I('post.password2');
            if($password != '' && $password != $password2){
                exit($this->error('两次输入密码不同'));
            }
            if($password == '' && $password2 == ''){
                unset($_POST['password']);
            }else{
                $adds['password'] = encrypt($_POST['password']);
            }

            // 支付密码
            $paypwd = I('post.paypwd');
            $paypwd2 = I('post.paypwd2');
            if($paypwd != '' && $paypwd != $paypwd2){
                exit($this->error('两次输入密码不同'));
            }
            if($paypwd == '' && $paypwd2 == ''){
                unset($_POST['paypwd']);
            }else{
                $adds['paypwd'] = encrypt($_POST['paypwd']);
            }

            if(!empty($_POST['email']))
            {   $email = trim($_POST['email']);
                $adds['email'] = trim($_POST['email']);
                $c = M('users')->where("user_id != $uid and email = '$email'")->count();
                $c && exit($this->error('邮箱不得和已有用户重复'));
            }
            
            
            if(!empty($_POST['mobile']))
            {   
                $mobile = trim($_POST['mobile']);
                $adds['mobile'] = trim($_POST['mobile']);
                $c = M('users')->where("user_id != $uid and mobile = '$mobile'")->count();
                $c && exit($this->error('手机号不得和已有用户重复'));
                 // 如果当前手机号跟提交的不一样（即：将要修改手机号）
                if ($user['mobile'] != $_POST['mobile']) {
                    $add_log['user_id']=$uid;
                    $add_log['admin_id']=session('admin_id');
                    $add_log['old_tel']=$user['mobile'];
                    $add_log['act_time']=time();
                    M('user_log')->add($add_log);
                }
                
            }else{
                exit($this->error('手机号不能为空'));
            }

            if($_POST['qq'] == ''){
                unset($_POST['qq']);
            }else{
                $adds['qq'] = trim($_POST['qq']);
            }

            if($_POST['sex'] == ''){
                unset($_POST['sex']);
            }else{
                $adds['sex'] = trim($_POST['sex']);
            }

            if(!empty($_POST['realname']))
            {   
                $realname = trim($_POST['realname']);
                $adds['realname']=$realname;
            }  
             if(!empty($_POST['idcard']))
            {   
                $idcard = trim($_POST['idcard']);
                $adds['idcard']=$idcard;
            }  
            if(!empty($_POST['uname_alipay']))
            {   
                $uname_alipay = trim($_POST['uname_alipay']);
                $adds['uname_alipay']=$uname_alipay;
            }  
            if(!empty($_POST['kh_uname']))
            {   
                $kh_uname = trim($_POST['kh_uname']);
                $adds['kh_uname']=$kh_uname;
            }  
             if(!empty($_POST['cash_alipay']))
            {   
                $cash_alipay = trim($_POST['cash_alipay']);
                $adds['cash_alipay']=$cash_alipay;
            }  
             if(!empty($_POST['kh_addr']))
            {   
                $kh_addr = trim($_POST['kh_addr']);
                $adds['kh_addr']=$kh_addr;
            }  
             if(!empty($_POST['cash_unionpay']))
            {   
                $cash_unionpay = trim($_POST['cash_unionpay']);
                $adds['cash_unionpay']=$cash_unionpay;
            }
            
             // $adds['user_money'] = trim($_POST['user_money']);    
            $row = M('users')->where(array('user_id'=>$uid))->save($adds);

            if($row){
                exit($this->success('修改成功'));
            }
            exit($this->error('未作内容修改或修改失败'));
        }
    
        $admin_info = getAdminInfo(session('admin_id'));
        $this->assign('admin_info',$admin_info);

        $user_log = M('user_log')->where(['user_id' => $user['user_id']])->select();
        $this->assign('user_log',$user_log);

        $this->assign('user',$user);
        return $this->fetch();
    }
     public function add_user(){
        if(IS_POST){
            $data = I('post.');            
            $user_obj = new UsersLogic();
            $res = $user_obj->addUser($data);
            if($res['status'] == 1){
                $this->success('添加成功',U('User/index'));exit;
            }else{
                $this->error('添加失败,'.$res['msg'],U('User/index'));
            }
        }
        return $this->fetch();
    }

      //汇款退回
    public function hkback(){

         return $this->fetch();
    }


         // 汇款退回操作
    public function hkbackadd(){

        if (I('post.money')<=0) {
           echo "<script>alert('金额需大于0！！');parent.window.location.reload();</script>";die;
        }
        if (empty(I('post.realname'))) {
           echo "<script>alert('请填写账户开户名！！');parent.window.location.reload();</script>";die;
        }
        if (empty(I('post.bank_card'))) {
           echo "<script>alert('请填写账户号码！！');parent.window.location.reload();</script>";die;
        }
        if (empty(I('post.beizhu'))) {
           echo "<script>alert('请填写备注信息！！');parent.window.location.reload();</script>";die;
        }
            
               //退回记录
                $data['money'] = -I('post.money'); // 放款金额
                $data['type'] = "汇款退回"; // 放款类型
                $data['bank_name'] = trim(I('post.bank_name')); // 机构类型
                $data['realname'] = trim(I('post.realname')); // 收款名
                $data['bank_card'] = trim(I('post.bank_card')); // 卡号账号
                $data['user_id'] = trim(I('post.user_id')); // 开户信息
                $data['remark'] = trim(I('post.beizhu')); // 备注
                $data['status'] = 2; // 状态
                $data['create_time'] = time(); // 退回时间
                $data['check_time'] = time(); // 退回时间
                $data['pay_time'] = time(); // 退回时间
                
                $row = M('withdrawals')->add($data);
           
            if(!$row){
                echo "<script>alert('操作失败');parent.window.location.reload();</script>";die;
            }
            echo "<script>alert('操作成功');parent.window.location.reload();</script>";
           
    }


    public function blacklist(){
        return $this->fetch();
    }
    public function ajaxblacklist(){
         // 搜索条件   
        $condition = array();
        // $condition= ['add_time'=>['between',"$this->begin,$this->end"]];
        $account = I('account');

        $account ? $condition['tel|qq|wangwang'] = ['like',"%$account%"] : false;

        $sort_order = I('order_by').' '.I('sort');
               
        $count = M('blacklists')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        $userList = M('blacklists')->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();

        // print_r(M('blacklists')->getlastsql());
        // die;
                                   
        $show = $Page->show();
        $this->assign('userList',$userList);
        $this->assign('level',M('user_level')->getField('level_id,level_name'));
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    public function add_blacklist(){
        if(IS_POST){
            $data = I('post.');
            $user_obj = new UsersLogic();
            $res = $user_obj->addblacklist($data);
            if($res['status'] == 1){
                $this->success('添加成功',U('User/blacklist'));exit;
            }else{
                $this->error('添加失败,'.$res['msg'],U('User/blacklist'));
            }
        }
        return $this->fetch();
    }
    public function del_blacklist(){
        if(IS_GET){
            $data = I('get.');
            $condition['id']=$data['id'];
            $res = M('blacklists')->where($condition)->delete();

            if($res){
                $this->success('删除成功',U('User/blacklist'));exit;
            }else{
                $this->error('删除失败,',U('User/blacklist'));
            }
        }
    }

    
   
    
    public function export_user(){
    	$strTable ='<table width="500" border="1">';
    	$strTable .= '<tr>';
    	$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">会员ID</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="100">会员昵称</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">会员等级</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">手机号</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">邮箱</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">注册时间</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">最后登陆</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">余额</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">积分</td>';
    	$strTable .= '<td style="text-align:center;font-size:12px;" width="*">累计消费</td>';
    	$strTable .= '</tr>';
        $user_ids =I('user_ids');
        if($user_ids){
           $condition['user_id']=['in',$user_ids];
        }else{
            $mobile =I('mobile');
            $email =I('email');
            $mobile ? $condition['mobile'] = $mobile : false;
            $email ? $condition['email'] = $email : false;
        };
    	$count = DB::name('users')->where($condition)->count();
    	$p = ceil($count/5000);
    	for($i=0;$i<$p;$i++){
    		$start = $i*5000;
    		$end = ($i+1)*5000;
    		$userList = M('users')->where($condition)->order('user_id')->limit($start.','.$end)->select();
    		if(is_array($userList)){
    			foreach($userList as $k=>$val){
    				$strTable .= '<tr>';
    				$strTable .= '<td style="text-align:center;font-size:12px;">'.$val['user_id'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nickname'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['level'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['mobile'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['email'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['reg_time']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i',$val['last_login']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_money'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pay_points'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_amount'].' </td>';
    				$strTable .= '</tr>';
    			}
    			unset($userList);
    		}
    	}
    	$strTable .='</table>';
    	downloadExcel($strTable,'users_'.$i);
    	exit();
    }


    /**
     * 删除会员
     */
    public function delete(){
        $uid = I('get.id');
        
        // $row = M('users')->where(array('user_id'=>$uid))->delete();
        if($row){
            $this->success('成功删除会员');
        }else{
            $this->error('操作失败');
        }
    }
    /**
     * 删除会员
     */
    public function ajax_delete(){
        $uid = I('id');
        if($uid){
            // $row = M('users')->where(array('user_id'=>$uid))->delete();
            if($row !== false){
                $this->ajaxReturn(array('status' => 1, 'msg' => '删除成功', 'data' => ''));
            }else{
                $this->ajaxReturn(array('status' => 0, 'msg' => '删除失败', 'data' => ''));
            }
        }else{
            $this->ajaxReturn(array('status' => 0, 'msg' => '参数错误', 'data' => ''));
        }
    }

    /**
     * 账户资金记录
     */
    public function account_log(){
        $user_id = I('get.id');
        //获取类型
        $type = I('get.type');
        //获取记录总数
        $count = M('account_log')->where(array('user_id'=>$user_id))->count();
        $page = new Page($count);
        $lists  = M('account_log')->where(array('user_id'=>$user_id))->order('change_time desc')->limit($page->firstRow.','.$page->listRows)->select();
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        
        $this->assign('roleid',$roleid);
        $this->assign('user_id',$user_id);
        $this->assign('page',$page->show());
        $this->assign('lists',$lists);
        return $this->fetch();
    }

    /**
     * 账户资金调节
     */
    public function account_edit(){
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        if ($roleid>2) {
            $this->error('非财务人员不得擅自调节资金!');
        }

        $user_id = I('user_id');
        if(!$user_id > 0) $this->ajaxReturn(['status'=>0,'msg'=>"参数有误"]);
        $user = M('users')->field('user_id,user_money,frozen_money,pay_points,is_lock')->where('user_id',$user_id)->find();
        if(IS_POST){
            $desc = I('post.desc');
            if(!$desc)
                $this->ajaxReturn(['status'=>0,'msg'=>"请填写操作说明"]);
            //加减用户资金
            $m_op_type = I('post.money_act_type');
            $user_money = I('post.user_money/f');
            $user_money =  $m_op_type ? $user_money : 0-$user_money;
            if (($user['user_money']+$user_money)<0){
                $this->ajaxReturn(['status'=>0,'msg'=>"用户剩余资金不足！！"]);
            }
            //加减用户积分
            $p_op_type = I('post.point_act_type');
            $pay_points = I('post.pay_points/d');
            $pay_points =  $p_op_type ? $pay_points : 0-$pay_points;
            if(($pay_points+$user['pay_points'])<0 ){
                $this->ajaxReturn(['status'=>0,'msg'=>'用户剩余积分不足！！']);
            }
            //加减冻结资金
            $f_op_type = I('post.frozen_act_type');
            $revision_frozen_money = I('post.frozen_money/f');
            if( $revision_frozen_money != 0){    //有加减冻结资金的时候
                $frozen_money =  $f_op_type ? $revision_frozen_money : 0-$revision_frozen_money;
                $frozen_money = $user['frozen_money']+$frozen_money;    //计算用户被冻结的资金
                if($f_op_type==1 && $revision_frozen_money > $user['user_money'])
                {
                    $this->ajaxReturn(['status'=>0,'msg'=>"用户剩余资金不足！！"]);
                }
                if($f_op_type==0 && $revision_frozen_money > $user['frozen_money'])
                {
                    $this->ajaxReturn(['status'=>0,'msg'=>"冻结的资金不足！！"]);
                }
                $user_money = $f_op_type ? 0-$revision_frozen_money : $revision_frozen_money ;    //计算用户剩余资金
                M('users')->where('user_id',$user_id)->update(['frozen_money' => $frozen_money]);
            }
            $admin_info = getAdminInfo(session('admin_id'));
            $desc=$desc."-手动调节-".$admin_info['user_name'];
            if(accountLog($user_id,$user_money,$pay_points,$desc,0))
            {
                $this->ajaxReturn(['status'=>1,'msg'=>"操作成功",'url'=>U("Admin/User/account_log",array('id'=>$user_id))]);
            }else{
                $this->ajaxReturn(['status'=>-1,'msg'=>"操作失败"]);
            }
            exit;
        }
        $this->assign('user_id',$user_id);
        $this->assign('user',$user);
        return $this->fetch();
    }
    //后台充值
       public  function recharge_cz(){
        $user_id =I('id');
        $user=M('users')->where('user_id',$user_id)->find();
        $this->assign('user',$user);
        if(IS_POST){
           
            $data['user_id'] =$user_id;
            $data['nickname'] = $user['nickname'];
            if (I('post.account')) {
                $data['account'] = I('post.account');
            }else{
                $this->error('请填写充值金额');
            }
            
            if (I('post.ddlastnum')) {
                $data['ddlastnum'] = I('post.ddlastnum');
            }else{
                $this->error('请填写订单/银行卡后六位');
            }
            
            
            $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
            $data['ctime'] = time();
            if (I('post.cz_type')=='支付宝') {
                 $data['pay_code'] = 'alipay';
                 $data['pay_name'] = '后台充值-支付宝';
            }else{
                 $data['pay_code'] = 'weixin';
                 $data['pay_name'] = '后台充值-银行卡';
            }
           

            $order_id = M('recharge')->add($data);
            if($order_id){
                $this->success('添加成功',U('User/index'));exit;
            }else{
                $this->error('提交失败,参数有误!');
            }
        }
        
        return $this->fetch();
   } 
    
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
    		$map['nickname|ddlastnum'] = array('like',"%$nickname%");
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

        if (session('admin_id')!=1 && session('admin_id')!=24) {
            $admin_info = getAdminInfo(session('admin_id'));
            $map['shenhe_name']=array('in',array(0,$admin_info['user_name']));
        }

    	$count = M('recharge')->where($map)->count();
   
        $where = $map;
        $where['pay_status'] = 1;
        $where['is_chakan'] = 1;
        $where['is_delete'] = 0;
        $account = M('recharge')->where($where)->sum('account');
        // print_r(M('recharge')->getlastsql());die;
    	$page = new Page($count);
    	$lists  = M('recharge')->where($map)->order('ctime desc')->limit($page->firstRow.','.$page->listRows)->select();

    	$this->assign('page',$page->show());
        $this->assign('account',$account);
        $this->assign('pager',$page);
    	$this->assign('lists',$lists);

        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $detail = M('admin_role')->where("role_id",$roleid)->find();
        $act_list = explode(',', $detail['act_list']);
        $this->assign('act_list',$act_list);

         $this->assign('admin_id',session('admin_id'));

        $this->assign('roleid', $roleid);
    	return $this->fetch();
    }

    //充值审核
    public function rechargedetail(){

        $oid = I('get.id');

        $recharge = D('recharge')->where(array('order_id'=>$oid))->find();
        if(!$recharge){
            exit($this->error('订单不存在'));
        }

                    if(IS_POST){
                            //  会员信息编辑
                            $account = I('post.account');
                            $pay_status = I('post.pay_status');
                            $pay_name = I('post.pay_name');

                                //验证重复审核
                                $rec_where = array(
                                'order_id' => array('eq', $oid),
                                'pay_status' => array('gt', 0)
                                 );
                                $is_end = D('recharge')->where($rec_where)->find();
                                
                                if ($is_end) {
                                     echo "<script>alert('该记录已审核，请勿重复操作！')</script>";
                                     // print_r($is_end);die;
                                }else{
                                            //查询是否已经短信提示过
                                            $data = M('sms_log')->where(array('mobile' => $tel, 'session_id' => $recharge['order_sn'], 'status' => 1, 'scene' => 5))->order('id DESC')->find();
                                            if ($data) {
                                                echo "<script>alert('请勿重复操作！')</script>";
                                            }else{

                                                if ($pay_status == 1) {
                                                    $alog = M('account_log')->where(array('user_id'=>$recharge['user_id'],'order_sn'=>$recharge['order_sn']))->find();
                                                    if($alog){
                                                        exit($this->error('请勿重复提交!'));
                                                    }
                                                    // 充值成功，添加充值金额
                                                    // 记录充值记录到资金明细
                                                    $usermoney=accountLog($recharge['user_id'],$account,0,$pay_name.'充值成功',0);

                                                    if ($usermoney) {
                                                        //修改充值订单状态
                                                        $admin_info = getAdminInfo(session('admin_id'));
                                                        $savedata=$_POST;
                                                        $savedata['shenhe_name'] = $admin_info['user_name'];
                                                        $row = M('recharge')->where(array('order_id'=>$oid))->save($savedata);
                                                       //充值成功，短信提示
                                                        $tel=M('users')->where(array('user_id'=>$recharge['user_id']))->getField('mobile');
                                                        $content='【蜂车车】'.$recharge['nickname'].'您好，您充值的'.$account.'元，已成功到账！请及时查看';
                                                        $res=send_msg($tel,$content);
                                                        //短信发送记录
                                                        if ($res['status']==1) {
                                                            $smsadd['mobile']=$tel;
                                                            $smsadd['status']=1;
                                                            $smsadd['msg']=$content;
                                                            $smsadd['scene']=5;
                                                            $smsadd['add_time']=time();
                                                            $smsadd['session_id']=$recharge['order_sn'];
                                                            $resms=M('sms_log')->add($smsadd);
                                                            if ($resms) {
                                                               echo "<script>alert('短信发送成功！')</script>";
                                                            }
                                                           
                                                        }else{
                                                            echo "<script>alert('短信发送失败！')</script>"; 
                                                        }
                                                    }
                                                }
                                                if ($pay_status == 2) {
                                                    //修改充值订单状态
                                                    $admin_info = getAdminInfo(session('admin_id'));
                                                    $savedata=$_POST;
                                                    $savedata['shenhe_name'] = $admin_info['user_name'];
                                                    $row = M('recharge')->where(array('order_id'=>$oid))->save($savedata);
                                                }

                                            }
                                           
                                            if($row){
                                                exit($this->success('修改成功'));
                                            }else{
                                                exit($this->error('未作内容修改或修改失败'));
                                            }
                                            
                                    }
                        
                    }
      
        $this->assign('lists',$recharge);
        return $this->fetch();
    }
    
  

  
    /**
     * 搜索用户名
     */
    public function search_user()
    {
        $search_key = trim(I('search_key'));
        if ($search_key == '') $this->ajaxReturn(['status'=>-1,'msg'=>'请按要求输入！！']);
        $list = M('users')->where(['nickname'=>['like',"%$search_key%"]])->select();
        if ($list){
            $this->ajaxReturn(['status'=>1,'msg'=>'获取成功','result'=>$list]);
        }
        $this->ajaxReturn(['status'=>-1,'msg'=>'未查询到相应数据！！']);
    }
 

  
    /**
     *  转账汇款记录
     */
    public function remittance(){
        $status = I('status',1);
        $nickname = trim(I('nickname'));
        $beizhu = trim(I('beizhu'));
        $realname = trim(I('realname'));
        
        $type = I('type');
        if (I('type')!='') {
            $where['type'] = $type;
        }
        
        $bank_card = I('bank_card');
        $where['status'] = $status;
        
        $userid =  M('users')->where("nickname like '%$nickname%'")->getField('user_id');
        $nickname && $where['user_id'] = $userid;
        $bank_card && $where['bank_card'] = array('like','%'.$bank_card.'%');
        $beizhu && $where['remark'] = array('like','%'.$beizhu.'%');
        $realname && $where['realname'] = array('like','%'.$realname.'%');

        $create_time = urldecode(I('create_time'));
     
        $create_time = $create_time  ? $create_time  : date('Y-m-d H:i:s',strtotime('-60 day')).','.date('Y-m-d H:i:s',strtotime('+1 day'));
        $create_time3 = explode(',',$create_time);
        $this->assign('start_time',$create_time3[0]);
        $this->assign('end_time',$create_time3[1]);
        if($status == 2){
            $time_name = 'pay_time';
            $export_time_name = '转账时间';
            $export_status = '已转账';
           
        }else{
            $time_name = 'check_time';
            $export_time_name = '审核时间';
            $export_status = '待转账';
            
        }
        $where[$time_name] =  array(array('gt', strtotime($create_time3[0])), array('lt', strtotime($create_time3[1])));
        $withdrawalsModel = new Withdrawals();
        $count = $withdrawalsModel->where($where)->count();
        $Page = new page($count,C('PAGESIZE'));
        $list = $withdrawalsModel->where($where)->limit($Page->firstRow,$Page->listRows)->order("id desc")->select();


        $shiji_jine = $withdrawalsModel->where($where)->sum('money');
        if (I('export') == 1) {
            # code...导出记录
            $selected = I('selected');
            if (!empty($selected)) {
                $selected_arr = explode(',', $selected);
                $where['id'] = array('in',$selected_arr);
            }
            $list = $withdrawalsModel->where($where)->order("id desc")->select();

            $strTable ='<table width="500" border="1">';
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">用户昵称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="100">银行机构名称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">账户号码</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">账户开户名</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请金额</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">状态</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">'.$export_time_name.'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
            $strTable .= '</tr>';
            if(is_array($list)){
                foreach($list as $k=>$val){
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['users']['nickname'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].' </td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_card'].'</td>';
                    $strTable .= '<td style="vnd.ms-excel.numberformat:@">'.$val['realname'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['money'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$export_status.'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i:s',$val[$time_name]).'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['remark'].'</td>';
                    $strTable .= '</tr>';
                }
            }
            $strTable .='</table>';
            unset($remittanceList);
            downloadExcel($strTable,'remittance');
            exit();
        }

            foreach ($list as $key => $value) {
                $user_id=$value['user_id'];
                $value['user'] =  M('users')->where("user_id=$user_id")->find();
                $lists[] = $value;
            }

        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('shiji_jine',$shiji_jine);
        $this->assign('admin_id',session('admin_id'));
        $this->assign('status',$status);
        $this->assign('Page',$Page);
        $this->assign('list',$lists);
        return $this->fetch();
    }

    /**
     * 提现申请记录
     */
    public function withdrawals()
    {
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $detail = M('admin_role')->where("role_id",$roleid)->find();
        $act_list = explode(',', $detail['act_list']);
        $this->assign('act_list',$act_list);
    	$this->get_withdrawals_list();
        $this->assign('withdraw_status',C('WITHDRAW_STATUS'));  
        return $this->fetch();
    }
    
    public function get_withdrawals_list($status=''){
        $id = I('selected/a');
    	$user_id = I('user_id/d');
    	$realname = I('realname');
    	$bank_card = I('bank_card');
        $create_time = urldecode(I('create_time'));
        $create_time = $create_time  ? $create_time  : date('Y-m-d H:i:s',strtotime('-1 year')).','.date('Y-m-d H:i:s',strtotime('+1 day'));
        $create_time3 = explode(',',$create_time);
        $this->assign('start_time',$create_time3[0]);
        $this->assign('end_time',$create_time3[1]);
        $where['w.create_time'] =  array(array('gt', strtotime($create_time3[0])), array('lt', strtotime($create_time3[1])));

     	$status = empty($status) ? I('status') : $status;
        if($status !== ''){
            $where['w.status'] = $status;
        }else{
            $where['w.status'] = ['lt',2];
        }

        if($id){
            $where['w.id'] = ['in',$id];
        }

        if (session('admin_id')!=1 && session('admin_id')!=24) {
            $admin_info = getAdminInfo(session('admin_id'));
            $where['w.shname'] = ['in',array('0',$admin_info['user_name'])];
        }

    	$user_id && $where['u.user_id'] = $user_id;
    	$realname && $where['w.realname'] = array('like','%'.$realname.'%');
    	$bank_card && $where['w.bank_card'] = array('like','%'.$bank_card.'%');
    	$export = I('export');
    	if($export == 1){
    		$strTable ='<table width="500" border="1">';
    		$strTable .= '<tr>';
    		$strTable .= '<td style="text-align:center;font-size:12px;width:120px;">申请人</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="100">提现金额</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行名称</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">银行账号</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">开户人姓名</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">申请时间</td>';
    		$strTable .= '<td style="text-align:center;font-size:12px;" width="*">提现备注</td>';
    		$strTable .= '</tr>';
    		$remittanceList = Db::name('withdrawals')->alias('w')->field('w.*,u.nickname')->join('__USERS__ u', 'u.user_id = w.user_id', 'INNER')->where($where)->order("w.id desc")->select();
    		if(is_array($remittanceList)){
    			foreach($remittanceList as $k=>$val){
    				$strTable .= '<tr>';
    				$strTable .= '<td style="text-align:center;font-size:12px;">'.$val['nickname'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['money'].' </td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].'</td>';
    				$strTable .= '<td style="vnd.ms-excel.numberformat:@">'.$val['bank_card'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['realname'].'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i:s',$val['create_time']).'</td>';
    				$strTable .= '<td style="text-align:left;font-size:12px;">'.$val['remark'].'</td>';
    				$strTable .= '</tr>';
    			}
    		}
    		$strTable .='</table>';
    		unset($remittanceList);
    		downloadExcel($strTable,'remittance');
    		exit();
    	}
    	$count = Db::name('withdrawals')->alias('w')->join('__USERS__ u', 'u.user_id = w.user_id', 'INNER')->where($where)->count();
    	$Page  = new Page($count,20);
    	$list = Db::name('withdrawals')->alias('w')->field('w.*,u.nickname')->join('__USERS__ u', 'u.user_id = w.user_id', 'INNER')->where($where)->order("w.id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
    	//$this->assign('create_time',$create_time2);
    	$show  = $Page->show();
    	$this->assign('show',$show);
    	$this->assign('list',$list);
    	$this->assign('pager',$Page);
    	C('TOKEN_ON',false);
    }
    
    /**
     * 删除申请记录
     */
    public function delWithdrawals()
    {
        $id = I('del_id/d');
        $res = Db::name("withdrawals")->where(['id'=>$id])->delete();
        if($res !== false){
            $return_arr = ['status' => 1,'msg' => '操作成功','data'  =>'',];
        }else{
            $return_arr = ['status' => -1,'msg' => '删除失败','data'  =>'',];
        }
        $this->ajaxReturn($return_arr);
    }

    /**
     * 修改编辑 申请提现
     */
    public  function editWithdrawals(){
       $id = I('id');
       $withdrawals = Db::name("withdrawals")->find($id);
       $user = M('users')->where(['user_id' => $withdrawals['user_id']])->find();
      
       if($user['nickname'])        
           $withdrawals['user_name'] = $user['nickname'];
       elseif($user['email'])        
           $withdrawals['user_name'] = $user['email'];
       elseif($user['mobile'])        
           $withdrawals['user_name'] = $user['mobile'];
        $status = $withdrawals['status'];
        $withdrawals['status_code'] = C('WITHDRAW_STATUS')["$status"];
       $this->assign('user',$user);
       $this->assign('data',$withdrawals);

        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $this->assign('roleid',$roleid);
       return $this->fetch();
    }  

    /**
     *  处理会员提现申请
     */
    public function withdrawals_update(){
    	$id_arr = I('id/a');
        $data['status']=$status = I('status');
        $data['type']=$type = I('type');
    	$data['remark'] = I('remark');

        $admin_info = getAdminInfo(session('admin_id'));
        $data['shname'] = $admin_info['user_name'];
        $ids = implode(',',$id_arr);
        if ($status == 1) {
            $data['check_time'] = time();
            if (I('user_id')) {
                $user = M('users')->where(array('user_id'=>I('user_id')))->find();
                   if($user['user_money'] < I('money'))
                    {
                        $this->ajaxReturn(array('status'=>0,'msg'=>"余额不足，不能提现"),'JSON');
                    }
                $rdata = array('type'=>1,'money'=>I('money'),'log_type_id'=>$ids,'user_id'=>I('user_id'));
                expenseLog($rdata);//支出记录日志
                accountLog(I('user_id'), (I('money') * -1), 0,"提现申请审核通过");//申请审核成功，默认视为已通过线下转方式处理了该笔提现申请
            }
            
        }
        if($status != 1) $data['refuse_time'] = time();
        
        $r = Db::name('withdrawals')->whereIn('id',$ids)->update($data);
    	if($r !== false){
            
    		$this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
    	}else{
    		$this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
    	}  	
    }

     /**
     *  编辑转款时间
     */
    public function editpaytime(){
        $ids = I('id/a');
        $data['pay_time']=strtotime(I('pay_time'));
        
        $r = Db::name('withdrawals')->whereIn('id',$ids)->update($data);
        if($r !== false){
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        }   
    }

    /**
     *  驳回处理会员提现申请
     */
    public function bohui_tixian(){
        $ids = I('id/a');
        $data['status']=0;
        $data['bohui']=I('bohui');
        
        $r = Db::name('withdrawals')->whereIn('id',$ids)->update($data);
        if($r !== false){
            accountLog(I('user_id'), I('money'), 0, I('bohui'));//驳回提现申请，返还已扣除余额
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
            // $this->success("操作成功!",U('remittance'),3);
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
            // $this->error("操作失败!",U('remittance'),3);
        }   
    }
    // 用户申请提现
    public function transfer(){
    	$id = I('selected/a');
    	if(empty($id))$this->error('请至少选择一条记录');
  
    	if(is_array($id)){
    		$withdrawals = M('withdrawals')->where('id in ('.implode(',', $id).')')->select();
    	}else{
    		$withdrawals = M('withdrawals')->where(array('id'=>$id))->select();
    	}
    
    	foreach($withdrawals as $val){
    		$user = M('users')->where(array('user_id'=>$val['user_id']))->find();
    		$r = M('withdrawals')->where(array('id'=>$val['id']))->save(array('status'=>2,'pay_time'=>time()));
    	}
    	
    	$this->success("操作成功!",U('remittance'),3);
    }


}