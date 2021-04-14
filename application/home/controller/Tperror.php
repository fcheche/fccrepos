<?php
/**
蜂车车
 */ 
namespace app\home\controller;
use think\Controller;
class Tperror extends Controller {

	public function index($msg='',$url=''){
		$msg = empty($msg) ? '您可能输入了错误的网址，或者该页面已经不存在了哦。' : $msg;
		$this->assign('error',$msg);		
		$tpshop_config = array();
		$tp_config = M('config')->cache(true,TPSHOP_CACHE_TIME)->select();
		foreach($tp_config as $k => $v)
		{
			if($v['name'] == 'hot_keywords'){
				$tpshop_config['hot_keywords'] = explode('|', $v['value']);
			}
			$tpshop_config[$v['inc_type'].'_'.$v['name']] = $v['value'];
		}
		$this->assign('goods_category_tree', get_goods_category_tree());
		
		$this->assign('tpshop_config', $tpshop_config);
		return $this->fetch('index/404');
	}
	
}