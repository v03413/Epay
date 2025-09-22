<?php
// 微信手机扫码支付页面

if (!defined('IN_PLUGIN'))
    exit();
?>
<html lang="zh-cn">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
        <meta name="renderer" content="webkit" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title>微信支付手机版</title>
        <link href="<?php echo $cdnpublic ?>twitter-bootstrap/3.4.1/css/bootstrap.min.css" rel="stylesheet" />
        <link href="/assets/pay/css/mobile-style.css?v=7" rel="stylesheet" />
    </head>

    <body>
        <div class="main">
            <div class="bg-weixin"></div>
            <div class="payment-logo payment-logo-wxwap">
                <img src="/assets/pay/icon/wxpay-white.svg" alt="logo">
                <span class="logo-tile">微信支付</span>
            </div>
            <div class="payment-content">
                <h1 style="margin-top: 8px;margin-bottom: 16px;">¥<?php echo $order['realmoney']; ?></h1>
                <div class="scan-the-code">
                    <ul class="nav nav-group" role="tablist">
                        <li class="active">
                            <a href="JavaScript:;"><i class="icon-qrcode"></i>扫码支付</a>
                        </li>
                        <li>
                            <a href="JavaScript:downloadCanvas();"><i class="icon-download"></i>保存二维码</a>
                        </li>
                    </ul>
                    <div class="list-group">
                        <div class="list-group-item text-center">
                            <h5 class="qr-title">请使用微信APP扫描二维码支付</h5>
                            <div class="qr-image" id="qrcode"></div>
                            <div class="operate">
                                <a href="weixin://" class="btn btn-default">
                                    <span class="glyphicon glyphicon-new-window" aria-hidden="true"></span>
                                    <span>打开微信APP</span>
                                </a>
                                <a href="javascript:checkresult()" class="btn btn-default">
                                    <span class="glyphicon glyphicon-repeat" aria-hidden="true"></span>
                                    <span>检测支付状态</span>
                                </a>
                            </div>
                        </div>
                        <div class="list-group-item">
                            <span>二维码链接：<a href="<?php echo $code_url ?>"><?php echo $code_url ?></a></span>
                            <span><button id="copy-btn" data-clipboard-text="<?php echo $code_url ?>" class="btn btn-info btn-sm">复制</button></span>
                        </div>
                        <div class="list-group-item">
                            <small>提示：你可将以上二维码链接发到自己微信的聊天框（在微信顶部搜索框可以搜到自己的微信），点击即可进入支付！</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
        <script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script>
        <script src="<?php echo $cdnpublic ?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
        <script src="<?php echo $cdnpublic ?>clipboard.js/1.7.1/clipboard.min.js"></script>
        <script>
            var clipboard = new Clipboard('#copy-btn');
            clipboard.on('success', function (e) {
                layer.msg('复制成功，请到微信里面粘贴');
            });
            clipboard.on('error', function (e) {
                layer.msg('复制失败，请长按链接后手动复制');
            });
            $('#qrcode').qrcode({
                text: "<?php echo $code_url ?>",
                width: 230,
                height: 230,
                foreground: "#000000",
                background: "#ffffff",
                typeNumber: -1
            });
            function downloadCanvas() {
                var canvas = document.getElementsByTagName('canvas')[0];
                var url = canvas.toDataURL('image/png');
                var a = document.createElement('a');
                var event = new MouseEvent('click');
                a.download = '微信支付二维码.png';
                a.href = url;
                a.dispatchEvent(event);
            };
            function loadmsg() {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "/getshop.php",
                    data: {type: "wxpay", trade_no: "<?php echo $order['trade_no'] ?>"},
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
                    data: { type: "wxpay", trade_no: "<?php echo $order['trade_no'] ?>" },
                    success: function (data) {
                        if (data.code == 1) {
                            layer.msg('支付成功，正在跳转中...', { icon: 16, shade: 0.1, time: 15000 });
                            setTimeout(window.location.href = data.backurl, 1000);
                        } else {
                            layer.msg('您还未完成付款，请继续付款', { shade: 0, time: 1500 });
                        }
                    },
                    error: function () {
                        layer.msg('服务器错误');
                    }
                });
            }
            window.onload = function () {
                window.onpopstate = function (e) {
                    if (e.state == 'forward' || confirm('是否取消支付并返回？')) {
                        window.history.back();
                    } else {
                        e.preventDefault();
                        window.history.pushState('forward', null, '');
                    }
                };
                window.history.pushState('forward', null, '');
                setTimeout("loadmsg()", 3000);
            }
        </script>
    </body>

</html>