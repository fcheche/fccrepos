<?php

namespace app\admin\controller; 
use think\AjaxPage;
use think\Controller;
use think\Url;
use think\Config;
use think\Page;
use think\Verify;
use think\Db;
use app\common\logic\OrderLogic;

class Index extends Base {


    public function recharges(){
    
        $content['recharge']=M('recharge')->where("pay_status= 0")->count();
        $content['withdrawals'] = M('withdrawals')->where("status= 0")->count();
        $content['zhuanzhang'] = M('withdrawals')->where("status= 1")->count();
        $this->AjaxReturn($content);
    }

    public function index(){

        $this->pushVersion();        
        $admin_info = getAdminInfo(session('admin_id'));
        if (empty(session('admin_id'))) {
            $this->error('登陆超时');
        }
        $CartLogic = new OrderLogic();
        $detail['act_list'] = $CartLogic->act_list_select();//查看权限
       // print_r($detail['act_list']);
        $this->assign('detail',$detail);
        $this->assign('admin_info',$admin_info);             
        $this->assign('menu',getMenuArr());   //view2
        return $this->fetch();
    }
   
    public function welcome(){

        // echo "<script>alert('后台面板管理中心正在加载，勿做其他操作，请稍等。。。');</script>";
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $this->assign('roleid',$roleid);

        $CartLogic = new OrderLogic();
        $act_list = $CartLogic->act_list_select();//查看权限
        $this->assign('act_list',$act_list);

        if (in_array(16, $act_list)) {
           print_r("欢迎登陆蜂车车网店管理后台！");die;
        }

        //登陆者信息
        $admin_info = getAdminInfo(session('admin_id'));

        $this->assign('admin_info',$admin_info);

        
        // 查询所有员工 分了战队的
        $admins = Db::name('admin')->where("corps_id > 0 and type = 0 and user_name <>'官方'")->order("dyyeji desc")->select();
        $this->assign('admins', $admins);
      
        $count['goods'] =  M('goods')->where("is_delete=0")->count();//商品总数
        $this->assign('count',$count);
        return $this->fetch();
    }

    public function ajax_wel(){

 // 战队搜索
        $admin_corps =  M('admin_corps')->where("zhuguan_name <> '天猫官方' and zhuguan_name <> '淘宝官方'")->order("dyyeji desc")->select();
        // print_r($admin_corps);
        if($admin_corps){
            foreach ($admin_corps as $k => $val) {
               // 挂店统计
                $corps_id=$val['corps_id'];
                $zhuguanname=trim($val['zhuguan_name']);
                $corps_yeji=trim($val['dyyeji']);

                 // 队员搜索
                $val['admins'] =  M('admin')->where("corps_id=$corps_id and type = 0")->order("dyyeji desc")->select();
                $val['dyyeji'] =  (M('order_list')->where("xs_corps_id=$corps_id and order_status > 0 and order_status != 5")->sum('shiji_yeji')+M('order_list')->where("gs_corps_id=$corps_id and order_status > 0 and order_status != 5")->sum('shiji_yeji'))*0.5;
                $val['dyguadian'] =  M('admin')->where("corps_id=$corps_id and type = 0")->sum('dyguadian');

                $data['dyyeji']=$val['dyyeji'];
                M('admin_corps')->where("corps_id=$corps_id")->save($data);
                $list[] = $val;
            }
        }
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
        $this->assign('roleid',$roleid);
        $admin_info = getAdminInfo(session('admin_id'));
        $this->assign('admin_info',$admin_info);
        $this->assign('list',$list);
        return $this->fetch();
    }



    public function admin_yeji(){

        $y=date("Y",time());
        $m=date("m",time());
        $t1=mktime(0,0,0,$m,1,$y); // 创建本月开始时间
        //建立条件
        $where['add_time']=['elt',$t1];
        $where['goods_type']=['eq',1];//天猫1淘宝2商标3专利4入驻5京东6企业7
        $where['order_status']=['gt',0];//天猫1淘宝2商标3专利4入驻5京东6企业7

        $tbyeji = M('order_list')->where("$t1<=add_time and goods_type = 2 and order_status > 0 and order_status != 5")->sum('shiji_yeji');//淘宝本月业绩

        $tmyeji = M('order_list')->where("$t1<=add_time and goods_type = 1 and order_status > 0 and order_status != 5")->sum('shiji_yeji');//天猫本月业绩

        $sbyeji = M('order_list')->where("$t1<=add_time and goods_type = 3 and order_status > 0 and order_status != 5")->sum('shiji_yeji');//商标本月业绩

        $zlyeji = M('order_list')->where("$t1<=add_time and goods_type = 4 and order_status > 0 and order_status != 5")->sum('shiji_yeji');//专利本月业绩

        $yeji = M('order_list')->where("$t1<=add_time and order_status > 0 and order_status != 5")->sum('shiji_yeji');//本月业绩

        $allyeji['tbyeji']=$tbyeji;
        $allyeji['tmyeji']=$tmyeji;
        $allyeji['sbyeji']=$sbyeji;
        $allyeji['zlyeji']=$zlyeji;
        $allyeji['yeji']=$yeji;
        $data=json_encode($allyeji);
        return $data;
    }

    
    public function get_sys_info(){
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off		
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
		$sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';	
		$sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();
		$sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		$sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
		$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
		$sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
		$sys_info['memory_limit']   = ini_get('memory_limit');	                                
        $sys_info['version']   	    = file_get_contents(APP_PATH.'admin/conf/version.php');
		$mysqlinfo = Db::query("SELECT VERSION() as version");
		$sys_info['mysql_version']  = $mysqlinfo[0]['version'];
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
    }
    
    // 在线升级系统
    public function pushVersion()
    {            
        if(!empty($_SESSION['isset_push']))
            return false;    
        $_SESSION['isset_push'] = 1;    
        error_reporting(0);//关闭所有错误报告
        $app_path = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $version_txt_path = $app_path.'/application/admin/conf/version.php';
        $curent_version = file_get_contents($version_txt_path);

        $vaules = array(            
                'domain'=>$_SERVER['SERVER_NAME'], 
                'last_domain'=>$_SERVER['SERVER_NAME'], 
                'key_num'=>$curent_version, 
                'install_time'=>INSTALL_DATE,
                'serial_number'=>SERIALNUMBER,
         );     
         $url = "http://service.tp-shop.cn/index.php?m=Home&c=Index&a=user_push&".http_build_query($vaules);
         stream_context_set_default(array('http' => array('timeout' => 3)));
         file_get_contents($url);         
    }
    
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
            $table = I('table'); // 表名
            $id_name = I('id_name'); // 表主键id名
            $id_value = I('id_value'); // 表主键id值
            $field  = I('field'); // 修改哪个字段
            $value  = I('value'); // 修改字段值                        
            M($table)->where([$id_name => $id_value])->save(array($field=>$value)); // 根据条件保存修改的数据
    }
    
     public function changeOnline(){
            $admin_id = I('admin_id'); // 表主键id值
            $value = I('value'); // 修改字段值  

            $admins=M('admin')->where(['admin_id' => $admin_id])->find();
            if (!empty(strstr($admins['is_online'], $value))) {
                $values = preg_replace('/['.$value.']+/i','',$admins['is_online']);//在字符串$admins['is_online']中去掉其中包含的$value
            }else{
                $values = $admins['is_online'].$value;
            }                  
            M('admin')->where(['admin_id' => $admin_id])->save(array('is_online'=>$values)); // 根据条件保存修改的数据
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }

    public function about(){
    	return $this->fetch();
    }
}