<?php
if(!defined('IN_CRONLITE'))exit();
$paytype = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
?>
<div id="toc2" class="api_block">
			<h3>
				协议规则
			</h3>
			<p>请求数据格式：application/x-www-form-urlencoded</p>
      <p>返回数据格式：JSON</p>
			<p>签名算法：MD5</p>
			<p>字符编码：UTF-8</p>
		</div>
		<div id="pay0" class="api_block">
			<h3>
				页面跳转支付
			</h3>
<p><font color="#777">此接口可用于用户前台直接发起支付，使用form表单跳转或拼接成url跳转。</font></p>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>submit.php</font></p>
<p>请求方式：<font color="#29389f">POST 或 GET（推荐POST，不容易被劫持或屏蔽）</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>支付方式</td><td>type</td><td>否</td><td>String</td><td>alipay</td><td><a href="#pay4" style="color:#4585d2">支付方式列表</a></td></tr>
  <tr><td>商户订单号</td><td>out_trade_no</td><td>是</td><td>String</td><td>20160806151343349</td><td></td></tr>
  <tr><td>异步通知地址</td><td>notify_url</td><td>是</td><td>String</td><td>http://www.pay.com/notify_url.php</td><td>服务器异步通知地址</td></tr>
  <tr><td>跳转通知地址</td><td>return_url</td><td>是</td><td>String</td><td>http://www.pay.com/return_url.php</td><td>页面跳转通知地址</td></tr>
  <tr><td>商品名称</td><td>name</td><td>是</td><td>String</td><td>VIP会员</td><td>如超过127个字节会自动截取</td></tr>
  <tr><td>商品金额</td><td>money</td><td>是</td><td>String</td><td>1.00</td><td>单位：元，最大2位小数</td></tr>
  <tr><td>业务扩展参数</td><td>param</td><td>否</td><td>String</td><td>没有请留空</td><td>支付后原样返回</td></tr>
  <tr><td>签名字符串</td><td>sign</td><td>是</td><td>String</td><td>202cb962ac59075b964b07152d234b70</td><td>签名算法<a href="#pay3" style="color:#4585d2">点此查看</a></td></tr>
  <tr><td>签名类型</td><td>sign_type</td><td>是</td><td>String</td><td>MD5</td><td>默认为MD5</td></tr>
  </tbody>
</table>
<p><font color="#993939">支付方式（type）不传会跳转到收银台支付</font></p>
		</div>
    <div id="pay1" class="api_block">
			<h3>
				API接口支付
			</h3>
<p><font color="#777">此接口可用于服务器后端发起支付请求，会返回支付二维码链接或支付跳转url。</font></p>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>mapi.php</font></p>
<p>请求方式：<font color="#29389f">POST</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>支付方式</td><td>type</td><td>是</td><td>String</td><td>alipay</td><td><a href="#pay4" style="color:#4585d2">支付方式列表</a></td></tr>
  <tr><td>商户订单号</td><td>out_trade_no</td><td>是</td><td>String</td><td>20160806151343349</td><td></td></tr>
  <tr><td>异步通知地址</td><td>notify_url</td><td>是</td><td>String</td><td>http://www.pay.com/notify_url.php</td><td>服务器异步通知地址</td></tr>
  <tr><td>跳转通知地址</td><td>return_url</td><td>否</td><td>String</td><td>http://www.pay.com/return_url.php</td><td>页面跳转通知地址</td></tr>
  <tr><td>商品名称</td><td>name</td><td>是</td><td>String</td><td>VIP会员</td><td>如超过127个字节会自动截取</td></tr>
  <tr><td>商品金额</td><td>money</td><td>是</td><td>String</td><td>1.00</td><td>单位：元，最大2位小数</td></tr>
  <tr><td>用户IP地址</td><td>clientip</td><td>是</td><td>String</td><td>192.168.1.100</td><td>用户发起支付的IP地址</td></tr>
  <tr><td>设备类型</td><td>device</td><td>否</td><td>String</td><td>pc</td><td>根据用户浏览器的UA判断，<br/>传入用户所使用的浏览器<br/>或设备类型，默认为pc<br/><a href="#pay5" style="color:#4585d2">设备类型列表</a></td></tr>
  <tr><td>业务扩展参数</td><td>param</td><td>否</td><td>String</td><td>没有请留空</td><td>支付后原样返回</td></tr>
  <tr><td>签名字符串</td><td>sign</td><td>是</td><td>String</td><td>202cb962ac59075b964b07152d234b70</td><td>签名算法<a href="#pay3" style="color:#4585d2">点此查看</a></td></tr>
  <tr><td>签名类型</td><td>sign_type</td><td>是</td><td>String</td><td>MD5</td><td>默认为MD5</td></tr>
  </tbody>
