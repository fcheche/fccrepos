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

use app\common\logic\AdminLogic;
use app\common\logic\ModuleLogic;
use think\Page;
use think\Verify;
use think\Loader;
use think\Db;
use think\Session;

class Admin extends Base {

    public function index(){

        $admin_info = getAdminInfo(session('admin_id'));
        $role = D('admin_role')->getField('role_id,role_name');
    	$list = array();

    	$keywords = trim(I('keywords/s'));
        if ($admin_info['leibie']==0) {
            if(empty($keywords)){
                $res = D('admin')->where('type = 0')->order('corps_id desc')->select();
            }else{
                $res = DB::name('admin')->where('type = 0 and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        }else{
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = '.$admin_info['leibie'])->order('corps_id desc')->select();
            }else{
        
                $res = DB::name('admin')->where('type = 0 and leibie = '.$admin_info['leibie'].' and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        }
    	
        $corps = D('admin_corps')->getField('corps_id,corps_name');

    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
    	$this->assign('list',$list);
        return $this->fetch();
    }

    public function taobao(){

        $role = D('admin_role')->getField('role_id,role_name');
        $list = array();

        $keywords = trim(I('keywords/s'));
     
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = 2')->order('corps_id desc')->select();
            }else{
                $res = DB::name('admin')->where('(type = 0 and leibie = 2) and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }

    public function tianmao(){

        $admin_info = getAdminInfo(session('admin_id'));
        $role = D('admin_role')->getField('role_id,role_name');
        $list = array();

        $keywords = trim(I('keywords/s'));
      
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = 1')->order('corps_id desc')->select();
            }else{
        
                $res = DB::name('admin')->where('(type = 0 and leibie = 1) and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }

        // 商标
    public function shangbiao(){

        $role = D('admin_role')->getField('role_id,role_name');
        $list = array();

        $keywords = trim(I('keywords/s'));
      
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = 3')->order('corps_id desc')->select();
            }else{
                $res = DB::name('admin')->where('(type = 0 and leibie = 3) and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }

    // 专利
    public function zhuanli(){

        $role = D('admin_role')->getField('role_id,role_name');
        $list = array();

        $keywords = trim(I('keywords/s'));
      
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = 4')->order('corps_id desc')->select();
            }else{
                $res = DB::name('admin')->where('(type = 0 and leibie = 4) and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }

    // 企业
    public function qiye(){

        $role = D('admin_role')->getField('role_id,role_name');
        $list = array();

        $keywords = trim(I('keywords/s'));
      
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = 7')->order('corps_id desc')->select();
            }else{
                $res = DB::name('admin')->where('(type = 0 and leibie = 7) and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }


    // 入驻
    public function ruzhu(){

        $role = D('admin_role')->getField('role_id,role_name');
        $list = array();

        $keywords = trim(I('keywords/s'));
      
            if(empty($keywords)){
                $res = D('admin')->where('type = 0 and leibie = 5')->order('corps_id desc')->select();
            }else{
        
                $res = DB::name('admin')->where('(type = 0 and leibie = 5) and (user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%")')->order('corps_id desc')->select();
            }
        
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }

        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }

     public function lizhi(){
        $list = array();
        $keywords = I('keywords/s');
        if(empty($keywords)){
            $res = D('admin')->where('type = 1')->order('corps_id desc')->select();
        }else{
            $res = DB::name('admin')->where('type = 1 and user_name like "%'.$keywords.'%" or qq like "%'.$keywords.'%" or tel like "%'.$keywords.'%"')->order('corps_id desc')->select();
        }
        $role = D('admin_role')->getField('role_id,role_name');
        $corps = D('admin_corps')->getField('corps_id,corps_name');

        if($res && $role){
            foreach ($res as $val){
                $val['role'] =  $role[$val['role_id']];
                $val['corps'] =  $corps[$val['corps_id']];
                $val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
                $list[] = $val;
            }
        }
       $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = session('admin_id');
        $this->assign('admin_id',$admin_id);
        $this->assign('list',$list);
        return $this->fetch();
    }
    
    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function modify_pwd(){
        $admin_id = I('admin_id/d',0);
        $oldPwd = I('old_pw/s');
        $newPwd = I('new_pw/s');
        $new2Pwd = I('new_pw2/s');
       
        if($admin_id){
            $info = D('admin')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info',$info);
        }
        
         if(IS_POST){
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $admin = M('admin')->where('admin_id' , $admin_id)->find();
            if(!$admin || $admin['password'] != $enOldPwd){
                exit(json_encode(array('status'=>-1,'msg'=>'旧密码不正确')));
            }else if($newPwd != $new2Pwd){
                exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
            }else{
                $row = M('admin')->where('admin_id' , $admin_id)->save(array('password' => $enNewPwd));
                if($row){
                    exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'修改失败')));
                }
            }
        }
        return $this->fetch();
    }
    
    public function admin_info(){
    	$admin_id = I('get.admin_id/d',0);
    	if($admin_id){
    		$info = Db::name('admin')->where("admin_id", $admin_id)->find();
			$info['password'] = "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
        if ($info['leibie']>0) {
            $role = D('admin_role')->where("leibie",$info['leibie'])->select();
        }else{
            $role = D('admin_role')->where("role_id>100")->select();
        }
    	
    	$this->assign('role',$role);
        if ($info['leibie']>0) {
            $corps = D('admin_corps')->where("type",$info['leibie'])->select();
        }else{
            $corps = D('admin_corps')->select();
        }
        
        $this->assign('corps',$corps);

         $leibie = D('admin')->where('admin_id',session('admin_id'))->getField('leibie');
        $this->assign('leibie',$leibie);
         
    	return $this->fetch();
    }
    
    public function adminHandle(){
    	$data = I('post.');
        $data['ruzhi_time'] = strtotime(I('post.ruzhi_time'));
        $data['lizhi_time'] = strtotime(I('post.lizhi_time'));
        $data['tel'] = trim(I('post.tel'));
        if (empty($data['role_id'])) {
            unset($data['role_id']);
        }
		if(empty($data['password'])){
			unset($data['password']);
		}else{
			$data['password'] =encrypt($data['password']);
		}
    	if($data['act'] == 'add'){
    		unset($data['admin_id']);
    		$data['add_time'] = time();
			$r = D('admin')->add($data);

    	}
    	
    	if($data['act'] == 'edit'){
    		$r = D('admin')->where('admin_id',$data['admin_id'])->save($data);

    	}
        if($data['act'] == 'fuzhi' && $data['admin_id']>1){
            $lizhi['type']=0;
            $r = D('admin')->where('admin_id', $data['admin_id'])->save($lizhi);
        }

        if($data['act'] == 'lizhi' && $data['admin_id']>1){
            $user_name = D('admin')->where('admin_id', $data['admin_id'])->getField('user_name');
            $leibie = D('admin')->where('admin_id', $data['admin_id'])->getField('leibie');
          
            $lizhi['type']=1;
            $lizhi['lizhi_time']=time();
            $lizhi['is_online']=0;
            $lizhi['corps_id']=0;
            $lizhi['dyyeji']=0;
            $lizhi['dyguadian']=0;
            $r = D('admin')->where('admin_id', $data['admin_id'])->save($lizhi);
        }

        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = D('admin')->where('admin_id', $data['admin_id'])->delete();
    	}
    	
        if ($r) {
            $this->ajaxReturn(['status'=>1,'msg'=>'操作成功','url'=>U('Admin/Admin/index')]);
        }else{
            $this->ajaxReturn(['status'=>-1,'msg'=>'填写内容有误','url'=>U('Admin/Admin/index')]);
        }
			
    }
    
    
    /**
     * 管理员登陆
     */
    public function login()
    {
        if (IS_POST) {
            $code = I('post.vertify');
            $username = I('post.username/s');
            $password = I('post.password/s');

            $verify = new Verify();
            if (!$verify->check($code, "admin_login")) {
                $this->ajaxReturn(['status' => 0, 'msg' => '验证码错误']);
            }

            $adminLogic = new AdminLogic;
            $return = $adminLogic->login($username, $password);
            $this->ajaxReturn($return);
        }

        if (session('?admin_id') && session('admin_id') > 0) {
            $this->error("您已登录", U('Admin/Index/index'));
        }

        return $this->fetch();
    }
    
    /**
     * 退出登陆
     */
    public function logout()
    {
        $adminLogic = new AdminLogic;
        $adminLogic->logout(session('admin_id'));

        $this->success("退出成功",U('Admin/Admin/login'));
    }
    
    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => false,
        	'reset' => false
        );    
        $Verify = new Verify($config);
        $Verify->entry("admin_login");
        exit();
    }


// 战队
    public function corps(){
        $list = D('admin_corps')->order('corps_id desc')->select();
        $this->assign('list',$list);
        return $this->fetch();
    }
    
    public function corps_info(){
        $corps_id = I('get.corps_id/d');
        $detail = array();
        if($corps_id){
            $detail = M('admin_corps')->where("corps_id",$corps_id)->find();
            $detail['act_list'] = explode(',', $detail['act_list']);
            $this->assign('detail',$detail);
        }
        $right = M('system_menu')->order('id')->select();
        foreach ($right as $val){
            if(!empty($detail)){
                $val['enable'] = in_array($val['id'], $detail['act_list']);
            }
            $modules[$val['group']][] = $val;
        }

        $list = D('admin')->where('type = 0')->order('admin_id desc')->select();
        $this->assign('list',$list);

        //admin权限组
        $group = (new ModuleLogic)->getPrivilege(0);
        $this->assign('group',$group);
        $this->assign('modules',$modules);
        return $this->fetch();
    }
    
    public function corpsSave(){
        $data = I('post.');
        $res = $data['data'];
        $res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        $res['zhuguan_id'] = Db::name('admin')->where(['user_name'=>$res['zhuguan_name']])->getField("admin_id");
        
        if(empty($data['corps_id'])){
            $admin_corps = Db::name('admin_corps')->where(['corps_name'=>$res['corps_name']])->find();
            if($admin_corps){
                $this->error("已存在相同的战队名称!");
            }else{
                $r = D('admin_corps')->add($res);
            }
        }else{
            $admin_corps = Db::name('admin_corps')->where(['corps_name'=>$res['corps_name'],'corps_id'=>['<>',$data['corps_id']]])->find();
            if($admin_corps){
                $this->error("已存在相同的战队名称!");
            }else{
                $r = D('admin_corps')->where('corps_id', $data['corps_id'])->save($res);
            }
        }
        if($r){
            adminLog('管理战队');
            $this->success("操作成功!",U('Admin/Admin/corps_info',array('corps_id'=>$data['corps_id'])));
        }else{
            $this->error("操作失败!",U('Admin/Admin/corps'));
        }
    }
    
    public function corpsDel(){
        $corps_id = I('post.corps_id/d');
        $admin = D('admin')->where('corps_id',$corps_id)->find();
        if($admin){
            exit(json_encode("请先清空所属该战队的管理员"));
        }else{
            $d = M('admin_corps')->where("corps_id",$corps_id)->delete();
            if($d){
                exit(json_encode(1));
            }else{
                exit(json_encode("删除失败"));
            }
        }
    }

    public function getcorps(){
        $type = I('type');
        $corps = Db::name('admin_corps')->where(['type'=>$type])->select();
        
        $return_arr = ['status' => 1, 'msg' => '获取成功','result' => $corps];
        $this->ajaxReturn($return_arr);
    }

    public function getroles(){
        $type = I('type');
        if ($type==1) {
            $role_name="天猫";
        }
        if ($type==2) {
            $role_name="淘宝";
        }
        if ($type==3) {
            $role_name="京东";
        }
        if ($type==4) {
            $role_name="入驻";
        }
        if ($type==100) {
            $role_name="其他";
        }
        $role = Db::name('admin_role')->where('role_name like "%'.$role_name.'%"')->select();
        
        $return_arr = ['status' => 1, 'msg' => '获取成功','result' => $role];
        $this->ajaxReturn($return_arr);
    }
    

    //角色
    public function role(){
    	$list = D('admin_role')->where("role_id>1")->order('role_id desc')->select();
        $admin_info = getAdminInfo(session('admin_id'));
        $this->assign('admin_info',$admin_info);
    	$this->assign('list',$list);
    	return $this->fetch();
    }
    
    public function role_info(){
    	$role_id = I('get.role_id/d');
    	$detail = array();
        $detail['act_list']=array(0);
    	if($role_id){
    		$detail = M('admin_role')->where("role_id",$role_id)->find();
    		$detail['act_list'] = explode(',', $detail['act_list']);
    	}

        $this->assign('detail',$detail);
        // $right = M('system_menu')->order('id')->select();
		$right = M('system_menu')->where("is_del",0)->order('id')->select();
		foreach ($right as $val){
			if(!empty($detail)){
				$val['enable'] = in_array($val['id'], $detail['act_list']);
			}
			$modules[$val['group']][] = $val;
		}
		//admin权限组
        $group = (new ModuleLogic)->getPrivilege(0);
        $admin_info = getAdminInfo(session('admin_id'));
        $this->assign('admin_info',$admin_info);
		$this->assign('group',$group);
		$this->assign('modules',$modules);
    	return $this->fetch();
    }
    
    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        if(empty($res['act_list']))
            $this->error("请选择权限!");        
    	if(empty($data['role_id'])){
			$admin_role = Db::name('admin_role')->where(['role_name'=>$res['role_name']])->find();
			if($admin_role){
				$this->error("已存在相同的角色名称!");
			}else{
				$r = D('admin_role')->add($res);
			}
    	}else{
			$admin_role = Db::name('admin_role')->where(['role_name'=>$res['role_name'],'role_id'=>['<>',$data['role_id']]])->find();
			if($admin_role){
				$this->error("已存在相同的角色名称!");
			}else{
				$r = D('admin_role')->where('role_id', $data['role_id'])->save($res);
			}
    	}
		if($r){
			adminLog('管理角色');
			$this->success("操作成功!",U('Admin/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->error("操作失败!",U('Admin/Admin/role'));
		}
    }
    
    public function roleDel(){
    	$role_id = I('post.role_id/d');
    	$admin = D('admin')->where('role_id',$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("role_id", $role_id)->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }
    
    public function log(){

        $keywords = trim(I('keywords/s'));
        
    	$p = I('p/d',1);
    	$logs = DB::name('admin_log')->alias('l')->join('__ADMIN__ a','a.admin_id =l.admin_id')->where('user_name like "%'.$keywords.'%" or log_ip like "%'.$keywords.'%"')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = DB::name('admin_log')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }


	/**
	 * 供应商列表
	 */
	public function supplier()
	{
		$supplier_count = DB::name('suppliers')->count();
		$page = new Page($supplier_count, 10);
		$supplier_list = DB::name('suppliers')
				->alias('s')
				->field('s.*,a.admin_id,a.user_name')
				->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
				->limit($page->firstRow, $page->listRows)
				->select();
		$this->assign('list', $supplier_list);
		$this->assign('pager', $page);
		return $this->fetch();
	}

	/**
	 * 供应商资料
	 */
	public function supplier_info()
	{
		$suppliers_id = I('get.suppliers_id/d', 0);
		if ($suppliers_id) {
			$info = DB::name('suppliers')
					->alias('s')
					->field('s.*,a.admin_id,a.user_name')
					->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
					->where(array('s.suppliers_id' => $suppliers_id))
					->find();
			$this->assign('info', $info);
		}
		$act = empty($suppliers_id) ? 'add' : 'edit';
		$this->assign('act', $act);
		$admin = M('admin')->field('admin_id,user_name')->select();
		$this->assign('admin', $admin);
		return $this->fetch();
	}

	/**
	 * 供应商增删改
	 */
	public function supplierHandle()
	{
		$data = I('post.');
		$suppliers_model = M('suppliers');
		//增
		if ($data['act'] == 'add') {
			unset($data['suppliers_id']);
			$count = $suppliers_model->where("suppliers_name", $data['suppliers_name'])->count();
			if ($count) {
				$this->error("此供应商名称已被注册，请更换", U('Admin/Admin/supplier_info'));
			} else {
				$r = $suppliers_model->insertGetId($data);
				if (!empty($data['admin_id'])) {
					$admin_data['suppliers_id'] = $r;
					M('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
					M('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
				}
			}
		}
		//改
		if ($data['act'] == 'edit' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->save($data);
			if (!empty($data['admin_id'])) {
				$admin_data['suppliers_id'] = $data['suppliers_id'];
				$suppliers = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->find();
				$admin_data['city_id'] = $suppliers['city_id'];
				$admin_data['province_id'] = $suppliers['province_id'];
				M('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
			}
		}
		//删
		if ($data['act'] == 'del' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->delete();
			M('admin')->where(array('suppliers_id' => $data['suppliers_id']))->save(array('suppliers_id' => 0));
			if($r){
				respose(1);
			}else{
				respose('删除失败');
			}
		}

		if ($r !== false) {
			$this->success("操作成功", U('Admin/Admin/supplier'));
		} else {
			$this->error("操作失败", U('Admin/Admin/supplier'));
		}
	}
}