<?php

class stripe_plugin
{
	static public $info = [
		'name'        => 'stripe', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'Stripe', //支付插件显示名称
		'author'      => 'Stripe', //支付插件作者
		'link'        => 'https://stripe.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank','paypal'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'API密钥',
				'type' => 'textarea',
				'note' => 'sk_live_开头的密钥',
			],
			'appkey' => [
				'name' => 'Webhook密钥',
				'type' => 'textarea',
				'note' => 'whsec_开头的密钥',
			],
			'appswitch' => [
				'name' => '支付模式',
				'type' => 'select',
				'options' => [0=>'跳转收银台',1=>'直接支付(仅限支付宝/微信)'],
			],
			'currency_code' => [
				'name' => '结算货币',
				'type' => 'select',
				'options' => [
					'CNY' => '人民币 (CNY)',
					'HKD' => '港币 (HKD)',
					'EUR' => '欧元 (EUR)',
					'USD' => '美元 (USD)',
					'AUD' => '澳元 (AUD)',
					'CAD' => '加拿大元 (CAD)',
					'GBP' => '英镑 (GBP)',
					'BRL' => '巴西雷亚尔 (BRL)',
					'CZK' => '克朗 (CZK)',
					'DKK' => '丹麦克朗(DKK)',
					'HUF' => '匈牙利福林 (HUF)',
					'INR' => '印度卢比 (INR)',
					'ILS' => '以色列新谢克尔 (ILS)',
					'JPY' => '日元 (JPY)',
					'MYR' => '马来西亚林吉特 (MYR)',
					'MXN' => '墨西哥比索 (MXN)',
					'TWD' => '新台币 (TWD)',
					'NZD' => '新西兰元 (NZD)',
					'NOK' => '挪威克朗 (NOK)',
					'PHP' => '菲律宾比索 (PHP)',
					'PLN' => '波兰兹罗提 (PLN)',
					'RUB' => '俄罗斯卢布 (RUB)',
					'SGD' => '新加坡元 (SGD)',
					'SEK' => '瑞典克朗 (SEK)',
					'CHF' => '瑞士法郎 (CHF)',
					'THB' => '泰铢 (THB)',
				],
			],
			'currency_rate' => [
				'name' => '货币汇率',
				'type' => 'input',
				'note' => '例如1元人民币兑换0.137美元(USD)，则此处填0.137',
			],
		],
		'select' => null,
		'note' => '需设置WebHook地址：[siteurl]pay/webhook/[channel]/ <br/>侦听的事件，直接支付用: payment_intent.succeeded，跳转收银台用：checkout.session.completed、checkout.session.async_payment_succeeded', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		if($channel['appswitch'] == 1 && $order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($channel['appswitch'] == 1 && $order['typename']=='wxpay'){
			return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
		}

		require_once PAY_ROOT.'inc/StripeClient.php';

		if($order['typename']=='alipay'){
			$payment_method='alipay';
		}elseif($order['typename']=='wxpay'){
			$payment_method='wechat_pay';
		}elseif($order['typename']=='paypal'){
			$payment_method='paypal';
		}else{
			$payment_method='';
		}

		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		if(!$channel['currency_code']) $channel['currency_code'] = 'cny';
		$amount = intval(round($order['realmoney'] * $channel['currency_rate'] * 100));

		try{
			$stripe = new Stripe\StripeClient($channel['appid']);
			$data = [
				'success_url'         => $siteurl.'pay/return/'.TRADE_NO.'/',
				'cancel_url'          => $siteurl.'pay/error/'.TRADE_NO.'/',
				'client_reference_id' => TRADE_NO,
				'line_items' => [[
					'price_data' => [
						'currency'     => strtolower($channel['currency_code']),
						'product_data' => [
							'name' => $ordername
						],
						'unit_amount'  => $amount 
					],
					'quantity'   => 1
				]],
				'mode'                => 'payment'
			];
			if($payment_method)$data['payment_method_types'] = [$payment_method];
			if($payment_method == 'wechat_pay'){
				$data['payment_method_options']['wechat_pay']['client'] = 'web';
			}
			$result = $stripe->request('post', '/v1/checkout/sessions', $data);
			$jump_url = $result['url'];
			return ['type'=>'jump','url'=>$jump_url];
		}catch(Exception $e){
			return ['type'=>'error','msg'=>$e->getMessage()];
		}
	}

	static private function paymentintent($payment_method){
		global $siteurl, $channel, $order, $ordername, $conf;

		require_once PAY_ROOT.'inc/StripeClient.php';

		$stripe = new Stripe\StripeClient($channel['appid']);

		$data = ['type' => $payment_method];
		try{
			$result = $stripe->request('post', '/v1/payment_methods', $data);
			$payment_method_id = $result['id'];
		}catch(Exception $e){
			throw new Exception('创建支付方式失败:'.$e->getMessage());
		}

		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		if(!$channel['currency_code']) $channel['currency_code'] = 'cny';
		$amount = intval(round($order['realmoney'] * $channel['currency_rate'] * 100));

		$data = [
			'amount' => $amount,
			'currency' => strtolower($channel['currency_code']),
			'confirm' => 'true',
			'payment_method' => $payment_method_id,
			'payment_method_types' => [$payment_method],
			'description' => $ordername,
			'metadata' => [
				'order_id' => TRADE_NO,
			],
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
		];
		if($payment_method == 'wechat_pay'){
			$data['payment_method_options']['wechat_pay']['client'] = 'web';
		}
		$result = $stripe->request('post', '/v1/payment_intents', $data);
		if($payment_method == 'alipay'){
			$url = $result['next_action']['alipay_handle_redirect']['url'];
		}elseif($payment_method == 'wechat_pay'){
			$url = $result['next_action']['wechat_pay_display_qr_code']['data'];
		}else{
			$url = $result['next_action']['redirect_to_url']['url'];
		}
		return $url;
	}

	static public function alipay(){
		try{
			$url = self::paymentintent('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'jump','url'=>$url];
	}

	static public function wxpay(){
		try{
			$url = self::paymentintent('wechat_pay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if(checkwechat()){
			return ['type'=>'jump','url'=>$url];
		} elseif (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$url];
		}
	}

	//异步回调
	static public function webhook(){
		global $channel, $order, $DB;

		require_once PAY_ROOT.'inc/Webhook.php';

		$payload = file_get_contents('php://input');
		$data = json_decode($payload, true);
		if(!$data){
			http_response_code(400);
			return ['type'=>'html','data'=>'no data'];
		}

		if(isset($data['data']['object']['client_reference_id'])){
			$out_trade_no = daddslashes($data['data']['object']['client_reference_id']);
		}elseif(isset($data['data']['object']['metadata']['order_id'])){
			$out_trade_no = daddslashes($data['data']['object']['metadata']['order_id']);
		}else{
			http_response_code(400);
			return ['type'=>'html','data'=>'no order_id'];
		}
		$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='$out_trade_no' limit 1");
		if(!$order){
			http_response_code(400);
			return ['type'=>'html','data'=>'no order'];
		}

		$channel = \lib\Channel::get($order['channel']);
		if(!$channel){
			http_response_code(400);
			return ['type'=>'html','data'=>'no channel'];
		}

		$endpoint_secret = $channel['appkey'];
		$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
		$event = null;

		try {
			$event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
		} catch(Exception $e) {
			http_response_code(400);
			return ['type'=>'html','data'=>$e->getMessage()];
		}

		switch($event['type']){
			case 'checkout.session.completed':
				$session = $event['data']['object'];
				if ($session['payment_status'] == 'paid') {
					processNotify($order, $session['payment_intent']);
				}
				break;
			case 'checkout.session.async_payment_succeeded':
				$session = $event['data']['object'];
				processNotify($order, $session['payment_intent']);
				break;
			case 'payment_intent.succeeded':
				$session = $event['data']['object'];
				processNotify($order, $session['id']);
				break;
		}
		return ['type'=>'html','data'=>'success'];
	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//同步回调
	static public function error(){
		return ['type'=>'page','page'=>'error'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require_once PAY_ROOT.'inc/StripeClient.php';

		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		$amount = intval(round($order['refundmoney'] * $channel['currency_rate'] * 100));

		try{
			$stripe = new Stripe\StripeClient($channel['appid']);
			$data = [
				'payment_intent' => $order['api_trade_no'],
				'amount' => $amount,
			];
			$result = $stripe->request('post', '/v1/refunds', $data);
			return ['code'=>0, 'trade_no'=>$result['payment_intent'], 'refund_fee'=>$result['amount']/100];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

}