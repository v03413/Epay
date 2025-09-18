<?php
$is_defend = true;
include("./inc.php");
@header('Content-Type: text/html; charset=UTF-8');
$trade_no=daddslashes($_GET['trade_no']);
$row=$DB->getRow("SELECT * FROM pre_order WHERE trade_no='{$trade_no}' limit 1");
if(!$row)showerror('订单号不存在');
if($row['status']!=1)showerror('订单未完成支付');
if(!isset($_SESSION['paypage_trade_no']) || $_SESSION['paypage_trade_no']!=$trade_no)showerror('订单校验失败');
$userrow=$DB->getRow("select codename,username from pre_user where uid='{$row['uid']}' limit 1");
$codename = !empty($userrow['codename'])?$userrow['codename']:$userrow['username'];
?>
<html class="weui-msg">
<head>
    <meta charset="UTF-8">
    <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>支付成功页面</title>
    <link href="/assets/css/weui.min.css" rel="stylesheet">
    <style>.page{position:absolute;top:0;right:0;bottom:0;left:0;overflow-y:auto;-webkit-overflow-scrolling:touch;box-sizing:border-box}</style>
</head>
<body>
<div class="container">
<div class="page">
<div class="weui-msg">
    <div class="weui-msg__icon-area">
        <i class="weui-icon-success weui-icon_msg"></i>
    </div>
    <div class="weui-msg__text-area">
        <h2 class="weui-msg__title">支付成功</h2>
		<h2 class="weui-msg__title"><span style="font-size:38px;font-weight:700;color:#f40;">¥<?php echo $row['money']?></span></h2>
		<div class="weui-msg__custom-area">
			<div class="weui-cells">
			  <div class="weui-cell weui-cell_example">
				<span class="weui-cell__bd">收款方</span>
				<span class="weui-cell__ft"><strong><?php echo $codename?></strong></span>
			  </div>
			  <div class="weui-cell weui-cell_example">
				<span class="weui-cell__bd">完成时间</span>
				<span class="weui-cell__ft"><?php echo $row['endtime']?></span>
			  </div>
			  <div class="weui-cell weui-cell_example">
				<span class="weui-cell__bd">订单号</span>
				<span class="weui-cell__ft"><?php echo $trade_no?></span>
			  </div>
			</div>
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
<script src="//open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
<script src="js/close.js"></script>
<script>
document.body.addEventListener('touchmove', function (event) {
	event.preventDefault();
},{ passive: false });
</script>
</body>
</html>