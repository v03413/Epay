<?php
if(!defined('IN_CRONLITE'))exit();
?><!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>开发文档 - <?php echo $conf['sitename']?></title>
    <!-- jQuery-->
    <script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
    <!-- layui -->
    <link rel="stylesheet" href="<?php echo $cdnpublic?>layui/2.6.13/css/layui.css" />
    <script src="<?php echo $cdnpublic?>layui/2.6.13/layui.js"></script>
    <!-- zTree -->
    <link rel="stylesheet" href="<?php echo $cdnpublic?>zTree.v3/3.5.42/css/zTreeStyle/zTreeStyle.min.css" />
    <script src="<?php echo $cdnpublic?>zTree.v3/3.5.42/js/jquery.ztree.core.min.js"></script>
    <!-- SyntaxHighlighter -->
    <script src="/assets/doc/js/shCore.min.js" type="text/javascript"></script>
    <link rel="stylesheet" type="text/css" href="/assets/doc/css/shCoreDefault.css"/>
    <!-- 自定义 -->
    <link rel="stylesheet" href="/assets/doc/css/style.css" />
    <script src="/assets/doc/js/home.js"></script>
    <link rel="stylesheet" href="/assets/doc/css/docView.css" />
    <script src="/assets/doc/js/docView.js"></script>
