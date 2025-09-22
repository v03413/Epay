<?php
/**
 * 结算列表
**/
include("../includes/common.php");
$title='结算列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
	<div class="row">
    <div class="col-md-12 center-block" style="float: none;">

<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
<input type="hidden" class="form-control" name="batch">
  <div class="form-group">
	<label>搜索</label>
    <input type="text" class="form-control" name="value" placeholder="结算账号/姓名">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="uid" style="width: 100px;" placeholder="商户号" value="">
  </div>
  <div class="form-group">
	<select name="type" class="form-control"><option value="0">所有结算方式</option><option value="1">支付宝</option><option value="2">微信</option><option value="3">QQ钱包</option><option value="4">银行卡</option></select>
  </div>
  <div class="form-group">
	<select name="dstatus" class="form-control"><option value="-1">全部状态</option><option value="0">待结算</option><option value="1">已完成</option><option value="2">正在结算</option><option value="3">结算失败</option></select>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="settle.php" class="btn btn-success">批量结算</a>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新记录列表"><i class="fa fa-refresh"></i></a>
  <div class="btn-group" role="group">
	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">批量修改 <span class="caret"></span></button>
	<ul class="dropdown-menu"><li><a href="javascript:operation(0)">待结算</a></li><li><a href="javascript:operation(1)">已完成</a></li><li><a href="javascript:operation(2)">正在结算</a></li><li><a href="javascript:operation(3)">结算失败</a></li><li><a href="javascript:operation(4)">删除记录</a></li></ul>
  </div>
</form>

	  <table id="listTable">
	  </table>
    </div>
  </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
$(document).ready(function(){
	updateToolbar();
	const defaultPageSize = 30;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax_settle.php?act=settleList',
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
				title: 'ID',
				formatter: function(value, row, index) {
					return '<b>'+value+'</b>';
				}
			},
			{
				field: 'uid',
				title: '商户号',
				formatter: function(value, row, index) {
					return '<a href="./ulist.php?column=uid&value='+value+'" target="_blank">'+value+'</a>';
				}
			},
			{
				field: 'type',
				title: '结算方式',
				formatter: function(value, row, index) {
					let typename = '';
					if(value == '1'){
						typename='<img src="/assets/icon/alipay.ico" width="16" onerror="this.style.display=\'none\'">支付宝';
					}else if(value == '2'){
						typename='<img src="/assets/icon/wxpay.ico" width="16" onerror="this.style.display=\'none\'">微信';
					}else if(value == '3'){
						typename='<img src="/assets/icon/qqpay.ico" width="16" onerror="this.style.display=\'none\'">QQ钱包';
					}else if(value == '4'){
						typename='<img src="/assets/icon/bank.ico" width="16" onerror="this.style.display=\'none\'">银行卡';
					}else if(value == '5'){
						typename='支付机构';
					}
					if(row.auto!=1) typename+='<small>[手动]</small>'
					return typename;
				}
			},
			{
				field: 'account',
				title: '结算账号/姓名',
				formatter: function(value, row, index) {
					if(row.type == '5'){
						return value+'&nbsp;'+row.username;
					}else{
						return '<span onclick="inputInfo('+row.id+')" title="点击直接修改">'+value+'&nbsp;'+row.username+'</span>';
					}
				}
			},
			{
				field: 'money',
				title: '结算金额/实际到账',
				formatter: function(value, row, index) {
					return '<b>'+value+'</b>&nbsp;/&nbsp;<b>'+row.realmoney+'</b>';
				}
			},
			{
				field: 'addtime',
				title: '创建时间'
			},
			{
				field: 'endtime',
				title: '完成时间'
			},
			{
				field: 'status',
				title: '状态',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<font color=green>已完成</font>' + (row.jumpurl ? '<br/><a href="javascript:showQrcode(\''+row.jumpurl+'\')" class="btn btn-xs btn-success"><i class="fa fa-qrcode"></i> 确认收款</a>' : '');
					}else if(value == '2'){
						return '<font color=orange>正在结算</font>';
					}else if(value == '3'){
						return '<a href="javascript:setResult('+row.id+')" title="点此填写失败原因"><font color=red>结算失败</font></a>';
					}else{
						return '<font color=blue>待结算</font>';
					}
				}
			},
			{
				field: '',
				title: '操作',
				formatter: function(value, row, index) {
					return '<select onChange="javascript:setStatus(\''+row.id+'\',this.value)" class=""><option selected>变更状态</option><option value="0">待结算</option><option value="1">已完成</option><option value="2">正在结算</option><option value="3">结算失败</option><option value="4">删除记录</option></select>';
				}
			},
		],
	})
})

