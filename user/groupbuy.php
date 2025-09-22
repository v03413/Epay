<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='购买会员';
include './head.php';
?>
<style>
.table>tbody>tr>td{vertical-align: middle;}
</style>
<?php

if($conf['group_buy']==0)exit('未开启购买会员');

$paytype = [];
$paytypes = [];
$rs = $DB->getAll("SELECT * FROM pre_type ORDER BY id ASC");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
	$paytypes[$row['id']] = $row['name'];
}
unset($rs);

function display_info($info){
	global $paytype,$paytypes,$DB;
	$result = '';
	$arr = json_decode($info, true);
	foreach($arr as $k=>$v){
		if($v['channel']==0)continue;
		if(empty($v['rate'])){
			$v['rate'] = 100;
			if($v['type'] == 'channel'){
				$v['rate'] = $DB->getColumn("SELECT rate FROM pre_channel WHERE id=:id", [':id'=>$v['channel']]);
			}
		}
		$result .= '<label><img src="/assets/icon/'.$paytypes[$k].'.ico" width="18px" title="'.$v['channel'].'">&nbsp;'.$paytype[$k].'('.round(100-$v['rate'],2).'%)</label>&nbsp;&nbsp;';
	}
	return substr($result,0,-1);
}

$urow = $DB->getRow("SELECT uid,gid FROM pre_user WHERE uid='{$conf['reg_pay_uid']}' limit 1");
if(!$urow)exit('购买会员收款商户不存在');
$paytypem = \lib\Channel::getTypes($urow['uid'], $urow['gid']);

$list = $DB->getAll("SELECT * FROM pre_group WHERE isbuy=1 ORDER BY SORT ASC");
$group=[];
foreach($list as $row){
	$group[$row['gid']] = $row['name'];
}
$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['csrf_token'] = $csrf_token;

