<?php
/**
 * 订单列表
**/
include("../includes/common.php");
$title='订单列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$type_select = '<option value="">支付方式</option>';
$rs = $DB->getAll("SELECT * FROM pre_type ORDER BY id ASC");
foreach($rs as $row){
	$type_select .= '<option value="'.$row['id'].'">'.$row['showname'].'</option>';
}
unset($rs);
?>
<style>
#orderItem .orderTitle{word-break:keep-all;}
#orderItem .orderContent{word-break:break-all;}
.dates{max-width: 120px;}
@media screen and (max-width: 767px) {
.table-responsive {
    overflow-y: auto;
}
}
.type-logo{width: 18px;margin-top: -2px;padding-right: 4px;}
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
  <div class="container-fluid" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">

<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <input type="hidden" name="subchannel" value="">
  <input type="hidden" name="applyid" value="">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="trade_no">订单号</option><option value="out_trade_no">商户订单号</option><option value="api_trade_no">接口订单号</option><option value="bill_mch_trade_no">渠道交易单号</option><option value="bill_trade_no">用户交易单号</option><option value="name">商品名称</option><option value="money">订单金额</option><option value="realmoney">实付金额</option><option value="getmoney">分成金额</option><option value="domain">网站域名</option><option value="buyer">支付账号</option><option value="ip">支付IP</option><option value="mobile">手机号码</option><option value="param">扩展参数</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容" value="">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="uid" style="width: 100px;" placeholder="商户号" value="">
  </div>
  <div class="form-group">
    <select name="type" class="form-control"><?php echo $type_select?></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="channel" style="width: 80px;" placeholder="通道ID" value="">
  </div>
  <div class="input-group input-daterange">
	<input type="text" id="starttime" name="starttime" class="form-control dates" placeholder="开始日期" autocomplete="off" title="留空则不限时间范围">
	<span class="input-group-addon" onclick="$('#starttime').val('');$('#endtime').val('');" title="清除"><i class="fa fa-chevron-right"></i></span>
	<input type="text" id="endtime" name="endtime" class="form-control dates" placeholder="结束日期" autocomplete="off" title="留空则不限时间范围">
  </div>
  <div class="form-group">
	<select name="dstatus" class="form-control"><option value="">全部状态</option><optgroup label="订单状态"><option value="0">未支付</option><option value="1">已支付</option><option value="2">已退款</option><option value="3">已冻结</option><option value="4">预授权</option></optgroup><optgroup label="结算状态"><option value="settle_1">待结算</option><option value="settle_2">结算成功</option><option value="settle_3">结算失败</option></optgroup></select>
  </div>
  <button type="submit" class="btn btn-primary">&nbsp;搜索&nbsp;</button>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新订单列表"><i class="fa fa-refresh"></i></a>
  <div class="btn-group" role="group">
	<button type="button" id="batchAction" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">批量操作 <span class="caret"></span></button>
	<ul class="dropdown-menu">
		<li><a href="javascript:operation(0)" data-action-type="0">改未完成</a></li>
		<li><a href="javascript:operation(1)" data-action-type="1">改已完成</a></li>
		<li><a href="javascript:operation(5)" data-action-type="5">API退款</a></li>
		<li><a href="javascript:operation(2)" data-action-type="2">冻结订单</a></li>
		<li><a href="javascript:operation(3)" data-action-type="3">解冻订单</a></li>
		<li><a href="javascript:operation(4)" data-action-type="4">删除订单</a></li>
		<li><a href="javascript:operation(7)" data-action-type="7">重新通知</a></li>
		<li><a href="javascript:operation(6)" data-action-type="6">确认结算</a></li>
	</ul>
  </div>
  <button type="button" onclick="statistics()" class="btn btn-default">&nbsp;统计&nbsp;</button>
</form>
      <table id="listTable">
	  </table>
    </div>
  </div>
<div class="modal" id="modal-statistics" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">订单统计概况</h4>
			</div>
			<div class="modal-body">
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-primary" data-dismiss="modal">关闭</button>
			</div>
		</div>
	</div>