function operation(status){
	var selected = $('#listTable').bootstrapTable('getSelections');
	if(selected.length == 0){
		layer.msg('未选择记录', {time:1500});return;
	}
	var checkbox = new Array();
	$.each(selected, function(key, item){
		checkbox.push(item.id)
	})
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_settle.php?act=opslist',
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
function setStatusDo(id, status) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'get',
		url : 'ajax_settle.php',
		data : 'act=setSettleStatus&id=' + id + '&status=' + status,
		dataType : 'json',
		success : function(ret) {
			layer.close(ii);
			if (ret['code'] != 200) {
				alert(ret['msg'] ? ret['msg'] : '操作失败');
			}
			layer.closeAll();
			searchSubmit();
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function setStatus(id, status) {
	if(status==4){
		var confirmobj = layer.confirm('你确实要删除此记录并退回商户余额吗？', {
			btn: ['确定','取消'], icon:0
		}, function(){
			setStatusDo(id, status);
		});
	}else{
		setStatusDo(id, status);
	}
}
function setResult(id) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_settle.php?act=settle_result',
		data : {id:id},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.prompt({title: '填写失败原因', value: data.result, formType: 2}, function(text, index){
					var ii = layer.load(2, {shade:[0.1,'#fff']});
				$.ajax({
					type : 'POST',
					url : 'ajax_settle.php?act=settle_setresult',
					data : {id:id,result:text},
					dataType : 'json',
					success : function(data) {
						layer.close(ii);
						if(data.code == 0){
							layer.msg('填写失败原因成功');
						}else{
							layer.alert(data.msg);
						}
					},
					error:function(data){
						layer.msg('服务器错误');
						return false;
					}
				});
			});
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function inputInfo(id) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_settle.php?act=settle_info&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.open({
				  type: 1,
				  title: '修改数据',
				  skin: 'layui-layer-rim',
				  content: data.data,
				  success: function(){
					  $("#pay_type").val(data.pay_type);
				  }
				});
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function saveInfo(id) {
	var pay_type=$("#pay_type").val();
	var pay_account=$("#pay_account").val();
	var pay_name=$("#pay_name").val();
	if(pay_account=='' || pay_name==''){layer.alert('请确保每项不能为空！');return false;}
	$('#save').val('Loading');
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : "POST",
		url : "ajax_settle.php?act=settle_save",
		data : {id:id,pay_type:pay_type,pay_account:pay_account,pay_name:pay_name},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.closeAll();
				layer.msg('保存成功！', {time:800});
				searchSubmit();
			}else{
				layer.alert(data.msg);
			}
			$('#save').val('保存');
		} 
	});
}
function showQrcode(url){
	layer.open({
		type: 1,
		title: '收款方使用微信扫描以下二维码',
		skin: 'layui-layer-demo',
		shadeClose: true,
		content: '<div id="qrcode" class="list-group-item text-center"></div>',
		success: function(){
			$('#qrcode').qrcode({
				text: url,
				width: 230,
				height: 230,
				foreground: "#000000",
				background: "#ffffff",
				typeNumber: -1
			});
		}
	});
}
</script>