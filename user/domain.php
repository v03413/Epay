<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='授权支付域名';
include './head.php';
?>
<?php

function display_status($status){
	if($status == 1){
		return '<font color="green">正常</font>';
	}elseif($status == 2){
		return '<font color="red">拒绝</font>';
	}else{
		return '<font color="blue">审核中</font>';
	}
}

$numrows=$DB->getColumn("SELECT count(*) from pre_domain WHERE uid={$uid}");

$list=$DB->getAll("SELECT * FROM pre_domain WHERE uid={$uid} order by id desc");

?>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">授权支付域名</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			授权支付域名&nbsp;(<?php echo $numrows?>)<a href="javascript:addDomain()" class="btn btn-success btn-xs pull-right">添加域名</a>
		</div>
		<div class="panel-body">
		<div class="table-responsive">
        <table class="table table-striped table-hover table-bordered">
          <thead><tr><th>域名</th><th>状态</th><th>提交时间</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res){
	echo '<tr><td>'.$res['domain'].'</td><td>'.display_status($res['status']).'</td><td>'.$res['addtime'].'</td><td><a href="javascript:delDomain('.$res['id'].')" class="btn btn-xs btn-danger">删除</a></td></tr>';
}
?>
		  </tbody>
        </table>
      </div>
	  </div>

	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>

<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function addDomain(){
	layer.open({
		type: 1,
		area: ['350px'],
		closeBtn: 2,
		title: '添加授权支付域名',
		content: '<div class="wrapper"><div class="alert alert-warning">域名添加后需要等管理员审核通过才能使用，请确保网站可以正常访问并且有明确的业务模式或在售商品。</div><input class="form-control" type="text" name="content" value="" autocomplete="off" placeholder="请输入域名，支持通配符*"></div>',
		btn: ['确认', '取消'],
		yes: function(){
			var content = $("input[name='content']").val();
			if(content == ''){
				$("input[name='content']").focus();return;
			}
			var ii = layer.load(2, {shade:[0.1,'#fff']});
			$.ajax({
				type : 'POST',
				url : 'ajax2.php?act=addDomain',
				data : {domain: content},
				dataType : 'json',
				success : function(data) {
					layer.close(ii);
					if(data.code == 0){
						layer.alert(data.msg, {icon:1}, function(){ window.location.reload() });
					}else{
						layer.alert(data.msg, {icon:0});
					}
				},
				error:function(data){
					layer.close(ii);
					layer.msg('服务器错误');
				}
			});
		}
	});
}
function delDomain(id) {
	if(confirm('确定要删除此域名吗？')){
		$.ajax({
			type : 'POST',
			url : 'ajax2.php?act=delDomain',
			data : {id: id},
			dataType : 'json',
			success : function(data) {
				if(data.code == 0){
					layer.msg('删除成功');
					window.location.reload()
				}else{
					layer.alert(data.msg, {icon:2});
				}
			}
		});
	}
}
</script>