<?php
$is_defend=true;
include("../includes/common.php");
if(!$conf['test_open'])sysmsg("未开启测试支付");
if(isset($_GET['ok']) && isset($_GET['trade_no'])){
	$trade_no=daddslashes($_GET['trade_no']);
	$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' AND uid='{$conf['test_pay_uid']}' limit 1");
	if(!$row)sysmsg('订单号不存在');
	if($row['status']!=1)sysmsg('订单未完成支付');
	$money = $row['money'];
}else{
	$trade_no=date("YmdHis").rand(111,999);
	$userrow = $DB->getRow("SELECT uid,gid FROM pre_user WHERE uid='{$conf['test_pay_uid']}' limit 1");
	if(!$userrow)sysmsg("测试支付商户不存在");
	$paytype = \lib\Channel::getTypes($userrow['uid'], $userrow['gid']);
	$csrf_token = md5(mt_rand(0,999).time());
	$_SESSION['csrf_token'] = $csrf_token;
	$money = 1;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<body>
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
	<title><?php echo $conf['sitename']?> - 测试支付</title>
    <link href="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet"/>
	<link rel="stylesheet" href="./assets/css/captcha.css" type="text/css" />
	<style>.form-group{margin-bottom:18px} #captcha{margin: auto;margin-bottom:16px}</style>
</head>
<div class="container">
<div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<div class="page-header">
  <h4><?php echo $conf['sitename']?> - 测试支付<a href="/" class="pull-right"><small>返回首页</small></a></h4>
</div>
<div class="panel panel-primary">
<div class="panel-body">

<form name="alipayment">
<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
<div class="form-group"><div class="input-group">
<span class="input-group-addon"><span class="glyphicon glyphicon-barcode"></span></span>
<input class="form-control" placeholder="商户订单号" value="<?php echo $trade_no?>" name="trade_no" type="text" disabled="">
</div></div>
<div class="form-group"><div class="input-group">
<span class="input-group-addon"><span class="glyphicon glyphicon-shopping-cart"></span></span>
<input class="form-control" placeholder="商品名称" value="支付测试" name="name" type="text" disabled="" >
</div></div>
<div class="form-group"><div class="input-group">
<span class="input-group-addon"><span class="glyphicon glyphicon-yen"></span></span>
<input class="form-control" placeholder="付款金额" value="<?php echo $money?>" name="money" type="text" <?php echo isset($_GET['ok'])?'disabled=""':'required=""'?>>	        
</div></div>
<center>
<?php if(isset($_GET['ok'])){?>
<div class="alert alert-success"><i class="glyphicon glyphicon-ok-circle"></i>&nbsp;订单已支付成功！</div>
<?php }else{?>
<?php if($conf['captcha_open_test']==1){?>
	<div class="list-group-item" id="captcha"><div id="captcha_text">
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
<div class="btn-group btn-group-justified" role="group" aria-label="...">
<?php foreach($paytype as $rows){?>
<div class="btn-group" role="group">
  <button type="button" name="type" value="<?php echo $rows['id']?>" class="btn btn-default" onclick="submitPay(this)"><img src="/assets/icon/<?php echo $rows['name']?>.ico" height="18">&nbsp;<?php echo $rows['showname']?></button>
</div>
<?php }?>
</div>
<?php }?>
</center>
</form>
</div>
<div class="panel-footer text-center">
<?php echo $conf['sitename']?> © <?php echo date("Y")?> All Rights Reserved.
</div>
</div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>twitter-bootstrap/3.4.1/js/bootstrap.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
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
function submitPay(obj){
	var csrf_token=$("input[name='csrf_token']").val();
	var money=$("input[name='money']").val();
	var typeid=$(obj).val();
	if(money==''){
		layer.alert("金额不能为空");
		return false;
	}
	if(captcha_open == 1 && !$.captchaResult){
		layer.alert('请先完成滑动验证！'); return false;
	}
	var ii = layer.load();
	$.ajax({
		type: "POST",
		dataType: "json",
		data: {money:money, typeid:typeid, csrf_token:csrf_token, ...$.captchaResult},
		url: "ajax.php?act=testpay",
		success: function (data, textStatus) {
			layer.close(ii);
			if (data.code == 0) {
				window.location.href=data.url;
			}else{
				layer.alert(data.msg, {icon: 2});
				$.captchaObj.reset();
			}
		},
		error: function (data) {
			layer.msg('服务器错误', {icon: 2});
		}
	});
	return false;
}
$(document).ready(function(){
	if($("#captcha").length>0) captcha_open=1;
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
					riskType: 'ai',
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
</script>
</body>