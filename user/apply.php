<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='申请提现';
include './head.php';
?>
<?php

function display_type($type){
	if($type==1)
		return '支付宝';
	elseif($type==2)
		return '微信';
	elseif($type==3)
		return 'QQ钱包';
	elseif($type==4)
		return '银行卡';
	else
		return 1;
}

function convert_type($type){
	if($type==1)
		return 'alipay';
	elseif($type==2)
		return 'wxpay';
	elseif($type==3)
		return 'qqpay';
	elseif($type==4)
		return 'bank';
	else
		return null;
}

if($conf['settle_open']==0||$conf['settle_open']==1)exit('未开启手动申请提现');

if($conf['settle_type']==1){
	$today=date("Y-m-d").' 00:00:00';
	$order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$uid} and status=1 and endtime>='$today'");
	if(!$order_today) $order_today = 0;
	$enable_money=round($userrow['money']-$order_today,2);
	if($enable_money<0)$enable_money=0;
}else{
	$enable_money=$userrow['money'];
}
if($userrow['remain_money'] > 0 && !strpos($userrow['remain_money'], '%') && $enable_money > 0){
	$enable_money = round($enable_money - $userrow['remain_money'], 2);
	if($enable_money<0)$enable_money=0;
}

if(isset($_GET['act']) && $_GET['act']=='do'){
	if($_POST['submit']=='申请提现'){
		if(!checkRefererHost())exit();
		$money=daddslashes(strip_tags($_POST['money']));
		if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)exit("<script language='javascript'>alert('提现金额输入不规范');history.go(-1);</script>");
		if($enable_money<$conf['settle_money']){
			exit("<script language='javascript'>alert('满{$conf['settle_money']}元才可以提现！');history.go(-1);</script>");
		}
		if($money>$enable_money){
			exit("<script language='javascript'>alert('所输入的提现金额大于你所拥有的余额！');history.go(-1);</script>");
		}
		if($money<$conf['settle_money']){
			exit("<script language='javascript'>alert('最低提现金额为{$conf['settle_money']}元');history.go(-1);</script>");
		}
		if($userrow['settle']==0){
			exit("<script language='javascript'>alert('您的商户出现异常，无法提现');history.go(-1);</script>");
		}
		if($conf['settle_maxlimit']>0){
			$a_count = $DB->getColumn('SELECT count(*) FROM pre_settle WHERE uid=:uid AND addtime>=:addtime', [':uid'=>$uid, ':addtime'=>date('Y-m-d').' 00:00:00']);
			if($a_count >= $conf['settle_maxlimit']){
				exit("<script language='javascript'>alert('您今天手动申请提现次数已达到上限');history.go(-1);</script>");
			}
		}
		if($conf['settle_rate']>0){
			$fee=round($money*$conf['settle_rate']/100,2);
			if(!empty($conf['settle_fee_min']) && $fee<$conf['settle_fee_min'])$fee=$conf['settle_fee_min'];
			if(!empty($conf['settle_fee_max']) && $fee>$conf['settle_fee_max'])$fee=$conf['settle_fee_max'];
			$realmoney=round($money-$fee, 2);
		}else{
			$realmoney=round($money, 2);
		}
		$data = ['uid'=>$uid, 'type'=>$userrow['settle_id'], 'account'=>$userrow['account'], 'username'=>$userrow['username'], 'money'=>$money, 'realmoney'=>$realmoney, 'addtime'=>'NOW()', 'status'=>0];
		if($DB->insert('settle', $data)){
			$settleid=$DB->lastInsertId();
			changeUserMoney($uid, $money, false, '手动提现');
			if($conf['settle_transfer']==1 && $conf['settle_transfermax']>0 && $money>$conf['settle_transfermax']) $conf['settle_transfer']=0;
			$app = convert_type($userrow['settle_id']);
			$channelid = $conf['transfer_'.$app];
			if($conf['settle_transfer']==1 && $channelid > 0){
				$out_biz_no = date("YmdHis").rand(11111,99999);
				$channel = \lib\Channel::get($channelid);
				$result = \lib\Transfer::submit($app, $channel, $out_biz_no, $userrow['account'], $userrow['username'], $realmoney);
				if($result['code']==0){
					$update = ['status'=>1, 'endtime'=>'NOW()', 'transfer_no'=>$out_biz_no, 'transfer_channel'=>$channelid, 'transfer_status'=>1, 'transfer_result'=>$result["orderid"], 'transfer_date'=>$result["paydate"]];
					if(isset($result['wxpackage'])) $update['transfer_ext'] = $result['wxpackage'];
					$DB->update('settle', $update, ['id'=>$settleid]);
					if($result['status'] == 1){
						$msg = '提现成功，资金已到账！';
					}elseif(isset($result['wxpackage'])){
						if(checkwechat()){
							$jumpurl = $siteurl.'paypage/wxtrans.php?id='.$out_biz_no.'&type=transfer';
							exit("<script language='javascript'>window.location.href='{$jumpurl}';</script>");
						}
						$msg = '提现成功！请在结算记录页面扫描二维码确认收款，1天内未确认，将退还给商家。';
					}else{
						$msg = '提现成功，资金稍后到账！';
					}
					exit("<script language='javascript'>alert('$msg');window.location.href='./settle.php';</script>");
				}else{
					$message='转账失败 '.$result['msg'];
					$DB->update('settle', ['status'=>3, 'result'=>$result["msg"], 'transfer_status'=>2, 'transfer_result'=>$message], ['id'=>$settleid]);
					\lib\MsgNotice::send('apply', 0, ['uid'=>$uid, 'money'=>$money, 'realmoney'=>$realmoney, 'type'=>display_type($userrow['settle_id']), 'account'=>$userrow['account'], 'username'=>$userrow['username']]);
					exit("<script language='javascript'>alert('申请提现成功，但转账失败，请联系客服处理！');window.location.href='./settle.php';</script>");
				}
			}else{
				\lib\MsgNotice::send('apply', 0, ['uid'=>$uid, 'money'=>$money, 'realmoney'=>$realmoney, 'type'=>display_type($userrow['settle_id']), 'account'=>$userrow['account'], 'username'=>$userrow['username']]);
			}
		}
		exit("<script language='javascript'>alert('申请提现成功！');window.location.href='./settle.php';</script>");
	}
}


