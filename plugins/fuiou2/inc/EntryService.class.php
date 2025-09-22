<?php

class EntryService
{
    private $ins_cd;
    private $key;
    private $xml;
    private $gateway_url = 'https://mchntapi.fuioupay.com';

    public function __construct($ins_cd, $key, $is_test = false)
	{
		$this->ins_cd = $ins_cd;
		$this->key = $key;
		if($is_test){
			$this->gateway_url = 'http://www-1.fuiou.com:28090/wmp';
		}
		$this->xml = new XmlWriter();
	}

    //发起API请求
	public function submit($path, $params, $is_file = false, $is_download = false){
		$requrl = $this->gateway_url.$path;
		$public_params = [
            'trace_no' => getSid(),
			'ins_cd' => $this->ins_cd,
		];

		foreach($params as $key=>$value){
			if(!$is_file && $value) $params[$key] = mb_convert_encoding($params[$key], 'GBK', 'UTF-8');
		}

		$params = array_merge($public_params, $params);
		$params['sign'] = $this->makeSign($params);

		if($is_file){
			$response = get_curl($requrl, $params);
		}else{
			$xml = "<?xml version=\"1.0\" encoding=\"GBK\" standalone=\"yes\"?><xml>".$this->toXml($params)."</xml>";

			$response = get_curl($requrl, 'req='.urlencode(urlencode($xml)));
		}
		if($is_download) return $response;
		if(strpos($response, '%3Cxml%3E') !== false){
			$response = urldecode($response);
		}
		$response = str_ireplace('encoding="gbk"', 'encoding="utf-8"', $response);
		LIBXML_VERSION < 20900 && libxml_disable_entity_loader(true);
		$result = json_decode(json_encode(simplexml_load_string($response)), true);
		if(empty($result)){
			$response = mb_convert_encoding($response, 'UTF-8', 'GBK');
			$result = json_decode(json_encode(simplexml_load_string($response)), true);
		}

		//file_put_contents('log.txt', $path.' '.$params['trace_no'].PHP_EOL, FILE_APPEND);

		if(isset($result['ret_code']) && ($result['ret_code']=='000000' || $result['ret_code']=='0000')){
			return $result;
		}elseif(isset($result['return_code']) && $result['return_code']=='SUCCESS'){
			return $result;
		}elseif(isset($result['ret_msg'])){
			throw new Exception($result['ret_msg']);
		}elseif(isset($result['return_msg'])){
			throw new Exception($result['return_msg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//发起API请求
	public function submit_json($path, $params, $is_file = false){
		$requrl = $this->gateway_url.$path;
		$public_params = [
            'trace_no' => getSid(),
			'ins_cd' => $this->ins_cd,
		];

		foreach($params as $key=>$value){
			if(!$is_file && $value) $params[$key] = mb_convert_encoding($params[$key], 'GBK', 'UTF-8');
		}

		$params = array_merge($public_params, $params);
		$params['sign'] = $this->makeSign($params);

		if($is_file){
			$response = get_curl($requrl, $params);
		}else{
			$json = json_encode($params);
			$response = get_curl($requrl, 'req='.urlencode($json));
		}
		$result = json_decode($response, true);

		//file_put_contents('log.txt', $path.' '.$params['trace_no'].PHP_EOL, FILE_APPEND);

		if(isset($result['ret_code']) && ($result['ret_code']=='000000' || $result['ret_code']=='0000')){
			return $result;
		}elseif(isset($result['ret_msg'])){
			throw new Exception($result['ret_msg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//发起API请求
	public function submit_json_v2($path, $params){
		$requrl = $this->gateway_url.$path;
		$public_params = [
            'traceNo' => getSid(),
			'insCd' => $this->ins_cd,
		];

		foreach($params as $key=>$value){
			if($value) $params[$key] = mb_convert_encoding($params[$key], 'GBK', 'UTF-8');
		}

		$params = array_merge($public_params, $params);
		$params['sign'] = $this->makeSign($params);

		$json = json_encode($params);
		$response = get_curl($requrl, 'req='.urlencode($json));
		$result = json_decode($response, true);

		//file_put_contents('log.txt', $path.' '.$params['traceNo'].PHP_EOL, FILE_APPEND);

		if(isset($result['retCode']) && $result['retCode']=='0000'){
			return $result;
		}elseif(isset($result['retMsg'])){
			throw new Exception($result['retMsg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	public function verifySign($param){
		$sign = $param['sign'];
		if(!$sign) return false;
		return $sign === $this->makeSign($param);
	}

    //生成签名
	private function makeSign($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && !$v instanceof \CURLFile && !is_array($v) && !$this->isEmpty($v)){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr .= 'key='.$this->key;
		$sign = md5($signstr);
		return $sign;
	}

	private function isEmpty($value){
		return $value === null || $value === '';
	}

    private function toXml($data, $eIsArray=FALSE) {
		if(!$eIsArray) {
		  $this->xml->openMemory(); 
		}
		foreach($data as $key => $value){ 
		  if(is_array($value)){
			$this->xml->startElement($key);
			$this->toXml($value, TRUE);
			$this->xml->endElement();
			continue;
		  }
		  $this->xml->writeElement($key, $value);
		}
		if(!$eIsArray) {
		  $this->xml->endElement();
		  return $this->xml->outputMemory(true);
		}
	}
}