<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){

case 'receiverList':
	$sql = " 1=1";
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$value=daddslashes($_POST['value']);
		if($_POST['column'] == 'info'){
			$sql .= " AND (A.`info` LIKE '%{$value}%' OR A.`account` LIKE '%{$value}%')";
		}else{
			$sql .= " AND A.`{$_POST['column']}`='{$value}'";
		}
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_psreceiver A WHERE{$sql}");
	$list = $DB->getAll("SELECT A.*,B.name channelname,C.name subchannelname,C.apply_id FROM pre_psreceiver A LEFT JOIN pre_channel B ON A.channel=B.id LEFT JOIN pre_subchannel C ON A.subchannel=C.id WHERE{$sql} order by A.id desc limit $offset,$limit");
	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;
case 'orderList':
	$paytype = [];
	$paytypes = [];
	$rs = $DB->getAll("SELECT * FROM pre_type");
	foreach($rs as $row){
		$paytype[$row['id']] = $row['showname'];
		$paytypes[$row['id']] = $row['name'];
	}
	unset($rs);

	$sql=" 1=1";
	if(isset($_POST['rid']) && !empty($_POST['rid'])) {
		$rid = intval($_POST['rid']);
		$sql.=" AND A.`rid`='$rid'";
	}
	if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
		$dstatus = intval($_POST['dstatus']);
		$sql.=" AND A.`status`={$dstatus}";
	}
	if(!empty($_POST['starttime']) || !empty($_POST['endtime'])){
		if(!empty($_POST['starttime'])){
			$starttime = daddslashes($_POST['starttime']);
			$sql.=" AND A.addtime>='{$starttime} 00:00:00'";
		}
		if(!empty($_POST['endtime'])){
			$endtime = daddslashes($_POST['endtime']);
			$sql.=" AND A.addtime<='{$endtime} 23:59:59'";
		}
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$sql.=" AND A.`{$_POST['column']}`='{$_POST['value']}'";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_channel C ON B.channel=C.id WHERE{$sql}");
	$list = $DB->getAll("SELECT A.*,C.id channelid,C.name channelname,C.type,D.realmoney ordermoney FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_channel C ON B.channel=C.id LEFT JOIN pre_order D ON D.trade_no=A.trade_no WHERE{$sql} order by A.id desc limit $offset,$limit");
	$list2 = [];
	foreach($list as $row){
		$row['typename'] = $paytypes[$row['type']];
		$row['typeshowname'] = $paytype[$row['type']];
		$list2[] = $row;
	}

	exit(json_encode(['total'=>$total, 'rows'=>$list2]));
break;

case 'get_receiver':
	$id=intval($_GET['id']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	$row['info'] = !empty($row['info']) ? json_decode($row['info'], true) : [['account'=>$row['account'], 'name'=>$row['name'], 'rate'=>$row['rate']]];
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	exit(json_encode(['code'=>0, 'data'=>$row]));
break;

case 'add_receiver':
	$data = [
		'channel' => intval($_POST['channel']),
		'uid' => !empty($_POST['uid'])?intval($_POST['uid']):null,
		'subchannel' => !empty($_POST['subchannel']) ? intval($_POST['subchannel']) : null,
		'info' => trim($_POST['info']),
		'minmoney' => trim($_POST['minmoney']),
		'status' => 0,
		'addtime' => 'NOW()'
	];
	if(!$data['channel'] || !$data['info'])exit('{"code":-1,"msg":"必填项不能为空"}');
	if(!empty($data['uid']) && !$DB->find('user', 'uid', ['uid'=>$data['uid']]))exit('{"code":-1,"msg":"商户ID不存在"}');
	if(!\lib\Channel::get($data['channel']))exit('{"code":-1,"msg":"支付通道不存在"}');
	if(!strpos($data['rate'], '|') && $data['rate'] > 100) exit('{"code":-1,"msg":"分账比例不能大于100"}');
	if($data['uid'] > 0 && $data['subchannel'] > 0){
		$sql = "`uid`='{$data['uid']}' AND `subchannel`='{$data['subchannel']}'";
	}elseif($data['uid'] > 0){
		$sql = "`uid`='{$data['uid']}'";
	}else{
		$sql = "`uid` IS NULL";
	}
	$rows = $DB->getRow("SELECT * FROM `pre_psreceiver` WHERE `channel`='{$data['channel']}' AND {$sql}");
	if($rows)exit('{"code":-1,"msg":"该支付通道&UID已存在分账规则"}');
	if($DB->insert('psreceiver', $data)){
		exit('{"code":0,"msg":"新增分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"新增分账规则失败['.$DB->error().']"}');
	}
break;

case 'edit_receiver':
	$id=intval($_POST['id']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	$data = [
		'channel' => intval($_POST['channel']),
		'uid' => !empty($_POST['uid'])?intval($_POST['uid']):null,
		'subchannel' => !empty($_POST['subchannel']) ? intval($_POST['subchannel']) : null,
		'info' => trim($_POST['info']),
		'minmoney' => trim($_POST['minmoney']),
	];
	if(!$data['channel'] || !$data['info'])exit('{"code":-1,"msg":"必填项不能为空"}');
	if(!empty($data['uid']) && !$DB->find('user', 'uid', ['uid'=>$data['uid']]))exit('{"code":-1,"msg":"商户ID不存在"}');
	if(!\lib\Channel::get($data['channel']))exit('{"code":-1,"msg":"支付通道不存在"}');
	if(!strpos($data['rate'], '|') && $data['rate'] > 100) exit('{"code":-1,"msg":"分账比例不能大于100"}');
	if($data['uid'] > 0 && $data['subchannel'] > 0){
		$sql = "`uid`='{$data['uid']}' AND `subchannel`='{$data['subchannel']}'";
	}elseif($data['uid'] > 0){
		$sql = "`uid`='{$data['uid']}'";
	}else{
		$sql = "`uid` IS NULL";
	}
	$rows = $DB->getRow("SELECT * FROM `pre_psreceiver` WHERE `channel`='{$data['channel']}' AND {$sql} AND id!='$id'");
	if($rows)exit('{"code":-1,"msg":"该支付通道&UID已存在分账规则"}');
	if($row['status']==1 && $data['channel'] != $row['channel']){
		exit('{"code":-1,"msg":"请先将状态改为已关闭再切换通道"}');
	}
	if($row['status']==1 && $data['info']!=$row['info']){
		$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
		if($channel){
			$new_info = json_decode($data['info'], true);
			$old_info = !empty($row['info']) ? json_decode($row['info'], true) : [['account'=>$row['account'], 'name'=>$row['name'], 'rate'=>$row['rate']]];
			
			$model = \lib\ProfitSharing\CommUtil::getModel($channel);
			foreach($new_info as $item){
				if(!array_filter($old_info, function($v) use ($item) {
					return $v['account'] == $item['account'];
				})){
					$result = $model->addReceiver($item['account'], $item['name']);
					if($result['code'] != 0) exit(json_encode($result));
				}
			}
		}
	}
	if($DB->update('psreceiver', $data, ['id'=>$id])!==false){
		exit('{"code":0,"msg":"修改分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"修改分账规则失败['.$DB->error().']"}');
	}
break;

case 'set_receiver':
	$id=intval($_POST['id']);
	$status=intval($_POST['status']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$row['info'] = !empty($row['info']) ? json_decode($row['info'], true) : [['account'=>$row['account'], 'name'=>$row['name'], 'rate'=>$row['rate']]];
	foreach($row['info'] as $item){
		if($status == 1){
			$result = $model->addReceiver($item['account'], $item['name']);
			if($result['code'] != 0) exit(json_encode($result));
		}elseif($status == 0){
			$result = $model->deleteReceiver($item['account'], $item['name']);
		}
	}
	$DB->update('psreceiver', ['status'=>$status], ['id'=>$id]);
	exit('{"code":0,"msg":"状态修改成功！"}');
break;

case 'del_receiver':
	$id=intval($_POST['id']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	if($DB->delete('psreceiver', ['id'=>$id])){
		exit('{"code":0,"msg":"删除分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"删除分账规则失败['.$DB->error().']"}');
	}
break;


case 'submit':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,B.account,B.name,B.rate,B.info,B.uid psuid,C.uid,C.subchannel,C.realmoney ordermoney FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=0&&$row['status']!=3)exit('{"code":-1,"msg":"只有待分账的订单才能提交分账"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$row['info'] = !empty($row['info']) ? json_decode($row['info'], true) : [['account'=>$row['account'], 'name'=>$row['name'], 'rate'=>$row['rate']]];
	if(!empty($row['sub_trade_no'])){
		$row['ordermoney'] = $DB->findColumn('suborder', 'money', ['sub_trade_no'=>$row['sub_trade_no']]);
		$row['trade_no'] = $row['sub_trade_no'];
	}
	$result = $model->submit($row['trade_no'], $row['api_trade_no'], $row['ordermoney'], $row['info']);
	if($result['code'] == 0){
		$DB->update('psorder', ['status'=>1,'settle_no'=>$result['settle_no']], ['id'=>$id]);
	}elseif($result['code'] == 1){
		$DB->update('psorder', ['status'=>2,'settle_no'=>$result['settle_no']], ['id'=>$id]);
		if(!empty($row['psuid']) && $channel['mode']==0){
			changeUserMoney($row['psuid'], $row['money'], false, '订单分账', $row['trade_no']);
		}
	}elseif($result['code'] == -2){
		//$DB->update('psorder', ['status'=>3,'result'=>$result['msg']], ['id'=>$id]);
	}
	if(isset($result['rdata'])){
		$DB->update('psorder', ['money'=>$result['money'], 'rdata'=>json_encode($result['rdata'])], ['id'=>$id]);
	}
	exit(json_encode($result));
break;

case 'query':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,B.uid psuid,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=1)exit('{"code":-1,"msg":"只有已提交的订单才能查询结果"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	if(!empty($row['sub_trade_no'])) $row['trade_no'] = $row['sub_trade_no'];
	$result = $model->query($row['trade_no'], $row['api_trade_no'], $row['settle_no']);
	if($result['code']==0){
		if($result['status']==1){
			$DB->update('psorder', ['status'=>2], ['id'=>$id]);
			if(!empty($row['psuid']) && $channel['mode']==0){
				changeUserMoney($row['psuid'], $row['money'], false, '订单分账', $row['trade_no']);
			}
		}elseif($result['status']==2){
			$DB->update('psorder', ['status'=>3,'result'=>$result['reason']], ['id'=>$id]);
		}
	}
	exit(json_encode($result));
break;

case 'unfreeeze':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']==2)exit('{"code":-1,"msg":"只有待分账的订单才能取消分账"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	if(!empty($row['sub_trade_no'])) $row['trade_no'] = $row['sub_trade_no'];
	$result = $model->unfreeeze($row['trade_no'], $row['api_trade_no']);
	if($result['code'] == 0){
		$DB->update('psorder', ['status'=>4], ['id'=>$id]);
	}
	exit(json_encode($result));
break;

case 'return':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=2)exit('{"code":-1,"msg":"只有分账成功的订单才能回退"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$row['rdata'] = json_decode($row['rdata'], true) ?? [];
	if(!empty($row['sub_trade_no'])) $row['trade_no'] = $row['sub_trade_no'];
	$result = $model->return($row['trade_no'], $row['api_trade_no'], $row['rdata']);
	if($result['code'] == 0){
		$DB->update('psorder', ['status'=>4], ['id'=>$id]);
	}
	exit(json_encode($result));
break;

case 'editmoney':
	$id=intval($_POST['id']);
	$money=trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额输入错误"}');
	$row = $DB->getRow("SELECT * FROM pre_psorder WHERE id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=0)exit('{"code":-1,"msg":"只有待分账的订单才能修改金额"}');
	$DB->update('psorder', ['money'=>$money], ['id'=>$id]);
	exit('{"code":0,"msg":"succ"}');
break;

case 'operation': //批量操作订单
	$status=is_numeric($_POST['status'])?intval($_POST['status']):exit('{"code":-1,"msg":"请选择操作"}');
	$checkbox=$_POST['checkbox'];
	$i=0;
	foreach($checkbox as $id){
		if($status==5)$DB->exec("DELETE FROM pre_psorder WHERE id='$id'");
		else $DB->exec("update pre_psorder set status='$status' where id='$id' limit 1");
		$i++;
	}
	exit('{"code":0,"msg":"成功改变'.$i.'条订单状态"}');
break;

case 'statistics':
    $sql = " 1=1";
    if(isset($_POST['rid']) && !empty($_POST['rid'])) {
        $rid = intval($_POST['rid']);
        $sql .= " AND rid='$rid'";
    }
    if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
        $dstatus = intval($_POST['dstatus']);
        $sql .= " AND status={$dstatus}";
    }
    if(!empty($_POST['starttime']) || !empty($_POST['endtime'])){
        if(!empty($_POST['starttime'])){
            $starttime = daddslashes($_POST['starttime']);
            $sql .= " AND addtime>='{$starttime} 00:00:00'";
        }
        if(!empty($_POST['endtime'])){
            $endtime = daddslashes($_POST['endtime']);
            $sql .= " AND addtime<='{$endtime} 23:59:59'";
        }
    }
    if(isset($_POST['value']) && !empty($_POST['value'])) {
        $column = daddslashes($_POST['column']);
        if($column == 'money'){
            $sql .= " AND {$column}='".floatval($_POST['value'])."'";
        }else{
            $sql .= " AND {$column}='".daddslashes($_POST['value'])."'";
        }
    }

    $result = $DB->getRow("SELECT 
        SUM(money) AS totalMoney,
        SUM(CASE WHEN status = 2 THEN money ELSE 0 END) AS successMoney,
        SUM(CASE WHEN status = 3 THEN money ELSE 0 END) AS failMoney,
        COUNT(*) AS totalCount,
        SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) AS successCount,
        SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) AS failCount
        FROM pre_psorder WHERE {$sql}");

    $successRate = $result['totalCount'] > 0 ? round(($result['successCount'] / $result['totalCount']) * 100, 2) : 0;

    $data = [
        'totalMoney' => number_format($result['totalMoney'] ?? 0, 2, '.', ''),
        'successMoney' => number_format($result['successMoney'] ?? 0, 2, '.', ''),
        'failMoney' => number_format($result['failMoney'] ?? 0, 2, '.', ''),
        'totalCount' => $result['totalCount'] ?? 0,
        'successCount' => $result['successCount'] ?? 0,
        'failCount' => $result['failCount'] ?? 0,
        'successRate' => $successRate
    ];
    
    exit(json_encode(['code' => 0, 'data' => $data]));
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}