<?php
/**
 * 风控记录
**/
include("../includes/common.php");
$title='风控记录';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="uid">商户号</option><option value="url">风控网址</option><option value="content">风控内容</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容">
  </div>
  <div class="form-group">
	<select name="type" class="form-control"><option value="-1">风控类型</option><option value="0">关键词屏蔽</option><option value="1">订单成功率</option><option value="2">连续通知失败</option><option value="3">订单投诉率</option></select>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新风控记录"><i class="fa fa-refresh"></i></a>
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
	const defaultPageSize = 30;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax_order.php?act=riskList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
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
					return '<b><a href="./ulist.php?column=uid&value='+value+'" target="_blank">'+value+'</a></b>';
				}
			},
			{
				field: 'type',
				title: '风控类型',
				formatter: function(value, row, index) {
					if(value == 1){
						return '订单成功率';
					}else if(value == 2){
						return '连续通知失败';
					}else if(value == 3){
						return '订单投诉率';
					}else{
						return '关键词屏蔽';
					}
				}
			},
			{
				field: 'content',
				title: '风控内容'
			},
			{
				field: 'url',
				title: '风控网址'
			},
			{
				field: 'date',
				title: '时间'
			}
		],
	})
})
</script>