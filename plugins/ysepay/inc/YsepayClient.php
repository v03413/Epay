<?php

require_once 'YsepayResponse.php';

/**
 * https://open.ysepay.com/openDocs/summary/
 */
class YsepayClient
{

    private $gatewayUrl = 'https://ysgate.ysepay.com';

    //服务商商户号
    private $partnerId;

    //银盛公钥证书
    private $businessgateCertPath;

    //私钥证书文件
    private $privateCertPath;

    //私钥密码
    private $privateKeyPwd;

    private $charset = 'UTF-8';
    private $signType = 'RSA';

    public $notifyUrl;
    public $returnUrl;
    
    public function __construct($partnerId, $privateKeyPwd){
        $this->partnerId = $partnerId;
        $this->privateKeyPwd = $privateKeyPwd;
        $this->businessgateCertPath = PLUGIN_ROOT.'ysepay/cert/businessgate.cer';
        $this->privateCertPath = PLUGIN_ROOT.'ysepay/cert/client.pfx';
        if(file_exists(PLUGIN_ROOT.'ysepay/cert/'.$partnerId.'.pfx')){
            $this->privateCertPath = PLUGIN_ROOT.'ysepay/cert/'.$partnerId.'.pfx';
        }
        if(!file_exists($this->businessgateCertPath)){
            throw new \Exception('银盛公钥证书文件businessgate.cer不存在');
        }
        if(!file_exists($this->privateCertPath)){
            throw new \Exception('商户私钥证书文件client.pfx不存在');
        }
    }

