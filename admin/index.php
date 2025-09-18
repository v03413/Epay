<?php
include("../includes/common.php");
$title='支付管理中心';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<?php
if($conf['admin_pwd']==='123456'){
	$msg[]='<li class="list-group-item list-group-item-danger"><span class="btn-sm btn-danger"><i class="fa fa-info-circle"></i> 提示</span>&nbsp;请及时修改网站默认管理员密码！</li>';
}elseif(strlen($conf['admin_pwd'])<6 || is_numeric($conf['admin_pwd']) && strlen($conf['admin_pwd'])<=10 || $conf['admin_pwd']===$conf['kfqq'] || $conf['admin_user']===$conf['admin_pwd']){
	$msg[]='<li class="list-group-item list-group-item-danger"><span class="btn-sm btn-danger"><i class="fa fa-info-circle"></i> 提示</span>&nbsp;网站管理员密码过于简单，请及时修改密码！</li>';
}
?>
<div class="container" style="padding-top:70px;">
<div class="col-xs-12 col-lg-9 center-block" style="float: none;">
<div id="browser-notice"></div>

<div class="row">
    <div class="col-xs-12 col-lg-8">
      <div class="panel panel-info">
        <div class="panel-heading"><h3 class="panel-title" id="title">后台管理首页</h3></div>
          <ul class="list-group">
			<?php if($msg){foreach($msg as $x){echo $x;}}?>
            <li class="list-group-item"><span class="glyphicon glyphicon-stats"></span> <b>订单总数：</b><a id="count1" href="./order.php"></a></li>
			<li class="list-group-item"><span class="glyphicon glyphicon-tint"></span> <b>商户数量：</b><a id="count2" href="./ulist.php"></a></li>
			<li class="list-group-item"><span class="glyphicon glyphicon-tint"></span> <b>总计余额：</b><span id="usermoney"></span> 元（1小时更新一次）</li>
			<li class="list-group-item"><span class="glyphicon glyphicon-tint"></span> <b>结算总额：</b><span id="settlemoney"></span> 元（1小时更新一次）</li>
			<li class="list-group-item"><span class="glyphicon glyphicon-stats"></span> <b>今日订单成功率：</b><span id="success_rate"></span> %</li>
            <li class="list-group-item"><span class="glyphicon glyphicon-time"></span> <b>现在时间：</b> <?=$date?></li>
			</li>
          </ul>
      </div>
	</div>
	<div class="col-xs-12 col-lg-4">
      <div class="panel panel-default">
        <div class="panel-heading"><h3 class="panel-title" id="title">管理员信息</h3></div>
          <ul class="list-group text-center">
            <li class="list-group-item">
			<img src="<?php echo ($conf['kfqq'])?'//q2.qlogo.cn/headimg_dl?bs=qq&dst_uin='.$conf['kfqq'].'&src_uin='.$conf['kfqq'].'&fid='.$conf['kfqq'].'&spec=100&url_enc=0&referer=bu_interface&term_type=PC':'../assets/img/user.png'?>" alt="avatar" class="img-circle img-thumbnail"></br>
			<span class="text-muted"><strong>用户名：</strong><font color="blue"><?php echo $conf['admin_user']?></font></span><br/><span class="text-muted"><strong>用户权限：</strong><font color="orange">管理员</font></span></li>
			<li class="list-group-item"><a href="../" class="btn btn-xs btn-default">返回首页</a>&nbsp;<a href="./set.php?mod=account" class="btn btn-xs btn-info">修改密码</a>&nbsp;<a href="./login.php?logout" class="btn btn-xs btn-danger">退出登录</a>
			</li>
          </ul>
      </div>
	</div>
</div>
	  <div class="panel panel-success">
	    <div class="panel-heading"><h3 class="panel-title">支付方式收入统计（1小时更新一次）<span class="pull-right"><a href="javascript:getData(true)" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i></a></span></h3></div>
          <table class="table table-bordered table-striped">
		    <thead><tr id="paytype_head"><th>日期</th></thead>
            <tbody id="paytype_list">
			</tbody>
          </table>
      </div>
	  <div class="panel panel-warning">
	    <div class="panel-heading"><h3 class="panel-title">支付通道收入统计（1小时更新一次）<span class="pull-right"><a href="javascript:getData(true)" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i></a></span></h3></div>
		<div class="table-responsive">
          <table class="table table-bordered table-striped">
		    <thead><tr id="channel_head"><th>日期</th></thead>
            <tbody id="channel_list">
			</tbody>
          </table>
		</div>
      </div>
	  <div class="panel panel-warning">
	    <div class="panel-heading" style="background-color: #c09853;"><h3 class="panel-title">支付方式手续费利润（已扣除通道成本，1小时更新一次）<span class="pull-right"><a href="javascript:getData(true)" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i></a></span></h3></div>
          <table class="table table-bordered table-striped">
		    <thead><tr id="profit_paytype_head"><th>日期</th></thead>
            <tbody id="profit_paytype_list">
			</tbody>
          </table>
      </div>
    </div>
  </div>
