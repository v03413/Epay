<?php

namespace Alipay;

use Exception;

/**
 * 支付宝账单服务类
 * @see https://opendocs.alipay.com/open/01inem
 */
class AlipayBillService extends AlipayService
{
    public function __construct($config)
    {
        parent::__construct($config);
    }

	/**
	 * 账户卖出交易查询
	 * @param string $start_time 创建时间的起始
	 * @param string $end_time 创建时间的结束
	 * @param int $page_no 分页号，从1开始
	 * @param int $page_size 分页大小1000-2000，默认2000
	 * @return mixed {"page_no":"1","page_size":"2000","total_size":"10000","detail_list":[]}
	 * @throws Exception
	 */
    public function sellQuery(string $start_time, string $end_time, int $page_no = 1, int $page_size = 2000)
    {
        $apiName = 'alipay.data.bill.sell.query';
        $bizContent = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page_no' => $page_no,
            'page_size' => $page_size,
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 账户买入交易查询
	 * @param string $start_time 创建时间的起始
	 * @param string $end_time 创建时间的结束
	 * @param int $page_no 分页号，从1开始
	 * @param int $page_size 分页大小1000-2000，默认2000
	 * @return mixed {"page_no":"1","page_size":"2000","total_size":"10000","detail_list":[]}
	 * @throws Exception
	 */
    public function buyQuery(string $start_time, string $end_time, int $page_no = 1, int $page_size = 2000)
    {
        $apiName = 'alipay.data.bill.buy.query';
        $bizContent = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page_no' => $page_no,
            'page_size' => $page_size,
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 账户账务明细查询
	 * @param string $start_time 创建时间的起始
	 * @param string $end_time 创建时间的结束
	 * @param int $page_no 分页号，从1开始
	 * @param int $page_size 分页大小1000-2000，默认2000
	 * @param null $bill_user_id 指定用户做账单查询
	 * @return mixed {"page_no":"1","page_size":"2000","total_size":"10000","detail_list":[]}
	 * @throws Exception
	 */
    public function accountlogQuery(string $start_time, string $end_time, int $page_no = 1, int $page_size = 2000, $bill_user_id = null)
    {
        $apiName = 'alipay.data.bill.accountlog.query';
        $bizContent = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page_no' => $page_no,
            'page_size' => $page_size,
        ];
        if ($bill_user_id) $bizContent['bill_user_id'] = $bill_user_id;
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 账户充值，转账，提现查询
	 * @param string $start_time 创建时间的起始
	 * @param string $end_time 创建时间的结束
	 * @param int $page_no 分页号，从1开始
	 * @param int $page_size 分页大小1000-2000，默认2000
	 * @return mixed {"page_no":"1","page_size":"2000","total_size":"10000","detail_list":[]}
	 * @throws Exception
	 */
    public function transferQuery(string $start_time, string $end_time, int $page_no = 1, int $page_size = 2000)
    {
        $apiName = 'alipay.data.bill.transfer.query';
        $bizContent = [
            'start_time' => $start_time,
            'end_time' => $end_time,
            'page_no' => $page_no,
            'page_size' => $page_size,
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 账户当前余额查询
	 * @return mixed {"total_amount":"支付宝账户余额","available_amount":"账户可用余额","freeze_amount":"冻结金额","settle_amount":"待结算金额"}
	 * @throws Exception
	 */
    public function balanceQuery()
    {
        $apiName = 'alipay.data.bill.balance.query';
        return $this->aopExecute($apiName);
    }

	/**
	 * 申请电子回单
	 * @param string $type 申请的类型
	 * @param string $key 根据不同业务类型，传入不同参数
	 * @return mixed {"file_id":"文件申请号"}
	 * @throws Exception
	 */
    public function ereceiptApply(string $type, string $key)
    {
        $apiName = 'alipay.data.bill.ereceipt.apply';
        $bizContent = [
            'type' => $type,
            'key' => $key,
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 查询电子回单状态
	 * @param string $file_id 文件申请号
	 * @return mixed {"status":"处理状态","download_url":"下载链接","error_message":"失败原因"}
	 * @throws Exception
	 */
    public function ereceiptQuery(string $file_id)
    {
        $apiName = 'alipay.data.bill.ereceipt.query';
        $bizContent = [
            'file_id' => $file_id
        ];
        return $this->aopExecute($apiName, $bizContent);
    }
}