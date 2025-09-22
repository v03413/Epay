<?php
Class AdaTools
{
	public $rsaPrivateKeyFilePath;
	public $rsaPublicKeyFilePath;
	public $rsaPrivateKey;
	public $rsaPublicKey;
	
	public function __construct()
	{
	}
	
	public function generateSignature($url , $params = []):string
	{
		$data = '';
		if (is_array($params)) {
			$data .= $url . json_encode($params);
		} else {
			$data .= $url . $params;
		}
		$sign = $this->SHA1withRSA($data);
		return $sign;
	}
	
	public function SHA1withRSA($data)
	{
		if ($this->checkEmpty($this->rsaPrivateKeyFilePath)) {
			$privKey = trim($this->rsaPrivateKey);
			$key = "-----BEGIN PRIVATE KEY-----\n" . wordwrap($privKey, 64, "\n", true) . "\n-----END PRIVATE KEY-----";
		} else {
			$privKey = file_get_contents($this->rsaPrivateKeyFilePath);
			$key = openssl_get_privatekey($privKey);
		}
		openssl_sign($data , $signature , $key , OPENSSL_ALGO_SHA1);
		return base64_encode($signature);
	}
	
	public function verifySign($signature , $data)
	{
		if ($this->checkEmpty($this->rsaPublicKeyFilePath)) {
			$pubKey = trim($this->rsaPublicKey);
			$key = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($pubKey, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
		} else {
			$pubKey = file_get_contents($this->rsaPublicKeyFilePath);
			$key = openssl_get_publickey($pubKey);
		}
		if (openssl_verify($data , base64_decode($signature) , $key , OPENSSL_ALGO_SHA1)) {
			return true;
		}
		return false;
	}
	
	public function checkEmpty($value)
	{
		if (!isset($value) || ('' === trim($value)) || is_null($value)) {
			return true;
		}
		return false;
	}
}
class AdaPay 
{
	const SDK_VERSION = 'v1.0.0';
	static $gateWayUrl = 'https://api.adapay.tech'; //网关地址
	static $header = ['Content-Type:application/json'];
	static $headerText = ['Content-Type:text/html'];
	static $rsaPrivateKey;
	static $rsaPublicKey = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwN6xgd6Ad8v2hIIsQVnbt8a3JituR8o4Tc3B5WlcFR55bz4OMqrG/356Ur3cPbc2Fe8ArNd/0gZbC9q56Eb16JTkVNA/fye4SXznWxdyBPR7+guuJZHc/VW2fKH2lfZ2P3Tt0QkKZZoawYOGSMdIvO+WqK44updyax0ikK6JlNQIDAQAB";
	static $signType = 'RSA2';
	static $app_id;
	static $api_key;
	public $ada_tools;

	public function __construct($config_info)
	{
		if (empty($config_info) || !is_array($config_info)) {
			throw new \Exception('缺少SDK配置信息');
		}
		if (empty($config_info['app_id'])) {
			throw new \Exception('应用AppID不能为空');
		}
		if (empty($config_info['api_key_live'])) {
			throw new \Exception('API_KEY不能为空');
		}
		if (empty($config_info['rsa_private_key'])) {
			throw new \Exception('商户RSA私钥不能为空');
		}

		$sdk_version = self::SDK_VERSION;
		array_push(self::$header , "sdk_version:{$sdk_version}");
		array_push(self::$headerText , "sdk_version:{$sdk_version}");

		self::$app_id = trim($config_info['app_id']);
		self::$api_key = trim($config_info['api_key_live']);
		self::$rsaPrivateKey = trim($config_info['rsa_private_key']);

		$this->ada_tools = new AdaTools();
		$this->ada_tools->rsaPrivateKey = self::$rsaPrivateKey;
		$this->ada_tools->rsaPublicKey = self::$rsaPublicKey;
	}

	static function config($config = [])
	{
		return new static($config);
	}

	private function get_headers($req_url , $postData , array $header = [])
	{
		array_push($header , 'Authorization:' . self::$api_key);
		array_push($header , 'Signature:' . $this->ada_tools->generateSignature($req_url , $postData));
		return $header;
	}

	private function request($method, $endpoint, $params = null)
	{
		$req_url = self::$gateWayUrl . $endpoint;
		if($method == 'GET'){
			if($params){
				ksort($params);
				$headers = $this->get_headers($req_url , http_build_query($params) , self::$headerText);
				$req_url .= '?' . http_build_query($params);
			}else{
				$headers = $this->get_headers($req_url , "" , self::$headerText);
			}
			$response = $this->curl($req_url , null , $headers);
		}else{
			$headers = $this->get_headers($req_url , $params , self::$header);
			$response = $this->curl($req_url , $params , $headers);
		}
		
		if (!$response || !($result = json_decode($response , true))) {
			throw new Exception('返回内容为空或解析失败');
		}
		if(!isset($result['data']) && isset($result['message'])){
			throw new Exception($result['message']);
		}
		$data = json_decode($result['data'], true);

		if ($data['status'] !== 'succeeded' && $data['status'] !== 'pending' && empty($data['expend'])) {
			throw new Exception('['.$data['error_code'].']'.$data['error_msg']);
		}
		return $data;
	}

	//创建支付对象
	public function createPayment($params)
	{
		$endpoint = '/v1/payments';
		$public_params = [
			'app_id' => self::$app_id,
			'sign_type' => self::$signType,
		];
		$params = array_merge($params, $public_params);
		return $this->request('POST', $endpoint, $params);
	}

	//通用请求
	public function queryAdapay($params)
	{
		self::$gateWayUrl = "https://page.adapay.tech";
		$adapayFuncCode = $params["adapay_func_code"];
		$endpoint = '/v1/'.str_replace(".", "/",$adapayFuncCode);
		$public_params = [
			'app_id' => self::$app_id,
		];
		$params = array_merge($public_params, $params);
		return $this->request('POST', $endpoint, $params);
	}

	//通用请求
	public function requestAdapay($params)
	{
		$adapayFuncCode = $params["adapay_func_code"];
		$endpoint = '/v1/'.str_replace(".", "/",$adapayFuncCode);
		$public_params = [
			'app_id' => self::$app_id,
		];
		$params = array_merge($public_params, $params);
		return $this->request('POST', $endpoint, $params);
	}

	//查询支付对象
	public function queryPayment($id)
	{
		$endpoint = '/v1/payments/'.$id;
		return $this->request('GET', $endpoint, null);
	}

	//创建退款对象
	public function createRefund($params){
		$charge_id = isset($params['payment_id']) ? $params['payment_id'] : '';
		$endpoint = '/v1/payments/'.$charge_id.'/refunds';
		return $this->request('POST', $endpoint, $params);
	}

	//查询退款对象
	public function queryRefund($params){
		$endpoint = '/v1/payments/refunds';
		return $this->request('GET', $endpoint, $params);
	}

	//创建用户对象
	public function createMember($member_id){
		$params = [
			'app_id' => self::$app_id,
			'member_id' => $member_id,
		];
		$endpoint = '/v1/members';
		return $this->request('POST', $endpoint, $params);
	}

	//创建结算账户对象
	public function createSettleAccount($member_id, $account_info){
		$params = [
			'app_id' => self::$app_id,
			'member_id' => $member_id,
			'channel' => 'bank_account',
			'account_info' => $account_info
		];
		$endpoint = '/v1/settle_accounts';
		return $this->request('POST', $endpoint, $params);
	}

	//查询结算账户对象
	public function querySettleAccount($member_id, $settle_account_id){
		$params = [
			'app_id' => self::$app_id,
			'member_id' => $member_id,
			'settle_account_id' => $settle_account_id
		];
		$endpoint = '/v1/settle_accounts/'.$settle_account_id;
		return $this->request('GET', $endpoint, $params);
	}

	//删除结算账户对象
	public function deleteSettleAccount($member_id, $settle_account_id){
		$params = [
			'app_id' => self::$app_id,
			'member_id' => $member_id,
			'settle_account_id' => $settle_account_id
		];
		$endpoint = '/v1/settle_accounts/delete';
		return $this->request('POST', $endpoint, $params);
	}

	//创建支付确认对象
	public function createPaymentConfirm($params){
		$endpoint = '/v1/payments/confirm';
		return $this->request('POST', $endpoint, $params);
	}

	//查询支付确认对象
	public function queryPaymentConfirm($payment_confirm_id){
		$params = [
			'payment_confirm_id' => $payment_confirm_id
		];
		$endpoint = '/v1/payments/confirm/'.$payment_confirm_id;
		return $this->request('GET', $endpoint, $params);
	}

	//创建支付撤销对象
	public function createPaymentReverse($params){
		$endpoint = '/v1/payments/reverse';
		return $this->request('POST', $endpoint, $params);
	}

	//查询支付确认对象
	public function queryPaymentReverse($reverse_id){
		$params = [
			'reverse_id' => $reverse_id
		];
		$endpoint = '/v1/payments/reverse/'.$reverse_id;
		return $this->request('GET', $endpoint, $params);
	}

	//创建取现对象
	public function createDrawCash($params){
		$endpoint = '/v1/cashs';
		$public_params = [
			'app_id' => self::$app_id,
		];
		$params = array_merge($params, $public_params);
		return $this->request('POST', $endpoint, $params);
	}

	//查询取现对象
	public function queryDrawCash($order_no){
		$endpoint = '/v1/cashs/stat';
		$params = [
			'order_no' => $order_no,
		];
		return $this->request('GET', $endpoint, $params);
	}

	//查询账户余额
	public function queryBalance($member_id, $settle_account_id = null){
		$endpoint = '/v1/settle_accounts/balance';
		$params = [
			'app_id' => self::$app_id,
			'member_id' => $member_id
		];
		if($settle_account_id){
			$params['settle_account_id'] = $settle_account_id;
		}
		return $this->request('GET', $endpoint, $params);
	}

	//钱包登录
	public function walletLogin($member_id, $ip){
		$endpoint = '/v1/walletLogin';
		$params = [
			'app_id' => self::$app_id,
			'member_id' => $member_id,
			'ip' => $ip
		];
		return $this->request('GET', $endpoint, $params);
	}

	//账户转账
	public function createTransfer($params){
		$endpoint = '/v1/settle_accounts/transfer';
		$public_params = [
			'app_id' => self::$app_id,
		];
		$params = array_merge($params, $public_params);
		return $this->request('POST', $endpoint, $params);
	}

	//账户转账查询
	public function queryTransfer($params){
		$endpoint = '/v1/settle_accounts/transfer/list';
		$public_params = [
			'app_id' => self::$app_id,
		];
		$params = array_merge($public_params, $params);
		return $this->request('GET', $endpoint, $params);
	}
	
	/**
	 * @param $url
	 * @param null $post
	 * @param null $cookie
	 * @return bool|string
	 */
	protected function curl($url, $post = null, array $headers = [])
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if (!is_null($post)) {
			curl_setopt($ch, CURLOPT_POST, true);
			if (is_array($post)) {
				$postData = json_encode($post);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
			}
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$ret = curl_exec($ch);
		curl_close($ch);
		return $ret;
	}
}