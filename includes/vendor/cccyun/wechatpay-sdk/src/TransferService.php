<?php

namespace WeChatPay;

use Exception;

/**
 * 转账服务类
 * @see https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay.php?chapter=14_1
 * @see https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay_yhk.php?chapter=25_1
 */
class TransferService extends BaseService
{
    //加密公钥
    private $publicKeyPath;

    public function __construct($config)
    {
        parent::__construct($config);

        $this->publicKeyPath = $config['publickey_path'];
        $this->publicParams = [
            'nonce_str'  => $this->getNonceStr(),
        ];
    }

	/**
	 * 企业付款到零钱
	 * @param string $partner_trade_no 商户唯一订单号
	 * @param string $openid 用户openid
	 * @param string $name 用户姓名(填写后校验)
	 * @param numeric $amount 金额
	 * @param string $desc 备注
	 * @return mixed {"partner_trade_no":"商户唯一订单号","payment_no":"微信付款单号","payment_time":"付款成功时间"}
	 * @throws Exception
	 */
    public function transfer(string $partner_trade_no, string $openid, string $name, $amount, string $desc)
    {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
        $params = [
            'mch_appid' => $this->appId,
            'mchid' => $this->mchId,
            'partner_trade_no' => $partner_trade_no,
            'openid' => $openid,
            'amount' => $amount,
            'desc' => $desc
        ];
        if (!empty($name)) {
            $params['check_name'] = 'FORCE_CHECK';
            $params['re_user_name'] = $name;
        }
        return $this->execute($url, $params, true);
    }

	/**
	 * 查询付款
	 * @param string $partner_trade_no 商户唯一订单号
	 * @return mixed {"partner_trade_no":"商户唯一订单号","detail_id":"微信付款单号","status":"转账状态","reason":"失败原因","openid":"用户openid","transfer_name":"用户姓名","payment_amount":"付款金额","transfer_time":"转账时间","payment_time":"付款成功时间","desc":"付款备注"}
	 * @throws Exception
	 */
    public function transferQuery(string $partner_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gettransferinfo';
        $params = [
            'mch_appid' => $this->appId,
            'mchid' => $this->mchId,
            'partner_trade_no' => $partner_trade_no
        ];
        return $this->execute($url, $params);
    }

    /**
     * 企业付款到银行卡
     * @param string $partner_trade_no 商户唯一订单号
     * @param string $bank_no 收款方银行卡号
     * @param string $name 收款方用户名
     * @param string $bank_code 收款方开户行(https://pay.weixin.qq.com/wiki/doc/api/tools/mch_pay_yhk.php?chapter=24_4)
     * @param numeric $amount 金额
     * @param string $desc 备注
     * @return mixed {"partner_trade_no":"商户唯一订单号","payment_no":"微信付款单号","cmms_amt":"手续费金额"}
     * @throws Exception
     */
    public function transferToBank(string $partner_trade_no, string $bank_no, string $name, string $bank_code, $amount, string $desc)
    {
        $pubKey = null;
        if (empty($this->publicKeyPath)) {
            throw new Exception('RSA加密公钥路径不能为空');
        }
        if (file_exists($this->publicKeyPath)) {
            $pubKey = file_get_contents($this->publicKeyPath);
        }
        if (!$pubKey) {
            $pubKey = $this->getPublicKey();
            if (!file_put_contents($this->publicKeyPath, $pubKey)) {
                throw new Exception('RSA加密公钥文件写入失败');
            }
        }
        $pubkeyid = openssl_pkey_get_public($pubKey);
        if (!$pubkeyid) {
            throw new Exception('RSA加密公钥不正确');
        }

        $url = 'https://api.mch.weixin.qq.com/mmpaysptrans/pay_bank';
        $params = [
            'mch_id' => $this->mchId,
            'partner_trade_no' => $partner_trade_no,
            'enc_bank_no' => $this->encryptData($bank_no, $pubkeyid),
            'enc_true_name' => $this->encryptData($name, $pubkeyid),
            'bank_code' => $bank_code,
            'amount' => $amount,
            'desc' => $desc
        ];
        return $this->execute($url, $params, true);
    }

    /**
     * 查询付款银行卡
     * @param string $partner_trade_no 商户唯一订单号
     * @return mixed {"partner_trade_no":"商户唯一订单号","payment_no":"微信付款单号","bank_no_md5":"收款用户银行卡号(MD5加密)","true_name_md5":"收款人真实姓名(MD5加密)","amount":"金额","cmms_amt":"手续费金额","status":"转账状态","create_time":"商户下单时间","pay_succ_time":"成功付款时间","reason":"失败原因"}
     * @throws Exception
     */
    public function queryBank(string $partner_trade_no)
    {
        $url = 'https://api.mch.weixin.qq.com/mmpaysptrans/query_bank';
        $params = [
            'mch_id' => $this->mchId,
            'partner_trade_no' => $partner_trade_no
        ];
        return $this->execute($url, $params);
    }

    /**
     * 获取RSA加密公钥
     * @throws Exception
     */
    public function getPublicKey()
    {
        $url = 'https://fraud.mch.weixin.qq.com/risk/getpublickey';
        $params = [
            'mch_id' => $this->mchId,
        ];
        $result = $this->execute($url, $params, true);
        $pub_key = $result['pub_key'];
        $pub_key = RsaTool::pkcs1ToSpkiPem($pub_key);
        return $pub_key;
    }

	/**
	 * RSA加密
	 * @param string $data
	 * @param $pubKey
	 * @return string
	 * @throws Exception
	 */
    private function encryptData(string $data, $pubkeyid): string
    {
        openssl_public_encrypt($data, $encrypted, $pubkeyid, OPENSSL_PKCS1_OAEP_PADDING);
	    return base64_encode($encrypted);
    }
}
