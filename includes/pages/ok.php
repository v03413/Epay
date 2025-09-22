<?php
// 支付成功页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
	<title>支付结果</title>
	<link href="/assets/pay/css/weui.css" rel="stylesheet" />
</head>
<body>
	<div class="container js_container">
		<div class="page msg">
			<div class="weui_msg">
				<div class="weui_icon_area"><i class="weui_icon_success weui_icon_msg"></i></div>
				<div class="weui_text_area">
					<h2 class="weui_msg_title">支付成功</h2>
					<p class="weui_msg_desc">支付成功，请回到浏览器查看订单</p>
				</div>
				<div class="weui_opr_area">
					<p class="weui_btn_area">
						<a href="javascript:;" class="weui_btn weui_btn_primary" id="Close">关闭</a>
						<!--a href="javascript:;" class="weui_btn weui_btn_default">返回</a-->
					</p>
				</div>
			</div>
		</div>
	</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script type="text/javascript">
	document.body.addEventListener('touchmove', function (event) {
		event.preventDefault();
	},{ passive: false });
	if(navigator.userAgent.indexOf("AlipayClient") > -1){
		function Alipayready(callback) {
			if (window.AlipayJSBridge) {
				callback && callback();
			} else {
				document.addEventListener('AlipayJSBridgeReady', callback, false);
			}
		}
		Alipayready(function(){
			$('.weui_opr_area #Close').click(function() {
				AlipayJSBridge.call('popWindow');
			});
		})
	}else if(navigator.userAgent.indexOf("MicroMessenger") > -1){
		if (typeof WeixinJSBridge == "undefined") {
			if (document.addEventListener) {
				document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
			} else if (document.attachEvent) {
				document.attachEvent('WeixinJSBridgeReady', jsApiCall);
				document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
			}
		} else {
			jsApiCall();
		}
		function jsApiCall() {
			$('.weui_opr_area #Close').click(function() {
				WeixinJSBridge.call('closeWindow');
			});
		}
	}else{
		$('.weui_opr_area #Close').click(function() {
			window.opener=null;window.close();
		});
	}
</script>
	</body>
</html>
