<?php
if(!defined('IN_CRONLITE'))exit();
?><html class="weui-msg">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>红包领取确认</title>
    <link href="/assets/css/weui.min.css" rel="stylesheet">
    <style>.page{position:absolute;top:0;right:0;bottom:0;left:0;overflow-y:auto;-webkit-overflow-scrolling:touch;box-sizing:border-box}</style>
</head>
<body>
<div class="container">
<div class="page">
<div class="weui-msg">
    <div class="weui-msg__icon-area" style="margin-top:20px">
        <i class="weui-icon-waiting weui-icon_msg"></i>
    </div>
    <div class="weui-msg__text-area">
        <h2 class="weui-msg__title"><span style="font-size:18px;">待你收款</span></h2>
		<p class="weui-msg__desc"><span style="font-size:34px;font-weight:700;line-height: 64px;">¥</span><span style="font-size:44px;font-weight:700;vertical-align:top;"><?php echo $trans['money']?></span></p>
        <div class="weui-msg__custom-area">
            <ul class="weui-form-preview__list">
                <li role="option" class="weui-form-preview__item"><label class="weui-form-preview__label">创建时间</label><p class="weui-form-preview__value weui-cell__ft"><?php echo $trans['addtime']?></p></li>
            </ul>
        </div>
    </div>
    <div class="weui-msg__opr-area">
        <p class="weui-btn-area">
            <a href="javascript:;" class="weui-btn weui-btn_primary" id="Confirm" disabled>收款</a>
        </p>
    </div>
    <div class="weui-msg__tips-area">
        <p class="weui-msg__tips">请在24小时内确认</p>
    </div>
    <div class="weui-msg__extra-area">
        <div class="weui-footer"><p class="weui-footer__links"></p><p class="weui-footer__text">Copyright © <?php echo date("Y")?> <?php echo $conf['sitename']?></p></div>
    </div>
</div>
    <div role="alert" id="loadingToast">
        <div class="weui-mask_transparent"></div>
        <div class="weui-toast__wrp">
          <div class="weui-toast">
              <span class="weui-primary-loading weui-icon_toast">
                <span class="weui-primary-loading__dot"></span>
              </span>
              <p class="weui-toast__content">正在加载</p>
          </div>
        </div>
    </div>
    <div class="js_dialog" role="dialog" aria-hidden="true" aria-modal="true" aria-labelledby="dialog_title" id="iosDialog" style="display: none;">
        <div class="weui-mask"></div>
        <div class="weui-dialog">
            <div class="weui-dialog__hd"><strong class="weui-dialog__title" id="dialog_title">提示</strong></div>
            <div class="weui-dialog__bd" id="dialog_content"></div>
            <div class="weui-dialog__ft">
                <a role="button" href="javascript:" id="dialogClose" class="weui-dialog__btn weui-dialog__btn_primary">关闭</a>
            </div>
        </div>
    </div>
</div>
</div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="//res.wx.qq.com/open/js/jweixin-1.6.0.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
wx.config(<?php echo $wxconfig?>);
wx.ready(function () {
  $('#loadingToast').fadeOut(100);
  wx.checkJsApi({
    jsApiList: ['requestMerchantTransfer'],
    success: function (res) {
      if (res.checkResult['requestMerchantTransfer']) {
        $('#Confirm').removeAttr('disabled');
      } else {
        alert('你的微信版本过低，请更新至最新版本。');
      }
    }
  });
});
wx.error(function(res){
  //$('#loadingToast').fadeOut(100);
  //alert(res.errMsg);
});
function showDialog(title, content) {
    $('#dialog_title').text(title);
    $('#dialog_content').text(content);
    $('#iosDialog').fadeIn(100);
}

var wxtransfer = null;
$(document).ready(function(){
  $("#dialogClose").click(function(){
    $('#iosDialog').fadeOut(100);
  });
  $("#Confirm").click(function(){
    if(wxtransfer){
      WeixinJSBridge.invoke('requestMerchantTransfer', wxtransfer,
        function (res) {
          if (res.err_msg === 'requestMerchantTransfer:ok') {
            window.location.href = response.redirect_url;
          }
        }
      );
      return;
    }
    
    $('#loadingToast').fadeIn(100);
    $.ajax({
      type: "POST",
      url: "./red_ajax.php",
      data: {n: "<?php echo $biz_no?>", t: "<?php echo $time?>", s: "<?php echo $sign?>", openid: "<?php echo $openid?>"},
      dataType: "json",
      success: function(response) {
        $('#loadingToast').fadeOut(100);
        if(response.code == 0) {
          if(response.wxtransfer){
            wxtransfer = response.wxtransfer;
            WeixinJSBridge.invoke('requestMerchantTransfer', wxtransfer,
              function (res) {
                if (res.err_msg === 'requestMerchantTransfer:ok') {
                  window.location.href = response.redirect_url;
                }
              }
            );
          }else{
            window.location.href = response.redirect_url;
          }
        } else {
          showDialog('错误提示', response.msg);
        }
      },
      error: function() {
        $('#loadingToast').fadeOut(100);
        showDialog('错误提示', '网络异常，请稍后再试！');
      }
    });
  });
})
</script>
</body>
</html>