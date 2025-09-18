<?php

namespace Alipay;

use Exception;

/**
 * 支付宝转账服务类
 * @see https://opendocs.alipay.com/open/309/106235
 */
class AlipayTransferService extends AlipayService
{
    /**
     * @param array $config 支付宝配置信息
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
    }

	/**
	 * 转账到支付宝账号
	 * @param string $out_biz_no 商户转账唯一订单号
	 * @param numeric $amount 转账金额
	 * @param int $is_userid 收款方是否支付宝userid（0支付宝账号,1支付宝UID,2支付宝openid）
	 * @param string $payee_account 收款方账户
	 * @param string $payee_real_name 收款方姓名
	 * @param string $payer_show_name 付款方显示姓名
	 * @return mixed {"out_biz_no":"商户订单号","order_id":"支付宝转账订单号","pay_fund_order_id":"支付宝支付资金流水号","status":"SUCCESS","trans_date":"订单支付时间"}
	 * @throws Exception
	 */
    public function transferToAccount(string $out_biz_no, $amount, int $is_userid, string $payee_account, string $payee_real_name, string $payer_show_name)
    {
        if ($this->isCertMode) {
            $apiName = 'alipay.fund.trans.uni.transfer';
            switch($is_userid) {
                case 2:$payee_type = 'ALIPAY_OPEN_ID';break;
                case 1:$payee_type = 'ALIPAY_USER_ID';break;
                default:$payee_type = 'ALIPAY_LOGON_ID';break;
            }
            $bizContent = [
                'out_biz_no' => $out_biz_no, //商户转账唯一订单号
                'trans_amount' => $amount, //转账金额
                'product_code' => 'TRANS_ACCOUNT_NO_PWD',
                'biz_scene' => 'DIRECT_TRANSFER',
                'order_title' => $payer_show_name, //付款方显示名称
                'payee_info' => array('identity' => $payee_account, 'identity_type' => $payee_type),
                'business_params' => json_encode(['payer_show_name_use_alias'=>'true']),
            ];
            if(!empty($payee_real_name))$bizContent['payee_info']['name'] = $payee_real_name; //收款方真实姓名
        } else {
            $apiName = 'alipay.fund.trans.toaccount.transfer';
            $payee_type = $is_userid?'ALIPAY_USERID':'ALIPAY_LOGONID';
            $bizContent = [
                'out_biz_no' => $out_biz_no, //商户转账唯一订单号
                'payee_type' => $payee_type, //收款方账户类型
                'payee_account' => $payee_account, //收款方账户
                'amount' => $amount, //转账金额
                'payer_show_name' => $payer_show_name, //付款方显示姓名
            ];
            if(!empty($payee_real_name))$bizContent['payee_real_name'] = $payee_real_name; //收款方真实姓名
        }

        $result = $this->aopExecute($apiName, $bizContent);
        if(isset($result['pay_date'])) $result['trans_date'] = $result['pay_date'];
        return $result;
    }

