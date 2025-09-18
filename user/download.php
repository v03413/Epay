<?php
include("../includes/common.php");

if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

function display_status($status){
	if($status==1){
		return '已支付';
	}elseif($status==2){
		return '已退款';
	}elseif($status==3){
		return '已冻结';
	}else{
		return '未支付';
	}
}

function display_psstatus($status){
	if($status==1){
		return '已提交';
	}elseif($status==2){
		return '分账成功';
	}elseif($status==3){
		return '分账失败';
	}elseif($status==4){
		return '已取消';
	}else{
		return '待分账';
	}
}

switch($act){

case 'order':
$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
}
unset($rs);

$sql=" A.`uid`='$uid'";
if(isset($_GET['paytype']) && !empty($_GET['paytype'])) {
	$type = intval($_GET['paytype']);
	$sql.=" AND A.`type`='$type'";
}elseif(isset($_GET['channel']) && !empty($_GET['channel'])) {
	$channel = intval($_GET['channel']);
	$sql.=" AND A.`channel`='$channel'";
}elseif(isset($_GET['subchannel']) && !empty($_GET['subchannel'])) {
	$subchannel = trim($_GET['subchannel']);
	$subchannel = explode('|', $subchannel);
	$subchannel = array_map('intval', $subchannel);
	$sql.=" AND A.`subchannel` IN (".implode(",", $subchannel).")";
}
if(isset($_GET['dstatus']) && $_GET['dstatus']>-1) {
	$dstatus = intval($_GET['dstatus']);
	$sql.=" AND A.status='{$dstatus}'";
}
if(!empty($_GET['starttime']) || !empty($_GET['endtime'])){
	if(!empty($_GET['starttime'])){
		$starttime = daddslashes($_GET['starttime']);
		$sql.=" AND A.addtime>='{$starttime} 00:00:00'";
	}
	if(!empty($_GET['endtime'])){
		$endtime = daddslashes($_GET['endtime']);
		$sql.=" AND A.addtime<='{$endtime} 23:59:59'";
	}
}
if(isset($_GET['kw']) && !empty($_GET['kw'])) {
	$kw=daddslashes($_GET['kw']);
	if($_GET['type']==1){
		$sql.=" AND A.`trade_no`='{$kw}'";
	}elseif($_GET['type']==2){
		$sql.=" AND A.`out_trade_no`='{$kw}'";
	}elseif($_GET['type']==3){
		$sql.=" AND A.`name` like '%{$kw}%'";
	}elseif($_GET['type']==4){
		$sql.=" AND A.`money`='{$kw}'";
	}elseif($_GET['type']==5){
		$sql.=" AND A.`realmoney`='{$kw}'";
	}elseif($_GET['type']==6){
		$sql.=" AND A.`domain`='{$kw}'";
	}elseif($_GET['type']==7){
		$sql.=" AND A.`ip`='{$kw}'";
	}elseif($_GET['type']==8){
		$sql.=" AND A.`buyer`='{$kw}'";
	}elseif($_GET['type']==9){
		$sql.=" AND A.`api_trade_no`='{$kw}'";
	}elseif($_GET['type']==10){
		$sql.=" AND A.`bill_trade_no`='{$kw}'";
	}elseif($_GET['type']==11){
		$sql.=" AND A.`param`='{$kw}'";
	}
}

$file="系统订单号,商户订单号,接口订单号,商户ID,网站域名,商品名称,订单金额,实际支付,商户分成,支付方式,支付账号,支付IP,创建时间,完成时间,支付状态,已退款金额,退款时间\r\n";

$rs = $DB->query("SELECT A.*,B.plugin,C.apply_id submchid FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id LEFT JOIN pre_subchannel C ON A.subchannel=C.id WHERE{$sql} order by trade_no desc limit 100000");
while($row = $rs->fetch()){
	if($row['status']==2){
		$row['refundtime'] = $DB->findColumn('refundorder', 'addtime', ['trade_no'=>$row['trade_no']], 'refund_no DESC');
	}
	$file.='="'.$row['trade_no'].'",="'.$row['out_trade_no'].'",="'.$row['api_trade_no'].'",'.$row['submchid'].','.$row['domain'].','.$row['name'].','.$row['money'].','.$row['realmoney'].','.$row['getmoney'].','.$paytype[$row['type']].','.$row['buyer'].','.$row['ip'].','.$row['addtime'].','.$row['endtime'].','.display_status($row['status']).','.($row['status']==2?$row['refundmoney']:'').','.$row['refundtime']."\r\n";
}

$file = hex2bin('efbbbf').$file;
$file_name='order_'.$starttime.'_'.$endtime.'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
break;

case 'complain':
$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
}
unset($rs);

