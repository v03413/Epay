<?php
include("../includes/common.php");
$title='获取用户标识';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
$app = isset($_GET['app'])?$_GET['app']:'wechat';
?>

	  <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">获取用户标识</h3></div>
        <div class="panel-body">
		<ul class="nav nav-tabs">
			<li class="<?php echo $app=='wechat'?'active':null;?>"><a href="?app=wechat">微信Openid</a></li><li class="<?php echo $app=='alipayuid'?'active':null;?>"><a href="?app=alipayuid">支付宝用户ID</a></li><li class="<?php echo $app=='apptoken'?'active':null;?>"><a href="?app=apptoken">支付宝应用授权Token</a></li>
		</ul>
		<div class="tab-pane active">
		<input type="hidden" id="apptype" value="<?php echo $app?>">
		<input type="hidden" id="siteurl" value="<?php echo $siteurl?>">
<?php if($app=='wechat'){
	$wxpay_channel = $DB->getAll("SELECT * FROM pre_weixin WHERE type=0");
	$default_wx = $conf['login_wx'];
	if($conf['transfer_wxpay']){
		$channel = \lib\Channel::get($conf['transfer_wxpay']);
		if($channel) {
			$default_wx = $channel['appwxmp'];
		}
	}
	?>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">选择微信公众号</div>
				<select id="channel" class="form-control">
					<?php foreach($wxpay_channel as $channel){echo '<option value="'.$channel['id'].'" '.($channel['id']==$default_wx?'selected':'').'>'.$channel['name'].'</option>';} ?>
				</select>
			</div>
			<font color="green">在公众号小程序管理里面添加公众号</font>
		</div>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">获取链接</div>
				<input type="text" id="geturl" value="" class="form-control" readonly="readonly">
				<div class="input-group-btn"><a href="javascript:;" class="btn btn-default copy-btn" data-clipboard-text="" title="点击复制"><i class="fa fa-copy"></i></a></div>
			</div>
			<font color="green">复制链接后在微信打开</font>
		</div>
		<div class="list-group-item list-group-item-info text-center">
			或使用微信扫描以下二维码
		</div>
		<div class="list-group-item text-center">
			<div id="qrcode"></div>
		</div>
<?php }elseif($app=='alipayuid'){
	$alipay_channel = $DB->getAll("SELECT * FROM pre_channel WHERE plugin='alipay' OR plugin='alipaysl' OR plugin='alipayd' OR plugin='alipayrp'");
	?>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">选择支付通道</div>
				<select id="channel" class="form-control">
					<?php foreach($alipay_channel as $channel){echo '<option value="'.$channel['id'].'" '.($channel['id']==$conf['login_alipay']?'selected':'').'>'.$channel['name'].'</option>';} ?>
				</select>
			</div>
			<font color="green">支持alipay、alipaysl、alipayd支付插件，需要先在支付宝应用的授权回调地址配置好当前域名</font>
		</div>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">获取链接</div>
				<input type="text" id="geturl" value="" class="form-control" readonly="readonly">
				<div class="input-group-btn"><a href="javascript:;" class="btn btn-default copy-btn" data-clipboard-text="" title="点击复制"><i class="fa fa-copy"></i></a></div>
			</div>
			<font color="green">复制链接后在支付宝打开</font>
		</div>
		<div class="list-group-item list-group-item-info text-center">
			或使用支付宝扫描以下二维码
		</div>
		<div class="list-group-item text-center">
			<div id="qrcode"></div>
		</div>
<?php }elseif($app=='apptoken'){
	$alipay_channel = $DB->getAll("SELECT * FROM pre_channel WHERE plugin='alipaysl'");
	?>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">选择支付通道</div>
				<select id="channel" class="form-control">
					<?php foreach($alipay_channel as $channel){echo '<option value="'.$channel['id'].'" '.($channel['id']==$conf['login_alipay']?'selected':'').'>'.$channel['name'].'</option>';} ?>
				</select>
			</div>
			<font color="green">支持alipaysl支付插件，需要先在支付宝应用（第三方应用）的授权回调地址配置好回调地址</font>
		</div>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">授权方式</div>
				<select id="authtype" class="form-control"><option value="0">基础应用授权</option><option value="1">指定应用授权</option></select>
			</div>
		</div>
		<div class="list-group-item">
			<div class="input-group">
				<div class="input-group-addon">获取链接</div>
				<input type="text" id="geturl" value="" class="form-control" readonly="readonly">
				<div class="input-group-btn"><a href="javascript:;" class="btn btn-default copy-btn" data-clipboard-text="" title="点击复制"><i class="fa fa-copy"></i></a></div>
			</div>
			<font color="green">复制链接后在支付宝打开</font>
		</div>
		<div class="list-group-item list-group-item-info text-center">
			或使用支付宝扫描以下二维码
		</div>
		<div class="list-group-item text-center">
			<div id="qrcode"></div>
		</div>
<?php }?>
        </div>
		</div>
      </div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
var apptype = $("#apptype").val();
var siteurl = $("#siteurl").val();
$(document).ready(function(){
	var clipboard = new Clipboard('.copy-btn');
	clipboard.on('success', function (e) {
		layer.msg('复制成功！', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
	});
	$("#channel").change(function(){
		var channel = $("#channel").val();
		if(channel != null){
			if(apptype == 'wechat'){
				var geturl = siteurl+'user/openid.php?wechatid='+channel;
			}else if(apptype == 'alipayuid'){
				var geturl = siteurl+'user/openid.php?channel='+channel;
			}else if(apptype == 'apptoken'){
				var authtype = $("#authtype").val();
				if(authtype == '1'){
					var geturl = siteurl+'user/openid.php?act=app_auth_assign&channel='+channel;
				}else{
					var geturl = siteurl+'user/openid.php?act=app_auth&channel='+channel;
				}
			}
			$("#geturl").val(geturl);
			$(".copy-btn").attr('data-clipboard-text', geturl);
			$('#qrcode').empty();
			$('#qrcode').qrcode({
				text: geturl,
				width: 180,
				height: 180,
				foreground: "#000000",
				background: "#ffffff",
				typeNumber: -1
			});
		}else{
			layer.msg('无可用的通道')
		}
	});
	$("#channel").change();
	$("#authtype").change(function(){
		$("#channel").change();
	});
});
</script>