    //扫码支付
    public function execute($method, $bizContent){
        $url = 'https://qrcode.ysepay.com/gateway.do';
        $params = [
            'method' => $method,
            'partner_id' => $this->partnerId,
            'timestamp' => date("Y-m-d H:i:s"),
            'charset' => $this->charset,
            'sign_type' => $this->signType,
            'version' => '3.5'
        ];
        if (!empty($this->notifyUrl)) {
            $params["notify_url"] = $this->notifyUrl;
        }
        if (!empty($this->returnUrl)) {
            $params["return_url"] = $this->returnUrl;
        }
        $params['biz_content'] = json_encode($bizContent, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $params['sign'] = $this->generateSign($params);
        $raw = $this->curl($url, $params);
        $response = new YsepayResponse($raw, $method);
        
        $result = $response->getData();
        if (isset($result['code']) && $result['code'] == '10000') {
            $this->verifyResponse($response);
            return $result;
        } elseif(isset($result['sub_code'])) {
			throw new Exception('['.$result['sub_code'].']'.$result['sub_msg']);
		} elseif(isset($result['msg'])) {
			throw new Exception($result['msg']);
		} else {
			throw new Exception('系统异常，状态未知！');
		}
    }

    //页面跳转支付
    public function pageExecute($method, $bizParams){
        $url = 'https://openapi.ysepay.com/gateway.do';
        $params = [
            'method' => $method,
            'partner_id' => $this->partnerId,
            'timestamp' => date("Y-m-d H:i:s"),
            'charset' => $this->charset,
            'sign_type' => $this->signType,
            'version' => '3.0'
        ];
        if (!empty($this->notifyUrl)) {
            $params["notify_url"] = $this->notifyUrl;
        }
        if (!empty($this->returnUrl)) {
            $params["return_url"] = $this->returnUrl;
        }
        $params = array_merge($params, $bizParams);
        $params['sign'] = $this->generateSign($params);

        $html = "<form id='alipaysubmit' name='alipaysubmit' action='{$url}' method='POST'>";
        foreach ($params as $key => $value) {
            if ($this->isEmpty($value)) {
                continue;
            }
            $value = htmlentities($value, ENT_QUOTES | ENT_HTML5);
            $html .= "<input type='hidden' name='{$key}' value='{$value}'/>";
        }
        $html .= "<input type='submit' value='ok' style='display:none;'></form>";
        $html .= "<script>document.forms['alipaysubmit'].submit();</script>";

        return $html;
    }

    //发起请求
    public function request($path, $method, $version, $bizContent, $file = null){
        $url = $this->gatewayUrl . $path;
        $key = random(16);
        $bizContent = json_encode($bizContent, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $bizContent = $this->aesEncrypt($bizContent, $key);
        $params = [
            'method' => $method,
            'timeStamp' => date("Y-m-d H:i:s"),
            'charset' => 'utf-8',
            'reqId' => date("YmdHis").rand(1000, 9999),
            'certId' => $this->partnerId,
            'version' => $version,
            'check' => $this->rsaPublicEncrypt($key),
            'bizContent' => $bizContent,
        ];
        $params['sign'] = $this->rsa256PrivateSign($this->getSignContent($params));
        if($file instanceof \CURLFile){
            $params['file'] = $file;
        }
        $response = $this->curl($url, $params);
        $result = json_decode(base64_decode($response), true);
        if (isset($result['code']) && $result['code'] == '00000') {
            if(!$this->rsa256PubilcVerify($this->getSignContent($result), $result['sign'])){
                throw new Exception('返回结果验签失败');
            }
            if(!empty($result['subCode']) && $result['subCode'] != '0000') {
                throw new Exception('['.$result['subCode'].']'.$result['subMsg']);
            }
            if(!empty($result['businessData'])){
                $dec_data = $this->aesDecrypt($result['businessData'], $key);
                if(!$dec_data){
                    throw new Exception('业务响应参数解密失败');
                }
                return json_decode($dec_data, true);
            }
            return $result;
        } elseif(isset($result['subMsg'])) {
			throw new Exception('['.$result['subCode'].']'.$result['subMsg']);
		} elseif(isset($result['msg'])) {
			throw new Exception($result['msg']);
		} else {
			throw new Exception('系统异常，状态未知！');
		}
    }

    private function aesEncrypt($data, $key){
        $encData = openssl_encrypt($data, 'aes-128-ecb', $key, OPENSSL_PKCS1_PADDING);
        return base64_encode($encData);
    }

    private function aesDecrypt($data, $key){
        $decData = openssl_decrypt(base64_decode($data), 'aes-128-ecb', $key, OPENSSL_PKCS1_PADDING);
        return $decData;
    }

    //异步通知回调验签
    public function verify($params)
    {
        if (!$params || !isset($params['sign'])) {
            return false;
        }
        $sign = $params['sign'];
        $data = $this->getSignContent($params);
        try {
            return $this->rsaPubilcVerify($data, $sign);
        } catch (\Exception $ex) {
            return false;
        }
    }

    //异步通知回调验签
    public function verify2($params)
    {
        if (!$params || !isset($params['sign'])) {
            return false;
        }
        $sign = $params['sign'];
        $data = $this->getSignContent($params);
        try {
            return $this->rsa256PubilcVerify($data, $sign);
        } catch (\Exception $ex) {
            return false;
        }
    }

    //验证返回内容签名
    private function verifyResponse(YsepayResponse $response)
    {
        $signData = $response->getSignData();
        $sign = $response->getSign();
        if ($this->isEmpty($signData) || $this->isEmpty($sign)) {
            throw new \Exception('返回数据解析签名失败');
        }
        $checkResult = $this->rsaPubilcVerify($signData, $sign);
        if (!$checkResult) {
            if (strpos($signData, '\/') > 0) {
                $signData = str_replace('\/', '/', $signData);
                $checkResult = $this->rsaPubilcVerify($signData, $sign);
            }
            if (!$checkResult) {
                throw new \Exception('对返回数据使用银盛公钥验签失败');
            }
        }
    }

    private function generateSign($params){
        return $this->rsaPrivateSign($this->getSignContent($params));
    }

    private function getSignContent($params){
        ksort($params);
        unset($params['sign']);

        $stringToBeSigned = "";
        foreach ($params as $k => $v) {
            if($this->isEmpty($v) || substr($v, 0, 1) == '@') continue;
            $stringToBeSigned .= "&{$k}={$v}";
        }
        $stringToBeSigned = substr($stringToBeSigned, 1);
        return $stringToBeSigned;
    }

    private function isEmpty($value)
    {
        return $value === null || trim($value) === '';
    }

    //银盛公钥
    private function getPublicKey()
    {
        $file = file_get_contents($this->businessgateCertPath);
        $cert = chunk_split(base64_encode($file), 64, "\n");
        $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";
        $res = openssl_pkey_get_public($cert);
        if (!$res) {
            throw new \Exception('从银盛公钥证书获取公钥失败');
        }
        return $res;
    }

    //商户私钥
    private function getPrivateKey()
    {
        $file = file_get_contents($this->privateCertPath);
        if (!openssl_pkcs12_read($file, $cert, $this->privateKeyPwd)) {
            throw new \Exception('商户私钥证书解析失败');
        }
        return openssl_pkey_get_private($cert['pkey']);
    }
    
    //私钥加签
    private function rsaPrivateSign($data)
    {
        $prikey = $this->getPrivateKey();
        $result   = openssl_sign($data, $sign, $prikey);
        if (!$result) throw new \Exception('sign error');
        return base64_encode($sign);
    }

    //公钥验签
    public function rsaPubilcVerify($data, $sign)
    {
        $pubkey = $this->getPublicKey();
        $result = openssl_verify($data, base64_decode($sign), $pubkey);
        return $result === 1;
    }

    //私钥加签
    private function rsa256PrivateSign($data)
    {
        $prikey = $this->getPrivateKey();
        $result = openssl_sign($data, $sign, $prikey, OPENSSL_ALGO_SHA256);
        if (!$result) throw new \Exception('sign error');
        return base64_encode($sign);
    }

    //公钥验签
    public function rsa256PubilcVerify($data, $sign)
    {
        $pubkey = $this->getPublicKey();
        $result = openssl_verify($data, base64_decode($sign), $pubkey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    private function rsaPublicEncrypt($data)
    {
        $pubkey = $this->getPublicKey();
        $result = openssl_public_encrypt($data, $encrypted, $pubkey);
        return base64_encode($encrypted);
    }

    private function curl($url, $postFields = null)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if (is_array($postFields) && 0 < count($postFields)) {
            $postMultipart = false;
            foreach ($postFields as &$value) {
                if($value instanceof \CURLFile){
                    $postMultipart = true;
                }elseif(substr($value, 0, 1) == '@'){
                    $postMultipart = true;
                    $file = substr($value, 1);
                    if(file_exists($file)){
                        $value = new \CURLFile($file);
                    }
                }
            }
            curl_setopt($ch, CURLOPT_POST, true);
            if($postMultipart){
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            }else{
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
            }
        }

        $response = curl_exec($ch);

        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new \Exception($errmsg, 0);
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpStatusCode != 200) {
            curl_close($ch);
            throw new \Exception($response, $httpStatusCode);
        }

        curl_close($ch);

        return $response;
    }
    
}