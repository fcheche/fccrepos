var urlList = {
	index: {
		// 评估 咨询  求购 出售等表单提交
		sendForm: '/home/Ajaxapi/pinggu',
		// 获取随机客服
		getSJQQ: '/home/Ajaxapi/kefu?pingtai=1'
	},
	goods: {
		// 商品收藏
		goodsCollect: '/home/Goods/collect_goods'
	},
	lists: {
		//商品列表
		goodsList: '/home/Lists/ajax_goods',

	},
	order: {

	},
	user: {
		//登录接口
		login: '/index.php?m=Home&c=User&a=do_login&t='+Math.random(),
		// 检查用户是否存在
		checkUsername: '/index.php?m=Home&c=User&a=check_username&t='+Math.random(),
		//发送手机短信
		sendMsg: '/Home/Api/send_validate_code',
		//注册
		reg: '/Home/User/reg',
		// 验证码图片
		verifyImg: '/index.php?m=Home&c=User&a=verify&r='+Math.random(),
		//提现
		withdrawals: '/Home/User/withdrawals',
		//添加银行卡
		addCard: 'Home/User/add_card',
		// 修改登录密码
		modLoginPwd: '/Home/User/password',
		// 修改支付密码
		modPayPwd: '/Home/User/paypwd',
		// 删除收藏
		delGoodsCollect: '/Home/User/delGoodsCollect',
		// 删除足迹
		delVisitLog: '/home/user/del_visit_log'
	},
	article: {
		// 新闻资讯
		news: '/home/Index/news_z',
		// 售后采访
		shouHou: '/home/Index/news_z',
		// 公司动态
		companyNews: '/home/Index/dongtai'
	}
} 