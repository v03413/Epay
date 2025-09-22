<?php
/**
 * 登录
**/
$nosession=true;
include("../includes/common.php");
if($conf['login_alipay']==0)sysmsg("未开启支付宝快捷登录");

if(isset($_GET['sid'])){
	$sid = trim(daddslashes($_GET['sid']));
	if(!preg_match('/^(.[a-zA-Z0-9]+)$/',$sid))exit("Access Denied");
	session_id($sid);
}
session_start();

if(isset($_GET['act']) && $_GET['act']=='login'){
	if(isset($_SESSION['alipay_uid']) && !empty($_SESSION['alipay_uid'])){
		$alipay_uid = daddslashes($_SESSION['alipay_uid']);
		$userrow=$DB->getRow("SELECT * FROM pre_user WHERE alipay_uid='{$alipay_uid}' limit 1");
		if($userrow){
			$uid=$userrow['uid'];
			$key=$userrow['key'];
			if($islogin2==1){
				exit('{"code":-1,"msg":"当前支付宝已绑定商户ID:'.$uid.'，请勿重复绑定！"}');
			}
			$session=md5($uid.$key.$password_hash);
			$expiretime=time()+2592000;
			$token=authcode("{$uid}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
			setcookie("user_token", $token, time() + 2592000);
			$DB->exec("update `pre_user` set `lasttime`=NOW() where `uid`='$uid'");
			$result=array("code"=>0,"msg"=>"登录成功！正在跳转到用户中心","url"=>"./");
		}elseif($islogin2==1){
			$sds=$DB->exec("update `pre_user` set `alipay_uid`='$alipay_uid' where `uid`='$uid'");
			$result=array("code"=>0,"msg"=>"已成功绑定支付宝账号！","url"=>"./editinfo.php");
		}else{
			$_SESSION['Oauth_alipay_uid']=$alipay_uid;
			$result=array("code"=>0,"msg"=>"请输入商户ID和密钥完成绑定和登录","url"=>"./login.php?connect=true");
		}
		unset($_SESSION['alipay_uid']);
	}else{
		$result=array("code"=>1);
	}
	exit(json_encode($result));
}

if(isset($_GET['auth_code'])){

	$channel = \lib\Channel::get($conf['login_alipay']);
	if(!$channel)sysmsg('当前支付通道信息不存在');
	
	$alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
	try{
		$oauth = new \Alipay\AlipayOauthService($alipay_config);
		$result = $oauth->getToken($_GET['auth_code']);
	}catch(Exception $e){
		sysmsg('支付宝快捷登录失败！'.$e->getMessage());
	}

	//支付宝用户号
	if(!empty($result['user_id'])){
		$user_id = $result['user_id'];
		$user_type = 'userid';
	}else{
		$user_id = $result['open_id'];
		$user_type = 'openid';
	}
	if(isset($_GET['state'])){
		$redirect_uri = authcode(str_replace(' ', '+', $_GET['state']), 'DECODE', SYS_KEY);
		if($redirect_uri && substr($redirect_uri, 0, 1) == '/'){
			exit("<script language='javascript'>window.location.replace('{$redirect_uri}?userid={$user_id}&usertype={$user_type}&appid={$channel['appid']}');</script>");
		}
	}
	$_SESSION['alipay_uid'] = $user_id;

	$userrow=$DB->getRow("SELECT * FROM pre_user WHERE alipay_uid=:user_id limit 1", [':user_id'=>$user_id]);
	if($userrow){
		$uid=$userrow['uid'];
		$key=$userrow['key'];
		if($islogin2==1){
			@header('Content-Type: text/html; charset=UTF-8');
			exit("<script language='javascript'>alert('当前支付宝已绑定商户ID:{$uid}，请勿重复绑定！');window.location.href='./editinfo.php';</script>");
		}
		$DB->insert('log', ['uid'=>$uid, 'type'=>'支付宝快捷登录', 'date'=>'NOW()', 'ip'=>$clientip, 'city'=>$city]);
		$session=md5($uid.$key.$password_hash);
		$expiretime=time()+2592000;
		$token=authcode("{$uid}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
		setcookie("user_token", $token, time() + 2592000);
		@header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>window.location.href='./';</script>");
	}elseif($islogin2==1){
		$sds=$DB->exec("update `pre_user` set `alipay_uid`=:user_id where `uid`='$uid'", [':user_id'=>$user_id]);
		@header('Content-Type: text/html; charset=UTF-8');
		exit("<script language='javascript'>alert('已成功绑定支付宝账号！');window.location.href='./editinfo.php';</script>");
	}else{
		$_SESSION['Oauth_alipay_uid']=$user_id;
		exit("<script language='javascript'>alert('请输入商户ID和密钥完成绑定和登录');window.location.href='./login.php?connect=true';</script>");
	}

}elseif($islogin2==1 && isset($_GET['unbind'])){
	$DB->exec("update `pre_user` set `alipay_uid`=NULL where `uid`='$uid'");
	@header('Content-Type: text/html; charset=UTF-8');
	exit("<script language='javascript'>alert('您已成功解绑支付宝账号！');window.location.href='./editinfo.php';</script>");
}elseif($islogin2==1 && !isset($_GET['bind']) && !isset($_GET['state'])){
	exit("<script language='javascript'>alert('您已登陆！');window.location.href='./';</script>");
}elseif(checkmobile()==false || strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient')){
	$channel = \lib\Channel::get($conf['login_alipay']);
	if(!$channel)sysmsg('当前支付通道信息不存在');
	$alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
	$oauth = new \Alipay\AlipayOauthService($alipay_config);
	$redirect_uri = $siteurl.'user/oauth.php';
	$oauth->oauth($redirect_uri, isset($_GET['state'])?trim($_GET['state']):null);
}else{

$code_url = $siteurl.'user/oauth.php?sid='.session_id();
if(isset($_GET['bind'])){
	$code_url .= '&bind=1';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8" />
<title>支付宝快捷登录 | <?php echo $conf['sitename']?></title>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>animate.css/3.7.2/animate.min.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $cdnpublic?>font-awesome/4.7.0/css/font-awesome.min.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/font.css" type="text/css" />
<link rel="stylesheet" href="./assets/css/app.css" type="text/css" />
<style>input:-webkit-autofill{-webkit-box-shadow:0 0 0px 1000px white inset;-webkit-text-fill-color:#333;}img.logo{width:14px;height:14px;margin:0 5px 0 3px;}</style>
</head>
<body>
<div class="app app-header-fixed  ">
<div class="container w-xxl w-auto-xs" ng-controller="SigninFormController" ng-init="app.settings.container = false;">
<span class="navbar-brand block m-t" id="sitename"><?php echo $conf['sitename']?></span>
<div class="m-b-lg">
<div class="wrapper text-center">
支付宝快捷登录
</div>
<form name="form" class="form-validation">
<div class="text-center">
<button type="button" class="btn btn-lg btn-primary btn-block" onclick="jump()" ng-disabled='form.$invalid'>跳转到支付宝</button>
</div>
</div>
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
<script>
function jump(){
	var url = '<?php echo $code_url?>';
	window.location.href='alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode='+encodeURIComponent(url);
}
$(document).ready(function(){
	jump();
	setTimeout('checkopenid()', 2000);
});
function checkopenid(){
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "oauth.php?act=login",
		success: function (data, textStatus) {
			if (data.code == 0) {
				layer.msg(data.msg, {icon: 16,time: 10000,shade:[0.3, "#000"]});
				setTimeout(function(){ window.location.href=data.url }, 1000);
			}else if (data.code == 1) {
				setTimeout('checkopenid()', 2000);
			}else{
				layer.alert(data.msg);
			}
		},
		error: function (data) {
			layer.msg('服务器错误', {icon: 2});
			return false;
		}
	});
}
</script>
</body>
</html>
<?php
}