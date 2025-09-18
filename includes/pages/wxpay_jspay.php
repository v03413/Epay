<?php
// 微信公众号支付页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<title>微信支付手机版</title>
<style>
    body{
        margin: 0px !important;
    }
</style>
</head>
<body style="background-color:#f6f6f6">
<div style="display: flex;justify-content: center; padding-top: 20px;border-radius: 15px;height: 100px;text-align: center;align-items: center;">
      <span style="font-size: 15px;font-weight:800;color:#020202;"><?php echo $order['name']?><br>
      <div style="display: flex;justify-content: center;">
          <strong style="font-size: 22px;color: #000000;padding-top: 6px;margin-right: 3px;">¥</strong>
          <strong style="font-size: 40px;color: #000000;"><?php echo $order['realmoney']?></strong>
      </div>
  </div>
</div>

<div style="background: #fff;padding: 16px;border-top: 1px solid #d8d8d8;border-bottom: 1px solid #d8d8d8;">
    <div style="display: flex;">
        <span style="font-weight: 400;color: #a1a1a1;width: 40px;">商家</span>
        <span style="flex:1;text-align: right;color: black;font-weight: 600;font-size: 14px;">微信支付平台商户</span>
    </div>
</div>

<div style="margin-top: 1px;border-radius: 1px;">
<div style="display: flex; justify-content: center; padding-top: 20px;">
  <a class="immediate_pay" style="width:100%;max-width:600px;border-radius: 10px;margin: 0 4px;background: #05c160;padding: 12px 0px;text-align:center;color: #fff;" onclick="callpay()"><font size="4">立即支付</font></a>
</div>

<div style="position: fixed;width: 100%;text-align: center;color: #a1a1a1;bottom: 17px;font-size: 12px;">
    支付安全由中国人民财产保险股份有限公司承保
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
	document.body.addEventListener('touchmove', function (event) {
		event.preventDefault();
	},{ passive: false });
    //调用微信JS api 支付
	function jsApiCall()
	{
		WeixinJSBridge.invoke(
			'getBrandWCPayRequest',
			<?php echo $jsApiParameters; ?>,
			function(res){
				if(res.err_msg == "get_brand_wcpay_request:ok" ) {
					loadmsg();
				}
				//WeixinJSBridge.log(res.err_msg);
				//alert(res.err_code+res.err_desc+res.err_msg);
			}
		);
	}

	function callpay()
	{
		if (typeof WeixinJSBridge == "undefined"){
		    if( document.addEventListener ){
		        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
		    }else if (document.attachEvent){
		        document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
		        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
		    }
		}else{
		    jsApiCall();
		}
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
    window.onload = callpay();
</script>
</div>
</div>
</body>
</html>