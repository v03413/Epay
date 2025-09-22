<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='结算记录';
include './head.php';
?>
<style>
.fixed-table-toolbar,.fixed-table-pagination{padding: 15px;}
</style>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">结算记录</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			结算记录
		</div>
		<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
			<div class="form-group">
				<select name="dstatus" class="form-control"><option value="-1">全部状态</option><option value="0">状态待结算</option><option value="1">状态已完成</option><option value="2">状态正在结算</option><option value="3">状态结算失败</option></select>
			</div>
			<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> 搜索</button>
			<a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i> 重置</a>
		</form>
      <table id="listTable">
	  </table>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
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
		url: 'ajax2.php?act=settleList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover',
		columns: [
			{
				field: 'type',
				title: '结算方式',
				formatter: function(value, row, index) {
					let typename = '';
					if(value == '1'){
						typename='支付宝';
					}else if(value == '2'){
						typename='微信';
					}else if(value == '3'){
						typename='QQ钱包';
					}else if(value == '4'){
						typename='银行卡';
					}else if(value == '5'){
						typename='支付机构';
					}
					if(row.auto!=1) typename+='<small>[手动]</small>'
					return typename;
				}
			},
			{
				field: 'account',
				title: '结算账号'
			},
			{
				field: 'money',
				title: '结算金额',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b>';
				}
			},
			{
				field: 'realmoney',
				title: '实际到账',
				formatter: function(value, row, index) {
					return '¥<b>'+value+'</b>';
				}
			},
			{
				field: 'addtime',
				title: '结算时间'
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
						return '<a href="javascript:showResult('+row.id+')" title="点此查看失败原因"><font color=red>结算失败</font></a>';
					}else{
						return '<font color=blue>待结算</font>';
					}
				}
			},
		],
	})
})
function showResult(id) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=settle_result&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:0, title:'失败原因'});
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