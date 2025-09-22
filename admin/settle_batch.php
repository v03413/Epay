<?php
/**
 * 批量转账页面
**/
include("../includes/common.php");
$type = isset($_GET['type'])?intval($_GET['type']):exit('no type');
if($type == 1){
	$typename = '支付宝';
	$default_channel = $conf['transfer_alipay'];
	$app = 'alipay';
}elseif($type == 2){
	$typename = '微信';
	$default_channel = $conf['transfer_wxpay'];
	$app = 'wxpay';
}elseif($type == 3){
	$typename = 'QQ钱包';
	$default_channel = $conf['transfer_qqpay'];
	$app = 'qqpay';
}elseif($type == 4){
	$typename = '银行卡';
	$default_channel = $conf['transfer_bank'];
	$app = 'bank';
}else{
	sysmsg('参数错误');
}
$title=$typename.'批量转账';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
<?php
if(!isset($_SESSION['paypwd']) || $_SESSION['paypwd']!==$conf['admin_paypwd'])showmsg('支付密码错误，请返回重新进入该页面');

if(isset($_GET['batch'])){
	$batch=$_GET['batch'];
	$row=$DB->getRow("SELECT * from pre_batch where batch='$batch'");
	if(!$row)showmsg('批次号不存在');
	$list=$DB->getAll("SELECT * FROM pre_settle WHERE batch='{$batch}' and type={$type}");

	$channel_select = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%".$app."%')");

?>
<script>
var paytype = '<?php echo $type?>';
function SelectAll(chkAll) {
	var items = $('.uins');
	for (i = 0; i < items.length; i++) {
		if (items[i].id.indexOf("uins") != -1) {
			if (items[i].type == "checkbox") {
				items[i].checked = chkAll.checked;
			}
		}
	}
}
function Transfer(){
	var url="ajax_settle.php?act=transfer";
	$("input[name=uins]:checked:first").each(function(){
		var checkself=$(this);
		var id=checkself.val();
		var statusself=$('#id'+id);
		var channel = $("select[name='channel']").val();
		statusself.html("<img src='../assets/img/load.gif' height=22>");
		$.post(url, {type:paytype,channel:channel,id:id}, function(d) {
			if(d.code==0){
				transnum++;
				var num = $('#donenum').text();
				num=parseInt(num);
				num++;
				$('#donenum').text(num);
				if(d.ret==1){
					statusself.html('<font color="green">成功</font>');
				}else if(d.ret==2){
					statusself.html('<font color="green">已完成</font>');
				}else{
					statusself.html('<font color="red">失败</font>');
				}
				$('#res'+id).html('<font color="blue">'+d.result+'</font>');
				checkself.attr('checked',false);
				Transfer();
			}else if(d.code==-1){
				statusself.html('<font color="red">失败</font>');
				alert(d.msg);
			}else{
				statusself.html('<font color="red">失败</font>');
			}
		});
		return true;
	});
}
var transnum = 0;
$(document).ready(function(){
	var allmoney = 0;
	var items = $('.money');
	for (i = 0; i < items.length; i++) {
		allmoney+=parseFloat(items[i].innerHTML);
	}
	$('#allmoney').html('总金额:'+allmoney.toFixed(2));
	$('#startsend').click(function(){
		var self=$(this);
		if (self.attr("data-lock") === "true") return;
			else self.attr("data-lock", "true");
		self.html('正在转账中');
		Transfer();
		if(transnum<1) self.html('没有待转账的记录');
		else self.html('转账处理完成');
		self.attr("data-lock", "false");
	});
	$('.recheck').click(function(){
		var self=$(this),
			id=self.attr('uin');
		var channel = $("select[name='channel']").val();
		var url="ajax_settle.php?act=transfer";
		self.html("<img src='../assets/img/load.gif' height=22>");
		$.post(url, {type:paytype,channel:channel,id:id}, function(d) {
			if(d.code==0){
				if(d.ret==1){
					self.html('<font color="green">成功</font>');
				}else if(d.ret==2){
					self.html('<font color="green">已完成</font>');
				}else{
					self.html('<font color="red">失败</font>');
				}
				$('#res'+id).html('<font color="blue">'+d.result+'</font>');
				$('.uins[value='+id+']').attr('checked',false);
				self.removeClass('nocheck');
			}else if(d.code==-1){
				self.html('<font color="red">失败</font>');
				alert(d.msg);
			}else{
				self.html('<font color="red">失败</font>');
			}
		});
	});
});
</script>
    <div class="col-md-12 center-block" style="float: none;">
<div class="panel panel-default">
	<div class="panel-heading" style="text-align:center">
		<div class="panel-title"><p><h3 class="panel-title"><?php echo $typename?>批量转账</h3></p>
			<div class="input-group"><div class="input-group-addon">通道选择</div>
				<select name="channel" class="form-control">
					<?php foreach($channel_select as $channel){echo '<option value="'.$channel['id'].'" '.($channel['id']==$default_channel?'selected':'').'>'.$channel['name'].''.($channel['id']==$default_channel?'（默认）':'').'</option>';} ?>
				</select>
			</div>
			<div class="input-group" style="padding:8px 0;">
				<div class="input-group-addon btn">全选<input type="checkbox" onclick="SelectAll(this)" /></div>
				<div class="input-group-addon btn" id="startsend">点此开始转账</div>
				<div class="input-group-addon btn"><span id="allmoney">总金额</span></div>
			</div>
			<div id="result"></div>
		</div>
	</div>
</div>


<div class="panel panel-primary">
	<table class="table table-bordered table-condensed">
		<tbody>
			<tr>
			<td align="center"><span style="color:silver;"><b>ID</b></span></td>
			<td align="center"><span style="color:silver;"><b>商户ID</b></span></td>
			<td align="center"><span style="color:silver;"><b>结算账号</b></span></td>
			<td align="center"><span style="color:silver;"><b>结算姓名</b></span></td>
			<td align="center"><span style="color:silver;"><b>金额</b></span></td>
			<td align="center"><span style="color:silver;"><b>操作</b></span></td>
			</tr>
			<?php
			echo '<tr><td colspan="6" align="center">总共<span id="allnum">'.count($list).'<span>个记录,已经处理<span id="donenum">0</span>个记录！</td></tr>';
			foreach($list as $row) {
			echo '<tr><td uin="'.$row['id'].'"><input name="uins" type="checkbox" id="uins" class="uins" value="'.$row['id'].'" '.($row['transfer_status']!=1?'checked':null).'>'.$row['id'].'</td><td>'.$row['uid'].'</td><td>'.$row['account'].'</td><td>'.$row['username'].'</td><td class="money">'.$row['realmoney'].'</td><td id="id'.$row['id'].'" uin="'.$row['id'].'" class="nocheck recheck" align="center">'.($row['transfer_status']!=1?'<span class="btn btn-xs btn-block btn-primary">立即转账</span>':'<font color="green">已完成</font>').'</td></tr><tr><td><span style="color:silver;">结果</span></td><td colspan="5" id="res'.$row['id'].'"><font color="blue">'.($row['transfer_status']==1?'转账订单号:'.$row['transfer_result'].' 支付时间:'.$row['transfer_date']:$row['transfer_result']).'</font></td></tr>';
			}
			?>
		</tbody>
	</table>
</div>
</div>
    </div>
<?php }?>
  </div>