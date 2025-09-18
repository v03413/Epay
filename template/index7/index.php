<?php
if(!defined('IN_CRONLITE'))exit();
?>
<!DOCTYPE html>
<html lang="zh-CN" class="no-js">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo $conf['title']?></title>
  <meta name="keywords" content="<?php echo $conf['keywords']?>" />
  <meta name="description" content="<?php echo $conf['description']?>" />
  <link rel="stylesheet" type="text/css" href="<?php echo $cdnpublic?>twitter-bootstrap/4.6.2/css/bootstrap.min.css" />
  <link rel="stylesheet" type="text/css" href="<?php echo STATIC_ROOT?>css/magnific-popup.css">
  <link rel="stylesheet" type="text/css" href="<?php echo STATIC_ROOT?>css/owl.theme.css">
  <link rel="stylesheet" type="text/css" href="<?php echo STATIC_ROOT?>css/aos.css">
  <link rel="stylesheet" type="text/css" href="<?php echo STATIC_ROOT?>css/mobiriseicons.css">
  <link rel="stylesheet" type="text/css" href="<?php echo STATIC_ROOT?>css/as.css">
</head>
<body>
  <nav class="navbar navbar-expand-lg fixed-top custom_nav_menu sticky">
    <div class="container">
      <a class="navbar-brand logo" href=""><img class="header-logo-img" src="assets/img/logo.png" alt="<?php echo $conf['sitename']?>" /></a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-as"
      aria-controls="navbar-as" aria-expanded="false" aria-label="Toggle navigation">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="height:37px;width:28px"><path d="M3,6H21V8H3V6M3,11H21V13H3V11M3,16H21V18H3V16Z" /></svg>
      </button>
      <div class="collapse navbar-collapse" id="navbar-as">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item active">
            <a href="#home" class="nav-link">主页</a>
          </li>
          <li class="nav-item">
            <a href="#about" class="nav-link">接入</a>
          </li>
          <li class="nav-item">
            <a href="#services" class="nav-link">服务</a>
          </li>
          <li class="nav-item">
            <a href="#statistics" class="nav-link">统计</a>
          </li>
          <li class="nav-item">
            <a href="#outlook" class="nav-link">展望</a>
          </li>
          <li class="nav-item">
            <a href="./doc.html" class="nav-link">开发文档</a>
          </li>
          <li class="nav-item">
            <a href="./agreement.html" class="nav-link">服务条款</a>
          </li>
		  <?php if($conf['test_open']){?><li class="nav-item"><a href="/user/test.php" class="nav-link">在线测试</a></li><?php }?>
        </ul>
        <a href="./user/" class="btn_outline btn btn_small text-capitalize btn_rounded navbar-btn">商户登录</a>
      </div>
    </div>
  </nav>
  <section class="bg_home_tech_soft full_height_100vh_home" id="home">
    <div class="bg_overlay_cover_on"></div>
    <div class="home_table_cell">
      <div class="home_table_cell_center">
        <div class="container position-relative up-index">
          <div class="row">
            <div class="col-lg-6">
              <div class="mt-3">
                <h1 class="home_title font-weight-normal text-white mx-auto text-capitalize mb-0"><?php echo $conf['sitename']?> - <span class="text-typed" data-elements="用支付响应世界 用支付创造未来！"></span></h1>
                <div class="home_text_details">
                  <p class="home_subtitle mt-4 mb-0"><font style="text-transform: uppercase;"><?php echo $_SERVER['HTTP_HOST']?></font> - 极速响应、安全可靠、方便快捷是我们最大的特点，轻松实现手机付款、在线付款，<?php echo $conf['sitename']?>是您的不二之选，欢迎咨询<?php echo $conf['sitename']?>。</p>
                </div>
                <div class="home_btn_manage mt-4 pt-3">
                  <a href="./user/" class="btn btn_outline btn_rounded mr-3">商户中心</a>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="home_side_img mt-3">
                <img src="<?php echo STATIC_ROOT?>images/info1.png" alt="首页" class="img-fluid mx-auto d-block"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="section_all bg-light" id="about">
    <div class="container">
      <div class="row vertical_content_manage" data-aos="fade-up">
        <div class="col-lg-6">
          <div class="about_details mt-3">
            <h3 class="text-capitalize mb-3">我们拥有比同行更优质的服务</h3>
            <div class="section_title_border">
            </div>
            <p class="text_muted mt-3">您永远不会想象那么强大的创意业务可以轻松实现，<?php echo $conf['sitename']?>为您提供多种解决方案。</p>
          </div>
          <div class="row mt-3">
            <div class="col-lg-6">
              <div class="about_details_box bg-white p-4 mt-3">
                <p class="text_muted mb-0">支持支付宝、微信、QQ钱包等主流支付渠道，让您拥有PC网页支付、扫码支付、移动HTML5支付。</p>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="about_details_box bg-white p-4 mt-3">
                <p class="text_muted mb-0"><?php echo $conf['sitename']?>通过简单的页面配置，可以替代复杂繁琐的人工资金结算业务，提高业务实时性，降低错误。</p>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-lg-12">
              <div class="mt-3">
                <a href="./doc.html" class="btn btn_custom btn_rounded">开发文档</a>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="img_about mt-3">
            <img src="<?php echo STATIC_ROOT?>images/info2.png" alt="接入" class="img-fluid mx-auto d-block">
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="bg_custom">
    <div class="container">
      <div class="row pt-5 pb-5" data-aos="fade-up">
        <div class="col-lg-8 text-white small_cta_desc mt-3 mb-3">
          <h4>那么，你下一步准备好了吗？ 赶紧加入我们吧</h4>
        </div>
        <div class="col-lg-4 mt-3 mb-3 text-md-right">
          <a href="./user/reg.php" class="btn btn_outline">立即注册</a>
        </div>
      </div>
    </div>
  </section>
  <section class="section_all" id="services">
    <div class="container">
      <div class="row vertical_content_manage" data-aos="fade-up">
        <div class="col-lg-5">
          <div class="services_img mt-3">
            <img src="<?php echo STATIC_ROOT?>images/info3.png" alt="服务" class="img-fluid mx-auto d-block">
          </div>
        </div>
        <div class="col-lg-7">
          <div class="row mt-3">
            <div class="col-lg-6">
              <div class="services_boxes p-4 mt-3">
                <div class="services_icon ">
                  <i class="mbri-desktop text-white bg_first_service"></i>
                </div>
                <div class="services_desc mt-4">
                  <h5 class="font-weight-bold">支付能力</h5>
                  <p class="mt-3 text_muted mb-0">适用于商家在移动端网页应用中集成<?php echo $conf['sitename']?>的快捷支付功能 ，集成<?php echo $conf['sitename']?>提供的SDK，一键接入</p>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="services_boxes p-4 mt-3">
                <div class="services_icon">
                  <i class="mbri-features text-white bg_second_service"></i>
                </div>
                <div class="services_desc mt-4">
                  <h5 class="font-weight-bold">金融科技</h5>
                  <p class="mt-3 text_muted mb-0"><?php echo $conf['sitename']?>基于互联网，融合行业解决方案，驱动产业模式升级，创新应用场景</p>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-lg-6">
              <div class="services_boxes p-4 mt-3">
                <div class="services_icon">
                  <i class="mbri-globe-2 text-white bg_third_service"></i>
                </div>
                <div class="services_desc mt-4">
                  <h5 class="font-weight-bold">接口支持</h5>
                  <p class="mt-3 text_muted mb-0">支付、分享、账户、营销、信用、服务窗等九大优质接口支持</p>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="services_boxes p-4 mt-3">
                <div class="services_icon">
                  <i class="mbri-photo text-white bg_four_service"></i>
                </div>
                <div class="services_desc mt-4">
                  <h5 class="font-weight-bold">盈利模式</h5>
                  <p class="mt-3 text_muted mb-0">基于商家服务市场，为合作伙伴的插件及服务提供变现渠道</p>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-lg-12">
              <div class="mt-3">
                <a href="./user/reg.php" class="btn btn_custom btn_rounded">加入我们</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="section_all bg_custom" id="statistics">
    <div class="container">
      <div class="row vertical_content_manage" data-aos="fade-up">
        <div class="col-lg-6">
          <div class="features_box p-3 mt-3">
            <div class="features_icon">
              <i class="mbri-laptop text-white"></i>
            </div>
            <div class="features_details text-white">
              <p class="text-white mb-0">商户总数：2789</p>
            </div>
          </div>
          <div class="features_box p-3 mt-3">
            <div class="features_icon">
              <i class="mbri-touch-swipe text-white"></i>
            </div>
            <div class="features_details text-white">
              <p class="text-white mb-0">订单总数：548156</p>
            </div>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="features_box p-3 mt-3">
            <div class="features_icon">
              <i class="mbri-laptop text-white"></i>
            </div>
            <div class="features_details text-white">
              <p class="text-white mb-0">商户结算总额：¥348511.89
              </p>
            </div>
          </div>
          <div class="features_box p-3 mt-3">
            <div class="features_icon">
              <i class="mbri-touch-swipe text-white"></i>
            </div>
            <div class="features_details text-white">
              <p class="text-white mb-0">商户总计余额：¥65498.34
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <section class="section_all bg-light" id="outlook">
    <div class="container">
      <div class="row" data-aos="fade-up">
        <div class="col-lg-12">
          <div class="about_details mx-auto text-center mt-3">
            <h3 class="text-capitalize mb-3">展望未来</h3>
            <div class="section_title_border mx-auto">
            </div>
            <p class="text_muted mt-3">您永远不会想象那么强大的创意业务可以轻松实现，<?php echo $conf['sitename']?>为您提供多种解决方案。</p>
          </div>
        </div>
      </div>
      <div class="row mt-5" data-aos="fade-up">
        <div class="col-lg-12">
          <div id="owl-demo" class="owl-carousel owl-theme">
            <div class="item testi_box mx-auto text-center">
              <div class="testi_icon">
                <i class="mbri-user text_custom"></i>
              </div>
              <p class="review_box">"人生的磨难是很多的，所以我们不可对于每一件轻微的伤害都过于敏感。在生活磨难面前，精神上的坚强和无动于衷是我们抵抗罪恶和人生意外的最好武器。"</p>
              <p class="client_name text-center mb-0 mt-4 font-weight-bold">- 《<?php echo $conf['sitename']?>》创始人</p>
            </div>
            <div class="item testi_box mx-auto text-center">
              <div class="testi_icon">
                <i class="mbri-user2 text_custom"></i>
              </div>
              <p class="review_box">"人生必有风险，所以引人入胜亦在于此。"</p>
              <p class="client_name text-center mb-0 mt-4 font-weight-bold">- 《<?php echo $conf['sitename']?>》产品经理</p>
            </div>
            <div class="item testi_box mx-auto text-center">
              <div class="testi_icon">
                <i class="mbri-github text_custom"></i>
              </div>
              <p class="review_box">"当你看到不可理解的现象，感到迷惑时，真理可能已经披着面纱悄悄地站在你的面前。"</p>
              <p class="client_name text-center mb-0 mt-4 font-weight-bold">- <?php echo $conf['sitename']?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <footer class="section_all pb-0 footer_detail footer_background">
    <div class="container">
      <div class="row" data-aos="fade-up">
        <div class="col-lg-4">
          <h6 class="text-white text-capitalize">关于我们</h6>
          <p class="mt-3 text-white ">E-mail : <?php echo $conf['email']?></p>
          <p class="text-white">客服QQ : <a href="https://wpa.qq.com/msgrd?v=3&uin=<?php echo $conf['kfqq']?>&site=pay&menu=yes" title="点击联系客服QQ" target="_blank"><?php echo $conf['kfqq']?></a></p>
          <p class="mb-0 mt-3 text-white">本站域名 : <?php echo $_SERVER['HTTP_HOST']?></p>
        </div>
        <div class="col-lg-4">
          <h6 class="text-white text-capitalize">本站相关</h6>
          <ul class="list-unstyled footer_menu_list mt-3">
            <li>
              <a href="./agreement.html">服务条款</a>
            </li>
            <li>
              <a href="./doc.html">开发文档</a>
            </li>
          </ul>
        </div>
        <div class="col-lg-4">
          <h6 class="text-white text-capitalize">合作伙伴</h6>
          <ul class="list-unstyled footer_menu_list mt-3">
            <li>
              <a href="<?php echo $conf['hzlink1'];?>"><?php echo $conf['hzhb1'];?></a>
            </li>
            <li>
              <a href="<?php echo $conf['hzlink2'];?>"><?php echo $conf['hzhb2'];?></a>
            </li>
          </ul>
        </div>
      </div>
      <div class="fot_bor"></div>
      <div class="row pt-3 pb-3">
        <div class="col-lg-6">
          <div class=" text-left">
            <p class="text-white mb-0"><?php echo date("Y")?> &copy; <a href=""><?php echo $conf['sitename']?></a></p>
          </div>
        </div>
        <div class="col-lg-6">
          <div class=" text-right">
            <p class="text-white mb-0"><?php echo $conf['footer']?></p>
          </div>
        </div>
      </div>
    </div>
  </footer>
  <script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
  <script src="<?php echo $cdnpublic?>twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
  <script src="<?php echo STATIC_ROOT?>js/owl.carousel.min.js"></script>
  <script src="<?php echo STATIC_ROOT?>js/aos.js"></script>
  <script src="<?php echo STATIC_ROOT?>js/typed.js"></script>
  <script src="<?php echo STATIC_ROOT?>js/particles.js"></script>
  <script src="<?php echo STATIC_ROOT?>js/particles.app.js"></script>
  <script>AOS.init({easing:'ease-in-out-sine',duration:1000});$(".text-typed").each(function(){var $this=$(this);$this.typed({strings:$this.attr('data-elements').split(','),typeSpeed:100,backDelay:3000})});</script>
</body>
</html>