<?php

class alipayg_plugin
{
	static public $info = [
		'name'        => 'alipayg', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '支付宝国际版', //支付插件显示名称
		'author'      => 'Antom', //支付插件作者
		'link'        => 'https://www.antom.com/', //支付插件作者链接
		'types'       => ['alipay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用Client ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'Antom公钥',
				'type' => 'textarea',
				'note' => '填错也可以支付成功但会无法回调',
			],
			'appsecret' => [
				'name' => '应用私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appswitch' => [
				'name' => '选择网关地址',
				'type' => 'select',
				'options' => [
					0=>'亚洲（https://open-sea-global.alipay.com）',
					1=>'北美（https://open-na-global.alipay.com）',
					2=>'欧洲（https://open-de-global.alipay.com）',
				],
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
		'note' => '<p>默认使用Antom在线支付的收银台支付</p>', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf, $clientip;

		return ['type'=>'jump','url'=>'/pay/pay/'.TRADE_NO.'/'];
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice, $method;

		return self::pay();
	}

	//扫码支付
	static public function pay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/AlipayGlobalClient.php');

		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		if(!$channel['currency_code']) $channel['currency_code'] = 'CNY';
		$amount = intval(round($order['realmoney'] * $channel['currency_rate'] * 100));

		$osType = '';
		$terminalType = 'WEB';
		if(checkmobile() || $device == 'mobile'){
			$terminalType = 'WAP';
			if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad') !== false){
				$osType = 'IOS';
			}else{
				$osType = 'ANDROID';
			}
		}

		$client = new AlipayGlobalClient($channel['appswitch'], $channel['appid'], $channel['appsecret'], $channel['appkey']);
		$params = [
			'env' => [
				'terminalType' => $terminalType,
				'osType' => $osType
			],
			'order' => [
				'orderAmount' => [
					'currency' => $channel['currency_code'],
					'value' => $amount,
				],
				'referenceOrderId' => TRADE_NO,
				'orderDescription' => $ordername,
			],
			'paymentRequestId' => TRADE_NO,
			'paymentAmount' => [
				'currency' => $channel['currency_code'],
				'value' => $amount,
			],
			'settlementStrategy' => [
				'settlementCurrency' => $channel['currency_code'],
			],
			'paymentMethod' => [
				'paymentMethodType' => 'ALIPAY_CN',
			],
			'paymentNotifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'paymentRedirectUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'productCode' => 'CASHIER_PAYMENT',
		];
		try{
			$result = $client->execute('/v1/payments/pay', $params);
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$e->getMessage()];
		}

		if(!empty($result['normalUrl'])){
			return ['type'=>'jump','url'=>$result['normalUrl']];
		}else{
			return ['type'=>'error','msg'=>'支付宝下单失败！未获取到支付链接'];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr){
			return ['type'=>'json','data'=>['result'=>['resultCode'=>'FAIL','resultStatus'=>'F','resultMessage'=>'data error']]];
		}

		require(PAY_ROOT.'inc/AlipayGlobalClient.php');
		$client = new AlipayGlobalClient($channel['appswitch'], $channel['appid'], $channel['appsecret'], $channel['appkey']);

		$verify_result = $client->check($json);

		if($verify_result) {//验证成功
			$out_trade_no = $arr['paymentRequestId'];
			$trade_no = $arr['paymentId'];
			$buyer_id = $arr['pspCustomerInfo']['pspCustomerId'];
			$total_amount = $arr['paymentAmount']['value'];

			if ($arr['result']['resultStatus'] == 'S') {
				if($out_trade_no == TRADE_NO){
					processNotify($order, $trade_no, $buyer_id);
				}
			}
			return ['type'=>'json','data'=>['result'=>['resultCode'=>'SUCCESS','resultStatus'=>'S','resultMessage'=>'success']]];
		}
		else {
			//验证失败
			return ['type'=>'json','data'=>['result'=>['resultCode'=>'FAIL','resultStatus'=>'F','resultMessage'=>'sign error']]];
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();
		
		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		if(!$channel['currency_code']) $channel['currency_code'] = 'CNY';
		$amount = intval(round($order['refundmoney'] * $channel['currency_rate'] * 100));

		require(PAY_ROOT.'inc/AlipayGlobalClient.php');
		$client = new AlipayGlobalClient($channel['appswitch'], $channel['appid'], $channel['appsecret'], $channel['appkey']);
		$params = [
			'refundRequestId' => $order['refund_no'],
			'paymentId' => $order['api_trade_no'],
			'refundAmount' => [
				'currency' => $channel['currency_code'],
				'value' => $amount,
			]
		];
		try{
			$result = $client->execute('/v1/payments/refund', $params);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return  ['code'=>0, 'trade_no'=>$result['refundId'], 'refund_fee'=>$result['refundAmount']['value']];
	}

	//关闭订单
	static public function close($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT.'inc/AlipayGlobalClient.php');
		$client = new AlipayGlobalClient($channel['appswitch'], $channel['appid'], $channel['appsecret'], $channel['appkey']);
		$params = [
			'paymentRequestId' => $order['trade_no'],
		];
		try{
			$client->execute('/v1/payments/cancel', $params);
			return ['code'=>0];
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
	}
}