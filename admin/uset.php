<?php
/**
 * 商户信息
**/
include("../includes/common.php");
$title='商户信息';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
<?php
$usergroup = [];
$select = '';
$rs = $DB->getAll("SELECT * FROM pre_group");
foreach($rs as $row){
	$usergroup[$row['gid']] = $row['name'];
	$select.='<option value="'.$row['gid'].'">'.$row['name'].'</option>';
}
unset($rs);
$settle_select = '';
if($conf['settle_alipay'])$settle_select.='<option value="1">支付宝</option>';
if($conf['settle_wxpay'])$settle_select.='<option value="2">微信</option>';
if($conf['settle_qqpay'])$settle_select.='<option value="3">QQ钱包</option>';
if($conf['settle_bank'])$settle_select.='<option value="4">银行卡</option>';

$my=isset($_GET['my'])?$_GET['my']:null;

if($my=='add')
{
?>
<div class="col-sm-12 col-md-10 col-lg-8 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">添加商户</h3></div>
<div class="panel-body">
<form method="POST" onsubmit="return addUser(this)">
<h4><font color="blue">基本信息</font></h4>
<div class="form-group">
<label>手机号(登录账号):</label><br>
<input type="text" class="form-control" name="phone" value="" placeholder="和邮箱不能同时为空">
</div>
<div class="form-group">
<label>邮箱(登录账号):</label><br>
<input type="text" class="form-control" name="email" value="" placeholder="和手机号不能同时为空">
</div>
<div class="form-group">
<label>登录密码:</label><br>
<input type="text" class="form-control" name="pwd" value="" placeholder="留空则只能使用密钥登录">
</div>
<div class="form-group">
<label>用户组:</label><br>
<select class="form-control" name="gid"><?php echo $select?></select>
</div>
<div class="form-group">
<label>ＱＱ:</label><br>
<input type="text" class="form-control" name="qq" value="" placeholder="可留空">
</div>
<div class="form-group">
<label>网站域名:</label><br>
<input type="text" class="form-control" name="url" value="" placeholder="可留空">
</div>
<h4><font color="blue">结算信息</font></h4>
<div class="form-group">
<label>结算方式:</label><br><select class="form-control" name="settle_id"><?php echo $settle_select?></select>
</div>
<div class="form-group">
<label>结算账号:</label><br>
<input type="text" class="form-control" name="account" value="" placeholder="不使用平台代收可留空">
</div>
<div class="form-group">
<label>结算账号姓名:</label><br>
<input type="text" class="form-control" name="username" value="" placeholder="不使用平台代收可留空">
</div>
<h4><font color="blue">功能开关</font></h4>
<div class="form-group">
<label>手续费扣除模式:</label><br><select class="form-control" name="mode"><option value="0">余额扣费</option><option value="1">订单加费</option></select>
</div>
<div class="row">
	<div class="col-md-4 col-sm-12">
		<div class="form-group">
		<label>商户状态:</label><br><select class="form-control" name="status"><option value="1">1_正常</option><option value="0">0_封禁</option><option value="2">2_未审核</option></select>
		</div>
	</div>
	<div class="col-md-4 col-sm-12">
		<div class="form-group">
		<label>支付权限:</label><br><select class="form-control" name="pay"><option value="1">1_开启</option><option value="0">0_关闭</option></select>
		</div>
	</div>
	<div class="col-md-4 col-sm-12">
		<div class="form-group">
		<label>结算权限:</label><br><select class="form-control" name="settle"><option value="1">1_开启</option><option value="0">0_关闭</option></select>
		</div>
	</div>
</div>
<input type="submit" class="btn btn-primary btn-block" value="确定添加"></form>
<br/><a href="./ulist.php">>>返回商户列表</a>
</div></div>
<?php }
elseif($my=='edit')
{
$uid=intval($_GET['uid']);
$row=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
if(!$row)showmsg('该商户不存在',4);
$group=\lib\Channel::getGroup($row['gid']);
?>
<div class="col-md-12 col-lg-10 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">修改商户信息 UID:<?php echo $uid?></h3></div>
<div class="panel-body">
<ul class="nav nav-tabs">
<li align="center" class="active"><a href="#">基本信息</a></li>
<li align="center"><a href="./uset.php?my=api&uid=<?php echo $uid?>">密钥信息</a></li>
<?php if($group['settings']){?><li align="center"><a href="./uset.php?my=edit2&uid=<?php echo $uid?>">自定义接口信息</a></li><?php }?>
<?php if($group['subchannel_type']){?><li align="center"><a href="./uset.php?my=subchannel&uid=<?php echo $uid?>">自定义子通道</a></li><?php }?>
</ul>
<form onsubmit="return editUser(this, <?php echo $uid?>)" method="POST">
<div class="row">
<div class="col-sm-12 col-md-6">
<h4><font color="blue">基本信息</font></h4>
<div class="form-group">
<label>手机号(登录账号):</label><br>
<input type="text" class="form-control" name="phone" value="<?php echo $row['phone']?>" placeholder="和邮箱不能同时为空">
</div>
<div class="form-group">
<label>邮箱(登录账号):</label><br>
<input type="text" class="form-control" name="email" value="<?php echo $row['email']?>" placeholder="和手机号不能同时为空">
</div>
<div class="form-group">
<label>商户余额:</label><br>
<input type="text" class="form-control" name="money" value="<?php echo $row['money']?>" required>
</div>
<div class="form-group">
<label>用户组:</label><br>
<select class="form-control" name="gid" default="<?php echo $row['gid']?>"><?php echo $select?></select>
</div>
<div class="form-group">
<label>ＱＱ:</label><br>
<input type="text" class="form-control" name="qq" value="<?php echo $row['qq']?>" placeholder="可留空">
</div>
<div class="form-group">
<label>网站域名:</label><br>
<input type="text" class="form-control" name="url" value="<?php echo $row['url']?>" placeholder="可留空">
</div>
<div class="form-group">
<label>商品名称自定义:</label><br>
<input type="text" class="form-control" name="ordername" value="<?php echo $row['ordername']?>" placeholder="默认以系统设置里面的为准">
<font color="green">支持变量值：[name]原商品名称，[order]支付订单号，[outorder]商户订单号，[time]时间戳，[qq]当前商户的联系QQ</font>
</div>
<div class="form-group">
<label>商户保证金余额:</label><br>
<input type="text" class="form-control" name="deposit" value="<?php echo $row['deposit']?>" placeholder="开启商户保证金功能后使用">
</div>
<div class="form-group">
<label>预留余额:</label><br>
<input type="text" class="form-control" name="remain_money" value="<?php echo $row['remain_money']?>" placeholder="可设置预留部分商户余额不参与每日自动结算，数字后加%即为百分比">
</div>
<h4><font color="blue">结算信息</font></h4>
<div class="form-group">
<label>结算方式:</label><br><select class="form-control" name="settle_id" default="<?php echo $row['settle_id']?>"><?php echo $settle_select?></select>
</div>
<div class="form-group">
<label>结算账号:</label><br>
<input type="text" class="form-control" name="account" value="<?php echo $row['account']?>" placeholder="不使用平台代收可留空">
</div>
<div class="form-group">
<label>结算账号姓名:</label><br>
<input type="text" class="form-control" name="username" value="<?php echo $row['username']?>" placeholder="不使用平台代收可留空">
</div>
</div>
<div class="col-sm-12 col-md-6">
<h4><font color="blue">实名信息</font></h4>
<div class="form-group">
<label>是否实名认证:</label><br><select class="form-control" name="cert" default="<?php echo $row['cert']?>"><option value="0">0_未实名</option><option value="1">1_已实名</option></select>
</div>
<div class="form-group">
<label>认证类型:</label><br><select class="form-control" name="certtype" default="<?php echo $row['certtype']?>"><option value="0">个人实名认证</option><option value="1">企业实名认证</option></select>
</div>
<div class="form-group">
<label>认证方式:</label><br><select class="form-control" name="certmethod" default="<?php echo $row['certmethod']?>"><option value="0">支付宝快捷认证</option><option value="1">微信快捷认证</option><option value="2">手机号三要素认证</option><option value="3">人工审核认证</option></select>
</div>
<div class="form-group">
<label>真实姓名:</label><br>
<input type="text" class="form-control" name="certname" value="<?php echo $row['certname']?>">
</div>
<div class="form-group">
<label>身份证号:</label><br>
<input type="text" class="form-control" name="certno" value="<?php echo $row['certno']?>" maxlength="18">
</div>
<div class="form-group">
<label>公司名称:</label><br>
<input type="text" class="form-control" name="certcorpname" value="<?php echo $row['certcorpname']?>">
</div>
<div class="form-group">
<label>营业执照号码:</label><br>
<input type="text" class="form-control" name="certcorpno" value="<?php echo $row['certcorpno']?>" maxlength="30">
</div>
<h4><font color="blue">功能开关</font></h4>
<div class="form-group">
<label>手续费扣除模式:</label><br><select class="form-control" name="mode" default="<?php echo $row['mode']?>"><option value="0">余额扣费</option><option value="1">订单加费</option></select>
</div>
<div class="form-group">
<label>聚合收款码:</label><br><select class="form-control" name="open_code" default="<?php echo $row['open_code']?>"><option value="0">缺省（与系统设置一致）</option><option value="1">开启</option><option value="2">关闭</option></select>
</div>
<div class="row">
	<div class="col-md-4 col-sm-12">
		<div class="form-group">
		<label>商户状态:</label><br><select class="form-control" name="status" default="<?php echo $row['status']?>"><option value="1">1_正常</option><option value="0">0_封禁</option><option value="2">2_未审核</option></select>
		</div>
	</div>
	<div class="col-md-4 col-sm-12">
		<div class="form-group">
		<label>支付权限:</label><br><select class="form-control" name="pay" default="<?php echo $row['pay']?>"><option value="1">1_开启</option><option value="0">0_关闭</option></select>
		</div>
	</div>
	<div class="col-md-4 col-sm-12">
		<div class="form-group">
		<label>结算权限:</label><br><select class="form-control" name="settle" default="<?php echo $row['settle']?>"><option value="1">1_开启</option><option value="0">0_关闭</option></select>
		</div>
	</div>
</div>
<div class="form-group">
<label>邀请方商户ID:</label><br>
<input type="text" class="form-control" name="upid" value="<?php echo $row['upid'] == 0 ? '' : $row['upid']?>" placeholder="用于邀请返现">
</div>
<h4><font color="blue">密码修改</font></h4>
<div class="form-group">
<label>重置登录密码:</label><br>
<input type="text" class="form-control" name="pwd" value="" placeholder="不重置密码请留空">
</div>
</div>
</div>
<input type="submit" class="btn btn-primary btn-block" value="确定修改"></form>
<br/><a href="./ulist.php">>>返回商户列表</a>
</div></div>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
</script>
<?php
}
elseif($my=='api')
{
$uid=intval($_GET['uid']);
$row=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
if(!$row)showmsg('该商户不存在',4);
$group=\lib\Channel::getGroup($row['gid']);
if(!$conf['apiurl'])$conf['apiurl'] = $siteurl;
?>
		<div class="modal inmodal fade" id="myModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">关闭</span>
						</button>
						<h4 class="modal-title">商户私钥查看窗口</h4>
					</div>
					<div class="modal-body">
						<div class="form-group"><font color="red"><i class="fa fa-info-circle"></i> 请及时复制保存商户私钥！当前窗口关闭后，无法再次查询商户私钥，本站也不会保存。如遗失商户私钥，可重新生成进行替换。</font></div>
						<div class="form-group">
							<label>商户私钥</label>
							<textarea class="form-control" name="merchant_private_key" rows="5" readonly></textarea>
							<center><a href="javascript:;" class="btn btn-default" data-clipboard-text="" title="点击复制" id="merchant_private_key_copy"><i class="fa fa-copy"></i> 复制</a></center>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
					</div>
				</div>
			</div>
		</div>
<div class="col-md-12 col-lg-10 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">商户密钥信息 UID:<?php echo $uid?></h3></div>
<div class="panel-body">
<ul class="nav nav-tabs">
<li align="center"><a href="./uset.php?my=edit&uid=<?php echo $uid?>">基本信息</a></li>
<li align="center" class="active"><a href="#">密钥信息</a></li>
<?php if($group['settings']){?><li align="center"><a href="./uset.php?my=edit2&uid=<?php echo $uid?>">自定义接口信息</a></li><?php }?>
<?php if($group['subchannel_type']){?><li align="center"><a href="./uset.php?my=subchannel&uid=<?php echo $uid?>">自定义子通道</a></li><?php }?>
</ul>
<form class="form-horizontal">
	<input type="hidden" name="uid" value="<?php echo $uid?>"/>
	<div class="form-group">
		<label class="col-sm-2 control-label">接口地址</label>
		<div class="col-sm-9">
			<div class="input-group"><input class="form-control" type="text" value="<?php echo $conf['apiurl']?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $conf['apiurl']?>" title="点击复制"><i class="fa fa-copy"></i></a></div></div>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">商户ID</label>
		<div class="col-sm-9">
			<div class="input-group"><input class="form-control" type="text" value="<?php echo $uid?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $uid?>" title="点击复制"><i class="fa fa-copy"></i></a></div></div>
		</div>
	</div>
	<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>V1接口（MD5签名方式）：</h4></div></div>
	<div class="form-group">
		<label class="col-sm-2 control-label">商户MD5密钥</label>
		<div class="col-sm-9">
			<div class="input-group"><input class="form-control" type="text" value="<?php echo $row['key']?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $row['key']?>" title="点击复制"><i class="fa fa-copy"></i></a></div></div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-4"><a href="/doc_old.html" class="btn btn-sm btn-info" target="_blank">查看V1开发文档</a>&nbsp;&nbsp;<a href="javascript:resetKey()" class="btn btn-sm btn-danger">重置商户MD5密钥</a>
		</div>
	</div>
	<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>V2接口（RSA签名方式）：</h4></div></div>
	<div class="form-group">
		<label class="col-sm-2 control-label">平台公钥</label>
		<div class="col-sm-9">
			<div class="input-group"><textarea class="form-control" name="platform_public_key" rows="3" readonly><?php echo $conf['public_key']?></textarea><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $conf['public_key']?>" title="点击复制"><i class="fa fa-copy"></i></a></div></div>
		</div>
	</div>
	<div class="form-group">
		<label class="col-sm-2 control-label">商户公钥</label>
		<div class="col-sm-9">
			<div class="input-group"><textarea class="form-control" name="merchant_public_key" rows="3" readonly><?php echo $row['publickey']?></textarea><div class="input-group-addon"><a href="javascript:;" class="copy-btn" title="点击复制" onclick="alert('请仔细分清各种密钥区别，只需要复制平台公钥和商户私钥即可！如商户私钥遗失请重新生成。')"><i class="fa fa-copy"></i></a></div></div>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-4"><a href="/doc.html" class="btn btn-sm btn-info" target="_blank">查看V2开发文档</a>&nbsp;&nbsp;<?php if($row['publickey']){?><a href="javascript:createRsaPair()" class="btn btn-sm btn-danger">重置商户RSA密钥对</a><?php }else{?><a href="javascript:createRsaPair()" class="btn btn-sm btn-success">生成商户RSA密钥对</a><?php }?>
		</div>
	</div>
	<div class="line line-dashed b-b line-lg pull-in"></div>
	<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>签名方式开关设置：</h4></div></div>
	<div class="form-group">
		<label class="col-sm-2 control-label">签名方式开关</label>
		<div class="col-sm-9">
			<select class="form-control" name="keytype" default="<?php echo $row['keytype']?>"><option value="0">开启MD5+RSA签名（兼容模式）</option><option value="1">仅开启RSA签名（安全模式）</option></select>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-4"><input type="button" id="editKeyType" value="确定修改" class="btn btn-primary form-control"/><br/>
		</div>
	</div>
</form>
<br/><a href="./ulist.php">>>返回商户列表</a>
</div></div>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
</script>
<?php
}
elseif($my=='edit2')
{
$uid=intval($_GET['uid']);
$row=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
if(!$row)showmsg('该商户不存在',4);
$group=\lib\Channel::getGroup($row['gid']);
$channelinfo = json_decode($row['channelinfo'], true);
?>
<div class="col-md-12 col-lg-10 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">修改商户信息 UID:<?php echo $uid?></h3></div>
<div class="panel-body">
<ul class="nav nav-tabs">
<li align="center"><a href="./uset.php?my=edit&uid=<?php echo $uid?>">基本信息</a></li>
<li align="center"><a href="./uset.php?my=api&uid=<?php echo $uid?>">密钥信息</a></li>
<li align="center" class="active"><a href="#">自定义接口信息</a></li>
<?php if($group['subchannel_type']){?><li align="center"><a href="./uset.php?my=subchannel&uid=<?php echo $uid?>">自定义子通道</a></li><?php }?>
</ul>
<form onsubmit="return editUserChannelInfo(this, <?php echo $uid?>)" method="POST">
<?php
foreach(explode(',',$group['settings']) as $row){
	$arr = explode(':', $row);
	echo '<div class="form-group">
<label>'.$arr[1].':</label><br>
<input type="text" class="form-control" name="setting['.$arr[0].']" value="'.$channelinfo[$arr[0]].'" required>
</div>';
}
?>
<input type="submit" class="btn btn-primary btn-block" value="确定修改"></form>
<br/><a href="./ulist.php">>>返回商户列表</a>
</div></div>
<?php
}
elseif($my=='subchannel')
{
$uid=intval($_GET['uid']);
$row=$DB->getRow("select * from pre_user where uid='$uid' limit 1");
if(!$row)showmsg('该商户不存在',4);
$group=\lib\Channel::getGroup($row['gid']);

$paytype = [];
$paytypes = [];
$type_select = '';
$rs = $DB->getAll("SELECT * FROM pre_type ORDER BY id ASC");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
	$paytypes[$row['id']] = $row['name'];
	if(in_array($row['name'], $group['subchannel_type'])){
		$type_select .= '<option value="'.$row['id'].'">'.$row['showname'].'</option>';
	}
}
unset($rs);
$list = $DB->getAll("SELECT A.*,B.type,B.name channelname FROM pre_subchannel A LEFT JOIN pre_channel B ON A.channel=B.id WHERE A.uid='$uid' ORDER BY A.id ASC");
?>
<style>td{overflow: hidden;text-overflow: ellipsis;white-space: nowrap;max-width:300px;}</style>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">自定义子通道修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="id" id="id"/>
					<input type="hidden" name="uid" value="<?php echo $uid?>"/>
					<div class="form-group">
						<label class="col-sm-2 control-label">支付方式</label>
						<div class="col-sm-10">
							<select name="type" id="type" class="form-control" onchange="changeType()">
								<option value="0">请选择支付方式</option><?php echo $type_select; ?>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">支付通道</label>
						<div class="col-sm-10">
							<select name="channel" id="channel" class="form-control">
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">备注</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="name" id="name" placeholder="仅显示使用，不要与其他备注重复">
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
				<button type="button" class="btn btn-primary" id="store" onclick="save()">保存</button>
			</div>
		</div>
	</div>
