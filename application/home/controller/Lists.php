<?php
/**
列表展示
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

class Lists extends Base
{
    public function index()
    {
        $sort = I('sort'); // 排序
        $arr_sort = array(1=>['on_time'=> 'asc'],2=>['on_time'=> 'desc'],3=>['shop_price'=> 'asc'],4=>['shop_price'=> 'desc']);
        if ($sort) {
            $sort_asc=$arr_sort[$sort];
        }else{
            $sort_asc=['top_time'=> 'desc'];
        }
        // 大类0-平台1-小类2-价格3-粉丝4-认证类型5-注册时间6-关键词7-微博等级8-是否直播9-是否处罚10-是否橱窗11
        $arr = explode('-',I('l'));
        //价格数组
        $start_prices = array(1 => 0.1,2 => 5000,3 => 10000,4 => 20000,5 => 50000,6 => 80000,7 => 100000,8 => 150000,9 => 200000);
        $end_prices = array(1 => 5000,2 => 10000,3 => 20000,4 => 50000,5 => 80000,6 => 100000,7 => 150000,8 => 200000,9 => 10000000);

        //粉丝量数组
        $start_fensis = array(1 => 0.1,2 => 10000,3 => 50000,4 => 100000,5 => 200000,6 => 500000,7 => 1000000);
        $end_fensis = array(1 => 10000,2 => 50000,3 => 100000,4 => 200000,5 => 500000,6 => 1000000,7 => 10000000);
       
        $type=$arr[0];//(短视频1 自媒体2 微博3 微信4
        $pingtai_id=$arr[1];
        $cat_id=$arr[2];
        if ($cat_id) {
            $type = M("GoodsCategory")->where("id",$cat_id)->cache(true)->getField('parent_id');
        }
        $start_price=$start_prices[$arr[3]];//价格起始值
        $end_price=$end_prices[$arr[3]];//价格结束值
        $start_fensi_num=$start_fensis[$arr[4]];
        $end_fensi_num=$end_fensis[$arr[4]];
        $renzheng_type=$arr[5];
        $zhuce_time=$arr[6];
        $key_words=$arr[7];//关键词
        $wb_dengji=$arr[8];
        $is_zhibo=$arr[9];
        $is_chufa=$arr[10];
        $is_chuchuang=$arr[11];
       
        if ($key_words) {
            $where['goods_sn|goods_name|goods_remark']=array('like','%'.$key_words.'%'); //关键词
        }
        if ($start_price&&$end_price) {
            $where['shop_price']=array(array('egt',$start_price),array('elt',$end_price));
        }
        if ($start_fensi_num&&$end_fensi_num) {
            $where['fensi_num']=array(array('egt',$start_fensi_num),array('elt',$end_fensi_num));
        }
        if ($type) {
            $whereattr['type_id']=$type;
            $wherecat['parent_id']=$type;
            $where['goods_type']=$type;
        }else{
            $whereattr['type_id']=1;//默认短视频
            $wherecat['parent_id']=1;//默认短视频
            $arr['type_id']=1;
            $arr['parent_id']=1;
        }
        $arr['sort']=$sort;
        $this->assign('urls', $arr);

        $pingtaiList = Db::name('goods_attribute')->where($whereattr)->order(["order" => "asc"])->cache(true)->select(); // 所属平台列表
        $this->assign('pingtaiList', $pingtaiList);

        $wherecat['is_show']=1;
        $categoryList = M("GoodsCategory")->where($wherecat)->cache(true)->getField('id,name,parent_id');//短视频1 自媒体2 微博3  微信4
        $this->assign('categoryList', $categoryList);

        if ($pingtai_id) {
            $where['pingtai_id']=$pingtai_id;
        }
        if ($cat_id) {
            $where['cat_id']=$cat_id;
        }
        if ($renzheng_type) {
            $where['renzheng_type']=$renzheng_type;
        }
        if ($zhuce_time) {
            $where['zhuce_time']=$zhuce_time;
        }
        if ($is_chufa) {
            $where['is_chufa']=$is_chufa;
        }
        if ($is_chuchuang) {
            $where['is_chuchuang']=$is_chuchuang;
        }
        if ($is_zhibo) {
            $where['is_zhibo']=$is_zhibo;
        }
        if ($wb_dengji) {
            $where['wb_dengji']=$wb_dengji;
        }
        // print_r($where);
        $where['is_delete']=0;
        $where['is_on_sale']=1;
        $count = M('goods')->where($where)->count();
        $Page = new Page($count,12);
        $show = $Page->show();
        $ress = Db::name('goods')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->order($sort_asc)->select();

        foreach ($ress as $key => $value) {
            $map['admin_id']=$value['admin_id'];
            $map['is_online']=['like','%'.$type.'%'];
            $value['qq']=M('admin')->where($map)->getField('qq');
            if (!$value['qq']) {
                $value['qq']=M('admin')->where('is_online','like','%'.$type.'%')->order('rand()')->limit(1)->getField('qq');
            }
            $res[]=$value;
        }

        $this->assign('page',$show);
        $this->assign('goods', $res);
        $html = $this->fetch();
        S($key, $html);
        return $html;
    }

    public function ajax_goods(){
        $sort = I('sort', 'top_time'); // 排序
        $sort_asc = I('sort_asc', 'desc'); // 排序
        
        $where['is_delete']=0;
        $where['is_hot']=1;
        $where['is_on_sale']=1;
        if (I('pingtai_id')) {
        $where['pingtai_id']=I('pingtai_id');
        }
        if (I('goods_type')) {
        $where['goods_type']=I('goods_type');
        }
     
        $ress = Db::name('goods')->where($where)->limit(6)->order([$sort => $sort_asc])->select();
        // print_r(Db::name('goods')->getlastsql());
        foreach ($ress as $key => $value) {
            $value['catname']=M("GoodsCategory")->where("id",$value['cat_id'])->cache(true)->getField('name');
            $map['admin_id']=$value['admin_id'];
            $map['is_online']=['like','%'.$type.'%'];
            $value['qq']=M('admin')->where($map)->getField('qq');
            if (!$value['qq']) {
                $value['qq']=M('admin')->where('is_online','like','%'.$type.'%')->order('rand()')->limit(1)->getField('qq');
            }
            $res[]=$value;
        }

        if (!$res) {
            $this->ajaxReturn("数据为空");
        }else{
            $this->ajaxReturn($res);
        }
    }
  
}