$mygroup = $DB->getRow("SELECT * FROM pre_group WHERE gid='{$userrow['gid']}'");
$mygroupname = $mygroup['name'] ? $mygroup['name'] : '默认用户组';
$gexpire = $userrow['endtime'] ? date("Y-m-d", strtotime($userrow['endtime'])) : '永久';
if($userrow['endtime'] && $mygroup['isbuy']==1) $gexpire.=' [<a href="javascript:buy('.$userrow['gid'].',1)">续期</a>]';
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">购买会员</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="row" id="listFrame">
	<div class="col-xs-12">

	<?php if(isset($_GET['ok']) && $_GET['ok']==1){
	$order_param = $DB->getColumn("SELECT `param` FROM pre_order WHERE trade_no=:trade_no limit 1", [':trade_no'=>$_GET['trade_no']]);
	if($order_param){
		$order_param = json_decode($order_param, true);
		$groupname = $DB->getColumn("SELECT name FROM pre_group WHERE gid=:gid", [':gid'=>$order_param['gid']]);
	?>
	<div class="alert alert-success alert-dismissible" role="alert">
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	  会员等级 <b><?php echo $groupname?></b> 购买成功！
	</div>
	<?php }}?>

	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-shopping-cart"></i>&nbsp;购买会员
		</div>
		<div class="panel-body">
		<div class="list-group-item">
		  <b>当前会员等级：<font color="#f35a1f"><?php echo $mygroupname?></b></font>
		</div>
		<div class="list-group-item">
		  <b>到期时间：</b><font color="green"><?php echo $gexpire?></font>
		</div>
		<div class="line line-dashed b-b line-lg pull-in"></div>
        <table class="table table-striped table-hover">
          <thead><tr><th>会员等级</th><th>可用支付通道及费率</th><th>售价</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res){
	if(!isNullOrEmpty($res['visible'])){
		$visible = explode(',',$res['visible']);
		if(!in_array($userrow['gid'], $visible))continue;
	}
	echo '<tr><td><b>'.$res['name'].'</b></td><td>'.display_info($res['info']).'</td><td><span style="font-size:20px;font-weight:700;color:#f40;">'.$res['price'].'</span> / '.($res['expire']==0?'永久':$res['expire'].'个月').'</td><td>'.($userrow['gid']==$res['gid']?'<a class="btn btn-sm btn-info" href="javascript:;" disabled>当前等级</a>':'<a class="btn btn-sm btn-info" href="javascript:buy('.$res['gid'].')">立即购买</a>').'</td></tr>';
}
?>
		  </tbody>
        </table>
		</div>
	</div>
	</div>
	</div>
	<div class="row" id="infoFrame" style="display:none;">
	<div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
	<button class="btn btn-default btn-block" onclick="back()"><i class="fa fa-reply"></i>&nbsp;返回列表</button>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-shopping-cart"></i>&nbsp;<span id="buy_title">购买会员</span>
		</div>
		<div class="panel-body">
        <form class="form-horizontal devform">
			<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
			<input type="hidden" name="group_id" value="">
			<input type="hidden" name="group_expire" value="">
			<input type="hidden" name="group_price" value="">
			<input id="num" name="num" type="hidden" value="1"/>
				<div class="form-group">
					<label class="col-sm-3 control-label">会员等级</label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="group_name" value="" readonly="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">有效期</label>
					<div class="col-sm-8">
						<div class="input-group" id="num_form1" style="display:none;">
							<span class="input-group-btn"><input id="num_min" type="button" class="btn btn-info" style="border-radius: 0px;" value="━"></span>
							<input class="form-control" type="text" name="group_expire_show" value="" readonly="">
							<span class="input-group-btn"><input id="num_add" type="button" class="btn btn-info" style="border-radius: 0px;" value="✚"></span>
						</div>
						<input class="form-control" id="num_form2" type="text" value="永久" readonly="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">售价</label>
					<div class="col-sm-8">
						<input class="form-control" type="text" name="group_price_show" value="" readonly="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-3 control-label">支付方式</label>
					<div class="col-sm-8">
						<div class="radio">
						<label class="i-checks"><input type="radio" name="type" value="0"><i></i>余额支付</label>&nbsp;
						<?php foreach($paytypem as $row){?>
						  <label class="i-checks"><input type="radio" name="type" value="<?php echo $row['id']?>" rate="<?php echo $row['rate']?>"><i></i><?php echo $row['showname']?>
						  </label>&nbsp;
						<?php }?>
						</div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-3 col-sm-8"><input type="button" id="submit" value="立即购买" class="btn btn-success form-control"/><br/>
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
function buy(gid, type){
	var ii = layer.load();
	$.ajax({
		type: "POST",
		dataType: "json",
		data: {gid:gid},
		url: "ajax2.php?act=groupinfo",
		success: function (data, textStatus) {
			layer.close(ii);
			if (data.code == 0) {
				$("#buy_title").text(type==1?'续期会员':'购买会员');
				$("input[name='group_id']").val(gid);
				$("input[name='group_name']").val(data.name);
				$("input[name='group_expire']").val(data.expire);
				$("input[name='group_expire_show']").val(data.expire==0?'永久':data.expire+'个月');
				$("input[name='group_price']").val(data.price);
				$("input[name='group_price_show']").val(data.price+'元');
				$("input[name='num']").val(1);
				if(data.expire==0){
					$("#num_form1").hide();
					$("#num_form2").show();
				}else{
					$("#num_form1").show();
					$("#num_form2").hide();
				}
				$("#listFrame").slideUp();
				$("#infoFrame").slideDown();
			}else{
				layer.alert(data.msg, {icon: 0});
			}
		},
		error: function (data) {
			layer.msg('服务器错误', {icon: 2});
		}
	});
}
function back(){
	$("#listFrame").slideDown();
	$("#infoFrame").slideUp();
}
$(document).ready(function(){
	$("input[name=type]:first").attr("checked",true);
	$("#submit").click(function(){
		var csrf_token=$("input[name='csrf_token']").val();
		var gid=$("input[name='group_id']").val();
		var typeid=$("input[name=type]:checked").val();
		var num=$("input[name='num']").val();
		var ii = layer.load();
		$.ajax({
			type: "POST",
			dataType: "json",
			data: {gid:gid, num:num, typeid:typeid, csrf_token:csrf_token},
			url: "ajax2.php?act=groupbuy",
			success: function (data, textStatus) {
				layer.close(ii);
				if (data.code == 0) {
					window.location.href=data.url;
				}else if (data.code == 1) {
					layer.alert(data.msg, {icon: 1}, function(){ window.location.reload() });
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
$("#num_add").click(function () {
	var i = parseInt($("#num").val());
	i++;
	$("#num").val(i);
	var price = parseFloat($("input[name='group_price']").val());
	var count = parseInt($("input[name='group_expire']").val());
	if(count==0){
		layer.alert('不支持选择数量');
		return false;
	}
	price = price * i;
	count = count * i;
	$("input[name='group_expire_show']").val(count+'个月');
	$("input[name='group_price_show']").val(price.toFixed(2)+'元');
});
$("#num_min").click(function (){
	var i = parseInt($("#num").val());
	if(i<=1){
      	return false;
    }
	var price = parseFloat($("input[name='group_price']").val());
	var count = parseInt($("input[name='group_expire']").val());
	if(count==0){
		layer.alert('不支持选择数量');
		return false;
	}
	i--;
	if (i <= 0) i = 1;
	$("#num").val(i);
	price = price * i;
	count = count * i;
	$("input[name='group_expire_show']").val(count+'个月');
	$("input[name='group_price_show']").val(price.toFixed(2)+'元');
});
});
</script>