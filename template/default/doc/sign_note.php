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
                <h1 id="签名规则"><a href="#签名规则">签名规则</a></h1><h2 id="签名步骤"><a href="#签名步骤">签名步骤</a></h2><blockquote>对本平台接口发起的请求，需要进行签名。</blockquote><p>1、获取请求报文所有<strong>非空</strong>请求参数，不包括数组、字节类型参数，如文件、字节流，剔除<strong>sign</strong>、<strong>sign_type</strong>字段，并按照第一个字符的键值ASCII码递增排序（字母升序排序），如果遇到相同字符则按照第二个字符的键值ASCII码递增排序，以此类推。</p><p>2、将排序后的参数和对应值，组合成“<strong>参数=参数值</strong>”的格式，并且把这些参数用 <strong>&</strong> 字符连接起来，此时生成的字符串为待签名字符串。</p><p>3、使用<strong>商户私钥</strong>，对待签名字符串计算RSA签名（SHA256WithRSA），得到签名sign。</p><h2 id="验签步骤"><a href="#验签步骤">验签步骤</a></h2><blockquote>针对接口返回的数据，以及异步通知回调的数据，需进行验签。</blockquote><p>1、先根据签名步骤里面的1～2，获取到待签名字符串。</p><p>2、使用<strong>平台公钥</strong>，根据签名字符串sign，对待签名字符串与进行RSA验签（SHA256WithRSA）</p><h2 id="注意事项"><a href="#注意事项">注意事项</a></h2><p>1、商户私钥（private key）需填写到代码中供签名时使用。生成的私钥需妥善保管，避免遗失，不要泄露。</p><p>2、平台公钥（public key）用于接口返回数据、异步通知回调数据的验签。</p><p>3、具体发起支付相关流程的示例代码可下载SDK查看。</p>            <div id="mask" style="display:none" onclick="clickMask()"></div>
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