</div>
<template id="statistics">
<ul class="list-inline" style="margin-bottom: 0;padding-bottom: 10px;border-bottom: 1px solid #dddddd;">
    <li>订单总金额：<span style="font-weight: 600;">¥ {totalMoney}</span></li>
    <li>已支付金额：<span style="font-weight: 600;">¥ {successMoney}</span></li>
    <li>未支付金额：<span style="font-weight: 600;">¥ {unpaidMoney}</span></li>
    <li>已退款金额：<span style="font-weight: 600;">¥ {refundMoney}</span></li>
    <li>总收入利润: <span style="font-weight: 600;">¥ {platformProfit}</li>
</ul>
<ul class="list-inline" style="padding-top:10px;margin-bottom: 0;">
    <li>订单总数：<span style="font-weight: 600;">{totalCount}</span></li>
    <li>已支付订单：<span style="font-weight: 600;">{successCount}</span></li>
    <li>未支付订单：<span style="font-weight: 600;">{unpaidCount}</span></li>
    <li>已退款订单：<span style="font-weight: 600;">{refundCount}</span></li>
	<li>订单成功率：<span style="font-weight: 600;">{successRate}%</span></li>
</ul>
</template>
<a style="display: none;" href="" id="vurl" rel="noreferrer" target="_blank"></a>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
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
		url: 'ajax_order.php?act=orderList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		uniqueId: 'trade_no',
		columns: [
			{
				field: '',
				checkbox: true
			},
			{
				field: 'trade_no',
				title: '系统订单号<br/>商户订单号',
				formatter: function(value, row, index) {
					return '<a href="javascript:showOrder(\''+value+'\')" title="点击查看详情">'+value+'</a></b><br/>'+row.out_trade_no;
				}
			},
			{
				field: 'uid',
				title: '商户号<br/>网站域名',
				formatter: function(value, row, index) {
					return '<a href="./ulist.php?my=search&column=uid&value='+value+'" target="_blank">'+value+'</a><br/><a onclick="openlink(\'http://'+row.domain+'\')">'+row.domain+'</a>';
				}
			},
			{
				field: 'name',
				title: '商品名称<br/>订单金额',
				formatter: function(value, row, index) {
					return value+'<br/>¥<b>'+row.money+'</b>';
				}
			},
			{
				field: 'realmoney',
				title: '实际支付<br/>商户分成',
				formatter: function(value, row, index) {
					return value!=null?'¥<b>'+value+'</b><br/>¥<b>'+row.getmoney+'</b>':'';
				}
			},
			{
				field: 'type',
				title: '支付方式(通道ID)<br/>支付插件',
				formatter: function(value, row, index) {
					return row.typename ? '<img src="/assets/icon/'+row.typename+'.ico" class="type-logo" onerror="this.style.display=\'none\'">'+row.typeshowname+'(<a href="./pay_channel.php?kw='+row.channel+'" target="_blank" title="'+row.channelname+'">'+row.channel+'</a>)<br/>'+row.plugin : '';
				}
			},
			{
				field: 'ip',
				visible: false,
				cellStyle: function(value, row, index) {
					return {css:{"max-width":"150px","word-break":"break-all"}};
				},
				title: '支付IP<br/>支付账号',
				formatter: function(value, row, index) {
					return '<a href="https://m.ip138.com/iplookup.asp?ip='+value+'" target="_blank" rel="noreferrer">'+value+'</a>&nbsp;<a href="javascript:addBlackList(1,\''+row.ip+'\')" class="btn btn-xs btn-default"><i class="fa fa-stop-circle"></i></a><br/>'+(row.buyer?row.buyer+'&nbsp;<a href="javascript:addBlackList(0,\''+row.buyer+'\')" class="btn btn-xs btn-default"><i class="fa fa-stop-circle"></i></a>':'&nbsp;');
				}
			},
			{
				field: 'param',
				title: '扩展参数',
				visible: false,
			},
			{
				field: 'addtime',
				title: '创建时间<br/>完成时间',
				formatter: function(value, row, index) {
					return value+'<br/>'+(row.endtime?row.endtime:'&nbsp;');
				}
			},
			{
				field: 'status',
				title: '支付状态',
				formatter: function(value, row, index) {
					var text = '';
					if(value == '1'){
						text = '<font color=green>已支付</font>';
					}else if(value == '2'){
						text = '<font color=red>已退款</font>';
						if(row.refundmoney > 0 && row.refundmoney < row.realmoney){
							text += '<br/><font color=red>('+row.refundmoney+'元)</font>';
						}
					}else if(value == '3'){
						text = '<font color=red>已冻结</font>';
					}else if(value == '4'){
						text = '<font color=orange>预授权</font>';
					}else{
						text = '<font color=blue>未支付</font>';
					}
					if(row.plugin=='alipayd' || row.plugin=='wxpaynp'){
						if(row.settle == '1'){
							text += '<br/><font color=#8c8f93>待结算</font>';
						}else if(row.settle == '2'){
							text += '<br/><font color=#37db3c>结算成功</font>';
						}else if(row.settle == '3'){
							text += '<br/><font color=#ed6565>结算失败</font>';
						}
					}else if(row.plugin=='alipayrp'){
						if(row.settle == '1'){
							text += '<br/><font color=#8c8f93>待转账</font>';
						}else if(row.settle == '2'){
							text += '<br/><font color=#37db3c>转账成功</font>';
						}else if(row.settle == '3'){
							text += '<br/><font color=#ed6565>转账失败</font>';
						}
					}
					return text;
				}
			},
			{
				field: 'status',
				title: '操作',
				formatter: function(value, row, index) {
					let html = '<div class="btn-group dropdown-group" role="group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">操作订单 <span class="caret"></span></button><ul class="dropdown-menu">';
					if((row.plugin=='alipayd' || row.plugin=='wxpaynp') && (row.settle=='1'||row.settle=='3')){
						html += '<li><a href="javascript:alipaydSettle(\''+row.trade_no+'\')">确认结算</a></li>';
					}
					else if(row.plugin=='alipayrp' && (row.settle=='1'||row.settle=='3')){
						html += '<li><a href="javascript:alipayRedPacketTansfer(\''+row.trade_no+'\')">红包转账重试</a></li>';
					}
					if(value == '1'){
						html+= '<li><a href="javascript:setStatus(\''+row.trade_no+'\', 0)">改未完成</a></li><li><a href="javascript:apirefund(\''+row.trade_no+'\')">API退款</a></li><li><a href="javascript:refund(\''+row.trade_no+'\')">手动退款</a></li><li><a href="javascript:freeze(\''+row.trade_no+'\')">冻结订单</a></li><li role="separator" class="divider"></li><li><a href="javascript:callnotify(\''+row.trade_no+'\')">重新通知</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}else if(value == '2'){
						html+= '<li><a href="javascript:setStatus(\''+row.trade_no+'\', 0)">改未完成</a></li><li><a href="javascript:apirefund(\''+row.trade_no+'\')">API退款</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 1)">改已完成</a></li><li role="separator" class="divider"></li><li><a href="javascript:callnotify(\''+row.trade_no+'\')">重新通知</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}else if(value == '3'){
						html+= '<li><a href="javascript:unfreeze(\''+row.trade_no+'\')">解冻订单</a></li><li><a href="javascript:apirefund(\''+row.trade_no+'\')">API退款</a></li><li role="separator" class="divider"></li><li><a href="javascript:callnotify(\''+row.trade_no+'\')">重新通知</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}else{
						if(value == '4'){
							html += '<li><a href="javascript:alipayPreAuthPay(\''+row.trade_no+'\')">授权资金支付</a></li>';
							html += '<li><a href="javascript:alipayUnfreeze(\''+row.trade_no+'\')">授权资金解冻</a></li><li role="separator" class="divider"></li>';
						}
						html+= '<li><a href="javascript:setStatus(\''+row.trade_no+'\', 1)">改已完成</a></li><li role="separator" class="divider"></li><li><a href="javascript:fillorder(\''+row.trade_no+'\')">手动补单</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}
					html += '</ul></div>';
					return html;
				}
			},
		],
		onLoadSuccess: function(data) {
			$('.dropdown-group').on('show.bs.dropdown', function (e) {
				var btnPos = $(e.target)[0].getBoundingClientRect();
				var screenWidth = $(window).width();
				var screenHeight = $(window).height();
				var childrenWidth = $(e.target).children('.dropdown-menu').width();
				var childrenHeight = $(e.target).children('.dropdown-menu').height();
				var top = btnPos.bottom;
				if(top + childrenHeight + 12 > screenHeight){
					top = btnPos.top - childrenHeight - 12;
				}
				var left = btnPos.left;
				if(left + childrenWidth + 7 > screenWidth){
					left = screenWidth - childrenWidth - 7;
				}
				$(e.target).children('.dropdown-menu').css({position:'fixed', top:top, left:left});
			});
		}
	})
})

