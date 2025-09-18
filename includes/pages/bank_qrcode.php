<?php
// 银联云闪付扫码支付页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="Content-Language" content="zh-cn">
<meta name="renderer" content="webkit">
<title>银联云闪付扫码支付</title>
<link href="/assets/css/bank_pay.css?v=3" rel="stylesheet" media="screen">
</head>
<body>
<div class="body">
<h1 class="mod-title">
<span class="ico-wechat"></span><span class="text">银联云闪付扫码支付</span>
</h1>
<div class="mod-ct">
<div class="order">
</div>
<div class="amount">¥<?php echo $order['realmoney']?></div>
<div class="qr-image" id="qrcode">
</div>
<div class="open_app" style="display: none;">
    <a class="btn-open-app">打开云闪付APP继续付款</a><br/><br/><br/>
	<a onclick="checkresult()" class="btn-check">我已付款，返回查看订单</a>
</div>
<div class="detail" id="orderDetail">
<dl class="detail-ct" style="display: none;">
<dt>购买物品</dt>
<dd id="productName"><?php echo $order['name']?></dd>
<dt>商户订单号</dt>
<dd id="billId"><?php echo $order['trade_no']?></dd>
<dt>创建时间</dt>
<dd id="createTime"><?php echo $order['addtime']?></dd>
</dl>
<a href="javascript:void(0)" class="arrow"><i class="ico-arrow"></i></a>
</div>
<div class="tip">
<span class="dec dec-left"></span>
<span class="dec dec-right"></span>
<div class="ico-scan"></div>
<div class="tip-text">
<p>请使用银联云闪付扫一扫</p>
<p>扫描二维码完成支付</p>
</div>
</div>
<div class="tip-text">
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
	var code_url = '<?php echo $code_url?>';
    var code_type = code_url.indexOf('data:image/')>-1?1:0;
    if(code_type == 0){
        var url_scheme = 'upwallet://html/' + code_url.replace('https://', '').replace('http://', '');
        $('#qrcode').qrcode({
            text: code_url,
            width: 230,
            height: 230,
            foreground: "#000000",
            background: "#ffffff",
            typeNumber: -1
        });
    }else{
        $('#qrcode').html('<img src="'+code_url+'"/>');
    }
    // 订单详情
    $('#orderDetail .arrow').click(function (event) {
        if ($('#orderDetail').hasClass('detail-open')) {
            $('#orderDetail .detail-ct').slideUp(500, function () {
                $('#orderDetail').removeClass('detail-open');
            });
        } else {
            $('#orderDetail .detail-ct').slideDown(500, function () {
                $('#orderDetail').addClass('detail-open');
            });
        }
    });
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "bank", trade_no: "<?php echo $order['trade_no']?>"},
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
	function checkresult() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "bank", trade_no: "<?php echo $order['trade_no']?>"},
            success: function (data) {
                if (data.code == 1) {
                    layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
                }else{
					layer.msg('您还未完成付款，请继续付款', {shade: 0,time: 1500});
				}
            },
            error: function () {
                layer.msg('服务器错误');
            }
        });
    }
	var isMobile = function (){
		var ua = navigator.userAgent;
		var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
		isIphone =!ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
		isAndroid = ua.match(/(Android)\s+([\d.]+)/);
		return isIphone || isAndroid;
	}
    function wx_open(){
        layer.alert('请点击屏幕右上角，<b>在浏览器打开</b>即可跳转支付。<br/><font color="red">支付成功后，回到微信查看结果</font>', {title:'支付提示'});
    }
	window.onload = function(){
		if(isMobile() && code_type==0){
			$('.open_app').show();
            if(navigator.userAgent.indexOf('MicroMessenger/')>0){
                $('.btn-open-app').attr('onclick', 'wx_open()');
            }else{
                $('.btn-open-app').attr('href', url_scheme);
                if(navigator.userAgent.indexOf('EdgA/')==-1 && $(window).height() > $(window).width()){
                    window.location.href = url_scheme;
                }
            }
		}
		setTimeout("loadmsg()", 2000);
	}
</script>
</body>
</html>