$sql=" A.uid=$uid";
if(isset($_GET['paytype']) && !empty($_GET['paytype'])) {
	$paytypen = intval($_GET['paytype']);
	$sql.=" AND A.`paytype`='$paytypen'";
}elseif(isset($_GET['channel']) && !empty($_GET['channel'])) {
	$channel = intval($_GET['channel']);
	$sql.=" AND A.`channel`='$channel'";
}
if(isset($_GET['dstatus']) && $_GET['dstatus']>-1) {
	$dstatus = intval($_GET['dstatus']);
	$sql.=" AND A.`status`={$dstatus}";
}
if(!empty($_GET['starttime']) || !empty($_GET['endtime'])){
	if(!empty($_GET['starttime'])){
		$starttime = daddslashes($_GET['starttime']);
		$sql.=" AND A.addtime>='{$starttime} 00:00:00'";
	}
	if(!empty($_GET['endtime'])){
		$endtime = daddslashes($_GET['endtime']);
		$sql.=" AND A.addtime<='{$endtime} 23:59:59'";
	}
}
if(isset($_GET['kw']) && !empty($_GET['kw'])) {
	$kw=daddslashes($_GET['kw']);
	if($_GET['type']==1){
		$sql.=" AND A.`trade_no`='{$kw}'";
	}elseif($_GET['type']==2){
		$sql.=" AND A.`thirdid`='{$kw}'";
	}elseif($_GET['type']==3){
		$sql.=" AND A.`type`='{$kw}'";
	}elseif($_GET['type']==4){
		$sql.=" AND A.`title` like '%{$kw}%'";
	}elseif($_GET['type']==5){
		$sql.=" AND A.`content` like '%{$kw}%'";
	}elseif($_GET['type']==6){
		$sql.=" AND A.`phone`='{$kw}'";
	}
}

$file="ID,支付方式,商户ID,关联订单号,商品名称,订单金额,问题类型,投诉原因,投诉详情,创建时间,最后更新时间,状态\r\n";

$rs = $DB->query("SELECT A.*,B.money,B.name ordername,C.apply_id submchid FROM pre_complain A LEFT JOIN pre_order B ON A.trade_no=B.trade_no LEFT JOIN pre_subchannel C ON A.subchannel=C.id WHERE{$sql} order by A.addtime desc limit 100000");
while($row = $rs->fetch()){
	$file.=''.$row['id'].','.$paytype[$row['paytype']].','.$row['submchid'].',="'.$row['trade_no'].'",'.$row['ordername'].','.$row['money'].','.$row['type'].','.$row['title'].','.str_replace(["\r\n", "\n"]," ",$row['content']).','.$row['addtime'].','.$row['edittime'].','.['0'=>'待处理','1'=>'处理中','2'=>'处理完成'][$row['status']]."\r\n";
}

$file = hex2bin('efbbbf').$file;
$file_name='complain_'.time().'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
break;

case 'psorder':
$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
}
unset($rs);

$sql=" rid in (select id from pre_psreceiver where uid='$uid' and subchannel>0)";
if(isset($_GET['rid']) && !empty($_GET['rid'])) {
	$rid = intval($_GET['rid']);
	$sql.=" AND A.`rid`='$rid'";
}
if(isset($_GET['dstatus']) && $_GET['dstatus']>-1) {
	$dstatus = intval($_GET['dstatus']);
	$sql.=" AND A.`status`={$dstatus}";
}
if(!empty($_GET['starttime']) || !empty($_GET['endtime'])){
	if(!empty($_GET['starttime'])){
		$starttime = daddslashes($_GET['starttime']);
		$sql.=" AND A.addtime>='{$starttime} 00:00:00'";
	}
	if(!empty($_GET['endtime'])){
		$endtime = daddslashes($_GET['endtime']);
		$sql.=" AND A.addtime<='{$endtime} 23:59:59'";
	}
}
if(isset($_GET['kw']) && !empty($_GET['kw'])) {
	$kw=daddslashes($_GET['kw']);
	if($_GET['type']==1){
		$sql.=" AND A.`trade_no`='{$kw}'";
	}elseif($_GET['type']==2){
		$sql.=" AND A.`api_trade_no`='{$kw}'";
	}elseif($_GET['type']==3){
		$sql.=" AND A.`money` like '%{$kw}%'";
	}
}

$file="系统订单号,分账规则,支付方式,订单金额,分账金额,时间,分账状态\r\n";

$rs = $DB->query("SELECT A.*,C.id channelid,C.name channelname,C.type,D.realmoney ordermoney FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_channel C ON B.channel=C.id LEFT JOIN pre_order D ON D.trade_no=A.trade_no WHERE{$sql} order by A.id desc limit 100000");
while($row = $rs->fetch()){
	$file.='="'.$row['trade_no'].'",'.$row['rid'].','.$paytype[$row['type']].','.$row['ordermoney'].','.$row['money'].','.$row['addtime'].','.display_psstatus($row['status'])."\r\n";
}

$file = hex2bin('efbbbf').$file;
$file_name='psorder_'.$starttime.'_'.$endtime.'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
break;

case 'wximg':
	if(!checkRefererHost())exit();
	$channelid = intval($_GET['channel']);
	$subchannelid = intval($_GET['subchannel']);
	$media_id = $_GET['mediaid'];
	$channel = $subchannelid ? \lib\Channel::getSub($subchannelid) : \lib\Channel::get($channelid);
	$model = \lib\Complain\CommUtil::getModel($channel);
	$image = $model->getImage($media_id);
	if($image !== false){
		$seconds_to_cache = 3600*24*7;
		header("Cache-Control: max-age=$seconds_to_cache");
		header("Content-Type: image/jpeg");
		echo $image;
	}
break;

default:
	exit('No Act');
break;
}