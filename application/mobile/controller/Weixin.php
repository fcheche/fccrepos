<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: 当燃 2016-01-09
 */
namespace app\mobile\controller;

use Think\Db;
use app\common\logic\wechat\WechatUtil;

class Weixin extends MobileBase {
     /**
     * 获取微信用户openid
     */
    public function get_openid(){
       
            $openid = $_REQUEST['openid'];
            session('wx_openid',$openid);
            return $openid;
        
}