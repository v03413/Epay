<?php
// 支付宝JS支付页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <title>支付宝支付</title>
    <link href="/assets/pay/css/weui.css" rel="stylesheet" />
</head>
<body>
    <div class="container js_container">
        <div class="page msg">
            <div class="weui_msg">
                <div class="weui_icon_area"><i class="weui_icon_info weui_icon_msg"></i></div>
                <div class="weui_text_area">
                    <h2 class="weui_msg_title">正在跳转支付...</h2>
                </div>
            </div>
        </div>
    </div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });

var tradeNO = '<?php echo $alipay_trade_no?>';

function Alipayready(callback) {
    if (window.AlipayJSBridge) {
        callback && callback();
    } else {
        document.addEventListener('AlipayJSBridgeReady', callback, false);
    }
}
function AlipayJsPay() {
	Alipayready(function(){
		AlipayJSBridge.call("tradePay",{
			tradeNO: tradeNO
		}, function(result){
			var msg = "";
			if(result.resultCode == "9000"){
				loadmsg();
			}else if(result.resultCode == "8000"){
				msg = "正在处理中";
			}else if(result.resultCode == "4000"){
				msg = "订单支付失败";
			}else if(result.resultCode == "6002"){
				msg = "网络连接出错";
			}
			if (msg!="") {
				layer.msg(msg);
			}
		});
	});
}
function loadmsg() {
	$.ajax({
		type: "GET",
		dataType: "json",
		url: "/getshop.php",
		data: {type: "wxpay", trade_no: "<?php echo TRADE_NO?>"},
		success: function (data) {
			if (data.code == 1) {
				layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.01,time: 15000});
				window.location.href=<?php echo $redirect_url?>;
			}else{
				setTimeout("loadmsg()", 2000);
			}
		},
		error: function () {
			setTimeout("loadmsg()", 2000);
		}
	});
}
window.onload = AlipayJsPay();
</script>
</body>
</html>