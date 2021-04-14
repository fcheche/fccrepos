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

class Goods extends Base {
    /**
     * 初始化操作
     */
    public function _initialize() {
        if (empty(session('admin_id'))) {
            $this->error('登录过时！请重新登录',U('Admin/logout'));
        } 
    }

     /**
     * 添加编辑
     */
    public function save(){

        $data = input('post.');

        // 备注
        if (empty($data['beizhu'])){
            unset($data['beizhu']); 
        }else{
                $add['goods_id'] = $data['goods_id'];
                $add['beizhu'] = trim($data['beizhu']);

                $admin_info = getAdminInfo(session('admin_id'));
                $add['bz_name'] = $admin_info['user_name'];
                $add['bz_time'] = time();
                $row =  M('goods_beizhu')->add($add);

                $return_arr = ['status' => 1, 'msg' => '备注成功'];
                $this->ajaxReturn($return_arr);
        }
        // 备注

        if (empty($data['sellername'])) {
            $this->ajaxReturn(['status' => -1, 'msg' => '卖家账号未填写！']);
        }
        $datas['sellername']= preg_replace('# #','',$data['sellername']);

        $sellerid=M('users')->where('nickname', $data['sellername'])->getField('user_id');
        if ($sellerid) {
            $data['sellerid']= $sellerid;
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '卖家账号有误']);
        }
        if (input('post.on_time')) {
            $data['on_time'] = strtotime(input('post.on_time'));
        }else{
            $data['on_time'] = time();
        }
            $data['top_time'] = time();
            $data['goods_name']=preg_replace('# #','',$data['goods_name']);
            $data['goods_remark']=preg_replace('# #','',$data['goods_remark']);
    
        if ($data['goods_id']) {
            $a=M('goods')->where('goods_id', $data['goods_id'])->save($data);
        }else{
            if ($data['goods_type']>10) {
                $leibie=$data['goods_type'];
            }else{
                $leibie='0'.$data['goods_type'];
            }
            
            $data['goods_sn'] = "MD".str_pad(time(),7,"0",STR_PAD_LEFT).rand(10,99).$leibie;
            $a=M('goods')->add($data);
        }
       
       if ($a) {
          $return_arr = ['status' => 1, 'msg' => '操作成功'];
       }else{
          $return_arr = ['status' => -1, 'msg' => '添加失败'];
       }
        $this->ajaxReturn($return_arr);
    }

    public function saveimages(){
        // 店铺图片相册  图册
        $goods_images = I('goods_images/a');
        $goods_id = I('goods_id');
        if(count($goods_images) > 1)
        {
            array_pop($goods_images); // 弹出最后一个
            $goodsImagesArr = M('GoodsImages')->where("goods_id = $goods_id")->getField('img_id,image_url'); // 查出所有已经存在的图片

            // 删除图片
            foreach($goodsImagesArr as $key => $val)
            {
                if(!in_array($val, $goods_images)) M('GoodsImages')->where("img_id = {$key}")->delete();
            }
            // 添加图片
            foreach($goods_images as $key => $val)
            {
                if($val == null)  continue;
                if(!in_array($val, $goodsImagesArr))
                {
                    $data = array('goods_id' => $goods_id,'image_url' => $val);
                    M("GoodsImages")->insert($data); // 实例化User对象
                }
            }
        }
    }

    /**
     *  商品列表
     */
    public function ajaxGoodsList(){     
   
        $goods_type=I('goods_type');
        $this->assign('goods_type', $goods_type);

        $where = 'is_delete = 0 and goods_type = '.$goods_type;//未下架

        $user_name = M('admin')->where(['admin_id'=>session('admin_id')])->getField('user_name');
        $status=I('status',0);//1我的 2下架的
        $this->assign('status', $status);

        if ($status==2) {
            $where = 'is_delete = 1 and goods_type = '.$goods_type;//已下架
        }

        if ($status==1) {
            $where .= " and admin_id = '".session('admin_id')."'"; // 归属人
        }
        
        $pingtai_id=I('pingtai_id');
        $is_on_sale=I('is_on_sale');
        $zhuce_time=I('zhuce_time');
        $is_zhibo=I('is_zhibo');
        $is_chuchuang=I('is_chuchuang');   
        $renzheng_type=I('renzheng_type'); 
        $is_chufa=I('is_chufa');   
        $cat_id = I('cat_id');
        if($cat_id > 0)
        {
            $where .= " and cat_id = ".$cat_id; // 行业分类
        }
        if($pingtai_id > 0)
        {
            $where .= " and pingtai_id = ".$pingtai_id; // 所属平台
        }
        if($is_on_sale > 0)
        {
            $where .= " and is_on_sale = ".$is_on_sale; // 状态
        }
        if($zhuce_time > 0)
        {
            $where .= " and zhuce_time = ".$zhuce_time; // 注册时间
        }
        if($is_zhibo > 0)
        {
            $where .= " and is_zhibo = ".$is_zhibo; // 直播状态
        }
        if($is_chuchuang > 0)
        {
            $where .= " and is_chuchuang = ".$is_chuchuang; // 是否开通橱窗
        }
        if($renzheng_type!='')
        {
            $where .= " and renzheng_type = ".$renzheng_type; // 认证主体
        }
        if($is_chufa > 0)
        {
            $where .= " and is_chufa = ".$is_chufa; // 是否处罚
        }

        // 关键词搜索               
        $key_word = I('key_word') ? trim(I('key_word')) : '';
        if($key_word)
        {
            $where = "$where and (goods_name like '%$key_word%' or wangwang like '%$key_word%' or sellername like '%$key_word%' or goods_sn like '%$key_word%')" ;
        }

        // 粉丝区间
        $fensi_qujian=I('fensi_qujian');
        if ($fensi_qujian==1) {
            $where = "$where and (fensi_num < 50001)" ;
        }
        if ($fensi_qujian==2) {
            $where = "$where and (fensi_num < 100001 and fensi_num > 50000)" ;
        }
        if ($fensi_qujian==3) {
            $where = "$where and (fensi_num < 200001 and fensi_num > 100000)" ;
        }
        if ($fensi_qujian==4) {
            $where = "$where and (fensi_num < 300001 and fensi_num > 200000)" ;
        }
        if ($fensi_qujian==5) {
            $where = "$where and (fensi_num < 500001 and fensi_num > 300000)" ;
        }
        if ($fensi_qujian==6) {
            $where = "$where and (fensi_num < 1000001 and fensi_num > 500000)" ;
        }
        if ($fensi_qujian==7) {
            $where = "$where and (fensi_num < 2000001 and fensi_num > 1000000)" ;
        }
        if ($fensi_qujian==8) {
            $where = "$where and (fensi_num > 2000000)" ;
        }

        // 价格区间              
        $priceqj = I('price_qujian');
        if ($priceqj==1) {
            $where = "$where and (shop_price < 5001)" ;
        }
        if ($priceqj==2) {
            $where = "$where and (shop_price < 10001 and shop_price > 5000)" ;
        }
        if ($priceqj==3) {
            $where = "$where and (shop_price < 20001 and shop_price > 10000)" ;
        }
        if ($priceqj==4) {
            $where = "$where and (shop_price < 30001 and shop_price > 20000)" ;
        }
        if ($priceqj==5) {
            $where = "$where and (shop_price < 50001 and shop_price > 30000)" ;
        }
        if ($priceqj==6) {
            $where = "$where and (shop_price < 80001 and shop_price > 50000)" ;
        }
        if ($priceqj==7) {
            $where = "$where and (shop_price < 100001 and shop_price > 80000)" ;
        }
        if ($priceqj==8) {
            $where = "$where and (shop_price < 150001 and shop_price > 100000)" ;
        }
        if ($priceqj==9) {
            $where = "$where and (shop_price < 200001 and shop_price > 150000)" ;
        }
        if ($priceqj==10) {
            $where = "$where and (shop_price > 200000)" ;
        }

        $begin_time=strtotime(I('start_time',"2018-01-01"));
        $end_time=strtotime(I('end_time',date("Y-m-d")+1));

        $where = "$where and (on_time < $end_time and on_time > $begin_time)" ;


        $orderby1 = I('orderby1',"goods_id");

        $orderby2 = I('orderby2',"desc");
        
        $count = M('Goods')->where($where)->count();
       
        $Page  = new AjaxPage($count,15);
      
        $show = $Page->show();
        
        $order_str = "`$orderby1` $orderby2";
       
        $goodsList = M('Goods')->where($where)->order($order_str)->limit($Page->firstRow.','.$Page->listRows)->select();
      
        foreach ($goodsList as $kg=>$vg){
            $where_in['admin_id']=session('admin_id');
            $where_in['goods_id']=$vg['goods_id'];
            $vg['pingtai_name'] = Db::name('goods_attribute')->where(['attr_id'=>$vg['pingtai_id']])->getField('attr_name');
            $vg['cat_name'] = Db::name('goods_category')->where(['id'=>$vg['cat_id']])->getField('name');
            $is_in = Db::name('goods_collect')->where($where_in)->count();
            if($is_in>0){
                $vg['is_in'] = 1;
            }else{
                $vg['is_in'] = 0;
            }
            $goods_list[]=$vg;
        }
  
        $roleid = M('admin')->where(['admin_id'=>session('admin_id')])->getField('role_id');
     
        $detail = M('admin_role')->where("role_id",$roleid)->find();
        if ($detail['act_list']=='all') {
            $detail['act_list']="1,19";//系统 下架
        }
        $act_list = explode(',', $detail['act_list']);
        $this->assign('act_list',$act_list);


        $this->assign('roleid', $roleid);
        $this->assign('user_name', $user_name);
        $this->assign('goodsList',$goods_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('count',$count);// 赋值分页输出
        return $this->fetch();
    }



    /**
     * 修改编辑
     */
    public function addEditGoods()
    {
        $goods_id = I('id');
        $act = I('act');
        $this->assign('act', $act);
        //是否查看过该条数据
        $admin_info = getAdminInfo(session('admin_id'));

        $where['is_chakan'] =['like', '%'.$admin_info['user_name'].'%'];
        $where['goods_id'] =$goods_id;
        $chakan=M("Goods")->where($where)->find();
       
        if (empty($chakan['is_chakan'])) {
           $update['is_chakan']=$admin_info['user_name'].','.$chakan['is_chakan'];
           M("Goods")->where(['goods_id'=>$goods_id])->save($update);  //记录查看
        }
        
        $admin_id = $admin_info['admin_id'];
        $roleid = M('admin')->where(['admin_id'=>$admin_id])->getField('role_id');
        $this->assign('roleid', $roleid);
        $this->assign('admin_info',$admin_info);    

        $ck_num = M('admin')->where(['admin_id'=>$admin_id])->getField('ck_num');
        $this->assign('ck_num', $ck_num);

        if($goods_id){
            $goods = M("Goods")->where('goods_id', $goods_id)->find();
            $goods['goods_images'] = M("GoodsImages")->where('goods_id', $goods_id)->select();
            $this->assign('goods', $goods);
        }else{
            $this->error('该数据不存在或有误!');
        }

        $GoodsLogic = new GoodsLogic();
        $categoryList = $GoodsLogic->getSortCategory(substr($goods['goods_type'], -1));//短视频1 自媒体2 微博3  微信4
        $pingtaiList = Db::name('goods_attribute')->where("type_id",$goods['goods_type'])->order('`order` asc,attr_id DESC')->select(); // 所属平台列表
        $admin_corps = Db::name('admin_corps')->select();// 查询所有主管
        $admins = Db::name('admin')->where("corps_id <> 0 and type = 0")->select();// 查询所有员工 分了战队的
        $this->assign('pingtaiList', $pingtaiList);   
        $this->assign('categoryList',$categoryList);
        $this->assign('admin_corps', $admin_corps);
        $this->assign('admins', $admins);

        $nickname=$goods['sellername'];
        $seller = D('users')->where(['nickname'=>$nickname])->find();//查询卖家信息
        $beizhus = Db::name('goods_beizhu')->where('goods_id', $goods_id)->select();// 查询该商品所有备注信息
        $goods_log = Db::name('goods_log')->where('goods_id', $goods_id)->select();// 查询该商品所有查看记录信息
      
        $this->assign('seller', $seller);
        $this->assign('beizhus', $beizhus);
        $this->assign('goods_log', $goods_log);
        $this->assign('todeytime', time());
        return $this->fetch('_goods');
    }


    /**
     * 用户收藏商品
     */
    public function Oncollect()
    {
        $goods_id = I('goods_id');
        if (empty($goods_id)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }

        $count = Db::name('goods_collect')->where("admin_id",session('admin_id'))->where("goods_id", $goods_id)->count();
        if($count > 0){
            $this->ajaxReturn(['status' => -1, 'msg' => '该商品已收藏', 'result' => array()]);
        }

        $result=Db::name('goods_collect')->add(array('goods_id'=>$goods_id,'admin_id'=>session('admin_id'), 'add_time'=>time()));
        if ($result) {
            $this->ajaxReturn(['status' => 1, 'msg' => '已添加至我的收藏', 'result' => $result]);
        }else{
            $this->ajaxReturn(['status' => -1, 'msg' => '添加失败', 'result' => array()]);
        }
       
    }
    
    /**
     *  商品分类列表
     */
    public function categoryList(){                
        $GoodsLogic = new GoodsLogic();               
        $cat_list = $GoodsLogic->goods_cat_list();
        // print_r($cat_list);
        $this->assign('cat_list',$cat_list);    
        return $this->fetch();
    }
      
    /**
     * 添加修改商品分类
     * 手动拷贝分类正则 ([\u4e00-\u9fa5/\w]+)  ('393','$1'), 
     * select * from tp_goods_category where id = 393
        select * from tp_goods_category where parent_id = 393
        update tp_goods_category  set parent_id_path = concat_ws('_','0_76_393',id),`level` = 3 where parent_id = 393
        insert into `tp_goods_category` (`parent_id`,`name`) values 
        ('393','时尚饰品'),
     */
    public function addEditCategory(){

            $GoodsLogic = new GoodsLogic();        
            if(IS_GET)
            {
                $goods_category_info = D('GoodsCategory')->where('id='.I('GET.id',0))->find();
                $this->assign('goods_category_info',$goods_category_info);
                
                $all_type = M('goods_category')->where("level<3")->getField('id,name,parent_id');//上级分类数据集，限制3级分类，那么只拿前两级作为上级选择
                if(!empty($all_type)){
                	$parent_id = empty($goods_category_info) ? I('parent_id',0) : $goods_category_info['parent_id'];
                	$all_type = $GoodsLogic->getCatTree($all_type);
                	$cat_select = $GoodsLogic->exportTree($all_type,0,$parent_id);
                	$this->assign('cat_select',$cat_select);
                }
                
                //$cat_list = M('goods_category')->where("parent_id = 0")->select(); 
                //$this->assign('cat_list',$cat_list);         
                return $this->fetch('_category');
                exit;
            }

            $GoodsCategory = D('GoodsCategory'); //

            $type = I('id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新                        
            //ajax提交验证
            if(I('is_ajax') == 1)
            {
                // 数据验证            
                $validate = \think\Loader::validate('GoodsCategory');
                if(!$validate->batch()->check(input('post.')))
                {                          
                    $error = $validate->getError();
                    $error_msg = array_values($error);
                    $return_arr = array(
                        'status' => -1,
                        'msg' => $error_msg[0],
                        'data' => $error,
                    );
                    $this->ajaxReturn($return_arr);
                } else {
                    $GoodsCategory->data(input('post.'),true); // 收集数据
                    $GoodsCategory->parent_id = I('parent_id');
                    
                    //查找同级分类是否有重复分类
                    $par_id = ($GoodsCategory->parent_id > 0) ? $GoodsCategory->parent_id : 0;
                    $sameCateWhere = ['parent_id'=>$par_id , 'name'=>$GoodsCategory['name']];
                    $GoodsCategory->id && $sameCateWhere['id'] = array('<>' , $GoodsCategory->id);
                    $same_cate = M('GoodsCategory')->where($sameCateWhere)->find();
                    if($same_cate){
                        $return_arr = array('status' => 0,'msg' => '同级已有相同分类存在','data' => '');
                        $this->ajaxReturn($return_arr);
                    }
                    
                    if ($GoodsCategory->id > 0 && $GoodsCategory->parent_id == $GoodsCategory->id) {
                        //  编辑
                        $return_arr = array('status' => 0,'msg' => '上级分类不能为自己','data' => '',);
                        $this->ajaxReturn($return_arr);
                    }
                    if($GoodsCategory->commission_rate > 100)
                    {
                        //  编辑
                        $return_arr = array('status' => -1,'msg'   => '分佣比例不得超过100%','data'  => '');
                        $this->ajaxReturn($return_arr);                        
                    }   
                   
                    if ($type == 2)
                    {
                        $GoodsCategory->isUpdate(true)->save(); // 写入数据到数据库
                        $GoodsLogic->refresh_cat(I('id'));
                    }
                    else
                    {
                        $GoodsCategory->save(); // 写入数据到数据库
                        $insert_id = $GoodsCategory->getLastInsID();
                        $GoodsLogic->refresh_cat($insert_id);
                    }
                    $url=I('url');
                    $return_arr = array(
                        'status' => 1,
                        'msg'   => '操作成功',
                        'data'  => array('url'=>''),
                    );
                    $this->ajaxReturn($return_arr);

                }  
            }

    }
  
    /**
     * 获取商品分类 的帅选属性 复选框
     */
    public function ajaxGetAttrList(){
        $GoodsLogic = new GoodsLogic();
        $_REQUEST['category_id'] = $_REQUEST['category_id'] ? $_REQUEST['category_id'] : 0;
        $filter_attr = M('GoodsCategory')->where("id = ".$_REQUEST['category_id'])->getField('filter_attr');        
        $filter_attr_arr = explode(',',$filter_attr);        
        $str = $GoodsLogic->GetAttrCheckboxList($_REQUEST['type_id'],$filter_attr_arr);          
        $str = $str ? $str : '没有可帅选的商品属性';
        exit($str);        
    }    
    
    /**
     * 删除分类
     */
    public function delGoodsCategory(){
        $ids = I('post.ids','');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'data'  =>'']);
        // 判断子分类
        $count = Db::name("goods_category")->where("parent_id = {$ids}")->count("id");
        $count > 0 && $this->ajaxReturn(['status' => -1,'msg' =>'该分类下还有分类不得删除!']);
        // 判断是否存在商品
        $goods_count = Db::name('Goods')->where("cat_id = {$ids}")->count('1');
        $goods_count > 0 && $this->ajaxReturn(['status' => -1,'msg' =>'该分类下有商品不得删除!']);
        // 删除分类
        DB::name('goods_category')->where('id',$ids)->delete();
        $this->ajaxReturn(['status' => 1,'msg' =>'操作成功','url'=>U('Admin/Goods/categoryList')]);
    }

   
