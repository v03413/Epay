<?php
if(!defined('IN_CRONLITE'))exit();
?><html class="weui-msg">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>确认收款页面</title>
    <link href="/assets/css/weui.min.css" rel="stylesheet">
    <style>.page{position:absolute;top:0;right:0;bottom:0;left:0;overflow-y:auto;-webkit-overflow-scrolling:touch;box-sizing:border-box}</style>
</head>
<body>
<div class="container">
<div class="page">
<div class="weui-msg">
    <div class="weui-msg__icon-area" style="margin-top:20px">
        <i class="weui-icon-success weui-icon_msg"></i>
    </div>
    <div class="weui-msg__text-area">
        <h2 class="weui-msg__title"><span style="font-size:18px;">你已收款，资金已存入零钱</span></h2>
		<p class="weui-msg__desc"><span style="font-size:34px;font-weight:700;line-height: 64px;">¥</span><span style="font-size:44px;font-weight:700;vertical-align:top;"><?php echo $money?></span></p>
        <div class="weui-msg__custom-area">
            <ul class="weui-form-preview__list">
                <li role="option" class="weui-form-preview__item"><label class="weui-form-preview__label">转账时间</label><p class="weui-form-preview__value weui-cell__ft"><?php echo $addtime?></p></li>
                <li role="option" class="weui-form-preview__item"><label class="weui-form-preview__label">收款时间</label><p class="weui-form-preview__value weui-cell__ft"><?php echo $paytime?></p></li>
            </ul>
        </div>
    </div>
    <div class="weui-msg__opr-area">
        <p class="weui-btn-area">
            <a href="javascript:;" class="weui-btn weui-btn_default" id="Close">关闭</a>
        </p>
    </div>
    <div class="weui-msg__extra-area">
        <div class="weui-footer"><p class="weui-footer__links"></p><p class="weui-footer__text">Copyright © <?php echo date("Y")?> <?php echo $conf['sitename']?></p></div>
    </div>
</div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
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
</script>
</body>
</html>