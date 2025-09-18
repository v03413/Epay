<?php

namespace lib\ProfitSharing;

use Exception;

class Haipay implements IProfitSharing
{

    static $paytype = 'haipay';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $order_money, $info){
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $rdata){
        $channel = $this->channel;

        require_once PLUGIN_ROOT.'haipay/inc/HaiPayClient.php';

        $client = new \HaiPayClient($channel['accessid'], $channel['accesskey']);

        $params = [
            'agent_apply_no' => date('YmdHis').rand(1000,9999),
            'agent_no' => $channel['agent_no'],
            'merch_no' => $channel['merch_no'],
            'trade_Id' => $api_trade_no,
            'collection_type' => '1',
        ];
        try{
            $client->mchRequest('/api/v1/merchant-ledger/collection', $params);
            return ['code'=>0];
        }catch(Exception $e){
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        global $DB;
        return ['code'=>0, 'msg'=>'添加分账接收方成功'];
        if($DB->getRow("SHOW TABLES LIKE 'pre_haipay_relation'")){
            if($DB->find("haipay_relation", ['account'=>$account])){
                return ['code'=>0, 'msg'=>'添加分账接收方成功'];
            }
        }
        return ['code'=>-1, 'msg'=>'请先在“进件商户管理-分账关系管理”创建分账关系'];
    }

    //删除分账接收方
    public function deleteReceiver($account){
        return ['code'=>0, 'msg'=>'删除分账接收方成功'];
    }
}