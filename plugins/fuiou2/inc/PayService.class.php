<?php

/**
 * https://fundwx.fuiou.com/doc/#/
 */
class PayService
{
	private $version = '1.0';
	private $ins_cd;
	private $mchnt_cd;
	private $platform_public_key;
	private $merchant_private_key;
	private $xml;
	private $gateway_url = 'https://spay-mc.fuioupay.com';

	public function __construct($ins_cd, $mchnt_cd, $platform_public_key, $merchant_private_key, $is_test = false)
	{
		$this->ins_cd = $ins_cd;
		$this->mchnt_cd = $mchnt_cd;
		$this->platform_public_key = $platform_public_key;
		$this->merchant_private_key = $merchant_private_key;
		if($is_test){
			$this->gateway_url = 'https://fundwx.fuiou.com';
		}
		$this->xml = new XmlWriter();
	}

	//发起API请求
	public function submit($path, $params){
		$requrl = $this->gateway_url.$path;
		$public_params = [
			'version' => $this->version,
			'ins_cd' => $this->ins_cd,
			'mchnt_cd' => $this->mchnt_cd,
			'term_id' => '88888888',
			'random_str' => getSid(),
		];

		foreach($params as $key=>$value){
			if($value) $params[$key] = mb_convert_encoding($params[$key], 'GBK', 'UTF-8');
		}

		$params = array_merge($public_params, $params);
		$params['sign'] = $this->generateSign($params);

		$xml = "<?xml version=\"1.0\" encoding=\"GBK\" standalone=\"yes\"?><xml>".$this->toXml($params)."</xml>";

		$response = get_curl($requrl, 'req='.urlencode(urlencode($xml)));
		$response = urldecode($response);
		LIBXML_VERSION < 20900 && libxml_disable_entity_loader(true);
		$result = json_decode(json_encode(simplexml_load_string($response)), true);

		if(isset($result['result_code']) && ($result['result_code']=='000000' || $result['result_code']=='030010')){
			if(!$this->verifyResponse($result)){
				throw new Exception('返回数据验签失败');
			}
			return $result;
		}elseif(isset($result['result_msg'])){
			throw new Exception($result['result_msg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//获取待签名字符串
	private function getSignContent($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && substr($k,0,8)!='reserved'){
				if(is_array($v))$v = '';
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		return $signstr;
	}

	//请求参数签名
	private function generateSign($param){
		return $this->rsaPrivateSign($this->getSignContent($param));
	}

	//返回数据验签
	private function verifyResponse($param){
		if(empty($param['sign'])) return false;
		foreach($param as $key=>$value){
			if(is_array($value)) $param[$key] = '';
			else $param[$key] = mb_convert_encoding($param[$key], 'GBK', 'UTF-8');
		}
		return $this->verifySign($param);
	}

	//验签方法
	public function verifySign($param){
		if(empty($param['sign'])) return false;
		return $this->rsaPubilcSign($this->getSignContent($param), $param['sign']);
	}

	//商户私钥签名
	private function rsaPrivateSign($data){
		$priKey = str_replace(array("\r\n", "\r", "\n"), "", $this->merchant_private_key);
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
		$pkeyid = openssl_pkey_get_private($res);
		if(!$pkeyid){
			throw new Exception('签名失败，商户私钥不正确');
		}
		openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_MD5);
		$signature = base64_encode($signature);
		return $signature;
	}

	//平台公钥验签
	private function rsaPubilcSign($data, $signature){
		$pubKey = str_replace(array("\r\n", "\r", "\n"), "", $this->platform_public_key);
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
		$pubkeyid = openssl_pkey_get_public($res);
		if(!$pubkeyid){
			throw new Exception('验签失败，富友公钥不正确');
		}
		$result = openssl_verify($data, base64_decode($signature), $pubkeyid, OPENSSL_ALGO_MD5);
		return $result;
	}

	private function toXml($data, $eIsArray=FALSE) {
		if(!$eIsArray) {
		  $this->xml->openMemory(); 
		}
		foreach($data as $key => $value){ 
		  if(is_array($value)){
			$this->xml->startElement($key);
			$this->toXml($value, TRUE);
			$this->xml->endElement();
			continue;
		  }
		  $this->xml->writeElement($key, $value);
		}
		if(!$eIsArray) {
		  $this->xml->endElement();
		  return $this->xml->outputMemory(true);
		}
	}

}