<?php
/**
文章
 */
namespace app\home\controller;
use think\Db;
use think\AjaxPage;
use think\Page;
use app\admin\logic\ArticleCatLogic;

class Article extends Base {
    
    public function index(){       
        $article_map['article_id'] = I('article_id/d',1);
        $article_map['is_only_houtai'] = 0;
    	$article = Db::name('article')->where($article_map)->find();
        if(empty($article)) $this->error('抱歉，您访问的页面不存在！',U('Article/articleList'));
        $article['content']=htmlspecialchars_decode($article['content']); 
    	$this->assign('article',$article);
        return $this->fetch();
    }

     public function aboutUs(){       
        
        return $this->fetch();
    }

     public function read(){
        $article_map['article_id'] = I('article_id/d',1);
        $article_map['is_only_houtai'] = 1;
        $article = Db::name('article')->where($article_map)->find();
        if(empty($article)) $this->error('抱歉，您访问的页面不存在！',U('Article/articleList'));
        $article['content']=htmlspecialchars_decode($article['content']); 
        $this->assign('article',$article);
        return $this->fetch();
    }
 
    /**
     * 文章内列表页
     */
    public function articleList(){
        
        $where['cat_id']=I('id',19);
        $where['is_open']=1;
        $where['is_only_houtai']=0;
        $count = M('Article')->where($where)->count();
        $Page = new Page($count,15);
        $show = $Page->show();
        $article = M('Article')->where($where)->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign('article',$article);
        $this->assign('page',$show);
        return $this->fetch();
    }    
    /**
     * 文章内容页
     */
    public function detail(){
    	$article_map['article_id'] = I('article_id/d',1);
        $article_map['is_only_houtai'] = 0;
    	$article = Db::name('article')->where($article_map)->find();
    	if($article){
    		$parent = Db::name('article_cat')->where("cat_id",$article['cat_id'])->find();
    		$this->assign('cat_name',$parent['cat_name']);
    		$this->assign('article',$article);
    	}else{
            $this->error('抱歉，您访问的页面不存在！',U('Article/articleList'));
        }
        return $this->fetch();
    }
    
    /**
     * 获取服务协议
     * @return mixed
     */
    public function agreement(){
    	$doc_code = I('doc_code','agreement');
    	$article = Db::name('system_article')->where('doc_code',$doc_code)->find();
    	if(empty($article)) $this->error('抱歉，您访问的页面不存在！');
    	$this->assign('article',$article);
    	return $this->fetch();
    }

}