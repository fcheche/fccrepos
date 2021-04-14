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
 * Date: 2015-10-09
 */

namespace app\admin\controller;
use think\AjaxPage;
use think\console\command\make\Model;
use think\Verify;
use app\admin\logic\GoodsLogic;
use app\common\logic\ModuleLogic;
use think\db;
use think\Cache;
use think\Page;
use app\admin\logic\UsersLogic;
use app\common\model\Users;

class System extends Base
{
	/*
	 * 配置入口
	 */
	public function index()
	{
		/*配置列表*/
		$group_list = [
            'shop_info' => '网站信息',
            // 'basic'     => '基本设置',
            // 'cash'      => '提现设置',
            // 'sms'       => '短信设置',
            // 'shopping'  => '购物流程设置',
            // 'smtp'      => '邮件设置',
            // 'water'     => '水印设置',
            // 'distribut' => '分销设置',
            // 'push'      => '推送设置',
            // 'oss'       => '对象存储',
            // 'express'	=> '物流设置'
        ];		
		$this->assign('group_list',$group_list);
		$inc_type =  I('get.inc_type','shop_info');
		$this->assign('inc_type',$inc_type);
		$config = tpCache($inc_type);
		if($inc_type == 'shop_info'){
			$province = M('region')->where(array('parent_id'=>0))->select();
			$city =  M('region')->where(array('parent_id'=>$config['province']))->select();
			$area =  M('region')->where(array('parent_id'=>$config['city']))->select();
			$this->assign('province',$province);
			$this->assign('city',$city);
			$this->assign('area',$area);
		}
		$this->assign('config',$config);//当前配置项
                //C('TOKEN_ON',false);
		return $this->fetch($inc_type);
	}

