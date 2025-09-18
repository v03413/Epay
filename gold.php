<?php
/*
 * 微信点金计划iframe页面
*/
$nosession = true;
include("./includes/common.php");

@header('Content-Type: text/html; charset=UTF-8');

$sub_mch_id = $_GET['sub_mch_id'];
$out_trade_no = $_GET['out_trade_no'];
$check_code = $_GET['check_code'];

$errmsg = null;

if($out_trade_no){
	$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
	if(!$order)$order = $DB->getRow("SELECT * FROM pre_order WHERE api_trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
	if(!$order)$order = $DB->getRow("SELECT * FROM pre_order WHERE bill_mch_trade_no=:trade_no limit 1", [':trade_no'=>$out_trade_no]);
	if($order){
		$trade_no = $order['trade_no'];
		$jump_url = $siteurl.'pay/return/'.$trade_no.'/';
	}else{
		$errmsg = '订单号不存在<br/>out_trade_no='.$out_trade_no;
	}
}else{
	$errmsg = '订单号不能为空';
}

?><!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>支付结果页面</title>
    <meta content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=0" name="viewport"/>
    <meta content="yes" name="apple-mobile-web-app-capable"/>
    <meta content="black" name="apple-mobile-web-app-status-bar-style"/>
    <meta content="telephone=no" name="format-detection"/>
    <style>
*{margin:0;padding:0}
body{background-color:#f5f5f5}
.main{width:100%;height:100%;display:flex;justify-content:center;align-items:center}
.container{display:flex;flex-direction:column;align-items:center;margin-top:30px}
.container .icons{width:84px;height:84px}
.container .text{font-size:28px;color:#333;margin:20px 0}
.container .message{text-align:center}
.container .message p{font-size:14px;color:#666;word-break:break-all}
.container .btn a{width:100px;height:30px;margin:35px 0;font-size:14px;color:#fff;text-decoration:none;background-color:#07c160;border-radius:3px;display:flex;justify-content:center;align-items:center;padding:5px 15px}
    </style>
    <script type="text/javascript" charset="UTF-8" src="https://wx.gtimg.com/pay_h5/goldplan/js/jgoldplan-1.0.0.js"></script>
</head>
<body>
<?php if($errmsg){?>
    <div class="main">
        <div class="container">
            <div class="icons"><svg t="1704520210068" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="6182" xmlns:xlink="http://www.w3.org/1999/xlink" width="100%" height="100%"><path d="M512 56.888889C261.688889 56.888889 56.888889 261.688889 56.888889 512s204.8 455.111111 455.111111 455.111111 455.111111-204.8 455.111111-455.111111-204.8-455.111111-455.111111-455.111111m0 853.333333c-221.866667 0-398.222222-176.355556-398.222222-398.222222s176.355556-398.222222 398.222222-398.222222 398.222222 176.355556 398.222222 398.222222-176.355556 398.222222-398.222222 398.222222" fill="#fa5151" p-id="6183"></path><path d="M512 682.666667c-17.066667 0-28.444444 5.688889-39.822222 17.066666-11.377778 11.377778-17.066667 22.755556-17.066667 39.822223 0 17.066667 5.688889 28.444444 17.066667 39.822222 11.377778 11.377778 22.755556 17.066667 39.822222 17.066666 17.066667 0 28.444444-5.688889 39.822222-17.066666 11.377778-11.377778 17.066667-22.755556 17.066667-39.822222 0-17.066667-5.688889-28.444444-17.066667-39.822223-11.377778-11.377778-22.755556-17.066667-39.822222-17.066666z m-51.2-455.111111l17.066667 409.6h62.577777L563.2 227.555556H460.8z" fill="#fa5151" p-id="6184"></path></svg></div>
            <div class="text">错误提示</div>
            <div class="message">
                <p><?php echo $errmsg?></p>
            </div>
        </div>
    </div>
<script>
var mchData = {action:'onIframeReady', displayStyle:'SHOW_CUSTOM_PAGE'};
var postData = JSON.stringify(mchData);
parent.postMessage(postData,'https://payapp.weixin.qq.com');
</script>
<?php }else{?>
    <div class="main">
        <div class="container">
            <div class="icons"><svg t="1704509605113" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4373" xmlns:xlink="http://www.w3.org/1999/xlink" width="100%" height="100%"><path d="M512 896c-212.077 0-384-171.923-384-384s171.923-384 384-384 384 171.923 384 384-171.923 384-384 384z m0 64c247.424 0 448-200.576 448-448S759.424 64 512 64 64 264.576 64 512s200.576 448 448 448z" fill="#60b968" p-id="4374"></path><path d="M767.696 347.343a8 8 0 0 0-11.314 0L445.137 658.588 268.598 482.049a8 8 0 0 0-11.314 0l-33.941 33.941a8 8 0 0 0 0 11.314l197.99 197.99c0.973 0.973 1.993 1.87 3.053 2.692 12.572 10.837 31.57 10.293 43.496-1.634l333.755-333.754a8 8 0 0 0 0-11.314l-33.941-33.941z" fill="#60b968" p-id="4375"></path></svg></div>
            <div class="text">¥<?php echo $order['money']?></div>
            <div class="message">
                <p>支付成功！请点击按钮返回商家页面</p>
            </div>
            <div class="btn">
                <a href="javascript:JumpOut()">返回商家页面</a>
            </div>
        </div>
    </div>
<script>
var mchData = {action:'onIframeReady', displayStyle:'SHOW_CUSTOM_PAGE'};
var postData = JSON.stringify(mchData);
parent.postMessage(postData,'https://payapp.weixin.qq.com');
mchData = {action:'jumpOut', jumpOutUrl:'<?php echo $jump_url?>'};
postData = JSON.stringify(mchData);
parent.postMessage(postData,'https://payapp.weixin.qq.com');
var JumpOut = function(){
    mchData = {action:'jumpOut', jumpOutUrl:'<?php echo $jump_url?>'};
    postData = JSON.stringify(mchData);
    parent.postMessage(postData,'https://payapp.weixin.qq.com');
}
</script>
<?php }?>
</body>
</html>