<?php

/**
 * https://www.yuque.com/youyun-8yqqt/vpbgq7
 */
class HuolianClient
{
    //支付接口地址
    private $gateway_url = 'https://open.lianok.com/open/v1/api/biz/do';

    private $upload_url = 'https://open.lianok.com/open/v1/api/biz/file';

    //对接商授权编号
    private $authCode;

    //对接商MD5加密盐
    private $salt;

    
    public function __construct($authCode, $salt){
        $this->authCode = $authCode;
        $this->salt = $salt;
    }

    //发起请求
    public function execute($resource, $params)
    {
        $params = array_filter($params, function($v){
            return $v!==null;
        });
        $commonData = [
			'authCode' => $this->authCode,
            'requestTime' => date('YmdHis'),
			'resource' => $resource,
            'versionNo' => '1'
        ];
        $commonData['sign'] = $this->make_sign(array_merge($commonData, $params));
        $commonData['params'] = json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);

        $data = get_curl($this->gateway_url, json_encode($commonData, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);

        $result = json_decode($data, true);

        if(isset($result['code']) && $result['code']==0 && $result['status'] == 200){
            return $result['data'];
        }else{
            throw new Exception($result['message']?$result['message']:'返回数据解析失败');
        }
    }

    //上传文件
    public function upload($resource, $file_path, $file_name)
    {
        $params = [
            'authCode' => $this->authCode,
            'requestTime' => date('YmdHis'),
			'resource' => $resource,
        ];
        $params['sign'] = $this->make_sign($params);
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $mime_type = self::mime_content_type($file_ext);
        if(empty($mime_type)) $mime_type = 'image/jpeg';
        $params['file'] = new \CURLFile($file_path, $mime_type, $file_name);

        $data = get_curl($this->upload_url, $params);

        $result = json_decode($data, true);

        if(isset($result['code']) && $result['code']==0){
            return $result['data'];
        }else{
            throw new Exception($result['message']?$result['message']:'返回数据解析失败');
        }
    }

    private static function mime_content_type($ext)
    {
        $mime_types = [
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
        ];
        return $mime_types[$ext];
    }

    public function verify($param){
        if(!isset($param['sign'])) return false;
        unset($param['code']);
        unset($param['message']);
        $sign = $this->make_sign($param);
        return $sign === $param['sign'];
    }

    private function make_sign($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!==null){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = strtolower($signstr);
		$signstr .= $this->salt;
		$sign = md5($signstr);
		return $sign;
	}
}