?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">申请提现</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			申请提现
		</div>
		<div class="panel-body">
			<form class="form-horizontal devform" action="./apply.php?act=do" method="post">
				<div class="form-group">
					<label class="col-sm-2 control-label">提现方式</label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" value="<?php echo display_type($userrow['settle_id'])?>" disabled><a href="./editinfo.php" class="input-group-addon">修改收款账号</a></div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">提现账号</label>
					<div class="col-sm-9">
						<input class="form-control" type="text" value="<?php echo $userrow['account']?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">你的姓名</label>
					<div class="col-sm-9">
						<input class="form-control" type="text" value="<?php echo $userrow['username']?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">当前余额</label>
					<div class="col-sm-9">
						<input class="form-control" type="text" value="<?php echo $userrow['money']?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">可提现余额</label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="tmoney" value="<?php echo $enable_money?>" disabled>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">申请提现余额</label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" name="money" value="" required><a href="javascript:inputMoney()" class="input-group-addon">全部</a></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="submit" name="submit" value="申请提现" class="btn btn-primary form-control"/><br/>
				 </div>
			</form>
			<footer class="panel-footer">
				<div class="col-sm-offset-2 col-sm-6"><br/>
				<h4><span class="glyphicon glyphicon-info-sign"></span>注意事项</h4>
					当前最低提现金额为<b><?php echo $conf['settle_money']?></b>元<br/>
					当前手动提现模式是：<?php echo $conf['settle_type']==1?'<b>D+1</b>，可提现余额为截止到前一天你的收入':'<b>D+0</b>，可提现余额为截止到现在你的收入';?><br/>
					<?php echo $conf['settle_transfer']==1?'申请提现后，你的款项将立刻下发到指定账户内。':'申请提现后，你的款项将在1天内下发到指定账户内。';?>
				</div>
			</footer>
		</div>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
<script>
function inputMoney(){
	$("input[name='money']").val($("input[name='tmoney']").val());
}
</script>