<?php
/**
个人中心
 */
namespace app\home\controller; 
use app\common\logic\MessageLogic;
use app\common\logic\OrderLogic;
use app\common\logic\UsersLogic;
use app\common\logic\GoodsLogic;
use app\common\logic\CartLogic;
use app\common\model\GoodsCollect;
use app\common\model\GoodsVisit;
use app\common\model\UserAddress;
use app\common\util\TpshopException;
use think\Loader;
use think\Page;
use think\Session;
use think\Verify;
use think\Db;
class User extends Base{

	public $user_id = 0;
	public $user = array();
	
    public function _initialize() {
        parent::_initialize();
        if(session('?user'))
        {
            $session_user = session('user');
            $user = Db::name('users')->where("user_id", $session_user['user_id'])->find();
            empty($user) && $user = [];
            session('user',$user);  //覆盖session 中的 user               
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
        	$this->assign('user_id',$this->user_id);
            
        }else{
            //没有登陆都可以访问的函数
        	$nologin = array(
        			'login','pop_login','do_login','logout','verify','finished','verifyHandle','reg','send_sms_reg_code','forget_pwd','check_captcha','check_username',
        	);
        	if(!in_array(ACTION_NAME,$nologin)){
                setcookie('uname','',time()-3600,'/');
                setcookie('cn','',time()-3600,'/');
                setcookie('user_id','',time()-3600,'/');
                setcookie('PHPSESSID','',time()-3600,'/');
                session_unset();
                session_destroy();
                $this->redirect('Home/User/login');
        		exit;
        	}
        }
        //用户中心面包屑导航
        $navigate_user = navigate_user();
        $this->assign('navigate_user',$navigate_user);        
    }

    /*
     * 用户中心首页
     */
    public function index(){
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $user = $user['result'];
        $user['count_goods_collect'] = M('goods_collect')->where('user_id', $this->user_id)->count();
        $user['count_goods_visit'] = M('goods_visit')->where('user_id', $this->user_id)->count();
       
        $this->assign('user',$user);
        return $this->fetch();
    }

    public function chushou(){

        return $this->fetch();
    }

        /**
     *  求购信息提交
     */
    public function qiugou(){
       
        return $this->fetch();
    }

    // 求购出售提交页面
    public function save_qgcs(){
        $data = I('post.');

        if ($data) {
            $logic = new UsersLogic();
            $user = $logic->get_info($this->user_id);
            $userinfo = $user['result'];
            $data['tel']=$userinfo['mobile'];
            $data['qq']=$userinfo['qq'];
            $data['userid']=$userinfo['user_id'];
            $data['username']=$userinfo['nickname'];
            $data['on_time'] = time();
            
            $qiugou = M('qiugou')->add($data);
                if ($qiugou) {
                    $this->success('提交成功，我们将尽快给您回复，请保持电话畅通！');
                }else{
                    $this->error('系统繁忙，请联系客服');
                }
        }
    }


    public function userHeader(){
        return $this->fetch();
    }


