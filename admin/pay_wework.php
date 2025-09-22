<?php
/**
 * 企业微信账号列表
**/
include("../includes/common.php");
$title='企业微信账号列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-8 center-block" style="float: none;">
<?php

$list = $DB->getAll("SELECT * FROM pre_wework ORDER BY id ASC");
?>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">企业微信修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="id" id="id"/>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">名称</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="name" id="name" placeholder="仅用于显示，不要重复">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">企业ID</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="appid" id="appid" placeholder="">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">Secret</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="appsecret" id="appsecret" placeholder="">
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
				<button type="button" class="btn btn-primary" id="store" onclick="save()">保存</button>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-info">
   <div class="panel-heading"><h3 class="panel-title">企业微信账号列表&nbsp;<span class="pull-right"><a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>名称</th><th>企业ID</th><th>客服账号数量</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
$wxkf_allnum = 0;
foreach($list as $res)
{
	$wxkf_num = $DB->getColumn("SELECT COUNT(*) FROM pre_wxkfaccount WHERE wid='{$res['id']}'");
	$wxkf_allnum += $wxkf_num;
echo '<tr><td><b>'.$res['id'].'</b></td><td>'.$res['name'].'</td><td>'.$res['appid'].'</td><td><span style="font-weight:700;color:#f40;">'.$wxkf_num.'</span> [<a href="javascript:refreshnum('.$res['id'].')">刷新</a>]</td><td>'.($res['status']==1?'<a class="btn btn-xs btn-success" onclick="setStatus('.$res['id'].',0)">已开启</a>':'<a class="btn btn-xs btn-warning" onclick="setStatus('.$res['id'].',1)">已关闭</a>').'</td><td><a class="btn btn-xs btn-info" onclick="editframe('.$res['id'].')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['id'].')">删除</a>&nbsp;<a onclick="testwework('.$res['id'].')" class="btn btn-xs btn-default">测试</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	  <div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 当前共有<span style="font-weight:700;color:#f40;"><?php echo count($list);?></span>个企业微信，总共<span style="font-weight:700;color:#f40;"><?php echo $wxkf_allnum ?></span>个客服账号<?php if($conf['wework_paymsgmode']==1)echo '，<font color="red">也即单个用户48小时内最多拉起'.$wxkf_allnum.'次微信客服支付</font>';?><br/>如需在企业下添加多个客服账号，需先在开发配置停用API，然后再添加客服账号。
        </div>
    </div>
	</div>
	
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增企业微信");
	$("#action").val("add");
	$("#id").val('');
	$("#name").val('');
	$("#appid").val('');
	$("#appsecret").val('');
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_pay.php?act=getWework&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改企业微信");
				$("#action").val("edit");
				$("#id").val(data.data.id);
				$("#name").val(data.data.name);
				$("#appid").val(data.data.appid);
				$("#appsecret").val(data.data.appsecret);
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function save(){
	if($("#name").val()==''||$("#appid").val()==''||$("#appsecret").val()==''){
		layer.alert('请确保各项不能为空！');return false;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_pay.php?act=saveWework',
		data : $("#form-store").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function delItem(id) {
	var confirmobj = layer.confirm('你确实要删除此企业微信吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
	  $.ajax({
		type : 'GET',
		url : 'ajax_pay.php?act=delWework&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
function setStatus(id,status) {
	$.ajax({
		type : 'GET',
		url : 'ajax_pay.php?act=setWework&id='+id+'&status='+status,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.msg(data.msg, {icon:2, time:1500});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function refreshnum(id) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_pay.php?act=refreshWework',
		data : {id:id},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1,closeBtn:false}, function(){window.location.reload()});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function testwework(id) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_pay.php?act=testWework',
		data : {id:id},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
</script>