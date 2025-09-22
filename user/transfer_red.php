<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='创建红包';
include './head.php';
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">创建红包</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
<div class="row">
	<div class="col-sm-12 col-md-10 col-lg-8 center-block" style="float: none;">
<?php

if(!$conf['user_transfer']) showmsg('未开启代付功能');
if(!$conf['user_transfer_red']) showmsg('未开启创建红包功能');

if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];

$app = isset($_GET['app'])?$_GET['app']:'alipay';

if(isset($_POST['submit'])){
	if(!checkRefererHost())exit();
	$out_biz_no = trim($_POST['out_biz_no']);
	$money = trim($_POST['money']);
	$desc = htmlspecialchars(trim($_POST['desc']));
	$pwd = trim($_POST['paypwd']);
	$pwdenc = getMd5Pwd($pwd, $userrow['uid']);
	if(empty($pwd) || $pwdenc!==$userrow['pwd'])showmsg('登录密码输入错误',3);
	if(empty($out_biz_no) || empty($money))showmsg('必填项不能为空',3);
	if(strlen($out_biz_no)!=19 || !is_numeric($out_biz_no))showmsg('交易号输入不规范',3);
	if($desc && mb_strlen($desc)>32)showmsg('转账备注最多32个字',3);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)showmsg('转账金额输入不规范',3);
	$need_money = round($money + $money*$conf['transfer_rate']/100,2);
	if($userrow['settle']==0)showmsg('您的商户出现异常，无法使用代付功能',3);

	$result = \lib\Transfer::red_add($uid, $app, $out_biz_no, $money, $desc);

	if($result['code']==0){
		$result='红包创建成功！请在付款记录列表查看红包二维码。';
		$_SESSION['transfer_desc'] = $desc;
		showmsg($result,1,'./transfer.php');
	}else{
		$result='转账失败，'.$result['msg'];
		showmsg($result,4);
	}
}

$out_biz_no = date("YmdHis").rand(11111,99999);
$desc = $_SESSION['transfer_desc'];

if($conf['settle_type']==1){
	$today=date("Y-m-d").' 00:00:00';
	$order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$uid} and tid<>2 and status=1 and endtime>='$today'");
	if(!$order_today) $order_today = 0;
	$enable_money=round($userrow['money']-$order_today,2);
	if($enable_money<0)$enable_money=0;
}else{
	$enable_money=$userrow['money'];
}

?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			创建红包
		</div>
		<div class="panel-body">
			<ul class="nav nav-tabs">
				<?php if($conf['transfer_alipay']>0 || $conf['transfer_alipay']==-1){?><li class="<?php echo $app=='alipay'?'active':null;?>"><a href="?app=alipay">支付宝</a></li><?php }?>
				<?php if($conf['transfer_wxpay']>0 || $conf['transfer_wxpay']==-1){?><li class="<?php echo $app=='wxpay'?'active':null;?>"><a href="?app=wxpay">微信</a></li><?php }?>
			</ul>

			<div class="tab-pane active" id="alipay">
          <form action="?app=<?php echo $app?>" method="POST" role="form">
			<input type="hidden" name="rate" value="<?php echo $conf['transfer_rate']?>"/>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">可转账余额</div>
				<input type="text" value="<?php echo $enable_money?>" class="form-control" disabled/>
				<?php if($conf['recharge']==1){?><div class="input-group-btn"><a href="./recharge.php" class="btn btn-default">充值</a></div><?php }?>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">红包金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">需支付金额</div>
				<input type="text" name="need" value="" class="form-control" disabled/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">红包备注</div>
				<input type="text" name="desc" value="<?php echo $desc?>" class="form-control" placeholder="选填，默认为：<?php echo $conf['transfer_desc']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">验证登录密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="立即转账" class="btn btn-primary form-control"/></p>
          </form>
        </div>
		</div>
		<div class="panel-footer">
		<h4><span class="glyphicon glyphicon-info-sign"></span>注意事项</h4>
		  红包和代付的区别是，红包不需要填写收款人账号。红包创建成功之后会生成一个二维码，收款人使用微信/支付宝扫码之后即可自动领取到账。<br/><br/>
		  代付手续费是<?php echo $conf['transfer_rate']; ?>%<?php if($conf['transfer_minmoney']>0)echo '，单笔最小代付'.$conf['transfer_minmoney'].'元'; if($conf['transfer_maxmoney']>0)echo '，单笔最大代付'.$conf['transfer_maxmoney'].'元';?>
		  <?php if($conf['settle_type']==1){?><br/>可转账余额为截止到前一天你的收入+充值的余额。<?php }?>
        </div>
      </div>
	</div>
    </div>
  </div>
</div>
</div>
<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
function showneed(){
	var money = parseFloat($("input[name='money']").val());
	var rate = parseFloat($("input[name='rate']").val());
	if(isNaN(money) || isNaN(rate))return;
	var need = (money + money * (rate/100)).toFixed(2);
	$("input[name='need']").val(need)
}
$(document).ready(function(){
	$("input[name='money']").blur(function(){
		showneed()
	});
})
</script>