<?php

namespace lib\ProfitSharing;

use Exception;

require_once PLUGIN_ROOT.'kuaiqian/inc/PayApp.class.php';

class Kuaiqian implements IProfitSharing
{

    static $paytype = 'kuaiqian';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
        $this->service = new \kuaiqian\PayApp($channel['appid'], $channel['appkey'], $channel['appsecret']);
	}

    private function requestApi($messageType, $params, $out_biz_no = null){
        if(!$out_biz_no) $out_biz_no = date("YmdHis").rand(11111,99999);
        $head = [
            'version' => '1.0.0',
			'messageType' => $messageType,
			'memberCode' => $this->channel['appid'],
			'externalRefNumber' => $out_biz_no,
        ];
        $result = $this->service->execute($head, $params);
        if($result['bizResponseCode'] == '0000'){
            return $result;
        }else{
            throw new \Exception('['.$result['bizResponseCode'].']'.$result['bizResponseMessage']);
        }
    }

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        $sharingDataList = [];
        $rdata = [];
        $allmoney = 0;
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $sharingDataList[] = ['sharingDataContact'=>$receiver['account'], 'sharingDataAmount'=>strval($money * 100), 'sharingDataSyncFlag'=>'0'];
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
            $allmoney += $money;
        }

        $params = [
            'merchantId' => $this->channel['merchant_id'],
            'terminalId' => $this->channel['terminal_id'],
            'refNumber' => $api_trade_no,
            'amount' => strval($order_money * 100),
            'txnTime' => substr($trade_no, 0, 14),
            'feeMode' => '0',
            'feePayer' => $info[0]['account'],
            'sharingDataList' => $sharingDataList
        ];

        try{
            $result = $this->requestApi('A9010', $params, $trade_no);
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
        return ['code'=>0, 'msg'=>'分账请求成功', 'settle_no'=>$trade_no, 'money'=>$allmoney, 'rdata'=>$rdata];
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $params = [
            'merchantId' => $this->channel['merchant_id'],
            'refNumber' => $api_trade_no,
        ];

        try{
            $result = $this->requestApi('A9013a', $params, $trade_no);
            if($result['resultcode'] == 'SU' || $result['resultcode'] == 'PU'){
                return ['code'=>0, 'status'=>1];
            } elseif($result['resultcode'] == 'IN') {
                return ['code'=>0, 'status'=>0];
            } else {
                return ['code'=>0, 'status'=>2];
            }
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        return ['code'=>-1,'msg'=>'不支持当前操作'];
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $rdata){
        global $DB;
        $sharingDataList = [];
        $allmoney = 0;
        foreach($rdata as $row){
            $sharingDataList[] = ['refundSharingDataContactFlag' => '2', 'refundSharingDataContact'=>$row['account'], 'refundSharingDataAmount'=>strval($row['money'] * 100)];
            $allmoney += $row['money'];
        }
        $params = [
            'merchantId' => $this->channel['merchant_id'],
            'terminalId' => $this->channel['terminal_id'],
            'origRefNumber' => $api_trade_no,
            'amount' => strval($allmoney * 100),
            'refundSharingInfo' => [
                'refundSharingFlag' => '1',
                'refundSharingDataList' => $sharingDataList,
            ],
        ];

        try{
            $result = $this->requestApi('A9019', $params);
            return ['code'=>0, 'msg'=>'分账撤销成功'];
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
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