</table>
<p>返回结果（json）：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>返回状态码</td><td>code</td><td>Int</td><td>1</td><td>1为成功，其它值为失败</td></tr>
  <tr><td>返回信息</td><td>msg</td><td>String</td><td></td><td>失败时返回原因</td></tr>
  <tr><td>订单号</td><td>trade_no</td><td>String</td><td>20160806151343349</td><td>支付订单号</td></tr>
  <tr><td>支付跳转url</td><td>payurl</td><td>String</td><td><?php echo $siteurl?>pay/wxpay/202010903/</td><td>如果返回该字段，则直接跳转到该url支付</td></tr>
  <tr><td>二维码链接</td><td>qrcode</td><td>String</td><td>weixin://wxpay/bizpayurl?pr=04IPMKM</td><td>如果返回该字段，则根据该url生成二维码</td></tr>
  <tr><td>小程序跳转url</td><td>urlscheme</td><td>String</td><td>weixin://dl/business/?ticket=xxx</td><td>如果返回该字段，则使用js跳转该url，可发起微信小程序支付</td></tr>
  </tbody>
</table>
<p><font color="#993939">注：payurl、qrcode、urlscheme 三个参数只会返回其中一个</font></p>
		</div>
		<div id="pay2" class="api_block">
			<h3>
				支付结果通知
			</h3>
<p>通知类型：服务器异步通知（notify_url）、页面跳转通知（return_url）</p>
<p>请求方式：GET</p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>易支付订单号</td><td>trade_no</td><td>是</td><td>String</td><td>20160806151343349021</td><td><?php echo $conf['sitename']?>订单号</td></tr>
  <tr><td>商户订单号</td><td>out_trade_no</td><td>是</td><td>String</td><td>20160806151343349</td><td>商户系统内部的订单号</td></tr>
  <tr><td>支付方式</td><td>type</td><td>是</td><td>String</td><td>alipay</td><td><a href="#pay4" style="color:#4585d2">支付方式列表</a></td></tr>
  <tr><td>商品名称</td><td>name</td><td>是</td><td>String</td><td>VIP会员</td><td></td></tr>
  <tr><td>商品金额</td><td>money</td><td>是</td><td>String</td><td>1.00</td><td></td></tr>
  <tr><td>支付状态</td><td>trade_status</td><td>是</td><td>String</td><td>TRADE_SUCCESS</td><td>只有TRADE_SUCCESS是成功</td></tr>
  <tr><td>业务扩展参数</td><td>param</td><td>否</td><td>String</td><td></td><td></td></tr>
  <tr><td>签名字符串</td><td>sign</td><td>是</td><td>String</td><td>202cb962ac59075b964b07152d234b70</td><td>签名算法<a href="#pay3" style="color:#4585d2">点此查看</a></td></tr>
  <tr><td>签名类型</td><td>sign_type</td><td>是</td><td>String</td><td>MD5</td><td>默认为MD5</td></tr>
  </tbody>
</table>
<p><font color="#993939">收到异步通知后，需返回success以表示服务器接收到了订单通知</font></p>
		</div>
		<div id="pay3" class="api_block">
			<h3>
				MD5签名算法
			</h3>

      <p>1、将发送或接收到的所有参数按照参数名ASCII码从小到大排序（a-z），sign、sign_type、和空值不参与签名！</p>
      <p>2、将排序后的参数拼接成URL键值对的格式，例如 <code>a=b&amp;c=d&amp;e=f</code>，参数值不要进行url编码。</p>
      <p>3、再将拼接好的字符串与商户密钥KEY进行MD5加密得出sign签名参数，<code>sign = md5 ( a=b&amp;c=d&amp;e=f + KEY )</code> （注意：+ 为各语言的拼接符，不是字符！），md5结果为小写。</p>
      <p>4、具体签名与发起支付的示例代码可下载SDK查看。</p>
		</div>
    <div id="pay4" class="api_block">
			<h3>
				支付方式列表
			</h3>
<table class="table table-bordered table-hover">
  <thead><tr><th>调用值</th><th>描述</th></tr></thead>
  <tbody>
<?php
foreach($paytype as $row){
  echo '<tr><td>'.$row['name'].'</td><td>'.$row['showname'].'</td></tr>';
}
?>
  </tbody>
</table>
		</div>
    <div id="pay5" class="api_block">
			<h3>
				设备类型列表
			</h3>
<table class="table table-bordered table-hover">
  <thead><tr><th>调用值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>pc</td><td>电脑浏览器</td></tr>
  <tr><td>mobile</td><td>手机浏览器</td></tr>
  <tr><td>qq</td><td>手机QQ内浏览器</td></tr>
  <tr><td>wechat</td><td>微信内浏览器</td></tr>
  <tr><td>alipay</td><td>支付宝客户端</td></tr>
  <tr><td>jump</td><td>仅返回支付跳转url</td></tr>
  </tbody>
