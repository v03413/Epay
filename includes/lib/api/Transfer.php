<?php
namespace lib\api;

use Exception;

class Transfer
{
    public static function submit(){
        global $conf, $DB, $userrow, $queryArr, $siteurl;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        $type = $queryArr['type'];
        $out_biz_no = trim($queryArr['out_biz_no']);
        $account = htmlspecialchars(trim($queryArr['account']));
        $name = htmlspecialchars(trim($queryArr['name']));
        $money = trim($queryArr['money']);
        $desc = htmlspecialchars(trim($queryArr['remark']));

        if(empty($type))throw new Exception('代付方式(type)不能为空');
        if(empty($out_biz_no)) $out_biz_no = date("YmdHis").rand(11111,99999);
        if(empty($account))throw new Exception('收款人账号(account)不能为空');
        //if(empty($name))throw new Exception('收款人姓名(name)不能为空');
        if(empty($money))throw new Exception('转账金额(money)不能为空');
        if(!preg_match('/^[a-zA-Z0-9.\_\-|]+$/',$out_biz_no))throw new Exception('交易号输入不规范');
        if($desc && mb_strlen($desc)>32)throw new Exception('转账备注最多32个字');
        if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)throw new Exception('转账金额输入不规范');

        $result = \lib\Transfer::add($pid, $type, $out_biz_no, $account, $name, $money, $desc, $queryArr['bookid']);
        return $result;
    }

    public static function query(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        if(!empty($queryArr['biz_no'])){
            $biz_no = trim($queryArr['biz_no']);
            $order = $DB->find('transfer', '*', ['biz_no'=>$biz_no, 'uid'=>$pid]);
        }elseif(!empty($queryArr['out_biz_no'])){
            $out_biz_no = trim($queryArr['out_biz_no']);
            $order = $DB->find('transfer', '*', ['biz_no'=>$out_biz_no, 'uid'=>$pid]);
        }else{
            throw new Exception('转账交易号不能为空');
        }
        if(!$order){
            if(class_exists('\\lib\\AlipaySATF\\AlipaySATF') && $conf['alipay_satf']==1){
                if(!empty($queryArr['biz_no'])){
                    $biz_no = trim($queryArr['biz_no']);
                    $order = $DB->find('satf_transfer', 'trade_no', ['trade_no'=>$biz_no, 'uid'=>$pid]);
                }elseif(!empty($queryArr['out_biz_no'])){
                    $out_biz_no = trim($queryArr['out_biz_no']);
                    $order = $DB->find('satf_transfer', 'trade_no', ['out_trade_no'=>$out_biz_no, 'uid'=>$pid]);
                }
                if(!$order) throw new Exception('转账交易号不存在');
                $satf = new \lib\AlipaySATF\AlipaySATF();
                $result = $satf->transferQuery($order['trade_no'], $pid);
                return $result;
            }
            throw new Exception('转账交易号不存在');
        }
        if(!$order['out_biz_no']) $order['out_biz_no'] = $order['biz_no'];

        if($order['status'] == 1){
            $result = ['code'=>0, 'msg'=>'转账成功！', 'biz_no'=>$order['biz_no'], 'out_biz_no'=>$order['out_biz_no'], 'status'=>1, 'amount'=>$order['money'], 'cost_money'=>$order['costmoney'], 'paydate'=>$order['paytime'], 'remark'=>$order['desc']];
        }elseif($order['status'] == 2){
            $errmsg = ($order['result']?$order['result']:'原因未知');
            $result = ['code'=>0, 'msg'=>'转账失败：'.($order['result']?$order['result']:'原因未知'), 'biz_no'=>$order['biz_no'], 'out_biz_no'=>$order['out_biz_no'], 'status'=>2, 'amount'=>$order['money'], 'cost_money'=>$order['money'], 'paydate'=>$order['paytime'], 'remark'=>$order['desc'], 'errmsg'=>$errmsg];
        }else{
            $result = \lib\Transfer::status($order['biz_no']);
            $result['biz_no'] = $order['biz_no'];
            $result['out_biz_no'] = $order['out_biz_no'];
            $result['remark'] = $order['desc'];
            $result['cost_money'] = $order['costmoney'];
        }

        return $result;
    }

    public static function proof(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        if(!empty($queryArr['biz_no'])){
            $biz_no = trim($queryArr['biz_no']);
            $order = $DB->find('transfer', 'biz_no', ['biz_no'=>$biz_no, 'uid'=>$pid]);
        }elseif(!empty($queryArr['out_biz_no'])){
            $out_biz_no = trim($queryArr['out_biz_no']);
            $order = $DB->find('transfer', 'biz_no', ['biz_no'=>$out_biz_no, 'uid'=>$pid]);
        }else{
            throw new Exception('转账交易号不能为空');
        }
        if(!$order) throw new Exception('当前转账订单不存在');

        $result = \lib\Transfer::proof($order['biz_no']);

        return $result;
    }

    public static function balance(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        if($conf['settle_type']==1){
            $today=date("Y-m-d").' 00:00:00';
            $order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$pid} and tid<>2 and status=1 and endtime>='$today'");
            if(!$order_today) $order_today = 0;
            $enable_money=round($userrow['money']-$order_today,2);
            if($enable_money<0)$enable_money=0;
        }else{
            $enable_money=$userrow['money'];
        }
        if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];

        $result = ['code'=>0, 'available_money'=>strval($enable_money), 'transfer_rate'=>$conf['transfer_rate']];

        return $result;
    }
}