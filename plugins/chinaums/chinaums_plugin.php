<?php

class chinaums_plugin
{
	static public $info = [
		'name'        => 'chinaums', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '银联商务', //支付插件显示名称
		'author'      => '银联商务', //支付插件作者
		'link'        => 'https://open.chinaums.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'AppId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'AppKey',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '商户号mid',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '终端号tid',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '通讯密钥',
				'type' => 'input',
				'note' => '',
			],
			'msgsrcid' => [
				'name' => '来源编号',
				'type' => 'input',
				'note' => '4位来源编号',
			],
			'appswitch' => [
				'name' => '环境选择',
				'type' => 'select',
				'options' => [0=>'生产环境',1=>'测试环境'],
			],
		],
		'select_alipay' => [
			'1' => '扫码支付',
			'2' => 'H5支付',
			//'3' => 'APP支付',
		],
		'select_wxpay' => [
			'1' => '扫码支付',
			'2' => 'H5支付',
			'3' => 'H5转小程序支付',
			//'4' => 'APP支付',
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];


	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			if(in_array('2',$channel['apptype']) && checkmobile()){
				$code_url = self::h5pay('alipay');
				return ['type'=>'jump','url'=>$code_url];
			}else{
				return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='wxpay'){
			if(in_array('2',$channel['apptype']) && checkmobile()){
				$code_url = self::h5pay('wxpay');
				return ['type'=>'jump','url'=>$code_url];
			}elseif(in_array('3',$channel['apptype']) && checkmobile()){
				$code_url = self::h5pay('wxminipay');
				return ['type'=>'jump','url'=>$code_url];
			}elseif(in_array('4',$channel['apptype']) && checkmobile() && strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone OS')!==false){
				return ['type'=>'jump','url'=>'/pay/wxapppay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			if(in_array('2',$channel['apptype']) && $device=='mobile'){
				$code_url = self::h5pay('alipay');
				return ['type'=>'jump','url'=>$code_url];
			}else{
				return self::alipay();
			}
		}elseif($order['typename']=='wxpay'){
			if(in_array('2',$channel['apptype']) && $device=='mobile'){
				$code_url = self::h5pay('wxpay');
				return ['type'=>'jump','url'=>$code_url];
			}elseif(in_array('3',$channel['apptype']) && $device=='mobile'){
				$code_url = self::h5pay('wxminipay');
				return ['type'=>'jump','url'=>$code_url];
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//扫码下单
	static private function qrcode(){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], $channel['appswitch'] == 1);
		
		$path = '/v1/netpay/bills/get-qrcode';
		$time = time();
		$param = [
			'msgId' => md5(uniqid(mt_rand(), true)),
			'requestTimestamp' => date('Y-m-d H:i:s', $time),
			'mid' => $channel['appmchid'],
			'tid' => $channel['appurl'],
			'instMid' => 'QRPAYDEFAULT',
			'billNo' => $channel['msgsrcid'].TRADE_NO,
			'billDate' => date('Y-m-d', $time),
			'billDesc' => $ordername,
			'totalAmount' => intval(round($order['realmoney']*100)),
			'notifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'returnUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'clientIp' => $clientip,
		];
		if($order['profits']>0){
			self::handleProfits($param);
		}

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $path, $param, $time) {
			$result = $client->request($path, $param, $time);
			if(isset($result['errCode']) && $result['errCode']=='SUCCESS'){
				return $result['billQRCode'];
			}elseif(isset($result['errMsg'])){
				throw new Exception($result['errMsg']);
			}elseif(isset($result['errInfo'])){
				throw new Exception($result['errInfo']);
			}else{
				throw new Exception('返回数据解析失败');
			}
		});
	}

	static private function h5pay($pay_type){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], $channel['appswitch'] == 1);
		
		if($pay_type == 'alipay'){
			$path = '/v1/netpay/trade/h5-pay';
		}elseif($pay_type == 'wxpay'){
			$path = '/v1/netpay/wxpay/h5-pay';
		}elseif($pay_type == 'wxminipay'){
			$path = '/v1/netpay/wxpay/h5-to-minipay';
		}
		
		$time = time();
		$param = [
			'msgId' => md5(uniqid(mt_rand(), true)),
			'requestTimestamp' => date('Y-m-d H:i:s', $time),
			'mid' => $channel['appmchid'],
			'tid' => $channel['appurl'],
			'instMid' => 'H5DEFAULT',
			'merOrderId' => $channel['msgsrcid'].TRADE_NO,
			'orderDesc' => $ordername,
			'totalAmount' => intval(round($order['realmoney']*100)),
			'notifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'returnUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'clientIp' => $clientip,
		];
		if($pay_type == 'wxpay'){
			$param['sceneType'] = 'AND_WAP';
			$param['merAppName'] = $conf['sitename'];
			$param['merAppId'] = $siteurl;
		}
		if($order['profits']>0){
			self::handleProfits($param);
		}

		$url = $client->requestGet($path, $param, $time);

		\lib\Payment::updateOrderCombine(TRADE_NO);

		return $url;
	}

	//APP支付(跳转小程序)
	static private function apppay($pay_type, $sub_app_id = null){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], $channel['appswitch'] == 1);
		
		if($pay_type == 'alipay'){
			$path = '/v1/netpay/trade/app-pre-order';
		}elseif($pay_type == 'wxpay'){
			$path = '/v1/netpay/wx/app-pre-order';
		}elseif($pay_type == 'bank'){
			$path = '/v1/netpay/uac/app-order';
		}
		$time = time();
		$param = [
			'msgId' => md5(uniqid(mt_rand(), true)),
			'requestTimestamp' => date('Y-m-d H:i:s', $time),
			'mid' => $channel['appmchid'],
			'tid' => $channel['appurl'],
			'instMid' => 'APPDEFAULT',
			'merOrderId' => $channel['msgsrcid'].TRADE_NO,
			'orderDesc' => $ordername,
			'totalAmount' => intval(round($order['realmoney']*100)),
			'notifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'clientIp' => $clientip,
		];
		if($sub_app_id){
			$param['subAppId'] = $sub_app_id;
		}
		if($order['profits']>0){
			self::handleProfits($param);
		}

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $path, $param, $time) {
			$result = $client->request($path, $param, $time);
			if(isset($result['errCode']) && $result['errCode']=='SUCCESS'){
				return $result['appPayRequest'];
			}elseif(isset($result['errMsg'])){
				throw new Exception($result['errMsg']);
			}elseif(isset($result['errInfo'])){
				throw new Exception($result['errInfo']);
			}else{
				throw new Exception('返回数据解析失败');
			}
		});
	}

	static private function handleProfits(&$param){
		global $order, $channel;
		$psreceiver = \lib\ProfitSharing\CommUtil::getReceiver($order['profits']);
		if($psreceiver){
			$param['divisionFlag'] = true;
			$suborders = [];
			$i = 1;
			$allmoney = 0;
			foreach($psreceiver['info'] as $receiver){
				$psmoney = intval(round(floor($order['realmoney'] * $receiver['rate'])));
				$suborders[] = [
					'mid' => $receiver['account'],
					'merOrderId' => $channel['msgsrcid'].TRADE_NO.$i++,
					'totalAmount' => $psmoney
				];
				$allmoney += $psmoney;
			}
			$param['platformAmount'] = $param['totalAmount'] - $allmoney;
			$param['subOrders'] = $suborders;
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		global $mdevice;
		try{
			$code_url = self::qrcode();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		if(checkalipay() || $mdevice=='alipay'){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	//支付宝APP支付
	static public function alipayapp(){
		global $channel, $method;
		try{
			$result = self::apppay('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		if($method == 'app'){
			return ['type'=>'app','data'=>$result];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice;
		try{
			$code_url = self::qrcode();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if(checkwechat() || $mdevice=='wechat'){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile() || $device=='mobile') {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信APP支付
	static public function wxapppay(){
		global $channel, $method;
		$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
		if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
		try{
			$result = self::apppay('wxpay', $wxinfo['appid']);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		if($method == 'app'){
			return ['type'=>'app','data'=>$result];
		}

		$param = [
			'nonceStr' => $result['noncestr'],
			'package' => $result['package'],
			'partnerId' => $result['partnerid'],
			'prepayId' => $result['prepayid'],
			'timeStamp' => $result['timestamp'],
			'sign' => $result['sign'],
		];
		$code_url = 'weixin://app/'.$result['appid'].'/pay/?'.http_build_query($param);
		return ['type'=>'qrcode','page'=>'wxpay_h5','url'=>$code_url];
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], $channel['appswitch'] == 1);

		$verifyResult = $client->verify($_POST, $channel['appsecret']);

		if($verifyResult){
			if($_POST['instMid'] == 'H5DEFAULT'){
				if($_POST['status'] == 'TRADE_SUCCESS'){
					$out_trade_no = substr($_POST['merOrderId'],4);
					$trade_no = $_POST['merOrderId'];
					$money = $_POST['totalAmount'];
					$buyer = $_POST['buyerId'];
					$bill_trade_no = $_POST['targetOrderId'];
					if($out_trade_no == TRADE_NO && $money==strval($order['realmoney']*100)){
						processNotify($order, $trade_no, $buyer, $bill_trade_no);
					}
					return ['type'=>'html','data'=>'SUCCESS'];
				}else{
					return ['type'=>'html','data'=>'FAILED'];
				}
			}else{
				if($_POST['billStatus'] == 'PAID'){
					$out_trade_no = substr($_POST['billNo'],4);
					$billPayment = json_decode($_POST['billPayment'], true);
					$trade_no = $_POST['billNo'];
					$money = $_POST['totalAmount'];
					$buyer = $billPayment['buyerId'];
					$bill_trade_no = $billPayment['targetOrderId'];
					if($out_trade_no == TRADE_NO && $money==strval($order['realmoney']*100)){
						processNotify($order, $trade_no, $buyer, $bill_trade_no);
					}
					return ['type'=>'html','data'=>'SUCCESS'];
				}else{
					return ['type'=>'html','data'=>'FAILED'];
				}
			}
		}
		return ['type'=>'html','data'=>'FAILED'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		require(PAY_ROOT."inc/Build.class.php");

		$client = new ChinaumsBuild($channel['appid'], $channel['appkey'], $channel['appswitch'] == 1);
		
		if($order['combine'] == 1){ //H5支付退款
			$path = '/v1/netpay/refund';
			$time = time();
			$param = [
				'msgId' => md5(uniqid(mt_rand(), true)),
				'requestTimestamp' => date('Y-m-d H:i:s', $time),
				'mid' => $channel['appmchid'],
				'tid' => $channel['appurl'],
				'instMid' => 'H5DEFAULT',
				'merOrderId' => $order['api_trade_no'],
				'billDate' => date('Y-m-d', strtotime($order['addtime'])),
				'refundOrderId' => $channel['msgsrcid'].$order['refund_no'],
				'refundAmount' => $order['refundmoney']*100,
			];
		}else{
			$path = '/v1/netpay/bills/refund';
			$time = time();
			$param = [
				'msgId' => md5(uniqid(mt_rand(), true)),
				'requestTimestamp' => date('Y-m-d H:i:s', $time),
				'mid' => $channel['appmchid'],
				'tid' => $channel['appurl'],
				'instMid' => 'QRPAYDEFAULT',
				'billNo' => $order['api_trade_no'],
				'billDate' => date('Y-m-d', strtotime($order['addtime'])),
				'refundOrderId' => $channel['msgsrcid'].$order['refund_no'],
				'refundAmount' => $order['refundmoney']*100,
			];
		}

		$result = $client->request($path, $param, $time);
		if(isset($result['errCode']) && $result['errCode']=='SUCCESS'){
			return ['code'=>0, 'trade_no'=>$result['billNo'], 'refund_fee'=>round($result['refundAmount']/100, 2)];
		}else{
			return ['code'=>-1, 'msg'=>$result['errMsg']?$result['errMsg']:'返回数据解析失败'];
		}
	}
}