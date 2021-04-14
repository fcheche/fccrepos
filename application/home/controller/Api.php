<?php
/**
数据接口-蜂车车
 */

namespace app\home\controller;

use app\common\logic\User;
use app\common\logic\UsersLogic;
use app\common\model\Shop;
use app\common\model\Users;
use app\common\util\TpshopException;
use think\Db;
use think\Session;
use think\Verify;
use think\Cookie;

class Api extends Base
{
    public $send_scene;

    public function _initialize()
    {
        parent::_initialize();
        session('user');
    }

   
    /*
     * 获取下级分类
     */
    public function get_category()
    {
        $parent_id = I('get.parent_id/d'); // 商品分类 父id
        $list = M('goods_category')->where("parent_id", $parent_id)->select();
        if ($list) {
            $this->ajaxReturn(['status' => 1, 'msg' => '获取成功！', 'result' => $list]);
        }
        $this->ajaxReturn(['status' => -1, 'msg' => '获取失败！', 'result' =>[]]);
    }


    /**
     * 前端发送短信方法: APP/WAP/PC 共用发送方法
     */
    public function send_validate_code()
    {
                            // 获取客户端IP地址
                            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                                $pos = array_search('unknown', $arr);
                                if (false !== $pos)
                                    unset($arr[$pos]);
                                $ip = trim($arr[0]);
                                }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                                } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                                    $ip = $_SERVER['REMOTE_ADDR'];
                                }
                                // IP地址合法验证
                                $ip = (false !== ip2long($ip)) ? $ip : '0.0.0.0';
                                    
                            // 获取客户端IP地址
                                // 访问记录
                                $time=time();
                                $jbegintime=mktime(0,0,0,date('m'),date('d'),date('Y'));//今天开始时间
                                $memwhere['ip']=$ip;
                                $memwhere['start_time']=$jbegintime;
                                $is_fangwen=M('member')->where($memwhere)->find();
             
                                if ($is_fangwen['count']>40) {
        $this->ajaxReturn(['status' => 0, 'msg' => '对不起，操作频繁，请联系管理员处理后再试！', 'result' => '']);
                                }
                                if ($is_fangwen) {
                                    $data['ip'] = $ip;
                                    $data['end_time'] = $time;
                                    $data['count'] = $is_fangwen['count']+1;
                                    $data['beizhu'] = '短信验证'.I('scene').'/'.$is_fangwen['beizhu'];
                                    $member = M('member')->where($memwhere)->save($data);
                                }else{
                                    $data['ip'] = $ip;
                                    $data['start_time'] = $time;
                                    $data['beizhu'] = '短信验证'.I('scene');
                                    $data['end_time'] = $time;
                                    $data['count'] = 1;
                                    $member = M('member')->add($data);
                                }
                                // 访问记录

        $this->send_scene = C('SEND_SCENE');

        $type = I('type');
        $scene = I('scene');    //发送短信验证码使用场景
        $mobile = I('mobile');
        $sender = I('send');
        $mobile = !empty($mobile) ? $mobile : $sender;
        $session_id = I('unique_id', session_id());
        session("scene", $scene);

        //注册 图片验证码
        // $verify_code = I('verify_code');
        // if ($scene == 1 && !empty($verify_code)) {
        //     $verify = new Verify();
        //     if (!$verify->check($verify_code, 'user_reg')) {
        //         ajaxReturn(array('status' => -1, 'msg' => '图像验证码错误'));
        //     }
        // }

        //领红包
        if ($scene == 8) {
            $map['tel']=$mobile;
            $info = M('voucher')->where($map)->find();
            if ($info) {
                $this->ajaxReturn(array('status' => -2, 'msg' => '该手机号已领取过红包，请联系客服使用！', 'money' => $info['price']));
            }
        }

        if ($scene > 1 && $scene < 8) {
            $map['mobile']=$mobile;
            $info = M('users')->where($map)->find();
            if (!$info) {
                $this->ajaxReturn(array('status' => -1, 'msg' => '该手机号还没有注册账号，请注册后操作'));
            }
        }
           
            //判断是否存在验证码
            $data = M('sms_log')->where(array('mobile' => $mobile, 'session_id' => $session_id, 'status' => 1))->order('id DESC')->find();
            //获取时间配置
            $sms_time_out = tpCache('sms.sms_time_out');
            $sms_time_out = $sms_time_out ? $sms_time_out : 120;
            //120秒以内不可重复发送
            if ($data && (time() - $data['add_time']) < $sms_time_out) {
                $return_arr = array('status' => -1, 'msg' => $sms_time_out . '秒内不允许重复发送');
                $this->ajaxReturn($return_arr);
            }
            //随机一个验证码
            $code = rand(1000, 9999);
            $params['code'] = $code;

            //发送短信
            $resp = sendSms($scene, $mobile, $params, $session_id,$code);

            if ($resp['status'] == '1') {
                // print_r(111);
                //发送成功, 修改发送状态位成功
                 M('sms_log')->where(array('mobile' => $mobile, 'code' => $code, 'session_id' => $session_id, 'status' => 0))->save(array('status' => 1));
                $return_arr = array('status' => 1, 'msg' => '发送成功,请注意查收');
            } else {
                $return_arr = array('status' => -1, 'msg' => '发送失败,请绑定正确手机号');
            }
            $this->ajaxReturn($return_arr);
    }

    /**
     * 验证短信验证码: APP/WAP/PC 共用发送方法
     */
    public function check_validate_code()
    {
// scene发送场景1:用户注册,2:找回密码或者手机短信登陆,3:客户下单,4:客户支付,5:充值成功提示,6:身份验证支付密码，7：提示财务充值记录 8:领取红包或者卡券
        $code = I('post.code');
        $mobile = I('mobile');
        $send = I('send');
        $sender = empty($mobile) ? $send : $mobile;
        $type = I('type');
        $session_id = I('unique_id', session_id());
        $scene = I('scene', -1);

        $logic = new UsersLogic();
        $res = $logic->check_validate_code($code, $sender, $type, $session_id, $scene);
        $this->ajaxReturn($res);
    }

    /**
     * 检测手机号是否已经存在
     */
    public function issetMobile()
    {
        $mobile = I("mobile", '0');
        $users = M('users')->where('mobile', $mobile)->find();
        if ($users)
            exit ('1');
        else
            exit ('0');
    }

    public function issetMobileOrEmail()
    {
        $mobile = I("mobile", '0');
        $users = M('users')->where("email", $mobile)->whereOr('mobile', $mobile)->find();
        if ($users)
            exit ('1');
        else
            exit ('0');
    }

    /**
     * 查询物流
     */
    public function queryExpress()
    {
        $express_switch = tpCache('express.express_switch');
        if ($express_switch == 1) {
            require_once(PLUGIN_PATH . 'kdniao/kdniao.php');
            $kdniao = new \kdniao();
            $data['OrderCode'] = empty(I('order_sn')) ? date('YmdHis') : I('order_sn');
            $data['ShipperCode'] = I('shipping_code');
            $data['LogisticCode'] = I('invoice_no');
            $res = $kdniao->getOrderTracesByJson(json_encode($data));
            $res = json_decode($res, true);
            if ($res['State'] == 3) {
                foreach ($res['Traces'] as $val) {
                    $tmp['context'] = $val['AcceptStation'];
                    $tmp['time'] = $val['AcceptTime'];
                    $res['data'][] = $tmp;
                }
                $res['status'] = "200";
            } else {
                $res['message'] = $res['Reason'];
            }
            return json($res);
        } else {
            $shipping_code = input('shipping_code');
            $invoice_no = input('invoice_no');
            if (empty($shipping_code) || empty($invoice_no)) {
                return json(['status' => 0, 'message' => '参数有误', 'result' => '']);
            }
            return json(queryExpress($shipping_code, $invoice_no));
        }
    }

   
    /**
     * 检查订单状态
     */
    public function check_order_pay_status()
    {
        $order_id = I('order_id/d');
        if (empty($order_id)) {
            $res = ['message' => '参数错误', 'status' => -1, 'result' => ''];
            $this->AjaxReturn($res);
        }
        $order = M('order')->field('pay_status')->where(['order_id' => $order_id])->find();
        if ($order['pay_status'] != 0) {
            $res = ['message' => '已支付', 'status' => 1, 'result' => $order];
        } else {
            $res = ['message' => '未支付', 'status' => 0, 'result' => $order];
        }
        $this->AjaxReturn($res);
    }

    /**
     * 广告位js
     */
    public function ad_show()
    {
        $pid = I('pid/d', 1);
        $where = array(
            'pid' => $pid,
            'enable' => 1,
            'start_time' => array('lt', strtotime(date('Y-m-d H:00:00'))),
            'end_time' => array('gt', strtotime(date('Y-m-d H:00:00'))),
        );
        $ad = D("ad")->where($where)->order("orderby desc")->cache(true, TPSHOP_CACHE_TIME)->find();
        $this->assign('ad', $ad);
        return $this->fetch();
    }
    
    /**
     *  搜索关键字
     * @return array
     */
    public function searchKey()
    {
        $searchKey = input('key');
        $searchKeyList = Db::name('search_word')
            ->where('keywords', 'like', $searchKey . '%')
            ->whereOr('pinyin_full', 'like', $searchKey . '%')
            ->whereOr('pinyin_simple', 'like', $searchKey . '%')
            ->limit(5)
            ->select();
        if ($searchKeyList) {
            return json(['status' => 1, 'msg' => '搜索成功', 'result' => $searchKeyList]);
        } else {
            return json(['status' => 0, 'msg' => '没记录', 'result' => $searchKeyList]);
        }
    }

    /**
     * 根据ip设置获取的地区来设置地区缓存
     */
    public function doCookieArea()
    {
//        $ip = '183.147.30.238';//测试ip
        $address = input('address/a', []);
        if (empty($address) || empty($address['province'])) {
            $this->setCookieArea();
            return;
        }
        $province_id = Db::name('region')->where(['level' => 1, 'name' => ['like', '%' . $address['province'] . '%']])->limit('1')->value('id');
        if (empty($province_id)) {
            $this->setCookieArea();
            return;
        }
        if (empty($address['city'])) {
            $city_id = Db::name('region')->where(['level' => 2, 'parent_id' => $province_id])->limit('1')->order('id')->value('id');
        } else {
            $city_id = Db::name('region')->where(['level' => 2, 'parent_id' => $province_id, 'name' => ['like', '%' . $address['city'] . '%']])->limit('1')->value('id');
        }
        if (empty($address['district'])) {
            $district_id = Db::name('region')->where(['level' => 3, 'parent_id' => $city_id])->limit('1')->order('id')->value('id');
        } else {
            $district_id = Db::name('region')->where(['level' => 3, 'parent_id' => $city_id, 'name' => ['like', '%' . $address['district'] . '%']])->limit('1')->value('id');
        }
        $this->setCookieArea($province_id, $city_id, $district_id);
    }

    /**
     * 设置地区缓存
     * @param $province_id
     * @param $city_id
     * @param $district_id
     */
    private function setCookieArea($province_id = 1, $city_id = 2, $district_id = 3)
    {
        Cookie::set('province_id', $province_id);
        Cookie::set('city_id', $city_id);
        Cookie::set('district_id', $district_id);
    }

    public function shop()
    {
        $province_id = input('province_id/d', 0);
        $city_id = input('city_id/d', 0);
        $district_id = input('district_id/d', 0);
        $shop_address = input('shop_address/s', '');
        $longitude = input('longitude/s', 0);
        $latitude = input('latitude/s', 0);
        if (empty($province_id) && empty($province_id) && empty($district_id)) {
            $this->ajaxReturn([]);
        }
        $where = ['deleted' => 0, 'shop_status' => 1, 'province_id' => $province_id, 'city_id' => $city_id, 'district_id' => $district_id];
        $field = '*';
        $order = 'shop_id desc';
        if ($longitude) {
            $field .= ',round(SQRT((POW(((' . $longitude . ' - longitude)* 111),2))+  (POW(((' . $latitude . ' - latitude)* 111),2))),2) AS distance';
            $order = 'distance ASC';
        }
        if($shop_address){
            $where['shop_name|shop_address'] = ['like', '%'.$shop_address.'%'];
        }
        $Shop = new Shop();
        $shop_list = $Shop->field($field)->where($where)->order($order)->select();
        $origins = $destinations = [];
        if ($shop_list) {
            $shop_list = collection($shop_list)->append(['phone','area_list','work_time','work_day'])->toArray();
            $shop_list_length = count($shop_list);
            for ($shop_cursor = 0; $shop_cursor < $shop_list_length; $shop_cursor++) {
                $origin = $latitude . ',' . $longitude;
                array_push($origins, $origin);
                $destination = $shop_list[$shop_cursor]['latitude'] . ',' . $shop_list[$shop_cursor]['longitude'];
                array_push($destinations, $destination);
            }
            $url = 'http://api.map.baidu.com/routematrix/v2/driving?output=json&origins=' . implode('|', $origins) . '&destinations=' . implode('|', $destinations) . '&ak=Sgg73Hgc2HizzMiL74TUj42o0j3vM5AL';
            $result = httpRequest($url, "get");
            $data = json_decode($result, true);
            if (!empty($data['result'])) {
                for ($shop_cursor = 0; $shop_cursor < $shop_list_length; $shop_cursor++) {
                    $shop_list[$shop_cursor]['distance_text'] = $data['result'][$shop_cursor]['distance']['text'];
                }
            }else{
                for ($shop_cursor = 0; $shop_cursor < $shop_list_length; $shop_cursor++) {
                    $shop_list[$shop_cursor]['distance_text'] = $data['message'];
                }
            }
        }
        $this->ajaxReturn($shop_list);
    }

    /**
     * 检查绑定账号的合法性
     */
    public function checkBindMobile()
    {
        $mobile = input('mobile/s');
        if(empty($mobile)){
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if(!check_mobile($mobile)){
            $this->ajaxReturn(['status' => 0, 'msg' => '手机格式错误', 'result' => '']);
        }
        //1.检查账号密码是否正确
        $users = Users::get(['mobile'=>$mobile]);
        if (empty($users)) {
            $this->ajaxReturn(['status' => 0, 'msg' => '账号不存在', 'result' => '']);
        }
        $user = new User();
        try{
            $user->setUser($users);
            $user->checkOauthBind();
            $this->ajaxReturn(['status' => 1, 'msg' => '该手机可绑定', 'result' => '']);
        }catch (TpshopException $t){
            $error = $t->getErrorArr();
            $this->ajaxReturn($error);
        }
    }
    /**
     * 检查注册账号的合法性
     */
    public function checkRegMobile()
    {
        $mobile = input('mobile/s');
        if(empty($mobile)){
            $this->ajaxReturn(['status' => 0, 'msg' => '参数错误', 'result' => '']);
        }
        if(!check_mobile($mobile)){
            $this->ajaxReturn(['status' => 0, 'msg' => '手机格式错误', 'result' => '']);
        }
        //1.检查账号密码是否正确
        $users = Db::name('users')->where("mobile", $mobile)->find();
        if ($users) {
            $this->ajaxReturn(['status' => 0, 'msg' => '该手机号已被注册', 'result' => '']);
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '该手机可注册', 'result' => '']);
    }
}