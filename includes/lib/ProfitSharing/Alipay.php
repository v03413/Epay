<?php

namespace lib\ProfitSharing;

use Exception;

class Alipay implements IProfitSharing
{

    static $paytype = 'alipay';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
        $alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $this->service = new \Alipay\AlipaySettleService($alipay_config);
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
        global $conf;
        $receivers = [];
        $allmoney = 0;
        $rdata = [];
        foreach($info as $receiver){
            $money = round(floor($order_money * $receiver['rate']) / 100, 2);
            $type = self::get_alipay_account_type($receiver['account']);
            $receivers[] = [
                'trans_in_type' => $type,
                'trans_in' => $receiver['account'],
                'amount' => $money,
                'desc' => $conf['profits_desc'],
            ];
            $allmoney += $money;
            $rdata[] = ['account'=>$receiver['account'], 'money'=>$money];
        }
        $bizContent = array(
            'out_request_no' => date("YmdHis").rand(11111,99999),
            'trade_no' => $api_trade_no,
            'royalty_parameters' => $receivers,
            'extend_params' => [
                'royalty_finish' => 'true'
            ],
            'royalty_mode' => count($receivers) > 3 ? 'async' : 'sync',
        );
        try{
            $result = $this->service->aopExecute('alipay.trade.order.settle', $bizContent);
            $status = $bizContent['royalty_mode'] == 'sync' ? 1 : 0;
            return ['code'=>$status, 'msg'=>'分账成功', 'settle_no'=>$result['settle_no'], 'money'=>round($allmoney, 2), 'rdata'=>$rdata];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        try{
            $result = $this->service->order_settle_query($settle_no);
            $receiver = $result['royalty_detail_list'][0];
            if($receiver['state'] == 'SUCCESS'){
                return ['code'=>0, 'status'=>1];
            }elseif($receiver['state'] == 'FAIL'){
                return ['code'=>0, 'status'=>2, 'reason'=>'['.$receiver['error_code'].']'.$receiver['error_desc']];
            }else{
                return ['code'=>0, 'status'=>0];
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        try{
            $this->service->order_settle_unfreeze($api_trade_no);
            return ['code'=>0, 'msg'=>'解冻剩余资金成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $rdata){
        $out_request_no = date("YmdHis").rand(11111,99999);
        $receivers = [];
        foreach($rdata as $receiver){
            $type = self::get_alipay_account_type($receiver['account']);
            $receivers[] = [
                'royalty_type' => 'transfer',
                'trans_out_type' => $type,
                'trans_out' => $receiver['account'],
                'amount' => $receiver['money']
            ];
        }
        $bizContent = array(
            'trade_no' => $api_trade_no,
            'refund_amount' => '0',
            'out_request_no' => $out_request_no,
            'refund_royalty_parameters' => $receivers
        );
        try{
            $this->service->aopExecute('alipay.trade.refund', $bizContent);
            return ['code'=>0, 'msg'=>'退分账成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage().'（提示：分账到个人账户不支持回退，分账接收方需要开启分账回退授权）'];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        $type = self::get_alipay_account_type($account);

        try{
            $this->service->relation_bind($type, $account, $name);
            return ['code'=>0, 'msg'=>'添加分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //删除分账接收方
    public function deleteReceiver($account){
        $type = self::get_alipay_account_type($account);

        try{
            $this->service->relation_unbind($type, $account);
            return ['code'=>0, 'msg'=>'删除分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    private static function get_alipay_account_type($account){
        if(is_numeric($account) && substr($account,0,4)=='2088' && strlen($account)==16)$type = 'userId';
	    else $type = 'loginName';
        return $type;
    }
}