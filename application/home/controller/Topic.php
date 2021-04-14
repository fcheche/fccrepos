<?php
/**
新闻专题资讯
 */ 
namespace app\home\controller;
use think\Db;
use think\AjaxPage;
use think\Page;

class Topic extends Base {
	/*
	 * 专题列表
	 */
	public function topicList(){
        $where['topic_state']=2;
        $count = M('topic')->where($where)->count();
        $Page = new Page($count,15);
        $show = $Page->show();
		$topicList = M('topic')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
		$this->assign('topicList',$topicList);
        $this->assign('page',$show);
		return $this->fetch();
	}
	
	/*
	 * 专题详情
	 */
	public function index(){
		$topic_id = I('topic_id/d',1);
		$topic = Db::name('topic')->where("topic_id", $topic_id)->find();
		$this->assign('topic',$topic);
		return $this->fetch();
	}
	
	public function info(){
		$topic_id = I('topic_id/d',1);
		$topic = Db::name('topic')->where("topic_id", $topic_id)->find();
        echo htmlspecialchars_decode($topic['topic_content']);                
        exit;
	}
}