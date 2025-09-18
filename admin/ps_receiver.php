<?php
/**
 * 分账规则列表
**/
include("../includes/common.php");
$title='分账规则列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<style>
tbody tr>td:nth-child(4){overflow: hidden;text-overflow: ellipsis;white-space: nowrap;max-width:160px;}
tbody tr>td:nth-child(5){overflow: hidden;text-overflow: ellipsis;white-space: nowrap;max-width:320px;}
#modal-store .table>tbody>tr>td{vertical-align: middle;}
</style>
  <div class="container" style="padding-top:70px;">
	<div class="row">
    <div class="col-md-12">
<?php
$support_plugins = \lib\ProfitSharing\CommUtil::$plugins;
$channels = $DB->getAll("SELECT id,name,plugin,mode FROM pre_channel WHERE plugin IN ('".implode("','",$support_plugins)."') ORDER BY id ASC");
$channel_select = '';
foreach($channels as $row){
	$channel_select .= '<option value="'.$row['id'].'" plugin="'.$row['plugin'].'" mode="'.$row['mode'].'">'.$row['id'].'__'.$row['name'].'</option>';
}

?>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">分账规则修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="id" id="id"/>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">支付通道</label>
						<div class="col-sm-9">
							<select name="channel" id="channel" class="form-control" onchange="changeChannel()">
								<option value="0">请选择支付通道</option><?php echo $channel_select; ?>
							</select>
							<font color="green">支持的支付插件可在支付插件列表查看</font>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">商户ID</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="uid" id="uid" placeholder="可留空，留空则为当前支付通道所有订单">
							<font color="green" id="uid_note" style="display:none">填写商户ID后，将自动从商户余额扣除分账的金额</font>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">商户子通道</label>
						<div class="col-sm-9">
							<select name="subchannel" id="subchannel" class="form-control"></select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">订单最小金额</label>
						<div class="col-sm-9">
						<div class="input-group"><input type="text" class="form-control" name="minmoney" id="minmoney" placeholder="订单超过该金额才进行分账，留空则不限制"><span class="input-group-addon">元</span></div>
						</div>
					</div>
					<input type="hidden" name="info" id="info" value="">
					<hr/>
				</form>
				<div class="form-group text-center">
					<label>分账接收人列表</label>
					<table class="table table-bordered" id="receiverTable" style="margin-bottom:0;">
						<thead>
							<tr>
								<th width="40%">接收方账号</th>
								<th width="25%">接收方姓名</th>
								<th width="25%">分账比例</th>
								<th width="10%">操作</th>
							</tr>
						</thead>
						<tbody id="receiverList">
						</tbody>
					</table>
					<button type="button" class="btn btn-sm btn-success" onclick="addReceiverRow()" id="addReceiverBtn" style="margin-bottom:2px;">
						<i class="fa fa-plus"></i> 增加接收人
					</button>
				</div>
				
				<p style="color:green" id="account_note"></p>
				<p style="color:green">分账比例一般限制最高30%（微信还需要减去手续费，例如手续费是0.6%，则填写29.4%）</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
				<button type="button" class="btn btn-primary" id="store" onclick="save()">保存</button>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-info">
   <div class="panel-heading"><h3 class="panel-title">分账规则列表</h3></div>
<div class="panel-body">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="form-group">
    <label><b>搜索</b></label>
	<select name="column" class="form-control" default="<?php echo @$_GET['column']?>"><option value="channel">通道ID</option><option value="uid">商户ID</option><option value="info">接收方</option><option value="id">ID</option></select>
    <input type="text" class="form-control" name="value" placeholder="输入搜索内容" value="<?php echo @$_GET['value']?>">
	<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;搜索</button>&nbsp;
	<a href="javascript:addframe()" class="btn btn-success"><i class="fa fa-plus"></i>&nbsp;新增</a>&nbsp;
	<a href="javascript:searchClear()" class="btn btn-default" title="刷新列表"><i class="fa fa-refresh"></i></a>
  </div>
