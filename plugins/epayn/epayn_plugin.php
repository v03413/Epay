<?php

class epayn_plugin
{
	static public $info = [
		'name'        => 'epayn', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '彩虹易支付V2', //支付插件显示名称
		'author'      => '彩虹', //支付插件作者
		'link'        => '', //支付插件作者链接
		'types'       => ['alipay','qqpay','wxpay','bank','jdpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['alipay','wxpay','qqpay','bank'], //支付插件支持的转账方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appurl' => [
				'name' => '接口地址',
				'type' => 'input',
				'note' => '必须以http://或https://开头，以/结尾',
			],
			'appid' => [
				'name' => '商户ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '平台公钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appsecret' => [
				'name' => '商户私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appswitch' => [
				'name' => '是否使用mapi接口',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		if($channel['appswitch']==1){
			return ['type'=>'jump','url'=>'/pay/'.$order['typename'].'/'.TRADE_NO.'/'];
		}else{

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");
		$params = [
			"type" => $order['typename'],
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			"out_trade_no"	=> TRADE_NO,
			"name"	=> $order['name'],
			"money"	=> $order['realmoney']
		];

		$epay = new EpayCore($epay_config);
		if(is_https() && substr($epay_config['apiurl'],0,7)=='http://'){
			$jump_url = $epay->getPayLink($params);
			return ['type'=>'jump','url'=>$jump_url];
		}else{
			$html_text = $epay->pagePay($params, '正在跳转');
			return ['type'=>'html','data'=>$html_text];
		}

		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;
		
		if($channel['appswitch']==1){
			$typename = $order['typename'];
			return self::$typename();
		}else{
			return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
        }
	}

	static private function getDevice(){
		if (checkwechat()) {
			$device = 'wechat';
		}elseif (checkmobbileqq()) {
			$device = 'qq';
		}elseif (checkalipay()) {
			$device = 'alipay';
		}elseif (checkmobile()) {
			$device = 'mobile';
		}else{
			$device = 'pc';
		}
		return $device;
	}

	//统一下单接口
	static private function pay_mapi($method, $type, $auth_code = null, $sub_openid = null, $sub_appid = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");
		$params = [
			"method" => $method,
			"type" => $type,
			"device" => self::getDevice(),
			"clientip" => $clientip,
			"notify_url"	=> $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			"return_url"	=> $siteurl.'pay/return/'.TRADE_NO.'/',
			"out_trade_no"	=> TRADE_NO,
			"name"	=> $order['name'],
			"money"	=> $order['realmoney']
		];
		if($auth_code) $params['auth_code'] = $auth_code;
		if($sub_openid) $params['sub_openid'] = $sub_openid;
		if($sub_appid) $params['sub_appid'] = $sub_openid;

		$epay = new EpayCore($epay_config);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($epay, $params) {
			$result = $epay->apiPay($params);
			return [$result['pay_type'], $result['pay_info']];
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			list($method, $url) = self::pay_mapi('web','alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'html'){
			return ['type'=>'html','data'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			list($method, $url) = self::pay_mapi('web','wxpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'html'){
			return ['type'=>'html','data'=>$url];
		}elseif($method == 'urlscheme'){
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$url];
		}else{
			if(checkwechat()){
				return ['type'=>'jump','url'=>$url];
			} elseif (checkmobile()) {
				return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$url];
			}
		}
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			list($method, $url) = self::pay_mapi('web','qqpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'html'){
			return ['type'=>'html','data'=>$url];
		}else{
			if(checkmobbileqq()){
				return ['type'=>'jump','url'=>$url];
			} elseif(checkmobile() && !isset($_GET['qrcode'])){
				return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$url];
			} else {
				return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$url];
			}
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			list($method, $url) = self::pay_mapi('web','bank');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}

		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'html'){
			return ['type'=>'html','data'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$url];
		}
	}

	//京东支付
	static public function jdpay(){
		try{
			list($method, $url) = self::pay_mapi('web','jdpay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>$ex->getMessage()];
		}
		
		if($method == 'jump'){
			return ['type'=>'jump','url'=>$url];
		}elseif($method == 'html'){
			return ['type'=>'html','data'=>$url];
		}else{
			return ['type'=>'qrcode','page'=>'jdpay_qrcode','url'=>$url];
		}
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		//计算得出通知验证结果
		$epayNotify = new EpayCore($epay_config);
		$verify_result = $epayNotify->verify($_GET);

		if($verify_result) {//验证成功
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];

			//易支付交易号
			$trade_no = $_GET['trade_no'];

			//交易金额
			$money = $_GET['money'];

			//支付人账号
			$buyer = $_GET['buyer'];

			$api_trade_no = $_GET['api_trade_no'];

			if ($_GET['trade_status'] == 'TRADE_SUCCESS') {
				if($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)){
					processNotify($order, $trade_no, $buyer, $api_trade_no);
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

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		//计算得出通知验证结果
		$epayNotify = new EpayCore($epay_config);
		$verify_result = $epayNotify->verify($_GET);
		if($verify_result) {
			//商户订单号
			$out_trade_no = $_GET['out_trade_no'];

			//易支付交易号
			$trade_no = $_GET['trade_no'];

			//交易金额
			$money = $_GET['money'];

			//支付人账号
			$buyer = $_GET['buyer'];

			$api_trade_no = $_GET['api_trade_no'];

			if($_GET['trade_status'] == 'TRADE_SUCCESS') {
				if ($out_trade_no == TRADE_NO && round($money, 2)==round($order['realmoney'], 2)) {
					processReturn($order, $trade_no, $buyer, $api_trade_no);
				}else{
					return ['type'=>'error','msg'=>'订单信息校验失败'];
				}
			}else{
				return ['type'=>'error','msg'=>'trade_status='.$_GET['trade_status']];
			}
		}
		else {
			//验证失败
			return ['type'=>'error','msg'=>'验证失败！'];
		}
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/epay.config.php");
		require(PAY_ROOT."inc/EpayCore.class.php");

		$epay = new EpayCore($epay_config);
		try{
			$result = $epay->refund($order['refund_no'], $order['api_trade_no'], $order['refundmoney']);
			return ['code'=>0, 'trade_no'=>$result['refund_no'], 'refund_fee'=>$result['money']];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}
	
	//转账
	static public function transfer($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require(PLUGIN_ROOT."epayn/inc/epay.config.php");
		require(PLUGIN_ROOT."epayn/inc/EpayCore.class.php");

		$params = [
			'type' => $bizParam['type'],
			'account' => $bizParam['payee_account'],
			'name' => $bizParam['payee_real_name'],
			'money' => $bizParam['money'],
			'remark' => $bizParam['transfer_desc'],
			'out_biz_no' => $bizParam['out_biz_no'],
		];
		$epay = new EpayCore($epay_config);
		try{
			$result = $epay->execute('api/transfer/submit', $params);
			if(isset($result['jumpurl'])){
				return ['code'=>0, 'status'=>$result['status'], 'orderid'=>$result['out_biz_no'], 'paydate'=>$result['paydate'], 'wxpackage'=>$result['jumpurl']];
			}else{
				return ['code'=>0, 'status'=>$result['status'], 'orderid'=>$result['out_biz_no'], 'paydate'=>$result['paydate']];
			}
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
	}

	//转账查询
	static public function transfer_query($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require(PLUGIN_ROOT."epayn/inc/epay.config.php");
		require(PLUGIN_ROOT."epayn/inc/EpayCore.class.php");

		$params = [
			'out_biz_no' => $bizParam['out_biz_no'],
		];
		$epay = new EpayCore($epay_config);
		try{
			$result = $epay->execute('api/transfer/query', $params);
			return ['code'=>0, 'status'=>$result['status'], 'amount'=>$result['amount'], 'paydate'=>$result['paydate'], 'errmsg'=>$result['errmsg']];
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
	}

	//余额查询
	static public function balance_query($channel, $bizParam){
		if(empty($channel))exit();

		require(PLUGIN_ROOT."epayn/inc/epay.config.php");
		require(PLUGIN_ROOT."epayn/inc/EpayCore.class.php");

		$params = [
			'out_biz_no' => $bizParam['out_biz_no'],
		];
		$epay = new EpayCore($epay_config);
		try{
			$result = $epay->execute('api/transfer/balance', $params);
			return ['code'=>0, 'amount'=>$result['available_money']];
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
	}
}