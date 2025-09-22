<?php
if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    die('require PHP >= 7.4 !');
}
$is_defend = true;
$allow_search = true;
include("./includes/common.php");

if(isset($_GET['doc'])){
    $doc = trim($_GET['doc']);
    if(!$conf['apiurl'])$conf['apiurl'] = $siteurl;
    $loadfile = \lib\Template::loadDoc($doc);
    include $loadfile;
    exit;
}

$mod = isset($_GET['mod'])?$_GET['mod']:'index';

if(isset($_GET['invite'])){
    $invite_code = trim($_GET['invite']);
    $uid = get_invite_uid($invite_code);
    if($uid && is_numeric($uid)){
        $_SESSION['invite_uid'] = intval($uid);
    }
}

if($mod=='index'){
    if($conf['homepage']==2){
        echo '<html><frameset framespacing="0" border="0" rows="0" frameborder="0">
        <frame name="main" src="'.$conf['homepage_url'].'" scrolling="auto" noresize>
    </frameset></html>';
        exit;
    }elseif($conf['homepage']==1){
        exit("<script language='javascript'>window.location.href='/user/';</script>");
    }
}

$loadfile = \lib\Template::load($mod);
include $loadfile;