<?php

namespace lib\ProfitSharing;

require(PLUGIN_ROOT.'yeepay/inc/YopClient.php');

use Exception;

class Yeepay implements IProfitSharing
{

    static $paytype = 'yeepay';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
        $this->service = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        $divideDetail = [];
        $allmoney = 0;
        $rdata = [];
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $divideDetail[] = [
                'ledgerNo' => $receiver['account'],
                'amount' => $money,
                'ledgerType' => 'MERCHANT2MERCHANT',
            ];
            $allmoney += $money;
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
        }
        $params = [
            'parentMerchantNo' => $this->channel['appid'],
			'merchantNo' => empty($this->channel['appmchid'])?$this->channel['appid']:$this->channel['appmchid'],
            'orderId' => $trade_no,
            'uniqueOrderNo' => $api_trade_no,
            'divideRequestId' => 'F'.$trade_no,
            'divideDetail' => json_encode($divideDetail),
            'isUnfreezeResidualAmount' => 'TRUE',
        ];
        try{
            $result = $this->service->post('/rest/v1.0/divide/apply', $params);
            if($result['code'] == 'OPR00000'){
                if($result['status'] == 'SUCCESS'){
                    return ['code'=>1, 'msg'=>'分账成功', 'settle_no'=>$result['divideRequestId'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
                }else{
                    return ['code'=>0, 'msg'=>'请求分账成功', 'settle_no'=>$result['divideRequestId'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
                }
            }else{
                throw new Exception('['.$result['code'].']'.$result['message']);
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $params = [
            'parentMerchantNo' => $this->channel['appid'],
			'merchantNo' => empty($this->channel['appmchid'])?$this->channel['appid']:$this->channel['appmchid'],
            'divideRequestId' => $settle_no,
            'orderId' => $trade_no,
            'uniqueOrderNo' => $api_trade_no,
        ];
        try{
            $result = $this->service->get('/rest/v1.0/divide/query', $params);
            if($result['code'] == 'OPR00000'){
                if($result['status'] == 'SUCCESS'){
                    return ['code'=>0, 'status'=>1];
                }elseif($result['status'] == 'FAIL'){
                    return ['code'=>0, 'status'=>2, 'reason'=>$result['message']];
                }else{
                    return ['code'=>0, 'status'=>0];
                }
            }else{
                throw new Exception('['.$result['code'].']'.$result['message']);
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        $params = [
            'parentMerchantNo' => $this->channel['appid'],
			'merchantNo' => empty($this->channel['appmchid'])?$this->channel['appid']:$this->channel['appmchid'],
            'divideRequestId' => 'C'.$trade_no,
            'orderId' => $trade_no,
            'uniqueOrderNo' => $api_trade_no,
        ];
        try{
            $result = $this->service->post('/rest/v1.0/divide/complete', $params);
            if($result['code'] == 'OPR00000'){
                return ['code'=>0, 'msg'=>'解冻剩余资金成功'];
            }else{
                throw new Exception('['.$result['code'].']'.$result['message']);
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $rdata){
        return ['code'=>-1, 'msg'=>'暂不支持分账回退'];
        $params = [
            'parentMerchantNo' => $this->channel['appid'],
			'merchantNo' => empty($this->channel['appmchid'])?$this->channel['appid']:$this->channel['appmchid'],
            'divideBackRequestId' => 'B'.$trade_no,
            'divideRequestId' => $settle_no,
            'orderId' => $trade_no,
            'uniqueOrderNo' => $api_trade_no,
            'divideBackDetail' => '',
        ];
        try{
            $result = $this->service->post('/rest/v1.0/divide/back', $params);
            if($result['code'] == 'OPR00000'){
                return ['code'=>0, 'msg'=>'退分账成功'];
            }else{
                throw new Exception('['.$result['code'].']'.$result['message']);
            }
        } catch (Exception $e) {
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