<?php

/**
 * @see https://doc.huilianlink.com/
 */
class HlpayClient
{
    //接口地址
    private $gateway_url = 'https://api.huilianlink.com';

    //子商户编码
    private $sub_sn;

	//应用APPID
    private $app_id;
    
    //应用私钥
    private $merchant_private_key;

    //平台公钥
    private $platform_public_key;

    private $sign_type = 'RSA2';
	private $version = '1.01';

    public function __construct($app_id, $merchant_private_key, $platform_public_key, $sub_sn = null)
    {
        $this->sub_sn = $sub_sn;
        $this->app_id = $app_id;
        $this->merchant_private_key = $merchant_private_key;
        $this->platform_public_key = $platform_public_key;
    }

    //请求API接口并解析返回数据
    public function execute($path, $bizContent)
    {
        $requrl = $this->gateway_url . $path;
        $params = [
            'appId' => $this->app_id,
			'subSn' => $this->sub_sn,
            'timestamp' => time().'',
			'requestId' => getMillisecond(),
			'version' => $this->version,
			'signType' => $this->sign_type,
            'bizContent' => json_encode($bizContent, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
        ];
        $params['sign'] = $this->generateSign($params);
        $response = get_curl($requrl, json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), 0, 0, 0, 0, 0, ['Content-Type: application/json']);
        $result = json_decode($response, true);
		if(isset($result['code']) && $result['code']==200){
            if(!$this->verifySign($result)){
                throw new Exception('返回数据验签失败');
            }
			return $result['data'];
		}elseif(isset($result['msg'])){
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
			if($k != "sign" && !isNullOrEmpty($v)){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		return $signstr;
	}

	// 深度处理data字段（支持JSON字符串解析）
    private static function processDataField($data) {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }
        
        if (is_array($data)) {
            $filtered = self::deepFilterParams($data);
            $sorted = self::deepSortParams($filtered);
            return json_encode($sorted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        return $data;
    }

    // 深度过滤空值参数（支持多维数组）
    private static function deepFilterParams(array $params): array {
        $filtered = [];
        foreach ($params as $k => $v) {
            if (isNullOrEmpty($v)) continue;

            // 递归处理数组
            if (is_array($v)) {
                $v = self::deepFilterParams($v);
                if (empty($v)) continue;
            }

            $filtered[$k] = $v;
        }
        return $filtered;
    }

    // 深度排序参数（支持多维数组）
    private static function deepSortParams(array $params): array {
        ksort($params, SORT_STRING);
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = self::deepSortParams($v);
            }
        }
        return $params;
    }

    //请求参数签名
	private function generateSign($param){
		return $this->rsaPrivateSign($this->getSignContent($param));
	}

    //验签方法
	public function verifySign($param){
		if(empty($param['sign'])) return false;
		if (isset($param['data'])) {
            $param['data'] = self::processDataField($param['data']);
        }
		return $this->rsaPubilcSign($this->getSignContent($param), $param['sign']);
	}

	//商户私钥签名
	private function rsaPrivateSign($data){
		$priKey = $this->merchant_private_key;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
		$pkeyid = openssl_pkey_get_private($res);
		if(!$pkeyid){
			throw new Exception('签名失败，商户私钥不正确');
		}
		openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
		$signature = base64_encode($signature);
		return $signature;
	}

	//平台公钥验签
	private function rsaPubilcSign($data, $signature){
		$pubKey = $this->platform_public_key;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
		$pubkeyid = openssl_pkey_get_public($res);
		if(!$pubkeyid){
			throw new Exception('验签失败，平台公钥不正确');
		}
		$result = openssl_verify($data, base64_decode($signature), $pubkeyid, OPENSSL_ALGO_SHA256);
		return $result === 1;
	}
}
