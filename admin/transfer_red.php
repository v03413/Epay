<?php
include("../includes/common.php");
$title='创建红包';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
$app = isset($_GET['app'])?$_GET['app']:'alipay';

if(isset($_POST['submit'])){
	if(!checkRefererHost())exit();
	$out_biz_no = trim($_POST['out_biz_no']);
	if(!isset($_POST['paypwd']) || $_POST['paypwd']!==$conf['admin_paypwd'])showmsg('支付密码错误',3);
	$money = trim($_POST['money']);
	$desc = htmlspecialchars(trim($_POST['desc']));
	if(empty($out_biz_no) || empty($money))showmsg('必填项不能为空',3);
	if(strlen($out_biz_no)!=19 || !is_numeric($out_biz_no))showmsg('交易号输入不规范',3);
	if($desc && mb_strlen($desc)>32)showmsg('转账备注最多32个字',3);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)showmsg('转账金额输入不规范',3);

	$channelid = isset($_POST['channel'])?$_POST['channel']:null;

	$result = \lib\Transfer::red_add(0, $app, $out_biz_no, $money, $desc, $channelid);

	if($result['code']==0){
		$result='红包创建成功！请在付款记录列表查看红包二维码。';
		showmsg($result,1,'./transfer.php');
	}else{
		$result='红包创建失败：'.$result['msg'];
		showmsg($result,4);
	}
}

$out_biz_no = date("YmdHis").rand(11111,99999);

$channel_select = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%".$app."%')");

if($app=='alipay'){
	$default_channel = $conf['transfer_alipay'];
}elseif($app=='wxpay'){
	$default_channel = $conf['transfer_wxpay'];
}
?>

	  <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">创建红包</h3></div>
        <div class="panel-body">
		<ul class="nav nav-tabs">
			<li class="<?php echo $app=='alipay'?'active':null;?>"><a href="?app=alipay">支付宝</a></li><li class="<?php echo $app=='wxpay'?'active':null;?>"><a href="?app=wxpay">微信</a></li>
		</ul>
		<div class="tab-pane active" id="alipay">
          <form action="?app=<?php echo $app?>" method="POST" role="form">
			<input type="hidden" name="type" value="<?php echo $app?>"/>
		    <div class="form-group">
				<div class="input-group"><div class="input-group-addon">通道选择</div>
				<select name="channel" class="form-control" default="<?php echo $default_channel?>">
					<?php foreach($channel_select as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].''.($channel['id']==$default_channel?'（默认）':'').'</option>';} ?>
				</select>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">红包金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">红包备注</div>
				<input type="text" name="desc" value="" class="form-control" placeholder="可留空，默认为：<?php echo $app=='alipay'?$conf['transfer_name']:$conf['transfer_desc']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="确认创建" class="btn btn-primary form-control"/></p>
			<p><a href="javascript:balanceQuery()" class="btn btn-block btn-default">查询账户余额</a></p>
          </form>
        </div>
		</div>
		<div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 红包和转账付款的区别是，红包不需要填写收款人账号。红包创建成功之后会生成一个二维码，收款人使用微信/支付宝扫码之后即可自动领取到账。领取时也是调用的支付宝/微信商家转账接口，并非现金红包接口。<br/><br/>
		  <a href="./set.php?mod=account">修改支付密码</a>
        </div>
      </div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
function balanceQuery(){
	var type = $("input[name=type]").val();
	var channel = $("select[name=channel]").val();
	if(channel == ''){
		layer.alert('请先选择通道');return;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_transfer.php?act=balance_query',
		dataType : 'json',
		data : {type: type, channel: channel},
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				if(data.msg){
					layer.alert(data.msg);
				}else{
					layer.alert('账户可用余额：'+data.amount+'元');
				}
			}else{
				if(data.msg.indexOf('插件方法不存在')>-1) data.msg = '该通道不支持查询账户余额';
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
</script>