</form>
      <table id="listTable">
	  </table>
	</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
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
		url: 'ajax_profitsharing.php?act=receiverList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		uniqueId: 'id',
		columns: [
			{
				field: 'id',
				title: 'ID',
				formatter: function(value, row, index) {
					return '<b>'+value+'</b>';
				}
			},
			{
				field: 'channel',
				title: '支付通道',
				formatter: function(value, row, index) {
					return '<a href="./pay_channel.php?kw='+value+'" target="_blank">'+value+'</a>__'+row.channelname;
				}
			},
			{
				field: 'uid',
				title: '商户ID',
				formatter: function(value, row, index) {
					return value?'<a href="./ulist.php?my=search&column=uid&value='+value+'" target="_blank">'+value+'</a>':null;
				}
			},
			{
				field: 'subchannel',
				title: '商户子通道',
				formatter: function(value, row, index) {
					return value?value+'__'+row.subchannelname:null;
				}
			},
			{
				field: 'info',
				title: '分账接收方+分账比例',
				formatter: function(value, row, index) {
					var html = '';
					var receiver = value ? JSON.parse(value) : [{account:row.account, name:row.name, rate:row.rate}];
					$.each(receiver, function(i, item) {
						html += '<div>';
						html += '<span>'+item.account+(item.name?'／'+item.name:'')+'</span>﹣';
						html += '<span style="color:#451ddf"><b>'+item.rate+'</b>%</span>';
						html += '</div>';
					});
					return html;
				}
			},
			{
				field: 'status',
				title: '状态',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<a class="btn btn-xs btn-success" onclick="setStatus('+row.id+',0)">已开启</a>';
					}else{
						return '<a class="btn btn-xs btn-warning" onclick="setStatus('+row.id+',1)">已关闭</a>';
					}
				}
			},
			{
				field: 'status',
				title: '操作',
				formatter: function(value, row, index) {
					return '<a class="btn btn-xs btn-info" onclick="editframe('+row.id+')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('+row.id+')">删除</a>&nbsp;<a href="./ps_order.php?rid='+row.id+'" target="_blank" class="btn btn-xs btn-default">记录</a>';
				}
			},
		]
	})
})
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增分账规则");
	$("#action").val("add");
	$("#subchannel").empty();
	$("#id").val('');
	$("#channel").val(0);
	$("#uid").val('');
	$("#subchannel").val(0);
	$("#minmoney").val('');
	$("#receiverList").empty();
	receiverCount = 0;
	addReceiverRow();
}
function changeChannel(subchannel){
	subchannel = subchannel || null
	var channel = parseInt($("#channel").val());
	if(channel>0){
		var plugin = $("#channel option:selected").attr('plugin');
		if(plugin == 'wxpayn' || plugin == 'wxpaynp')
			$("#account_note").text('账号支持填写商户号或个人OpenId！OpenId获取地址：<?php echo $siteurl?>user/openid.php?channel='+channel);
		else if(plugin == 'alipay' || plugin == 'alipaysl')
			$("#account_note").text('账号支持填写支付宝UID（2088开头的16位数字）或支付宝账号');
		else
			$("#account_note").text('');
		var mode = $("#channel option:selected").attr('mode');
		if(mode == '1')
			$("#uid_note").hide();
		else
			$("#uid_note").show();
	}else{
		$("#account_note").text('');
	}
	getSubChannels(subchannel);
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_profitsharing.php?act=get_receiver&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改分账规则");
				$("#action").val("edit");
				$("#subchannel").empty();
				$("#id").val(data.data.id);
				$("#channel").val(data.data.channel);
				$("#uid").val(data.data.uid);
				$("#subchannel").val(data.data.subchannel);
				$("#minmoney").val(data.data.minmoney);
				changeChannel(data.data.subchannel);
				
				// Initialize receiver table
				$("#receiverList").empty();
				receiverCount = 0;
				$.each(data.data.info, function(index, item) {
					addReceiverRow(item);
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
function save(){
	if($("#channel").val()=='0'){
		layer.alert('请选择支付通道！');return false;
	}
	var receivers = [];
	var allrate = 0;
	$('#receiverList tr').each(function() {
		var account = $(this).find('input[name^="account_"]').val();
		var name = $(this).find('input[name^="name_"]').val();
		var rate = $(this).find('input[name^="rate_"]').val();
		
		if(account && rate) {
			receivers.push({
				account: account,
				name: name,
				rate: rate
			});
			allrate += parseFloat(rate);
		}
	});
	if(receivers.length == 0) {
		layer.alert('请至少添加一个有效的分账接收人！');return false;
	}
	if(allrate == 0) {
		layer.alert('分账比例总和不能为0！');return false;
	}
	if(allrate > 100) {
		layer.alert('分账比例总和不能超过100%！');return false;
	}
	$('#info').val(JSON.stringify(receivers));

	var url = 'ajax_profitsharing.php?act=add_receiver';
	if($("#action").val() == 'edit') url = 'ajax_profitsharing.php?act=edit_receiver';
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : url,
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
	var confirmobj = layer.confirm('你确实要删除此分账规则吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'POST',
		url : 'ajax_profitsharing.php?act=del_receiver',
		data : {id: id},
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
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
function setStatus(id,status) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_profitsharing.php?act=set_receiver',
		data : {id:id, status:status},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon: 1}, function(){layer.closeAll();window.location.reload()});
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function getSubChannels(subchannel){
	subchannel = subchannel || null;
	var uid = $("#uid").val();
	var channel = $("#channel").val();
	$("#subchannel").empty();
	if(uid == '' || channel == '0') return;
	$.ajax({
		type : 'GET',
		url : 'ajax_pay.php?act=getSubChannels&channel='+channel+'&uid='+uid,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				$("#subchannel").append('<option value="0">可留空，留空表示当前商户所有子通道</option>');
				$.each(data.data, function (i, res) {
					$("#subchannel").append('<option value="'+res.id+'">'+res.id+'__'+res.name+'</option>');
				})
				if(subchannel!=null)$("#subchannel").val(subchannel);
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
var receiverCount = 0;
var maxReceivers = 10;

function addReceiverRow(data) {
    if(receiverCount >= maxReceivers) {
        layer.alert('最多只能添加'+maxReceivers+'个分账接收人');
        return;
    }
    
	data = data || {account:'', name:'', rate:''};
    var rowId = 'receiver_'+receiverCount;
    var html = '<tr id="'+rowId+'">'+
        '<td><input type="text" class="form-control" name="account_'+receiverCount+'" value="'+data.account+'" placeholder="" required></td>'+
        '<td><input type="text" class="form-control" name="name_'+receiverCount+'" value="'+data.name+'" placeholder="可留空"></td>'+
        '<td><div class="input-group"><input type="number" class="form-control" name="rate_'+receiverCount+'" value="'+data.rate+'" placeholder="1-100" min="1" max="100" required><span class="input-group-addon">%</span></div></td>'+
        '<td><button type="button" class="btn btn-danger btn-sm" onclick="removeReceiverRow(\''+rowId+'\')"><i class="fa fa-trash"></i></button></td>'+
        '</tr>';
    
    $('#receiverList').append(html);
    receiverCount++;
    updateReceiverBtn();
}

function removeReceiverRow(rowId) {
	if(receiverCount <= 1) {
		return;
	}
    $('#'+rowId).remove();
    receiverCount--;
    updateReceiverBtn();
}

function updateReceiverBtn() {
    if(receiverCount >= maxReceivers) {
        $('#addReceiverBtn').prop('disabled', true);
    } else {
        $('#addReceiverBtn').prop('disabled', false);
    }
}

$(document).ready(function(){
    var items = $("select[default]");
    for (i = 0; i < items.length; i++) {
        if($(items[i]).attr("default")!=''){
            $(items[i]).val($(items[i]).attr("default"));
        }
    }
    $("#uid").change(function(){
        getSubChannels();
    });
})
</script>
