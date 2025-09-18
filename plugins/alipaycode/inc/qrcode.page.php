<?php
// 支付宝扫码支付页面
if (!defined('IN_PLUGIN')) exit();
?>
<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="renderer" content="webkit">
    <title>支付宝扫码支付</title>
    <link href="/assets/css/alipay_pay.css?v=3" rel="stylesheet" media="screen">
    <style>
        .countdown-container {
            margin: 15px auto;
            text-align: center;
        }

        .countdown-title {
            font-size: 16px;
            color: #e8501c;
            margin-bottom: 10px;
        }

        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .countdown-box {
            background-color: #1E9FFF;
            color: white;
            border-radius: 4px;
            padding: 5px 8px;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .countdown-box span {
            font-size: 18px;
            font-weight: bold;
        }

        .countdown-label {
            margin-left: 4px;
            font-size: 14px;
        }

        .qr-expired-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 10;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .qr-expired-text {
            color: #fff;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .qr-expired-icon {
            font-size: 40px;
            color: #FF5722;
            margin-bottom: 10px;
        }

        .qr-container {
            position: relative;
            display: inline-block;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <img src="/assets/img/guide1.png" alt="引导图" class="top-guide" style="max-width: 100%;margin: auto;display:none">
    <div class="guide" style="display:none"><img src="/assets/img/guide2.png" alt="引导图" style="width: 96%;"></div>
    <div class="body">
        <h1 class="mod-title">
            <span class="ico-wechat"></span><span class="text">支付宝扫码支付</span>
        </h1>
        <div class="mod-ct">
            <div class="order">
            </div>
            <div class="amount">￥<?php echo $order['realmoney'] ?></div>
            <div class="qr-container" style="margin-top: 20px;">
                <div class="qr-image" id="qrcode" style="margin-top: 0px;"></div>
                <!-- 二维码过期蒙层 -->
                <div class="qr-expired-overlay" id="qrExpiredOverlay">
                    <div class="qr-expired-icon">×</div>
                    <div class="qr-expired-text">二维码已失效</div>
                    <div class="qr-expired-text">请返回重新发起支付</div>
                </div>
            </div>
            <!-- 倒计时 -->
            <div class="countdown-container">
                <div class="countdown-title">支付剩余时间</div>
                <div class="countdown-timer">
                    <div class="countdown-box">
                        <span id="hours">00</span>
                        <span class="countdown-label">时</span>
                    </div>
                    <div class="countdown-box">
                        <span id="minutes">00</span>
                        <span class="countdown-label">分</span>
                    </div>
                    <div class="countdown-box">
                        <span id="seconds">00</span>
                        <span class="countdown-label">秒</span>
                    </div>
                </div>
            </div>
            <div class="open_app" style="display: none;">
                <a class="btn-open-app">打开支付宝APP继续付款</a><br /><br /><br />
                <a onclick="checkresult()" class="btn-check">我已付款，返回查看订单</a>
            </div>
            <div class="detail" id="orderDetail" style="margin-top: 0px;">
                <dl class="detail-ct" style="display: none;">
                    <dt>购买物品</dt>
                    <dd id="productName"><?php echo $order['name'] ?></dd>
                    <dt>商户订单号</dt>
                    <dd id="billId"><?php echo $order['trade_no'] ?></dd>
                    <dt>创建时间</dt>
                    <dd id="createTime"><?php echo $order['addtime'] ?></dd>
                </dl>
                <a href="javascript:void(0)" class="arrow"><i class="ico-arrow"></i></a>
            </div>
            <div class="tip">
                <span class="dec dec-left"></span>
                <span class="dec dec-right"></span>
                <div class="ico-scan"></div>
                <div class="tip-text">
                    <p>请使用支付宝扫一扫</p>
                    <p>扫描二维码完成支付</p>
                </div>
            </div>
        </div>
        <script src="<?php echo $cdnpublic ?>jquery/1.12.4/jquery.min.js"></script>
        <script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script>
        <script src="<?php echo $cdnpublic ?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
        <script>
            var code_url = '<?php echo $code_url ?>';
            var code_type = code_url.indexOf('data:image/') > -1 ? 1 : 0;
            if (code_type == 0) {
                var url_scheme = 'alipays://platformapi/startapp?appId=20000067&url=' + encodeURIComponent(code_url);
                $('#qrcode').qrcode({
                    text: code_url,
                    width: 230,
                    height: 230,
                    foreground: "#000000",
                    background: "#ffffff",
                    typeNumber: -1
                });
            } else {
                $('#qrcode').html('<img src="' + code_url + '"/>');
            }
            // 订单详情
            $('#orderDetail .arrow').click(function(event) {
                if ($('#orderDetail').hasClass('detail-open')) {
                    $('#orderDetail .detail-ct').slideUp(500, function() {
                        $('#orderDetail').removeClass('detail-open');
                    });
                } else {
                    $('#orderDetail .detail-ct').slideDown(500, function() {
                        $('#orderDetail').addClass('detail-open');
                    });
                }
            });

            function loadmsg() {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "/getshop.php",
                    data: {
                        type: "alipay",
                        trade_no: "<?php echo $order['trade_no'] ?>"
                    },
                    success: function(data) {
                        if (data.code == 1) {
                            layer.msg('支付成功，正在跳转中...', {
                                icon: 16,
                                shade: 0.1,
                                time: 15000
                            });
                            setTimeout(window.location.href = data.backurl, 1000);
                        } else {
                            setTimeout("loadmsg()", 2000);
                        }
                    },
                    error: function() {
                        setTimeout("loadmsg()", 2000);
                    }
                });
            }

            function checkresult() {
                $.ajax({
                    type: "GET",
                    dataType: "json",
                    url: "/getshop.php",
                    data: {
                        type: "alipay",
                        trade_no: "<?php echo $order['trade_no'] ?>"
                    },
                    success: function(data) {
                        if (data.code == 1) {
                            layer.msg('支付成功，正在跳转中...', {
                                icon: 16,
                                shade: 0.1,
                                time: 15000
                            });
                            setTimeout(window.location.href = data.backurl, 1000);
                        } else {
                            layer.msg('您还未完成付款，请继续付款', {
                                shade: 0,
                                time: 1500
                            });
                        }
                    },
                    error: function() {
                        layer.msg('服务器错误');
                    }
                });
            }
            var isMobile = function() {
                var ua = navigator.userAgent;
                var ipad = ua.match(/(iPad).*OS\s([\d_]+)/),
                    isIphone = !ipad && ua.match(/(iPhone\sOS)\s([\d_]+)/),
                    isAndroid = ua.match(/(Android)\s+([\d.]+)/);
                return isIphone || isAndroid;
            }

            function wx_open() {
                $(".guide").show();
                //layer.alert('请点击屏幕右上角，<b>在浏览器打开</b>即可跳转支付。<br/><font color="red">支付成功后，回到微信查看结果</font>', {title:'支付提示'});
            }
            window.onload = function() {
                if (isMobile()) {
                    window.onpopstate = function(e) {
                        if (e.state == 'forward' || confirm('是否取消支付并返回？')) {
                            window.history.back();
                        } else {
                            e.preventDefault();
                            window.history.pushState('forward', null, '');
                        }
                    };
                    window.history.pushState('forward', null, '');
                }
                if (isMobile() && code_type == 0) {
                    $('.open_app').show();
                    if (navigator.userAgent.indexOf('MicroMessenger/') > 0) {
                        $(".top-guide").show();
                        $('.btn-open-app').attr('href', 'javascript:wx_open()');
                    } else {
                        $('.btn-open-app').attr('href', url_scheme)
                        if (navigator.userAgent.indexOf('EdgA/') == -1) {
                            setTimeout(window.location.href = url_scheme, 1000);
                        }
                    }
                }
                $("body").click(function() {
                    if (!$(".guide").is(":hidden")) {
                        $(".guide").hide();
                    }
                });
                setTimeout("loadmsg()", 2000);
                startCountdown(<?php echo $paytime; ?>);
            }

            // 倒计时功能
            function startCountdown(duration) {
                var timer = duration;
                var hoursElement = document.getElementById('hours');
                var minutesElement = document.getElementById('minutes');
                var secondsElement = document.getElementById('seconds');
                var qrExpiredOverlay = document.getElementById('qrExpiredOverlay');
                if(duration <= 0){
                    qrExpiredOverlay.style.display = 'flex';
                    return;
                }

                var countdown = function() {
                    var hours = Math.floor(timer / 3600);
                    var minutes = Math.floor((timer % 3600) / 60);
                    var seconds = timer % 60;
                    // 填充前导零
                    hours = hours < 10 ? "0" + hours : hours;
                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;
                    // 更新显示
                    hoursElement.textContent = hours;
                    minutesElement.textContent = minutes;
                    secondsElement.textContent = seconds;
                    // 计时器递减
                    if (--timer < 0) {
                        // 时间到，清除定时器并显示过期蒙层
                        clearInterval(window.countdownInterval);
                        qrExpiredOverlay.style.display = 'flex';
                    }
                }
                countdown();
                window.countdownInterval = setInterval(countdown, 1000);
            }
        </script>
</body>

</html>