<?php

class ChinaumsBuild
{
	private $appid;
	private $appkey;

	// 网关地址（生产环境）
	private $gateway = 'https://api-mop.chinaums.com';

	// 网关地址（测试环境）
	private $gateway_test = 'https://test-api-open.chinaums.com';

	public function __construct($appid, $appkey, $istest = false){
		$this->appid = $appid;
		$this->appkey = $appkey;
		if($istest){
			$this->gateway = $this->gateway_test;
		}
	}

	// 发起支付
	public function request($path, $params, $time){
		$url = $this->gateway.$path;
		$json = json_encode($params);
		$authorization = $this->getOpenBodySig($json, $time);
		$response = $this->httpPost($url, $json, $authorization);
		$arr = json_decode($response, true);
		return $arr;
	}

	// 发起支付GET
	public function requestGet($path, $params, $time){
		$url = $this->gateway.$path;
		$json = json_encode($params);
		$query = [
			'authorization' => 'OPEN-FORM-PARAM',
			'appId' => $this->appid,
			'timestamp' => date('YmdHis', $time),
			'nonce' => md5(uniqid(mt_rand(), true)),
			'content' => $json
		];
		$query['signature'] = $this->getSignature($query['timestamp'], $query['nonce'], $query['content']);
		return $url . '?' . http_build_query($query);
	}

	// 获取Authorization
	private function getOpenBodySig($body, $time){

		$timestamp = date('YmdHis', $time);
		$nonce = md5(uniqid(mt_rand(), true));
		$signature = $this->getSignature($timestamp, $nonce, $body);
		$authorization = 'OPEN-BODY-SIG AppId="'.$this->appid.'", Timestamp="'.$timestamp.'", Nonce="'.$nonce.'", Signature="'.$signature.'"';
		return $authorization;
	}

	// 获取Signature
	private function getSignature($timestamp, $nonce, $body){
		$hash = hash('sha256', $body);
		$str = $this->appid.$timestamp.$nonce.$hash;
		$hash = hash_hmac('sha256', $str, $this->appkey, true);
		$signature = base64_encode($hash);
		return $signature;
	}

	// 发送请求
	private function httpPost($url, $json, $authorization)
	{
		$ch = curl_init();
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Content-Type: application/json; charset=utf-8";
		$httpheader[] = "Connection: close";
		$httpheader[] = "Authorization: ".$authorization;
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$data  = curl_exec($ch);
		$errno = curl_errno($ch);
		if ($errno) {
			$msg = 'curl errInfo: ' . curl_error($ch) . ' curl errNo: ' . $errno;
			throw new \Exception($msg);
		}
		curl_close($ch);
		return $data;
	}

	// 回调签名验证
	public function verify($params, $key){
		$sign = $this->getSign($params, $key, $params['signType']);
		if($sign === $params['sign']){
			return true;
		}
		return false;
	}

	// 计算签名
	private function getSign($params, $key, $signType){
		ksort($params);
		$signstr = '';
	
		foreach($params as $k => $v){
			if($k != "sign" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr, 0, -1);
		$signstr = $signstr.$key;
		if($signType == 'SHA256'){
			$sign = strtoupper(hash('sha256', $signstr));
		}else{
			$sign = strtoupper(md5($signstr));
		}
		return $sign;
	}
	
}
