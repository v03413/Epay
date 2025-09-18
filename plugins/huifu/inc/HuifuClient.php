<?php

class HuifuClient
{
    const BASE_API_URL = "https://api.huifu.com";

    //汇付系统号
    private $sys_id;

    //汇付产品号
    private $product_id;

    //商户私钥
    private $merchant_private_key;

    //汇付公钥
    private $huifu_public_key;

    /**
     * @param $config 商户配置信息
     */
    public function __construct($config)
    {
        if (empty($config['sys_id'])) {
            throw new \InvalidArgumentException('汇付系统号不能为空');
        }
        if (empty($config['product_id'])) {
            throw new \InvalidArgumentException("汇付产品号不能为空");
        }
        if (empty($config['merchant_private_key'])) {
            throw new \InvalidArgumentException("商户私钥不能为空");
        }
        if (empty($config['huifu_public_key'])) {
            throw new \InvalidArgumentException("汇付公钥不能为空");
        }
        $this->sys_id = $config['sys_id'];
        $this->product_id = $config['product_id'];
        $this->merchant_private_key = $this->loadPrivateKey($config['merchant_private_key']);
        $this->huifu_public_key = $this->loadPublicKey($config['huifu_public_key']);
    }

    /**
     * 请求API接口并解析返回数据
     */
    public function requestApi($path, $data)
    {
        $url = self::BASE_API_URL . $path;
        $body = [
            'sys_id' => $this->sys_id,
            'product_id' => $this->product_id,
            'data' => $data
        ];
        $body['sign'] = $this->makeSign($data);
        $response = $this->curlPost($url, $body);
        $result = json_decode($response, true);
        if (!$result || empty($result['data']) || empty($result['sign'])) {
            throw new \Exception("接口返回数据解析失败");
        }
        //print_r($result);
        if (!$this->checkResponseSign($result['data'], $result['sign'])) {
            throw new \Exception("接口返回数据验签失败");
        }
        return $result['data'];
    }

    /**
     * 上传文件
     */
    public function upload($path, $data, $file_path, $file_name)
    {
        $url = self::BASE_API_URL . $path;
        $body = [
            'sys_id' => $this->sys_id,
            'product_id' => $this->product_id,
            'data' => $data
        ];
        $file = new \CURLFile($file_path, '', $file_name);
        $body['sign'] = $this->makeSign($data);
        $response = $this->curlPost($url, $body, $file);
        $result = json_decode($response, true);
        if (!$result || empty($result['data'])) {
            throw new \Exception("接口返回数据解析失败");
        }
        return $result['data'];
    }

    /**
     * 发起POST请求
     * @param string $url 请求URL
     * @param array $body POST数据
     * @param \CURLFile $file 上传文件
     * @return string
     */
    private function curlPost($url, $body, $file = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        if ($file) {
            $body['data'] = json_encode($body['data'], JSON_UNESCAPED_UNICODE);
            $body['file'] = $file;
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        } else {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
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
            throw new \Exception($response ? $response : 'http_code=' . $httpStatusCode, $httpStatusCode);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * 生成请求签名
     * @param array $params
     * @return string
     */
    private function makeSign($params)
    {
        $params = array_filter($params, function ($value) {
            return $value !== null;
        });
        ksort($params);
        $content = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this->rsaPrivateSign($content);
    }

    /**
     * 校验通知数据签名
     * @param string $data
     * @param string $sign
     * @return bool
     */
    public function checkNotifySign($data, $sign)
    {
        if (empty($sign)) return false;
        return $this->rsaPublicVerify($data, $sign);
    }

    /**
     * 校验返回数据签名
     * @param array $params
     * @param string $sign
     * @return bool
     */
    private function checkResponseSign($params, $sign)
    {
        if (empty($sign)) return false;
        ksort($params);
        $content = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this->rsaPublicVerify($content, $sign);
    }

    /**
     * 商户私钥签名
     * @param string $data 待签名字符串
     * @return string
     */
    private function rsaPrivateSign($data)
    {
        openssl_sign($data, $signature, $this->merchant_private_key, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    }

    /**
     * 平台公钥验签
     * @param string $data 待验签字符串
     * @return bool
     */
    private function rsaPublicVerify($data, $signature)
    {
        $result = openssl_verify($data, base64_decode($signature), $this->huifu_public_key, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }

    /**
     * 平台公钥加密
     * @param string $data 待加密字符串
     * @return string
     */
    private function rsaPublicEncrypt($data)
    {
        openssl_public_encrypt($data, $encryptResult, $this->huifu_public_key, OPENSSL_PKCS1_PADDING);
        return base64_encode($encryptResult);
    }

    /**
     * 加载汇付公钥
     */
    private function loadPublicKey($public_key)
    {
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($public_key, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $pubkeyid = openssl_get_publickey($res);
        if (!$pubkeyid) {
            throw new Exception('汇付公钥不正确');
        }
        return $pubkeyid;
    }

    /**
     * 加载商户私钥
     */
    private function loadPrivateKey($private_key)
    {
        $res = "-----BEGIN PRIVATE KEY-----\n" .
            wordwrap($private_key, 64, "\n", true) .
            "\n-----END PRIVATE KEY-----";
        $prikeyid = openssl_get_privatekey($res);
        if (!$prikeyid) {
            throw new Exception('商户私钥不正确');
        }
        return $prikeyid;
    }
}
