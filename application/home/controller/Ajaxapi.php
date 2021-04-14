<?php
/**
ipr网站接口数据接收代码
 */

namespace app\home\controller;

class Ajaxapi extends Base
{
     // 在线客服
    public function kefu(){
         $num=$_GET['num'];

         $pingtai=$_GET['pingtai'];//短视频1 自媒体2 微博3 微信4
         if ($pingtai) {
            $sjwhere['is_online'] = array('like','%'.$pingtai.'%');
         }else{
            $sjwhere['is_online'] = array('neq','0');
         }
         $sjwhere['type'] = 0;
         $sjlx=M('admin')->where($sjwhere)->order('rand()')->limit($num)->select();
         $data=json_encode($sjlx);
         return $data;
    }

    public function getip(){
        // 获取客户端IP地址
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos){
                unset($arr[$pos]);
            }
            $ip = trim($arr[0]);
        }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
        return $ip;                      
        // 获取客户端IP地址
    }

    public function fangwen_log($desc){
         // 访问记录
        $ip=$this->getip();
        $time=time();
        $jbegintime=mktime(0,0,0,date('m'),date('d'),date('Y'));//今天开始时间
        $memwhere['ip']=$ip;
        $memwhere['start_time']=$jbegintime;
        $is_fangwen=M('member')->where($memwhere)->find();
       
        if ($is_fangwen) { 
            if ($is_fangwen['count']>20) {
                return '对不起，操作频繁，请稍后再试！';exit();
            }
            $data['ip'] = $ip;
            $data['end_time'] = $time;
            $data['beizhu'] = "'".$desc."'";
            $data['count'] = $is_fangwen['count']+1;
            $member = M('member')->where($memwhere)->save($data);
        }else{
            $data['ip'] = $ip;
            $data['beizhu'] = "'".$desc."'";
            $data['start_time'] = $jbegintime;
            $data['end_time'] = $time;
            $data['count'] = 1;
            $member = M('member')->add($data);
        }
        // 访问记录
    }

    // 评估
    public function pinggu(){
        $tel = I('post.tel');

        if ($tel) {
        $userNames = strtoupper(I('post.name'));
        $userName=preg_replace('# #','',$userNames);
        $contents = strtoupper(I('post.content'));
        $content=preg_replace('# #','',$contents);
        $userPhones = strtoupper(I('post.userPhone'));
        $userPhone=preg_replace('# #','',$userPhones);
        $userQQs = strtoupper(I('post.userQQ'));
        $userQQ=preg_replace('# #','',$userQQs);
        $userWXs = strtoupper(I('post.userWX'));
        $userWX=preg_replace('# #','',$userWXs);
        $leixings = strtoupper(I('post.type'));
        $leixing=preg_replace('# #','',$leixings);

        $ip=$this->getip();
        $fangwen_log=$this->fangwen_log('提交咨询');
        if ($fangwen_log) {
            $this->ajaxReturn(['status' => -1, 'msg' => $fangwen_log, 'result' =>[]]);
        }                 

         //要过滤的非法字符 
        $ArrFiltrate=array('SELECT','INSERT','UPDATE','DELETE','ALERT','SCRIPT','SAVE','ADD',';','<','JS','HTTP','EXE','__','||','&','OR','+','-','=','WAITFOR ','DELAY','$','.','/','(','WINDOWS','WRITE','*','%'); 

        foreach ($ArrFiltrate as $key=>$value){

            if (strstr($userName,$value)){ 
                $this->ajaxReturn(['status' => -1, 'msg' => '内容含非法字符，请核实后发布', 'result' =>[]]);
            }
            if (strstr($content,$value)){ 
                $this->ajaxReturn(['status' => -1, 'msg' => '内容含非法字符，请核实后发布', 'result' =>[]]);
            }
            if (strstr($userPhone,$value)){ 
                $this->ajaxReturn(['status' => -1, 'msg' => '内容含非法字符，请核实后发布', 'result' =>[]]);
            }
            if (strstr($userQQ,$value)){ 
                $this->ajaxReturn(['status' => -1, 'msg' => '内容含非法字符，请核实后发布', 'result' =>[]]);
            }
            if (strstr($userWX,$value)){ 
                $this->ajaxReturn(['status' => -1, 'msg' => '内容含非法字符，请核实后发布', 'result' =>[]]);
            }
            if (strstr($leixing,$value)){ 
                $this->ajaxReturn(['status' => -1, 'msg' => '内容含非法字符，请核实后发布', 'result' =>[]]);
            }
        }

            $data['name'] =$userName;
            $data['ip'] =$ip;
            $data['weixin'] = $userWX;
            $data['content'] = $content;
            $data['tel'] = $userPhone;
            $data['qq'] = $userQQ;
            $data['leixing'] = $leixing;
            $data['on_time'] = time();
            $qiugou = M('pinggu')->add($data);

            if ($qiugou) {
                $this->ajaxReturn(['status' => 1, 'msg' => '提交成功，我们将尽快给您回复，请保持电话畅通！', 'result' =>[]]);
            }else{
                $this->ajaxReturn(['status' => 1, 'msg' => '系统繁忙，请联系客服', 'result' =>[]]);
            }
        }
    }
  

}