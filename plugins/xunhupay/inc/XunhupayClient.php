<?php

class XunhupayClient
{
	private $apiurl = 'https://api.xunhupay.com/payment/do.html';
	private $appid;
	private $appsecret;

	function __construct($appid, $appsecret, $apiurl){
		$this->appid = $appid;
		$this->appsecret = $appsecret;
		if(!empty($apiurl)) {
			$this->apiurl = $apiurl;
		}
	}

	//发起支付
	public function do_payment($params){
		return $this->execute($this->apiurl, $params);
	}

	//查询订单
	public function query_payment($params){
		$url = str_replace('/payment/do.html', '/payment/query.html', $this->apiurl);
		return $this->execute($url, $params);
	}

	//退款订单
	public function do_refund($params){
		$url = str_replace('/payment/do.html', '/payment/refund.html', $this->apiurl);
		return $this->execute($url, $params);
	}

	//发起通用请求
	public function execute($url, $params){
		$publicParams = [
			'appid' => $this->appid,
			'time' => time(),
			'nonce_str' => str_shuffle(time())
		];
		$params = array_merge($publicParams, $params);
		$params['hash'] = $this->generate_hash($params, $this->appsecret);
		$response = $this->curl_post($url, json_encode($params));
		$result = json_decode($response, true);
		if(isset($result['errcode']) && $result['errcode']==0){
			$hash = $this->generate_hash($result, $this->appsecret);
			if(!isset($result['hash']) || $hash !== $hash){
				throw new \Exception('返回数据签名校验失败');
			}
			return $result;
		}else{
			throw new \Exception($result['errmsg']?$result['errmsg']:'返回数据解析失败');
		}
	}

	//二维码图片链接解析二维码链接
	public function parseQrcode($url_qrcode){
		$redirect_url = $this->get_redirect_url($url_qrcode);
		if($redirect_url){
			$url = getSubstr($redirect_url, 'data=', '&');
			if($url){
				return base64_decode($url);
			}
		}else{
			$url = getSubstr($url_qrcode, 'data=', '&');
			if($url){
				return base64_decode($url);
			}
		}
		throw new \Exception('获取二维码链接失败');
	}

	public function verify($arr){
		if(!isset($arr['hash'])) return false;
		$hash = $this->generate_hash($arr, $this->appsecret);
		return $hash === $arr['hash'];
	}

	private function curl_post($url, $post, $timeout = 10){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Connection: close";
		$httpheader[] = "Content-Type: application/json; charset=utf-8";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}

	private function get_redirect_url($url, $timeout = 10){
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
		$httpheader[] = "Connection: close";
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_exec($ch);
		$redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
		curl_close($ch);
		return $redirect_url;
	}

	private function generate_hash($param, $key){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "hash" && $v!=='' && !is_null($v)){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$sign = md5($signstr.$key);
		return $sign;
	}
}