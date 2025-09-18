<?php
namespace lib\ProfitSharing;

interface IProfitSharing
{
    
    //请求分账
    function submit($trade_no, $api_trade_no, $order_money, $info);

    //查询分账结果
    function query($trade_no, $api_trade_no, $settle_no);

    //解冻剩余资金
    function unfreeeze($trade_no, $api_trade_no);

    //分账回退
    function return($trade_no, $api_trade_no, $rdata);

    //添加分账接收方
    function addReceiver($account, $name = null);

    //删除分账接收方
    function deleteReceiver($account);

}