<?php

namespace lib\ProfitSharing;

require(PLUGIN_ROOT.'yseqt/inc/YseqtClient.php');

use Exception;

class Yseqt implements IProfitSharing
{

    static $paytype = 'yseqt';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
        $this->service = new \YseqtClient($channel['appid'], $channel['appkey']);
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        $divisionList = [];
        $allmoney = 0;
        $rdata = [];
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $divisionList[] = [
                'divisionMercId' => $receiver['account'],
                'isChargeFee' => $allmoney == 0 ? 'Y' : 'N',
                'divAmount' => $money,
            ];
            $allmoney += $money;
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
        }
        if($order_money > $allmoney){
            $divisionList[] = [
                'divisionMercId' => $this->channel['appmchid'],
                'isChargeFee' => 'N',
                'divAmount' => round($order_money-$money, 2),
            ];
        }
        $requestNo = date('YmdHis').rand(1000,9999);
        $params = [
            'requestNo' => $requestNo,
            'payeeMerchantNo' => $this->channel['appmchid'],
            'origRequestNo' => $trade_no,
            'amount' => $order_money,
            'isDivision' => 'Y',
            'divisionMode' => '02',
            'divisionList' => $divisionList,
        ];
        try{
            $result = $this->service->execute('divisionRegister', $params);
            if($result['subCode'] == 'COM000'){
                if($result['state'] == 'SPLIT_SUCCESS'){
                    return ['code'=>1, 'msg'=>'预分账成功', 'settle_no'=>$result['requestNo'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
                }elseif($result['state'] == 'SUCCESS'){
                    return ['code'=>1, 'msg'=>'分账成功', 'settle_no'=>$result['requestNo'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
                }else{
                    return ['code'=>0, 'msg'=>'受理成功', 'settle_no'=>$result['requestNo'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
                }
            }else{
                throw new Exception($result['subMsg']);
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $params = [
            'origRequestNo' => $trade_no,
        ];
        try{
            $result = $this->service->execute('divisionQuery', $params);
            if($result['subCode'] == 'COM000'){
                if($result['state'] == 'SPLIT_SUCCESS' || $result['state'] == 'SUCCESS'){
                    return ['code'=>0, 'status'=>1];
                }elseif($result['state'] == 'FAILED'){
                    return ['code'=>0, 'status'=>2, 'reason'=>$result['note']];
                }else{
                    return ['code'=>0, 'status'=>0];
                }
            }else{
                throw new Exception($result['subMsg']);
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        global $DB;
        $requestNo = date('YmdHis').rand(1000,9999);
        $order_money = $DB->findColumn('order', 'realmoney', ['trade_no'=>$trade_no]);
        $params = [
            'requestNo' => $requestNo,
            'payeeMerchantNo' => $this->channel['appmchid'],
            'origRequestNo' => $trade_no,
            'amount' => $order_money,
            'isDivision' => 'N',
            'divisionMode' => '02',
        ];
        try{
            $result = $this->service->execute('divisionRegister', $params);
            if($result['subCode'] == 'COM000'){
                return ['code'=>0, 'msg'=>'解冻剩余资金成功'];
            }else{
                throw new Exception($result['subMsg']);
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $rdata){
        $i = 1;
        $success = 0;
        $errmsg = null;
        foreach($rdata as $receiver){
            $requestNo = date('YmdHis').rand(1000,9999);
            $params = [
                'requestNo' => $requestNo,
                'payeeMerchantNo' => $this->channel['appmchid'],
                'origRequestNo' => $trade_no,
                'divisionMercId' => $receiver['account'],
                'amount' => $receiver['money'],
            ];
            try{
                $result = $this->service->execute('divisionBack', $params);
                if($result['subCode'] == 'COM000'){
                    $success++;
                }else{
                    $errmsg = $result['subMsg'];
                }
            } catch (Exception $e) {
                $errmsg = $e->getMessage();
            }
        }
        if($success > 0 || $errmsg == null){
            return ['code'=>0, 'msg'=>'退分账成功'];
        }else{
            return ['code'=>-1, 'msg'=>$errmsg];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        return ['code'=>0, 'msg'=>'添加分账接收方成功'];
    }

    //删除分账接收方
    public function deleteReceiver($account){
        return ['code'=>0, 'msg'=>'删除分账接收方成功'];
    }
}