<?php
if(!defined('IN_CRONLITE'))exit;
?>
	<div class="tab-content">
		<div class="tab-pane ng-scope active">
			<div class="row step-line nav nav-pills nav-justified steps verified">
				<div id="tag1" class="col-sm-12 col-md-6 mt-step-col first fill complete-active">
					<div class="mt-step-col-cont row bg-primary">
						<div class="col-xs-3 bg-primary-l">
							<i class="icon glyphicon glyphicon-edit"></i>
						</div>
						<div class="col-xs-9 bg-primary-r">
							<div class="mt-step-title uppercase font-grey-cascade ">填写认证信息
							</div>
						</div>
					</div>
				</div>
				<div id="tag3" class="col-sm-12 col-md-6 mt-step-col last">
					<div class="mt-step-col-cont row ">
						<div class="col-xs-3 bg-primary-l">
							<i class="icon fa fa-check-circle-o"></i>
						</div>
						<div class="col-xs-9 bg-primary-r">
							<div class="mt-step-title uppercase font-grey-cascade ">
								认证完成</div>
						</div>
					</div>
				</div>
			</div>
			<div class="line line-dashed b-b line-lg pull-in"></div>

<?php if($conf['cert_money']>0){?>
			<div class="alert alert-info alert-dismissible" role="alert" style="line-height: 26px;">
<p>认证需要<b><?php echo $conf['cert_money']; ?></b>元，请确保你的账号内有<?php echo $conf['cert_money']; ?>元余额[<a href="recharge.php">点此充值</a>]，认证成功会自动扣除，认证失败不扣费</p>
			</div>
<?php }?>

			<div id="step1">
			<form class="form-horizontal devform">
				<input type="hidden" name="csrf_token" value="<?php echo $csrf_token?>">
				<div class="form-group">
					<label class="col-sm-2 control-label">认证方式</label>
					<div class="col-sm-9">
					<div class="certification_type">
						手机号三要素认证
					</div>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">真实姓名</label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="certname" value="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">身份证号</label>
					<div class="col-sm-9">
						<input class="form-control" type="text" name="certno" value="">
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">手机号码</label>
					<div class="col-sm-9">
						<div class="input-group"><input class="form-control" type="text" name="phone" value="<?php echo $userrow['phone']?>" disabled><a class="input-group-addon" href="./editinfo.php">修改绑定</a></div>
					</div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-9"><div class="input-group"><font color="red"><i class="fa fa-info-circle"></i>&nbsp;手机号码的实名信息需与所填身份证号码对应，否则无法认证成功</font></div>
				 </div>
				</div>
				<div class="form-group">
				  <div class="col-sm-offset-2 col-sm-4"><input type="button" id="certSubmit" value="提交认证" class="btn btn-primary form-control"/><br/>
				 </div>
				</div>
			</form>
			<div class="alert alert-warning alert-dismissible" role="alert" style="line-height: 26px;font-size: 13px;margin-top: 50px;">
<p>1、为了更好的享受<?php echo $conf['sitename']?>提供的服务，本人知晓并同意授权手机号实名认证方式用于验证本人信息的真实性</p>
<p>2、本站承诺任何在本网站提交的用户信息，仅限用于本站为用户提供服务，本站承诺为用户的隐私及其他个人信息采取严格保密措施，并在必要时销毁数据。</p>
			</div>
			</div>
		</div>
	</div>
</div>
</div>
    </div>
  </div>
<?php include 'foot.php';?>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="<?php echo $cdnpublic?>jquery.qrcode/1.0/jquery.qrcode.min.js"></script>
<script>
$(document).ready(function(){
	$("#certSubmit").click(function(){
		var certname=$("input[name='certname']").val();
		var certno=$("input[name='certno']").val();
		var csrf_token=$("input[name='csrf_token']").val();
		if(certname=='' || certno==''){
			layer.alert('请确保各项不能为空');return false;
		}
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : "POST",
			url : "ajax2.php?act=certificate",
			data : {certname:certname,certno:certno,csrf_token:csrf_token},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 2){
					layer.alert(data.msg, {icon: 1}, function(){ window.location.href='./certificate.php'; });
				}else{
					layer.alert(data.msg, {icon: 2});
				}
			}
		});
	});
});
</script>