</head>
<body>
    <!-- top-begin -->
    <div id="navbar">
        <div class="bg-blur" style="background: white!important;"></div>
        <div class="navbar-body">
            <ul class="layui-nav" lay-filter="">
                <div class="navRight">
                    <li class="layui-nav-item layui-this" lay-unselect>
                        <a href="/" style="padding-right: 40px;">返回官网</a>
                    </li>
                </div>
            </ul>
            <div class="nav-menu">
                <a href="" class="logo"><img src="/assets/img/logo.png"/></a>
                <a href="javascript:;" id="navMenuLeft"><i class="layui-icon layui-icon-spread-left"></i></a>
                <a href="javascript:;" id="navMenuRight"><i class="layui-icon">&#xe61a;</i></a>
            </div>
            <div class="clear"></div>
        </div>
    </div>
    <script>
    function showMask()
    {
        $('#mask').show();
    }
    function hideMask()
    {
        $('#mask').hide();
    }
    function clickMask()
    {
        closeMenuLeft();
        closeMenuRight();
    }
    function openMenuLeft()
    {
        $('#leftbar').addClass('show-item');
        $('#navMenuLeft').addClass('active');
        var ico = $('#navMenuLeft .layui-icon');
        ico.removeClass('layui-icon-spread-left');
        ico.addClass('layui-icon-shrink-right');
        showMask();
    }
    function closeMenuLeft()
    {
        $('#leftbar').removeClass('show-item');
        $('#navMenuLeft').removeClass('active');
        var ico = $('#navMenuLeft .layui-icon');
        ico.removeClass('layui-icon-shrink-right');
        ico.addClass('layui-icon-spread-left');
        hideMask();
    }
    $('#navMenuLeft').click(function(){
        var isShow = $('#leftbar').hasClass('show-item');
        if(isShow)
        {
            closeMenuLeft();
        }
        else
        {
            closeMenuRight();
            openMenuLeft();
        }
    });
    function openMenuRight()
    {
        $('#navbar > .navbar-body > .layui-nav').addClass('show-item')
        $('#navMenuRight').addClass('active').find('i').html('&#xe619;');
        showMask();
    }
    function closeMenuRight()
    {
        $('#navbar > .navbar-body > .layui-nav').removeClass('show-item')
        $('#navMenuRight').removeClass('active').find('i').html('&#xe61a;');
        hideMask();
    }
    $('#navMenuRight').click(function(){
        var isShow = $('#navbar > .navbar-body > .layui-nav').hasClass('show-item');
        if(isShow)
        {
            closeMenuRight();
        }
        else
        {
            closeMenuLeft();
            openMenuRight();
        }
    });
    </script>
    <!-- top-end -->

    <!-- left-begin -->
    <div id="leftbar" class="layui-nav-side">
        <div class="layui-tab layui-tab-brief" style="margin-top:0">
            <ul class="layui-tab-title">
                <li class="layui-this"><i class="layui-icon">&#xe705;</i> 目录</li>
            </ul>
            <div class="layui-tab-content">
                <div class="layui-tab-item layui-show">
                    <ul id="treeDirectory" class="ztree showIcon"></ul>
                </div>
                <div class="layui-tab-item">
                    <div class="searchBox">
                        <div id="searchForm">
                            <div class="inputBox">
                                <input type="text" id="search-keyword" autocomplete="off" name="keyword" placeholder="搜索关键词" class="layui-input"/>
                                <i class="layui-icon input-icon">&#xe615;</i>
                            </div>
                        </div>
                        <ul id="treeSearch">
                        </ul>
                        <div class="searchResultNone">
                            <i class="layui-icon">&#xe615;</i>
                            <p>未搜索到结果</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="copyright noScroll"><?php echo $conf['sitename']?></div>
        </div>
    </div>
    <script id="searchListTemplate" type="text/html">
        {{#  layui.each(d, function(index, item){ }}
        <li>
            <a href="{{ item.url }}">
                <h3>{{ item.searchedTitle }}</h3>
                <p>{{ item.searchedContent }}</p>
            </a>
        </li>
        {{#  }) }}
    </script>
    <!-- left-end -->

    <div id="body">
        <div id="content_body" name="content_body" style="width:100%;height:100%;border:none;overflow: auto;">
            <div id="article-content" class="markdown-body">
                <script>
                    var catalogList = [{"id":1,"parent_id":0,"title":"接口说明","mdFileName":"index.md","url":"index.html","level":0},{"id":2,"parent_id":0,"title":"签名规则","mdFileName":"sign_note.md","url":"sign_note.html","level":0},{"id":3,"parent_id":0,"title":"支付方式列表","mdFileName":"paytype.md","url":"paytype.html","level":0},{"id":4,"parent_id":0,"title":"支付相关接口","level":0},{"id":5,"parent_id":4,"title":"页面跳转支付","mdFileName":"pay_submit.md","url":"pay_submit.html","level":1},{"id":6,"parent_id":4,"title":"统一下单接口","mdFileName":"pay_create.md","url":"pay_create.html","level":1},{"id":7,"parent_id":4,"title":"订单查询","mdFileName":"pay_query.md","url":"pay_query.html","level":1},{"id":8,"parent_id":4,"title":"支付结果通知","mdFileName":"pay_notify.md","url":"pay_notify.html","level":1},{"id":9,"parent_id":4,"title":"订单退款","mdFileName":"pay_refund.md","url":"pay_refund.html","level":1},{"id":10,"parent_id":4,"title":"订单退款查询","mdFileName":"pay_refundquery.md","url":"pay_refundquery.html","level":1},{"id":11,"parent_id":4,"title":"关闭订单","mdFileName":"pay_close.md","url":"pay_close.html","level":1},{"id":20,"parent_id":0,"title":"商户相关接口","level":0},{"id":21,"parent_id":20,"title":"查询商户信息","mdFileName":"merchant_info.md","url":"merchant_info.html","level":1},{"id":22,"parent_id":20,"title":"查询订单列表","mdFileName":"merchant_orders.md","url":"merchant_orders.html","level":1},{"id":30,"parent_id":0,"title":"代付相关接口","level":0},{"id":31,"parent_id":30,"title":"转账发起","mdFileName":"transfer_submit.md","url":"transfer_submit.html","level":1},{"id":32,"parent_id":30,"title":"转账查询","mdFileName":"transfer_query.md","url":"transfer_query.html","level":1},{"id":33,"parent_id":30,"title":"可用余额查询","mdFileName":"transfer_balance.md","url":"transfer_balance.html","level":1},{"id":50,"parent_id":0,"title":"SDK下载","pageTitle":"接口说明","mdFileName":"sdk.md","url":"sdk.html","level":0}];
                    initTree(catalogList);
                </script>
                <h1 id="转账发起"><a href="#转账发起">转账发起</a></h1><blockquote>平台需开通代付功能，且在商户后台开启代付API接口开关，才能调用本接口发起转账</blockquote><h4 id="请求地址："><a href="#请求地址：">请求地址：</a></h4><p><code><?php echo $conf['apiurl']?>api/transfer/submit</code></p><h4 id="请求方式："><a href="#请求方式：">请求方式：</a></h4><p>POST</p><h4 id="请求参数说明："><a href="#请求参数说明：">请求参数说明：</a></h4><table><thead><tr><th align="left">字段名</th><th align="left">变量名</th><th align="left">必填</th><th align="left">类型</th><th align="left">示例值</th><th align="left">描述</th></tr></thead><tbody><tr><td align="left">商户ID</td><td align="left">pid</td><td align="left">是</td><td align="left">Int</td><td align="left">1001</td><td align="left"> </td></tr><tr><td align="left">转账方式</td><td align="left">type</td><td align="left">是</td><td align="left">String</td><td align="left">alipay</td><td align="left"><a href="#转账方式列表">转账方式列表</a></td></tr><tr><td align="left">收款方账号</td><td align="left">account</td><td align="left">是</td><td align="left">String</td><td align="left">alipay@alipay.com</td><td align="left">支付宝账号/微信OpenId/银行卡号</td></tr><tr><td align="left">收款方姓名</td><td align="left">name</td><td align="left">否</td><td align="left">String</td><td align="left">张三</td><td align="left">选填，传入则校验账号与该姓名是否匹配</td></tr><tr><td align="left">转账金额</td><td align="left">money</td><td align="left">是</td><td align="left">String</td><td align="left">1.00</td><td align="left">单位：元</td></tr><tr><td align="left">转账备注</td><td align="left">remark</td><td align="left">否</td><td align="left">String</td><td align="left"> </td><td align="left">选填</td></tr><tr><td align="left">转账交易号</td><td align="left">out_biz_no</td><td align="left">否</td><td align="left">String</td><td align="left">2016080615134334917</td><td align="left">传入后可避免出现重复请求转账</td></tr><tr><td align="left">安全发账本ID</td><td align="left">bookid</td><td align="left">否</td><td align="left">String</td><td align="left"> </td><td align="left">仅支付宝安全发转账可以传入</td></tr><tr><td align="left">当前时间戳</td><td align="left">timestamp</td><td align="left">是</td><td align="left">String</td><td align="left">1721206072</td><td align="left">10位整数，单位秒</td></tr><tr><td align="left">签名字符串</td><td align="left">sign</td><td align="left">是</td><td align="left">String</td><td align="left"> </td><td align="left">参考签名规则</td></tr><tr><td align="left">签名类型</td><td align="left">sign_type</td><td align="left">是</td><td align="left">String</td><td align="left">RSA</td><td align="left">默认为RSA</td></tr></tbody></table><h4 id="返回参数说明："><a href="#返回参数说明：">返回参数说明：</a></h4><table><thead><tr><th align="left">字段名</th><th align="left">变量名</th><th align="left">类型</th><th align="left">示例值</th><th align="left">描述</th></tr></thead><tbody><tr><td align="left">返回状态码</td><td align="left">code</td><td align="left">Int</td><td align="left">0</td><td align="left">0为成功，其它值为失败</td></tr><tr><td align="left">错误信息</td><td align="left">msg</td><td align="left">String</td><td align="left"> </td><td align="left">失败时返回转账失败原因</td></tr><tr><td align="left">转账状态</td><td align="left">status</td><td align="left">Int</td><td align="left"> </td><td align="left">0：正在处理<br />1：转账成功</td></tr><tr><td align="left">系统交易号</td><td align="left">biz_no</td><td align="left">String</td><td align="left">2016080615134334917</td><td align="left"> </td></tr><tr><td align="left">商户交易号</td><td align="left">out_biz_no</td><td align="left">String</td><td align="left">2016080615134334917</td><td align="left">可用于后续转账查询</td></tr><tr><td align="left">接口转账单号</td><td align="left">orderid</td><td align="left">String</td><td align="left">40001283951815782</td><td align="left">支付宝微信返回的转账单号</td></tr><tr><td align="left">转账完成时间</td><td align="left">paydate</td><td align="left">String</td><td align="left">2024-07-01 16:47:32</td><td align="left"> </td></tr><tr><td align="left">转账花费金额</td><td align="left">cost_money</td><td align="left">String</td><td align="left"> </td><td align="left">从商户可用余额扣减的金额</td></tr><tr><td align="left">当前时间戳</td><td align="left">timestamp</td><td align="left">String</td><td align="left">1721206072</td><td align="left">10位整数，单位秒</td></tr><tr><td align="left">签名字符串</td><td align="left">sign</td><td align="left">String</td><td align="left"> </td><td align="left">参考签名规则</td></tr><tr><td align="left">签名类型</td><td align="left">sign_type</td><td align="left">String</td><td align="left">RSA</td><td align="left">默认为RSA</td></tr></tbody></table><h4 id="转账方式列表"><a href="#转账方式列表">转账方式列表</a></h4><table><thead><tr><th align="left">状态值</th><th align="left">描述</th></tr></thead><tbody><tr><td align="left">alipay</td><td align="left">支付宝</td></tr><tr><td align="left">wxpay</td><td align="left">微信支付</td></tr><tr><td align="left">qqpay</td><td align="left">QQ钱包</td></tr><tr><td align="left">bank</td><td align="left">银行卡</td></tr></tbody></table><h4 id="其他说明："><a href="#其他说明：">其他说明：</a></h4><ul><li>如果返回的转账状态status=0，则需稍后调用转账查询接口查询转账状态。</li></ul>            <div id="mask" style="display:none" onclick="clickMask()"></div>
            </div>
        </div>
    </div>

    <script>
        $(function(){
            var leftBarTimeout = null;
            $('#leftbar').hover(function(e){
                if(null !== leftBarTimeout)
                {
                    clearTimeout(leftBarTimeout);
                    leftBarTimeout = null;
                }
                if(e.type === 'mouseenter')
                {
                    $('.left-show-hide').fadeIn(250);
                }
                else if($('#leftbar').css('left') == '0px')
                {
                    $('.left-show-hide').fadeOut(500);
                }
            });
        });
        function showLeftbar()
        {
            $('#leftbar').css('left', 0);
            $('#body').css('padding-left','');
            $('.left-show-hide > i.layui-icon').html('&#xe603;');
        }
        function hideLeftbar()
        {
            $('#leftbar').css('left', '-240px');
            $('#body').css('padding-left',0);
            $('.left-show-hide > i.layui-icon').html('&#xe602;');
            $('.left-show-hide').fadeIn(250);
        }
        $('.left-show-hide').click(function(){
            if($('#leftbar').css('left') == '0px')
            {
                hideLeftbar();
            }
            else
            {
                showLeftbar();
            }
        })
    </script>
</body>
</html>