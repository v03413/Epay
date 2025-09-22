<?php
use Exception;

/**
 * @see https://apifox.com/apidoc/shared/9758ecc8-2c38-4ec6-914f-b09be6f563bc
 */
class EasypayClient
{
	private $gateway_url = 'https://phoenix.eycard.cn/yqt';
	private $reqId;
	private $reqType;
	private $easypay_public_key;
	private $mch_rsa_private_key;

	public $request_body;
    public $response_body;

	public function __construct($reqId, $reqType, $easypay_public_key, $mch_rsa_private_key, $isTest = false)
	{
		$this->reqId = $reqId;
		$this->reqType = $reqType;
		$this->easypay_public_key = $easypay_public_key;
		$this->mch_rsa_private_key = $mch_rsa_private_key;
		if($isTest){
			$this->gateway_url = 'https://d-phoenix-gap.easypay.com.cn:24443/yqt';
		}
	}

	//发起API请求
	public function execute($path, $data){
		$requrl = $this->gateway_url . $path;
		$params = [
			'reqBody' => $data,
			'reqHeader' => [
				'transTime' => date('YmdHis'),
				'reqId' => $this->reqId,
				'reqType' => $this->reqType,
			],
		];
		$params['reqSign'] = $this->generateSign($params['reqHeader'], $params['reqBody']);

		$body = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$this->request_body = $body;
		$response = get_curl($requrl, $body, 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$this->response_body = $response;
		$result = json_decode($response, true);
		if(isset($result['rspHeader']['rspCode']) && $result['rspHeader']['rspCode']=='000000'){
			if(!$this->verifySign($result['rspHeader'], $result['rspBody'], $result['rspSign'])){
				throw new Exception('返回数据验签失败');
			}
			return $result['rspBody'];
		}elseif(isset($result['rspHeader']['rspInfo'])){
			throw new Exception('['.$result['rspHeader']['rspCode'].']'.$result['rspHeader']['rspInfo']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//获取待签名字符串
	private function getSignContent($header, $body){
		$sortHeader = $this->sortJSON($header);
		$sortBody = $this->sortJSON($body);
		$sortHeader = json_encode($sortHeader, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
		$sortBody = json_encode($sortBody, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
		$signstr = $sortHeader . strtoupper(md5($sortBody));
		return $signstr;
	}

	//请求参数签名
	private function generateSign($header, $body){
		return $this->rsaPrivateSign($this->getSignContent($header, $body));
	}

	//验签方法
	public function verifySign($header, $body, $sign){
		if(empty($sign)) return false;
		return $this->rsaPubilcVerify($this->getSignContent($header, $body), $sign);
	}

	private function isJSONObject($value) {
		return is_array($value) && array_keys($value) !== range(0, count($value) - 1);
	}

	private function isJSONArray($value) {
		return is_array($value) && array_keys($value) === range(0, count($value) - 1);
	}

	private function sortArray($array) {
		$sortedArray = [];
		foreach ($array as $item) {
			$sortedArray[] = $this->sortJSON($item);
		}
		return $sortedArray;
	}

	private function sortJSON($param) {
		if ($this->isJSONArray($param)) {
			return $this->sortArray($param);
		}

		$keys = array_keys($param);
		sort($keys, SORT_STRING);

		$paramPair = [];
		foreach ($keys as $key) {
			if ($this->isJSONArray($param[$key])) {
				$paramPair[$key] = $this->sortArray($param[$key]);
			} elseif ($this->isJSONObject($param[$key])) {
				$paramPair[$key] = $this->sortJSON($param[$key]);
			} else {
				$paramPair[$key] = $param[$key];
			}
		}

		return $paramPair;
	}

	//商户私钥签名
	private function rsaPrivateSign($data){
		$priKey = str_replace(["\n", "\r"], '', $this->mch_rsa_private_key);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
		$pkeyid = openssl_pkey_get_private($res);
		if(!$pkeyid){
			throw new Exception('签名失败，商户私钥不正确');
		}
		openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
		$signature = base64_encode($signature);
		return $signature;
	}

	//平台公钥验签
	private function rsaPubilcVerify($data, $signature){
		$pubKey = str_replace(["\n", "\r"], '', $this->easypay_public_key);
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
		$pubkeyid = openssl_pkey_get_public($res);
		if(!$pubkeyid){
			throw new Exception('验签失败，易生公钥不正确');
		}
		$result = openssl_verify($data, base64_decode($signature), $pubkeyid, OPENSSL_ALGO_SHA256);
		return $result === 1;
	}

}