    // 浏览记录
    public function goods_log(){
        $admin_name=trim(I('get.admin_name'));
       
        $where = [];
        if($admin_name){
            $where['admin_name'] = ['like','%'.$admin_name.'%'];
        }

        $count = M('goods_log')->where($where)->count();
        $Page = new Page($count, 10);
        $list = DB::name('goods_log')
                ->where($where)
                ->alias('s')
                ->field('s.*,g.goods_name,g.goods_type')
                ->join('__GOODS__ g','g.goods_id = s.goods_id','LEFT')
                ->limit($Page->firstRow, $Page->listRows)
                ->order("time desc")
                ->select();

        $this->assign('page', $Page);
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function duanxin(){

        if (IS_POST) {
            $tel=trim(I('tel'));
            $content=I('content');

            $url='http://msg.fcheche.com/demo/message_send_word.php?tel='.$tel.'&content=【蜂车车】'.$content;
            $curl = curl_init();
            curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($curl,CURLOPT_URL, $url);
            curl_setopt($curl,CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
            $resp = curl_exec($curl);
            curl_close($curl);
         
        //短信发送成功返回True，失败返回false
        if ($resp) {
            // print_r($resp);die;
            $this->success("操作成功!!!",U('Admin/System/duanxin'));
        } else {
            // print_r($resp);die;
            $this->error("操作失败!!!",U('Admin/System/duanxin'));
        }
        }
        return $this->fetch();
    }

    //报备系统
    public function baobei(){
        $up_name=I('get.up_name');
        $some_sn=I('get.some_sn');
        $where['is_delete'] = 0;
        if($up_name){
            $where['up_name'] = ['like','%'.$up_name.'%'];
        }
        if($some_sn){
            $where['some_sn'] = ['like','%'.$some_sn.'%'];
        }
        
        $leibie = M('admin')->where(['admin_id'=>session('admin_id')])->getField('leibie');
        if ($leibie==1) {
            $where['plates'] = ['eq','天猫'];
        }
        if ($leibie==2) {
            $where['plates'] = ['eq','淘宝'];
        }
        
        $count = M('baobei')->where($where)->count();
        $Page = new Page($count, 10);
        $list = M('baobei')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order("time desc")->select();
        $this->assign('page', $Page);
        $this->assign('list', $list);
        return $this->fetch();
    }
    //报备处理
    public function baobeichuli(){
 
        $ct_id=I('get.ct_id');
        $baobei = M('Baobei')->where('ct_id='.$ct_id)->find();
        $order_sn=trim($baobei['some_sn']);
        $info = M('Order')->where('order_sn',$order_sn)->find();
        if (!$info) {
           $this->error("此处仅处理订单状态问题，订单编号有误请重新填写!!!");
        }
        $this->assign('info', $info);
        $this->assign('ct_id', $ct_id);
        return $this->fetch();
    }

    public function bbHandle()
    {
        $data = I('post.');
       
        $info = M('Order')->where('order_sn='.$data['order_sn'])->find();
        if (!$info) {
            $this->ajaxReturn(['status' => -1, 'msg' => '订单不存在']);
        }else{

            $update['order_status']=$data['order_status'];
            $r = M('Order')->where('order_sn='.$data['order_sn'])->save($update);
        }
        
        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }else{

        $user_name = M('admin')->where(['admin_id'=>session('admin_id')])->getField('user_name');
            $upbb['cl_name']=$user_name;
            $upbb['cltime']=time();
            $upbb['beizhu']=$data['beizhu'];
            $baobei = M('Baobei')->where('ct_id='.$data['ct_id'])->save($upbb);
            $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
        }
    }

      public function baobei_delete()
    {
        $ct_id = input('ct_id');
        $data['is_delete']=1;
        Db::name('baobei')->where('ct_id', $ct_id)->save($data);
        $this->ajaxReturn(['status' => 1, 'msg' => '删除成功', 'result' => '']);
    }

// 访问记录
    public function member(){
        $ip=I('get.ip');
       
        $where = [];
        if($ip){
            $where['ip'] = ['like','%'.$ip.'%'];
        }
        
        $count = M('member')->where($where)->count();
        $Page = new Page($count, 10);
        $list = M('member')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order("end_time desc")->select();
        $this->assign('page', $Page);
        $this->assign('list', $list);
        return $this->fetch();
    }
	
	/*
	 * 新增修改配置
	 */
	public function handle()
	{
		$param = I('post.');
		$inc_type = $param['inc_type'];
		//unset($param['__hash__']);
		unset($param['inc_type']);
		tpCache($inc_type,$param);                
                
                // 设置短信商接口
                if($param['sms_platform'] == 2 &&  !empty($param['sms_appkey'])  && !empty($param['sms_secretKey']))
                {                     
                    $sms_appkey = trim($param['sms_appkey']);
                    $sms_secretKey = trim($param['sms_secretKey']);
                    $url = 'http://open.1cloudsp.com:8090/api/admin/setParentId?parentId=14257&accesskey='.urlencode($sms_appkey).'&secret='.urlencode($sms_secretKey);
                    httpRequest($url);                    
                }                                
		$this->success("操作成功",U('System/index',array('inc_type'=>$inc_type)));
	}        
        
       /**
        * 自定义导航
        */
    public function navigationList(){
           $model = M("Navigation");
           $navigationList = $model->order("id desc")->select();            
           $this->assign('navigationList',$navigationList);
           return $this->fetch('navigationList');
     }

    /**
     * 添加修改编辑 前台导航
     */
    public  function addEditNav(){
        if(IS_POST)
        {
            if (I('id'))
                DB::name("Navigation")->update(I('post.'));
            else
                DB::name("Navigation")->add(I('post.'));

            $this->success("操作成功!!!",U('Admin/System/navigationList'));
            exit;
        }
        // 点击过来编辑时
        $id = I('id',0);
        $navigation = DB::name('navigation')->where('id',$id)->find();
        //导航位置数组
        $system_position = array(
            'top' => '导航顶部',
            'bottom' => '导航底部'
        );
        // 系统菜单 顶部
        $GoodsLogic = new GoodsLogic();
        $cat_list = $GoodsLogic->goods_cat_list();
        $select_option = array();
        if(!empty($cat_list))
        {
            foreach ($cat_list AS $key => $value)
            {
                $strpad_count = $value['level']*4;
                $select_val = U("/Home/Goods/goodsList",array('id'=>$key));
                $select_option[$select_val] = str_pad('',$strpad_count,"-",STR_PAD_LEFT).$value['name'];
            }
        }
        $system_nav = array(
            '/index.php?m=Home&c=Activity&a=promoteList' => '促销活动',
            '/index.php?m=Home&c=Activity&a=flash_sale_list' => '限时抢购',
            '/index.php?m=Home&c=Activity&a=group_list' => '团购',
            '/index.php?m=Home&c=Index&a=street' => '店铺街',
            '/index.php?m=Home&c=Goods&a=integralMall' => '积分商城',
        );
        $system_nav = array_merge($system_nav,$select_option);
        $this->assign('system_nav',$system_nav);

        //地下菜单文章
        $system_bottom = array();
        $article = db('article')->where('is_open',1)->select();
        if(!empty($article)){
            foreach($article as $value){
                $system_bottom['/index.php/Home/Article/detail/article_id/'.$value['article_id']] = $value['title'];
            }
        }

        $yiji=DB::name('navigation')->where('yiji','0')->getField('name',true); 


        //分配底部文章
        $this->assign('system_bottom',$system_bottom);

        //分配位置数组
        $this->assign('position',$system_position);
        $this->assign('yiji',$yiji);
        $this->assign('navigation',$navigation);
        return $this->fetch('_navigation');
    }

    /**
     * 添加修改编辑 前台导航
     */
    public function navHandle()
    {
        $data = I('post.');
        empty($data) && $this->ajaxReturn(['status'=>-1,'msg'=>"参数有误！！"]);
        $result = $this->validate($data, 'Navigation.'.$data['act'], [], true);
        if ($result !== true) {
            // 验证失败 输出错误信息
            $validate_error = '';
            foreach ($result as $key =>$value){
                $validate_error .=$value.',';
            }
            $this->ajaxReturn(['status' => -1, 'msg' =>  $validate_error]);
        }
        if ($data['id']){
            DB::name("Navigation")->update($data);
        }else{
            DB::name("Navigation")->add($data);
        }
        $this->ajaxReturn(['status'=>1,'msg'=>"操作成功！！",'url'=>U('Admin/System/navigationList')]);
    }
    
    /**
     * 删除前台 自定义 导航
     */
    public function delNav()
    {     
        // 删除导航
        M('Navigation')->where("id",I('id'))->delete();
        $this->success("操作成功!!!",U('Admin/System/navigationList'));
    }
	
	public function refreshMenu(){
		$pmenu = $arr = array();
		$rs = M('system_module')->where('level>1 AND visible=1')->order('mod_id ASC')->select();
		foreach($rs as $row){
			if($row['level'] == 2){
				$pmenu[$row['mod_id']] = $row['title'];//父菜单
			}
		}
		foreach ($rs as $val){
			if($row['level']==2){
				$arr[$val['mod_id']] = $val['title'];
			}
			if($row['level']==3){
				$arr[$val['mod_id']] = $pmenu[$val['parent_id']].'/'.$val['title'];
			}
		}
		return $arr;
	}

        
        /**
         * 清空系统缓存
         */
        public function cleanCache(){
            $y=date("Y",time());
            $m=date("m",time());
            $t1=mktime(0,0,0,$m,1,$y); // 创建本月开始时间
            $allorder=M('order')->where("$t1<=add_time and pay_status != 0 and order_status < 3 and dingjin=2 and fadan_time=0")->select();
            if ($allorder) {
                foreach ($allorder as $key => $value) {
                    $order_id=$value['order_id'];
                    //已放款金额
                    $fk_price = M('fangkuan')->where(array('order_id'=>$value['order_id']))->sum('price');
                    $lirun=$value['paid_money']+$value['erci_price']-$fk_price;
                   if ($lirun>0 && $value['paid_money']+$value['erci_price']<($value['goods_price']*0.2)) {
                        // if ($value['dj_yeji']>$value['shiji_yeji']) {
                        //     $datas['dj_yeji']=$value['shiji_yeji'];
                        // }else{
                        $datas['dj_yeji']=($value['paid_money']+$value['erci_price'])*5;
                        // }
                        M('order')->where("order_id=$order_id")->save($datas);
                   }
                   if ($lirun==0 || $lirun < 0) {
                       $datas['dj_yeji']=$lirun*5;
                       $datas['shiji_yeji']=$lirun*5;
                     M('order')->where("order_id=$order_id")->save($datas);
                   }
                }
            }

			delFile(RUNTIME_PATH);
			Cache::clear();
			$quick = I('quick',0);
			if($quick == 1){
				$script = "<script>parent.layer.msg('缓存清除成功', {time:3000,icon: 1});window.parent.location.reload();</script>";
			}else{
				$script = "<script>parent.layer.msg('缓存清除成功', {time:3000,icon: 1});window.location='/index.php?m=Admin&c=Index&a=welcome';</script>";
			}
           	exit($script);
        }
	    
    /**
     * 清空静态商品页面缓存
     */
      public function ClearGoodsHtml(){
            $goods_id = I('goods_id');            
            if(unlink("./Application/Runtime/Html/Home_Goods_goodsInfo_{$goods_id}.html"))
            {
                // 删除静态文件                
                $html_arr = glob("./Application/Runtime/Html/Home_Goods*.html");
                foreach ($html_arr as $key => $val)
                {            
                    strstr($val,"Home_Goods_ajax_consult_{$goods_id}") && unlink($val); // 商品咨询缓存
                    strstr($val,"Home_Goods_ajaxComment_{$goods_id}") && unlink($val); // 商品评论缓存
                }
                $json_arr = array('status'=>1,'msg'=>'清除成功','result'=>'');
            }
            else 
            {
                $json_arr = array('status'=>-1,'msg'=>'未能清除缓存','result'=>'' );
            }                                                    
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      } 
    /**
     * 商品静态页面缓存清理
     */
    public function ClearGoodsThumb()
    {
        $goods_id = I('goods_id');
        delFile(UPLOAD_PATH . "goods/thumb/" . $goods_id); // 删除缩略图
        Cache::clear('original_img_cache');
        $json_arr = array('status' => 1, 'msg' => '清除成功,请清除对应的静态页面', 'result' => '');
        $json_str = json_encode($json_arr);
        exit($json_str);
    }
    /**
     * 清空 文章静态页面缓存
     */
      public function ClearAritcleHtml(){
            $article_id = I('article_id');            
            unlink("./Application/Runtime/Html/Index_Article_detail_{$article_id}.html"); // 清除文章静态缓存
            unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_api.html"); // 清除文章静态缓存
            unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_phper.html"); // 清除文章静态缓存
            unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_android.html"); // 清除文章静态缓存
            unlink("./Application/Runtime/Html/Doc_Index_article_{$article_id}_ios.html"); // 清除文章静态缓存
            $json_arr = array('status'=>1,'msg'=>'操作完成','result'=>'' );                                                          
            $json_str = json_encode($json_arr);            
            exit($json_str);            
      }
      
	//发送测试邮件
	public function send_email(){
		$param = I('post.');
//		tpCache($param['inc_type'],$param); //注释掉，不注释会出现重复写入数据库
        	$res = send_email($param['test_eamil'],'后台测试','测试发送验证码:'.mt_rand(1000,9999));
        	exit(json_encode($res));
      }
	        
    /**
     *  管理员登录后 处理相关操作
     */        
     public function login_task()
     {
         
        /*** 随机清空购物车的垃圾数据*/                     
        $time = time() - 3600; // 删除购物车数据  1小时以前的
        M("Cart")->where("user_id = 0 and  add_time < $time")->delete();            
        $today_time = time();
		
		// 删除 cart表垃圾数据 删除一个月以前的 
		$time = time() - 2592000; 
        M("cart")->where("add_time < $time")->delete();		
		// 删除 tp_sms_log表垃圾数据 删除一个月以前的短信
        M("sms_log")->where("add_time < $time")->delete();				
        
        // 发货后满多少天自动收货确认
        $auto_confirm_date = tpCache('shopping.auto_confirm_date');
        $auto_confirm_date = $auto_confirm_date * (60 * 60 * 24); // 7天的时间戳
		$time = time() - $auto_confirm_date; // 比如7天以前的可用自动确认收货
        $order_id_arr = M('order')->where("order_status = 1 and shipping_status = 1 and shipping_time < $time")->getField('order_id',true);       
        foreach($order_id_arr as $k => $v)
        {
            confirm_order($v);
        }      
        
        // 多少天后自动分销记录自动分成
         $switch = tpCache('distribut.switch');         
         if($switch == 1 && file_exists(APP_PATH.'common/logic/DistributLogic.php')){
            $distributLogic = new \app\common\logic\DistributLogic();
            $distributLogic->auto_confirm(); // 自动确认分成
         }         
     }
     
     function ajax_get_action()
     {
         $control = I('controller');
         $type = I('type',0);
         $module = (new ModuleLogic)->getModule($type);
         if (!$module) {
             exit('模块不存在或不可见');
         }

         $selectControl = [];
         $className = "app\\".$module['name']."\\controller\\".$control;
         $methods = (new \ReflectionClass($className))->getMethods(\ReflectionMethod::IS_PUBLIC);
         foreach ($methods as $method) {
             if ($method->class == $className) {
                 if ($method->name != '__construct' && $method->name != '_initialize') {
                     $selectControl[] = $method->name;
                 }
             }
         }

         $html = '';
         foreach ($selectControl as $val){
             $html .= "<li><label><input class='checkbox' name='act_list' value=".$val." type='checkbox'>".$val."</label></li>";
             if($val && strlen($val)> 18){
                 $html .= "<li></li>";
             }
         }
         exit($html);
     }
     
    function right_list()
    {
        $type = I('type',0);
        $moduleLogic = new ModuleLogic;
        if (!$moduleLogic->isModuleExist($type)) {
            $this->error('权限类型不存在');
        }
        $modules = $moduleLogic->getModules();
        $group = $moduleLogic->getPrivilege($type);

        $condition['type'] = $type;
        $condition['is_del'] = 0;
        $name = I('name');
        if(!empty($name)){
            $condition['name|right'] = array('like',"%$name%");
        }
        $right_list = M('system_menu')->where($condition)->order('id desc')->select();
        $this->assign('right_list',$right_list);
        $this->assign('group',$group);
        $this->assign('modules',$modules);
        return $this->fetch();
    }

    public function edit_right()
    {
        $type = I('type',0);  //0:平台权限资源;1:商家权限资源
        $moduleLogic = new ModuleLogic;
        if (!$moduleLogic->isModuleExist($type)) {
            $this->error('模块不存在或不可见');
        }

        if(IS_POST){
            $data = I('post.');
            if(!$data['right']){
                $this->error('请添加权限码');
            }
            $data['right'] = implode(',',$data['right']);
            if(!empty($data['id'])){
                M('system_menu')->where(array('id'=>$data['id']))->save($data);
            }else{
                // if(M('system_menu')->where(array('type'=>$data['type'],'name'=>$data['name']))->count()>0){
                //     $this->error('该权限名称已添加，请检查',U('System/right_list'));
                // }
                unset($data['id']);
                M('system_menu')->add($data);
            }
            $this->success('操作成功',U('System/right_list',array('type'=>$data['type'])));
            exit;
        }
        $id = I('id');
        if($id){
            $info = M('system_menu')->where(array('id'=>$id))->find();
            $info['right'] = explode(',', $info['right']);
            $this->assign('info',$info);
        }

        $modules = $moduleLogic->getModules();
        $group = $moduleLogic->getPrivilege($type);
        $planPath = APP_PATH.$modules[$type]['name'].'/controller';
        $planList = array();
        $dirRes   = opendir($planPath);
        while($dir = readdir($dirRes))
        {
            if(!in_array($dir,array('.','..','.svn')))
            {
                $planList[] = basename($dir,'.php');
            }
        }

        $this->assign('modules', $modules);
        $this->assign('planList',$planList);
        $this->assign('group',$group);
        return $this->fetch();
    }
     
     public function right_del(){
        die;
     	$id = I('del_id');
     	if(is_array($id)){
     		$id = implode(',', $id); 
     	}
     	if(!empty($id)){
     		$r = M('system_menu')->where("id in ($id)")->delete();
     		if($r){
     			respose(1);
     		}else{
     			respose('删除失败');
     		}
     	}else{
     		respose('参数有误');
     	}
     }
	//清除所有活动数据
	public function clearProm()
	{
		Db::name('flash_sale')->where('1=1')->delete();
		Db::name('group_buy')->where('1=1')->delete();
		Db::name('prom_goods')->where('1=1')->delete();
		Db::name('prom_order')->where('1=1')->delete();
		Db::name('coupon')->where('1=1')->delete();
		Db::name('coupon_list')->where('1=1')->delete();
		Db::name('goods_coupon')->where('1=1')->delete();
		Db::name('goods')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('spec_goods_price')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('cart')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('order_goods')->where('prom_type', '<>', 0)->whereOr('prom_id', '<>', 0)->update(['prom_type' => 0, 'prom_id' => 0]);
		$this->success('清除活动数据成功');
	}

	//清楚拼团活动数据
	public function clearTeam(){
		Db::name('team_activity')->where('1=1')->delete();
		Db::name('team_follow')->where('1=1')->delete();
		Db::name('team_found')->where('1=1')->delete();
		Db::name('team_lottery')->where('1=1')->delete();
		Db::name('goods')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('spec_goods_price')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('order')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		Db::name('order_goods')->where('prom_type',6)->update(['prom_type' => 0, 'prom_id' => 0]);
		$this->success('清除拼团活动数据成功');
	}
        
    /**
     * 清空演示数据 用完切记删除
     * http://www.xxx.com/Admin/system/truncate_demo_data
     */
    public function truncate_demo_data(){
        /*
        $result = Db::query('show tables');        
        $prefix   = \think\config::get('database.prefix');
        $database = \think\config::get('database.database');
        $tables = array();        
        foreach($result as $key => $val){
                $tables[] = array_shift($val);
        }	 			    
         
        $bl_table = array('tp_admin','tp_config','tp_region','tp_system_module','tp_admin_role','tp_system_menu','tp_article_cat','tp_article','tp_wx_user');
        foreach($bl_table as $k => $v)
        {
                $bl_table[$k] = str_replace('tp_',$prefix,$v); 
        }			      
        
        foreach($tables as $key => $val)
        {					
                if(!in_array($val, $bl_table))
                {
                     Db::execute("truncate table ".$val); 
                }		
        }   	
        delFile('../public/upload/goods'); // 清空测试图片			
               
        header("Content-type: text/html; charset=utf-8");  
        echo "数据已清空,请立即删除这个方法";
        */ 
         
    }        
        
}