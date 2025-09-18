<?php
$is_defend = true;
include("./inc.php");
@header('Content-Type: text/html; charset=UTF-8');

$id = isset($_GET['id'])?trim($_GET['id']):exit('No id');
$type = isset($_GET['type'])?trim($_GET['type']):'transfer';

if($type == 'transfer'){
    $row = $DB->find('transfer', '*', ['biz_no'=>$id]);
    if(!$row) showerror('转账订单号不存在');
    if($row['type'] != 'wxpay') showerror('转账类型错误');
    if($row['status'] == 2) showerror('转账已失败，请重新提交！失败原因:'.$row['result']);
    if(empty($row['ext'])) showerror('package_info不存在');
    $channelid = $row['channel'];
    $out_biz_no = $row['biz_no'];
    $money = $row['money'];
    $addtime = $row['addtime'];
    $paytime = $row['paytime'];
    $package_info = $row['ext'];

}elseif($type == 'settle'){
    $row = $DB->find('settle', '*', ['id'=>$id]);
    if(!$row) showerror('结算记录不存在');
    if(empty($row['transfer_no']) || empty($row['transfer_channel'])) showerror('结算记录未发起转账');
    if($row['transfer_status'] == 2) showerror('转账已失败，请重新提交！失败原因:'.$row['result']);
    if(empty($row['transfer_ext'])) showerror('package_info不存在');
    $channelid = $row['transfer_channel'];
    $out_biz_no = $row['transfer_no'];
    $money = $row['realmoney'];
    $addtime = $row['transfer_date'];
    $paytime = $row['endtime'];
    $package_info = $row['transfer_ext'];

}else{
    showerror('类型错误');
}

$channel = \lib\Channel::get($channelid);
if(!$channel) showerror('当前支付通道信息不存在');
$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
if(!$wxinfo) showerror('支付通道绑定的微信公众号不存在');
$url = rtrim($siteurl, '/').$_SERVER['REQUEST_URI'];

if(!checkwechat()) showerror('请在微信客户端打开链接');

$result = \lib\Transfer::query('wxpay', $channel, $out_biz_no, '');
if($result['code'] == 0){
    if($result['status'] == 2){
        if($type == 'transfer' && $row['status'] == 0){
            $resCount = $DB->update('transfer', ['status'=>2, 'result'=>$result['errmsg']], ['biz_no' => $out_biz_no]);
            if($row['uid'] > 0 && $resCount > 0){
                changeUserMoney($row['uid'], $row['costmoney'], true, '代付退回');
            }
        }elseif($type == 'settle' && $row['transfer_status'] == 1){
            $DB->update('settle', ['transfer_status'=>2, 'transfer_result'=>$result['errmsg'], 'status'=>3, 'result'=>$result['errmsg']], ['id' => $row['id']]);
        }
        showerror('转账已失败，请重新提交！失败原因:'.$result['errmsg']);
    }elseif($result['status'] == 1){
        if($type == 'transfer' && $row['status'] == 0){
            $paytimen = $result['paydate'] ?? 'NOW()';
            $DB->update('transfer', ['status'=>1, 'paytime'=>$paytimen, 'result'=>''], ['biz_no' => $out_biz_no]);
        }
        if(empty($paytime)) $paytime = date("Y-m-d H:i:s");
        include ROOT.'paypage/wxtrans_success.php';
        exit;
    }
}

if(isset($_GET['do']) && $_GET['do'] == 'success'){
    $paytime = date("Y-m-d H:i:s");
    include ROOT.'paypage/wxtrans_success.php';
    exit;
}

try{
    $wechat = new \lib\wechat\WechatAPI($wxinfo['id']);
    $wxconfig = $wechat->getJsapiConfig($wxinfo['appid'], $url, ['requestMerchantTransfer']);
    $wxconfig = json_encode($wxconfig);
}catch(Exception $e){
    showerror($e->getMessage());
}

$wxtransfer = [
    'mchId' => $channel['appmchid'],
    'appId' => $wxinfo['appid'],
    'package' => $package_info,
];
$wxtransfer = json_encode($wxtransfer);

include ROOT.'paypage/wxtrans_confirm.php';
exit;