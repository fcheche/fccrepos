<?php
/**
热门问题
 */
namespace app\home\controller;
use think\Db;
use think\AjaxPage;
use think\Page;

class Rmwd extends Base {
    /**
     * 热门问题列表页 
     */
     public function index(){
        $where['on_time']=['gt',0];
        $count = M('shcf')->where($where)->count();
        $Page = new Page($count,15);
        $show = $Page->show();
        $rmwd = Db::name('shcf')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order('on_time','desc')->select();
        $this->assign('rmwd',$rmwd);
        $this->assign('page',$show);
        return $this->fetch();
    }  
    
}