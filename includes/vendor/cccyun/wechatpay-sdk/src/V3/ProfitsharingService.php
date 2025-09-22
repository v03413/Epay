<?php

namespace WeChatPay\V3;

use Exception;

/**
 * 分账服务类
 * @see https://pay.weixin.qq.com/wiki/doc/apiv3/open/pay/chapter4_1_4.shtml
 */
class ProfitsharingService extends BaseService
{
    public function __construct(array $config)
    {
        parent::__construct($config);
    }


	/**
	 * 添加分账接收方
	 * @param string $type 分账接收方类型
	 * @param string $account 分账接收方账号
	 * @param string|null $name 用户姓名(填写后校验)
	 * @return mixed
	 * @throws Exception
	 */
    public function addReceiver(string $type, string $account, string $name = null)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/receivers/add' : '/v3/profitsharing/receivers/add';
        $params = [
            'appid' => $this->appId,
            'type' => $type,
            'account' => $account,
            'relation_type' => 'SUPPLIER',
        ];
        if (!empty($this->subMchId)) $params['sub_mchid'] = $this->subMchId;
        if (!empty($name)) {
            if ($this->ecommerce) {
                $params['encrypted_name'] = $this->rsaEncrypt($name);
            } else {
                $params['name'] = $this->rsaEncrypt($name);
            }
        }
        return $this->execute('POST', $path, $params, true);
    }

	/**
	 * 删除分账接收方
	 * @param string $type 分账接收方类型
	 * @param string $account 分账接收方账号
	 * @return mixed
	 * @throws Exception
	 */
    public function deleteReceiver($type, string $account)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/receivers/delete' : '/v3/profitsharing/receivers/delete';
        $params = [
            'appid' => $this->appId,
            'type' => $type,
            'account' => $account,
        ];
        if (!empty($this->subMchId)) $params['sub_mchid'] = $this->subMchId;
        return $this->execute('POST', $path, $params);
    }

	/**
	 * 请求分账
	 * @param array $params 请求参数
	 * @return mixed {"transaction_id":"微信订单号","out_order_no":"商户分账单号","order_id":"微信分账单号","status":"分账单状态","receivers":""}
	 * @throws Exception
	 */
    public function submit(array $params)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/orders' : '/v3/profitsharing/orders';
        $publicParams = [
            'appid' => $this->appId,
        ];
        if (!empty($this->subMchId)) $publicParams['sub_mchid'] = $this->subMchId;
        $params = array_merge($publicParams, $params);
        return $this->execute('POST', $path, $params, true);
    }

	/**
	 * 查询分账结果
	 * @param string $out_order_no 商户分账单号
	 * @param string $transaction_id 微信订单号
	 * @return mixed {"transaction_id":"微信订单号","out_order_no":"商户分账单号","order_id":"微信分账单号","status":"分账单状态","receivers":""}
	 * @throws Exception
	 */
    public function query(string $out_order_no, string $transaction_id)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/orders' : '/v3/profitsharing/orders/' . $out_order_no;
        $params = [
            'transaction_id' => $transaction_id,
        ];
        if ($this->ecommerce) {
            $params['out_order_no'] = $out_order_no;
        }
        if (!empty($this->subMchId)) $params['sub_mchid'] = $this->subMchId;
        return $this->execute('GET', $path, $params);
    }

	/**
	 * 解冻剩余资金
	 * @param string $out_order_no 商户分账单号
	 * @param string $transaction_id 微信订单号
	 * @return mixed {"transaction_id":"微信订单号","out_order_no":"商户分账单号","order_id":"微信分账单号"}
	 * @throws Exception
	 */
    public function unfreeze(string $out_order_no, string $transaction_id)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/finish-order' : '/v3/profitsharing/orders/unfreeze';
        $params = [
            'transaction_id' => $transaction_id,
            'out_order_no' => $out_order_no,
            'description' => '取消分账'
        ];
        if (!empty($this->subMchId)) $params['sub_mchid'] = $this->subMchId;
        return $this->execute('POST', $path, $params);
    }

	/**
	 * 查询订单待分账金额
	 * @param string $transaction_id 微信订单号
	 * @return mixed {"transaction_id":"微信订单号","unsplit_amount":"订单剩余待分金额"}
	 * @throws Exception
	 */
    public function orderAmountQuery(string $transaction_id)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/orders/' . $transaction_id . '/amounts' : '/v3/profitsharing/transactions/' . $transaction_id . '/amounts';
        return $this->execute('GET', $path);
    }

	/**
	 * 请求分账回退
	 * @param array $params 请求参数
	 * @return mixed
	 * @throws Exception
	 */
    public function return(array $params)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/returnorders' : '/v3/profitsharing/return-orders';
        if (!empty($this->subMchId)) $params['sub_mchid'] = $this->subMchId;
        return $this->execute('POST', $path, $params);
    }

	/**
	 * 查询分账回退结果
	 * @param string $out_return_no 商户回退单号
	 * @param string $out_order_no 商户分账单号
	 * @return mixed
	 * @throws Exception
	 */
    public function returnQuery(string $out_return_no, string $out_order_no)
    {
        $path = $this->ecommerce ? '/v3/ecommerce/profitsharing/returnorders' : '/v3/profitsharing/return-orders/' . $out_return_no;
        $params = [
            'out_order_no' => $out_order_no
        ];
        if ($this->ecommerce) $params['out_return_no'] = $out_return_no;
        if (!empty($this->subMchId)) $params['sub_mchid'] = $this->subMchId;
        return $this->execute('GET', $path, $params);
    }
}
