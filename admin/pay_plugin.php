<?php
/**
 * 支付插件
**/
include("../includes/common.php");
$title='支付插件';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-10 col-lg-9 center-block" style="float: none;">
<?php
$my=isset($_GET['my'])?$_GET['my']:null;
if($my=='refresh') {
	\lib\Plugin::updateAll();
	exit("<script language='javascript'>alert('刷新插件列表成功！');history.go(-1);</script>");
}else{
$list = \lib\Plugin::getAll();
?>
<div class="panel panel-info">
   <div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo count($list);?></b> 个支付插件&nbsp;<span class="pull-right"><a href="./pay_plugin.php?my=refresh" class="btn btn-default btn-xs"><i class="fa fa-refresh"></i> 刷新插件列表</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>插件名称</th><th>插件描述</th><th>插件作者</th><th>包含的支付方式</th><th>包含的转账方式</th><th>分账</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
  $ps_support = in_array($res['name'], \lib\ProfitSharing\CommUtil::$plugins) ? '支持' : '';
echo '<tr><td><b>'.$res['name'].'</b></td><td>'.$res['showname'].'</td><td>'.($res['link']?'<a href="'.$res['link'].'" target="_blank" rel="noreferrer">'.$res['author'].'
</a>':$res['author']).'</td><td>'.$res['types'].'</td><td>'.$res['transtypes'].'</td><td>'.$ps_support.'</td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	  <div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 支付插件目录：/plugins/，请将符合要求的支付插件源码解压到支付插件目录，然后点击 刷新插件列表 即可显示在该列表中。
        </div>
	</div>
<?php }?>
    </div>
  </div>