<?php
/**
 * 批量转账页面
**/
include("../includes/common.php");
$type = isset($_GET['type'])?daddslashes($_GET['type']):exit('no type');
if($type == 'alipay'){
	$typename = '支付宝';
	$default_channel = $conf['transfer_alipay'];
}elseif($type == 'wxpay'){
	$typename = '微信';
	$default_channel = $conf['transfer_wxpay'];
}elseif($type == 'qqpay'){
	$typename = 'QQ钱包';
	$default_channel = $conf['transfer_qqpay'];
}elseif($type == 'bank'){
	$typename = '银行卡';
	$default_channel = $conf['transfer_bank'];
}else{
	sysmsg('参数错误');
}
$channel_select = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%".$type."%')");

$title=$typename.'批量转账';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<div class="panel panel-default">
	<div class="panel-heading" style="text-align:center">
		<div class="panel-title"><p><h3 class="panel-title"><?php echo $typename?>批量转账</h3></p>
			<div class="form-group"><div class="input-group"><div class="input-group-addon">通道选择</div>
				<select name="channel" class="form-control">
					<?php foreach($channel_select as $channel){echo '<option value="'.$channel['id'].'" '.($channel['id']==$default_channel?'selected':'').'>'.$channel['name'].''.($channel['id']==$default_channel?'（默认）':'').'</option>';} ?>
				</select>
			</div></div>
			<div class="form-group"><div class="input-group"><div class="input-group-addon">收款列表</div>
				<button type="button" class="btn btn-default btn-block" id="importExcel">导入Excel</button><span type="button" class="input-group-addon"><a href="/assets/files/transfer.xlsx">下载Excel模板</a></span>
			</div></div>
			<div class="form-group"><div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" class="form-control" name="paypwd" placeholder="请输入支付密码" required>
			</div></div>
			<div class="form-group"><div class="btn-group btn-group-justified" style="padding:8px 0;">
				<span type="button" class="input-group-addon">全选<input type="checkbox" style="margin-left:5px;" onclick="SelectAll(this)" /></span>
				<a type="button" class="btn btn-default" id="startsend">点此开始转账</a>
				<span type="button" class="input-group-addon" id="allmoney">总金额</span>
			</div></div>
			<input type="file" id="excelFile" accept=".xlsx,.xls,.csv" style="display:none">
			<small id="result" style="color:chocolate">总共<span id="allnum">0</span>个记录,已经处理<span id="donenum">0</span>个记录！</small>
		</div>
	</div>
</div>


<div class="panel panel-primary">
	<table class="table table-bordered table-condensed">
		<thead>
			<tr>
			<th style="color:silver;text-align:center"><b>序号</b></th>
			<th style="color:silver;text-align:center"><b>收款账号</b></th>
			<th style="color:silver;text-align:center"><b>收款人姓名</b></th>
			<th style="color:silver;text-align:center"><b>转账金额</b></th>
			<th style="color:silver;text-align:center"><b>转账备注</b></th>
			<th style="color:silver;text-align:center"><b>操作/状态</b></th>
			</tr>
		</thead>
		<tbody id="list">
		</tbody>
	</table>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="/assets/vendor/xlsx/xlsx.full.min.js"></script>
<script>
function SelectAll(checkbox) {
	var isChecked = $(checkbox).is(':checked');
	$('#list input[type="checkbox"]').prop('checked', isChecked);
}