	/**
	 * 转账到银行卡账户
	 * @param string $out_biz_no 商户转账唯一订单号
	 * @param numeric $amount 转账金额
	 * @param string $payee_account 收款方账户
	 * @param string $payee_real_name 收款方姓名
	 * @param string $payer_show_name 付款方显示姓名
	 * @return mixed {"out_biz_no":"商户订单号","order_id":"支付宝转账订单号","pay_fund_order_id":"支付宝支付资金流水号","status":"SUCCESS","trans_date":"订单支付时间"}
	 * @throws Exception
	 */
    public function transferToBankCard(string $out_biz_no, $amount, string $payee_account, string $payee_real_name, string $payer_show_name)
    {
        $apiName = 'alipay.fund.trans.uni.transfer';
        $bizContent = [
            'out_biz_no' => $out_biz_no, //商户转账唯一订单号
            'trans_amount' => $amount, //转账金额
            'product_code' => 'TRANS_BANKCARD_NO_PWD',
            'biz_scene' => 'DIRECT_TRANSFER',
            'order_title' => $payer_show_name, //付款方显示名称
            'payee_info' => array(
                'identity_type' => 'BANKCARD_ACCOUNT',
                'identity' => $payee_account,
                'name' => $payee_real_name,
                'bankcard_ext_info' => array(
                    'account_type' => '2'
                )
            ),
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 转账单据查询
	 * @param string $order_id 订单号
	 * @param int $type 订单号类型(0=支付宝转账单据号,1=支付宝支付资金流水号,2=商户转账唯一订单号)
	 * @param int $code 产品类型(0=转账到支付宝账户,1=转账到银行卡)
	 * @return mixed {"order_id":"支付宝转账单据号","pay_fund_order_id":"支付宝支付资金流水号","out_biz_no":"商户转账唯一订单号","trans_amount":1,"status":"SUCCESS","pay_date":"支付时间","error_code":"PAYEE_CARD_INFO_ERROR","fail_reason":"收款方银行卡信息有误"}
	 * @throws Exception
	 */
    public function query(string $order_id, int $type=0, int $code = 0)
    {
        $apiName = 'alipay.fund.trans.common.query';
        $bizContent = [];
        if($type==1){
            $bizContent['pay_fund_order_id'] = $order_id;
        }elseif($type==2){
            $bizContent['out_biz_no'] = $order_id;
        }else{
            $bizContent['order_id'] = $order_id;
        }
        if($type==2){
            $bizContent['product_code'] = $code == 1 ? 'TRANS_BANKCARD_NO_PWD' : 'TRANS_ACCOUNT_NO_PWD';
            $bizContent['biz_scene'] = 'DIRECT_TRANSFER';
        }
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 账户余额查询
	 * @param string $alipay_user_id 支付宝用户ID
	 * @param int $user_type 用户标识类型（0支付宝UID,1支付宝openid）
	 * @return mixed {"available_amount":"账户可用余额","freeze_amount":"实时冻结余额"}
	 * @throws Exception
	 */
    public function accountQuery(string $alipay_user_id = null, int $user_type = 0)
    {
        $apiName = 'alipay.fund.account.query';
        if($user_type == 1){
            $bizContent = [
                'alipay_open_id' => $alipay_user_id,
                'account_type' => 'ACCTRANS_ACCOUNT',
            ];
        }else{
            $bizContent = [
                'alipay_user_id' => $alipay_user_id,
                'account_type' => 'ACCTRANS_ACCOUNT',
            ];
        }
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 现金红包转账接口
	 * @param string $out_biz_no 商户转账唯一订单号
	 * @param numeric $amount 转账金额
	 * @param string $user_id 收款方账户
	 * @param string $order_title 转账业务的标题
	 * @param null $original_order_id 原支付宝业务单号
	 * @return mixed {"out_biz_no":"商户订单号","order_id":"支付宝转账订单号","pay_fund_order_id":"支付宝支付资金流水号","status":"SUCCESS","trans_date":"订单支付时间"}
	 * @throws Exception
	 */
    public function redPacketTansfer(string $out_biz_no, $amount, string $user_id, string $order_title, $original_order_id = null)
    {
        $apiName = 'alipay.fund.trans.uni.transfer';
        $bizContent = [
            'out_biz_no' => $out_biz_no,
            'trans_amount' => $amount,
            'product_code' => 'STD_RED_PACKET',
            'biz_scene' => 'PERSONAL_COLLECTION',
            'order_title' => $order_title,
            'payee_info' => array('identity' => $user_id, 'identity_type' => 'ALIPAY_USER_ID'),
            'business_params' => json_encode(['sub_biz_scene'=>'REDPACKET'], JSON_UNESCAPED_UNICODE)
        ];
        if($original_order_id) $bizContent['original_order_id'] = $original_order_id;

	    return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 红包资金退回接口
	 * @param string $out_request_no 标识一次资金退回请求
	 * @param string $order_id 发红包时支付宝返回的支付宝订单号
	 * @param numeric $refund_amount 需要退款的金额
	 * @return mixed {"refund_order_id":"退款的支付宝系统内部单据id","order_id":"发红包时支付宝返回的支付宝订单号","out_request_no":"标识一次资金退回请求","status":"SUCCESS","refund_amount":"本次退款的金额","refund_date":"时间"}
	 * @throws Exception
	 */
    public function redPacketRefund(string $out_request_no, string $order_id, $refund_amount)
    {
        $apiName = 'alipay.fund.trans.refund';
        $bizContent = [
            'order_id' => $order_id,
            'out_request_no' => $out_request_no,
            'refund_amount' => $refund_amount,
        ];

	    return $this->aopExecute($apiName, $bizContent);
    }

}