function openlink(full_link){ 
	window.open('javascript:window.name;', '<script>location.replace("'+full_link+'")<\/script>');
}

function statistics(){
    var ii = layer.load(2, {shade:[0.1,'#fff']});
    $.ajax({
        type : 'POST',
        url : 'ajax_order.php?act=statistics',
        data: $('#searchToolbar').serializeArray(),
        dataType : 'json',
        success : function(data) {
            layer.close(ii);
            if(data.code == 0){
                var element = $('#modal-statistics');
                var htmlContent = $("#statistics").html().replace(/\{(\w+)\}/g, function (match, key) {
                    return data.data[key] || '';
                });
                element.find('.modal-body').html(htmlContent);
                element.modal('show');
            }else{
                layer.alert(data.msg);
            }
        },
        error:function(data){
            layer.close(ii);
            layer.msg('服务器错误');
        }
    });
}

function operation(status){
	var selected = $('#listTable').bootstrapTable('getSelections');
	if(selected.length == 0){
		layer.msg('未选择订单', {time:1500});return;
	}
	var checkbox = getBatchOperateData(selected, 'trade_no')[status];
	if(checkbox.length == 0){
		layer.msg('要处理的订单数量为0', {time:1500});return;
	}
	if(status == 4 && !confirm('确定要删除已选中的'+checkbox.length+'个订单吗？')) return;
	if(status == 5){
		return batch_apirefund(checkbox);
	}
	if(status == 6){
		return batch_alipayd_settle(checkbox);
	}
	if(status == 7){
		return batch_callnotify(checkbox);
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=operation',
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
function showOrder(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	var status = ['<span class="label label-primary">未支付</span>','<span class="label label-success">已支付</span>','<span class="label label-danger">已退款</span>','<span class="label label-info">已冻结</span>','<span class="label label-warning">预授权</span>'];
	$.ajax({
		type : 'GET',
		url : 'ajax_order.php?act=order&trade_no='+trade_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var data = data.data;
				var item = '<table class="table table-condensed table-hover" id="orderItem">';
				item += '<tr><td colspan="6" style="text-align:center" class="orderTitle"><b>订单信息</b></td></tr>';
				item += '<tr class="orderTitle"><td class="info" class="orderTitle">系统订单号</td><td colspan="5" class="orderContent">'+data.trade_no+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">商户订单号</td><td colspan="5" class="orderContent">'+data.out_trade_no+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">接口订单号</td><td colspan="5" class="orderContent">'+data.api_trade_no+'</td></tr>';
				if(data.bill_mch_trade_no){
					item += '<tr><td class="info" class="orderTitle">渠道交易单号</td><td colspan="5" class="orderContent">'+data.bill_mch_trade_no+'</td></tr>';
				}
				if(data.bill_trade_no){
					item += '<tr><td class="info" class="orderTitle">用户交易单号</td><td colspan="5" class="orderContent">'+data.bill_trade_no+'</td></tr>';
				}
				item += '<tr><td class="info">商户ID</td class="orderTitle"><td colspan="5" class="orderContent"><a href="./ulist.php?my=search&column=uid&value='+data.uid+'" target="_blank">'+data.uid+'</a></td>';
				item += '<tr><td class="info" class="orderTitle">支付方式</td><td colspan="5" class="orderContent">'+data.typename+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">支付通道</td><td colspan="5" class="orderContent"><a href="./pay_channel.php?id='+data.channel+'" target="_blank">'+data.channelname+'</a></td></tr>';
				if(data.subchannel > 0){
					item += '<tr><td class="info" class="orderTitle">自定义子通道</td><td colspan="5" class="orderContent">'+data.subchannelname+'</td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle">商品名称</td><td colspan="5" class="orderContent">'+data.name+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">订单金额</td><td colspan="5" class="orderContent">'+data.money+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">实际支付金额</td><td colspan="5" class="orderContent">'+data.realmoney+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">商户分成金额</td><td colspan="5" class="orderContent">'+data.getmoney+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">手续费利润</td><td colspan="5" class="orderContent">'+data.profitmoney+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">创建时间</td><td colspan="5" class="orderContent">'+data.addtime+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">完成时间</td><td colspan="5" class="orderContent">'+data.endtime+'</td></tr>';
				if(data.status==2){
					item += '<tr><td class="info" class="orderTitle">退款时间</td><td colspan="5" class="orderContent">'+data.refundtime+'</td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle" title="只有在官方通道支付完成后才能显示">支付账号</td><td colspan="5" class="orderContent">'+data.buyer+(data.buyer!=null?'&nbsp;&nbsp;<a href="javascript:addBlackList(0,\''+data.buyer+'\')" class="btn btn-xs btn-default">拉黑</a>':'')+'</td></tr>';
				if(data.mobile){
					item += '<tr><td class="info" class="orderTitle">手机号码</td><td colspan="5" class="orderContent">'+data.mobile+'&nbsp;&nbsp;<a href="javascript:addBlackList(0,\''+data.mobile+'\')" class="btn btn-xs btn-default">拉黑</a></td></tr>';
				}
				item += '<tr><td class="info" class="orderTitle">网站域名</td><td colspan="5" class="orderContent"><a href="http://'+data.domain+'" target="_blank" rel="noreferrer">'+data.domain+'</a></td></tr>';
				item += '<tr><td class="info" class="orderTitle">支付IP</td><td colspan="5" class="orderContent"><a href="https://m.ip138.com/iplookup.asp?ip='+data.ip+'" target="_blank" rel="noreferrer">'+data.ip+'</a>&nbsp;&nbsp;<a href="javascript:addBlackList(1,\''+data.ip+'\')" class="btn btn-xs btn-default">拉黑</a></td></tr>';
				item += '<tr><td class="info" class="orderTitle">扩展参数</td><td colspan="5" class="orderContent">'+data.param+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">订单状态</td><td colspan="5" class="orderContent">'+status[data.status]+'</td></tr>';
				if(data.status>0){
					item += '<tr><td class="info" class="orderTitle">通知状态</td><td colspan="5" class="orderContent">'+(data.notify==0?'<span class="label label-success">通知成功</span>':'<span class="label label-danger">通知失败</span>（已通知'+data.notify+'次）')+'</td></tr>';
				}
				item += '<tr><td colspan="6" style="text-align:center" class="orderTitle"><b>订单操作</b></td></tr>';
				item += '<tr><td colspan="6"><a href="javascript:callnotify(\''+data.trade_no+'\')" class="btn btn-xs btn-default">重新通知(异步)</a>&nbsp;<a href="javascript:callreturn(\''+data.trade_no+'\')" class="btn btn-xs btn-default">重新通知(同步)</a>'+(data.combine==1?'&nbsp;<a href="javascript:showSubOrders(\''+data.trade_no+'\')" class="btn btn-xs btn-default">查看子订单列表</a>':'')+'</td></tr>';
				item += '</table>';
				var area = [$(window).width() > 480 ? '480px' : '100%', ';max-height:100%'];
				layer.open({
				  type: 1,
				  area: area,
				  title: '订单详细信息',
				  skin: 'layui-layer-rim',
				  content: item
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
function callnotify(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=notify',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#vurl").attr("href",data.url);
				document.getElementById("vurl").click();
				searchSubmit();
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	});
	return false;
}
function callreturn(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=notify',
		data : {trade_no:trade_no,isreturn:1},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#vurl").attr("href",data.url);
				document.getElementById("vurl").click();
				searchSubmit();
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	});
	return false;
}
function batch_callnotify(orderids, index){
	index = index || 0;
	if (index >= orderids.length) {
		layer.alert('已成功通知'+orderids.length+'条订单');
		return;
	}
	var trade_no = orderids[index];
	var ii = layer.msg('正在重新通知' + trade_no, {icon: 16, shade: 0.3, time: 0});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=notify',
		data : {trade_no:trade_no, isget:1},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			batch_callnotify(orderids, index + 1);
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function refund(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=getmoney',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.open({
					area: ['360px'],
					title: '手动退款确认',
					content: '<p>此操作将从该商户扣除订单分成金额，你需要手动退款给购买者。</p><div class="form-group"><div class="input-group"><div class="input-group-addon">退款金额</div><input type="text" class="form-control" name="refund1" value="'+data.money+'" placeholder="请输入退款金额" autocomplete="off"/></div></div>',
					yes: function(){
						var money = $("input[name='refund1']").val();
						if(money == ''){
							layer.alert('金额不能为空');return;
						}
						var ii = layer.load(2, {shade:[0.1,'#fff']});
						$.ajax({
							type : 'POST',
							url : 'ajax_order.php?act=refund',
							data : {trade_no:trade_no, money:money},
							dataType : 'json',
							success : function(data) {
								layer.close(ii);
								if(data.code == 0){
									layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
								}else{
									layer.alert(data.msg, {icon:7});
								}
							},
							error:function(data){
								layer.close(ii);
								layer.msg('服务器错误');
							}
						});
					}
				});
			}else{
				layer.alert(data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function apirefund(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=getmoney',
		data : {trade_no:trade_no,api:"1"},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.open({
					area: ['360px'],
					title: 'API退款确认',
					content: '<p>此操作将直接原路退款该订单，退款金额不能大于订单金额。</p><div class="form-group"><div class="input-group"><div class="input-group-addon">退款金额</div><input type="text" class="form-control" name="refund2" value="'+data.money+'" placeholder="请输入退款金额" autocomplete="off"/></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">支付密码</div><input type="text" class="form-control" name="paypwd" value="" placeholder="请输入支付密码" autocomplete="off"/></div></div>',
					yes: function(){
						var money = $("input[name='refund2']").val();
						var paypwd = $("input[name='paypwd']").val();
						if(money == '' || paypwd == ''){
							layer.alert('金额或密码不能为空');return;
						}
						var ii = layer.load(2, {shade:[0.1,'#fff']});
						$.ajax({
							type : 'POST',
							url : 'ajax_order.php?act=apirefund',
							data : {trade_no:trade_no, money:money, paypwd:paypwd},
							dataType : 'json',
							success : function(data) {
								layer.close(ii);
								if(data.code == 0){
									layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
								}else{
									layer.alert(data.msg, {icon:7});
								}
							},
							error:function(data){
								layer.close(ii);
								layer.msg('服务器错误');
							}
						});
					}
				});
			}else{
				layer.alert(data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function batch_apirefund(orderids){
	layer.open({
		area: ['360px'],
		title: 'API退款确认',
		content: '<p>此操作将直接原路退款这'+orderids.length+'条订单，退款金额均为订单实际支付金额。</p><div class="form-group"><div class="input-group"><div class="input-group-addon">支付密码</div><input type="text" class="form-control" name="paypwd" value="" placeholder="请输入支付密码" autocomplete="off"/></div></div>',
		yes: function(){
			var paypwd = $("input[name='paypwd']").val();
			if(paypwd == ''){
				layer.alert('密码不能为空');return;
			}
			batch_apirefund_order(orderids, 0, paypwd);
		}
	});
}
function batch_apirefund_order(orderids, index, paypwd){
	if (index >= orderids.length) {
		layer.alert('已成功退款'+orderids.length+'条订单！', {icon: 1}, function(){
			layer.closeAll();searchSubmit();
		});
		return;
	}
	var trade_no = orderids[index];
	var row = $("#listTable").bootstrapTable('getRowByUniqueId', trade_no);
	var money = row.realmoney;
	var ii = layer.msg('正在退款' + trade_no + '（'+money+'元）', {icon: 16, shade: 0.3, time: 0});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=apirefund',
		data : {trade_no:trade_no, money:money, paypwd:paypwd},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			index > 0 && searchSubmit();
			if(data.code == 0){
				batch_apirefund_order(orderids, index + 1, paypwd);
			}else{
				layer.alert(trade_no + data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function freeze(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=freeze',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function unfreeze(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=unfreeze',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function setStatus(trade_no, status) {
	if(status==5){
		var confirmobj = layer.confirm('你确实要删除此订单吗？', {
			btn: ['确定','取消']
		}, function(){
			setStatusDo(trade_no, status);
		});
	}else{
		setStatusDo(trade_no, status);
	}
}
function setStatusDo(trade_no, status) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'get',
		url : 'ajax_order.php',
		data : 'act=setStatus&trade_no=' + trade_no + '&status=' + status,
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
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function fillorder(trade_no) {
	var confirmobj = layer.confirm('此操作将不管该订单是否真的支付，直接改为已支付状态并给商户分成，是否确定？', {
		btn: ['确定','取消'], icon:0
	}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_order.php?act=fillorder',
			data : {trade_no:trade_no},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
				}else{
					layer.alert(data.msg);
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
function alipaydSettle(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipaydSettle',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
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
function batch_alipayd_settle(orderids, index) {
	index = index || 0;
	if (index >= orderids.length) {
		layer.alert('已成功结算'+orderids.length+'条订单！', {icon: 1}, function(){
			layer.closeAll();searchSubmit();
		});
		return;
	}
	var trade_no = orderids[index];
	var ii = layer.msg('正在结算' + trade_no, {icon: 16, shade: 0.3, time: 0});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipaydSettle',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			index > 0 && searchSubmit();
			if(data.code == 0){
				batch_alipayd_settle(orderids, index + 1);
			}else{
				layer.alert(trade_no + data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function alipayPreAuthPay(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipayPreAuthPay',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
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
function alipayUnfreeze(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipayUnfreeze',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
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
function addBlackList(stype, scontent){
	var stitle = stype == 1?'IP地址':'支付账号';
	layer.open({
		type: 1,
		area: ['380px'],
		closeBtn: 2,
		title: '添加黑名单',
		content: '<div style="padding:15px"><div class="form-group"><div class="input-group"><div class="input-group-addon">'+stitle+'</div><input class="form-control" type="text" name="add_content" value="'+scontent+'" autocomplete="off" ></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">有效期</div><input class="form-control" type="text" name="add_days" value="0" autocomplete="off" placeholder="0为永久"><div class="input-group-addon">天</div></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">备注</div><input class="form-control" type="text" name="add_remark" value="" autocomplete="off" placeholder="选填"></div></div></div>',
		btn: ['确认', '取消'],
		yes: function(){
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
				data : {type:stype, content:content, days:days, remark:remark},
				dataType : 'json',
				success : function(data) {
					layer.close(ii);
					if(data.code == 0){
						layer.alert(data.msg, {icon:1}, function(){ layer.closeAll(); });
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
function alipayRedPacketTansfer(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipayRedPacketTansfer',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
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
function showSubOrders(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_order.php?act=subOrders&trade_no='+trade_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var list = data.data;
				var status = ['<span class="label label-primary">未支付</span>','<span class="label label-success">已支付</span>','<span class="label label-danger">已退款</span>'];
				var settle = ['<span class="label label-info">未结算</span>','<span class="label label-success">已结算</span>'];
				var item = '<table class="table table-condensed table-hover" id="orderItem">';
				item += '<thead><th class="orderTitle">系统订单号</th><th class="orderTitle">接口订单号</th><th class="orderTitle">订单金额</th class="orderTitle"><th class="orderTitle">订单状态</th><th class="orderTitle">结算状态</th></thead><tbody>';
				for(var i=0; i<list.length; i++){
					var statustext = status[list[i].status];
					if(list[i].status == 2 && list[i].refundmoney > 0 && list[i].refundmoney < list[i].money){
						statustext += '<font color=red>('+list[i].refundmoney+')</font>';
					}
					item += '<tr><td>'+list[i].sub_trade_no+'</td><td>'+list[i].api_trade_no+'</td><td>¥<b>'+list[i].money+'</b></td><td>'+statustext+'</td><td>'+(data.settle>0?settle[list[i].settle]:'')+'</td></tr>';
				}
				item += '</tbody></table>';
				var area = [$(window).width() > 680 ? '680px' : '100%'];
				layer.open({
				  type: 1,
				  area: area,
				  title: '合单支付 - 子订单列表',
				  skin: 'layui-layer-rim',
				  shadeClose: true,
				  content: item
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
function getBatchOperateData(selections, field) {
	var counts = {
		0: field ? [] : 0, // 改未完成
		1: field ? [] : 0, // 改已完成
		2: field ? [] : 0, // 冻结订单
		3: field ? [] : 0, // 解冻订单
		4: field ? [] : 0, // 删除订单
		5: field ? [] : 0, // API退款
		6: field ? [] : 0, // 确认结算
		7: field ? [] : 0 // 重新通知
	};
	selections.forEach(function(row) {
		var status = parseInt(row.status);
		if (status == 1) {
			if (field) {
				counts[0].push(row[field]);
				counts[2].push(row[field]);
				counts[4].push(row[field]);
				counts[5].push(row[field]);
			} else {
				counts[0]++;
				counts[2]++;
				counts[4]++;
				counts[5]++;
			}
		} else if (status == 2) {
			if (field) {
				counts[0].push(row[field]);
				counts[1].push(row[field]);
				counts[4].push(row[field]);
			} else {
				counts[0]++;
				counts[1]++;
				counts[4]++;
			}
		} else if (status == 3) {
			if (field) {
				counts[3].push(row[field]);
				counts[4].push(row[field]);
				counts[5].push(row[field]);
			} else {
				counts[3]++;
				counts[4]++;
				counts[5]++;
			}
		} else {
			if (field) {
				counts[1].push(row[field]);
				counts[4].push(row[field]);
			} else {
				counts[1]++;
				counts[4]++;
			}
		}
		if(row.plugin=='alipayd' || row.plugin=='wxpaynp'){
			if(row.settle == '1' || row.settle == '3'){
				if (field) {
					counts[6].push(row[field]);
				} else {
					counts[6]++;
				}
			}
		}
		if(row.notify != '0'){
			if (field) {
				counts[7].push(row[field]);
			} else {
				counts[7]++;
			}
		}
	});
	return counts;
}
$(document).ready(function(){
	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
	$("#batchAction").click(function(e) {
		e.preventDefault();
		var selections = $('#listTable').bootstrapTable('getSelections'),
			button = $(this);
		var counts = getBatchOperateData(selections);
		button.next('.dropdown-menu').find('a[data-action-type]').each(function() {
			var actionType = $(this).data('action-type');
			var count = counts[actionType];
			var originalText = $(this).text().split('(')[0];
			$(this).text(originalText + (count > 0 ? ' (可执行' + count + '条)' : ' (0)'));
		});
	});
})
</script>