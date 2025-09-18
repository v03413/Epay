<?php
$is_defend=true;
include("../includes/common.php");

if(isset($_GET['invite'])){
    $invite_code = trim($_GET['invite']);
    $uid = get_invite_uid($invite_code);
    if($uid && is_numeric($uid)){
        $_SESSION['invite_uid'] = intval($uid);
    }
}

if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

if(!$conf['reg_input_settle'] && (empty($userrow['account']) || empty($userrow['username']))){
	exit("<script language='javascript'>window.location.href='./completeinfo.php';</script>");
}

if($userrow['status']==0){
	$status = '<font color="red">已封禁</font>';
}elseif($userrow['pay']==0 && $userrow['settle']==0){
	$status = '<font color="red">关闭支付、结算</font>';
}elseif($userrow['pay']==0){
	$status = '<font color="red">关闭支付</font>';
}elseif($userrow['settle']==0){
	$status = '<font color="red">关闭结算</font>';
}elseif($conf['cert_force']==1 && $userrow['cert']==0){
	$status = '<a href="certificate.php"><font color="red">未实名认证</font></a>';
}elseif($userrow['pay']==2){
	$status = '<font color="orange">待审核</font>';
}else{
	$status = '<font color="green">正常</font>';
}
$title='用户中心';
include './head.php';
?>
<style>
.round {
    line-height: 53px;
    color: #7266ba;
    width: 58px;
    height: 58px;
    font-size: 26px;
    margin-left:15px;
    display: inline-block;
    font-weight: 400;
    border: 3px solid #f8f8fe;
    text-align: center;
    border-radius: 50%;
    background: #e3dff9;
}
</style>
<?php
$rs=$DB->query("SELECT * FROM pre_settle WHERE uid={$uid} AND status=1 ORDER BY id DESC LIMIT 9");
$max_settle=0;
$chart='';
$i=0;
while($row = $rs->fetch())
{
	if($row['money']>$max_settle)$max_settle=$row['money'];
	$chart.='['.$i++.','.$row['money'].'],';
}
$chart=substr($chart,0,-1);

$list = $DB->getAll("SELECT * FROM pre_anounce WHERE status=1 ORDER BY sort ASC");
?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">
		<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span>
						</button>
						<h4 class="modal-title">欢迎回来</h4>
					</div>
					<div class="modal-body">
<?php echo $conf['modal']?>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
					</div>
				</div>
			</div>
		</div>

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">用户中心</h1>
  <small class="text-muted">欢迎使用<?php echo $conf['sitename']?></small>
