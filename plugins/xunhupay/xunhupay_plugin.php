<?php

class xunhupay_plugin
{
	static public $info = [
		'name'        => 'xunhupay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '虎皮椒支付', //支付插件显示名称
		'author'      => '虎皮椒', //支付插件作者
		'link'        => 'https://www.xunhupay.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'API密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '网关地址',
				'type' => 'input',
				'note' => '不填写默认为https://api.xunhupay.com/payment/do.html',
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		$typename = $order['typename'];
		return self::$typename();
	}

	//通用下单
	static private function addOrder($type){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl, $device;

		require_once(PAY_ROOT."inc/XunhupayClient.php");

		$params = [
			'version'   => '1.1',
			'trade_order_id'=> TRADE_NO,
			'payment'   => $type,
			'total_fee' => $order['realmoney'],
			'title'     => $ordername,
			'notify_url'=>  $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url'=> $siteurl.'pay/return/'.TRADE_NO.'/',
		];

		if($type == 'wechat' && (checkmobile() || $device=='mobile')){
			$params['type'] = 'WAP';
			$params['wap_url'] = $_SERVER['HTTP_HOST'];
			$params['wap_name'] = $conf['sitename'];
		}

		$client = new XunhupayClient($channel['appid'],$channel['appkey'],$channel['appurl']);
		$result = $client->do_payment($params);
		if(checkmobile() || $device=='mobile'){
			return ['jump', $result['url']];
		}else{
			$code_url = $client->parseQrcode($result['url_qrcode']);
			return ['qrcode', $code_url];
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			[$type, $code_url] = self::addOrder('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}

		if($type == 'jump'){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			[$type, $code_url] = self::addOrder('wechat');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($type == 'jump'){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;
		
		if(!isset($_POST) || !isset($_POST['hash']) || !isset($_POST['trade_order_id'])) {
			return ['type'=>'html','data'=>'data_fail'];
		}

		require_once(PAY_ROOT."inc/XunhupayClient.php");

		$client = new XunhupayClient($channel['appid'],$channel['appkey'],$channel['appurl']);
		$verify_result = $client->verify($_POST);
		if(!$verify_result) {
			return ['type'=>'html','data'=>'sign_fail'];
		}

		if($_POST['status']=='OD'){
			$out_trade_no = $_POST['trade_order_id'];
			$order_id = $_POST['open_order_id'];
			$total_fee = $_POST['total_fee'];
			if($out_trade_no == TRADE_NO && round($total_fee,2)==round($order['realmoney'],2)){
				processNotify($order, $order_id);
			}
			return ['type'=>'html','data'=>'success'];
		}
		return ['type'=>'html','data'=>'fail'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();
		require_once(PAY_ROOT."inc/XunhupayClient.php");
		$client = new XunhupayClient($channel['appid'],$channel['appkey'],$channel['appurl']);
		$params = [
			'open_order_id' => $order['api_trade_no']
		];
		try{
			$result = $client->do_refund($params);
			return ['code'=>0, 'trade_no'=>$result['transaction_id'], 'refund_fee'=>$result['refund_fee']];
		}catch(Exception $ex){
			return ['code'=>-1,'msg'=>$ex->getMessage()];
		}
	}
}