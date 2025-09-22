<?php
/**
 * 商户列表
**/
include("../includes/common.php");
$title='商户列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$select = '';
$rs = $DB->getAll("SELECT * FROM pre_group");
foreach($rs as $row){
	$select .= '<option value="'.$row['gid'].'">'.$row['name'].'</option>';
}
unset($rs);
?>
<style>
#orderItem .orderTitle{word-break:keep-all;}
#orderItem .orderContent{word-break:break-all;}
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<div class="modal" id="modal-rmb">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">余额充值与扣除</h4>
			</div>
			<div class="modal-body">
				<form id="form-rmb" onsubmit="return false;">
					<input type="hidden" name="uid" value="">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon p-0">
								<select name="do"
										style="-webkit-border-radius: 0;height:20px;border: 0;outline: none !important;border-radius: 5px 0 0 5px;padding: 0 5px 0 5px;">
									<option value="0">充值</option>
									<option value="1">扣除</option>
								</select>
							</span>
							<input type="number" class="form-control" name="rmb" placeholder="输入金额">
							<span class="input-group-addon">元</span>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-info" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary" id="recharge">确定</button>
			</div>
		</div>
	</div>
</div>
<div class="modal" id="modal-advance">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">高级搜索</h4>
			</div>
			<div class="modal-body">
				<form id="form-advance" onsubmit="return false;">
					<input type="hidden" name="uid" value="">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon p-0">
								超过
							</span>
							<input type="number" class="form-control" name="days" placeholder="输入天数" value="30">
							<span class="input-group-addon">天无订单的用户</span>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-outline-info" data-dismiss="modal">取消</button>
				<button type="button" class="btn btn-primary" id="advance_search">查找</button>
			</div>
		</div>
	</div>
</div>
<div class="modal" id="modal-export">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
				<h4 class="modal-title">导出用户</h4>
			</div>
			<div class="modal-body">
<?php
$select = '<option value="">全部用户组</option>';
$rs = $DB->getAll("SELECT * FROM pre_group");
foreach($rs as $row){
	$select .= '<option value="'.$row['gid'].'">'.$row['name'].'</option>';
}
unset($rs);
?>
				<form action="" id="exportUser" method="POST" onsubmit="return exportUser()" role="form">
					<div class="form-group">
						<div class="input-group"><div class="input-group-addon">用户组</div>
						<select name="gid" class="form-control"><?php echo $select?></select>
					</div></div>
					<div class="form-group">
						<div class="input-group input-daterange"><div class="input-group-addon">注册时间</div>
						<input type="text" id="starttime" name="starttime" class="form-control" placeholder="开始日期" autocomplete="off">
						<span class="input-group-addon"><i class="fa fa-chevron-right"></i></span>
						<input type="text" id="endtime" name="endtime" class="form-control" placeholder="结束日期" autocomplete="off">
					</div></div>
					<div class="form-group">
						<div class="input-group"><div class="input-group-addon">用户状态</div>
						<select name="dstatus" class="form-control"><option value="">全部状态</option><option value="1">正常状态</option></select>
					</div></div>
					<p><input type="submit" name="submit" value="导出" class="btn btn-primary form-control"/></p>
				</form>
			</div>
		</div>
	</div>
</div>
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
<input type="hidden" class="form-control" name="gid">
<input type="hidden" class="form-control" name="upid">
<input type="hidden" class="form-control" name="order_days">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="uid">商户号</option><option value="key">密钥</option><option value="account">结算账号</option><option value="username">结算姓名</option><option value="url">域名</option><option value="qq">QQ</option><option value="phone">手机号码</option><option value="email">邮箱</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容">
  </div>
  <div class="form-group">
	<select name="dstatus" class="form-control"><option value="0">全部用户</option><option value="pay_2">待审核用户</option><option value="status_1">用户状态正常</option><option value="status_0">用户状态封禁</option><option value="pay_1">支付状态正常</option><option value="pay_0">支付状态关闭</option><option value="settle_1">结算状态正常</option><option value="settle_0">结算状态关闭</option><option value="cert_1">已实名认证</option><option value="cert_0">未实名认证</option></select>
  </div>
  <div class="form-group">
	<select name="order" class="form-control"><option value="">商户号倒序</option><option value="money_desc">余额倒序</option><option value="money_asc">余额正序</option><option value="lasttime_desc">登录时间倒序</option><option value="lasttime_asc">登录时间正序</option></select>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:advance()" class="btn btn-default">高级搜索</a>
  <a href="./uset.php?my=add" class="btn btn-success">添加商户</a>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新用户列表"><i class="fa fa-refresh"></i></a>
  <a href="javascript:$('#modal-export').modal('show')" class="btn btn-default">导出</a>
