<?php

/**
 * @see https://global.alipay.com/docs/ac/legacy/legacydoc
 */
class AlipayGlobalClient
{
    private $gateway_url = 'https://intlmapi.alipay.com/gateway.do';
    private $partner;
    private $key;
    private $private_key;
    private $ali_public_key;
    private $sign_type = 'MD5';
	private $input_charset = 'utf-8';

    public function __construct($alipay_config)
    {
        $this->partner = $alipay_config['partner'];
        if(!empty($alipay_config['sign_type'])){
            $this->sign_type = $alipay_config['sign_type'];
        }
        if($this->sign_type == 'RSA'){
            $this->private_key = $alipay_config['private_key'];
            $this->ali_public_key = $alipay_config['ali_public_key'];
        }else{
            $this->key = $alipay_config['key'];
        }
        if(!empty($alipay_config['gateway_url'])){
            $this->gateway_url = $alipay_config['gateway_url'];
        }
    }

	public function buildRequestForm($params, $method = 'POST'){
		$data = $this->buildRequestParam($params);

		if($method == 'REDIRECT'){
			$requestUrl = $this->gateway_url.'?'.http_build_query($data);
			$ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_FAILONERROR, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $response = curl_exec($ch);
            if (curl_errno($ch) > 0) {
                $errmsg = curl_error($ch);
                curl_close($ch);
                throw new Exception($errmsg, 0);
            }
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($httpStatusCode == 301 || $httpStatusCode == 302) {
                $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
                curl_close($ch);
                return $redirect_url;
            } elseif ($httpStatusCode == 200) {
                curl_close($ch);
                $response = mb_convert_encoding($response, 'UTF-8', 'GB2312');
                if(preg_match('/<div\s+class="Todo">([^<]+)<\/div>/i', $response, $matchers)) {
                    throw new Exception($matchers[1]);
                }
            }
            throw new Exception('返回数据解析失败', $httpStatusCode);
		}

		$url = $this->gateway_url.'?_input_charset='.$this->input_charset;

		$html = "<form id='alipaysubmit' name='alipaysubmit' action='{$url}' method='".$method."'>";
		foreach ($data as $key => $value) {
			$value = htmlentities($value, ENT_QUOTES | ENT_HTML5);
			$html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
		}
		$html .= "<input type='submit' value='ok' style='display:none;'></form>";
		$html .= "<script>document.forms['alipaysubmit'].submit();</script>";

		return $html;
	}

	public function sendRequest($params){
		$data = $this->buildRequestParam($params);
		$url = $this->gateway_url.'?_input_charset='.$this->input_charset;
		$response = $this->curl($url, http_build_query($data));
		$arr = $this->xml2array($response);
		return $arr;
	}

	public function buildSdkParam($params){
		$data = $this->buildRequestParam($params);
		return http_build_query($data);
	}

	public function verify($param){
		if(!isset($param['sign'])) return false;
		$sign = $param['sign'];
		$signstr = $this->getSignContent($param);
		if($this->sign_type == 'MD5'){
			$mysign = md5($signstr . $this->key);
			return $mysign === $sign;
		}else{
			return $this->rsaPubilcVerify($signstr, $sign, $this->sign_type);
		}
	}

    private function buildRequestParam($param){
		$param['sign'] = $this->getSign($param);
		$param['sign_type'] = $this->sign_type;
		return $param;
	}

	private function getSign($param){
		$signstr = $this->getSignContent($param);
		if($this->sign_type == 'MD5'){
			$sign = md5($signstr . $this->key);
		}else{
			$sign = $this->rsaPrivateSign($signstr, $this->sign_type);
		}
		return $sign;
	}

	private function getSignContent($param){
		ksort($param);
		$signstr = '';
		foreach($param as $k => $v){
			if($k != "sign" && $k != "sign_type" && !$this->isEmpty($v)){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		return $signstr;
	}

	private function isEmpty($value)
    {
        return $value === null || trim($value) === '';
    }

	private function xml2array($xml)
    {
        if (!$xml) {
            return false;
        }
		LIBXML_VERSION < 20900 && libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

	public static function getMillisecond()
	{
		list($s1, $s2) = explode(' ', microtime());
		return sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
	}

	//使用商户私钥签名
	private function rsaPrivateSign($data, $signType = 'RSA2'){
		$priKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
			wordwrap($this->private_key, 64, "\n", true) .
			"\n-----END RSA PRIVATE KEY-----";
		$res = openssl_get_privatekey($priKey);
		if(!$res){
			throw new Exception('签名失败，商户私钥不正确');
		}

		if($signType == 'RSA2'){
            openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
        }else{
            openssl_sign($data, $sign, $res);
        }
        openssl_free_key($res);
		return base64_encode($sign);
	}

	//使用支付宝公钥验签
	private function rsaPubilcVerify($data, $sign, $signType = 'RSA2'){
		$pubKey = "-----BEGIN PUBLIC KEY-----\n" .
			wordwrap($this->ali_public_key, 64, "\n", true) .
			"\n-----END PUBLIC KEY-----";
		$res = openssl_get_publickey($pubKey);
        if(!$res){
            throw new Exception('验签失败，支付宝公钥不正确');
        }

        if($signType == 'RSA2'){
            $result = openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        }else{
            $result = openssl_verify($data, base64_decode($sign), $res);
        }
        openssl_free_key($res);
        return $result === 1;
	}

	private function curl($url, $post = false, $timeout = 10){
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
		if($post){
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
}