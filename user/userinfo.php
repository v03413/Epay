<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='个人资料';
include './head.php';
?>
<?php
$mod=isset($_GET['mod'])?$_GET['mod']:'api';

if(strlen($userrow['phone'])==11){
	$userrow['phone']=substr($userrow['phone'],0,3).'****'.substr($userrow['phone'],7,10);
}

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
						<button type="button" class="btn btn-white" onclick="if(confirm('关闭后无法再次查询商户私钥，是否关闭？'))$('#myModal').modal('hide');">关闭</button>
					</div>
				</div>
			</div>
		</div>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">
<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">个人资料</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
<div class="tab-container ng-isolate-scope">
<ul class="nav nav-tabs">
	<li style="width: 25%;" align="center" class="<?php echo $mod=='api'?'active':null?>">
		<a href="userinfo.php?mod=api">API信息</a>
	</li>
	<li style="width: 25%;" align="center" class="<?php echo $mod=='info'?'active':null?>">
		<a href="editinfo.php">修改资料</a>
	</li>
	<li style="width: 25%;" align="center" class="<?php echo $mod=='account'?'active':null?>">
		<a href="userinfo.php?mod=account">修改密码</a>
	</li>
	<?php if($conf['cert_open']>0){?>
	<li style="width: 25%;" align="center">
		<a href="certificate.php">实名认证</a>
	</li>
	<?php }?>
</ul>
	<div class="tab-content">
		<div class="tab-pane ng-scope active">
<?php if($mod=='api'){?>
			<form class="form-horizontal devform">
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
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>V1接口（MD5签名方式）：</h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label">商户MD5密钥</label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" value="<?php echo $userrow['key']?>" readonly><div class="input-group-addon"><a href="javascript:;" class="copy-btn" data-clipboard-text="<?php echo $userrow['key']?>" title="点击复制"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><a href="/doc_old.html" class="btn btn-sm btn-info" target="_blank">查看V1开发文档</a>&nbsp;&nbsp;<a href="javascript:resetKey()" class="btn btn-sm btn-danger">重置商户MD5密钥</a>
				 </div>
				</div>
				<div class="line line-dashed b-b line-lg pull-in"></div>
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
						<div class="input-group"><textarea class="form-control" name="merchant_public_key" rows="3" readonly><?php echo $userrow['publickey']?></textarea><div class="input-group-addon"><a href="javascript:;" class="copy-btn" title="点击复制" onclick="alert('请仔细分清各种密钥区别，只需要复制平台公钥和商户私钥即可！如商户私钥遗失请重新生成。')"><i class="fa fa-copy"></i></a></div></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><a href="/doc.html" class="btn btn-sm btn-info" target="_blank">查看V2开发文档</a>&nbsp;&nbsp;<?php if($userrow['publickey']){?><a href="javascript:createRsaPair()" class="btn btn-sm btn-danger">重置商户RSA密钥对</a><?php }else{?><a href="javascript:createRsaPair()" class="btn btn-sm btn-success">生成商户RSA密钥对</a><?php }?>
				 </div>
				</div>
				<div class="line line-dashed b-b line-lg pull-in"></div>
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>签名方式开关设置：</h4></div></div>
				<div class="form-group">
					<label class="col-sm-2 control-label">签名方式开关</label>
					<div class="col-sm-9">
						<select class="form-control" name="keytype" default="<?php echo $userrow['keytype']?>"><option value="0">开启MD5+RSA签名（兼容模式）</option><option value="1">仅开启RSA签名（安全模式）</option></select>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="editKeyType" value="确定修改" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
			</form>
<?php }elseif($mod=='account'){?>
			<form class="form-horizontal devform">
				<div class="form-group"><div class="col-sm-offset-2 col-sm-4"><h4>修改登录密码：</h4></div></div>
				<?php if(!empty($userrow['pwd'])){?>
				<div class="form-group">
					<label class="col-sm-2 control-label">旧密码</label>
					<div class="col-sm-9">
						<input class="form-control" type="password" name="oldpwd" value="">
					</div>
				</div>
				<?php }?>
				<div class="form-group">
					<label class="col-sm-2 control-label">新密码</label>
					<div class="col-sm-9">
						<input class="form-control" type="password" name="newpwd" value="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">重复密码</label>
					<div class="col-sm-9">
						<input class="form-control" type="password" name="newpwd2" value="">
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="changePwd" value="修改密码" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
			</form>
<?php }?>
		</div>
	</div>
</div>
</div>
    </div>
  </div>
<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>clipboard.js/1.7.1/clipboard.min.js"></script>
<script>
$(document).ready(function(){
	var items = $("select[default]");
	for (i = 0; i < items.length; i++) {
		$(items[i]).val($(items[i]).attr("default")||0);
	}
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
	$("#changePwd").click(function(){
		var oldpwd=$("input[name='oldpwd']").val();
		var newpwd=$("input[name='newpwd']").val();
		var newpwd2=$("input[name='newpwd2']").val();
		if(oldpwd==''){layer.alert('旧密码不能为空');return false;}
		if(newpwd==''||newpwd2==''){layer.alert('新密码不能为空');return false;}
		if(newpwd!=newpwd2){layer.alert('两次输入密码不一致！');return false;}
		if(oldpwd==newpwd){layer.alert('旧密码和新密码相同！');return false;}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_pwd",
			data : {oldpwd:oldpwd,newpwd:newpwd,newpwd2:newpwd2},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 1){
					layer.alert(data.msg, {icon: 1}, function(){window.location.reload()});
				}else{
					layer.alert(data.msg);
				}
			}
		});
	});
	$("#editKeyType").click(function(){
		var keytype=$("select[name='keytype']").val();
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=edit_keytype",
			data : {keytype:keytype},
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
	var confirmobj = layer.confirm('是否确认重置对接密钥？重置后需要重新登录', {
	  btn: ['确定','取消']
	}, function(){
		$.ajax({
			type : 'POST',
			url : 'ajax2.php?act=resetKey',
			data : 'submit=do',
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
	var confirmobj = layer.confirm('是否确定生成商户RSA密钥对？', {
	  btn: ['确定','取消']
	}, function(){
		$.ajax({
			type : 'POST',
			url : 'ajax2.php?act=createRsaPair',
			data : 'submit=do',
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
</script>