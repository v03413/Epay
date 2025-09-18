<?php

class paypal_plugin
{
	static public $info = [
		'name'        => 'paypal', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'PayPal', //支付插件显示名称
		'author'      => 'PayPal', //支付插件作者
		'link'        => 'https://www.paypal.com/', //支付插件作者链接
		'types'       => ['paypal'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'ClientId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'ClientSecret',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '模式选择',
				'type' => 'select',
				'options' => [0=>'线上模式',1=>'沙盒模式'],
			],
			'currency_code' => [
				'name' => '结算货币',
				'type' => 'select',
				'options' => [
					'USD' => '美元 (USD)',
					'AUD' => '澳元 (AUD)',
					'BRL' => '巴西雷亚尔 (BRL)',
					'CAD' => '加拿大元 (CAD)',
					'CNY' => '人民币 (CNY)',
					'CZK' => '克朗 (CZK)',
					'DKK' => '丹麦克朗(DKK)',
					'EUR' => '欧元 (EUR)',
					'HKD' => '港币 (HKD)',
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
					'GBP' => '英镑 (GBP)',
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
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $DB;

		require_once(PAY_ROOT."inc/PayPalClient.php");

		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		$money = round($order['realmoney'] * $channel['currency_rate'], 2);

		$parameter = [
            'intent'            => 'CAPTURE',
            'purchase_units'    => [
                [
                    'amount'        => [
                        'currency_code' => $channel['currency_code'],
                        'value'         => $money,
                    ],
                    'description'   => $order['name'],
					'custom_id'     => TRADE_NO,
                    'invoice_id'    => TRADE_NO,
                ],
            ],
            'application_context'=> [
                'cancel_url'    => $siteurl.'pay/cancel/'.TRADE_NO.'/',
                'return_url'    => $siteurl.'pay/return/'.TRADE_NO.'/',
            ],
        ];

		try {
			$approvalUrl = \lib\Payment::lockPayData(TRADE_NO, function() use($channel, $parameter) {
				$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
				$result = $client->createOrder($parameter);

				$approvalUrl = null;
				foreach($result['links'] as $link){
					if($link['rel'] == 'approve'){
						$approvalUrl = $link['href'];
					}
				}
				if(empty($approvalUrl)){
					throw new Exception('获取支付链接失败');
				}
				return $approvalUrl;
			});

			return ['type'=>'jump','url'=>$approvalUrl];
		}
		catch (Exception $ex) {
			sysmsg('PayPal下单失败：'.$ex->getMessage());
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/PayPalClient.php");
		
		if (isset($_GET['token']) && isset($_GET['PayerID'])) {
		
			$token = $_GET['token'];
			try {
				$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
				$result = $client->captureOrder($token);
			} catch (Exception $ex) {
				return ['type'=>'error','msg'=>'支付订单失败 '.$ex->getMessage()];
			}

			$captures = $result['purchase_units'][0]['payments']['captures'][0];
			$amount = $captures['seller_receivable_breakdown']['gross_amount']['value'];
			$trade_no = $captures['id'];
			$out_trade_no = $captures['invoice_id'];
			$buyer = $result['payer']['email_address'];

			if($out_trade_no == TRADE_NO){
				processReturn($order, $trade_no, $buyer);
			}else{
				return ['type'=>'error','msg'=>'订单信息校验失败'];
			}
		} else {
			return ['type'=>'error','msg'=>'PayPal返回参数错误'];
		}
	}

	static public function cancel(){
		return ['type'=>'page','page'=>'error'];
	}

	static public function webhook(){
		global $channel, $order;
		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr || empty($arr['event_type'])){
            exit('事件类型为空');
        }
		if(!in_array($arr['event_type'], ['PAYMENT.CAPTURE.COMPLETED'])){
            exit('其他事件('.$arr['event_type'].':'.$arr['summary'].')');
        }
		if(empty($channel['appsecret'])){
			exit('未配置webhookid');
		}

		$crc32 = crc32($json);
        if (empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID']) || empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']) || empty($crc32)) {
			exit('签名数据为空');
        }
        $sign_string = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'].'|'.$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'].'|'.$channel['appsecret'].'|'.$crc32;

        // 通过PAYPAL-CERT-URL头信息去拿公钥
        $public_key = openssl_pkey_get_public(get_curl($_SERVER['HTTP_PAYPAL_CERT_URL']));
        $details = openssl_pkey_get_details($public_key);
        $verify = openssl_verify($sign_string, base64_decode($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']), $details['key'], 'SHA256');
        if($verify != 1)
        {
			exit('签名验证失败');
        }

		$resource = $arr['resource'];
		$amount = $resource['amount']['value'];
		$trade_no = $resource['id'];
		$out_trade_no = $resource['invoice_id'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require_once(PAY_ROOT."inc/PayPalClient.php");

		if(!$channel['currency_rate']) $channel['currency_rate'] = 1;
		$money = round($order['refundmoney'] * $channel['currency_rate'], 2);

		$parameter = [
            'amount'    => [
                'currency_code'  => $channel['currency_code'],
                'value'     => $money,
            ],
        ];

		try{
			$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$res = $client->refundPayment($order['api_trade_no'], $parameter);
			$result = ['code'=>0, 'trade_no'=>$res['id'], 'refund_fee'=>$res['amount']['value']];
		}catch(Exception $e){
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

}