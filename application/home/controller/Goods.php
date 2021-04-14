<?php
/**
数据展示 详情和收藏管理
 */

namespace app\home\controller;

use app\common\logic\FreightLogic;
use app\common\logic\GoodsPromFactory;
use app\common\logic\SearchWordLogic;
use app\common\logic\GoodsLogic;
use app\common\model\Combination;
use app\common\model\SpecGoodsPrice;
use think\AjaxPage;
use think\Page;
use think\Verify;
use think\Db;
use think\Cookie;

class Goods extends Base
{
    /**
     * 详情页
     */
    public function goodsInfo()
    {
        $goodsLogic = new GoodsLogic();
        $goods_id = I("get.id/d");
        $goods = M('goods')->where("goods_id", $goods_id)->find();
        if (empty($goods) || $goods['is_on_sale'] != 1 || $goods['is_delete'] == 1) {
            $this->error('该商品已经售罄或已下架');
        }
        
        if (cookie('user_id')) {
            $goodsLogic->add_visit_log(cookie('user_id'), $goods);
        }
        $goods['pingtai'] = Db::name('goods_attribute')->where("attr_id", $goods['pingtai_id'])->cache(true)->getField('attr_name'); // 所属平台列表
       
        $goods['catname'] = M("GoodsCategory")->where("id", $goods['cat_id'])->cache(true)->getField('name');//所属行业类型
        
        //随机选择联系客服 视频1 自媒体2 微博3 微信4 $goods['goods_type']
        $where = ['admin_id'=>$goods['admin_id'],'is_online'=>['like','%'.$goods['goods_type'].'%']];//视频1 自媒体2 微博3 微信4 $goods['goods_type']
        $admin=M('admin')->where($where)->find();
        if ($admin) {
            $corps_id=$admin['corps_id'];
        }else{
            $admin=M('admin')->where('is_online','like','%'.$goods['goods_type'].'%')->order('rand()')->limit(1)->find();
            $corps_id=$admin['corps_id'];
        }
        $zhuguan=M('admin_corps')->where('corps_id',$corps_id)->getField('zhuguan_name');
        $zhuguanqq=M('admin')->where('user_name',$zhuguan)->getField('qq');
        $goods['adminzhuguanqq']=$zhuguanqq;
        $goods['adminqq']=$admin['qq'];
        $goods['headpic']=$admin['head_pic'];
        $goods['erweipic']=$admin['erwei_pic'];
        $goods['admintel']=$admin['tel'];

        $this->assign('goods', $goods);
        return $this->fetch();
    }


    /**
     * 用户收藏商品
     */
    public function collect_goods()
    {
        $goods_ids = I('goods_ids/a', []);
        if (empty($goods_ids)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '请至少选择一个需要收藏的商品', 'result' => '']);
        }

        $goodsLogic = new GoodsLogic();
        $result = [];
        foreach ($goods_ids as $key => $val) {
            $result[] = $goodsLogic->collect_goods(cookie('user_id'), $val);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '已添加至我的收藏', 'result' => $result]);
    }
}