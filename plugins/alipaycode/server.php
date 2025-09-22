<?php
if (substr(php_sapi_name(), 0, 3) != 'cli') {
    die("This Programe can only be run in CLI mode");
}
@chdir(dirname(__FILE__));
$nosession = true;
include("../../includes/common.php");

$channelid = isset($argv[1]) ? intval($argv[1]) : exit('支付通道ID不能为空');
$channel = \lib\Channel::get($channelid);
if(!$channel || $channel['plugin'] != 'alipaycode') exit('支付通道不存在');
$sql = "";
if(substr($channel['apptoken'], 0, 1) == '['){
    $channel = \lib\Channel::getSub($channelid);
    if(!$channel || $channel['plugin'] != 'alipaycode') exit('子通道不存在');
    $sql = " AND subchannel='{$channel['subid']}'";
}
$alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
$aop = new \Alipay\AlipayBillService($alipay_config);

while(true){
    $now = time();
    $list = $DB->getAll("SELECT trade_no,realmoney FROM pre_order WHERE channel='{$channel['id']}'{$sql} AND status=0 AND addtime>=DATE_SUB(NOW(), INTERVAL 8 MINUTE)");
    if(empty($list)){
        echo '暂无未支付订单...'.PHP_EOL;
        goto WAIT;
    }
    $start_time = date('Y-m-d H:i:s', time()-180);
    $end_time = date('Y-m-d H:i:s', time()+60);
    try{
        $result = $aop->accountlogQuery($start_time, $end_time, 1, 2000);
    }catch(Exception $e){
        echo '查询账务明细失败，'.$e->getMessage().PHP_EOL;
        goto WAIT;
    }
    if(empty($result['detail_list'])){
        echo '共查询到0条账务明细'.PHP_EOL;
        goto WAIT;
    }
    echo '共查询到'.count($result['detail_list']).'条账务明细'.PHP_EOL;
    foreach($result['detail_list'] as $item){
        if(isset($item['trans_memo']) && isset($item['trans_amount'])){
            $trade_no = str_replace('请勿添加备注-', '', $item['trans_memo']);
            $money = $item['trans_amount'];
            $orders = array_filter($list, function($v) use($trade_no, $money){
                return $v['trade_no'] == $trade_no && $v['realmoney'] == $money;
            });
            if(!empty($orders)){
                $order = $DB->getRow("SELECT A.*,B.name typename,B.showname typeshowname FROM pre_order A left join pre_type B on A.type=B.id WHERE trade_no=:trade_no limit 1", [':trade_no'=>$trade_no]);
                $order['plugin'] = $channel['plugin'];
                $buyer = empty($order['buyer']) ? $item['other_account'] : null;
                processNotify($order, $item['alipay_order_no'], $buyer);
                echo '订单'.$trade_no.'('.$item['trans_amount'].'元)支付成功'.PHP_EOL;
            }
        }
    }
    WAIT:
    $time = time()-$now;
    if($time < 3){
        sleep(3-$time);
    }
}
echo 'stop!';