</form>

      <table id="listTable">
	  </table>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
var pay_domain = '<?php echo ($conf['pay_domain_forbid']==1 || $conf['pay_domain_open']==1)?'true':'false';?>';
$(document).ready(function(){
	updateToolbar();
	const defaultPageSize = 30;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax_user.php?act=userList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'uid',
				title: '商户号/用户组',
				formatter: function(value, row, index) {
					let groupname = row.groupname;
					if(groupname!=null && groupname.length > 14) groupname = groupname.substring(0,14);
					return '<b>'+value+'</b><br/><span onclick="editGroup('+value+','+row.gid+',\''+row.endtime+'\')" style="color:blue" title="到期时间：'+(row.endtime==null?'永久':row.endtime)+'">'+groupname+'</span>';
				}
			},
			{
				field: 'money',
				title: '余额',
				formatter: function(value, row, index) {
					return '<b><a href="javascript:showRecharge('+row.uid+')">'+value+'</a></b>';
				}
			},
			{
				field: 'settle_id',
				title: '结算账号/姓名',
				formatter: function(value, row, index) {
					var type_arr = {1:'alipay',2:'wxpay',3:'qqpay',4:'bank'};
					return row.account ? '<span onclick="inputInfo('+row.uid+')" title="点击修改结算账号">'+(value==2?'<font color="green">WX:</font>':'')+(value==3?'<font color="green">QQ:</font>':'')+row.account+'<br/>'+row.username+'</span> <a href="./transfer_add.php?app='+type_arr[value]+'&account='+row.account+'&username='+row.username+'" target="_blank"><i class="fa fa-send-o fa-fw"></i></a>' : '<span onclick="inputInfo('+row.uid+')" title="点击修改结算账号">未设置</span>';
				}
			},
			{
				field: 'qq',
				title: '联系方式',
				formatter: function(value, row, index) {
					return (value ? 'QQ:'+(isMobile() ? '<a href="mqqwpa://im/chat?chat_type=wpa&version=1&src_type=web&web_src=oicqzone.com&uin='+value+'">'+value+'</a>' : '<a href="tencent://message/?uin='+value+'&amp;Site=qq&amp;Menu=yes">'+value+'</a>') : '')+'<br/>'+(row.phone?row.phone:row.email);
				}
			},
			{
				field: 'url',
				title: '注册时间/域名',
				formatter: function(value, row, index) {
					return row.addtime+'<br/>'+(value?value:'')+(pay_domain=='true'?' [<a href="./domain.php?uid='+row.uid+'" target="_blank" >域名</a>]':'');
				}
			},
			{
				field: 'status',
				title: '状态',
				formatter: function(value, row, index) {
					let html = '';
					if(value == '1'){
						html += '<a href="javascript:setStatus('+row.uid+',\'user\',0)"><font color=green><i class="fa fa-check-circle"></i>正常</font></a>';
					}else{
						html += '<a href="javascript:setStatus('+row.uid+',\'user\',1)"><font color=red><i class="fa fa-times-circle"></i>封禁</font></a>';
					}
					html += '&nbsp;';
					if(row.cert == '1'){
						html += '<a href="javascript:showCert('+row.uid+')" title="查看实名认证信息"><font color=green><i class="fa fa-check-circle-o"></i>已实名</font></a>';
					}else{
						html += '<a href="javascript:showCert('+row.uid+')" title="查看实名认证信息"><font color=grey><i class="fa fa-times-circle"></i>未实名</font></a>';
					}
					html += '<br/>';
					if(row.pay == '2'){
						html += '<a href="javascript:setStatus('+row.uid+',\'pay\',1)"><font color=orange><i class="fa fa-exclamation-circle"></i>未审核</font></a>';
					}else if(row.pay == '1'){
						html += '<a href="javascript:setStatus('+row.uid+',\'pay\',0)"><font color=green><i class="fa fa-check-circle"></i>支付</font></a>';
					}else{
						html += '<a href="javascript:setStatus('+row.uid+',\'pay\',1)"><font color=red><i class="fa fa-times-circle"></i>支付</font></a>';
					}
					html += '&nbsp;';
					if(row.settle == '1'){
						html += '<a href="javascript:setStatus('+row.uid+',\'settle\',0)"><font color=green><i class="fa fa-check-circle"></i>结算</font></a>';
					}else{
						html += '<a href="javascript:setStatus('+row.uid+',\'settle\',1)"><font color=red><i class="fa fa-times-circle"></i>结算</font></a>';
					}
					return html;
				}
			},
			{
				field: '',
				title: '操作',
				formatter: function(value, row, index) {
					return '<a href="./uset.php?my=edit&uid='+row.uid+'" class="btn btn-xs btn-info">编辑</a>&nbsp;<a href="./sso.php?uid='+row.uid+'" target="_blank" class="btn btn-xs btn-success">登录</a>&nbsp;<a href="javascript:delUser('+row.uid+')" class="btn btn-xs btn-danger">删除</a><br/><a href="./order.php?uid='+row.uid+'" target="_blank" class="btn btn-xs btn-default">订单</a>&nbsp;<a href="./slist.php?uid='+row.uid+'" target="_blank" class="btn btn-xs btn-default">结算</a>&nbsp;<a href="./record.php?uid='+row.uid+'" target="_blank" class="btn btn-xs btn-default">明细</a>';
				}
			},
		],
	})
})

