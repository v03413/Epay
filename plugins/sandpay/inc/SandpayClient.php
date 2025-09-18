<?php

/**
 * https://api.sandpay.com.cn/
 */
class SandpayClient
{
    private $accessMid;
    private $version = '4.0.0';
    private $signType = 'RSA';
    private $encryptType = 'AES';

    // 公钥文件
    private $publicKeyPath = PLUGIN_ROOT.'sandpay/cert/sand.cer';
    // 私钥文件
    private $privateKeyPath = PLUGIN_ROOT.'sandpay/cert/client.pfx';
    // 私钥证书密码
    private $privateKeyPwd;

    private $publicKey;
    private $privateKey;

    private $apiUrl = 'https://openapi.sandpay.com.cn';

    public $request_body;
    public $response_body;

    public function __construct($accessMid, $privateKeyPwd, $isTest = 0){
        $this->accessMid = $accessMid;
        $this->privateKeyPwd = $privateKeyPwd;
        if(file_exists(PLUGIN_ROOT.'sandpay/cert/'.$accessMid.'.pfx')){
            $this->privateKeyPath = PLUGIN_ROOT.'sandpay/cert/'.$accessMid.'.pfx';
        }
        if($isTest == 1){
            $this->apiUrl = 'https://openapi-uat01.sand.com.cn';
        }
        $this->publicKey = $this->getPublicKey();
        $this->privateKey = $this->getPrivateKey();
    }

    //执行请求
    public function execute($path, $params){
        $aesKey = random(16);
        ksort($params);
        $data = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->request_body = $data;
        $bizData = $this->aesEncrypt($data, $aesKey);
        $encryptKey = $this->rsaPublicEncrypt($aesKey);
        $publicParams = [
            'accessMid' => $this->accessMid,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->version,
            'signType' => $this->signType,
            'encryptType' => $this->encryptType,
            'encryptKey' => $encryptKey,
            'bizData' => $bizData,
        ];
        $publicParams['sign'] = $this->rsaPrivateSign($publicParams['bizData']);
        $json = json_encode($publicParams, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response = get_curl($this->apiUrl.$path, $json, 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
        $result = json_decode($response, true);
        if($result['respCode']=='success'){
            if(!$this->rsaPubilcVerify($result['bizData'], $result['sign'])){
                throw new \Exception('返回数据验签失败');
            }
            $decryptAesKey = $this->rsaPrivateDecrypt($result['encryptKey']);
            if(!$decryptAesKey){
                throw new \Exception('AES密钥解密失败');
            }
            $decryptPlainText = $this->aesDecrypt($result['bizData'], $decryptAesKey);
            if(!$decryptPlainText){
                throw new \Exception('AES解密失败');
            }
            $this->response_body = $decryptPlainText;
            $arr = json_decode($decryptPlainText, true);
            if(!$arr){
                throw new \Exception('bizData解析失败');
            }
            if($arr['resultStatus'] == 'fail'){
                throw new \Exception('['.$arr['errorCode'].']'.$arr['errorDesc']);
            }
            return $arr;
        }elseif($result['respCode']=='fail'){
            throw new \Exception($result['respDesc']);
        }else{
            throw new \Exception('返回数据解析失败');
        }
    }

    public function verify($data, $sign){
        if(empty($sign)) return false;
        return $this->rsaPubilcVerify($data, $sign);
    }

    //AES加密
    private function aesEncrypt($data, $key){
        return openssl_encrypt($data, 'AES-128-ECB', $key);
    }

    //AES解密
    private function aesDecrypt($data, $key){
        return openssl_decrypt($data, 'AES-128-ECB', $key);
    }

    //杉德公钥
    private function getPublicKey()
    {
        $file = file_get_contents($this->publicKeyPath);
        $cert = chunk_split(base64_encode($file), 64, "\n");
        $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";
        $res = openssl_pkey_get_public($cert);
        if (!$res) {
            throw new \Exception('从杉德公钥证书获取公钥失败');
        }
        return $res;
    }

    //商户私钥
    private function getPrivateKey()
    {
        $file = file_get_contents($this->privateKeyPath);
        if (!openssl_pkcs12_read($file, $cert, $this->privateKeyPwd)) {
            throw new \Exception('商户私钥证书解析失败');
        }
        return openssl_pkey_get_private($cert['pkey']);
    }
    
    //私钥加签
    private function rsaPrivateSign($data)
    {
        $result = openssl_sign($data, $sign, $this->privateKey, OPENSSL_ALGO_SHA256);
        if (!$result) throw new \Exception('sign error');
        return base64_encode($sign);
    }

    //公钥验签
    public function rsaPubilcVerify($data, $sign)
    {
        $result = openssl_verify($data, base64_decode($sign), $this->publicKey, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    //公钥加密
    public function rsaPublicEncrypt($data) {
        $encrypted = '';
		openssl_public_encrypt($data, $encrypted, $this->publicKey, OPENSSL_PKCS1_PADDING);
		return base64_encode($encrypted);
    }

	//私钥解密
    public function rsaPrivateDecrypt($data) {
        $decrypted = '';
		openssl_private_decrypt(base64_decode($data), $decrypted, $this->privateKey, OPENSSL_PKCS1_PADDING);
		return $decrypted;
    }
}