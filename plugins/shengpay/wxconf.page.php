<?php
if(!defined('IN_CRONLITE'))exit();?>
<style>tbody tr>td:nth-child(1){width:180px}</style>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">微信参数配置查询<span class="pull-right"><a class="btn btn-default btn-xs" href="javascript:window.location.reload()">刷新</a></span></h3></div>
<div class="panel-body">
<p><b>已关联的微信AppID：</b></p>
<p><?php foreach($data['appid_list'] as $row){ echo $row['sub_appid'].'<br/>';}?></p>
<p><b>JSAPI支付授权目录：</b></p>
<p><?php foreach($data['payurl_list'] as $row){ echo $row.'<br/>';}?></p>
</div>
</div>
<div class="panel panel-success">
<div class="panel-heading"><h3 class="panel-title">微信AppID绑定配置</span></h3></div>
<div class="panel-body">
	<form action="./plugin_page.php?channel=<?php echo $channel['id']?>&func=wxconfig" method="POST" class="form-horizontal" role="form">
		<input type="hidden" name="conf_type" value="2">
		<div class="form-group">
			<label class="col-sm-3 control-label">微信AppID</label>
			<div class="col-sm-7"><input type="text" name="conf_value" value="<?php echo $data['appid']?>" placeholder="只能填写已认证的服务号或小程序，且必须与当前主体或渠道商主体一致" class="form-control" required></div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-3 col-sm-7"><input type="submit" name="submit" value="提交" class="btn btn-primary form-control"><br>
		</div>
		</div>
	</form>
</div>
</div>
<div class="panel panel-success">
<div class="panel-heading"><h3 class="panel-title">JSAPI支付授权目录配置</span></h3></div>
<div class="panel-body">
	<form action="./plugin_page.php?channel=<?php echo $channel['id']?>&func=wxconfig" method="POST" class="form-horizontal" role="form">
		<input type="hidden" name="conf_type" value="1">
		<div class="form-group">
			<label class="col-sm-3 control-label">JSAPI支付授权目录</label>
			<div class="col-sm-7"><input type="text" name="conf_value" value="<?php echo $siteurl?>" placeholder="必须以http://或https://开头，以/结尾" class="form-control" required></div>
		</div>
		<div class="form-group">
			<div class="col-sm-offset-3 col-sm-7"><input type="submit" name="submit" value="提交" class="btn btn-primary form-control"><br>
		</div>
		</div>
	</form>
</div>
</div>