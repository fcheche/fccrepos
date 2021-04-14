<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 聂晓克      
 * Date: 2017-12-14
 */
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\common\logic\GoodsActivityLogic;
use app\common\logic\ActivityLogic;
use think\Page;

use think\Db;

class Block extends Base{

	public function activeList(){
        $activity =  M('goods_activity'); 
        $res = $list = array();
        $p = empty($_REQUEST['p']) ? 1 : $_REQUEST['p'];
        $size = empty($_REQUEST['size']) ? 20 : $_REQUEST['size'];
        
        $where = " 1 = 1 ";
       
        $res = $activity->where($where)->order('act_id desc')->page("$p,$size")->select();
        $count = $activity->where($where)->count();// 查询满足要求的总记录数
        $pager = new Page($count,$size);// 实例化分页类 传入总记录数和每页显示的记录数
        //$page = $pager->show();//分页显示输出
        
        if($res){
            foreach ($res as $val){
                $val['start_time'] = date('Y-m-d H:i:s',$val['start_time']); 
                $val['end_time'] = date('Y-m-d H:i:s',$val['end_time']);                
                $list[] = $val;
            }
        }

        $this->assign('list',$list);// 赋值数据集
        $this->assign('pager',$pager);// 赋值分页输出        
        return $this->fetch('activeList');
    }
    public function active(){
        $act = I('GET.act','add');
        $info = array();
        $info['start_time'] = time()+3600*24;
        if(I('GET.act_id')){
           $act_id = I('GET.act_id');
           $info = M('goods_activity')->where('act_id='.$act_id)->find();
        }
      
        $this->assign('act',$act);
        $this->assign('info',$info);
        return $this->fetch();
    }

    public function activeHandle()
    {
        $act_id = I('post.act_id');
        $data['act_name'] = I('post.act_name');
        $data['act_desc'] = I('post.act_desc');
        $data['start_time'] = strtotime(I('post.start_time'));
        $data['end_time'] = strtotime(I('post.end_time'));
        $data['is_finished'] = I('post.is_finished');
        
        if (I('post.act') == 'add') {
            $r = M('goods_activity')->add($data);
        } elseif (I('post.act') == 'edit') {
            $r = M('goods_activity')->where('act_id='.$act_id)->save($data);
        } elseif (I('post.act') == 'del') {
            $r = M('goods_activity')->where('act_id='.$act_id)->delete();  
        }
        
        if (!$r) {
            $this->ajaxReturn(['status' => -1, 'msg' => '操作失败']);
        }
            
        $this->ajaxReturn(['status' => 1, 'msg' => '操作成功']);
    }

	//删除页面
	public function delete(){
		$id=I('post.id');
		if($id){
			$r = D('mobile_template')->where('id', $id)->delete();
    		exit(json_encode(1));
		}
	}

}
?>