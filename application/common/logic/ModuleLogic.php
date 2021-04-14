<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 */

namespace app\common\logic;


class ModuleLogic
{
    /**
     * 所有模块
     * @var array
     */
    public $modules = [];
    /**
     * 可见模块
     * @var array
     */
    public $showModules = [];

    public function getModules($onlyShow = true)
    {
        if ($this->modules) {
            return $onlyShow ? $this->showModules : $this->modules;
        }

        $isShow = Saas::instance()->isBaseUser() ? 1 : 0;
        $modules = [
            [
                'name'  => 'admin', 'title' => '平台后台', 'show' => 1,
                'privilege' => ['system'=>'系统设置','content'=>'内容管理','goodsdsp'=>'短视频','goodszmt'=>'新媒体','goodswb'=>'微博','goodswx'=>'微信','member'=>'会员中心','caiwu'=>'财务管理','comment'=>'咨询管理','leibie'=>'类别管理','log'=>'日志管理','goods'=>'商品操作','order'=>'订单操作','orderdsp'=>'短视频订单','orderzmt'=>'自媒体订单','orderwb'=>'微博订单','orderwx'=>'微信订单','count'=>'数据管理'],
            ],
            [
                'name'  => 'home', 'title' => 'PC端', 'show' => $isShow,
                'privilege' => [
                    'buy' => '购物流程', 'user' => '用户中心', 'article' => '文章功能', 'activity' => '活动优惠',
                    'virtual' => '虚拟网店', 'wechat' => '微信功能'
                ],
            ],
            [
                'name'  => 'mobile', 'title' => '手机端','show' => $isShow,
                'privilege' => [
                    'buy' => '购物流程', 'user' => '用户中心', 'article' => '文章功能', 'activity' => '活动优惠', 'distribut' => '分销功能',
                    'virtual' => '虚拟网店'
                ],
            ],
            [
                'name'  => 'api', 'title' => 'api接口', 'show' => $isShow,
                'privilege' => [
                    'buy' => '购物流程', 'user' => '用户中心', 'article' => '文章功能', 'activity' => '活动优惠', 'distribut' => '分销功能',
                    'virtual' => '虚拟网店', 'wechat' => '微信功能', 'message' => '消息推送', 'supplier' => '供应商', 'app' => '应用管理'
                ],
            ],
        ];

        $this->modules = $modules;
        foreach ($modules as $key => $module) {
            if (!$module['show']) {
                unset($modules[$key]);
            }
        }
        $this->showModules = $modules;

        return $onlyShow ? $this->showModules : $this->modules;
    }

    public function getModule($moduleIdx, $onlyShow = true)
    {
        if (!self::isModuleExist($moduleIdx, $onlyShow)) {
            return [];
        }

        $modules = $this->getModules($onlyShow);
        return $modules[$moduleIdx];
    }

    public function isModuleExist($moduleIdx, $onlyShow = true)
    {
        return key_exists($moduleIdx, $this->getModules($onlyShow));
    }

    public function getPrivilege($moduleIdx, $onlyShow = true)
    {
        $modules = $this->getModules($onlyShow);
        return $modules[$moduleIdx]['privilege'];
    }
}