</table>
		</div>
		<hr/>
		<div id="api1" class="api_block">
			<h3>
				[API]查询商户信息
			</h3>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>api.php?act=query&amp;pid={商户ID}&amp;key={商户密钥}</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>操作类型</td><td>act</td><td>是</td><td>String</td><td>query</td><td>此API固定值</td></tr>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>商户密钥</td><td>key</td><td>是</td><td>String</td><td>89unJUB8HZ54Hj7x4nUj56HN4nUzUJ8i</td><td></td></tr>
  </tbody>
</table>
<p>返回结果：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>返回状态码</td><td>code</td><td>Int</td><td>1</td><td>1为成功，其它值为失败</td></tr>
  <tr><td>商户ID</td><td>pid</td><td>Int</td><td>1001</td><td>商户ID</td></tr>
  <tr><td>商户密钥</td><td>key</td><td>String(32)</td><td>89unJUB8HZ54Hj7x4nUj56HN4nUzUJ8i</td><td>商户密钥</td></tr>
  <tr><td>商户状态</td><td>active</td><td>Int</td><td>1</td><td>1为正常，0为封禁</td></tr>
  <tr><td>商户余额</td><td>money</td><td>String</td><td>0.00</td><td>商户所拥有的余额</td></tr>
  <tr><td>结算方式</td><td>type</td><td>Int</td><td>1</td><td>1:支付宝,2:微信,3:QQ,4:银行卡</td></tr>
  <tr><td>结算账号</td><td>account</td><td>String</td><td>admin@pay.com</td><td>结算的支付宝账号</td></tr>
  <tr><td>结算姓名</td><td>username</td><td>String</td><td>张三</td><td>结算的支付宝姓名</td></tr>
  <tr><td>订单总数</td><td>orders</td><td>Int</td><td>30</td><td>订单总数统计</td></tr>
  <tr><td>今日订单</td><td>order_today</td><td>Int</td><td>15</td><td>今日订单数量</td></tr>
  <tr><td>昨日订单</td><td>order_lastday</td><td>Int</td><td>15</td><td>昨日订单数量</td></tr>
  </tbody>
</table>
		</div>
		<div id="api3" class="api_block">
			<h3>
				[API]查询结算记录
			</h3>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>api.php?act=settle&amp;pid={商户ID}&amp;key={商户密钥}</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>操作类型</td><td>act</td><td>是</td><td>String</td><td>settle</td><td>此API固定值</td></tr>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>商户密钥</td><td>key</td><td>是</td><td>String</td><td>89unJUB8HZ54Hj7x4nUj56HN4nUzUJ8i</td><td></td></tr>
  </tbody>
</table>
<p>返回结果：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>返回状态码</td><td>code</td><td>Int</td><td>1</td><td>1为成功，其它值为失败</td></tr>
  <tr><td>返回信息</td><td>msg</td><td>String</td><td>查询结算记录成功！</td><td></td></tr>
  <tr><td>结算记录</td><td>data</td><td>Array</td><td>结算记录列表</td><td></td></tr>
  </tbody>
</table>
		</div>
		<div id="api4" class="api_block">
			<h3>
				[API]查询单个订单
			</h3>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>api.php?act=order&amp;pid={商户ID}&amp;key={商户密钥}&amp;out_trade_no={商户订单号}</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>操作类型</td><td>act</td><td>是</td><td>String</td><td>order</td><td>此API固定值</td></tr>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>商户密钥</td><td>key</td><td>是</td><td>String</td><td>89unJUB8HZ54Hj7x4nUj56HN4nUzUJ8i</td><td></td></tr>
  <tr><td>系统订单号</td><td>trade_no</td><td>选择</td><td>String</td><td>20160806151343312</td><td></td></tr>
  <tr><td>商户订单号</td><td>out_trade_no</td><td>选择</td><td>String</td><td>20160806151343349</td><td></td></tr>
  </tbody>
