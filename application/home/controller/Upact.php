<?php
/**
报备
 */ 
namespace app\home\controller;
use think\Db;

class Upact extends Base {
   
    /*
     * 专题详情
     */
    public function index(){
        
        return $this->fetch();
    }

    public function do_act(){

        $user = Db::name('admin')->where('user_name',I('post.account'))->find();
        if (!$user) {
            $result = array('status' => -1, 'msg' => '账号不存在!');
        }elseif(encrypt(I('post.password')) != $user['password']) {
            $result = array('status' => -2, 'msg' => '密码错误!');
        }else{
            $update['plates'] = I('post.plates');
            $update['some_sn'] = I('post.some_sn');
            $update['desc'] = I('post.desc');
            $update['up_name'] = I('post.account');
            $update['time'] = time();
            $res=M('baobei')->add($update);
            if ($res) {
               $result = array('status' => 1, 'msg' => '申报成功!');
            }else{
               $result = array('status' => -3, 'msg' => '系统故障，申报失败，请稍后再试!');
            }
        }

        $this->ajaxReturn($result);
        return $this->fetch();
    }
    
}