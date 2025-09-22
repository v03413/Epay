<?php
/**
 * 公众号小程序
**/
include("../includes/common.php");
$title='公众号小程序';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-8 center-block" style="float: none;">
<?php
function display_type($type){
	if($type==1)
		return '微信小程序';
	else
		return '微信服务号';
}

$list = $DB->getAll("SELECT * FROM pre_weixin ORDER BY id ASC");
?>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">公众号小程序修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="id" id="id"/>
					<div class="form-group">
						<label class="col-sm-2 control-label">类别</label>
						<div class="col-sm-10">
							<select name="type" id="type" class="form-control" onchange="shownote()">
								<option value="0">微信服务号</option>
								<option value="1">微信小程序</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">名称</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="name" id="name" placeholder="仅用于显示，不要重复">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">APPID</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="appid" id="appid" placeholder="">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">APPSECRET</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="appsecret" id="appsecret" placeholder="">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right text-muted">说明</label>
						<div class="col-sm-10">
							<span class="text-muted" id="type0" style="display:none">需要在【微信公众平台->公众号设置->功能设置】设置<font color="red">网页授权域名</font>，如果用于支付，还需要在微信支付【商户平台->产品中心->开发配置】设置<font color="red">JSAPI支付授权目录</font></span>
							<span class="text-muted" id="type1" style="display:none">需要在【小程序后台->开发->开发设置->服务器域名】设置<font color="red">request合法域名</font>，如果用于支付，还需要在微信支付后台绑定此小程序</span>
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
   <div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo count($list);?></b> 个公众号/小程序&nbsp;<span class="pull-right"><a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>类别</th><th>名称</th><th>APPID</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
echo '<tr><td><b>'.$res['id'].'</b></td><td>'.display_type($res['type']).'</td><td>'.$res['name'].'</td><td>'.$res['appid'].'</td><td><a class="btn btn-xs btn-info" onclick="editframe('.$res['id'].')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['id'].')">删除</a>&nbsp;<a onclick="testweixin('.$res['id'].')" class="btn btn-xs btn-default">测试</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function shownote(){
	var type = $("#type").val();
	if(type == 1){
		$("#type0").hide();
		$("#type1").show();
	}else{
		$("#type0").show();
		$("#type1").hide();
	}
}
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增公众号/小程序");
	$("#action").val("add");
	$("#id").val('');
	$("#type").val(0);
	$("#name").val('');
	$("#appid").val('');
	$("#appsecret").val('');
	shownote()
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_pay.php?act=getWeixin&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改公众号/小程序");
				$("#action").val("edit");
				$("#id").val(data.data.id);
				$("#type").val(data.data.type);
				$("#name").val(data.data.name);
				$("#appid").val(data.data.appid);
				$("#appsecret").val(data.data.appsecret);
				shownote()
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
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
		url : 'ajax_pay.php?act=saveWeixin',
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
			layer.msg('服务器错误');
			return false;
		}
	});
}
function delItem(id) {
	var confirmobj = layer.confirm('你确实要删除此公众号/小程序吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'GET',
		url : 'ajax_pay.php?act=delWeixin&id='+id,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
function testweixin(id) {
	$.ajax({
		type : 'POST',
		url : 'ajax_pay.php?act=testweixin',
		data : {id:id},
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				layer.alert(data.msg, {icon:1});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
</script>