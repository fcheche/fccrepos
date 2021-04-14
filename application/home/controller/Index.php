<?php

namespace app\home\controller;
use think\Page;
use think\Verify;
use think\Image;
use think\Db;
use app\common\logic\UsersLogic;

class Index extends Base {

    public function _initialize() {
        parent::_initialize();
       
        $pingtaiList = Db::name('goods_attribute')->select(); // 所属平台列表
        $this->assign('pingtaiList', $pingtaiList);
    }

    //活动是否完成
    public function activity(){
            $time=time();
            $act_where['is_finished']=0;
            $act_where['start_time']=array('lt',$time);
            $act_where['end_time']=array('gt',$time);
            $act = M('goods_activity')->where($act_where)->find();
            // print_r(M('goods_activity')->getlastsql());
            if ($act) {
                return 0;
            }else{
                return 1;//活动结束
            }
    }
    
    public function dongtai(){
        $d = I('post.d');
        if ($d>20) {
            return '最多可查看20条数据';die;
        }
        if ($d) {
             // 公司动态
        $atwhere['is_open']=1;
        $atwhere['is_only_houtai']=0;
        $atwhere['cat_id']=19;
        $article=M('article')->where($atwhere)->order('publish_time','desc')->limit($d)->select(); 
        $this->ajaxReturn($article);
        }
    }

    public function news_z(){
        $d = I('post.d');
        if ($d>20) {
            return '最多可查看20条数据';die;
        }
        if ($d) {
        // 新闻资讯
        $tpwhere['topic_state']=2;
        $topic=M('topic')->where($tpwhere)->order('ctime','desc')->limit($d)->select(); 
        $this->ajaxReturn($topic);
        }
    }

     public function shcf(){
        $d = I('post.d');
        if ($d>20) {
            return '最多可查看20条数据';die;
        }
        if ($d) {
        // 售后采访
        $allshcf = Db::name('shcf')->limit($d)->order('on_time','desc')->select();
        $this->ajaxReturn($allshcf);
        }
    }

  
    public function admins(){
        $d = I('post.d');
        if ($d>20) {
            return '最多可查看20条数据';die;
        }
        if ($d) {
        // 随机获取在线人员
        $admins=M('admin')->where('leibie > 0 and (is_online != "0")')->order('rand()')->limit($d)->select();
        $this->ajaxReturn($admins);
        }
    }

    public function xinxing(){
        $d = I('post.d');
        if ($d==1) {
        // 当月业绩冠军
         $adminwhere['is_online']=array('neq','0');
         $xinxing=M('admin')->where($adminwhere)->order('dyyeji desc')->limit(1)->select();
         $this->ajaxReturn($xinxing[0]);
        }
    }


    public function index()
    {
        // 如果是手机跳转到 手机模块
        // if(isMobile()){
        //     header("Location: /m"); //跳转手机端
        //     exit;//确保重定向后，后续代码不会被执行 
        // }
        $where['parent_id']=1;
        $where['is_show']=1;
        $dspList = M("GoodsCategory")->where($where)->limit(28)->cache(true)->getField('id,name');//短视频1 自媒体2 微博3  微信4
        $this->assign('dspList', $dspList);

        $where['parent_id']=2;
        $where['is_show']=1;
        $zmtList = M("GoodsCategory")->where($where)->limit(28)->cache(true)->getField('id,name');//短视频1 自媒体2 微博3  微信4
        $this->assign('zmtList', $zmtList);

        $where['parent_id']=3;
        $where['is_show']=1;
        $wbList = M("GoodsCategory")->where($where)->limit(28)->cache(true)->getField('id,name');//短视频1 自媒体2 微博3  微信4
        $this->assign('wbList', $wbList);
        
        $where['parent_id']=4;
        $where['is_show']=1;
        $wxList = M("GoodsCategory")->where($where)->limit(28)->cache(true)->getField('id,name');//短视频1 自媒体2 微博3  微信4
        $this->assign('wxList', $wxList);

        return $this->fetch();
    }

    /**
     *  公告详情页
     */
    public function notice(){
        return $this->fetch();
    }
    
   

    /**
     * 猜你喜欢淘宝
     * @author lxl
     * @time 17-2-15
     */
    public function ajax_favorite(){
        $p = I('p/d',1);
        $i = I('i',5); //显示条数
        $time = time();
        $where = ['is_on_sale'=>1 ,'is_shenhe'=>1 ,'goods_type'=>2];
        $favourite_goods = Db::name('goods')->alias('a')->join('__GOODS_CATEGORY__ b', 'b.id = a.cat_id', 'LEFT')->where($where)->order('rand()')->page($p,$i)->cache(true,TPSHOP_CACHE_TIME)->select();//首页推荐商品
        $this->assign('favourite_goods',$favourite_goods);
        $this->assign('times', time());
        return $this->fetch();
    }

    
}