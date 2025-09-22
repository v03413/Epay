<?php

namespace Alipay;

use Exception;

/**
 * 支付宝身份认证服务类
 * @see https://opendocs.alipay.com/open/repo-013ubq
 */
class AlipayCertifyService extends AlipayService
{
    //认证成功返回页面
    private $return_url;

    /**
     * @param array $config 支付宝配置信息
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->return_url = $config['return_url'];
    }

	/**
	 * 身份认证初始化服务
	 * @param string $outer_order_no 商户请求的唯一标识
	 * @param string $cert_name 真实姓名
	 * @param string $cert_no 证件号码
	 * @param string $cert_type 证件类型
	 * @param string $biz_code 认证场景码（FACE、SMART_FACE）
	 * @return mixed {"code":"10000","msg":"Success","certify_id":"本次申请操作的唯一标识"}
	 *
	 * @throws Exception
	 * @see https://opendocs.alipay.com/open/02ahjy
	 */
    public function initialize(string $outer_order_no, string $cert_name, string $cert_no, string $cert_type = 'IDENTITY_CARD', string $biz_code = 'SMART_FACE')
    {
        $apiName = 'alipay.user.certify.open.initialize';
        $bizContent = [
            'outer_order_no' => $outer_order_no, //商户请求的唯一标识
            'biz_code' => $biz_code, //认证场景码
            'identity_param' => [
                'identity_type' => 'CERT_INFO', //身份信息参数类型
                'cert_type' => $cert_type, //证件类型
                'cert_name' => $cert_name, //真实姓名
                'cert_no' => $cert_no, //证件号码
            ],
            'merchant_config' => ['return_url'=>$this->return_url], //商户个性化配置
        ];
        return $this->aopExecute($apiName, $bizContent);
    }

	/**
	 * 身份认证开始认证
	 * @param string $certify_id 本次申请操作的唯一标识
	 * @return string html表单
	 * @throws Exception
	 */
    public function certify(string $certify_id): string
    {
        $apiName = 'alipay.user.certify.open.certify';
        $bizContent = array(
            'certify_id' => $certify_id,
        );
        return $this->aopPageExecute($apiName, $bizContent);
    }

	/**
	 * 身份认证记录查询
	 * @param string $certify_id 本次申请操作的唯一标识
	 * @return mixed {"code":"10000","msg":"Success","passed":"T"}
	 * @throws Exception
	 */
    public function query(string $certify_id)
    {
        $apiName = 'alipay.user.certify.open.query';
        $bizContent = array(
            'certify_id' => $certify_id,
        );
        return $this->aopExecute($apiName, $bizContent);
    }
}