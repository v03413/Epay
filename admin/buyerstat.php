<?php
/**
 * 支付用户统计
**/
include("../includes/common.php");
$title='支付用户统计';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$type_select = '<option value="0">所有支付方式</option>';
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
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="input-group">
	<label>查询日期:</label>
  </div>
  <div class="input-group input-daterange">
	<input type="text" id="startday" name="startday" class="form-control dates" placeholder="开始日期" autocomplete="off" value="<?php echo date("Y-m-d")?>">
	<span class="input-group-addon"><i class="fa fa-chevron-right"></i></span>
	<input type="text" id="endday" name="endday" class="form-control dates" placeholder="结束日期" autocomplete="off" value="<?php echo date("Y-m-d")?>">
  </div>
  <div class="form-group">
    <select name="type" class="form-control"><?php echo $type_select?></select>
  </div>
  <div class="form-group">
	<select name="method" class="form-control"><option value="0">支付账号统计</option><option value="1">支付IP统计</option><option value="2">支付手机号统计</option></select>
  </div>
  <button type="submit" class="btn btn-primary">&nbsp;搜索&nbsp;</button>
</form>

      <table id="listTable">
	  </table>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.10.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
$(document).ready(function(){
	updateToolbar();
	var method = $("select[name='method']").val();

	$("#listTable").bootstrapTable({
		url: 'ajax_user.php?act=buyerStat',
		pageNumber: 1,
		pageSize: 30,
		sidePagination: 'client',
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'user',
				title: '支付账号/IP',
				formatter: function(value, row, index) {
					return '<b>'+value+'</b>';
				}
			},
			{
				field: 'order_count',
				title: '付款笔数',
				formatter: function(value, row, index) {
					if(method == '2'){
						return '<a href="./order.php?column=mobile&value='+row.user+'" target="_blank">'+value+'</a>';
					}else if(method == '1'){
						return '<a href="./order.php?column=ip&value='+row.user+'" target="_blank">'+value+'</a>';
					}else if(method == '0'){
						return '<a href="./order.php?column=buyer&value='+row.user+'" target="_blank">'+value+'</a>';
					}
				}
			},
			{
				field: 'trade_no',
				title: '最新订单号',
				formatter: function(value, row, index) {
					return '<a href="./order.php?column=trade_no&value='+value+'" target="_blank">'+value+'</a>';
				}
			},
			{
				field: 'is_black',
				title: '是否拉黑',
				formatter: function(value, row, index) {
					return value == '1' ? '<font color="grey">否</font>' : '<font color="red">是</font>';
				}
			},
			{
				field: '',
				title: '操作',
				formatter: function(value, row, index) {
					var stype = method == '1' ? '1' : '0';
					return '<a href="javascript:addBlackList('+stype+',\''+row.user+'\')" class="btn btn-xs btn-default">拉黑</a>';
				}
			}
		],
	})
})
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
						layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
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
$(document).ready(function(){
	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
})
</script>