</div>
<div class="col-md-12 col-lg-10 center-block" style="float: none;">
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">修改商户信息 UID:<?php echo $uid?></h3></div>
<div class="panel-body">
<ul class="nav nav-tabs">
<li align="center"><a href="./uset.php?my=edit&uid=<?php echo $uid?>">基本信息</a></li>
<li align="center"><a href="./uset.php?my=api&uid=<?php echo $uid?>">密钥信息</a></li>
<?php if($group['settings']){?><li align="center"><a href="./uset.php?my=edit2&uid=<?php echo $uid?>">自定义接口信息</a></li><?php }?>
<li align="center" class="active"><a href="#">自定义子通道</a></li>
</ul>

<div class="panel panel-success">
   <div class="panel-heading"><h3 class="panel-title">当前商户共有 <b><?php echo count($list);?></b> 个子通道&nbsp;
     <span class="pull-right">
	   <a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增</a>
      </span>
   </h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>支付方式</th><th>支付通道</th><th>子通道备注</th><th>自定义支付参数</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
	if(empty($res['info'])) $res['info'] = '点击设置';
echo '<tr><td><b>'.$res['id'].'</b></td><td><img src="/assets/icon/'.$paytypes[$res['type']].'.ico" width="16" onerror="this.style.display=\'none\'"> '.$paytype[$res['type']].'</td><td>'.$res['channel'].'_'.$res['channelname'].'</td><td>'.$res['name'].'</td><td><a href="javascript:editInfo('.$res['id'].')">'.$res['info'].'</a></td><td>'.($res['status']==1?'<a class="btn btn-xs btn-success" onclick="setStatus('.$res['id'].',0)">已开启</a>':'<a class="btn btn-xs btn-warning" onclick="setStatus('.$res['id'].',1)">已关闭</a>').'</td><td><a class="btn btn-xs btn-info" onclick="editframe('.$res['id'].')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['id'].')">删除</a>&nbsp;<a href="./order.php?subchannel='.$res['id'].'" target="_blank" class="btn btn-xs btn-default">订单</a>&nbsp;<a onclick="testpay('.$res['id'].','.$res['channel'].')" class="btn btn-xs btn-default">测试</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	</div>
	
