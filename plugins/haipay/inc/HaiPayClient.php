<?php

/**
 * http://opendocs.hkrt.cn:8181/docs/saas
 */
class HaiPayClient
{
    private $pay_url = 'https://saas-front.hkrt.cn';
    private $mch_url = 'http://saas.hkrt.cn:8080';

    private $accessid;

    private $accesskey;

    public function __construct($accessid, $accesskey, $isTest = false){
        $this->accessid = $accessid;
        $this->accesskey = $accesskey;
        if($isTest){
            $this->pay_url = 'http://39.106.187.68:8080';
            $this->mch_url = 'http://47.95.131.62:8080';
        }
    }

    //发起支付请求
    public function payRequest($path, $params)
    {
        $requrl = $this->pay_url . $path;
        $params['accessid'] = $this->accessid;
        $params['req_id'] = date('YmdHis').rand(1000, 9999);
        $params['sign'] = $this->make_sign($params);

        $data = get_curl($requrl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

        $result = json_decode($data, true);

        if(isset($result['result_code']) && $result['result_code']==10000){
            return $result;
        }elseif(!empty($result['result_msg'])){
            throw new Exception($result['result_msg']);
        }else{
            throw new Exception($result['return_msg']?$result['return_msg']:'返回数据解析失败');
        }
    }

    //发起进件请求
    public function mchRequest($path, $params)
    {
        $requrl = $this->mch_url . $path;
        $params['accessid'] = $this->accessid;
        $params['sign'] = $this->make_sign($params);

        $data = get_curl($requrl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

        $result = json_decode($data, true);

        if(isset($result['return_code']) && $result['return_code']==10000){
            return $result;
        }elseif(!empty($result['result_msg'])){
            throw new Exception($result['result_msg']);
        }else{
            throw new Exception($result['return_msg']?$result['return_msg']:'返回数据解析失败');
        }
    }

    public function mchRequest2($path, $params)
    {
        $requrl = $this->mch_url . $path;
        $params['accessid'] = $this->accessid;
        $params['sign'] = $this->make_sign($params);

        $data = get_curl($requrl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

        $result = json_decode($data, true);

        return $result;
    }

    public function verify($params){
        if(!isset($params['sign'])) return false;
        $sign = $this->make_sign($params);
        return $sign === $params['sign'];
    }

    private function get_sign_str($param){
		if(!is_array($param)) return $param;
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!==null && $v!==''){
				if(is_array($v)){
                    if(empty($v)) continue;
					if(array_keys($v) === range(0, count($v) - 1)){
						$strs = [];
						foreach($v as $vv){
							$strs[] = self::get_sign_str($vv);
						}
						$v = implode('&', $strs);
					}else{
						$v = self::get_sign_str($v);
					}
				}
				$signstr .= $k.'='.$v.'&';
			}
		}
		return substr($signstr, 0, -1);
	}

	private function make_sign($param){
		$signstr = self::get_sign_str($param);
		$signstr .= $this->accesskey;
		$sign = strtoupper(md5($signstr));
		return $sign;
	}
}