<?php
/**
 * tpshop
 */
namespace app\admin\controller;
use app\admin\logic\GoodsLogic;
use app\admin\logic\UsersLogic;
use app\admin\logic\SearchWordLogic;
use think\AjaxPage;
use think\Loader;
use think\Page;
use think\Db;

class Goodswb extends Base {

    public  $goods_type=3;// 短视频1 自媒体2 微博3 微信4  营销短视频11  营销自媒体12  营销微博13  营销微信14
    public  $goods_catgory=3;// 需要调用的分类 平台关联
    /**
     * 初始化操作
     */
    public function _initialize() {
        if (empty(session('admin_id'))) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        }
        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->getSortCategory($this->goods_catgory);//短视频1 自媒体2 微博3  微信4
       
        $this->assign('categoryList',$categoryList);
        $this->assign('goods_type',$this->goods_type);
    }

    /**
     *  微博列表
     */
    public function goodsList(){ 
     
        $this->assign('status', 0);

        return $this->fetch("goods/goodsList");
    }

     /**
     *  下架微博列表
     **/
    public function deleteGoods(){
          
        $this->assign('status', 2);

        return $this->fetch("goods/deleteGoods");
    }

    /**
     *  我的微博列表
     */
    public function myGoods(){      
       
        $this->assign('status', 1);

        return $this->fetch("goods/myGoods");
    }

    /**
     * 添加微博
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