     /*
     * 订单列表
     */
    public function insellorder(){
        
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $where = " sellername='".$user['result']['nickname']."'";//会员本人身份

       // 搜索订单 根据店铺名称 或者 订单编号
       $search_key = trim(I('search_key'));       
       if($search_key)
       {
          $where .= " and (order_sn like '%$search_key%'";
       }
       
        $order_str = "order_id DESC";

        if (I('get.type')=="WAITSEND") {
            $where .= ' and order_status = 1';//在售 已预定 已审核 已支付
        }elseif (I('get.type')=="WAITRECEIVE") {
            $where .= ' and (order_status = 2 or order_status = 3)';//交接中 在售 已审核 已上架
        }elseif (I('get.type')=="FINISH") {
            $where .= ' and order_status = 4';//已完成 已审核 已上架
        }

        $count = Db::name('order_list')->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $order_list = Db::name('order_list')->order($order_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        //获取订单商品
        if (!empty($order_list)) {
            foreach($order_list as $k=>$v)
            {
                $order_list[$k]['goods']=M('goods')->where(['goods_id' => $v['goods_id']])->find();
                $order_list[$k]['cat_name'] = M("GoodsCategory")->where("id",$v['cat_id'])->cache(true)->getField('name');
                $order_list[$k]['pingtai_name'] = Db::name('goods_attribute')->where("attr_id", $v['pingtai_id'])->cache(true)->getField('attr_name');
            }
        }
        
        $this->assign('page',$show);
        $this->assign('lists',$order_list);
        return $this->fetch('insellshop');
    }

   /*
     * 订单列表
     */
    public function insellshop(){
        
        $logic = new UsersLogic();
        $user = $logic->get_info($this->user_id);
        $mgoods='goods';
        $where['sellername'] = $user['result']['nickname'];//会员本人身份

       if (I('get.type')=="SHENHE") {
            $where['is_on_sale'] = 0;//未审核 
        }elseif (I('get.type')=="ZAISHOU") {
            $where['is_on_sale'] = 1;//已上架
        }

        // 搜索订单 根据名称 或者 订单编号
        $search_key = trim(I('search_key'));       
        if($search_key)
        {
           $where['goods_sn'] =["like",'%$search_key%'];
        }

        $count = Db::name($mgoods)->where($where)->count();
        $Page = new Page($count,10);
        $show = $Page->show();
        $goods_str = "goods_id DESC";

        $order_list = Db::name($mgoods)->order($goods_str)->where($where)->limit($Page->firstRow.','.$Page->listRows)->select();

        foreach($order_list as $k=>$v)
        {
            $order_list[$k]['cat_name'] = M("GoodsCategory")->where("id",$v['cat_id'])->cache(true)->getField('name');
            $order_list[$k]['pingtai_name'] = Db::name('goods_attribute')->where("attr_id", $v['pingtai_id'])->cache(true)->getField('attr_name');
        }

        $this->assign('page',$show);
        $this->assign('lists',$order_list);
        return $this->fetch();
    }
    


    public function logout(){
    	setcookie('uname','',time()-3600,'/');
    	setcookie('cn','',time()-3600,'/');
    	setcookie('user_id','',time()-3600,'/');
        setcookie('PHPSESSID','',time()-3600,'/');
        session_unset();
        session_destroy();
        //$this->success("退出成功",U('Home/Index/index'));
        $this->redirect('/');
        exit;
    }

    /*
     * 账户资金
     */
    public function account(){
        $user = session('user');
        $type = I('type');
        $order_sn = I('order_sn');
        $logic = new UsersLogic();
        $data = $logic->get_account_log($this->user_id,$type,$order_sn);
        $account_log = $data['result'];
        $this->assign('user',$user);
        $this->assign('account_log',$account_log);
        $this->assign('page',$data['show']);
        $this->assign('active','account');
        return $this->fetch();
    }
   
    /**
     *  登录
     */
    public function login(){
        // print_r(session_id());
        if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }
        $redirect_url = Session::get('redirect_url');
        $referurl = $redirect_url ? $redirect_url : U("Home/User/index");
        $this->assign('referurl',$referurl);
        return $this->fetch();
    }
    
