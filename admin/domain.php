<?php
/**
 * 授权支付域名
**/
include("../includes/common.php");
$title='授权支付域名';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="form-group">
	<label>搜索</label>
    <input type="text" class="form-control" name="kw" placeholder="要搜索的域名">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="uid" style="width: 100px;" placeholder="商户号" value="">
  </div>
  <div class="form-group">
	<select name="dstatus" class="form-control"><option value="-1">全部状态</option><option value="0">待审核</option><option value="1">正常</option><option value="2">拒绝</option></select>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:addDomain()" class="btn btn-success">添加</a>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新域名列表"><i class="fa fa-refresh"></i></a>
  <div class="btn-group" role="group">
	<button type="button" id="batchAction" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">批量操作 <span class="caret"></span></button>
	<ul class="dropdown-menu">
		<li><a href="javascript:operation(1)">通过</a></li>
		<li><a href="javascript:operation(2)">拒绝</a></li>
		<li><a href="javascript:operation(3)">删除</a></li>
	</ul>
  </div>
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
		url: 'ajax_user.php?act=domainList',
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
				field: 'uid',
				title: '商户号',
				formatter: function(value, row, index) {
					return '<b><a href="./ulist.php?column=uid&value='+value+'" target="_blank">'+value+'</a></b>';
				}
			},
			{
				field: 'domain',
				title: '域名',
				formatter: function(value, row, index) {
					return '<a href="http://'+value.replace('*.','www.')+'/" target="_blank" rel="noopener noreferrer">'+value+'</a>';
				}
			},
			{
				field: 'status',
				title: '状态',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<font color="green">正常</font>';
					}else if(value == '2'){
						return '<font color="red">拒绝</font>';
					}else{
						return '<font color="blue">审核中</font>';
					}
				}
			},
			{
				field: 'addtime',
				title: '添加时间'
			},
			{
				field: 'endtime',
				title: '审核时间'
			},
			{
				field: '',
				title: '操作',
				formatter: function(value, row, index) {
					let html = '';
					if(row.status == '1'){
						html += '<a href="javascript:setStatus('+row.id+', 2)" class="btn btn-default btn-xs">改为拒绝</a>';
					}else if(row.status == '2'){
						html += '<a href="javascript:setStatus('+row.id+', 1)" class="btn btn-default btn-xs">改为通过</a>';
					}else{
						html += '<a href="javascript:setStatus('+row.id+', 1)" class="btn btn-success btn-xs">通过</a> <a href="javascript:setStatus('+row.id+', 2)" class="btn btn-warning btn-xs">拒绝</a>';
					}
					html += ' <a href="javascript:delDomain('+row.id+')" class="btn btn-danger btn-xs">删除</a>';
					return html;
				}
			},
		],
	})
})
function addDomain(){
	var adduid = $("input[name='uid']").val();
	layer.open({
		type: 1,
		area: ['350px'],
		closeBtn: 2,
		title: '添加授权支付域名',
		content: '<div style="padding:15px"><div class="form-group"><input class="form-control" type="text" name="adduid" value="'+adduid+'" autocomplete="off" placeholder="商户ID"></div><div class="form-group"><input class="form-control" type="text" name="content" value="" autocomplete="off" placeholder="请输入域名，支持通配符*"></div></div>',
		btn: ['确认', '取消'],
		yes: function(){
			var adduid = $("input[name='adduid']").val();
			var content = $("input[name='content']").val();
			if(adduid == ''){
				$("input[name='adduid']").focus();return;
			}
			if(content == ''){
				$("input[name='content']").focus();return;
			}
			var ii = layer.load(2, {shade:[0.1,'#fff']});
			$.ajax({
				type : 'POST',
				url : 'ajax_user.php?act=addDomain',
				data : {uid:adduid, domain: content},
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
function delDomain(id) {
	if(confirm('确定要删除此域名吗？')){
		$.ajax({
			type : 'POST',
			url : 'ajax_user.php?act=delDomain',
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
function operation(status){
	var selected = $('#listTable').bootstrapTable('getSelections');
	if(selected.length == 0){
		layer.msg('未选择域名', {time:1500});return;
	}
	var checkbox = new Array();
	$.each(selected, function(key, item){
		checkbox.push(item.id)
	})
	if(status == 3 && !confirm('确定要删除已选中的'+checkbox.length+'个域名吗？')) return;
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=domain_operation',
		data : {status:status, checkbox:checkbox},
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
</script>