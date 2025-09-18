<?php

class JlpayClient
{
	private $gateway_url = 'https://openapi.jlpay.com';
	private $sign_alg = 'SM3WithSM2WithDer';
	private $crypto_alg = 'SM2WithSM4';

	private $appid;

	private $platform_public_key;
	private $merchant_private_key;
	private $encrypt_key;

	public function __construct($appid, $platform_public_key, $merchant_private_key, $isTest = 0)
	{
		if(!function_exists('gmp_init')) throw new Exception('请先安装GMP扩展');
		$this->appid = $appid;
		$this->platform_public_key = $platform_public_key;
		$this->merchant_private_key = $merchant_private_key;
		if($isTest == 1) $this->gateway_url = 'https://openapi-uat.jlpay.com';
		$this->encrypt_key = random(16);
	}

	//发起API请求
	public function execute($path, $data, $encrypt = false){
		$timestamp = time();
        $nonce = getSid();
		$body = json_encode($data, JSON_UNESCAPED_UNICODE);
		$sign = $this->sign('POST', $path, $timestamp, $nonce, $body);
		$headers = [
			'Accept: application/json; charset=utf-8',
			'Content-Type: application/json; charset=utf-8',
			'x-jlpay-appid: '.$this->appid,
			'x-jlpay-nonce: '.$nonce,
			'x-jlpay-timestamp: '.$timestamp,
			'x-jlpay-sign-alg: '.$this->sign_alg,
			'x-jlpay-sign: '.$sign
		];
		if($encrypt){
			$key = $this->sm2Encrypt($this->encrypt_key);
			$headers += [
				'x-jlpay-crypto-alg: '.$this->crypto_alg,
				'x-jlpay-key: '.$key
			];
		}
		$requrl = $this->gateway_url.$path;

		[$httpCode, $respHeader, $respBody] = $this->curl($requrl, $body, $headers);

		$result = json_decode($respBody, true);
		if(isset($result['ret_code']) && ($result['ret_code'] == '00' || $result['ret_code'] == '00000')){
			if(!$this->verifyResponse('POST', $path, $respBody, $respHeader)){
				throw new Exception('返回数据验签失败');
			}
			return $result;
		}elseif(isset($result['ret_msg'])){
			if(!$this->verifyResponse('POST', $path, $respBody, $respHeader)){
				throw new Exception('返回数据验签失败');
			}
			throw new Exception($result['ret_msg']);
		}else{
			throw new Exception('返回数据解析失败(httpCode='.$httpCode.')');
		}
	}

	//上传文件
	public function upload($path, $file_name, $file_path){
		$meta = [
			'filename' => $file_name,
			'alg' => 'SM3',
			'abstract' => $this->sm3Digest(file_get_contents($file_path))
		];
		$timestamp = time();
        $nonce = getSid();
		$body = json_encode($meta, JSON_UNESCAPED_UNICODE);
		$sign = $this->sign('POST', $path, $timestamp, $nonce, $body);
		$headers = [
			'Accept: application/json',
			'x-jlpay-appid: '.$this->appid,
			'x-jlpay-nonce: '.$nonce,
			'x-jlpay-timestamp: '.$timestamp,
			'x-jlpay-sign-alg: '.$this->sign_alg,
			'x-jlpay-sign: '.$sign
		];
		$requrl = $this->gateway_url.$path;
		$params = [
			'meta' => $body,
			'file' => new CURLFile($file_path, null, $file_name)
		];

		[$httpCode, $respHeader, $respBody] = $this->curl($requrl, $params, $headers);
		
		$result = json_decode($respBody, true);
		if(isset($result['ret_code']) && $result['ret_code'] == '00'){
			if(!$this->verifyResponse('POST', $path, $respBody, $respHeader)){
				throw new Exception('返回数据验签失败');
			}
			if(empty($result['media_id'])) throw new Exception('上传文件未返回media_id');
			return $result['media_id'];
		}elseif(isset($result['ret_msg'])){
			throw new Exception($result['ret_msg']);
		}else{
			throw new Exception('返回数据解析失败(httpCode='.$httpCode.')');
		}
	}

	private function sign($method, $apiName, $timestamp, $nonceStr, $body) {
        $document = $method."\n".$apiName."\n".$timestamp."\n".$nonceStr."\n".$body."\n";
        return $this->sm2Sign($document);
    }

	private function verifySign($method, $apiName, $timestamp, $nonceStr, $body, $sign) {
        $document = $method."\n".$apiName."\n".$timestamp."\n".$nonceStr."\n".$body."\n";
		return $this->sm2VerifySign($document, $sign);
    }

    private function verifyResponse($method, $apiName, $body, $header) {
		if(preg_match('/x-jlpay-timestamp: (.*?)\r\n/i', $header, $match)){
			$timestamp = trim($match[1]);
		}
		if(preg_match('/x-jlpay-nonce: (.*?)\r\n/i', $header, $match)){
			$nonceStr = trim($match[1]);
		}
		if(preg_match('/x-jlpay-sign: (.*?)\r\n/i', $header, $match)){
			$sign = trim($match[1]);
		}
		if(empty($sign)) return false;
		return $this->verifySign($method, $apiName, $timestamp, $nonceStr, $body, $sign);
    }

	public function verifyNotify($body) {
		$timestamp = $_SERVER['HTTP_X_JLPAY_TIMESTAMP'];
		$nonceStr = $_SERVER['HTTP_X_JLPAY_NONCE'];
		$sign = $_SERVER['HTTP_X_JLPAY_SIGN'];
		$method = 'POST';
		$apiName = $_SERVER['REQUEST_URI'];
		if(empty($sign)) return false;
		return $this->verifySign($method, $apiName, $timestamp, $nonceStr, $body, $sign);
    }
	
	//使用商户私钥对数据进行sm2签名
    private function sm2Sign($signStr){
        $sm2 = new Rtgm\sm\RtSm2('base64');
        $sign = $sm2->doSign($signStr, $this->merchant_private_key);
		$sign = str_replace(PHP_EOL, '', $sign);
		return $sign;
    }

    //使用平台公钥对数据进行sm2验签
    public function sm2VerifySign($signStr,$sign){
        $sm2 = new Rtgm\sm\RtSm2('base64');
        $isSign = $sm2->verifySign($signStr, $sign, $this->platform_public_key);
        return $isSign;
    }
	
	//使用平台公钥对数据进行sm2加密
    private function sm2Encrypt($plaintext){
        $sm2 = new Rtgm\sm\RtSm2('base64');
        return base64_encode(hex2bin('04' . $sm2->doEncrypt($plaintext, $this->platform_public_key)));
    }

    //使用商户私钥对数据进行sm2解密
    private function sm2Decrypt($ciphertext){
        $sm2 = new Rtgm\sm\RtSm2('base64');
        return $sm2->doDecrypt($ciphertext, $this->merchant_private_key);
    }

	//SM3加密
	public function sm3Digest($str){
        $sm3 = new Rtgm\sm\RtSm3();
        return base64_encode(hex2bin($sm3->digest($str)));
    }

	//sm4加密
	public function sm4Encrypt($data){
		$sm4 = new Rtgm\sm\RtSm4($this->encrypt_key);
		return base64_encode(hex2bin($sm4->encrypt($data, 'sm4-ecb')));
	}

	private function curl($url, $body, $header, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($body) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $data = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new Exception($errmsg);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($data, 0, $headerSize);
        $body = substr($data, $headerSize);
        curl_close($ch);
        return [$httpCode, $header, $body];
    }
}