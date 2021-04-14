document.write('<script src="/template/pc/rainbow/static/js/jquery-1.11.3.min.js" type="text/javascript" charset="utf-8"></script>'+
	'<script src="/template/pc/rainbow/static/layui/layui.js" type="text/javascript" charset="utf-8"></script>'+
	'<link rel="stylesheet" type="text/css" href="/template/pc/rainbow/static/layui/css/layui.css">'+
	'<script src="/public/js/template-web.js"></script>'+
	'<script src="/template/pc/rainbow/static/js/url.js" type="text/javascript" charset="utf-8"></script>'+
	'<script src="/public/js/global.js" type="text/javascript" charset="utf-8"></script>'+
	'<link rel="stylesheet" type="text/css" href="/template/pc/rainbow/static/css/base.css">');

//ajax异步请求数据
//用法: ajaxGetData(urlList.user.login,'post',{"username": userPhone,"code": msgCode}).then(data => {})
function ajaxGetData(url,type,data){
	return new Promise((resolve,reject)=>{
		$.ajax({
			url: url,
			type: type,
			data: data,
			dataType: "json",
			success: function(data){
				resolve(data)
			},
			error: function(data){
				reject(data)
			}
		})
	});
}

//判断用户是否登录
// 登录和未登录状态类名区别用 lsLogin和noLogin  上面不做样式
function userIsLogin(){
	var uname = getCookie('uname');
	console.log(uname)
	if(uname == ''){
		$('.isLogin').remove();
		$('.noLogin').show();
	}else{
		$('.isLogin').show();
		$('.noLogin').remove();
		$('.username').html(decodeURIComponent(uname).substring(0,11));
	}
}

//信息提示框
//icon：0 警示 1成功 2失败 3问号 4锁 5哭脸 6笑脸
function showMsg(msg,icon,Callback){
	layui.use('layer', function(){
	  var layer = layui.layer;
	  
	  layer.alert(msg,{icon: icon},Callback);
	}); 
}






