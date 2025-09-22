<?php
if(!defined('IN_PLUGIN'))exit();
$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
if(strpos($useragent, 'iphone')!==false || strpos($useragent, 'ipod')!==false){
	$background_img = '/assets/img/ios.png';
}else{
	$background_img = '/assets/img/android.png';
}
?><!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <title>支付提示</title>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
    <meta name="format-detection" content="telephone=no"/>
    <meta name="format-detection" content="email=no"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=0"/>
    <style>
*,:after,:before{-webkit-tap-highlight-color:transparent}
blockquote,body,dd,div,dl,dt,fieldset,form,h1,h2,h3,h4,h5,h6,input,legend,li,ol,p,td,textarea,th,ul{margin:0;padding:0}
table{border-collapse:collapse;border-spacing:0}
fieldset,img{border:0}
li{list-style:none}
caption,th{text-align:left}
q:after,q:before{content:""}
input:password{ime-mode:disabled}
:focus{outline:0}
body,html{-webkit-touch-callout:none;touch-callout:none;-webkit-user-select:none;user-select:none;-webkit-tap-highlight-color:transparent;tap-highlight-color:transparent;height:100%;margin:0;padding:0;text-align:center;font-size:15px;font-weight:300;font-family:"Helvetica Neue",Helvetica,Arial,"Lucida Grande",sans-serif}
a{text-decoration:none}
body{background:#F4F4F8}
.weixin-tip{-webkit-box-sizing:border-box;box-sizing:border-box;position:absolute;top:15px;right:20px;width:265px;padding:55px 0 0;text-align:left;background:url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFoAAACICAMAAABQgAwUAAAAMFBMVEUAAADY2NjY2NjY2NjY2NjY2NjY2NjY2NjX19fY2NjY2NjY2NjY2NjY2NjY2NjY2Njr/TvvAAAAD3RSTlMAxy89c9CdTRyG7lvcD7FzqbJAAAACFklEQVR42uWYy4rkMBAErZdlPdzx/3+7LAw0tH0Y2orDsnnyKQlSVaWytoc6xrEpigFoinUAIBnWABAE5woW9o6GPbGwI1jYGSzsgoV9goU9wMLe0bA7FnYCC7uBhV2wsE+wsAdY2AENGyzsBBZ2Q8MuWNgH94pLbgELO6Bhg4VdwcJuaNgTCzuChZ3Bwg5o2GBhV7CwdzTsjoUdwcLOYGEXLOwTLOwBFvaOht2xsBNY2I1f6lhaenvhrfpkAblab+k9b/OD0iuX2F9/x8D+7ZL2pmpbuj+6o3Vg//oWmPU9p65VkXL6+oIJ8S738nwj62Pb1lvHACH+fBs7sG59U3yrVD3rce3GVcp8qGkPAGTprQUYy6xfaE8i82b6S7/pfZnzdYQIHeOXdfYKpHoFcmrvWlM8RW+CDO8JMWoNM/+FeyB4UfMpL48g5qG1Iqc29YI3mqq2knXvEJu2onJoQy9ok4mkQZf/GjqitUvQyqN6SU8NOvOhHq25xNCWj6LFQdLiyKuaZWpxBC2OrFVHxdryElbQsVtBx6KN0qAd4a71yo610uxa2b0s5xg052I5p26d4MCqusZFwzrAnqQhSogSMnkNcr+GUS3kEKWS62NJFlNCToWLZpWMe14RReGqdjz2PfNECbkGbrQ/Nj5q5y7j8/HRTW5UhvHfA7Mdzitji8rfWsgX3gVZ91eO22odKed6LLf9A/sRnc74RV7lAAAAAElFTkSuQmCC) no-repeat right top;background-size:45px 68px}
.weixin-tip-img{padding:110px 0 0}
.weixin-tip-img::after{display:block;margin:15px auto;content:' ';background-size:cover;width:150px;height:150px;background-image:url('<?php echo $background_img?>')}
    </style>
</head>
<body>
<div class="J-weixin-tip weixin-tip">
    <div class="weixin-tip-content">
        请在菜单中选择在浏览器中打开,<br/>
        以完成支付
    </div>
</div>
<div class="J-weixin-tip-img weixin-tip-img"></div>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "alipay", trade_no: "<?php echo $order['trade_no']?>"},
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
    window.onload = function(){
		setTimeout("loadmsg()", 5000);
	}
</script>
</body>
</html>