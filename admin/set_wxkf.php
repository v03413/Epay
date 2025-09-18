<?php
/**
 * H5跳转微信客服支付设置
**/
include("../includes/common.php");
$title='H5跳转微信客服支付设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$callback_url = $siteurl.'wework.php';

$errmsg = $CACHE->read('wxkferrmsg');
if($errmsg){
	$arr = unserialize($errmsg);
	$errmsg = $arr['time'].' - '.$arr['errmsg'];
}

$account_list = $DB->getAll("SELECT A.* FROM pre_wxkfaccount A LEFT JOIN pre_wework B ON A.wid=B.id WHERE B.status=1");
$account_select = '<option value="0">多客服账号轮询</option>';
foreach($account_list as $row){
	$account_select .= '<option value="'.$row['id'].'">'.$row['openkfid'].' - '.$row['name'].'</option>';
}
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">H5跳转微信客服支付设置</h3></div>
<div class="panel-body">
<?php if($errmsg){?><div class="alert alert-warning">回调接口上一次报错信息：<?php echo $errmsg;?></div><?php }?>
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-3 control-label">回调URL</label>
	  <div class="col-sm-9"><input type="text" value="<?php echo $callback_url; ?>" class="form-control" readonly/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">Token</label>
	  <div class="col-sm-9"><input type="text" name="wework_token" value="<?php echo $conf['wework_token']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">EncodingAESKey</label>
	  <div class="col-sm-9"><input type="text" name="wework_aeskey" value="<?php echo $conf['wework_aeskey']; ?>" class="form-control"/></div>
	</div><hr/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">是否开启H5跳转微信客服支付</label>
	  <div class="col-sm-9"><select class="form-control" name="wework_payopen" default="<?php echo $conf['wework_payopen']?>"><option value="0">关闭</option><option value="1">开启</option></select><font color="green">开启前需确保以上配置正确无误，否则会导致手机浏览器无法微信支付</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">微信客服支付消息模式</label>
	  <div class="col-sm-9"><select class="form-control" name="wework_paymsgmode" default="<?php echo $conf['wework_paymsgmode']?>"><option value="0">发送确认消息，用户回复后发送支付链接（默认）</option><option value="1">直接发送支付链接（用户将无法支付第二单）</option></select><font color="green">让用户回复消息，可防止因微信限制发消息导致用户无法在同一个客服二次支付</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">客服账号ID选择</label>
	  <div class="col-sm-9"><select name="wework_paykfid" id="wework_paykfid" class="form-control" default="<?php echo $conf['wework_paykfid']; ?>"><?php echo $account_select?></option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">人工客服链接</label>
	  <div class="col-sm-9"><input type="text" name="wework_contact" value="<?php echo $conf['wework_contact']; ?>" class="form-control" placeholder="选填，填写后在支付消息后面会追加人工客服链接"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">支付消息尾部附加内容</label>
	  <div class="col-sm-9"><input type="text" name="wework_remark" value="<?php echo $conf['wework_remark']; ?>" class="form-control" placeholder="选填，填写后在支付消息后面会追加自定义内容"/><font color="green">支持变量值：[qq]当前商户的联系QQ</font></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="保存" class="btn btn-primary form-control"/><br/><br/><a href="pay_wework.php" class="btn btn-success btn-block">企业微信账号列表</a><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
此功能可以通过<a href="https://kf.weixin.qq.com/" target="_blank" rel="noreferrer">微信客服</a>，从手机网站跳转到微信内进行支付。<br/>注：1、企业微信需要完成企业认证，否则接待客户数量会有限制。<br/>2、只能使用独立版<a href="https://kf.weixin.qq.com/" target="_blank" rel="noreferrer">微信客服</a>获取token并配置回调，不能开启企业微信内的微信客服应用！
</div>
</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
function saveSetting(obj){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax.php?act=set',
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('设置保存成功！', {
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
	return false;
}
</script>