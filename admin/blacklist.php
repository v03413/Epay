<?php
/**
 * 支付黑名单管理
**/
include("../includes/common.php");
$title='支付黑名单管理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="form-group">
	<label>搜索</label>
    <input type="text" class="form-control" name="kw" placeholder="黑名单内容">
  </div>
  <div class="form-group">
	<select name="type" class="form-control"><option value="-1">黑名单类型</option><option value="0">支付账号</option><option value="1">IP地址</option></select>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:addItem()" class="btn btn-success">添加</a>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新黑名单列表"><i class="fa fa-refresh"></i></a>
  <div class="btn-group" role="group">
	<button type="button" id="batchAction" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">批量操作 <span class="caret"></span></button>
	<ul class="dropdown-menu">
		<li><a href="javascript:batchdel()">批量删除</a></li>
	</ul>
  </div>
  <a tabindex="0" class="btn btn-default" role="button" data-toggle="popover" data-trigger="focus" title="说明" data-placement="bottom" data-content="支付账号黑名单，只支持微信公众号支付和支付宝JS支付"><span class="glyphicon glyphicon-question-sign"></span></a>
</form>

      <table id="listTable">
	  </table>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
$(document).ready(function(){
	updateToolbar();
	const defaultPageSize = 15;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax_user.php?act=blackList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: '',
				checkbox: true
			},
			{
				field: 'id',
				title: 'ID'
			},
			{
				field: 'type',
				title: '黑名单类型',
				formatter: function(value, row, index) {
					if(value == '1'){
						return 'IP地址';
					}else{
						return '支付账号';
					}
				}
			},
			{
				field: 'content',
				title: '黑名单内容'
			},
			{
				field: 'addtime',
				title: '添加时间'
			},
			{
				field: 'endtime',
				title: '过期时间',
				formatter: function(value, row, index) {
					if(value == null) return '永久';
					return value;
				}
			},
			{
				field: 'remark',
				title: '备注'
			},
			{
				field: '',
				title: '操作',
				formatter: function(value, row, index) {
					let html = '';
					html += ' <a href="javascript:delItem('+row.id+')" class="btn btn-danger btn-xs">删除</a>';
					return html;
				}
			},
		],
	})
})
function addItem(){
	layer.open({
		type: 1,
		area: ['380px'],
		closeBtn: 2,
		title: '添加黑名单',
		content: '<div style="padding:15px"><div class="form-group"><div class="input-group"><div class="input-group-addon">拉黑类型</div><select name="add_type" class="form-control"><option value="0">支付账号</option><option value="1">IP地址</option></select></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">拉黑内容</div><input class="form-control" type="text" name="add_content" value="" autocomplete="off" ></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">有效期</div><input class="form-control" type="text" name="add_days" value="0" autocomplete="off" placeholder="0为永久"><div class="input-group-addon">天</div></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">备注</div><input class="form-control" type="text" name="add_remark" value="" autocomplete="off" placeholder="选填"></div></div></div>',
		btn: ['确认', '取消'],
		yes: function(){
			var type = $("select[name='add_type']").val();
			var content = $("input[name='add_content']").val();
			var days = $("input[name='add_days']").val();
			var remark = $("input[name='add_remark']").val();
			if(content == ''){
				$("input[name='content']").focus();return;
			}
			var ii = layer.load(2, {shade:[0.1,'#fff']});
			$.ajax({
				type : 'POST',
				url : 'ajax_user.php?act=addBlack',
				data : {type:type, content:content, days:days, remark:remark},
				dataType : 'json',
				success : function(data) {
					layer.close(ii);
					if(data.code == 0){
						layer.alert(data.msg, {icon:1}, function(){ layer.closeAll(); searchSubmit() });
					}else{
						layer.alert(data.msg, {icon:0});
					}
				},
				error:function(data){
					layer.close(ii);
					layer.msg('服务器错误');
				}
			});
		}
	});
}
function setStatus(id, status){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'post',
		url : 'ajax_user.php?act=setDomainStatus',
		data : {id:id, status:status},
		dataType : 'json',
		success : function(ret) {
			layer.close(ii);
			if (ret.code != 0) {
				alert(ret.msg);
			}
			searchSubmit();
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function delItem(id) {
	if(confirm('确定要删除此黑名单吗？')){
		$.ajax({
			type : 'POST',
			url : 'ajax_user.php?act=delBlack',
			data : {id: id},
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					layer.msg('删除成功', {icon:1, time: 1000});
					searchSubmit();
				}else{
					layer.alert(data.msg, {icon:2});
				}
			}
		});
	}
}
function batchdel(){
	var selected = $('#listTable').bootstrapTable('getSelections');
	if(selected.length == 0){
		layer.msg('未选择黑名单记录', {time:1500});return;
	}
	var checkbox = new Array();
	$.each(selected, function(key, item){
		checkbox.push(item.id)
	})
	if(!confirm('确定要删除已选中的'+checkbox.length+'个黑名单吗？')) return;
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=batchdelBlack',
		data : {checkbox:checkbox},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				searchSubmit();
				layer.alert(data.msg);
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('请求超时');
			searchSubmit();
		}
	});
	return false;
}
$(function () {
  $('[data-toggle="popover"]').popover()
})
</script>