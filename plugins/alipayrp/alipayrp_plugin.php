<?php

class alipayrp_plugin
{
	static public $info = [
		'name'        => 'alipayrp', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '支付宝现金红包', //支付插件显示名称
		'author'      => '支付宝', //支付插件作者
		'link'        => 'https://b.alipay.com/signing/productSetV2.htm', //支付插件作者链接
		'types'       => ['alipay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用APPID',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '应用私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appmchid' => [
				'name' => '收款方支付宝UID',
				'type' => 'input',
				'note' => '留空则使用商户绑定的支付宝UID',
			],
		],
		'note' => '<p>需要签约支付宝现金红包才能使用！</p><p>使用公钥证书模式，需将<font color="red">应用公钥证书、支付宝公钥证书、支付宝根证书</font>3个crt文件放置于<font color="red">/plugins/alipayrp/cert/</font>文件夹（或<font color="red">/plugins/alipayrp/cert/应用APPID/</font>文件夹）</p><p>订阅“资金单据状态变更通知”，应用网关地址：[siteurl]pay/notify/[channel]/</p>', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $submit2;

		if(checkalipay()){
			return ['type'=>'jump','url'=>'/pay/pagepay/'.TRADE_NO.'/?d=1'];
		}else{
			return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $mdevice;
		if($mdevice=='alipay'){
			return ['type'=>'jump','url'=>'/pay/pagepay/'.TRADE_NO.'/?d=1'];
		}else{
			return self::qrcode();
		}
	}

	static private function getPayee(){
		global $order, $channel, $DB;

		if(!empty($channel['appmchid'])) return $channel['appmchid'];
		$alipay_uid = $DB->findColumn('user', 'alipay_uid', ['uid'=>$order['uid']]);
		return $alipay_uid;
	}

	//扫码支付
	static public function qrcode(){
		global $siteurl, $order, $DB;

		if(empty(self::getPayee())){
			return ['type'=>'error','msg'=>'当前商户未绑定支付宝账号'];
		}

		$code_url = $siteurl.'pay/pagepay/'.TRADE_NO.'/';
		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//红包转账页面支付
	static public function pagepay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;
		
		$alipay_config = require(PAY_ROOT.'inc/config.php');
		[$user_type, $user_id] = alipay_oauth($alipay_config);
		if($user_type == 'openid'){
			return ['type'=>'error','msg'=>'支付宝快捷登录获取uid失败，需将用户标识切换到uid模式'];
		}
		
		$blocks = checkBlockUser($user_id, TRADE_NO);
		if($blocks) return $blocks;

		$alipay_config['notify_url'] = $conf['localurl'].'pay/notify/'.TRADE_NO.'/';
		$bizContent = [
			'out_biz_no' => TRADE_NO,
			'trans_amount' => $order['realmoney'],
			'product_code' => 'STD_RED_PACKET',
			'biz_scene' => 'PERSONAL_PAY',
			'order_title' => $ordername,
			'business_params' => json_encode(['sub_biz_scene'=>'REDPACKET','payer_binded_alipay_uid'=>$user_id], JSON_UNESCAPED_UNICODE)
		];
		try{
			$aop = new \Alipay\AlipayTradeService($alipay_config);
			$result = $aop->transAppPay($bizContent);
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$e->getMessage()];
		}
		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		$code_url = 'alipays://platformapi/startApp?appId=20000125&orderSuffix='.urlencode($result).'#Intent;scheme=alipays;package=com.eg.android.AlipayGphone;end';
		return ['type'=>'page','page'=>'alipay_h5','data'=>['code_url'=>$code_url, 'redirect_url'=>$redirect_url]];
	}

	//订单查询
	static public function query($trade_no){
		global $order, $channel;
		
		$alipay_config = require(PAY_ROOT.'inc/config.php');
		$bizContent = [
			'product_code' => 'STD_RED_PACKET',
			'biz_scene' => 'PERSONAL_PAY',
			'out_biz_no' => $trade_no
		];
		try{
			$aop = new \Alipay\AlipayTransferService($alipay_config);
			$result = $aop->aopExecute('alipay.fund.trans.common.query', $bizContent);
			print_r($result);
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'订单查询失败！'.$e->getMessage()];
		}
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	//异步回调
	static public function notify(){
		global $channel, $order, $DB, $conf;

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		$aop = new \Alipay\AlipayTransferService($alipay_config);

		$verify_result = $aop->check($_POST);
		if($verify_result) {
			if($_POST['msg_method'] == 'alipay.fund.trans.order.changed'){
				$bizContent = json_decode($_POST['biz_content'], true);
				if($bizContent && $bizContent['product_code'] == 'STD_RED_PACKET' && $bizContent['biz_scene'] == 'PERSONAL_PAY'){
					$out_trade_no = $bizContent['out_biz_no']; //商户订单号
					$order_id = $bizContent['order_id']; //支付宝转账单据号
					$trans_amount = $bizContent['trans_amount']; //转账金额
	
					$order = $DB->getRow("SELECT * FROM pre_order WHERE trade_no='$out_trade_no' limit 1");
					if($order && $bizContent['status'] == 'SUCCESS'){
						if($order['settle']<=1){
							usleep(300000);
							$out_biz_no = date("YmdHis").rand(11111,99999);
							$payee_user_id = self::getPayee();
							try{
								$aop->redPacketTansfer($out_biz_no, $trans_amount, $payee_user_id, $conf['sitename'], $order_id);
								$order['settle'] = 2;
							}catch(Exception $e){
								$aop->writeLog('redPacketTansfer:'.$e->getMessage());
								$order['settle'] = 3;
							}
						}
						processNotify($order, $order_id);
					}
				}
			}
			return ['type'=>'html','data'=>'success'];
		}
		else {
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		$alipay_config = require(PAY_ROOT.'inc/config.php');
		$out_biz_no = date("YmdHis").rand(11111,99999);
		try{
			$aop = new \Alipay\AlipayTransferService($alipay_config);
			$result = $aop->redPacketRefund($out_biz_no, $order['api_trade_no'], $order['refundmoney']);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return  ['code'=>0, 'trade_no'=>$result['refund_order_id'], 'refund_fee'=>$result['refund_amount'], 'refund_time'=>$result['refund_date']];
	}

}