<?php

namespace Alipay;

use Alipay\Aop\AopClient;
use Alipay\Aop\AlipayCertHelper;
use Alipay\Aop\AlipayRequest;
use Alipay\Aop\AlipayResponseException;
use Exception;
use InvalidArgumentException;

class AlipayService
{
	//AopClient
	protected $client;

	//是否公钥证书模式
	protected $isCertMode = false;

	//应用ID
	protected $appId;

	//异步通知回调地址
	protected $notifyUrl;

	//同步通知回调地址
	protected $returnUrl;

	//服务商模式子商户token
	protected $appAuthToken;

	//日志文件夹路径
	protected $logPath;

	//页面跳转接口返回类型
	protected $pageMethod;

	/**
	 * @param array $config 支付宝配置信息
	 * @throws InvalidArgumentException
	 */
	public function __construct(array $config)
	{
		if (empty($config['app_id'])) {
			throw new InvalidArgumentException('应用AppID不能为空');
		}
		if (empty($config['app_private_key'])) {
			throw new InvalidArgumentException("应用私钥不能为空");
		}
		if (empty($config['alipay_public_key']) && empty($config['alipay_cert_path'])) {
			throw new InvalidArgumentException("支付宝公钥不能为空");
		}
		$this->appId = $config['app_id'];
		if (!empty($config['app_cert_path']) && !empty($config['alipay_cert_path']) && !empty($config['root_cert_path']) && (!isset($config['cert_mode']) || $config['cert_mode'] == 1)) {
			$this->isCertMode = true;
		}
		if (isset($config['app_auth_token'])) {
			$this->appAuthToken = $config['app_auth_token'];
		}
		if (isset($config['logPath'])) {
			$this->logPath = $config['logPath'];
		}
		if (isset($config['pageMethod'])) {
			$this->pageMethod = $config['pageMethod'];
		}

		$this->client = new AopClient();
		$this->client->appId = $config['app_id'];
		if (!empty($config['gateway_url'])) {
			$this->client->gatewayUrl = $config['gateway_url'];
		}
		if (!empty($config['sign_type'])) {
			$this->client->signType = $config['sign_type'];
		}
		if (!empty($config['charset'])) {
			$this->client->charset = $config['charset'];
		}

		$this->client->rsaPrivateKey = $config['app_private_key'];
		if ($this->isCertMode) {
			$this->client->rsaPublicKeyFilePath = $config['alipay_cert_path'];
			$this->client->appCertSN = AlipayCertHelper::getCertSN($config['app_cert_path']);
			$this->client->alipayRootCertSN = AlipayCertHelper::getRootCertSN($config['root_cert_path']);
		} else {
			$this->client->rsaPublicKey = $config['alipay_public_key'];
		}

	}

	/**
	 * 发起接口请求
	 *
	 * @param string $apiName 接口名称
	 * @param array|null $bizContent 请求参数的集合
	 * @param array|null $params 其他公共参数
	 * @return mixed
	 * @throws Exception
	 */
	public function aopExecute(string $apiName, array $bizContent = null, array $params = null)
	{
		$request = new AlipayRequest();
		$request->setApiMethodName($apiName);
		$request->setNotifyUrl($this->notifyUrl);
		$request->setAppAuthToken($this->appAuthToken);
		$request->setBizContent($bizContent);
		if (is_array($params) && count($params) > 0) {
			$request->setOtherParams($params);
		}
		$result = $this->client->execute($request)->getData();
		if ($apiName == 'alipay.system.oauth.token' && isset($result['access_token'])) {
			return $result;
		} elseif (isset($result['code']) && $result['code'] == '10000') {
			return $result;
		} else {
			throw new AlipayResponseException($result);
		}
	}

	/**
	 * 页面跳转接口，返回form表单html
	 *
	 * @param string $apiName 接口名称
	 * @param array|null $bizContent 请求参数的集合
	 * @param array|null $params 其他公共参数
	 * @return string
	 * @throws Exception
	 */
	public function aopPageExecute(string $apiName, array $bizContent = null, array $params = null): string
	{
		$request = new AlipayRequest();
		$request->setApiMethodName($apiName);
		$request->setNotifyUrl($this->notifyUrl);
		$request->setReturnUrl($this->returnUrl);
		$request->setAppAuthToken($this->appAuthToken);
		$request->setBizContent($bizContent);
		if (is_array($params) && count($params) > 0) {
			$request->setOtherParams($params);
		}
		if (!empty($this->pageMethod)) {
			switch ($this->pageMethod) {
				case '2':
					$httpmethod = 'REDIRECT';
					break;
				case '1':
					$httpmethod = 'GET';
					break;
				default:
					$httpmethod = 'POST';
					break;
			}
			return $this->client->pageExecute($request, $httpmethod);
		} else {
			return $this->client->pageExecute($request);
		}
	}

	/**
	 * APP接口，返回收银台SDK的字符串
	 *
	 * @param string $apiName 接口名称
	 * @param array|null $bizContent 请求参数的集合
	 * @param array|null $params 其他公共参数
	 * @return string
	 */
	public function aopSdkExecute(string $apiName, array $bizContent = null, array $params = null): string
	{
		$request = new AlipayRequest();
		$request->setApiMethodName($apiName);
		$request->setNotifyUrl($this->notifyUrl);
		$request->setAppAuthToken($this->appAuthToken);
		$request->setBizContent($bizContent);
		if (is_array($params) && count($params) > 0) {
			$request->setOtherParams($params);
		}
		return $this->client->sdkExecute($request);
	}

	/**
	 * 回调验签
	 * @param array $params 支付宝返回的信息
	 * @return bool
	 */
	public function check(array $params): bool
	{
		return $this->client->verify($params);
	}

	/**
	 * 记录日志
	 */
	public function writeLog($text)
	{
		if (empty($this->logPath)) return;
		//$text=iconv("GBK", "UTF-8//IGNORE", $text);
		file_put_contents($this->logPath . "log.txt", date("Y-m-d H:i:s") . "  " . $text . "\r\n", FILE_APPEND);
	}
}