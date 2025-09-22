<?php
/**
 * 登录
**/
$is_defend=true;
include("../includes/common.php");

if(isset($_GET['logout'])){
	if(!checkRefererHost())exit();
	setcookie("user_token", "", time() - 2592000);
	@header('Content-Type: text/html; charset=UTF-8');
	exit("<script language='javascript'>alert('您已成功注销本次登录！');window.location.href='./login.php';</script>");
}elseif($islogin2==1){
	exit("<script language='javascript'>alert('您已登录！');window.location.href='./';</script>");
}
$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['csrf_token'] = $csrf_token;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<title>登录 | <?php echo $conf['sitename']?></title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>animate.css/3.7.2/animate.min.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/font.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/app.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/captcha.css" type="text/css" />
<style>input:-webkit-autofill{-webkit-box-shadow:0 0 0px 1000px white inset;-webkit-text-fill-color:#333;}</style>
</head>
<body>
<div class="app app-header-fixed  ">
<div class="container w-xxl w-auto-xs" ng-controller="SigninFormController" ng-init="app.settings.container = false;">
<span class="navbar-brand block m-t"><?php echo $conf['sitename']?></span>
<div class="m-b-lg">
<div class="wrapper text-center">
<strong>请输入您的商户信息</strong>
</div>
<form name="form" class="form-validation" method="post" action="login.php">
<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
<div class="text-danger wrapper text-center" ng-show="authError">
</div>
<?php if(!$conf['close_keylogin']){?>
<ul class="nav nav-tabs">
    <li style="width: 50%;" align="center" class="<?php echo $_GET['m']!='key'?'active':null;?>">
  <a href="./login.php">密码登录(New)</a>
</li>
    <li style="width: 50%;" align="center" class="<?php echo $_GET['m']=='key'?'active':null;?>">
  <a href="./login.php?m=key">密钥登录</a>
</li>
</ul><?php }?>
<div class="tab-content">
<div class="tab-pane active">
<div class="list-group list-group-sm swaplogin">
<?php if($_GET['m']=='key'){?>
<input type="hidden" name="type" value="0"/>
<div class="list-group-item">
<input type="text" name="user" placeholder="商户ID" value="" class="form-control no-border" onkeydown="if(event.keyCode==13){$('#submit').click()}">
</div>
<div class="list-group-item">
<input type="password" name="pass" placeholder="商户密钥" value="" class="form-control no-border" onkeydown="if(event.keyCode==13){$('#submit').click()}">
</div>
<?php }else{?>
<input type="hidden" name="type" value="1"/>
<div class="list-group-item">
<input type="text" name="user" placeholder="邮箱/手机号" value="" class="form-control no-border" onkeydown="if(event.keyCode==13){$('#submit').click()}">
</div>
<div class="list-group-item">
<input type="password" name="pass" placeholder="密码" value="" class="form-control no-border" onkeydown="if(event.keyCode==13){$('#submit').click()}">
</div>
<?php }?>
	<?php if($conf['captcha_open_login']==1){?>
	<div class="list-group-item" id="captcha" style="margin: auto;"><div id="captcha_text">
		正在加载验证码
	</div>
	<div id="captcha_wait">
		<div class="loading">
			<div class="loading-dot"></div>
			<div class="loading-dot"></div>
			<div class="loading-dot"></div>
			<div class="loading-dot"></div>
		</div>
	</div></div>
	<div id="captchaform"></div>
	<?php }?>
</div>
<button type="button" class="btn btn-lg btn-primary btn-block" id="submit">立即登录</button>
</div>
</div>
<div class="line line-dashed"></div>
<div class="form-group">
	<a href="findpwd.php" class="btn btn-info btn-rounded"><i class="fa fa-unlock"></i>&nbsp;找回密码</a>
	<a href="reg.php" class="btn btn-danger btn-rounded <?php echo $conf['reg_open']==0?'hide':null;?>" style="float:right;"><i class="fa fa-user-plus"></i>&nbsp;注册商户</a>
</div>
<?php if(!isset($_GET['connect'])){?>
<div class="wrapper text-center">
<?php if($conf['login_alipay']>0 || $conf['login_alipay']==-1){?>
<button type="button" class="btn btn-rounded btn-lg btn-icon btn-default" title="支付宝快捷登录" onclick="connect('alipay')"><img src="../assets/icon/alipay.ico" style="border-radius:50px;"></button>
<?php }?>
<?php if($conf['login_qq']>0){?>
<button type="button" class="btn btn-rounded btn-lg btn-icon btn-default" title="QQ快捷登录" onclick="connect('qq')"><i class="fa fa-qq fa-lg" style="color: #0BB2FF"></i></button>
<?php }?>
<?php if($conf['login_wx']>0 || $conf['login_wx']==-1){?>
<button type="button" class="btn btn-rounded btn-lg btn-icon btn-default" title="微信快捷登录" onclick="connect('wx')"><i class="fa fa-wechat fa-lg" style="color: green"></i></button>
</div>
<?php }?>
<?php }?>
</form>
</div>
<div class="text-center">
<p>
<small class="text-muted"><a href="/"><?php echo $conf['sitename']?></a><br>&copy; 2016~<?php echo date("Y")?></small>
</p>
</div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jsencrypt/3.5.4/jsencrypt.min.js"></script>
<script src="//static.geetest.com/static/tools/gt.js"></script>
<script>
window.appendChildOrg = Element.prototype.appendChild;
Element.prototype.appendChild = function() {
    if(arguments[0].tagName == 'SCRIPT'){
        arguments[0].setAttribute('referrerpolicy', 'no-referrer');
    }
    return window.appendChildOrg.apply(this, arguments);
};
</script>
<script src="//static.geetest.com/v4/gt4.js"></script>
<script>
const PUBLIC_KEY_PEM = `<?php echo base64ToPem($conf['public_key'], 'PUBLIC KEY')?>`;
var captcha_open = 0;
var handlerEmbed = function (captchaObj) {
	captchaObj.appendTo('#captcha');
	captchaObj.onReady(function () {
		$("#captcha_wait").hide();
	}).onSuccess(function () {
		var result = captchaObj.getValidate();
		if (!result) {
			return alert('请完成验证');
		}
		$.captchaResult = result;
		$.captchaObj = captchaObj;
	});
};
$(document).ready(function(){
	if($("#captcha").length>0) captcha_open=1;
	$("#submit").click(function(){
		var type=$("input[name='type']").val();
		var user=$("input[name='user']").val();
		var pass=$("input[name='pass']").val();
		if(user=='' || pass==''){layer.alert(type==1?'账号和密码不能为空！':'ID和密钥不能为空！');return false;}
		submitLogin(type,user,pass);
	});
	if(captcha_open==1){
	$.ajax({
		url: "ajax.php?act=captcha",
		type: "get",
		cache: false,
		dataType: "json",
		success: function (data) {
			$('#captcha_text').hide();
			$('#captcha_wait').show();
			if(data.version == 1){
				initGeetest4({
					captchaId: data.gt,
					product: 'popup',
					protocol: 'https://',
					riskType: 'slide',
					hideSuccess: true,
					nativeButton: {width: '100%'}
				}, handlerEmbed);
			}else{
				initGeetest({
					gt: data.gt,
					challenge: data.challenge,
					new_captcha: data.new_captcha,
					product: "popup",
					width: "100%",
					offline: !data.success,
				}, handlerEmbed);
			}
		}
	});
	}
});
function submitLogin(type,user,pass){
	var csrf_token=$("input[name='csrf_token']").val();
	if(captcha_open == 1 && !$.captchaResult){
		layer.alert('请先完成滑动验证！'); return false;
	}
	var enc_type = '0';
	if(PUBLIC_KEY_PEM != ''){
		const enc = new JSEncrypt();
		enc.setPublicKey(PUBLIC_KEY_PEM);
		pass = enc.encrypt(pass);
		if(pass) enc_type = '1';
	}
	var ii = layer.load();
	$.ajax({
		type: "POST",
		dataType: "json",
		data: {type:type, user:user, pass:pass, enc:enc_type, csrf_token:csrf_token, ...$.captchaResult},
		url: "ajax.php?act=login",
		success: function (data, textStatus) {
			layer.close(ii);
			if (data.code == 0) {
				layer.msg(data.msg, {icon: 16,time: 10000,shade:[0.3, "#000"]});
				setTimeout(function(){ window.location.href=data.url }, 1000);
			}else{
				layer.alert(data.msg, {icon: 2});
				$.captchaObj.reset();
			}
		},
		error: function (data) {
			layer.msg('服务器错误', {icon: 2});
			return false;
		}
	});
}
function connect(type){
	var ii = layer.load();
	$.ajax({
		type : "POST",
		url : "ajax.php?act=connect",
		data : {type:type},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				window.location.href = data.url;
			}else{
				layer.alert(data.msg, {icon: 7});
			}
		} 
	});
}
</script>
</body>
</html>