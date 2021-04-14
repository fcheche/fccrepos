<?php
/**
评估
 */ 
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Session;

class Pinggu extends Controller {
  
    /*
     * 初始化操作
     */
    public function index() {
        
        return $this->fetch();

    }

  
}