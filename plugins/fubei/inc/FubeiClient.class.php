<?php

/**
 * https://www.yuque.com/51fubei/openapi
 */
class FubeiClient
{
    //支付接口地址
    private $gateway_url = 'https://shq-api.51fubei.com/gateway/agent';

    private $version = '1.0';

    private $sign_method = 'md5';
    
    private $format = 'json';

    //​开放平台ID
    private $app_id;

    //​接口密钥
    private $app_secret;

    
    public function __construct($app_id, $app_secret){
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
    }

    //发起请求
    public function execute($method, $bizContent)
    {
        $commonData = [
			'app_id' => $this->app_id,
			'method' => $method,
			'format' => $this->format,
			'sign_method' => $this->sign_method,
			'nonce' => random(12),
			'version' => $this->version,
			'biz_content' => json_encode($bizContent, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
		];
        $commonData['sign'] = $this->make_sign($commonData);

        $data = get_curl($this->gateway_url, json_encode($commonData), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

        $result = json_decode($data, true);

        if(isset($result['result_code']) && $result['result_code']==200){
            return $result['data'];
        }else{
            throw new Exception($result['result_message']?$result['result_message']:'返回数据解析失败');
        }
    }

    public function verify($param){
        if(!isset($param['sign'])) return false;
        $sign = $this->make_sign($param);
        return $sign === $param['sign'];
    }

    private function make_sign($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr, 0, -1);
		$signstr .= $this->app_secret;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}
}