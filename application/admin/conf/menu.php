<?php
return	array(
		'system'=>array('name'=>'系统','child'=>array(
		
				array('name' => '权限','child'=>array(
						// array('name' => '供应商列表', 'act'=>'supplier', 'op'=>'Admin'),
						array('name'=>'权限资源','act'=>'right_list','op'=>'System'),
				)),

				array('name' => '数据','child'=>array(
						array('name' => '数据备份', 'act'=>'index', 'op'=>'Tools'),
						array('name' => '数据还原', 'act'=>'restore', 'op'=>'Tools'),
						//array('name' => 'ecshop数据导入', 'act'=>'ecshop', 'op'=>'Tools'),
						//array('name' => '淘宝csv导入', 'act'=>'taobao', 'op'=>'Tools'),
						// array('name' => 'SQL查询', 'act'=>'log', 'op'=>'Admin'),
				)),
				
	)),

		'title'=>array('name'=>'综合','child'=>array(

			array('name' => '设置','child' => array(
						array('name'=>'商城设置','act'=>'index','op'=>'System'),
						array('name'=>'友情链接','act'=>'linkList','op'=>'Article'),
						array('name'=>'清除缓存','act'=>'cleanCache','op'=>'System'),
						array('name'=>'自定义导航栏','act'=>'navigationList','op'=>'System'),
				)),
		
				array('name' => '文章','child'=>array(
						array('name' => '文章列表', 'act'=>'articleList', 'op'=>'Article'),
						array('name' => '文章分类', 'act'=>'categoryList', 'op'=>'Article'),
						// array('name' => '帮助管理', 'act'=>'help_list', 'op'=>'Article'),
						array('name' => '会员协议', 'act'=>'agreement', 'op'=>'Article'),
						// array('name' => '公告管理', 'act'=>'notice_list', 'op'=>'Article'),
						array('name' => '新闻资讯', 'act'=>'topicList', 'op'=>'Topic'),
						array('name' => '最新问答', 'act'=>'shcfList', 'op'=>'Article'),
				)),

				array('name' => '员工','child'=>array(
						array('name' => '角色管理', 'act'=>'role', 'op'=>'Admin'),
						array('name' => '员工列表', 'act'=>'index', 'op'=>'Admin'),
						array('name' => '离职人员', 'act'=>'lizhi', 'op'=>'Admin'),
						array('name' => '战队管理', 'act'=>'corps', 'op'=>'Admin'),
				)),

				array('name' => '类别','child'=>array(
					
					// array('name' => '库存日志', 'act'=>'stock_list', 'op'=>'Goods'),
					array('name' => '商品版块', 'act'=>'goodsTypeList', 'op'=>'Goods'),
					// array('name' => '店铺规格', 'act' =>'specList', 'op' => 'Goods'),
					// array('name' => '品牌列表', 'act'=>'brandList', 'op'=>'Goods'),
					array('name' => '所属平台', 'act'=>'goodsAttributeList', 'op'=>'Goods'),
					array('name' => '行业分类', 'act'=>'categoryList', 'op'=>'Goods'),
				)),
				array('name' => '报备','child' => array(
						array('name'=>'报备信息','act'=>'baobei','op'=>'System'),
				)),
				array('name' => '日志','child'=>array(
					array('name' => '登陆日志', 'act'=>'log', 'op'=>'Admin'),
					array('name' => '浏览记录', 'act'=>'goods_log', 'op'=>'System'),
					array('name' => '访问记录', 'act'=>'member','op'=>'System'),
					array('name' => '订单日志', 'act'=>'order_log','op'=>'Order'),
				)),
			
	)),


		'dshipin'=>array('name'=>'短视频','child'=>array(
			
			array('name' => '交易','child'=>array(
					array('name' => '视频列表', 'act'=>'goodsList', 'op'=>'Goodsdsp'),
					array('name' => '我的视频', 'act'=>'myGoods', 'op'=>'Goodsdsp'),
					array('name' => '添加视频', 'act'=>'addGoods', 'op'=>'Goodsdsp'),
					array('name' => '下架视频', 'act'=>'deleteGoods', 'op'=>'Goodsdsp'),
				)),
			// array('name' => '营销','child'=>array(
			// 		array('name' => '视频列表', 'act'=>'goodsList', 'op'=>'Servicedsp'),
			// 		array('name' => '我的视频', 'act'=>'myGoods', 'op'=>'Servicedsp'),
			// 		array('name' => '添加视频', 'act'=>'addGoods', 'op'=>'Servicedsp'),
			// 		array('name' => '下架视频', 'act'=>'deleteGoods', 'op'=>'Servicedsp'),
			// 	)),
			
			array('name' => '订单','child'=>array(
					array('name' => '视频订单', 'act'=>'index','op'=>'Orderdsp'),
					array('name' => '完成订单', 'act'=>'end_order','op'=>'Orderdsp'),
					array('name' => '退款订单', 'act'=>'return_list','op'=>'Orderdsp'),
					array('name' => '认罚订单', 'act'=>'fadan_list','op'=>'Orderdsp'),
				)),
			
	)),


		'zmeiti'=>array('name'=>'自媒体','child'=>array(

			array('name' => '交易','child'=>array(
					array('name' => '媒体列表','act'=>'goodsList','op'=>'Goodszmt'),
					array('name' => '我的媒体', 'act'=>'myGoods', 'op'=>'Goodszmt'),
					array('name' => '添加媒体','act'=>'addGoods','op'=>'Goodszmt'),
				    array('name' => '下架媒体', 'act'=>'deleteGoods', 'op'=>'Goodszmt'),
						
				)),
			// array('name' => '营销','child'=>array(
			// 		array('name' => '媒体列表', 'act'=>'goodsList', 'op'=>'Servicezmt'),
			// 		array('name' => '我的媒体', 'act'=>'myGoods', 'op'=>'Servicezmt'),
			// 		array('name' => '添加媒体', 'act'=>'addGoods', 'op'=>'Servicezmt'),
			// 		array('name' => '下架媒体', 'act'=>'deleteGoods', 'op'=>'Servicezmt'),
			// 	)),
		
			array('name' => '订单','child'=>array(
					array('name' => '媒体订单', 'act'=>'index', 'op'=>'Orderzmt'),
					array('name' => '完成订单', 'act'=>'end_order', 'op'=>'Orderzmt'),
					array('name' => '退款订单', 'act'=>'return_list', 'op'=>'Orderzmt'),
					array('name' => '认罚订单', 'act'=>'fadan_list', 'op'=>'Orderzmt'),
				)),
				
				
	)),

		'weibo'=>array('name'=>'微博','child'=>array(
	
			  array('name' => '交易','child'=>array(
					array('name' => '微博列表', 'act'=>'goodsList', 'op'=>'Goodswb'),
					array('name' => '我的微博', 'act'=>'myGoods', 'op'=>'Goodswb'),
					array('name' => '添加微博', 'act'=>'addGoods', 'op'=>'Goodswb'),
					array('name' => '下架微博', 'act'=>'deleteGoods', 'op'=>'Goodswb'),
				)),
			 //  array('name' => '营销','child'=>array(
				// 	array('name' => '微博列表', 'act'=>'goodsList', 'op'=>'Servicewb'),
				// 	array('name' => '我的微博', 'act'=>'myGoods', 'op'=>'Servicewb'),
				// 	array('name' => '添加微博', 'act'=>'addGoods', 'op'=>'Servicewb'),
				// 	array('name' => '下架微博', 'act'=>'deleteGoods', 'op'=>'Servicewb'),
				// )),
			
			 array('name' => '订单','child'=>array(
					array('name' => '微博订单', 'act'=>'index', 'op'=>'Orderwb'),
					array('name' => '完成订单', 'act'=>'end_order', 'op'=>'Orderwb'),
					array('name' => '退款订单', 'act'=>'return_list', 'op'=>'Orderwb'),
					array('name' => '认罚订单', 'act'=>'fadan_list', 'op'=>'Orderwb'),
				)),
			 
	)),

		'weixin'=>array('name'=>'微信','child'=>array(
			
			array('name' => '交易','child'=>array(
					array('name' => '微信公众号', 'act'=>'goodsList', 'op'=>'Goodswx'),
					array('name' => '我的微信', 'act'=>'myGoods', 'op'=>'Goodswx'),
					// array('name' => '本组微信', 'act'=>'groupList', 'op'=>'Goodswx'),
					array('name' => '添加微信', 'act'=>'addGoods', 'op'=>'Goodswx'),
					array('name' => '下架微信', 'act'=>'deleteGoods', 'op'=>'Goodswx'),
				)),
			// array('name' => '营销','child'=>array(
			// 		array('name' => '微信列表', 'act'=>'goodsList', 'op'=>'Servicewx'),
			// 		array('name' => '我的微信', 'act'=>'myGoods', 'op'=>'Servicewx'),
			// 		array('name' => '添加微信', 'act'=>'addGoods', 'op'=>'Servicewx'),
			// 		array('name' => '下架微信', 'act'=>'deleteGoods', 'op'=>'Servicewx'),
			// 	)),

			array('name' => '订单','child'=>array(
					array('name' => '微信订单', 'act'=>'index', 'op'=>'Orderwx'),
					array('name' => '完成订单', 'act'=>'end_order', 'op'=>'Orderwx'),
					array('name' => '退款订单', 'act'=>'return_list', 'op'=>'Orderwx'),
					array('name' => '认罚订单', 'act'=>'fadan_list', 'op'=>'Orderwx'),
				)),
								
	)),

	

	'member'=>array('name'=>'会员','child'=>array(
			array('name' => '会员','child'=>array(
						array('name'=>'会员列表','act'=>'index','op'=>'User'),
						// array('name'=>'会员等级','act'=>'levelList','op'=>'User'),
						array('name'=>'充值记录','act'=>'recharge','op'=>'User'),
						array('name'=>'提现申请','act'=>'withdrawals','op'=>'User'),
						// array('name'=>'汇款记录','act'=>'remittance','op'=>'User'),
						array('name'=>'黑名单','act'=>'blacklist','op'=>'User'),
						// array('name'=>'会员整合','act'=>'integrate','op'=>'User'),
						// array('name'=>'会员签到','act'=>'signList','op'=>'User'),
				)),
			
	)),

	'zixun'=>array('name'=>'咨询','child'=>array(
			array('name' => '咨询','child'=>array(
					// array('name' => '砍价列表', 'act'=>'kanjia', 'op'=>'Comment'),
						// array('name' => '评论列表', 'act'=>'index', 'op'=>'Comment'),
					// array('name' => '店铺咨询', 'act'=>'ask_list', 'op'=>'Comment'),
					array('name' => '评估咨询', 'act'=>'pinggu_list', 'op'=>'Comment'),
					array('name' => '求购列表', 'act'=>'qiugou_list', 'op'=>'Comment'),
					array('name' => '出售列表', 'act'=>'chushou_list', 'op'=>'Comment'),
					// array('name' => '网店评估', 'act'=>'pinggu_list', 'op'=>'Comment'),
					// array('name' => '入驻需求', 'act'=>'dairuzhu_list', 'op'=>'Comment'),
					// array('name' => '回访列表', 'act'=>'huifang', 'op'=>'Comment'),
				)),
							
	)),

	'caiwu'=>array('name'=>'财务','child'=>array(
			array('name' => '账单','child' => array(
					array('name'=>'订单明细','act'=>'order_caiwu','op'=>'Caiwu'),
					// array('name' => '买家罚单', 'act'=>'fadan_list', 'op'=>'Order'),
			)),
			array('name' => '提现','child' => array(
					array('name'=>'汇款记录','act'=>'remittance','op'=>'User'),
			)),
			// array('name' => '充值','child' => array(
			// 		array('name'=>'充值记录','act'=>'recharge','op'=>'Caiwu'),
			// )),
			array('name' => '会员','child' => array(
				array('name'=>'所有会员','act'=>'index','op'=>'Caiwu'),
					array('name'=>'买家信息','act'=>'users','op'=>'Caiwu'),
					array('name'=>'卖家信息','act'=>'sellers','op'=>'Caiwu'),
			)),
			array('name' => '开支','child' => array(
					array('name' => '开支明细', 'act'=>'kaizhilist', 'op'=>'Caiwu'),
					array('name' => '开支分类', 'act'=>'categoryList', 'op'=>'Caiwu'),
			)),
	)),

	// 活动
		'active'=>array('name'=>'活动','child'=>array(
		
			array('name' => '活动','child'=>array(
					array('name' => '活动设置', 'act'=>'activeList', 'op'=>'Block'),
					array('name' => '领红包', 'act'=>'voucher', 'op'=>'Comment'),
				)),
			// array('name' => '看店卡','child'=>array(
			// 		array('name' => '看店卡', 'act'=>'index', 'op'=>'Coupon'),
			// 		array('name' => '购买记录', 'act'=>'order', 'op'=>'Coupon'),
			// 		array('name' => '使用记录', 'act'=>'coupon_list', 'op'=>'Coupon'),
					
			// 	)),
	)),
	// 活动

		
);