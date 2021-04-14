<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。

 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用最新Thinkphp5助手函数特性实现单字母函数M D U等简写方式
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
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

class Smap extends Base
{
    public function index()
    {
       
        $html = $this->fetch();
        S($key, $html);
        return $html;
    }


   
}