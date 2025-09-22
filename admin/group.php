<?php
/**
 * 用户组购买设置
**/
include("../includes/common.php");
$title='用户组购买设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<style>
.table>tbody>tr>td{vertical-align: middle;}</style>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-10 col-lg-9 center-block" style="float: none;">
<?php

$list1 = $DB->getAll("SELECT * FROM pre_group WHERE isbuy=1 ORDER BY SORT ASC");
$list2 = $DB->getAll("SELECT * FROM pre_group WHERE isbuy=0");
?>
<div class="panel panel-primary">
<div class="panel-body">
<div class="list-group-item">用户组购买开关：<?php echo $conf['group_buy']==1?'<span style="color:green">已开启</span>&nbsp;&nbsp;<a href="javascript:changeSetting(0)" class="btn btn-danger btn-sm">点击关闭</a>':'<span style="color:red">已关闭</span>&nbsp;&nbsp;<a href="javascript:changeSetting(1)" class="btn btn-success btn-sm">点击开启</a>';?></div>
</div>
</div>
<div class="panel panel-success">
   <div class="panel-heading"><h3 class="panel-title">可购买的用户组 （<b><?php echo count($list1);?></b>）</h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>GID</th><th>用户组名称</th><th>售价</th><th>有效期(月)</th><th>排序</th><th>可见范围&nbsp;<a tabindex="0" role="button" data-toggle="popover" data-trigger="focus" title="" data-placement="top" data-content="可设置哪些用户组可以看到并购买该用户组，留空为全部可见，填写可见用户组GID，多个GID用,隔开" data-original-title="可见范围说明"><span class="glyphicon glyphicon-info-sign"></span></a></th><th>操作</th></tr></thead>
          <tbody><form id="glist">
<?php
foreach($list1 as $res)
{
echo '<tr><td><b>'.$res['gid'].'</b></td><td>'.$res['name'].'</td><td><input type="text" class="form-control input-sm" name="price['.$res['gid'].']" value="'.$res['price'].'" placeholder="填写售价" required></td><td><input type="text" class="form-control input-sm" name="expire['.$res['gid'].']" value="'.($res['expire']=='0'?'':$res['expire']).'" placeholder="0或留空为永久" required></td><td><input type="text" class="form-control input-sm" name="sort['.$res['gid'].']" value="'.$res['sort'].'" placeholder="数字越小越靠前" required></td><td><input type="text" class="form-control input-sm" name="visible['.$res['gid'].']" value="'.$res['visible'].'" placeholder="留空为全部可见" required></td><td><a class="btn btn-sm btn-warning" onclick="setItem('.$res['gid'].',0)">下架</a></td></tr>';
}
if(count($list1)>0)echo '<tr><td></td><td></td><td colspan="4"><span class="btn btn-success btn-sm btn-block" onclick="saveAll()">保存全部</span></td><td></td></tr>';
?></form>
          </tbody>
        </table>
      </div>
	</div>
<div class="panel panel-primary">
   <div class="panel-heading"><h3 class="panel-title">不可购买的用户组 （<b><?php echo count($list2);?></b>）</h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>GID</th><th>用户组名称</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list2 as $res)
{
echo '<tr><td><b>'.$res['gid'].'</b></td><td>'.$res['name'].'</td><td><a class="btn btn-sm btn-success" onclick="setItem('.$res['gid'].',1)">上架</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	</div>
  </div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function saveAll(){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=saveGroupPrice',
		data : $("#glist").serialize(),
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
function setItem(id,status) {
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=saveGroup',
		data : {action:'changebuy', gid:id, status:status},
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
}
function changeSetting(value){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax.php?act=set',
		data : {group_buy:value},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('更换设置成功！', {
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
$(function () {
  $('[data-toggle="popover"]').popover()
})
</script>