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
 * Author: IT宇宙人     
 * Date: 2015-09-09
 */
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\admin\logic\UsersLogic;
use app\admin\logic\SearchWordLogic;
use think\AjaxPage;
use think\Loader;
use think\Page;
use think\Db;

class Goodsdsp extends Base {

    public  $goods_type=1;// 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
    public  $goods_catgory=1;// 需要调用的分类 平台关联
    /**
     * 初始化操作
     */
    public function _initialize() {
        if (empty(session('admin_id'))) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        }

        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->getSortCategory($this->goods_catgory);//短视频1 自媒体2 微博3  微信4

        $whereattr['type_id']=$this->goods_catgory;
        $pingtaiList = Db::name('goods_attribute')->where($whereattr)->select(); // 所属平台列表

        $this->assign('pingtaiList', $pingtaiList);   
        $this->assign('categoryList',$categoryList);
        $this->assign('goods_type',$this->goods_type);
    }

    /**
     *  短视频列表
     */
    public function goodsList(){ 
        $this->assign('status', 0);
        return $this->fetch("goods/goodsList");
    }

     /**
     *  下架短视频列表
     **/
    public function deleteGoods(){
        $this->assign('status', 2);
        return $this->fetch("goods/deleteGoods");
    }

    /**
     *  我的短视频列表
     */
    public function myGoods(){      
       
        $this->assign('status', 1);

        return $this->fetch("goods/myGoods");
    }

    /**
     * 添加视频
     */
    public function addGoods()
    {
        // 查询所有主管
        $admin_corps = Db::name('admin_corps')->select();
        $this->assign('admin_corps', $admin_corps);

        // 查询所有员工 分了战队的
        $admins = Db::name('admin')->where("corps_id <> 0 and type = 0")->select();
        $this->assign('admins', $admins);
      
        return $this->fetch('goods/_addgoods');
    }

    
}