<a href="./ulist.php">>>返回商户列表</a>
</div></div>
<?php } ?>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
$(document).ready(function(){
var clipboard = new Clipboard('.copy-btn');
clipboard.on('success', function (e) {
	layer.msg('复制成功！', {icon: 1});
});
clipboard.on('error', function (e) {
	layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
});
var clipboard = new Clipboard('#merchant_private_key_copy', {
	container: document.getElementById('myModal')
});
clipboard.on('success', function (e) {
	layer.msg('复制成功！', {icon: 1});
});
clipboard.on('error', function (e) {
	layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
});
$("#editKeyType").click(function(){
	var uid = $("input[name='uid']").val();
	var keytype=$("select[name='keytype']").val();
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : "POST",
		url : "ajax_user.php?act=edit_keytype",
		data : {uid:uid, keytype:keytype},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 1){
				layer.alert('修改成功！', {icon:1});
			}else{
				layer.alert(data.msg);
			}
		}
	});
});
});
function resetKey(){
	var uid = $("input[name='uid']").val();
	var confirmobj = layer.confirm('是否确认重置该商户的MD5密钥？', {
	  btn: ['确定','取消']
	}, function(){
		$.ajax({
			type : 'POST',
			url : 'ajax_user.php?act=resetKey',
			data : {uid: uid},
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					layer.alert('重置密钥成功！', {icon:1}, function(){window.location.reload()});
				}else{
					layer.alert(data.msg, {icon:2});
				}
			},
			error:function(data){
				layer.msg('服务器错误');
				return false;
			}
		});
	}, function(){
		layer.close(confirmobj);
	});
}
function createRsaPair(){
	var uid = $("input[name='uid']").val();
	var confirmobj = layer.confirm('是否确定生成该商户的RSA密钥对？', {
	  btn: ['确定','取消']
	}, function(){
		$.ajax({
			type : 'POST',
			url : 'ajax_user.php?act=createRsaPair',
			data : {uid: uid},
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					$("textarea[name='merchant_private_key']").val(data.private_key);
					$("textarea[name='merchant_public_key']").val(data.public_key);
					$("#merchant_private_key_copy").attr('data-clipboard-text', data.private_key);
					layer.alert('商户RSA密钥对生成成功', {icon:1}, function(){
						layer.closeAll();
						$('#myModal').modal('show');
					});
				}else{
					layer.alert(data.msg, {icon:2});
				}
			},
			error:function(data){
				layer.msg('服务器错误');
				return false;
			}
		});
	}, function(){
		layer.close(confirmobj);
	});
}
function addUser(obj){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=addUser',
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('添加商户成功！商户ID：'+data.uid+'<br/>密钥：'+data.key, {
					icon: 1,
					closeBtn: false
				}, function(){
					window.location.href = './ulist.php';
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
function editUser(obj, uid){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=editUser&uid='+uid,
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('修改商户信息成功！', {
					icon: 1,
					closeBtn: false
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
function editUserChannelInfo(obj, uid){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=editUserChannelInfo&uid='+uid,
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('修改商户信息成功！', {
					icon: 1,
					closeBtn: false
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

function changeType(channel){
	channel = channel || null
	var typeid = $("#type").val();
	if(typeid==0)return;
	$("#channel").empty();
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=getChannels&typeid='+typeid,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				$.each(data.data, function (i, res) {
					$("#channel").append('<option value="'+res.id+'">'+res.name+'</option>');
				})
				if(channel!=null)$("#channel").val(channel);
			}else{
				layer.msg(data.msg, {icon:2, time:1500})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	});
}
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增子通道");
	$("#action").val("add");
	$("#id").val('');
	$("#type").val(0);
	$("#channel").empty();
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=getSubChannel&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改子通道");
				$("#action").val("edit");
				$("#id").val(data.data.id);
				$("#name").val(data.data.name);
				$("#type").val(data.data.type);
				changeType(data.data.channel);
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function save(){
	if($("#name").val()==''){
		layer.alert('请确保各项不能为空！');return false;
	}
	if($("#type").val()==0){
		layer.alert('请选择支付方式！');return false;
	}
	if($("#channel").val()==0 || $("#channel").val()==null){
		layer.alert('请选择支付通道！');return false;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=saveSubChannel',
		data : $("#form-store").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
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
			layer.msg('服务器错误');
		}
	});
}
function delItem(id) {
	var confirmobj = layer.confirm('你确实要删除此子通道吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=delSubChannel&id='+id,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
function setStatus(id,status) {
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=setSubChannel&id='+id+'&status='+status,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.msg(data.msg, {icon:2, time:1500});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function editInfo(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=subChannelInfo&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var area = [$(window).width() > 520 ? '520px' : '100%', ';max-height:100%'];
				layer.open({
				  type: 1,
				  area: area,
				  title: '自定义支付参数',
				  skin: 'layui-layer-rim',
				  content: data.data
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function saveInfo(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=saveSubChannelInfo&id='+id,
		data : $("#form-info").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
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
			layer.msg('服务器错误');
			return false;
		}
	});
}
function testpay(subid, id) {
	var ii = layer.open({
		area: ['360px'],
		title: '测试支付',
		content: '<div class="form-group"><div class="input-group"><span class="input-group-addon"><span class="glyphicon glyphicon-shopping-cart"></span></span><input class="form-control" placeholder="订单名称" value="支付测试" name="test_name" type="text"></div></div><div class="form-group"><div class="input-group"><span class="input-group-addon"><span class="glyphicon glyphicon-yen"></span></span><input class="form-control" placeholder="订单金额" value="1" name="test_money" type="text"></div></div>',
		yes: function(){
			var name = $("input[name='test_name']").val();
			var money = $("input[name='test_money']").val();
			$.ajax({
				type : 'POST',
				url : 'ajax_pay.php?act=testpay',
				data : {channel:id, subchannel:subid, name:name, money:money},
				dataType : 'json',
				success : function(data) {
					if(data.code == 0){
						layer.close(ii);
						window.open(data.url);
					}else{
						layer.alert(data.msg, {icon:2});
					}
				},
				error:function(data){
					layer.msg('服务器错误');
					return false;
				}
			});
		}
	});
}
</script>