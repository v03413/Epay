<?php

class AlipayGlobalClient
{

    const DEFAULT_KEY_VERSION = 1;
    private $gatewayUrl;
    private $clientId;
    private $merchantPrivateKey;
    private $alipayPublicKey;
    private $agentToken;
    private $isSandboxMode;

    public function __construct($gatewayUrl, $clientId, $merchantPrivateKey, $alipayPublicKey, $agentToken = null)
    {
        $this->gatewayUrl = $gatewayUrl;
        if(is_numeric($this->gatewayUrl)){
			$this->gatewayUrl = $this->getGatewayUrl($this->gatewayUrl);
		}
        $this->clientId = $clientId;
        $this->merchantPrivateKey = $merchantPrivateKey;
        $this->alipayPublicKey = $alipayPublicKey;
        $this->agentToken = $agentToken;

        if (strpos($clientId, "SANDBOX_") === 0) {
            $this->isSandboxMode = true;
        }
    }

    //获取网关地址
	private function getGatewayUrl($code){
		if($code == 2){
			return 'https://open-de-global.alipay.com';
		}elseif($code == 1){
			return 'https://open-na-global.alipay.com';
		}else{
			return 'https://open-sea-global.alipay.com';
		}
	}

    public function execute($path, $params, $httpMethod = 'POST')
    {
        $path = ($this->isSandboxMode ? '/ams/sandbox/api' : '/ams/api') . $path;
        $reqUrl = $this->gatewayUrl . $path;
        $reqTime = $this->getMillisecond();
        $reqBody = json_encode($params);

        $signValue = $this->genSignValue($httpMethod, $path, $reqTime, $reqBody);
        $headers = $this->buildBaseHeader($reqTime, $this->clientId, $signValue);

        [$headerContent, $rspBody] = $this->sendRequest($reqUrl, $reqBody, $headers);

        $repHeaders = $this->getHeaderParams($headerContent);
        $arr = json_decode($rspBody, true);

        if(!$arr || !isset($arr['result'])){
			throw new Exception('接口响应数据错误');
		}

        if(!empty($repHeaders['signature'])){
			if(!$this->checkRspSign($httpMethod, $path, $repHeaders['responseTime'], $rspBody, $repHeaders['signature'])){
				throw new Exception('响应数据验签失败');
			}
		}

        if($arr['result']['resultStatus'] == 'S' || $arr['result']['resultStatus'] == 'U' && $arr['result']['resultCode'] == 'PAYMENT_IN_PROCESS'){
			return $arr;
		}else{
			throw new Exception('['.$arr['result']['resultCode'].']'.$arr['result']['resultMessage']);
		}
    }

    public function check($body){
        $signature = $_SERVER['HTTP_SIGNATURE'];
        $responseTime = $_SERVER['HTTP_REQUEST_TIME'];
        if(empty($signature)) return false;
        $httpMethod = 'POST';
        $path = $_SERVER['REQUEST_URI'];
        $signatureValue = trim(substr($signature, strrpos($signature, "=") + 1));
        if($this->checkRspSign($httpMethod, $path, $responseTime, $body, $signatureValue)){
            return true;
        }
        return false;
    }

    //生成请求头部
    private function buildBaseHeader($requestTime, $clientId, $signValue)
    {
        $baseHeader = array();
        $baseHeader[] = "Content-Type:application/json; charset=UTF-8";
        $baseHeader[] = "User-Agent:global-alipay-sdk-php";
        $baseHeader[] = "Request-Time:" . $requestTime;
        $baseHeader[] = "client-id:" . $clientId;

        if(!empty($this->agentToken)){
			$baseHeader[] = "agent-token: " . $this->agentToken;
		}

        $signatureHeader = "algorithm=RSA256,keyVersion=" . self::DEFAULT_KEY_VERSION . ",signature=" . $signValue;
        $baseHeader[] = "Signature:" . $signatureHeader;
        return $baseHeader;
    }

    //解析返回头部参数
	private function getHeaderParams($header){
		if (preg_match('/signature=(.*?)\r\n/i', $header, $signature)) {
            $signature = trim($signature[1]);
        }
		if (preg_match('/client-id: (.*?)\r\n/i', $header, $clientId)) {
            $clientId = trim($clientId[1]);
        }
		if (preg_match('/response-time: (.*?)\r\n/i', $header, $responseTime)) {
            $responseTime = trim($responseTime[1]);
        }
		return ['signature'=>$signature, 'clientId'=>$clientId, 'responseTime'=>$responseTime];
	}

    private function genSignContent($httpMethod, $path, $timeString, $content)
    {
        $payload = $httpMethod . " " . $path . "\n" . $this->clientId . "." . $timeString . "." . $content;
        return $payload;
    }

    private function genSignValue($httpMethod, $path, $reqTime, $reqBody)
    {
        $signContent = $this->genSignContent($httpMethod, $path, $reqTime, $reqBody);
        $signValue = $this->signWithSHA256RSA($signContent, $this->merchantPrivateKey);
        return urlencode($signValue);
    }

    private function checkRspSign($httpMethod, $path, $rspTime, $rspBody, $rspSignValue)
    {
        $rspContent = $this->genSignContent($httpMethod, $path, $rspTime, $rspBody);
        return $this->verifySignatureWithSHA256RSA($rspContent, $rspSignValue, $this->alipayPublicKey);
    }

    private function signWithSHA256RSA($signContent, $merchantPrivateKey)
    {
        $priKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($merchantPrivateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $res = openssl_pkey_get_private($priKey);
        if (!$res) {
            throw new \Exception("签名失败，应用私钥不正确");
        }
        openssl_sign($signContent, $signValue, $res, OPENSSL_ALGO_SHA256);
        return base64_encode($signValue);
    }

    private function verifySignatureWithSHA256RSA($rspContent, $rspSignValue, $alipayPublicKey)
    {
        $pubKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($alipayPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $res = openssl_get_publickey($pubKey);
        if(!$res){
            throw new \Exception('验签失败，Antom公钥不正确');
        }
        if (strstr($rspSignValue, "=")
            || strstr($rspSignValue, "+")
            || strstr($rspSignValue, "/")
            || $rspSignValue == base64_encode(base64_decode($rspSignValue))) {
            $originalRspSignValue = base64_decode($rspSignValue);
        } else {
            $originalRspSignValue = base64_decode(urldecode($rspSignValue));
        }
        $verifyResult = openssl_verify($rspContent, $originalRspSignValue, $res, OPENSSL_ALGO_SHA256);
        return $verifyResult === 1;
    }

    //发送请求
	private function sendRequest($url, $body, $headers){
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $rspContent = curl_exec($ch);

        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new \Exception($errmsg, 0);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode != 200) {
            curl_close($ch);
            if (empty($rspContent)) {
                $rspContent = "HTTP Code: " . $httpCode;
            }
            throw new \Exception($rspContent, $httpCode);
        }

		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerContent = substr($rspContent, 0, $headerSize);
        $rspBody = substr($rspContent, $headerSize);

        curl_close($ch);

        return [$headerContent, $rspBody];
    }

    private function getMillisecond()
	{
		list($s1, $s2) = explode(' ', microtime());
		return sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
	}
}