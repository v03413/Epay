<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='邀请返现';
include './head.php';
?>
<?php
if(!$conf['invite_open'])exit('未开启邀请返现功能');

$invite_url = $siteurl.'?invite='.urlencode(get_invite_code($uid));
if($conf['homepage']>0){
	$invite_url = $siteurl.'user/?invite='.urlencode(get_invite_code($uid));
}
$money_get = round(100*$conf['invite_rate']/100,2);
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">邀请返现</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-volume-up"></i>&nbsp;邀请返现介绍
		</div>
		<div class="panel-body">
		<p>● 用户通过下方推广链接注册成为商户后，每支付一笔订单，都会给你订单金额固定比例的分成。（分成金额最多不会超过订单手续费）</p>
		<p>● 当前邀请返现比例：<span style="font-weight:700;color:#f05050;"><?php echo $conf['invite_rate']?>%</span>，即用户支付100元的<?php echo $conf['invite_order_type']==1?'手续费':'订单'?>，你会得到<?php echo $money_get?>元奖励</p>
		</div>
	</div>
	<div class="row">
	<div class="col-md-6">
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-share-alt"></i>&nbsp;推广链接
		</div>
		<div class="panel-body">
		<h4 class="text-center">属于您的唯一推广链接</h4>
			<div class="form-group">
				<input class="form-control" type="text" id="invite_url" value="<?php echo $invite_url?>" readonly>
			</div>
			<p class="text-center"><a href="javascript:;" class="btn btn-success copy-btn" data-clipboard-text="<?php echo $invite_url?>" title="点击复制">点击复制</a></p>
		</div>
	</div>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			<i class="fa fa-area-chart"></i>&nbsp;邀请统计
		</div>
		<div class="list-group no-radius alt">
			<a class="list-group-item">
			<span class="badge bg-primary" id="invite_users">0</span>
			<i class="fa fa-users fa-fw text-muted"></i> 
			已邀请用户数量
			</a>
			<a class="list-group-item">
			<span class="badge bg-success" id="income_today">0</span>
			<i class="fa fa-plus fa-fw text-muted"></i> 
			今日邀请收入
			</a>
			<a class="list-group-item">
			<span class="badge bg-info" id="income_lastday">0</span>
			<i class="fa fa-plus-circle fa-fw text-muted"></i> 
			昨日邀请收入
			</a>
		</div>
	</div>
	</div>
	<div class="col-md-6">
	<div class="tab-container">
		<ul class="nav nav-tabs">
			<li class="active"><a href="" >已邀请的用户</a></li>
			<li class=""><a href="./record.php?type=1&kw=邀请返现">邀请返现记录</a></li>
		</ul>
		<div class="tab-content">
      		<div class="tab-pane active">
				<table id="listTable">
	  			</table>
			</div>
		</div>
	</div>
	</div>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
$(document).ready(function(){
	var clipboard = new Clipboard('.copy-btn');
	clipboard.on('success', function (e) {
		layer.msg('复制成功！', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
	});

	$.ajax({
		type : "GET",
		url : "ajax2.php?act=inviteStat",
		dataType : 'json',
		async: true,
		success : function(data) {
			$('#invite_users').html(data.invite_users);
			$('#income_today').html(data.income_today);
			$('#income_lastday').html(data.income_lastday);
		}
	});

	updateToolbar();
	const defaultPageSize = 10;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax2.php?act=inviteList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		showColumns: false,
		showFullscreen: false,
		columns: [
			{
				field: 'uid',
				title: '商户ID'
			},
			{
				field: 'addtime',
				title: '注册时间'
			},
		],
	})
});
</script>