    public function do_login(){
        $usernames = I('post.username');
        $username=preg_replace('# #','',$usernames);
        $passwords = I('post.password');
        $password=preg_replace('# #','',$passwords);
        $verify_code = I('post.verify_code');
    	$code = I('post.code');
        
        $logic = new UsersLogic();
        //短信登陆
        if ($code) {
            //验证短信验证码
            $check_code = $logic->check_validate_code($code, $username, 'phone', session_id(), 2);
            if($check_code['status'] != 1){
                $this->ajaxReturn($check_code);
            }
            $user = Db::name('users')->where("mobile", $username)->find();
            if (!$user) {
                $res = array('status' => -1, 'msg' => '账号不存在!');
            } elseif ($user['is_lock'] == 1) {
                $res = array('status' => -3, 'msg' => '账号异常已被锁定！！！');
            } else {
                $res = array('status' => 1, 'msg' => '登陆成功', 'result' => $user);
            }
        }else{
            $verify = new Verify();
            if (!$verify->check($verify_code,'user_login'))
            {
                 $res = array('status'=>0,'msg'=>'验证码错误');
                 exit(json_encode($res));
            }
            $res = $logic->login($username,$password);
        }

    	if($res['status'] == 1){
    		$res['url'] =  htmlspecialchars_decode(I('post.referurl'));
    		session('user',$res['result']);
    		setcookie('user_id',$res['result']['user_id'],null,'/');
    		$nickname = empty($res['result']['nickname']) ? $username : $res['result']['nickname'];
            setcookie('uname',urlencode($nickname),null,'/');
            setcookie('cn',0,time()-3600,'/');
    	}
        return json_encode($res);
    }
    /**
     *  注册
     */
    public function reg(){
    	if($this->user_id > 0){
            $this->redirect('Home/User/index');
        }
        if(IS_POST){
            $logic = new UsersLogic();
           
            $usernames = I('post.username','');
            $username=preg_replace('# #','',$usernames);
            $mobiles = I('post.mobile','');
            $mobile=preg_replace('# #','',$mobiles);
            $passwords = I('post.password','');
            $password=preg_replace('# #','',$passwords);
            $password2s = I('post.password2','');
            $password2=preg_replace('# #','',$password2s);
            $codes = I('post.code','');
            $code=preg_replace('# #','',$codes);
            $scenes = I('post.scene', 1);
            $scene=preg_replace('# #','',$scenes);
            $session_id = session_id();

            if (empty($username)) {
                $this->ajaxReturn(['status' => 0, 'msg' => '昵称必填，并作为您唯一代号不可更改', 'result' => '']);
            }

        // 获取客户端IP地址
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos){
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);
            }elseif(isset($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
            }elseif(isset($_SERVER['REMOTE_ADDR'])) {
                    $ip = $_SERVER['REMOTE_ADDR'];
            }
            // IP地址合法验证
            $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        // 获取客户端IP地址
            // 访问记录
            $time=time();
            $jbegintime=mktime(0,0,0,date('m'),date('d'),date('Y'));//今天开始时间
            $memwhere['ip']=$ip;
            $memwhere['start_time']=$jbegintime;
            $is_fangwen=M('member')->where($memwhere)->find();
          
            if ($is_fangwen) {
                $data['ip'] = $ip;
                $data['end_time'] = $time;
                $data['count'] = $is_fangwen['count']+1;
                $data['beizhu'] = '提交注册/'.$is_fangwen['beizhu'];
                $member = M('member')->where($memwhere)->save($data);
            }else{
                $data['ip'] = $ip;
                $data['beizhu'] = '提交注册';
                $data['start_time'] = $jbegintime;
                $data['end_time'] = $time;
                $data['count'] = 1;
                $member = M('member')->add($data);
            }
            // 访问记录
            
        //验证非法字符
        $usernames = strtoupper($username);
        $mobiles = strtoupper($mobile);
        $ArrFiltrate=array('SELECT','INSERT','UPDATE','DELETE','ALERT','SCRIPT','SAVE','ADD',';','<','JS','HTTP','EXE','__','||','&','OR','+','-','=','WAITFOR ','DELAY','$','/','(','WINDOWS','WRITE','*','%'); 
        foreach ($ArrFiltrate as $key=>$value){ 

            if (strstr($usernames,$value)){ 
            $this->ajaxReturn(['status' => 0, 'msg' => '内容含非法字符，请谨慎填写', 'result' => '']);
            }
            if (strstr($mobiles,$value)){ 
            $this->ajaxReturn(['status' => 0, 'msg' => '内容含非法字符，请谨慎填写', 'result' => '']);
            }
        }
        //验证非法字符

            //验证短信验证码
            $check_code = $logic->check_validate_code($code, $mobile, 'phone', $session_id, $scene);
            if($check_code['status'] != 1){
                $this->ajaxReturn($check_code);
            }

            $count = Db::name('users')->where("mobile", $mobile)->whereOr('nickname', $username)->count();
            if ($count>=1) {
                $this->ajaxReturn(['status'=>-1,'msg'=>'会员昵称或者手机号重复']);
            }
          
            $data = $logic->reg($username,$mobile,$password,$password2);
            if($data['status'] != 1){
                $this->ajaxReturn($data);
            }
            session('user',$data['result']);
    		setcookie('user_id',$data['result']['user_id'],null,'/');
            $nickname = empty($data['result']['nickname']) ? $username : $data['result']['nickname'];
            setcookie('uname',urlencode($nickname),null,'/');
           
            $this->ajaxReturn($data);
            exit;
        }
        $this->assign('regis_sms_enable',tpCache('sms.regis_sms_enable')); // 注册启用短信：
        $sms_time_out = tpCache('sms.sms_time_out')>0 ? tpCache('sms.sms_time_out') : 120;
        $this->assign('sms_time_out', $sms_time_out); // 手机短信超时时间
        return $this->fetch();
    }

    /*
     * 个人信息
     */
    public function info(){
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        if(IS_POST){
          
            I('post.qq') ? $post['qq'] = I('post.qq') : false;  //QQ号码
            I('post.head_pic') ? $post['head_pic'] = I('post.head_pic') : false; //头像地址
            I('post.sex') ? $post['sex'] = I('post.sex') : $post['sex'] = 0;  // 性别
            I('post.birthday') ? $post['birthday'] = strtotime(I('post.birthday')) : false;  // 生日
          
            if(!$userLogic->update_info($this->user_id,$post)){
                $this->error("保存失败");
            }
            setcookie('uname',urlencode($post['nickname']),null,'/');
            $this->success("操作成功");
            exit;
        }
       
        $this->assign('user',$user_info);
        $this->assign('sex',C('SEX'));
        $this->assign('active','info');
        return $this->fetch();
    }
   


    /*
     *商品收藏
     */
    public function goods_collect(){
        $userLogic = new UsersLogic();
        $data = $userLogic->get_goods_collect($this->user_id);
        $this->assign('page',$data['show']);// 赋值分页输出
        $this->assign('lists',$data['result']);
        $this->assign('active','goods_collect');
        return $this->fetch();
    }

    /*
     * 删除一个收藏商品
     */
    public function delGoodsCollect(){
        $ids = trim(I('get.ids',''),',');
        if(!$ids)$this->ajaxReturn(['status'=>-1,'msg'=>"请选择商品"]);
        $row = Db::name('goods_collect')->where(['user_id'=>$this->user_id,'collect_id'=>['in',$ids]])->delete();
        if(!$row)$this->ajaxReturn(['status'=>-1,'msg'=>'删除失败']);
        $this->ajaxReturn(['status'=>1,'msg'=>'删除成功','url'=>U('User/goods_collect')]);
    }


    /**
     * 安全设置
     */
    public function safety_settings()
    {
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];        
        $this->assign('user',$user_info);
        return $this->fetch();
    }

    /**
     * 支付密码
     * @return mixed
     */
    public function paypwd()
    {
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
       
        if ($user['mobile'] == ''){
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号有误，请联系客服操作！']);
        }

        if (IS_POST) {
            $userLogic = new UsersLogic();
            $data = I('post.');
            $data = $userLogic->paypwd($this->user_id, I('new_password'), I('confirm_password'));
            if ($data['status'] == -1){
                $this->ajaxReturn(['status'=>-1,'msg'=>$data['msg']]);
            }
                $this->ajaxReturn(['status'=>1,'msg'=>$data['msg']]);
        }
    }

    /*
     * 密码修改
     */
    public function password(){
        //检查是否第三方登录用户
        $logic = new UsersLogic();
        $data = $logic->get_info($this->user_id);
        $user = $data['result'];
        if($user['mobile'] == ''){
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号有误，请联系客服操作！']);
        }
        if(IS_POST){
            $userLogic = new UsersLogic();
            $data = $userLogic->password($this->user_id,I('post.new_password'),I('post.confirm_password')); // 获取用户信息
            if ($data['status'] == -1){
                $this->ajaxReturn(['status'=>-1,'msg'=>$data['msg']]);
            }
                $this->ajaxReturn(['status'=>1,'msg'=>$data['msg']]);
        }
    }


    public function check_username(){
    	$username = I('post.username');
    	if(!empty($username)){
    		$count = Db::name('users')->where("email", $username)->whereOr('mobile', $username)->whereOr('nickname', $username)->count();
    		exit(json_encode(intval($count)));
    	}else{
    		exit(json_encode(0));
    	}  	
    }


      
    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        $result = $verify->check(I('post.verify_code'), $id ? $id : 'user_login');
        if (!$result) {
            return false;
        }else{
            return true;
        }
    }

    /**
     * 验证码获取
     */
    public function verify()
    {
        //验证码类型
        $type = I('get.type') ? I('get.type') : 'user_login';
        $config = array(
            'fontSize' => 40,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => false,
        );
        $Verify = new Verify($config);
        $Verify->entry($type);
		exit();
    }

    /**
     * 查看个人详情
     */
    public function ajax_user_info()
    {
      
        $user_id = cookie('user_id');
        if (empty($user_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请登录后操作', 'result' => '']);
        }
        
        $userLogic = new UsersLogic();
        $user_info = $userLogic->get_info($this->user_id); // 获取用户信息
        $user_info = $user_info['result'];
        
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $user_info]);
    }


    //添加、编辑提现账号
    public function add_card(){
        $user_id=$this->user_id;
        $data=I('post.');
       
        if($data['type']==0){
            $info['cash_alipay']=$data['card'];
            $info['uname_alipay']=$data['uname_alipay'];
        }
        
        if($data['type']==2){
            $info['cash_unionpay']=$data['card'];
            $info['kh_addr']=$data['kh_addr'];
            $info['kh_uname']=$data['kh_uname'];
        }

        $info['realname']=$data['realname'];
        $info['idcard']=$data['id_card'];
        $info['user_id']=$user_id;
        $res=Db::name('users')->where('user_id='.$user_id)->find();
        if(!$res){
            $this->ajaxReturn(['status'=>-1,'msg'=>'账号错误，请重新登陆']);
        }else{
            $res2=Db::name('users')->where('user_id='.$user_id)->save($info);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>'操作成功']);
    }
    
    /**
     * 申请提现记录
     */
    public function withdrawals(){

        if (IS_POST) {
            $data = I('post.');
            $data['user_id'] = $this->user_id;
            $data['create_time'] = time();
            $cash = tpCache('cash');
            $userpaypwd=$this->user['paypwd'];

            if(encrypt($data['paypwd']) != $this->user['paypwd']){
                $this->ajaxReturn(['status'=>0, 'msg'=>'支付密码错误']);
            }
            if ($data['money'] > $this->user['user_money']) {
                $this->ajaxReturn(['status'=>0, 'msg'=>"本次提现余额不足"]);
            } 
            if ($data['money'] <= 0) {
                $this->ajaxReturn(['status'=>0, 'msg'=>'提现额度必须大于0']);
            }

            // 统计所有0，1的金额
            $status = ['in','0'];
            $total_money = Db::name('withdrawals')->where(array('user_id' => $this->user_id, 'status' => $status))->sum('money');
            $alltixian=$total_money+$data['money'];//总提现申请
            if ($alltixian > $this->user['user_money']) {
                $this->ajaxReturn(['status'=>0, 'msg'=>"您有提现申请待处理，本次提现余额不足"]);
            }

            if (M('withdrawals')->add($data)) {
                $this->ajaxReturn(['status'=>1,'msg'=>"已提交申请",'url'=>U('User/recharge',['type'=>2])]);
            } else {
                $this->ajaxReturn(['status'=>0,'msg'=>'提交失败,联系客服!']);
            }
        }

        $users=Db::name('users')->where('user_id='.$this->user_id)->find();

        $this->assign('users',$users);
        $this->assign('user_money', $this->user['user_money']);    //用户余额
        return $this->fetch();
    }


