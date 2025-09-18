<?php
/**
 * @see https://paas.tianquetech.com/
 */

class Suixingpay
{
	private $gateway = 'https://openapi.tianquetech.com';
	private $sign_type = 'RSA';
	private $version = '1.0';
	private $org_id;
	private $platform_public_key;
	private $merchant_private_key;

	public function __construct($org_id, $platform_public_key, $merchant_private_key)
	{
		$this->org_id = $org_id;
		$this->platform_public_key = $platform_public_key;
		$this->merchant_private_key = $merchant_private_key;
	}

	//发起API请求
	public function submit($url, $data){
		$apiurl = $this->gateway.$url;
		$params = [
			'orgId' => $this->org_id,
			'reqId' => $this->getMillisecond(),
			'reqData' => $data,
			'timestamp' => date('YmdHis'),
			'version' => $this->version,
			'signType' => $this->sign_type,
		];

		$params['sign'] = $this->generateSign($params);

		$response = get_curl($apiurl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		if(!$response){
			throw new Exception('请求接口失败');
		}
		$result = json_decode($response, true);
		if (isset($result['code']) && $result['code'] == '0000') {
			if(!$this->verifySign($result)) throw new Exception('返回数据验签失败');
			return $result['respData'];
		} elseif(isset($result['msg'])) {
			throw new Exception($result['msg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//获取待签名字符串
	private function getSignContent($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!==null && $v!==''){
				if(is_array($v)){
					$signstr .= $k.'='.json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).'&';
				}else{
					$signstr .= $k.'='.$v.'&';
				}
			}
		}
		$signstr = substr($signstr,0,-1);
		return $signstr;
	}

	//请求参数签名
	private function generateSign($param){
		return $this->rsaPrivateSign($this->getSignContent($param));
	}

	//验签方法
	public function verifySign($param){
		if(empty($param['sign'])) return false;
		return $this->rsaPubilcSign($this->getSignContent($param), $param['sign']);
	}

	//商户私钥签名
	private function rsaPrivateSign($data){
		$key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->merchant_private_key, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
		$res = openssl_pkey_get_private($key);
		if(!$res){
			throw new Exception('签名失败，商户私钥错误');
		}
		openssl_sign($data, $sign, $res);
		$sign = base64_encode($sign);
		return $sign;
	}

	//平台公钥验签
	private function rsaPubilcSign($data, $sign){
		$key = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->platform_public_key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
		$res = openssl_pkey_get_public($key);
		if(!$res){
			throw new Exception('验签失败，平台公钥错误');
		}
		$result = openssl_verify($data, base64_decode($sign), $res);
		return $result;
	}

	private function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

}