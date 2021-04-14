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
 * 评论管理控制器
 * Date: 2015-10-20
 */

namespace app\admin\controller;

use think\AjaxPage;
use think\Page;
use think\Db;

class Comment extends Base {

    public function voucher(){
        return $this->fetch();
    }

    public function cl_voucher(){
        //获取管理员信息 
        $id = I('id','','trim');
        $ress = M('voucher')->where(array('v_id'=>$id))->find();
        if ($ress['cl_adminid']!=session('admin_id')) {
            $this->ajaxReturn(['status' => -1,'msg' => '对不起你不是分配人员，不能处理','url'=>U('Admin/Comment/voucher')]);
        }
        if($id){
            $row = M('voucher')->where(array('v_id'=>$id))->save(array('cl_time' => time()));
            if ($row) {
                 $this->ajaxReturn(['status' => 1,'msg' => '处理成功','url'=>U('Admin/Comment/voucher')]);
            }
           
        }else{
            $this->ajaxReturn(['status' => -1,'msg' => '操作错误','url'=>U('Admin/Comment/voucher')]);
        }
    }

    public function ajaxvoucher(){
        $tel = I('tel','','trim');
       
        if($tel){
            $where['tel']= $tel;
        }
      
        $count = Db::name('voucher')->where($where)->count();
        $Page  = $pager = new AjaxPage($count,10);
        $show  = $Page->show();             
        
        $pinggus = Db::name('voucher')->where($where)->order('time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
      
       foreach ($pinggus as $key => $value) {
            $value['name'] = M('admin')->where('admin_id='.$value['cl_adminid'])->getField('user_name');
            $pinggu[]=$value;
        }

            $time=time();
            $act_where['is_finished']=0;
            $act_where['start_time']=array('lt',$time);
            $act_where['end_time']=array('gt',$time);
            $act = M('goods_activity')->where($act_where)->find();
            if ($act) {
                $huodong = 0;
            }else{
                $huodong = 1;//活动结束
            }

        $this->assign('huodong',$huodong);
        $this->assign('adminid',session('admin_id'));
        $this->assign('pinggu',$pinggu);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch();
    }

    // 红包详情 管理员分配给员工
    public function voucher_info(){

        $id = I('get.id/d');
        $cl_adminid = I('post.cl_adminid');
        $desc = I('post.desc');
        if ($cl_adminid) {
            $data['cl_adminid']=$cl_adminid;
            $data['desc']=$desc;
            $ress = Db::name('voucher')->where(['v_id'=>$id])->save($data);
        }
        if ($ress) {
            exit($this->success('成功'));
        }

        $res = Db::name('voucher')->where(['v_id'=>$id])->find();
        if(!$res){
            exit($this->error('网页错误'));
        }else{
            $this->assign('res',$res);
        }

         // 销售人
        $xs_name=Db::name('admin')->where("corps_id > 0")->select();
        $this->assign('xs_name',$xs_name);
        return $this->fetch();
    }


    //评估列表
    public function pinggu_list(){
        return $this->fetch();
    }

     public function cl_pinggu_list(){
        //获取管理员信息 
        $admin_info = getAdminInfo(session('admin_id'));
        $pg_id = I('pg_id','','trim');
        
        if($pg_id){

            $row = M('pinggu')->where(array('pg_id'=>$pg_id))->save(array('cl_time' => time(),'cl_name' => $admin_info['user_name'],'is_chuli' =>1));
            if ($row) {
                 $this->ajaxReturn(['status' => 1,'msg' => '处理成功','url'=>U('Admin/Comment/pinggu_list')]);
            }
           
        }else{
            $this->ajaxReturn(['status' => -1,'msg' => '操作错误','url'=>U('Admin/Comment/pinggu_list')]);
        }
    }
    

    public function ajax_pinggu_list(){
        $tel = I('tel','','trim');
        $leixing = I('leixing','','trim');
        
        if($tel){
            $where['tel']= $tel;
        }
        if($leixing){
            $where['dpleixing'] = ['like', "%$leixing%"];
        }
        $where['is_delete']= 0;
       
        $count = Db::name('pinggu')->where($where)->count();
        $Page  = $pager = new AjaxPage($count,10);
        $show  = $Page->show();             
        
        $pinggu = Db::name('pinggu')->where($where)->order('on_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
       
        $this->assign('pinggu',$pinggu);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch();
    }


//求购咨询
     public function qiugou_list(){
        $this->assign('type',1);
        return $this->fetch();
    }
    

    public function ajax_qiugou_list(){
        $tel = I('tel','','trim');
        $content = I('content','','trim');
        $where['is_delete']= 0;
        if($tel){
            $where['tel']= $tel;
        }
        if($content){
            $where['desc'] = ['like', "%$content%"];
        }

        $where['type'] = I('type');
        $count = Db::name('qiugou')->where($where)->count();
        $Page  = $pager = new AjaxPage($count,10);
        $show  = $Page->show();             
        
        $qiugou_list = Db::name('qiugou')->where($where)->order('on_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
       
        $this->assign('qiugou_list',$qiugou_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch();
    }
        public function qiugou_info(){
        $id = I('id/d',0);

        $res = M('qiugou')->where(array('qg_id'=>$id))->find();
        if(!$res){
            exit($this->error('不存在该咨询'));
        }

        //获取管理员信息 
        $admin_info = getAdminInfo(session('admin_id'));

        if(IS_POST){
            $add['cl_time']=time();
            $row = M('qiugou')->where(array('qg_id'=>$id))->save(array('cl_time' => $add['cl_time'],'cl_name' => $admin_info['user_name'],'chulires' =>I('post.chulires')));
            if ($row) {

                $add['cl_time']=date('Y-m-d H:i',$add['cl_time']);
                $add['chulires']=I('post.chulires');
                $this->ajaxReturn(['status'=>1,'msg'=>'添加成功','resault'=>$add]);
            } else {
                $this->ajaxReturn(['status'=>1,'msg'=>'添加失败']);
            }
            exit;       
        }
        // $reply = M('qiugou')->where(array('parent_id'=>$id))->select(); // 咨询回复列表
        $this->assign('id', $id);
        $this->assign('comment',$res);
        // $this->assign('reply',$reply);
        return $this->fetch();
    }
   public function qiugou_handle()
    {
        $type = I('post.type');
        $ids = I('ids','');
        if(!in_array($type, array('del', 'show', 'hide')) || empty($ids)){
            $this->ajaxReturn(['status' => -1,'msg' => '非法操作！']);
        }
        $selected_id = rtrim($ids,",");
        $row = false;
        if ($type == 'del') {
            //删除咨询
            $row = M('qiugou')->where('qg_id', 'IN', $selected_id)->save(array('is_delete' => 1));
        }
        if ($type == 'show') {
            $row = M('qiugou')->where('qg_id', 'IN', $selected_id)->save(array('is_delete' => 0));
        }
        if ($type == 'hide') {
            $row = M('qiugou')->where('qg_id', 'IN', $selected_id)->save(array('is_delete' => 2));
        }
        if($row !== false){

            $this->ajaxReturn(['status' => 1,'msg' => '操作完成']);
        }else{
            $this->ajaxReturn(['status' => -1,'msg' => '操作失败']);
        }
    }



//出售信息
     public function chushou_list(){

        $this->assign('type',2);
        return $this->fetch();
    }
    
 
}