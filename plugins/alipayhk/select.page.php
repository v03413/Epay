<?php
if(!defined('IN_PLUGIN'))exit();?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-cn">
<meta name="renderer" content="webkit">
<title>支付宝支付</title>
<link href="/assets/css/alipay_pay.css?v=2" rel="stylesheet" media="screen">
<style>
.alipay-row{display:flex;justify-content:center;align-items:center;margin-top:35px}
.alipay-col{margin:0 25px;text-align:center}
.alipay-col p{margin:3px 0 10px;font-size:14px;color:#000}
.alipay-logo{width:100px}
.alipay-logo:hover{box-shadow:1px 3px 10px #888}
</style>
</head>
<body>
<div class="body">
<h1 class="mod-title">
<span class="ico-wechat"></span><span class="text">支付宝支付</span>
</h1>
<div class="mod-ct">
<div class="order">
</div>
<div class="amount">¥<?php echo $order['realmoney']?></div>
<div style="margin-top: 30px">
<div style="font-size:16px">请选择付款APP</div>
<div style="font-size:14px;color:#999">Please select your wallet region</div>
<div class="alipay-row">
    <div class="alipay-col">
        <a href="?type=ALIPAYCN"><img class="alipay-logo" src="https://payment.pa-sys.com/imgs/alipay-cn-20240905.png">
        <p>支付宝(中国)</p></a>
    </div>
    <div class="alipay-col">
        <a href="?type=ALIPAYHK"><img class="alipay-logo" src="https://payment.pa-sys.com/imgs/alipay-hk-20240905.png">
        <p>AlipayHK</p></a>
    </div>
</div>
<div></div>
</div>
<div class="detail detail-open" id="orderDetail">
<dl class="detail-ct">
<dt>购买物品</dt>
<dd id="productName"><?php echo $order['name']?></dd>
<dt>商户订单号</dt>
<dd id="billId"><?php echo $order['trade_no']?></dd>
<dt>创建时间</dt>
<dd id="createTime"><?php echo $order['addtime']?></dd>
</dl>
</div>
<div class="tip">
<span class="dec dec-left"></span>
<span class="dec dec-right"></span>
</div>
<div class="tip-text">
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "alipay", trade_no: "<?php echo $order['trade_no']?>"},
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
	window.onload = function(){
		setTimeout("loadmsg()", 2000);
	}
</script>
</body>
</html>