</div>
<div class="wrapper-md control">
<!-- stats -->
<?php
if($conf['cert_force']==1 && $userrow['cert']==0){
	echo '<div class="alert alert-danger"><span class="btn-sm btn-danger">重要</span>&nbsp;请完成实名认证，否则您的商户无法正常收款！ <a href="./certificate.php" class="btn btn-default btn-xs">立即实名认证</a></div>';
}
if($conf['verifytype']==1 && empty($userrow['phone'])){
	echo '<div class="alert alert-warning"><span class="btn-sm btn-warning">提示</span>&nbsp;您还没有绑定密保手机，请&nbsp;<a href="editinfo.php" class="btn btn-default btn-xs">尽快绑定</a></div>';
}elseif($conf['verifytype']==0 && empty($userrow['email'])){
	echo '<div class="alert alert-warning"><span class="btn-sm btn-warning">提示</span>&nbsp;您还没有绑定密保邮箱，请&nbsp;<a href="editinfo.php" class="btn btn-default btn-xs">尽快绑定</a></div>';
}
if(empty($userrow['pwd'])){
	echo '<div class="alert alert-warning"><span class="btn-sm btn-warning">提示</span>&nbsp;您还没有设置登录密码，请&nbsp;<a href="userinfo.php?mod=account" class="btn btn-default btn-xs">点此设置</a>，设置登录密码之后你就可以使用手机号/邮箱+密码登录</div>';
}
?>

          <div class="row row-sm text-center">
            <div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item">
			    <div class="top text-right w-full"><i class="fa fa-caret-down text-warning m-r-sm"></i></div>
			  <div class="row">
			  <div class="col-xs-3"><div class="round"><i class="fa fa-money fa-fw"></i></div></div>
			  <div class="col-xs-9"><div class="h1 text-primary-dk font-thin h1"><span class="text-muted text-md">¥</span><?php echo $userrow['money']?></div><span class="text-muted">商户当前余额</span></div>
			  </div>
			  </div>
            </div>
			<div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item">
			    <div class="top text-right w-full"><i class="fa fa-caret-down text-warning m-r-sm"></i></div>
			  <div class="row">
			  <div class="col-xs-3"><div class="round"><i class="fa fa-check-square-o fa-fw"></i></div></div>
			  <div class="col-xs-9"><div class="h1 text-dark-dk font-thin h1"><span class="text-muted text-md">¥</span><span id="settle_money"></span></div><span class="text-muted">已结算余额</span></div>
			  </div>
			  </div>
            </div>
			<div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item">
			    <div class="top text-right w-full"><i class="fa fa-caret-down text-warning m-r-sm"></i></div>
			  <div class="row">
			  <div class="col-xs-3"><div class="round"><i class="fa fa-area-chart fa-fw"></i></div></div>
			  <div class="col-xs-9"><div class="h1 text-success-dk font-thin h1"><span id="orders"></span><span class="text-muted text-md">个</span></div><span class="text-muted">订单总数</span></div>
			  </div>
			  </div>
            </div>
			<div class="col-xs-6 col-sm-3">
              <div class="panel padder-v item">
			    <div class="top text-right w-full"><i class="fa fa-caret-down text-warning m-r-sm"></i></div>
			  <div class="row">
			  <div class="col-xs-3"><div class="round"><i class="fa fa-cart-plus fa-fw"></i></div></div>
			  <div class="col-xs-9"><div class="h1 text-info-dk font-thin h1"><span id="orders_today"></span><span class="text-muted text-md">个</span></div><span class="text-muted">今日订单</span></div>
			  </div>
			  </div>
            </div>
        </div>
	      <div class="row">
        <div class="col-md-6">

		<div class="panel b-a">
            <div class="panel-heading bg-info dk no-border wrapper-lg">
              <a class="btn btn-sm btn-rounded btn-info pull-right m-r" href="./editinfo.php"><i class="fa fa-cog fa-fw"></i>&nbsp;修改资料</a>
              <a class="btn btn-sm btn-rounded btn-info m-l" href="./userinfo.php?mod=api"><i class="fa fa-lock fa-fw"></i>&nbsp;API信息</a>
            </div>
            <div class="text-center m-b clearfix">
              <div class="thumb-lg avatar m-t-n-xxl">
                <img src="<?php echo ($userrow['qq'])?'//q2.qlogo.cn/headimg_dl?bs=qq&dst_uin='.$userrow['qq'].'&src_uin='.$userrow['qq'].'&fid='.$userrow['qq'].'&spec=100&url_enc=0&referer=bu_interface&term_type=PC':'assets/img/user.png'?>" alt="..." class="b b-3x b-white">
              </div>
			  <div class="h2 font-thin m-t-sm">欢迎您，<?php echo $userrow['username']?></div>
			  <div class="h4 font-thin m-t-sm">商户状态：<?php echo $status;?></div>
            </div>
            <div class="hbox text-center b-t b-light bg-light">          
              <a class="col padder-v text-muted b-r b-light">
                <div class="h3"><span id="order_today_all"></span></div>
                <i class="fa fa-plus fa-fw"></i><span>今日收入</span>
              </a>
              <a class="col padder-v text-muted">
                <div class="h3"><span id="order_lastday_all"></span></div>
                <i class="fa fa-plus-circle fa-fw"></i><span>昨日收入</span>
              </a>
            </div>
			<?php if($conf['user_transfer']==1){?>
			<div class="hbox text-center b-t b-light bg-light">          
              <a class="col padder-v text-muted b-r b-light">
                <div class="h3"><span id="transfer_today_all"></span></div>
                <i class="fa fa-send fa-fw"></i><span>今日支出</span>
              </a>
              <a class="col padder-v text-muted">
                <div class="h3"><span id="transfer_lastday_all"></span></div>
                <i class="fa fa-send-o fa-fw"></i><span>昨日支出</span>
              </a>
            </div>
			<?php }?>
          </div>

		  <div class="panel panel-default text-center">
		<div class="panel-heading font-bold">
			收入统计与通道费率
		</div>
		<div class="table-responsive">
		<table class="table table-striped">
		<thead><tr id="paytypes"></tr></thead>
		<tbody><tr id="order_today"></tr><tr id="order_lastday"></tr><tr id="success_rate"></tr><tr id="payrates"></tr></tbody>
		</table>
		</div>
		</div>

		  </div>
		<div class="col-md-6">

		  <div class="panel panel-default">
		<div class="panel-heading font-bold text-center">
			公告通知
		</div>
		<div class="list-group">