//商品备注
    public function beizhugoods(){

    $admin_info = getAdminInfo(session('admin_id'));

               if ($_POST['beizhu']) {

                $beizhu = $_POST['beizhu'];
                $add['goods_id'] =$_POST['goods_id'];
                $add['beizhu'] = trim($beizhu);
                $add['bz_name'] = $admin_info['user_name'];
                $add['bz_time'] = time();
                $row =  M('goods_beizhu')->add($add);

                $return_arr = ['status' => 1, 'msg' => '备注成功'];
                $this->ajaxReturn($return_arr);
               }else{
                $return_arr = ['status' => -1, 'msg' => '请填写备注内容'];
                $this->ajaxReturn($return_arr);
               }
    }



    public function getCategoryguishu(){
        $corps_id = I('corps_id');
        $admins = Db::name('admin')->where(['corps_id'=>$corps_id])->select();

        $return_arr = ['status' => 1, 'msg' => '获取成功','result' => $admins];
        $this->ajaxReturn($return_arr);
    }


// 查看商品次数
     public  function ckgoods(){

        $begintime=date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y')));
        $todaytime=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $goodsid = I('goodsid');//查看商品id
        $admin_info = getAdminInfo(session('admin_id'));
        $admin_id = session('admin_id');//查看人员id

        //商品归属人
        $adminid=M('goods')->where(['goods_id'=>$goodsid])->getField('admin_id');
        if (session('admin_id')==$adminid) {
           $this->ajaxReturn(['status'=>1,'msg'=>'这是我挂的店，我随便看！']);
        }

        //查看记录
                $add['goods_id'] = $goodsid;
                $add['admin_name'] = $admin_info['user_name'];
                $add['time'] = time();
                $row =  M('goods_log')->add($add);
        //查看记录

        $update['ck_time']=$todaytime;
        $update['ck_goodsid']=$goodsid;

        $info = M('admin')->where(['admin_id'=>$admin_id])->find();//获取所有查看过商品id
        $goodsids=explode(",", $info['ck_goodsid']);

        $ck_num_res = M('admin')->where(['admin_id'=>$admin_id])->getField('ck_num');//获取当天已查看数
        $ck_goods_count = M('admin')->where(['admin_id'=>$admin_id])->getField('ck_goods_count');//获取权限内最多查看数
        
        //判断是否为今天的时间
        if ($todaytime!=$info['ck_time']){
            $update['ck_num']=1;
            M('admin')->where(['admin_id'=>$admin_id])->save($update);
        }

         //判断今天是否查看过该商品
            if(in_array($goodsid,$goodsids)){
                $cknumres = M('admin')->where(['admin_id'=>$admin_id])->getField('ck_num');
                $this->ajaxReturn(['status'=>1,'msg'=>'谢谢你，又回来啦！']); 
            }else{

                 if ($ck_num_res>=$ck_goods_count) {
                    $ckres="哎哟~你已经看人家".$ck_num_res."次了，还没看够啊？大爷明天再来吧！";
                    $status=2;
                   $this->ajaxReturn(['status'=>$status,'msg'=>$ckres]);
                    }else{
                        $shengyu=$ck_goods_count-$ck_num_res;
                        if ($shengyu<10) {
                           $ckres="老板，你还可以看我".$shengyu."次哦，机会不多哟！";
                           $status=1;
                        }else{
                            $ckres="我的天哪！你居然还有".$shengyu."次机会看人家！";
                           $status=1;
                        }
                        
                    }
                //如果没有查看过就记录新的查看
                $update['ck_goodsid']=$goodsid.','.$info['ck_goodsid'];
                $res1=M('admin')->where(['admin_id'=>$admin_id])->save($update);
                if ($res1) {
                    $ck_num=M('admin')->where(['admin_id'=>$admin_id])->setInc('ck_num',1);
                }
            }

      $this->ajaxReturn(['status'=>$status,'msg'=>$ckres]);

    }


    // 审核商品
     public  function goods_shenhe(){

        $ctl = CONTROLLER_NAME;
        $act = ACTION_NAME;
        $act_list = session('act_list');

        $right = M('system_menu')->where("id", "in", $act_list)->cache(true)->getField('right',true);
        $role_right = '';
        foreach ($right as $val){
            $role_right .= $val.',';
        }
        $role_right = explode(',', $role_right);
        //检查是否拥有此操作权限
        if(!in_array($ctl.'@'.$act, $role_right)&&$act_list!='all'){
            $this->ajaxReturn(['status'=>-1,'msg'=>'您没有操作权限,请联系超级管理员分配权限']);
        }


        $admin_info = getAdminInfo(session('admin_id'));
        $user_name=$admin_info['user_name'];
        $goodsid = I('goodsid');

        $GoodsLogic = new GoodsLogic();
        $Goods = new \app\common\model\Goods();

        $thisgoods=$Goods->where('goods_id', $goodsid)->find();
        if (empty($thisgoods['sellername'])||empty($thisgoods['pingtai_id'])||empty($thisgoods['cat_id'])||empty($thisgoods['goods_name'])||empty($thisgoods['shop_price'])) {
            $this->ajaxReturn(['status'=>-1,'msg'=>'必填项没有填写，请认真审核后操作！']);
        }

        $update['is_on_sale']=1;
        $update['shenhe']=$user_name;
        $goods = $Goods->where('goods_id', $goodsid)->save($update);

        if ($goods) {
           $msgs="审核成功";
           $status=1;
        }else{
            $msgs="系统繁忙，请稍后再试";
            $status=2;
        }

        $this->ajaxReturn(['status'=>$status,'msg'=>$msgs]);

    }


       // 验证会员账号是否存在
     public  function hyzhcc(){
        $hyzh = I('filename');

        $where['nickname']=$hyzh;
        
        $users = M('users')->where($where)->find();

        if ($users) {
           
           $status=1;
        }else{
            $wangwangres="没有查到该账号，添加账号！";
            $status=2;
        }

        $this->ajaxReturn(['status'=>$status,'msg'=>$wangwangres]);

    }

     
 // 旺旺查询重复
     public  function wangwangcc(){
        $wangwang = I('filename');

        $GoodsLogic = new GoodsLogic();
        $Goods = new \app\common\model\Goods();
        $where['wangwang']=$wangwang;
        // $where['is_delete']=0;
        // $where['is_on_sale'] = array('NEQ',2);
        $goods = $Goods->where($where)->find();

        if ($goods) {
            if ($goods['is_on_sale']==1) {
                $wangwangres="该旺旺目前在售状态，请查证后录入！";
            }
            if ($goods['is_on_sale']==0) {
                $wangwangres="该旺旺目前未售状态，请查证后录入！";
            }
            if ($goods['is_delete']==1) {
                $wangwangres="该旺旺目前下架状态，请查证后录入！";
            }
            if ($goods['is_on_sale']==2) {
                $wangwangres="该旺旺目前已售状态，请查证后录入！";
            }
           $status=1;
        }else{
            $wangwangres="该旺旺目前不存在，可以录入！";
            $status=1;
        }

        $this->ajaxReturn(['status'=>$status,'msg'=>$wangwangres]);
    }


    /**
     * 商品类型  用于设置商品的属性
     */
    public function goodsTypeList(){
        $model = M("GoodsType");                
        $count = $model->count();        
        $Page = $pager = new Page($count,14);
        $show  = $Page->show();
        $goodsTypeList = $model->order("id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('pager',$pager);
        $this->assign('show',$show);
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch('goodsTypeList');
    }

    /**
     * 添加修改编辑  商品属性类型
     */
    public function addEditGoodsType()
    {
        $id = $this->request->param('id', 0);
        $model = M("GoodsType");
        if (IS_POST) {
            $data = $this->request->post();
            if ($id)
                DB::name('GoodsType')->update($data);
            else
                DB::name('GoodsType')->insert($data);

            $this->success("操作成功!!!", U('Admin/Goods/goodsTypeList'));
            exit;
        }
        $goodsType = $model->find($id);
        $this->assign('goodsType', $goodsType);
        return $this->fetch('_goodsType');
    }
    
    /**
     * 商品属性列表
     */
    public function goodsAttributeList(){       
        $goodsTypeList = M("GoodsType")->select();
        $this->assign('goodsTypeList',$goodsTypeList);
        return $this->fetch();
    }   
    
    /**
     *  商品属性列表
     */
    public function ajaxGoodsAttributeList(){            
        //ob_start('ob_gzhandler'); // 页面压缩输出
        $where = ' 1 = 1 '; // 搜索条件                        
        I('type_id')   && $where = "$where and type_id = ".I('type_id') ;                
        // 关键词搜索               
        $model = M('GoodsAttribute');
        $count = $model->where($where)->count();
        $Page       = new AjaxPage($count,13);
        $show = $Page->show();
        $goodsAttributeList = $model->where($where)->order('`order` desc,attr_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        $goodsTypeList = M("GoodsType")->getField('id,name');
        $attr_input_type = array(0=>'手工录入',1=>' 从列表中选择',2=>' 多行文本框');
        $this->assign('attr_input_type',$attr_input_type);
        $this->assign('goodsTypeList',$goodsTypeList);        
        $this->assign('goodsAttributeList',$goodsAttributeList);
        $this->assign('page',$show);// 赋值分页输出
        return $this->fetch();
    }   
    
    /**
     * 添加修改编辑  商品属性
     */
    public  function addEditGoodsAttribute(){
                        
            $model = D("GoodsAttribute");                      
            $type = I('attr_id') > 0 ? 2 : 1; // 标识自动验证时的 场景 1 表示插入 2 表示更新         
            $attr_values = str_replace('_', '', I('attr_values')); // 替换特殊字符
            $attr_values = str_replace('@', '', $attr_values); // 替换特殊字符            
            $attr_values = trim($attr_values);
            
            $post_data = input('post.');
            $post_data['attr_values'] = $attr_values;
            
            if((I('is_ajax') == 1) && IS_POST)//ajax提交验证
            {                                
                    // 数据验证            
                    $validate = \think\Loader::validate('GoodsAttribute');
                    if(!$validate->batch()->check($post_data))
                    {                          
                        $error = $validate->getError();
                        $error_msg = array_values($error);
                        $return_arr = array(
                            'status' => -1,
                            'msg' => $error_msg[0],
                            'data' => $error,
                        );
                        $this->ajaxReturn($return_arr);
                    } else {     
                             $model->data($post_data,true); // 收集数据
                            
                             if ($type == 2)
                             {                                 
                                 $model->isUpdate(true)->save(); // 写入数据到数据库                         
                             }
                             else
                             {
                                 $model->save(); // 写入数据到数据库
                                 $insert_id = $model->getLastInsID();                        
                             }
                             $return_arr = array(
                                 'status' => 1,
                                 'msg'   => '操作成功',                        
                                 'data'  => array('url'=>U('Admin/Goods/goodsAttributeList')),
                             );
                             $this->ajaxReturn($return_arr);
                }  
            }                
           // 点击过来编辑时                 
           $attr_id = I('attr_id/d',0);  
           $goodsTypeList = M("GoodsType")->select();           
           $goodsAttribute = $model->find($attr_id);           
           $this->assign('goodsTypeList',$goodsTypeList);                   
           $this->assign('goodsAttribute',$goodsAttribute);
           return $this->fetch('_goodsAttribute');
    }  
    
    /**
     * 更改指定表的指定字段
     */
    public function updateField(){
        $primary = array(
                'goods' => 'goods_id',
                'goods_category' => 'id',
                'brand' => 'id',            
                'goods_attribute' => 'attr_id',
        		'ad' =>'ad_id',            
        );        
        $model = D($_POST['table']);
        $model->$primary[$_POST['table']] = $_POST['id'];
        $model->$_POST['field'] = $_POST['value'];        
        $model->save();   
        $return_arr = array(
            'status' => 1,
            'msg'   => '操作成功',                        
            'data'  => array('url'=>U('Admin/Goods/goodsAttributeList')),
        );
        $this->ajaxReturn($return_arr);
    }

    /**
     * 动态获取商品属性输入框 根据不同的数据返回不同的输入框类型
     */
    public function ajaxGetAttrInput(){
        $GoodsLogic = new GoodsLogic();
        $str = $GoodsLogic->getAttrInput($_REQUEST['goods_id'],$_REQUEST['type_id']);
        exit($str);
    }

     /**
     * 删除商品
     */
    public function huishouzhan()
    {
        $goods_id = I('post.id');
        empty($goods_id) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'data'  =>'']);
       
        // 判断此商品是否有订单
        $order_goods_id = Db::name('order_list')->where('goods_id',$goods_id)->getField('goods_id');
        if($order_goods_id)
        {
            $this->ajaxReturn(['status' => -1,'msg' =>"ID为【{$order_goods_id}】的商品有订单,不得删除!",'data'  =>'']);
        }
        
        // 删除此商品     
        M("Goods")->where('goods_id',$goods_id)->delete();  //商品表
        M("cart")->where('goods_id',$goods_id)->delete();  // 购物车
        M("goods_images")->where('goods_id',$goods_id)->delete();  //商品相册
        M("goods_attr")->where('goods_id',$goods_id)->delete();  //商品属性
        M("goods_collect")->where('goods_id',$goods_id)->delete();  //商品收藏
        M("goods_visit")->where('goods_id',$goods_id)->delete();  //商品浏览记录
        M("goods_beizhu")->where('goods_id',$goods_id)->delete();  //商品备注记录

        $this->success("操作成功!!!");
    }

    /**
     * 删除店铺相册图
     */
    public function del_goods_images()
    {
        $path = I('filename','');
        M('goods_images')->where("image_url = '$path'")->delete();
    }
        
    /**
     * 下架到回收站
     */
    public function delGoods()
    {
        $goods_id = I('post.ids','');
        empty($goods_id) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！",'data'  =>'']);

        // 判断此商品是否在售（is_on_sale=1)
        $is_on_sale=M("Goods")->where('goods_id',$goods_id)->getField('is_on_sale');
       
        if($is_on_sale==1)
        {
            $this->ajaxReturn(['status' => -1,'msg' =>"ID为【{$goods_id}】的商品在售中,不得下架!",'data'  =>'']);
        }
               
        // 下架
        $update['is_delete'] = 1;
        $update['deltime'] = time();
        M("Goods")->where('goods_id',$goods_id)->save($update);  //商品表
        M("goods_collect")->where('goods_id',$goods_id)->delete();  //商品收藏

        $this->ajaxReturn(['status' => 1,'msg' => '操作成功']);
    }
    
    /**
     * 删除商品类型 
     */
    public function delGoodsType()
    {
        // 判断 商品规格
        $id = $this->request->param('id');
        $count = M("Spec")->where("type_id = {$id}")->count("1");
        $count > 0 && $this->error('该类型下有商品规格不得删除!',U('Admin/Goods/goodsTypeList'));
        // 判断 商品属性        
        $count = M("GoodsAttribute")->where("type_id = {$id}")->count("1");
        $count > 0 && $this->error('该类型下有商品属性不得删除!',U('Admin/Goods/goodsTypeList'));        
        // 删除分类
        M('GoodsType')->where("id = {$id}")->delete();
        $this->success("操作成功!!!",U('Admin/Goods/goodsTypeList'));
    }    

    /**
     * 删除商品属性
     */
    public function delGoodsAttribute()
    {
        $ids = I('post.ids','');
        empty($ids) &&  $this->ajaxReturn(['status' => -1,'msg' =>"非法操作！"]);
        $attrBute_ids = rtrim($ids,",");
        // 判断 有无商品使用该属性
        $count_ids = Db::name("GoodsAttr")->whereIn('attr_id',$attrBute_ids)->group('attr_id')->getField('attr_id',true);
        if($count_ids){
            $count_ids = implode(',',$count_ids);
            $this->ajaxReturn(['status' => -1,'msg' => "ID为【{$count_ids}】的属性有商品正在使用,不得删除!"]);
        }
        // 删除 属性
        M('GoodsAttribute')->whereIn('attr_id',$attrBute_ids)->delete();
        $this->ajaxReturn(['status' => 1,'msg' => "操作成功!",'url'=>U('Admin/Goods/goodsAttributeList')]);
    }            
    

    /**
     * 初始化商品关键词搜索
     */
    public function initGoodsSearchWord(){
        $searchWordLogic = new SearchWordLogic();
        $successNum = $searchWordLogic->initGoodsSearchWord();
        $this->success('成功初始化'.$successNum.'个搜索关键词');
    }

    /**
     * 初始化地址json文件
     */
    public function initLocationJsonJs()
    {
        $goodsLogic = new GoodsLogic();
        $region_list = $goodsLogic->getRegionList();//获取配送地址列表
        $area_list = $goodsLogic->getAreaList();
        $data = "var locationJsonInfoDyr = ".json_encode($region_list, JSON_UNESCAPED_UNICODE).';'."var areaListDyr = ".json_encode($area_list, JSON_UNESCAPED_UNICODE).';';
        file_put_contents(ROOT_PATH."public/js/locationJson.js", $data);
        $this->success('初始化地区json.js成功。文件位置为'.ROOT_PATH."public/js/locationJson.js");
    }

}