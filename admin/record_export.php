<?php
include("../includes/common.php");
$title='导出资金明细';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$select = '<option value="">全部用户组</option>';
$rs = $DB->getAll("SELECT * FROM pre_group");
foreach($rs as $row){
	$select .= '<option value="'.$row['gid'].'">'.$row['name'].'</option>';
}
unset($rs);
?>
<link href="../assets/css/datepicker.css" rel="stylesheet">
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">导出资金明细</h3></div>
<div class="panel-body">
		<form action="" method="POST" onsubmit="return exportRecord()" role="form">
			<div class="form-group">
				<div class="input-group input-daterange"><div class="input-group-addon">时间范围</div>
				<input type="text" id="starttime" name="starttime" class="form-control" placeholder="开始日期*" autocomplete="off" required>
				<span class="input-group-addon"><i class="fa fa-chevron-right"></i></span>
				<input type="text" id="endtime" name="endtime" class="form-control" placeholder="结束日期*" autocomplete="off" required>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">商户号</div>
				<input type="text" name="uid" value="" class="form-control" placeholder="留空为全部商户"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">操作类型</div>
				<input type="text" name="type" value="" class="form-control" placeholder="留空为全部类型"/>
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
function exportRecord(){
	var starttime = $("input[name='starttime']").val();
	var endtime = $("input[name='endtime']").val();
	var uid = $("input[name='uid']").val();
	var type = $("input[name='type']").val();
	if(starttime == '' || endtime == ''){
		layer.alert('时间范围是必填的！'); return false;
	}
	window.location.href='./download.php?act=record&starttime='+starttime+'&endtime='+endtime+'&uid='+uid+'&type='+type;
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