</table>
<p>提示：系统订单号 和 商户订单号 二选一传入即可，如果都传入以系统订单号为准！</p>
<p>返回结果：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>返回状态码</td><td>code</td><td>Int</td><td>1</td><td>1为成功，其它值为失败</td></tr>
  <tr><td>返回信息</td><td>msg</td><td>String</td><td>查询订单号成功！</td><td></td></tr>
  <tr><td>易支付订单号</td><td>trade_no</td><td>String</td><td>2016080622555342651</td><td><?php echo $conf['sitename']?>订单号</td></tr>
  <tr><td>商户订单号</td><td>out_trade_no</td><td>String</td><td>20160806151343349</td><td>商户系统内部的订单号</td></tr>
  <tr><td>第三方订单号</td><td>api_trade_no</td><td>String</td><td>20160806151343349</td><td>支付宝微信等接口方订单号</td></tr>
  <tr><td>支付方式</td><td>type</td><td>String</td><td>alipay</td><td><a href="#pay4" style="color:#4585d2">支付方式列表</a></td></tr>
  <tr><td>商户ID</td><td>pid</td><td>Int</td><td>1001</td><td>发起支付的商户ID</td></tr>
  <tr><td>创建订单时间</td><td>addtime</td><td>String</td><td>2016-08-06 22:55:52</td><td></td></tr>
  <tr><td>完成交易时间</td><td>endtime</td><td>String</td><td>2016-08-06 22:55:52</td><td></td></tr>
  <tr><td>商品名称</td><td>name</td><td>String</td><td>VIP会员</td><td></td></tr>
  <tr><td>商品金额</td><td>money</td><td>String</td><td>1.00</td><td></td></tr>
  <tr><td>支付状态</td><td>status</td><td>Int</td><td>0</td><td>1为支付成功，0为未支付</td></tr>
  <tr><td>业务扩展参数</td><td>param</td><td>String</td><td></td><td>默认留空</td></tr>
  <tr><td>支付者账号</td><td>buyer</td><td>String</td><td></td><td>默认留空</td></tr>
  </tbody>
</table>
		</div>
		<div id="api5" class="api_block">
			<h3>
				[API]批量查询订单
			</h3>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>api.php?act=orders&amp;pid={商户ID}&amp;key={商户密钥}</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>操作类型</td><td>act</td><td>是</td><td>String</td><td>orders</td><td>此API固定值</td></tr>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>商户密钥</td><td>key</td><td>是</td><td>String</td><td>89unJUB8HZ54Hj7x4nUj56HN4nUzUJ8i</td><td></td></tr>
  <tr><td>查询订单数量</td><td>limit</td><td>否</td><td>Int</td><td>20</td><td>返回的订单数量，最大50</td></tr>
  <tr><td>页码</td><td>page</td><td>否</td><td>Int</td><td>1</td><td>当前查询的页码</td></tr>
  </tbody>
</table>
<p>返回结果：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>返回状态码</td><td>code</td><td>Int</td><td>1</td><td>1为成功，其它值为失败</td></tr>
  <tr><td>返回信息</td><td>msg</td><td>String</td><td>查询结算记录成功！</td><td></td></tr>
  <tr><td>订单列表</td><td>data</td><td>Array</td><td></td><td>订单列表</td></tr>
  </tbody>
</table>
		</div>
    <div id="api6" class="api_block">
			<h3>
				[API]提交订单退款
			</h3>
<p><font color="red">需要先在商户后台开启订单退款API接口开关，才能调用该接口发起订单退款</font></p>
<p>URL地址：<font color="#29389f"><?php echo $siteurl?>api.php?act=refund</font></p>
<p>请求方式：<font color="#29389f">POST</font></p>
<p>请求参数说明：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>必填</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>商户ID</td><td>pid</td><td>是</td><td>Int</td><td>1001</td><td></td></tr>
  <tr><td>商户密钥</td><td>key</td><td>是</td><td>String</td><td>89unJUB8HZ54Hj7x4nUj56HN4nUzUJ8i</td><td></td></tr>
  <tr><td>易支付订单号</td><td>trade_no</td><td>特殊可选</td><td>String</td><td>20160806151343349021</td><td>易支付订单号</td></tr>
  <tr><td>商户订单号</td><td>out_trade_no</td><td>特殊可选</td><td>String</td><td>20160806151343349</td><td>订单支付时传入的商户订单号，商家自定义且保证商家系统中唯一</td></tr>
  <tr><td>退款金额</td><td>money</td><td>是</td><td>String</td><td>1.50</td><td>少数通道需要与原订单金额一致</td></tr>
  </tbody>
</table>
<p><font color="#993939">注：trade_no、out_trade_no 不能同时为空，如果都传了以trade_no为准</font></p>
<p>返回结果：</p>
<table class="table table-bordered table-hover">
  <thead><tr><th>字段名</th><th>变量名</th><th>类型</th><th>示例值</th><th>描述</th></tr></thead>
  <tbody>
  <tr><td>返回状态码</td><td>code</td><td>Int</td><td>0</td><td>0为成功，其它值为失败</td></tr>
  <tr><td>返回信息</td><td>msg</td><td>String</td><td>退款成功</td><td></td></tr>
  </tbody>
</table>
		</div>
		
		<div id="sdk0" class="api_block">
			<h3>
				SDK下载
			</h3>
<blockquote>
<a href="./assets/files/SDK.zip?_v=1.3" style="color:blue">SDK.zip</a><br/>
SDK版本：V1.3
</blockquote>
		</div>
