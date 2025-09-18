<?php

/**
 * https://mer.jd.com/open/?agg_recpt
 */
class PayApp
{
    const GATEWAY = 'https://openapi.duolabao.com';
    private $accessKey;
	private $secretKey;

    function __construct($accessKey, $secretKey)
	{
		$this->accessKey = $accessKey;
		$this->secretKey = $secretKey;
	}

    public function submit($path, $param = null){
        $body = $param ? json_encode($param, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $time = time();
        $token = $this->get_token($time, $path, $body);
        $headers = [
            'Content-Type: application/json',
            'accessKey: ' . $this->accessKey,
            'timestamp: ' . $time,
            'token: ' . $token
        ];
		$path = implode('/', array_map('urlencode', explode('/', $path)));
        $response = $this->curl($path, $headers, $body);
        $result = json_decode($response, true);
        if($result['result'] == 'success'){
            return $result['data'];
        }elseif(isset($result['error'])){
            throw new Exception('['.$result['error']['errorCode'].']'.$result['error']['errorMsg']);
        }else{
            throw new Exception('接口请求失败');
        }
    }

	public function submitNew($path, $param = null){
        $body = $param ? json_encode($param, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $time = time();
        $token = $this->get_token($time, $path, $body);
        $headers = [
            'Content-Type: application/json',
            'accessKey: ' . $this->accessKey,
            'timestamp: ' . $time,
            'token: ' . $token
        ];
		$path = implode('/', array_map('urlencode', explode('/', $path)));
        $response = $this->curl($path, $headers, $body);
        $result = json_decode($response, true);
        if(isset($result['success']) && $result['success']===true || isset($result['result']) && $result['result']===true){
            return $result;
        }elseif(isset($result['errorCode'])){
            throw new Exception('['.$result['errorCode'].']'.$result['errorMsg']);
        }elseif(isset($result['msg'])){
            throw new Exception('['.$result['code'].']'.$result['msg']);
        }elseif(isset($result['message'])){
            throw new Exception('['.$result['code'].']'.$result['message']);
        }else{
            throw new Exception('接口请求失败');
        }
    }

    public function verifyNotify($body = null)
	{
		$timestamp = $_SERVER['HTTP_TIMESTAMP'];
		$token = $this->get_token($timestamp, null, $body);
		if ($token === $_SERVER['HTTP_TOKEN']) {
			return true;
		}
		return false;
	}

    private function get_token($time, $path, $body){
        $sign_data = [
			'secretKey' => $this->secretKey,
			'timestamp' => $time,
		];
		if($path) $sign_data['path'] = $path;
		if($body) $sign_data['body'] = $body;
		$o = '';
		foreach ($sign_data as $k => $v) {
			 $o .= "{$k}={$v}&";
		}
		$o = substr($o , 0 , -1);
		$token = strtoupper(sha1($o));
        return $token;
    }

    private function curl($path, $headers, $post = null)
	{
		$url = self::GATEWAY . $path;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Encoding: gzip,deflate";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.9";
		$httpheader[] = "Connection: keep-alive";
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($httpheader, $headers));
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}