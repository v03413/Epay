<?php
include("../includes/common.php");
$title='导出付款记录';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

?>
<link href="../assets/css/datepicker.css" rel="stylesheet">
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">导出付款记录</h3></div>
<div class="panel-body">
		<form action="" method="POST" onsubmit="return exportUser()" role="form">
			<div class="form-group">
				<div class="input-group input-daterange"><div class="input-group-addon">提交时间</div>
				<input type="text" id="starttime" name="starttime" class="form-control" placeholder="开始日期" autocomplete="off">
				<span class="input-group-addon"><i class="fa fa-chevron-right"></i></span>
				<input type="text" id="endtime" name="endtime" class="form-control" placeholder="结束日期" autocomplete="off">
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">商户号</div>
				<input type="text" name="uid" value="" class="form-control" placeholder="留空为全部商户"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">付款方式</div>
				<select name="type" class="form-control"><option value="">所有付款方式</option><option value="alipay">支付宝</option><option value="wxpay">微信</option><option value="qqpay">QQ钱包</option><option value="bank">银行卡</option></select>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">状态</div>
				<select name="dstatus" class="form-control"><option value="3">待处理</option><option value="0">正在处理</option><option value="1">转账成功</option><option value="2">转账失败</option><option value="">全部状态</option></select>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">导出表格模板</div>
				<select name="sheet" class="form-control"><option value="common">通用表格</option><option value="alipay">支付宝批量付款表格</option><option value="mybank">网商银行批量付款表格</option><option value="wxpay">微信批量转账到零钱表格</option></select>
			</div></div>
            <p><input type="submit" name="submit" value="导出" class="btn btn-primary form-control"/></p>
        </form>
</div>
</div>
 </div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script>
function exportUser(){
	var starttime = $("input[name='starttime']").val();
	var endtime = $("input[name='endtime']").val();
	var uid = $("input[name='uid']").val();
	var type = $("select[name='type']").val();
	var dstatus = $("select[name='dstatus']").val();
	var sheet = $("select[name='sheet']").val();
	window.location.href='./download.php?act=transfer&starttime='+starttime+'&endtime='+endtime+'&uid='+uid+'&type='+type+'&dstatus='+dstatus+'&sheet='+sheet;
	return false;
}
$(document).ready(function(){
	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
})
</script>