$(function(){
	// 导入Excel按钮点击
	$('#importExcel').click(function(){
		$('#excelFile').click();
	});

	// Excel文件选择处理
	$('#excelFile').change(function(e){
		var file = e.target.files[0];
		if(!file) return;
		
		var reader = new FileReader();
		reader.onload = function(e){
			var data = new Uint8Array(e.target.result);
			var workbook = XLSX.read(data, {type: 'array'});
			var firstSheet = workbook.Sheets[workbook.SheetNames[0]];
			var jsonData = XLSX.utils.sheet_to_json(firstSheet, {header:['index','account','name','money','remark']});

			// 清空表格并渲染新数据
			$('#list').empty();
			var totalMoney = 0;
			var totalCount = 0;
			jsonData.forEach((item, i) => {
				if(i==0 || !item.index || !item.account || !item.money) return;
				
				totalMoney += parseFloat(item.money) || 0;
				
				// 第一行：转账信息
				$('#list').append(`
					<tr>
						<td><input type="checkbox" checked> ${item.index}</td>
						<td>${item.account}</td>
						<td>${item.name||''}</td>
						<td>${item.money}</td>
						<td>${item.remark||''}</td>
						<td style="text-align:center"><button class="btn btn-xs btn-primary submit-btn">提交转账</button></td>
					</tr>
					<tr>
						<td><span style="color:silver;">结果</span></td><td colspan="5" class="result-cell"></td>
					</tr>
				`);
				totalCount++;
			});
			
			$('#allnum').text(totalCount);
			$('#allmoney').text('总金额：'+totalMoney.toFixed(2));
		};
		reader.readAsArrayBuffer(file);
	});

	// 提交转账
	function submitTransfer(row, callback) {
		var btn = row.find('.submit-btn');
		var nextRow = row.next();
		var checkbox = row.find('input[type="checkbox"]');
		if(row.find('td:nth-child(6)').text().includes('转账成功')){
			checkbox.prop('checked', false);
			if(typeof callback === 'function') callback(false);
			return;
		}
		var paypwd = $('input[name="paypwd"]').val();
		if(paypwd == ''){
			layer.alert('请输入支付密码', {icon: 2, title: '提示'});
			if(typeof callback === 'function') callback(false);
			return;
		}
		var channel = $('select[name="channel"]').val();
		if(channel == ''){
			layer.alert('请选择支付通道', {icon: 2, title: '提示'});
			if(typeof callback === 'function') callback(false);
			return;
		}
		
		btn.hide().after('<img src="../assets/img/load.gif" height="22">');
		
		// 获取转账数据
		var transferData = {
			type: '<?php echo $type?>',
			channel: channel,
			paypwd: paypwd,
			account: row.find('td:nth-child(2)').text(),
			name: row.find('td:nth-child(3)').text(),
			money: row.find('td:nth-child(4)').text(),
			desc: row.find('td:nth-child(5)').text(),
		};
		
		$.post('./ajax_transfer.php?act=batch_submit', transferData, function(result){

			row.find('img').remove();
			var rescode = false;
			if(result.code == 0){
				// 成功
				btn.replaceWith(result.status==1?'<font color="green">转账成功</font>':'<font color="green">正在处理</font>');
				nextRow.find('.result-cell').html('<font color="green">'+result.msg+'</font>');
				checkbox.prop('checked', false);
				rescode = true;
			}else if(result.code == -1){
				// 收款方原因失败
				btn.replaceWith('<font color="red">转账失败</font>');
				nextRow.find('.result-cell').html('<font color="red">'+result.msg+'</font>');
				checkbox.prop('checked', false);
				rescode = true;
			}else{
				// 付款方原因失败
				layer.alert(result.msg, {icon: 2, title: '转账失败'});
				btn.show();
			}
			
			if(typeof callback === 'function') {
				callback(rescode);
			}
		}, 'json');
		// });
	}

	// 单笔提交
	$(document).on('click', '.submit-btn', function(){
		submitTransfer($(this).closest('tr'));
	});

	// 批量转账
	$('#startsend').click(function(){
		var btn = $(this);
		var items = $('#list tr:has(input[type="checkbox"]:checked)');
		var total = items.length;
		var processed = 0;
		if(btn.attr('disabled')) return;
		
		if(total == 0) return alert('请选择要转账的记录');
		
		// 禁用所有相关按钮
		btn.attr('disabled', true);
		$('#importExcel').prop('disabled', true);
		$('.submit-btn').prop('disabled', true);
		
		function processNext(index){
			if(index >= total) {
				// 恢复所有按钮状态
				btn.attr('disabled', false);
				$('#importExcel').prop('disabled', false);
				$('.submit-btn').prop('disabled', false);
				return;
			}
			
			var item = items.eq(index);
			submitTransfer(item, function(rescode){
				if(rescode) {
					$('#donenum').text(++processed);
					processNext(index+1);
				} else {
					// 付款方原因失败时恢复按钮状态
					btn.attr('disabled', false);
					$('#importExcel').prop('disabled', false);
					$('.submit-btn').prop('disabled', false);
				}
			});
		}
		
		processNext(0);
	});
});
</script>
	</table>
</div>
</div>
    </div>
  </div>
