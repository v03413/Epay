<?php

class alipayhk_plugin
{
	static public $info = [
		'name'        => 'alipayhk', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'AlipayHK', //支付插件显示名称
		'author'      => '支付宝', //支付插件作者
		'link'        => 'https://global.alipay.com/', //支付插件作者链接
		'types'       => ['alipay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'Partner ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'MD5 Key',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '支付时选择钱包类型',
				'type' => 'select',
				'options' => [
					'0' => '否',
					'1' => '是',
				],
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => 'PC支付',
			'2' => 'WAP支付',
			'3' => 'APP支付',
		],
		'note' => '支付时选择钱包类型开启后，支付时可选择Alipay或AlipayHK，关闭则默认使用Alipay', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static private $trade_information = ['business_type'=>'5','other_business_type'=>'在线充值'];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		if(checkwechat()){
			if(!$submit2){
				return ['type'=>'jump','url'=>'/pay/submit/'.TRADE_NO.'/'];
			}
			return ['type'=>'page','page'=>'wxopen'];
		}

		return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
	}

	static public function alipay(){
		global $channel, $order, $cdnpublic, $siteurl;

		if($channel['appswitch'] == 1 && empty($_GET['type'])){
			include PAY_ROOT.'select.page.php';
			exit;
		}

		if(checkmobile()){
			if(in_array('2',$channel['apptype'])){
				return self::wappay();
			}elseif(in_array('1',$channel['apptype'])){
				return self::submitpc();
			}elseif(in_array('3',$channel['apptype'])){
				if(checkalipay()){
					return self::apppay();
				}else{
					$code_url = $siteurl.'pay/apppay/'.TRADE_NO.'/?type='.$_GET['type'];
					return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
				}
			}
		}else{
			if(in_array('1',$channel['apptype'])){
				$code_url = '/pay/submitpc/'.TRADE_NO.'/?type='.$_GET['type'];
				return ['type'=>'qrcode','page'=>'alipay_qrcodepc','url'=>$code_url];
			}elseif(in_array('2',$channel['apptype'])){
				$code_url = $siteurl.'pay/wappay/'.TRADE_NO.'/?type='.$_GET['type'];
				return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
			}elseif(in_array('3',$channel['apptype'])){
				$code_url = $siteurl.'pay/apppay/'.TRADE_NO.'/?type='.$_GET['type'];
				return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
			}
		}
	}

	static public function submitpc(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT."inc/AlipayGlobalClient.php");

		$parameter = array(
			'service' => 'create_forex_trade',
			'partner' => trim($alipay_config['partner']),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'out_trade_no' => TRADE_NO,
			'subject' => $ordername,
			'currency' => 'HKD',
			'rmb_fee' => $order['realmoney'],
			'refer_url' => $siteurl,
			'product_code' => 'NEW_WAP_OVERSEAS_SELLER',
			'qr_pay_mode' => '4',
			'qrcode_width' => '230',
			'trade_information' => json_encode(self::$trade_information),
			'_input_charset' => 'utf-8'
		);
		if(!empty($_GET['type'])){
			$parameter['payment_inst'] = trim($_GET['type']);
		}

		$client = new AlipayGlobalClient($alipay_config);
		if(checkmobile() && !checkalipay()){
			try{
				$url = $client->buildRequestForm($parameter, 'REDIRECT');
				$html = get_curl($url, 0, 0, 0, 0, 0, 0, 0, 1);
				$html = mb_convert_encoding($html, 'utf-8', 'gbk');
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
			if(preg_match('!<input name="qrCode" type="hidden" value="(.*?)"!i', $html, $match)){
				$code_url = $match[1];
				return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
			}else{
				return ['type'=>'error','msg'=>'支付宝下单失败！获取二维码失败'];
			}
		}else{
			$html_text = $client->buildRequestForm($parameter);
			$html_text = '<!DOCTYPE html><html><body><style>body{margin:0;padding:0}.waiting{position:absolute;width:100%;height:100%;background:#fff url(/assets/img/load.gif) no-repeat fixed center/80px;}</style><div class="waiting"></div>'.$html_text.'</body></html>';
			return ['type'=>'html','data'=>$html_text];
		}
	}

	static public function wappay(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT."inc/AlipayGlobalClient.php");

