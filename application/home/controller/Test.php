<?php
/**

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

class Test extends Base
{
    public function index()
    {
        $html = $this->fetch();
        S($key, $html);
        return $html;
    }
}