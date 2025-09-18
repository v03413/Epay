<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>快捷支付页面</title>
    <!-- Bootstrap 5 CSS -->
    <link href="<?php echo $cdnpublic ?>twitter-bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 图标 -->
    <link rel="stylesheet" href="<?php echo $cdnpublic ?>font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="/assets/css/datepicker.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4cc9f0;
            --light-bg: #f8f9fa;
            --card-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .payment-container {
            max-width: 800px;
            margin: 40px auto;
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
        }
        
        .payment-header {
            background: var(--primary-color);
            color: white;
            padding: 25px 30px;
            text-align: center;
        }
        
        .payment-header h2 {
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .payment-header h2 i {
            margin-right: 12px;
        }
        
        .payment-body {
            padding: 30px;
        }

        .credit-card-fields {
            display: flex;
            gap: 15px;
        }
        
        .credit-card-fields .form-group {
            flex: 1;
        }
        
        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }

        .bank-info-row {
            display: flex;
            align-items: center;
            margin-top: 8px;
            display: none; /* 默认隐藏 */
        }
        
        .bank-name-display {
            font-weight: 600;
            color: var(--primary-color);
            margin-right: 15px;
        }
        
        .card-type-display {
            background: var(--success-color);
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 13px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .sms-group {
            display: flex;
            gap: 10px;
        }
        
        .sms-group .form-group {
            flex: 1;
        }
        
        .timer {
            display: inline-block;
            width: 30px;
            text-align: center;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .payment-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fa fa-bolt"></i> 快捷支付</h2>
            <p class="mb-0">安全、便捷的支付体验</p>
        </div>
        
        <form id="paymentForm" class="payment-body">
            <div class="mb-4">
                <div class="card mb-4" style="border-left: 4px solid var(--primary-color);">
                    <div class="card-body py-2">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-book me-2 fa-fw text-muted"></i>
                                    <div>
                                        <small class="text-muted">订单号</small>
                                        <div><?php echo TRADE_NO ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <i class="fa fa-leaf me-2 fa-fw text-muted"></i>
                                    <div>
                                        <small class="text-muted">订单金额</small>
                                        <div class="fw-bold" style="color:coral">¥<?php echo $order['realmoney'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <h4 class="mb-3">请输入快捷支付绑卡信息</h4>
            </div>
            
            <div class="mb-3">
                <label class="form-label">银行卡号</label>
                <input type="text" class="form-control form-control-lg" id="card-number" placeholder="请输入银行卡号" required pattern="\d{15,19}" maxlength="19">
                <div class="invalid-feedback">请输入有效的银行卡号</div>
                <div class="bank-info-row mt-2" id="bank-info-display">
                    <span class="bank-name-display" id="bank-name-display">-</span>
                    <span class="card-type-display" id="card-type-display">-</span>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">手机号码</label>
                <input type="tel" class="form-control form-control-lg" id="phone-number" placeholder="银行卡绑定的手机号码" required pattern="1[3-9]\d{9}" maxlength="11">
                <div class="invalid-feedback">请输入有效的手机号码</div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">持卡人姓名</label>
                    <input type="text" class="form-control form-control-lg" id="cardholder-name" placeholder="请输入姓名" required pattern="[\u4E00-\u9FA5]{2,20}" maxlength="20">
                    <div class="invalid-feedback">请输入正确的姓名</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">身份证号码</label>
                    <input type="text" class="form-control form-control-lg" id="id-number" placeholder="请输入身份证号" required pattern="\d{17}[\dXx]" maxlength="18">
                    <div class="invalid-feedback">请输入有效的身份证号码</div>
                </div>
            </div>
            
            <div id="credit-card-fields" class="mb-3" style="display:none;">
                <label class="form-label">信用卡信息</label>
                <div class="credit-card-fields">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" id="expiry-date" placeholder="到期时间" required>
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" id="cvv" placeholder="CVV码" maxlength="3" required pattern="\d{3}">
                        <div class="invalid-feedback">请输入有效的CVV码</div>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">短信验证码</label>
                <div class="sms-group">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-lg" id="bind-sms-code" placeholder="请输入短信验证码" required>
                    </div>
                    <button class="btn btn-outline-primary" id="get-sms-code">
                        获取验证码
                    </button>
                </div>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="agree-protocol" required>
                <label class="form-check-label" for="agree-protocol">
                    我已阅读并同意<a href="/pay/agreement/<?php echo TRADE_NO?>/" target="_blank">《新生支付服务协议》</a>
                </label>
                <div class="invalid-feedback">请阅读并同意支付服务协议</div>
            </div>

            <div class="d-grid">
                <button class="btn btn-primary btn-lg" id="confirm-payment" disabled>
                    确认支付 <i class="fa fa-check ms-2"></i>
                </button>
            </div>
        </form>
    </div>

    <script src="<?php echo $cdnpublic ?>jquery/3.4.1/jquery.min.js"></script>
    <script src="<?php echo $cdnpublic ?>twitter-bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $cdnpublic ?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
    <script src="<?php echo $cdnpublic ?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
    <script src="<?php echo $cdnpublic ?>jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="<?php echo $cdnpublic ?>layer/3.1.1/layer.js"></script>

    <script>
    $(document).ready(function() {
        
        // 当前状态
        let currentStep = 1;
        let smsToken = null;
        let bankCardType = null;
        let protocol = null;

        $('input').on('input', function() {
            $(this).removeClass('is-invalid');
        });

        $('#phone-number').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        $('#card-number').on('input', function() {
            this.value = this.value.replace(/\D/g, '');
        });

        $('#expiry-date').datepicker({
            format: 'mm/yy',
            startView: "months",
            minViewMode: "months",
            autoclose: true,
            clearBtn: true,
            language: 'zh-CN'
        });

        // 银行卡号输入事件
        $('#card-number').on('change', function() {
            let cardNumber = $(this).val().replace(/\s/g, '');

            if (/^\d{16,19}$/.test(cardNumber)) {
                const formData = {
                    action: 'query_card',
                    cardno: cardNumber,
                };
                var ii = layer.load(2, {shade:[0.1,'#fff']});
                $.ajax({
                    url: '?',
                    method: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        layer.close(ii);
                        if (response.code == 0) {
                            $('#bank-name-display').text(response.data.bank_name);
                            var card_type_arr = {
                                'DC': '储蓄卡',
                                'CC': '信用卡',
                                'SCC': '准贷记卡',
                                'PC': '预付费卡',
                            };
                            $('#card-type-display').text(card_type_arr[response.data.card_type]);
                            $('#bank-info-display').show();
                            bankCardType = response.data.card_type == 'CC' ? 'credit' : 'debit';
                            if(response.data.card_type == 'CC'){
                                $('#credit-card-fields').show();
                            }else{
                                $('#credit-card-fields').hide();
                            }
                        } else {
                            $('#bank-info-display').hide();
                            layer.alert(response.msg, {icon: 2});
                        }
                    },
                    error: function() {
                        layer.close(ii);
                        alert('网络请求异常，请检查网络连接');
                    }
                });
            } else {
                $('#bank-info-display').hide();
                $('#credit-card-fields').hide();
            }
        });

        $('#get-sms-code').click(function(){
            let isValid = true;

            $('#paymentForm input:visible').each(function() {
                const $input = $(this);
                const pattern = new RegExp($input.attr('pattern'));
                
                if (!pattern.test($input.val())) {
                    $input.addClass('is-invalid');
                    isValid = false;
                }
            });

            if (!isValid) return;

            const $btn = $(this).prop('disabled', true);

            const formData = {
                action: 'request',
                phone: $('#phone-number').val(),
                cardno: $('#card-number').val(),
                cardtype: bankCardType,
                name: $('#cardholder-name').val(),
                idcard: $('#id-number').val(),
                expiry: $('#expiry-date').val(),
                cvv: $('#cvv').val(),
            };

            var ii = layer.load(2, {shade:[0.1,'#fff']});
            $.ajax({
                url: '?',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    layer.close(ii);
                    $btn.prop('disabled', false);
                    if (response.code == 0) {
                        smsToken = response.token;
                        layer.msg('短信验证码已发送，请注意查收', {icon: 1, time: 1500});
                        $('#confirm-payment').prop('disabled', false);
                        startBindSmsCountdown();
                    } else {
                        layer.alert(response.msg, {icon: 2});
                    }
                },
                error: function() {
                    layer.close(ii);
                    $btn.prop('disabled', false);
                    alert('网络请求异常，请检查网络连接');
                }
            });
        })

        $('#confirm-payment').click(function(){
            if (!smsToken) {
                layer.alert('请先获取短信验证码');
                return;
            }
            const smsCode = $('#bind-sms-code').val();
            if (smsCode == '') {
                layer.alert('请输入短信验证码');
                return;
            }
            if(!$('#agree-protocol').is(':checked')) {
                layer.alert('请阅读并同意支付服务协议');
                return;
            }

            const $btn = $(this).prop('disabled', true);

            const formData = {
                action: 'confirm',
                phone: $('#phone-number').val(),
                token: smsToken,
                smscode: smsCode,
            };

            var ii = layer.load(2, {shade:[0.1,'#fff']});
            $.ajax({
                url: '?',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    layer.close(ii);
                    $btn.prop('disabled', false);
                    if (response.code == 0) {
                        $.cookie('fastpay_phone', $('#phone-number').val(), {expires: 365, path: '/'});
                        $.cookie('fastpay_cardno', $('#card-number').val(), {expires: 365, path: '/'});
                        $.cookie('fastpay_name', $('#cardholder-name').val(), {expires: 365, path: '/'});
                        $.cookie('fastpay_idcard', $('#id-number').val(), {expires: 365, path: '/'});
                        layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					    setTimeout(window.location.href=response.backurl, 1000);
                    } else {
                        layer.alert(response.msg, {icon: 2});
                    }
                },
                error: function() {
                    layer.close(ii);
                    $btn.prop('disabled', false);
                    alert('网络请求异常，请检查网络连接');
                }
            });
        })
        
        function startBindSmsCountdown() {
            let timeLeft = 60;
            const button = $('#get-sms-code');
            const originalHtml = button.html();
            
            button.prop('disabled', true);
            
            const countdown = setInterval(function() {
                timeLeft--;
                button.html(`重新发送(${timeLeft})`);
                
                if (timeLeft <= 0) {
                    clearInterval(countdown);
                    button.html(originalHtml);
                    button.prop('disabled', false);
                }
            }, 1000);
        }

        if($.cookie('fastpay_phone') && $.cookie('fastpay_cardno')) {
            $('#phone-number').val($.cookie('fastpay_phone'));
            $('#card-number').val($.cookie('fastpay_cardno'));
            $('#cardholder-name').val($.cookie('fastpay_name'));
            $('#id-number').val($.cookie('fastpay_idcard'));
            $('#card-number').change();
        }

        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "bank", trade_no: "<?php echo $order['trade_no']?>"},
            success: function (data) {
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
                }
            }
        });
    });
    </script>
</body>
</html>