function showKey(uid,key){
	var clipboard;
	var confirmobj = layer.confirm(key+'<input type="hidden" id="copyContent" value="'+key+'"/>', {
	  title:'查看密钥',shadeClose:true,btn: ['复制','重置','关闭'], success: function(){
		clipboard = new Clipboard('.layui-layer-btn0',{text: function() {return key;}});
		clipboard.on('success', function (e) {
			$(".layui-layer-btn0").text('复制成功')
		});
		clipboard.on('error', function (e) {
			$(".layui-layer-btn0").text('复制失败')
		});
	  }
	  ,end: function(){
		clipboard.destroy();
	  }
	}, function(){
	}, function(){
		if(!confirm('确定要重置商户'+uid+'的密钥吗？')){
			showKey(uid,key);return;
		}
		$.ajax({
			type : 'GET',
			url : 'ajax_user.php?act=resetUser&uid='+uid,
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					alert('重置密钥成功！');
					showKey(uid,data.key);
				}else{
					layer.alert(data.msg, {icon:2});
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
function setStatus(uid,type,status) {
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=setUser',
		data : {uid:uid, type:type, status:status},
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				searchSubmit();
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
function editGroup(uid, gid, endtime){
	if(endtime == 'null') endtime = '';
	var html = '<div class="modal-body"><form class="form" id="form-info">';
	html += '<div class="form-group"><div class="input-group"><div class="input-group-addon">用户组</div><select class="form-control" id="gid"><?php echo $select?></select></div></div>';
	html += '<div class="form-group"><div class="input-group"><div class="input-group-addon">有效期</div><select class="form-control" id="endtime_set"><option value="0">永久</option><option value="1">设置日期</option></select><input type="date" class="form-control" name="endtime" value="'+endtime+'" id="endtime_show"></div></div>';
	html += '<div class="form-group"><button type="button" id="save" onclick="saveGroup('+uid+')" class="btn btn-primary btn-block">保存</button></div></form></div>';
	layer.open({
	  type: 1,
	  shadeClose: true,
	  title: '修改用户组',
	  content: html,
	  success: function(){
			$("#gid").val(gid);
			$("#endtime_set").val(endtime==''?0:1);
			$("#endtime_set").change(function(){
				if($(this).val() == 1){
					$("#endtime_show").show();
				}else{
					$("#endtime_show").hide();
				}
			});
			$("#gid").change(function(){
				if($(this).val() == 0){
					$("#endtime_set").val(0);
					$("#endtime_set").change();
				}
			});
			$("#endtime_set").change();
		}
	});
}
function saveGroup(uid){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	var gid = $("#gid").val();
	var endtime = $("#endtime_set").val() == '1' ? $("#endtime_show").val() : '';
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=setUserGroup',
		data : {uid:uid, gid:gid, endtime:endtime},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
					icon: 1,
					closeBtn: false
				},function(){layer.closeAll();searchSubmit();});
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
function showRecharge(uid) {
	$("input[name='uid']").val(uid);
	$('#modal-rmb').modal('show');
}
function inputInfo(uid) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=user_settle_info&uid='+uid,
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
function saveInfo(uid) {
	var pay_type=$("#pay_type").val();
	var pay_account=$("#pay_account").val();
	var pay_name=$("#pay_name").val();
	if(pay_account=='' || pay_name==''){layer.alert('请确保每项不能为空！');return false;}
	$('#save').val('Loading');
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : "POST",
		url : "ajax_user.php?act=user_settle_save",
		data : {uid:uid,pay_type:pay_type,pay_account:pay_account,pay_name:pay_name},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.closeAll();
				layer.msg('保存成功！');
				searchSubmit();
			}else{
				layer.alert(data.msg);
			}
			$('#save').val('保存');
		} 
	});
}
function showCert(uid) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=user_cert&uid='+uid,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var item = '<table class="table table-condensed table-hover">';
				if(data.data.certtype==1){
					item += '<tr><td class="info">商户号</td><td colspan="5">'+uid+'</td></tr><tr><td class="info">认证类型</td><td colspan="5">企业认证</td></tr><tr><td class="info">认证方式</td><td colspan="5">'+data.data.certmethodname+'</td><tr><tr><td class="info">公司名称</td><td colspan="5">'+data.data.certcorpname+'</td><tr><td class="info">营业执照号码</td><td colspan="5">'+data.data.certcorpno+'</td><tr><td class="info">法人姓名</td><td colspan="5">'+data.data.certname+'</td></tr><tr><td class="info">法人身份证号</td><td colspan="5">'+data.data.certno+'</td></tr><tr><td class="info">认证时间</td><td colspan="5">'+data.data.certtime+'</td></tr>';
				}else{
					item += '<tr><td class="info">商户号</td><td colspan="5">'+uid+'</td></tr><tr><td class="info">认证类型</td><td colspan="5">个人认证</td></tr><tr><td class="info">认证方式</td><td colspan="5">'+data.data.certmethodname+'</td><tr><tr><td class="info">真实姓名</td><td colspan="5">'+data.data.certname+'</td></tr><tr><td class="info">身份证号</td><td colspan="5">'+data.data.certno+'</td></tr><tr><td class="info">认证时间</td><td colspan="5">'+data.data.certtime+'</td></tr>';
				}
				item += '</table>';
				layer.open({
				  type: 1,
				  shadeClose: true,
				  title: '查看实名认证信息',
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
function delUser(uid) {
	var confirmobj = layer.confirm('你确实要删除此商户吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  var ii = layer.load(2, {shade:[0.1,'#fff']});
	  $.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=delUser&uid='+uid,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.closeAll();
				searchSubmit();
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
function advance() {
	$('#modal-advance').modal('show');
}
$(document).ready(function(){
	$("#recharge").click(function(){
		var uid=$("input[name='uid']").val();
		var actdo=$("select[name='do']").val();
		var rmb=$("input[name='rmb']").val();
		if(rmb==''){layer.alert('请输入金额');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax_user.php?act=recharge",
			data : {uid:uid,actdo:actdo,rmb:rmb},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.msg('修改余额成功', {time:800});
					$('#modal-rmb').modal('hide');
					searchSubmit();
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
	$("#advance_search").click(function(){
		var days = $("#form-advance input[name='days']").val();
		if(days == '' || days == '0'){
			layer.alert('请输入天数！');return false;
		}
		$("#searchToolbar input[name='order_days']").val(days);
		searchSubmit();
		$('#modal-advance').modal('hide');
	});
	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
})
function exportUser(){
	var starttime = $("#exportUser input[name='starttime']").val();
	var endtime = $("#exportUser input[name='endtime']").val();
	var gid = $("#exportUser select[name='gid']").val();
	var dstatus = $("#exportUser select[name='dstatus']").val();
	window.location.href='./download.php?act=user&starttime='+starttime+'&endtime='+endtime+'&gid='+gid+'&dstatus='+dstatus;
	return false;
}
</script>