		$parameter = array(
			'service' => 'create_forex_trade_wap',
			'partner' => trim($alipay_config['partner']),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'out_trade_no' => TRADE_NO,
			'subject' => $ordername,
			'currency' => 'HKD',
			'rmb_fee' => $order['realmoney'],
			'refer_url' => $siteurl,
			'product_code' => 'NEW_WAP_OVERSEAS_SELLER',
			'trade_information' => json_encode(self::$trade_information),
			'_input_charset' => 'utf-8'
		);
		if(!empty($_GET['type'])){
			$parameter['payment_inst'] = trim($_GET['type']);
		}

		$client = new AlipayGlobalClient($alipay_config);
		$html_text = $client->buildRequestForm($parameter);
		return ['type'=>'html','data'=>$html_text];
	}

	static public function apppay(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $method;

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT."inc/AlipayGlobalClient.php");

		$parameter = array(
			'service' => 'mobile.securitypay.pay',
			'partner' => trim($alipay_config['partner']),
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'out_trade_no' => TRADE_NO,
			'subject' => $ordername,
			'payment_type' => '1',
			'seller_id' => trim($alipay_config['partner']),
			'currency' => 'HKD',
			'rmb_fee' => $order['realmoney'],
			'forex_biz' => 'FP',
			'refer_url' => $siteurl,
			'product_code' => 'NEW_WAP_OVERSEAS_SELLER',
			'trade_information' => json_encode(self::$trade_information),
			'_input_charset' => 'utf-8'
		);
		if(!empty($_GET['type'])){
			$parameter['payment_inst'] = trim($_GET['type']);
		}

		$client = new AlipayGlobalClient($alipay_config);
		$result = $client->buildSdkParam($parameter);
		if($method == 'app'){
			return ['type'=>'app','data'=>$result];
		}
		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		$code_url = 'alipays://platformapi/startApp?appId=20000125&orderSuffix='.urlencode($result).'#Intent;scheme=alipays;package=com.eg.android.AlipayGphone;end';
		return ['type'=>'page','page'=>'alipay_h5','data'=>['code_url'=>$code_url, 'redirect_url'=>$redirect_url]];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT."inc/AlipayGlobalClient.php");

		//计算得出通知验证结果
		$client = new AlipayGlobalClient($alipay_config);
		$verify_result = $client->verify($_POST);

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = $_POST['out_trade_no'];

			//支付宝交易号
			$trade_no = $_POST['trade_no'];

			//买家支付宝
			$buyer_id = $_POST['buyer_id'];

			//交易金额
			$total_fee = $_POST['total_fee'];

			if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
				if($out_trade_no == TRADE_NO){
					processNotify($order, $trade_no, $buyer_id);
				}
			}
			return ['type'=>'html','data'=>'success'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT."inc/AlipayGlobalClient.php");

		//计算得出通知验证结果
		$client = new AlipayGlobalClient($alipay_config);
		$verify_result = $client->verify($_GET);
		if($verify_result) {
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];

			//支付宝交易号
			$trade_no = $_GET['trade_no'];

			//买家支付宝
			$buyer_id = $_GET['buyer_id'];

			//交易金额
			$total_fee = $_GET['total_fee'];

			if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
				if($out_trade_no == TRADE_NO){
                    processReturn($order, $trade_no, $buyer_id);
                }else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error','msg'=>'trade_status='.$_GET['trade_status']];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'签名验证失败！'];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		require(PAY_ROOT."inc/AlipayGlobalClient.php");

		$params = [
			'service' => 'forex_refund',
			'partner' => trim($alipay_config['partner']),
			'out_return_no' => $order['refund_no'],
			'out_trade_no' => $order['trade_no'],
			'return_rmb_amount' => $order['refundmoney'],
			'currency' => 'HKD',
			'gmt_return' => date('Y-m-d H:i:s'),
			'_input_charset' => 'utf-8'
		];
		$client = new AlipayGlobalClient($alipay_config);
		$result = $client->sendRequest($params);
		if(isset($result['is_success']) && $result['is_success'] == 'T'){
			return ['code'=>0];
		}elseif(isset($result['error'])){
			return ['code'=>1,'msg'=>$result['error']];
		}else{
			return ['code'=>1,'msg'=>'未知错误'];
		}
	}
}