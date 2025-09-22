<?php
/*
 * 获取openid结果页面
*/
if(!defined('IN_CRONLITE'))exit();
?>
<html class="weui-msg">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>获取<?php echo $openid_name?></title>
    <link href="/assets/css/weui.min.css" rel="stylesheet">
    <style>.page{position:absolute;top:0;right:0;bottom:0;left:0;overflow-y:auto;-webkit-overflow-scrolling:touch;box-sizing:border-box}</style>
</head>
<body>
<div class="container">
<div class="page">
<div class="weui-form">
    <div class="weui-msg__icon-area">
        <i class="weui-icon-success weui-icon_msg"></i>
    </div>
    <div class="weui-form__text-area">
        <h2 class="weui-form__title">获取<?php echo $openid_name?>成功</h2>
    </div>
	<div class="weui-form__control-area">
      <div class="weui-cells__group weui-cells__group_form">
        <div class="weui-cells__title">如未自动填写，请手动复制下方<?php echo $openid_name?>：</div>
        <div class="weui-cells weui-cells_form">
            <div class="weui-cell weui-cell_active">
                <div class="weui-cell__bd">
                    <textarea class="weui-textarea" rows="2" style="text-align:center"><?php echo $openid_content?></textarea>
                </div>
            </div>
        </div>
      </div>
    </div>
    <div class="weui-form__opr-area">
		<a role="button" class="weui-btn weui-btn_default copy-btn" href="javascript:" data-clipboard-text="<?php echo $openid_content?>">点击复制</a>
        <a href="javascript:;" class="weui-btn weui-btn_warn" id="Close">关闭</a>
    </div>
    <div class="weui-form__extra-area">
        <div class="weui-footer"><p class="weui-footer__links"></p><p class="weui-footer__text">Copyright © <?php echo date("Y")?> <?php echo $conf['sitename']?></p></div>
    </div>
</div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
$(document).ready(function(){
	var clipboard = new Clipboard('.copy-btn');
	clipboard.on('success', function (e) {
		layer.msg('复制成功！', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
	});
});
if(navigator.userAgent.indexOf("AlipayClient/") > -1){
    function Alipayready(callback) {
        if (window.AlipayJSBridge) {
            callback && callback();
        } else {
            document.addEventListener('AlipayJSBridgeReady', callback, false);
        }
    }
    Alipayready(function(){
        $('#Close').click(function() {
            AlipayJSBridge.call('popWindow');
        });
    })
}else if(navigator.userAgent.indexOf("MicroMessenger/") > -1){
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
        $('#Close').click(function() {
            WeixinJSBridge.call('closeWindow');
        });
    }
}else if(navigator.userAgent.indexOf("QQ/") > -1){
    $('#Close').hide();
}else {
    $('#Close').click(function() {
        window.opener=null;window.close();
    });
}
</script>
</body>
</html>