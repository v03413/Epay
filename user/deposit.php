<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='保证金管理';
include './head.php';
?>
<?php
$urow = $DB->getRow("SELECT uid,gid FROM pre_user WHERE uid='{$conf['reg_pay_uid']}' limit 1");
if(!$urow)exit('充值收款商户不存在');
$paytype = \lib\Channel::getTypes($urow['uid'], $urow['gid']);
$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['csrf_token'] = $csrf_token;

if(!$userrow['deposit']) $userrow['deposit'] = 0;
$money1 = null;
if($conf['user_deposit_min']>$userrow['deposit']) $money1 = round($conf['user_deposit_min'] - $userrow['deposit'], 2);
$money2 = null;
if($userrow['deposit'] > 0) $money2 = $userrow['deposit'];
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">保证金管理</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="row">
	<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
	<?php if(isset($_GET['ok']) && $_GET['ok']==1){
	$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$_GET['trade_no']]);
	?>
	<div class="alert alert-success alert-dismissible" role="alert">
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	  恭喜你成功充值<strong><?php echo $order['money']?></strong>元保证金！
	</div>
	<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-certificate"></i>&nbsp;保证金管理
		</div>
		<div class="panel-body">
			<?php if($conf['user_deposit_min']>0){?><div class="alert alert-warning">
				<p>当前平台要求最低缴纳保证金<b><?php echo $conf['user_deposit_min']; ?></b>元，否则无法调用接口发起支付。</p>
			</div><?php }?>
			<form class="form-horizontal devform">
				<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
				<div class="form-group">
					<label class="col-sm-3 control-label">当前保证金余额</label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="deposit" value="<?php echo $userrow['deposit']?> 元" readonly="">
					</div>
				</div>
			</form>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-cny"></i>&nbsp;充值保证金
		</div>
		<div class="panel-body">
			<form class="form-horizontal devform">
				<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
				<div class="form-group">
					<label class="col-sm-3 control-label">要充值的金额</label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="money1" value="<?php echo $money1?>" autocomplete="off">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">支付方式</label>
					<div class="col-sm-8">
						<div class="radio">
						<label class="i-checks"><input type="radio" name="type" value="0"><i></i>余额支付</label>&nbsp;
						<?php foreach($paytype as $row){?>
						  <label class="i-checks"><input type="radio" name="type" value="<?php echo $row['id']?>"><i></i><?php echo $row['showname']?>
						  </label>&nbsp;
						<?php }?>
						</div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-3 col-sm-8"><input type="button" id="submit1" value="立即充值" class="btn btn-success form-control"/><br/>
				 </div>
				</div>
			</form>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="glyphicon glyphicon-edit"></i>&nbsp;提取保证金
		</div>
		<div class="panel-body">
			<form class="form-horizontal devform">
				<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
				<div class="form-group">
					<label class="col-sm-3 control-label">要提取的金额</label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="money2" value="<?php echo $money2?>" autocomplete="off">
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-3 col-sm-8"><input type="button" id="submit2" value="提取到商户余额" class="btn btn-success form-control"/><br/>
				 </div>
				</div>
			</form>
		</div>
	</div>
	</div>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
$(document).ready(function(){
	$("input[name=type]:first").attr("checked",true);
	$("#submit1").click(function(){
		var csrf_token=$("input[name='csrf_token']").val();
		var money=$("input[name='money1']").val();
		var typeid=$("input[name=type]:checked").val();
		if(money==''){
			layer.alert("金额不能为空");
			return false;
		}
		var ii = layer.load();
		$.ajax({
			type: "POST",
			dataType: "json",
			data: {money:money, typeid:typeid, csrf_token:csrf_token},
			url: "ajax2.php?act=deposit_recharge",
			success: function (data, textStatus) {
				layer.close(ii);
				if (data.code == 0) {
					window.location.href=data.url;
				}else if (data.code == 1) {
					layer.alert(data.msg, {icon: 1}, function(){
						window.location.reload();
					});
				}else{
					layer.alert(data.msg, {icon: 2});
				}
			},
			error: function (data) {
				layer.msg('服务器错误', {icon: 2});
			}
		});
		return false;
	})
	$("#submit2").click(function(){
		var csrf_token=$("input[name='csrf_token']").val();
		var money=$("input[name='money2']").val();
		if(money==''){
			layer.alert("金额不能为空");
			return false;
		}
		var ii = layer.load();
		$.ajax({
			type: "POST",
			dataType: "json",
			data: {money:money, csrf_token:csrf_token},
			url: "ajax2.php?act=deposit_withdraw",
			success: function (data, textStatus) {
				layer.close(ii);
				if (data.code == 0) {
					layer.alert(data.msg, {icon: 1}, function(){
						window.location.reload();
					});
				}else{
					layer.alert(data.msg, {icon: 2});
				}
			},
			error: function (data) {
				layer.msg('服务器错误', {icon: 2});
			}
		});
		return false;
	})
});
</script>