<script>
$(document).ready(function(){
	getData();
});
function getData(getnew){
	getnew = getnew || false;
	$('#title').html('正在加载数据中...');
	$.ajax({
		type : "GET",
		url : "ajax.php?act=getcount"+(getnew?'&getnew=1':''),
		dataType : 'json',
		async: true,
		success : function(data) {
			$('#title').html('后台管理首页');
			$('#count1').html(data.count1);
			$('#count2').html(data.count2);
			$('#usermoney').html(data.usermoney);
			$('#settlemoney').html(data.settlemoney);
			$('#success_rate').html(data.success_rate);

			$("#paytype_head").html('<th>日期</th>');
			$("#paytype_list").empty();
			var paytype=new Array();
			$.each(data.paytype, function(k, v){
				paytype.push(k);
				$("#paytype_head").append('<th>'+v+'</th>');
			});
			$("#paytype_head").append('<th>总计</th>');
			var order = '';
			$.each(paytype, function(k, v){
				if(typeof data.order_today.paytype[v] != "undefined")order+='<td>'+data.order_today.paytype[v]+'</td>';
				else order+='<td>0</td>';
			});
			$("#paytype_list").append('<tr><td>今日</td>'+order+'<td>'+data.order_today.all+'</td></tr>');
			$.each(data.order, function(k, v){
				var order = '';
				$.each(paytype, function(key, value){
					if(typeof v.paytype[value] != "undefined")order+='<td>'+v.paytype[value]+'</td>';
					else order+='<td>0</td>';
				});
				$("#paytype_list").append('<tr><td>'+k+'</td>'+order+'<td>'+v.all+'</td></tr>');
			});

			$("#channel_head").html('<th>日期</th>');
			$("#channel_list").empty();
			var channel=new Array();
			$.each(data.channel, function(k, v){
				channel.push(k);
				$("#channel_head").append('<th>'+v+'</th>');
			});
			$("#channel_head").append('<th>总计</th>');
			var order = '';
			$.each(channel, function(k, v){
				if(typeof data.order_today.channel[v] != "undefined")order+='<td>'+data.order_today.channel[v]+'</td>';
				else order+='<td>0</td>';
			});
			$("#channel_list").append('<tr><td>今日</td>'+order+'<td>'+data.order_today.all+'</td></tr>');
			$.each(data.order, function(k, v){
				var order = '';
				$.each(channel, function(key, value){
					if(typeof v.channel[value] != "undefined")order+='<td>'+v.channel[value]+'</td>';
					else order+='<td>0</td>';
				});
				$("#channel_list").append('<tr><td>'+k+'</td>'+order+'<td>'+v.all+'</td></tr>');
			});

			$("#profit_paytype_head").html('<th>日期</th>');
			$("#profit_paytype_list").empty();
			var paytype=new Array();
			$.each(data.paytype, function(k, v){
				paytype.push(k);
				$("#profit_paytype_head").append('<th>'+v+'</th>');
			});
			$("#profit_paytype_head").append('<th>总计</th>');
			var order = '';
			$.each(paytype, function(k, v){
				if(typeof data.order_today.profit_paytype[v] != "undefined")order+='<td>'+data.order_today.profit_paytype[v]+'</td>';
				else order+='<td>0</td>';
			});
			$("#profit_paytype_list").append('<tr><td>今日</td>'+order+'<td>'+data.order_today.profit_all+'</td></tr>');
			$.each(data.order, function(k, v){
				var order = '';
				$.each(paytype, function(key, value){
					if(typeof v.profit_paytype[value] != "undefined")order+='<td>'+v.profit_paytype[value]+'</td>';
					else order+='<td>0</td>';
				});
				$("#profit_paytype_list").append('<tr><td>'+k+'</td>'+order+'<td>'+v.profit_all+'</td></tr>');
			});
		}
	});
}
</script>
<script>
function speedModeNotice(){
	var ua = window.navigator.userAgent;
	if(ua.indexOf('Windows NT')>-1 && ua.indexOf('Trident/')>-1){
		var html = "<div class=\"panel panel-default\"><div class=\"panel-body\">当前浏览器是兼容模式，为确保后台功能正常使用，请切换到<b style='color:#51b72f'>极速模式</b>！<br>操作方法：点击浏览器地址栏右侧的IE符号<b style='color:#51b72f;'><i class='fa fa-internet-explorer fa-fw'></i></b>→选择“<b style='color:#51b72f;'><i class='fa fa-flash fa-fw'></i></b><b style='color:#51b72f;'>极速模式</b>”</div></div>";
		$("#browser-notice").html(html)
	}
}
speedModeNotice();
</script>