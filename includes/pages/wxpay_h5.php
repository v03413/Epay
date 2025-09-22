<?php
// 微信H5支付页面

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
        <link href="/assets/pay/css/mobile-style.css" rel="stylesheet" />
    </head>

    <body>
        <div class="main">
            <div class="bg-weixin"></div>
            <div class="payment-logo">
                <img src="/assets/pay/icon/wxpay-white.svg" alt="logo">
                <span class="logo-tile">微信支付</span>
            </div>
            <div class="payment-content">
                <div class="content-info">
                    <h1>¥<?php echo $order['realmoney'] ?></h1>
                    <ul class="nk-activity">
                        <li class="nk-activity-item">
                            <span>商品名称：<?php echo $order['name'] ?></span>
                        </li>
                        <li class="nk-activity-item">
                            <span>商户订单号：<?php echo $order['trade_no'] ?></span>
                        </li>
                        <li class="nk-activity-item">
                            <span>创建时间：<?php echo $order['addtime'] ?></span>
                        </li>
                    </ul>
                </div>
                <div class="content-footer">
                    <a href="javascript:;" id="openUrl" class="btn btn-success btn-block btn-lg">跳转到微信支付</a>
                    <a href="javascript:checkresult()" onclick="" class="btn btn-info btn-block btn-lg">检测支付状态</a>
                </div>
            </div>
        </div>
        <script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
        <script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script>
        <script>
            var url_scheme = '<?php echo $code_url ?>';
            function loadmsg() {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "/getshop.php",
                    data: { type: "wxpay", trade_no: "<?php echo $order['trade_no'] ?>" },
                    success: function (data) {
                        if (data.code == 1) {
                            layer.msg('支付成功，正在跳转中...', { icon: 16, shade: 0.01, time: 15000 });
                            setTimeout(window.location.href = data.backurl, 1000);
                        } else {
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
                            layer.msg('支付成功，正在跳转中...', { icon: 16, shade: 0.01, time: 15000 });
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

                document.getElementById("openUrl").href = url_scheme;
                if (!url_scheme.startsWith('http://') && !url_scheme.startsWith('https://') && navigator.userAgent.indexOf('EdgA/') == -1) {
                    window.location.href = url_scheme;
                }
                setTimeout("loadmsg()", 3000);
            }
        </script>
    </body>

</html>