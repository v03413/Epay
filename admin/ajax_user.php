<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'userList':
	$usergroup = [0=>'默认用户组'];
	$rs = $DB->getAll("SELECT * FROM pre_group");
	foreach($rs as $row){
		$usergroup[$row['gid']] = $row['name'];
	}
	unset($rs);

	$sql=" 1=1";
	if(isset($_POST['dstatus']) && !empty($_POST['dstatus'])) {
		$dstatus = explode('_',$_POST['dstatus']);
		$sql.=" AND `{$dstatus[0]}`='{$dstatus[1]}'";
	}
	if(isset($_POST['gid']) && $_POST['gid']!=='') {
		$gid = intval($_POST['gid']);
		$sql.=" AND `gid`='$gid'";
	}
	if(isset($_POST['upid']) && $_POST['upid']!=='') {
		$upid = intval($_POST['upid']);
		$sql.=" AND `upid`='$upid'";
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$sql.=" AND `{$_POST['column']}`='{$_POST['value']}'";
	}
	if(isset($_POST['order_days']) && !empty($_POST['order_days'])) {
		$order_days = intval($_POST['order_days']);
		$sql.=" AND uid NOT IN (SELECT DISTINCT uid FROM pre_order WHERE date>=NOW()-INTERVAL {$order_days} DAY)";
	}
	$order = "uid desc";
	if(isset($_POST['order']) && !empty($_POST['order'])) {
		$order=str_replace('_', ' ', $_POST['order']);
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_user WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_user WHERE{$sql} order by {$order} limit $offset,$limit");
	$list2 = [];
	foreach($list as $row){
		if($row['endtime']!=null && strtotime($row['endtime'])<time()){
			$DB->exec("UPDATE pre_user SET gid=0,endtime=NULL WHERE uid='{$row['uid']}'");
			$row['gid']=0;
		}elseif($row['endtime']!=null){
			$row['endtime'] = date("Y-m-d", strtotime($row['endtime']));
		}
		$row['groupname'] = $usergroup[$row['gid']];
		$list2[] = $row;
	}

	exit(json_encode(['total'=>$total, 'rows'=>$list2]));
break;

case 'recordList':
	$sql=" 1=1";
	if(isset($_POST['uid']) && !empty($_POST['uid'])) {
		$uid = intval($_POST['uid']);
		$sql.=" AND `uid`='$uid'";
	}
	if(!empty($_POST['starttime']) || !empty($_POST['endtime'])){
		if(!empty($_POST['starttime'])){
			$starttime = daddslashes($_POST['starttime']);
			$sql.=" AND `date`>='{$starttime} 00:00:00'";
		}
		if(!empty($_POST['endtime'])){
			$endtime = daddslashes($_POST['endtime']);
			$sql.=" AND `date`<='{$endtime} 23:59:59'";
		}
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$sql.=" AND `{$_POST['column']}`='{$_POST['value']}'";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_record WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_record WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'record_stats':
	$sql=" 1=1";
	if(isset($_POST['uid']) && !empty($_POST['uid'])) {
		$uid = intval($_POST['uid']);
		$sql.=" AND `uid`='$uid'";
	}
	if(!empty($_POST['starttime']) || !empty($_POST['endtime'])){
		if(!empty($_POST['starttime'])){
			$starttime = daddslashes($_POST['starttime']);
			$sql.=" AND `date`>='{$starttime} 00:00:00'";
		}
		if(!empty($_POST['endtime'])){
			$endtime = daddslashes($_POST['endtime']);
			$sql.=" AND `date`<='{$endtime} 23:59:59'";
		}
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$sql.=" AND `{$_POST['column']}`='{$_POST['value']}'";
	}
	$result = $DB->getRow("SELECT 
        SUM(CASE WHEN action = 1 THEN money ELSE 0 END) AS incMoney,
        SUM(CASE WHEN action = 2 THEN money ELSE 0 END) AS decMoney
        FROM pre_record WHERE {$sql}");
	$data = [
        'incMoney' => number_format($result['incMoney'] ?? 0, 2, '.', ''),
        'decMoney' => number_format($result['decMoney'] ?? 0, 2, '.', ''),
        'totalMoney' => number_format(($result['incMoney'] ?? 0) - ($result['decMoney'] ?? 0), 2, '.', ''),
    ];
    exit(json_encode(['code' => 0, 'data' => $data]));
break;

case 'userPayStat':
	$startday = trim($_POST['startday']);
	$endday = trim($_POST['endday']);
	$method = trim($_POST['method']);
	$type = intval($_POST['type']);
	if(!$startday || !$endday)exit(json_encode(['code'=>0, 'msg'=>'no day']));
	$data = [];
	$columns = ['uid'=>'商户ID', 'total'=>'总计'];

	if($method == 'type'){
		$paytype = [];
		$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
		foreach($rs as $row){
			$paytype[$row['id']] = $row['showname'];
			if($type == 4){
				$columns['type_'.$row['name']] = $row['showname'];
			}else{
				$columns['type_'.$row['id']] = $row['showname'];
			}
		}
		unset($rs);
	}else{
		$channel = [];
		$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
		foreach($rs as $row){
			$channel[$row['id']] = $row['name'];
		}
		unset($rs);
	}

	if($type == 4){
		$startday .= ' 00:00:00';
		$endday .= ' 23:59:59';
		$rs=$DB->query("SELECT uid,type,channel,money from pre_transfer where status=1 and paytime>='$startday' and paytime<='$endday'");
		while($row = $rs->fetch())
		{
			$money = (float)$row['money'];
			if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
			$data[$row['uid']]['total'] += $money;
			if($method == 'type'){
				$ukey = 'type_'.$row['type'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
			}else{
				$ukey = 'channel_'.$row['channel'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
				if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
			}
		}
	}else{
		$rs=$DB->query("SELECT uid,type,channel,money,realmoney,getmoney,profitmoney from pre_order where status=1 and date>='$startday' and date<='$endday'");
		while($row = $rs->fetch())
		{
			if($type == 3){
				$money = (float)$row['profitmoney'];
			}elseif($type == 2){
				$money = (float)$row['getmoney'];
			}elseif($type == 1){
				$money = (float)$row['realmoney'];
			}else{
				$money = (float)$row['money'];
			}
			if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
			$data[$row['uid']]['total'] += $money;
			if($method == 'type'){
				$ukey = 'type_'.$row['type'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
			}else{
				$ukey = 'channel_'.$row['channel'];
				if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
				else $data[$row['uid']][$ukey] += $money;
				if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
			}
		}
	}
	ksort($data);
	//计算总计
	$total = ['uid'=>'总计'];
	foreach($data as $row){
		foreach($row as $key=>$val){
			if($key=='uid')continue;
			if(!array_key_exists($key, $total)) $total[$key] = $val;
			else $total[$key] += $val;
		}
	}
	array_unshift($data, $total);
	$list = [];
	foreach($data as $row){
		$list[] = $row;
	}
	exit(json_encode(['code'=>0, 'columns'=>$columns, 'data'=>$list]));
break;

case 'userTransferStat':
	$startday = trim($_POST['startday']);
	$endday = trim($_POST['endday']);
	$method = trim($_POST['method']);
	if(!$startday || !$endday)exit(json_encode(['code'=>0, 'msg'=>'no day']));
	$data = [];
	$columns = ['uid'=>'商户ID', 'total'=>'总计'];

	if($method == 'type'){
		$paytype = [];
		$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
		foreach($rs as $row){
			$paytype[$row['name']] = $row['showname'];
			$columns['type_'.$row['name']] = $row['showname'];
		}
		unset($rs);
	}else{
		$channel = [];
		$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
		foreach($rs as $row){
			$channel[$row['id']] = $row['name'];
		}
		unset($rs);
	}

	$rs=$DB->query("SELECT uid,type,channel,money from pre_transfer where status=1 and paytime>='$startday' and paytime<='$endday'");
	while($row = $rs->fetch())
	{
		$money = (float)$row['money'];
		if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
		$data[$row['uid']]['total'] += $money;
		if($method == 'type'){
			$ukey = 'type_'.$row['type'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
		}else{
			$ukey = 'channel_'.$row['channel'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
			if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
		}
	}
	ksort($data);
	$list = [];
	foreach($data as $row){
		$list[] = $row;
	}
	exit(json_encode(['code'=>0, 'columns'=>$columns, 'data'=>$list]));
break;

case 'buyerStat':
	$startday = trim($_POST['startday']);
	$endday = trim($_POST['endday']);
	$method = intval($_POST['method']);
	if($method == '2') $column = 'mobile';
	else if($method == '1') $column = 'ip';
	else $column = 'buyer';
	if(!$startday || !$endday)exit(json_encode(['code'=>0, 'msg'=>'no day']));
	$sql = "`date` BETWEEN '{$startday}' AND '{$endday}' AND {$column} is not null AND status>0";
	if(isset($_POST['type']) && !empty($_POST['type'])) {
		$type = intval($_POST['type']);
		$sql.=" AND `type`='$type'";
	}
	$list = $DB->getAll("SELECT A.*,ISNULL(B.id) is_black
		FROM (SELECT {$column} `user`,COUNT(*) AS order_count,MAX(trade_no) trade_no
		FROM pay_order
		WHERE {$sql}
		GROUP BY {$column}
		ORDER BY order_count DESC) A
		LEFT JOIN pay_blacklist B ON A.`user`=B.content");
	exit(json_encode($list));
break;

case 'logList':
	$sql=" 1=1";
	if(isset($_POST['value']) && $_POST['value']!=='') {
		$sql.=" AND `{$_POST['column']}`='{$_POST['value']}'";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_log WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_log WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'domainList':
	$sql=" 1=1";
	if(isset($_POST['uid']) && !empty($_POST['uid'])) {
		$uid = intval($_POST['uid']);
		$sql.=" AND `uid`='$uid'";
	}
	if(isset($_POST['kw']) && !empty($_POST['kw'])) {
		$sql.=" AND `domain`='{$_POST['kw']}'";
	}
	if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
		$dstatus = intval($_POST['dstatus']);
		$sql.=" AND `status`={$dstatus}";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_domain WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_domain WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'blackList':
	$sql=" 1=1";
	if(isset($_POST['kw']) && !empty($_POST['kw'])) {
		$sql.=" AND `content`='{$_POST['kw']}'";
	}
	if(isset($_POST['type']) && $_POST['type']>-1) {
		$type = intval($_POST['type']);
		$sql.=" AND `type`={$type}";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_blacklist WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_blacklist WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'getGroup': //用户组
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','gid'=>$gid,'name'=>$row['name'],'info'=>json_decode($row['info'],true),'config'=>$row['config']?json_decode($row['config'],true):[],'settings'=>$row['settings']];
	exit(json_encode($result));
break;
case 'delGroup':
	$gid=intval($_GET['gid']);
	$row=$DB->getRow("select * from pre_group where gid='$gid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户组不存在！"}');
	$sql = "DELETE FROM pre_group WHERE gid='$gid'";
	if($DB->exec($sql)){
		$DB->exec("UPDATE pre_user SET gid=0 WHERE gid='$gid'");
		exit('{"code":0,"msg":"删除用户组成功！"}');
	}
	else exit('{"code":-1,"msg":"删除用户组失败['.$DB->error().']"}');
break;
case 'saveGroup':
	if($_POST['action'] == 'add'){
		$name=trim($_POST['name']);
		$row=$DB->getRow("select * from pre_group where name='$name' limit 1");
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=json_encode($_POST['info']);
		$config=json_encode($_POST['config']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$data = ['name'=>$name, 'info'=>$info, 'config'=>$config, 'settings'=>$settings];
		if($DB->insert('group', $data))exit('{"code":0,"msg":"新增用户组成功！"}');
		else exit('{"code":-1,"msg":"新增用户组失败['.$DB->error().']"}');
	}elseif($_POST['action'] == 'changebuy'){
		$gid=intval($_POST['gid']);
		$status=intval($_POST['status']);
		if($DB->update('group',['isbuy'=>$status],['gid'=>$gid]))exit('{"code":0,"msg":"修改上架状态成功！"}');
		else exit('{"code":-1,"msg":"修改上架状态失败['.$DB->error().']"}');
	}else{
		$gid=intval($_POST['gid']);
		$name=trim($_POST['name']);
		$row=$DB->getRow("select * from pre_group where name='$name' and gid<>$gid limit 1");
		if($row)
			exit('{"code":-1,"msg":"用户组名称重复"}');
		$info=json_encode($_POST['info']);
		$config=json_encode($_POST['config']);
		$settings=trim($_POST['settings']);
		if($settings && !checkGroupSettings($settings))exit('{"code":-1,"msg":"用户变量格式不正确"}');
		$data = ['name'=>$name, 'info'=>$info, 'config'=>$config, 'settings'=>$settings];
		if($DB->update('group', $data, ['gid'=>$gid])!==false)exit('{"code":0,"msg":"修改用户组成功！"}');
		else exit('{"code":-1,"msg":"修改用户组失败['.$DB->error().']"}');
	}
break;
case 'saveGroupPrice':
	$prices = $_POST['price'];
	$expires = $_POST['expire'];
	$sorts = $_POST['sort'];
	$visibles = $_POST['visible'];
	foreach($prices as $gid=>$item){
		$price = trim($item);
		$expire = intval($expires[$gid]);
		$sort = trim($sorts[$gid]);
		$visible = str_replace('，',',',trim($visibles[$gid]));
		if(!is_numeric($price)||$price<0)exit('{"code":-1,"msg":"GID:'.$gid.'的售价填写错误"}');
		$DB->update('group', ['price'=>$price, 'expire'=>$expire, 'sort'=>$sort, 'visible'=>$visible], ['gid'=>$gid]);
	}
	exit('{"code":0,"msg":"保存成功！"}');
break;

case 'addUser':
	$key = random(32);
	$data = [
		'gid' => intval($_POST['gid']),
		'key' => $key,
		'settle_id' => intval($_POST['settle_id']),
		'account' => trim($_POST['account']),
		'username' => trim($_POST['username']),
		'money' => '0.00',
		'url' => trim($_POST['url']),
		'email' => trim($_POST['email']),
		'qq' => trim($_POST['qq']),
		'phone' => trim($_POST['phone']),
		'mode' => intval($_POST['mode']),
		'cert' => 0,
		'pay' => intval($_POST['pay']),
		'settle' => intval($_POST['settle']),
		'status' => intval($_POST['status']),
		'addtime' => 'NOW()',
	];

	if(empty($data['phone']) && empty($data['email'])) exit('{"code":-1,"msg":"手机号和邮箱不能都为空"}');

	if(!empty($data['phone'])){
		if($DB->find('user','*',['phone'=>$data['phone']])) exit('{"code":-1,"msg":"手机号已存在！"}');
	}
	if(!empty($data['email'])){
		if($DB->find('user','*',['email'=>$data['email']])) exit('{"code":-1,"msg":"邮箱已存在！"}');
	}

	$uid = $DB->insert('user', $data);
	if($uid!==false){
		if(!empty($_POST['pwd'])){
			$pwd = getMd5Pwd(trim($_POST['pwd']), $uid);
			$DB->update('user', ['pwd'=>$pwd], ['uid'=>$uid]);
		}
		exit(json_encode(['code'=>0, 'uid'=>$uid, 'key'=>$key]));
	}else{
		exit('{"code":-1,"msg":"添加商户失败！'.$DB->error().'"}');
	}
break;
case 'editUser':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
	if(!$rows) exit('{"code":-1,"msg":"当前商户不存在！"}');
	$data = [
		'gid' => intval($_POST['gid']),
		'upid' => intval($_POST['upid']),
		'settle_id' => intval($_POST['settle_id']),
		'account' => trim($_POST['account']),
		'username' => trim($_POST['username']),
		'money' => trim($_POST['money']),
		'url' => trim($_POST['url']),
		'email' => trim($_POST['email']),
		'qq' => trim($_POST['qq']),
		'phone' => trim($_POST['phone']),
		'cert' => intval($_POST['cert']),
		'certtype' => intval($_POST['certtype']),
		'certmethod' => intval($_POST['certmethod']),
		'certno' => trim($_POST['certno']),
		'certname' => trim($_POST['certname']),
		'certcorpno' => trim($_POST['certcorpno']),
		'certcorpname' => trim($_POST['certcorpname']),
		'ordername' => trim($_POST['ordername']),
		'mode' => intval($_POST['mode']),
		'pay' => intval($_POST['pay']),
		'settle' => intval($_POST['settle']),
		'status' => intval($_POST['status']),
		'open_code' => intval($_POST['open_code']),
		'remain_money' => !empty($_POST['remain_money']) ? trim($_POST['remain_money']) : null,
		'deposit' => !empty($_POST['deposit']) ? trim($_POST['deposit']) : null,
	];

	if($DB->update('user', $data, ['uid'=>$uid])!==false){
		if(!empty($_POST['pwd'])){
			$pwd = getMd5Pwd(trim($_POST['pwd']), $uid);
			$DB->update('user', ['pwd'=>$pwd], ['uid'=>$uid]);
		}
		exit('{"code":0}');
	}else{
		exit('{"code":-1,"msg":"修改商户信息失败！'.$DB->error().'"}');
	}
break;
case 'edit_keytype':
	$uid=intval($_POST['uid']);
	$keytype=intval($_POST['keytype']);
	$sqs = $DB->update('user', ['keytype'=>$keytype], ['uid'=>$uid]);
	if($sqs!==false){
		exit('{"code":1,"msg":"succ"}');
	}else{
		exit('{"code":-1,"msg":"保存失败！'.$DB->error().'"}');
	}
break;
case 'resetKey':
	$uid=intval($_POST['uid']);
	$key = random(32);
	$sql = "UPDATE pre_user SET `key`='$key' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"重置密钥成功","key":"'.$key.'"}');
	else exit('{"code":-1,"msg":"重置密钥失败['.$DB->error().']"}');
break;
case 'createRsaPair':
	$uid=intval($_POST['uid']);
	$keypair = generate_key_pair();
	$DB->update('user', ['publickey'=>$keypair['public_key']], ['uid'=>$uid]);
	exit(json_encode(['code'=>0, 'msg'=>'succ', 'public_key'=>$keypair['public_key'], 'private_key'=>$keypair['private_key']]));
break;
case 'editUserChannelInfo':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
	if(!$rows) exit('{"code":-1,"msg":"当前商户不存在！"}');
	$setting=$_POST['setting'];
	$channelinfo = json_encode($setting);
	if($DB->update('user', ['channelinfo'=>$channelinfo], ['uid'=>$uid])!==false){
		exit('{"code":0}');
	}else{
		exit('{"code":-1,"msg":"修改商户信息失败！'.$DB->error().'"}');
	}
break;
case 'delUser':
	$uid=intval($_GET['uid']);
	if($DB->exec("DELETE FROM pre_user WHERE uid='$uid'")){
		$DB->exec("DELETE FROM pre_subchannel WHERE uid='$uid'");
		exit('{"code":0}');
	}else{
		exit('{"code":-1,"msg":"删除商户失败！'.$DB->error().'"}');
	}
break;
case 'setUser':
	$uid=intval($_POST['uid']);
	$type=trim($_POST['type']);
	$status=intval($_POST['status']);
	if($type=='pay')$sql = "UPDATE pre_user SET pay='$status' WHERE uid='$uid'";
	elseif($type=='settle')$sql = "UPDATE pre_user SET settle='$status' WHERE uid='$uid'";
	elseif($type=='group')$sql = "UPDATE pre_user SET gid='$status' WHERE uid='$uid'";
	else $sql = "UPDATE pre_user SET status='$status' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败['.$DB->error().']"}');
break;
case 'setUserGroup':
	$uid=intval($_POST['uid']);
	$gid=intval($_POST['gid']);
	$endtime=trim($_POST['endtime']);
	if(changeUserGroup($uid, $gid, $endtime)!==false)exit('{"code":0,"msg":"修改用户成功！"}');
	else exit('{"code":-1,"msg":"修改用户失败['.$DB->error().']"}');
break;
case 'resetUser':
	$uid=intval($_GET['uid']);
	$key = random(32);
	$sql = "UPDATE pre_user SET `key`='$key' WHERE uid='$uid'";
	if($DB->exec($sql)!==false)exit('{"code":0,"msg":"重置密钥成功","key":"'.$key.'"}');
	else exit('{"code":-1,"msg":"重置密钥失败['.$DB->error().']"}');
break;
case 'user_settle_info':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$data = '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算方式</div><select class="form-control" id="pay_type" default="'.$rows['settle_id'].'">'.($conf['settle_alipay']?'<option value="1">支付宝</option>':null).''.($conf['settle_wxpay']?'<option value="2">微信</option>':null).''.($conf['settle_qqpay']?'<option value="3">QQ钱包</option>':null).''.($conf['settle_bank']?'<option value="4">银行卡</option>':null).'</select></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算账号</div><input type="text" id="pay_account" value="'.$rows['account'].'" class="form-control" required/></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">真实姓名</div><input type="text" id="pay_name" value="'.$rows['username'].'" class="form-control" required/></div></div>';
	$data .= '<input type="submit" id="save" onclick="saveInfo('.$uid.')" class="btn btn-primary btn-block" value="保存">';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data,"pay_type"=>$rows['settle_id']);
	exit(json_encode($result));
break;
case 'user_settle_save':
	$uid=intval($_POST['uid']);
	$pay_type=trim(daddslashes($_POST['pay_type']));
	$pay_account=trim(daddslashes($_POST['pay_account']));
	$pay_name=trim(daddslashes($_POST['pay_name']));
	$sds=$DB->exec("update `pre_user` set `settle_id`='$pay_type',`account`='$pay_account',`username`='$pay_name' where `uid`='$uid'");
	if($sds!==false)
		exit('{"code":0,"msg":"修改记录成功！"}');
	else
		exit('{"code":-1,"msg":"修改记录失败！'.$DB->error().'"}');
break;
case 'user_cert':
	$uid=intval($_GET['uid']);
	$rows=$DB->getRow("select cert,certtype,certmethod,certno,certname,certcorpno,certcorpname,certtime from pre_user where uid='$uid' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	$rows['certmethodname'] = show_cert_method($rows['certmethod']);
	$result = ['code'=>0,'msg'=>'succ','uid'=>$uid,'data'=>$rows];
	exit(json_encode($result));
break;
case 'recharge':
	$uid=intval($_POST['uid']);
	$do=$_POST['actdo'];
	$rmb=floatval($_POST['rmb']);
	$row=$DB->getRow("select uid,money from pre_user where uid='$uid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($do==1 && $rmb>$row['money'])$rmb=$row['money'];
	if($do==0){
		changeUserMoney($uid, $rmb, true, '后台加款');
	}else{
		changeUserMoney($uid, $rmb, false, '后台扣款');
	}
	exit('{"code":0,"msg":"succ"}');
break;

case 'addDomain':
	$uid=intval($_POST['uid']);
	$domain = trim(daddslashes($_POST['domain']));
	if(empty($domain))exit('{"code":-1,"msg":"域名不能为空"}');
	if(!checkDomain($domain))exit('{"code":-1,"msg":"域名格式不正确"}');
	$row=$DB->getRow("select uid from pre_user where uid='$uid' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前用户不存在！"}');
	if($DB->getRow("select * from pre_domain where uid=:uid and domain=:domain limit 1", [':uid'=>$uid, ':domain'=>$domain]))
		exit('{"code":-1,"msg":"该域名已存在，请勿重复添加"}');
	if(!$DB->exec("INSERT INTO `pre_domain` (`uid`,`domain`,`status`,`addtime`,`endtime`) VALUES (:uid, :domain, 1, NOW(), NOW())", [':uid'=>$uid, ':domain'=>$domain]))exit('{"code":-1,"msg":"添加失败'.$DB->error().'"}');
	exit(json_encode(['code'=>0, 'msg'=>'添加域名成功！']));
break;
case 'setDomainStatus':
	$id=intval($_POST['id']);
	$status=intval($_POST['status']);
	if($DB->exec("UPDATE pre_domain SET status='$status',endtime=NOW() WHERE id='$id'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"修改失败['.$DB->error().']"}');
break;
case 'delDomain':
	$id=intval($_POST['id']);
	if($DB->exec("DELETE FROM pre_domain WHERE id='$id'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;
case 'domain_operation':
	$status=is_numeric($_POST['status'])?intval($_POST['status']):exit('{"code":-1,"msg":"请选择操作"}');
	$checkbox=$_POST['checkbox'];
	$i=0;
	foreach($checkbox as $id){
		if($status==3)$DB->exec("DELETE FROM pre_domain WHERE id='$id'");
		else $DB->exec("UPDATE pre_domain SET status='$status',endtime=NOW() WHERE id='$id'");
		$i++;
	}
	exit('{"code":0,"msg":"成功改变'.$i.'个记录状态"}');
break;

case 'getChannels':
	$typeid = intval($_GET['typeid']);
	$type=$DB->getColumn("SELECT name FROM pre_type WHERE id='$typeid'");
	if(!$type)
		exit('{"code":-1,"msg":"当前支付方式不存在！"}');
	$list=$DB->getAll("SELECT id,name FROM pre_channel WHERE `type`='$typeid' AND status=1 ORDER BY id ASC");
	if($list){
		$result = ['code'=>0,'msg'=>'succ','data'=>$list];
		exit(json_encode($result));
	}
	else exit('{"code":-1,"msg":"该支付方式下没有可用的支付通道"}');
break;
case 'getSubChannel':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT A.*,B.type FROM pre_subchannel A LEFT JOIN pre_channel B ON A.channel=B.id WHERE A.id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','data'=>$row];
	exit(json_encode($result));
break;
case 'setSubChannel':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	$sql = "UPDATE pre_subchannel SET status='$status' WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"修改子通道成功！"}');
	else exit('{"code":-1,"msg":"修改子通道失败['.$DB->error().']"}');
break;
case 'delSubChannel':
	$id=intval($_GET['id']);
	$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE id='$id'");
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	$sql = "DELETE FROM pre_subchannel WHERE id='$id'";
	if($DB->exec($sql))exit('{"code":0,"msg":"删除子通道成功！"}');
	else exit('{"code":-1,"msg":"删除子通道失败['.$DB->error().']"}');
break;
case 'saveSubChannel':
	if($_POST['action'] == 'add'){
		$uid=intval($_POST['uid']);
		$name=trim($_POST['name']);
		$type=intval($_POST['type']);
		$channel=intval($_POST['channel']);
		$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE name='$name' AND uid='$uid' LIMIT 1");
		if($row)
			exit('{"code":-1,"msg":"子通道备注重复"}');
		$data = ['channel'=>$channel, 'uid'=>$uid, 'name'=>$name, 'addtime'=>'NOW()', 'usetime'=>'NOW()'];
		if($DB->insert('subchannel', $data))exit('{"code":0,"msg":"新增子通道成功！"}');
		else exit('{"code":-1,"msg":"新增子通道失败['.$DB->error().']"}');
	}else{
		$id=intval($_POST['id']);
		$row=$DB->getRow("SELECT * FROM pre_subchannel WHERE id='$id'");
		if(!$row) exit('{"code":-1,"msg":"当前子通道不存在！"}');
		$uid=intval($_POST['uid']);
		$name=trim($_POST['name']);
		$type=intval($_POST['type']);
		$channel=intval($_POST['channel']);
		$nrow=$DB->getRow("SELECT * FROM pre_subchannel WHERE name='$name' AND uid='$uid' AND id<>$id LIMIT 1");
		if($nrow)
			exit('{"code":-1,"msg":"子通道名称重复"}');
		$data = ['channel'=>$channel, 'name'=>$name];
		if($DB->update('subchannel', $data, ['id'=>$id])!==false){
			exit('{"code":0,"msg":"修改子通道成功！"}');
		}else exit('{"code":-1,"msg":"修改子通道失败['.$DB->error().']"}');
	}
break;
case 'subChannelInfo':
	$id=intval($_GET['id']);
	$subrow=$DB->getRow("SELECT * FROM pre_subchannel WHERE id='$id'");
	if(!$subrow)
		exit('{"code":-1,"msg":"当前子通道不存在！"}');
	$row=$DB->getRow("SELECT * FROM pre_channel WHERE id='{$subrow['channel']}'");
	if(!$row)
		exit('{"code":-1,"msg":"当前子通道对应支付通道不存在！"}');
	$typename = $DB->getColumn("SELECT name FROM pre_type WHERE id='{$row['type']}'");
	$plugin = \lib\Plugin::getConfig($row['plugin']);
	if(!$plugin)
		exit('{"code":-1,"msg":"当前支付插件不存在！"}');

	$info = json_decode($subrow['info'], true);
	$config = json_decode($row['config'],true);
	$data = '<div class="modal-body"><form class="form" id="form-info">';
	foreach($plugin['inputs'] as $key=>$input){
		if(substr($config[$key],0,1)=='['){
			$key = substr($config[$key],1,-1);
			if($input['type'] == 'textarea'){
				$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><textarea id="'.$key.'" name="info['.$key.']" rows="2" class="form-control" placeholder="'.$input['note'].'">'.$info[$key].'</textarea></div>';
			}elseif($input['type'] == 'select'){
				$addOptions = '';
				foreach($input['options'] as $k=>$v){
					$addOptions.='<option value="'.$k.'" '.($info[$key]==$k?'selected':'').'>'.$v.'</option>';
				}
				$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><select class="form-control" name="info['.$key.']" default="'.$info[$key].'">'.$addOptions.'</select></div>';
			}else{
				$data .= '<div class="form-group"><label>'.$input['name'].'：</label><br/><input type="text" id="'.$key.'" name="info['.$key.']" value="'.$info[$key].'" class="form-control" placeholder="'.$input['note'].'"/></div>';
			}
		}
	}

	$data .= '<button type="button" id="save" onclick="saveInfo('.$id.')" class="btn btn-primary btn-block">保存</button></form></div>';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data);
	exit(json_encode($result));
break;
case 'saveSubChannelInfo':
	$id=intval($_GET['id']);
	$info=$_POST['info'];
	$info = $info ? json_encode($info) : null;
	if($DB->update('subchannel', ['info'=>$info], ['id'=>$id])!==false)exit('{"code":0,"msg":"修改自定义支付参数成功！"}');
	else exit('{"code":-1,"msg":"修改自定义支付参数失败['.$DB->error().']"}');
break;

case 'addBlack':
	$type=intval($_POST['type']);
	$content = trim($_POST['content']);
	$days=intval($_POST['days']);
	$remark = trim($_POST['remark']);
	if(empty($content))exit('{"code":-1,"msg":"拉黑内容不能为空"}');
	if($DB->getRow("select * from pre_blacklist where type=:type and content=:content limit 1", [':type'=>$type, ':content'=>$content]))
		exit('{"code":-1,"msg":"该黑名单记录已存在，请勿重复添加"}');
	$endtime = $days > 0 ? date('Y-m-d H:i:s', strtotime('+'.$days.' days')) : null;
	$data = ['type'=>$type, 'content'=>$content, 'addtime'=>'NOW()', 'endtime'=>$endtime, 'remark'=>$remark];
	if($DB->insert('blacklist', $data))exit(json_encode(['code'=>0, 'msg'=>'添加黑名单成功！']));
	else exit('{"code":-1,"msg":"添加失败'.$DB->error().'"}');
break;
case 'delBlack':
	$id=intval($_POST['id']);
	if($DB->exec("DELETE FROM pre_blacklist WHERE id='$id'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;
case 'batchdelBlack':
	$checkbox=$_POST['checkbox'];
	$i = 0;
	if(!empty($checkbox)){
		$i = $DB->exec("DELETE FROM pre_blacklist WHERE id IN (".implode(',',$checkbox).")");
	}
	exit('{"code":0,"msg":"成功删除了'.$i.'个黑名单"}');
break;

case 'delRecord':
	$id=intval($_GET['id']);
	if($DB->exec("DELETE FROM pre_record WHERE id='$id'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;

case 'checkuid':
	$uid=intval($_GET['uid']);
	$row=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
	if($row)
		exit('{"code":0,"msg":"succ"}');
	else
		exit('{"code":-1,"msg":"当前商户ID不存在"}');
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}