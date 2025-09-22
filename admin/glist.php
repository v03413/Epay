<?php
/**
 * 用户组设置
**/
include("../includes/common.php");
$title='用户组设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-10 center-block" style="float: none;">
<?php

$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
}
unset($rs);

function display_info($info){
	global $paytype;
	$result = '';
	$arr = json_decode($info, true);
	foreach($arr as $k=>$v){
		if($v['channel']==0)continue;
		$result .= $paytype[$k].'('.$v['channel'].'):'.$v['rate'].',';
	}
	return substr($result,0,-1);
}

$list = $DB->getAll("SELECT * FROM pre_group ORDER BY gid ASC");
?>
<div class="panel panel-success">
   <div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo count($list);?></b> 个用户组&nbsp;<span class="pull-right"><a href="./gedit.php?act=add" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>GID</th><th>用户组名称</th><th>通道与费率</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
echo '<tr><td><b>'.$res['gid'].'</b></td><td>'.$res['name'].'</td><td>'.display_info($res['info']).'</td><td><a class="btn btn-xs btn-default" href="./ulist.php?gid='.$res['gid'].'">用户</a>&nbsp;<a class="btn btn-xs btn-info" href="./gedit.php?act=edit&gid='.$res['gid'].'">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['gid'].')">删除</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	  <div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 未设置用户组的用户是默认用户组，会自动使用已添加的可用支付通道和通道默认费率
        </div>
	</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script>
function delItem(id) {
	if(id==0){
		layer.msg('系统自带默认用户组不支持删除');
		return false;
	}
	var confirmobj = layer.confirm('你确实要删除此用户组吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=delGroup&gid='+id,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
</script>