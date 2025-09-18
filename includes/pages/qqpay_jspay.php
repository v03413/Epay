<?php
// QQ公众号支付页面

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
<script src="//open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
<script>
	document.body.addEventListener('touchmove', function (event) {
		event.preventDefault();
	},{ passive: false });

	function callpay()
	{
		mqq.tenpay.pay({
			tokenId: '<?php echo $tokenId; ?>',
			appInfo: "<?php echo $appInfo; ?>"
		}, function(result, resultCode){
			if(result.resultCode == 0){ //支付成功
				loadmsg();
			}
		});
	}
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "qqpay", trade_no: "<?php echo $order['trade_no']?>"},
            success: function (data) {
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
                }else{
                    setTimeout("loadmsg()", 2000);
                }
            },
            error: function () {
                setTimeout("loadmsg()", 2000);
            }
        });
    }
    window.onload = callpay();
</script>
</div>
</div>
</body>
</html>