<?php foreach($list as $row){?>
			<a class="list-group-item"><em class="fa fa-fw fa-volume-up"></em><font color="<?php echo $row['color']?$row['color']:null?>"><?php echo $row['content']?></font><span class="text-xs text-muted">&nbsp;-<?php echo $row['addtime']?></span></a>
<?php }?>
		</div>
		</div>
		
          <div class="panel wrapper">
            <label class="i-switch bg-warning pull-right" ng-init="showSpline=true">
              <input type="checkbox" ng-model="showSpline">
              <i></i>
            </label>
            <h4 class="font-thin m-t-none m-b text-muted">结算统计表</h4>
            <div ui-jq="plot" ui-refresh="showSpline" ui-options="
              [
                { data: [ <?php echo $chart?> ], label:'结算金额', points: { show: true, radius: 1}, splines: { show: true, tension: 0.4, lineWidth: 1, fill: 0.8 } }
              ], 
              {
                colors: ['#23b7e5', '#7266ba'],
                series: { shadowSize: 3 },
                xaxis:{ font: { color: '#a1a7ac' } },
                yaxis:{ font: { color: '#a1a7ac' }, max:<?php echo ($max_settle+10)?> },
                grid: { hoverable: true, clickable: true, borderWidth: 0, color: '#dce5ec' },
                tooltip: true,
                tooltipOpts: { content: '结算金额¥%y',  defaultTheme: false, shifts: { x: 10, y: -25 } }
              }
            " style="height:246px" >
            </div>
          </div>
        </div>
      </div>
      <!-- / stats -->
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script>
$(document).ready(function(){
	$.ajax({
		type : "GET",
		url : "ajax2.php?act=getcount",
		dataType : 'json',
		async: true,
		success : function(data) {
			$('#orders').html(data.orders);
			$('#orders_today').html(data.orders_today);
			$('#settle_money').html(data.settle_money);
			$('#order_today_all').html(data.order_today_all);
			$('#order_lastday_all').html(data.order_lastday_all);
			$('#transfer_today_all').html(data.transfer_today_all);
			$('#transfer_lastday_all').html(data.transfer_lastday_all);
			$.each(data.channels, function (i, item) {
				$('#paytypes').append('<th style="text-align:center;"><img src="/assets/icon/'+item.name+'.ico" width="18px">&nbsp;'+item.showname+'</th>');
			});
			$.each(data.channels, function (i, item) {
				$('#order_today').append('<td>今日：'+item.order_today+' 元</td>');
				$('#order_lastday').append('<td>昨日：'+item.order_lastday+' 元</td>');
				$('#success_rate').append('<td>成功率：'+item.success_rate+' %</td>');
				$('#payrates').append('<td>费率：'+item.rate+' %</td>');
			});
		}
	});
	<?php if(!empty($conf['modal'])){?>
	$('#myModal').modal('show');
	<?php }?>
});
</script>