//充值
       public  function recharge(){
       if(IS_POST){
            $user = session('user');
            $data['user_id'] = $this->user_id;
            $data['nickname'] = $user['nickname'];

            $data['account'] = I('account');
            if (I('ddlastnum')) {
                $data['ddlastnum'] = I('ddlastnum');
            }else{
                $this->error('请输入支付订单后六位');
            }
            $data['pay_code'] = I('pay_code');
            $data['pay_name'] = I('pay_name');
            $data['order_sn'] = 'recharge'.get_rand_str(10,0,1);
            $data['ctime'] = time();
            $order_id = M('recharge')->add($data);
            if($order_id){

                 //充值成功，短信提示
$tel=M('users')->where(array('user_id'=>$data['user_id']))->getField('mobile');
// $content='【充值提示】充值记录信息，会员账号：'.$data[nickname].'，电话：'.$tel.'，充值单号'.$data[order_sn].'，充值金额'.$data[account].'元，订单后六位'.$data[ddlastnum].'，请及时审核！';
               
                // $res=send_msg('13258222602',$content);
             
                $smsadd['mobile']=$tel;
                $smsadd['status']=1;
                $smsadd['msg']=$content;
                $smsadd['scene']=7;
                $smsadd['add_time']=time();
                $smsadd['session_id']=$data['order_sn'];
                M('sms_log')->add($smsadd);
               
                $this->success('提交成功',U('User/index'));exit;
            }else{
                $this->error('提交失败,参数有误!');
            }
        }
     
        $Userlogic = new UsersLogic();
        $user = $Userlogic->get_info($this->user_id);
        $this->assign('user',$user['result']);
        return $this->fetch();
   } 
    
   public  function record(){

        $type = I('type');
        $Userlogic = new UsersLogic();
        if($type == 1){
            $result = $Userlogic->get_account_log($this->user_id);  //用户资金变动记录
        }else if($type == 2){
            $this->assign('status', C('WITHDRAW_STATUS'));
            $result=$Userlogic->get_withdrawals_log($this->user_id);  //提现记录
        }else{
            $this->assign('status', C('RECHARGE_STATUS'));
            $result=$Userlogic->get_recharge_log($this->user_id);  //充值记录
        }
       
        $this->assign('page', $result['show']);
        $this->assign('lists', $result['result']);
        $this->assign('allmoney', $result['sum']);
        return $this->fetch();
   } 

    
    /**
     * 删除足迹
     * @author lxl
     * @time  17-4-20
     * 拷多商家User控制器
     */
    public function del_visit_log(){

        $visit_id = I('visit_id/d' , 0);
        $row = Db::name('goods_visit')->where(['visit_id'=>$visit_id])->delete();
        if($row>0){
            $this->ajaxReturn(['status'=>1 , 'msg'=> '删除成功']);
        }else{
            $this->ajaxReturn(['status'=>-1 , 'msg'=> '删除失败']);
        }
    }

    /**
     * 我的足迹
     * @author lxl
     * @time  17-4-20
     * 拷多商家User控制器
     * */
    public function visit_log()
    {
        $cat_id = I('cat_id', 0);
        $map['user_id'] = $this->user_id;
        if ($cat_id > 0) $map['a.cat_id'] = $cat_id;
        $count_all = Db::name('goods_visit')->where(['user_id' => $this->user_id])->count();
        $count = Db::name('goods_visit a')->where($map)->count();
        $Page = new Page($count, 20);
        $visit_list = Db::name('goods_visit a')->field("a.*,g.goods_name,g.shop_price")
            ->join('__GOODS__ g', 'a.goods_id = g.goods_id', 'LEFT')
            ->where($map)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->order('a.visittime desc')
            ->select();
        $visit_log = $cates = array();
        $visit_total = 0;
        if ($visit_list) {
            $now = time();
            $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
            $weekarray = array("日", "一", "二", "三", "四", "五", "六");
            foreach ($visit_list as $k => $val) {
                if ($now - $val['visittime'] < 3600 * 24 * 7) {
                    if (date('Y-m-d') == date('Y-m-d', $val['visittime'])) {
                        $val['date'] = '今天';
                    } else {
                        if ($val['visittime'] < $endLastweek) {
                            $val['date'] = "上周" . $weekarray[date("w", $val['visittime'])];
                        } else {
                            $val['date'] = "周" . $weekarray[date("w", $val['visittime'])];
                        }
                    }
                } else {
                    $val['date'] = '更早以前';
                }
                $visit_log[$val['date']][] = $val;
            }
            $cates = Db::name('goods_visit a')->field('cat_id,COUNT(cat_id) as csum')->where($map)->group('cat_id')->select();
            $cat_ids = get_arr_column($cates,'cat_id');
            $cateArr = Db::name('goods_category')->whereIN('id', array_unique($cat_ids))->getField('id,name'); //收藏商品对应分类名称
            foreach ($cates as $k => $v) {
                if (isset($cateArr[$v['cat_id']])) $cates[$k]['name'] = $cateArr[$v['cat_id']];
                $visit_total += $v['csum'];
            }
        }
        $this->assign('visit_total', $visit_total);
        $this->assign('count', $count_all);
        $this->assign('catids', $cates);
        $this->assign('page', $Page->show());
        $this->assign('visit_log', $visit_log); //浏览记录
        return $this->fetch();
    }

    public function myCollect()
    {
        $item = input('item', 12);
        $goodsCollectModel = new GoodsCollect();
        $user_id = $this->user_id;
        $goodsList = $goodsCollectModel->with('goods')->where('user_id', $user_id)->limit($item)->order('collect_id', 'desc')->select();
        foreach($goodsList as $key=>$goods){
            $goodsList[$key]['url'] = $goods->url;
            $goodsList[$key]['imgUrl'] = goods_thum_images($goods['goods_id'], 160, 160);
        }
        if ($goodsList) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $goodsList]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '没有记录', 'result' => '']);
        }
    }

    /**
     * 历史记录
     */
    public function historyLog(){
        $item = input('item', 12);
        $goodsCollectModel = new GoodsVisit();
        $user_id = $this->user_id;
        $goodsList = $goodsCollectModel->with('goods')->where('user_id', $user_id)->limit($item)->order('visit_id', 'desc')->select();
        foreach($goodsList as $key=>$goods){
            $goodsList[$key]['url'] = $goods->url;
            $goodsList[$key]['imgUrl'] = goods_thum_images($goods['goods_id'], 160, 160);
        }
        if ($goodsList) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'result' => $goodsList]);
        } else {
            $this->ajaxReturn(['status' => 0, 'msg' => '没有记录', 'result' => '']);
        }
    }

}