<?php

namespace lib\ProfitSharing;

class Common implements IProfitSharing
{

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
        return ['code'=>-1,'msg'=>'不支持当前操作'];
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