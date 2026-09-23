<?php
/******************************************
* Created on : 2009/04/27                 *
* Author     : Yoshinori Iketani          *
* Copyright  : SkyAvy, Inc.               *
* URL        : www.skyayv.com             *
******************************************/
require_once '../includes/config.php';

// 海外IPブロック
$geo = @file_get_contents('http://ip-api.com/json/' . $_SERVER['REMOTE_ADDR'] . '?fields=countryCode');
if ($geo !== false) {
    $geo_data = json_decode($geo);
    if (isset($geo_data->countryCode) && $geo_data->countryCode !== 'JP') {
        http_response_code(403);
        exit('Access denied');
    }
}

if (! isMobile()) {
    $redirectUrl = SSL_URL.'vendors/quotes_search.php';
    if (empty($_GET)) {
        header('Location: '.$redirectUrl);
    } else {
        header('Location: '.$redirectUrl.'?'.http_build_query($_GET));
    }
    exit;
}

$shouldDisplayChatbot = true;
if (! empty($_GET)) {
  // パラメータの取得
    session_start();
    if (isset($_GET['raku'])) {
        $_SESSION['tp'] = "raku=" . $_GET['raku'];
    } elseif (isset($_GET['atown'])) {
        $_SESSION['tp'] = "atown=" . $_GET['atown'];
    } elseif (isset($_GET['p'])) {
        $_SESSION['tp'] = "p=" . $_GET['p'];
    }
    // Ad判定用パラメータの取得
    // a8: https://www.housingbazar.jp/vendors/quotes_search_simple.php?province=.php
    // ゴンドラ: http://www.housingbazar.jp/vendors/quotes_search_simple.php?p=1&in=g
    if (isset($_GET['a8'])) {
        $_SESSION['ad_company'] = 'a8';
        $_SESSION['ad_code'] = $_GET['a8'];
        $shouldDisplayChatbot = false;
    } elseif (isset($_GET['in'])) {
        $_SESSION['ad_company'] = $_GET['in'];
        $_SESSION['ad_code'] = 'Unknown';
        $shouldDisplayChatbot = false;
    } elseif (isset($_GET['province']) && $_GET['province'] === '.php') {
        $_SESSION['ad_company'] = 'Unknown';
        $_SESSION['ad_code'] = 'Unknown';
    }
}

$pageTitle        = META_VENDORS_QUOTES_SEARCH;
$metaDescription  = META_VENDORS_QUOTES_SEARCH_DESC;
$metaKeywords     = META_VENDORS_QUOTES_SEARCH_KEY;

$vClass = new Vendors();
$pClass = new Provinces();

$ps = $pClass->getAll();
// getters
if (! empty($_GET['txt'])) {
    $txt = $_GET['txt'];
} else {
    $txt = "";
}
if (empty($_GET['province']) || !is_numeric($_GET['province'])) {
    $pid = null;
} else {
    $pid = $_GET['province'];
}
$vs = $vClass->getAllInclusiveByTxt($pid, $txt, 999999, 0);
$vid = array();
if (! empty($_GET['vid'])) {
    $vid = $_GET['vid'];
    $vid = array_unique($vid);
}
if (! empty($_GET['del_id']) && is_numeric($_GET['del_id'])) {
    $del_id = intval($_GET['del_id']);
    unset($vid[array_search($del_id, $vid)]);
} else {
    if (count($vs) > 0) {
        foreach ($vs as $v) {
            $vid[] = $v['id'];
        }
    }
}

if (! empty($_GET['send_quotes'])) {
    header('Location: '.SSL_URL.'vendors/quotes_city_select_sp.php?'.$_SERVER['QUERY_STRING']);
    exit;
}

if (isset($_GET['utm_source'])) {
  setcookie('utm_source', $_GET['utm_source'], time() + 3600 * 6);
}

$bread = array(META_VENDORS_QUOTES_SEARCH);

/**
 * スマートフォン用ヘッダー
 */
@session_start();
if (isset($_SESSION['cart'])) {
    $cart = $_SESSION['cart'];
    if (isset($cart['vendors'])) {
        $cartcount = count($cart['vendors']);
    } else {
        $cartcount = 0;
    }

    if (! empty($cart['papers'])) {
        $cartcount += count($cart['papers']);
    }
} else {
    $cart = array();
    $cartcount = 0;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>一括資料請求｜ハウジングバザール</title>
<meta name="viewport"
content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
<meta name="format-detection" content="telephone=no">
<script src="js/jquery-1.7.min.js" type="text/javascript"></script>
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="stylesheet" href="css/styles.css" type="text/css">
<link rel="stylesheet" href="css/package.css" type="text/css">

<!--    <link rel="stylesheet" href="../style/style.css" type="text/css" media="all" />-->
<link rel="stylesheet" href="/css/responsive4.css" type="text/css" media="all">
<link rel="stylesheet" href="/css/pagetop.css" type="text/css" media="all">
<link rel="stylesheet" href="/css/hamburgermenu.css" type="text/css" media="all">
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-NKDXW78');</script>
<!-- End Google Tag Manager -->
<script type="text/javascript" src="/js/jquery.easing.1.3.js"></script>
<script type="text/javascript" src="/js/easyTooltip.js"></script>
<script type="text/javascript" src="/js/jquery-preloadImages.js"></script>
<script type="text/javascript" src="/js/script.js"></script>
<script src="//statics.a8.net/a8sales/a8sales.js"></script>
<!-- AFRo トラッキング -->
<script src="https://www.cross-a.net/act/afrolp.js"></script>
<style>
.hb-wrap { width: 100% !important; max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
.hb-hero-img { width: 100% !important; display: block !important; }
.hb-form-card {
  background: #fff !important;
  margin: 8px 12px !important;
  border-radius: 16px !important;
  padding: 20px 16px !important;
  box-shadow: 0 6px 24px rgba(74,55,40,0.12) !important;
}
.hb-badge-row { display: flex !important; gap: 6px !important; margin-bottom: 14px !important; flex-wrap: wrap !important; }
.hb-badge {
  font-size: 12px !important;
  font-weight: bold !important;
  padding: 5px 10px !important;
  border-radius: 20px !important;
  background: #2e7d32 !important;
  color: #fff !important;
  display: inline-block !important;
}
.hb-form-label {
  font-size: 17px !important;
  font-weight: bold !important;
  color: #1a1a1a !important;
  margin-bottom: 12px !important;
  display: block !important;
  line-height: 1.5 !important;
  letter-spacing: 0.3px !important;
}
.hb-form-label span {
  color: #fff !important;
  font-size: 12px !important;
  background: #c0392b !important;
  padding: 3px 8px !important;
  border-radius: 4px !important;
  margin-left: 8px !important;
  vertical-align: middle !important;
}
.hb-select-wrap select {
  width: 100% !important;
  padding: 13px 14px !important;
  font-size: 15px !important;
  border: 1.5px solid #ddd !important;
  border-radius: 10px !important;
  background: #fdfbf9 !important;
  -webkit-appearance: none !important;
  appearance: none !important;
  display: block !important;
  box-sizing: border-box !important;
}
.hb-arrow { text-align: center !important; margin: 8px 0 !important; }
.hb-submit-btn {
  width: 100% !important;
  padding: 15px !important;
  font-size: 16px !important;
  font-weight: bold !important;
  background: #c0392b !important;
  color: #fff !important;
  border: none !important;
  border-radius: 10px !important;
  display: block !important;
  text-align: center !important;
  cursor: pointer !important;
  box-sizing: border-box !important;
}
.hb-form-note {
  font-size: 11px !important;
  color: #999 !important;
  text-align: center !important;
  margin-top: 10px !important;
  display: block !important;
}
.hb-trust-row {
  padding: 4px 12px !important;
  display: block !important;
}
.hb-trust-row img {
  width: 100% !important;
  display: block !important;
  border-radius: 8px !important;
  margin-bottom: 4px !important;
}
.hb-section { padding: 16px 12px 0 !important; }
.hb-section-heading {
  font-size: 15px !important;
  font-weight: bold !important;
  color: #4a3728 !important;
  margin-bottom: 10px !important;
  padding-left: 10px !important;
  border-left: 3px solid #c0392b !important;
  display: block !important;
}
.hb-section img { width: 100% !important; border-radius: 8px !important; display: block !important; }
.hb-line-banner {
  margin: 16px 12px 0 !important;
  background: linear-gradient(135deg, #06c755, #04a844) !important;
  border-radius: 14px !important;
  padding: 14px 18px !important;
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  text-decoration: none !important;
}
.hb-line-icon {
  width: 42px !important; height: 42px !important;
  background: #fff !important;
  border-radius: 50% !important;
  display: flex !important; align-items: center !important; justify-content: center !important;
  font-size: 24px !important;
  flex-shrink: 0 !important;
}
.hb-line-main { font-size: 14px !important; font-weight: bold !important; color: #fff !important; }
.hb-line-sub { font-size: 11px !important; color: rgba(255,255,255,0.8) !important; margin-top: 2px !important; }
.hb-voice-card {
  background: #fff !important;
  border-radius: 10px !important;
  margin-bottom: 8px !important;
  overflow: hidden !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06) !important;
}
.hb-voice-card img { width: 100% !important; display: block !important; }
.hb-bottom-form {
  margin: 16px 12px 0 !important;
  background: #fff !important;
  border-radius: 16px !important;
  padding: 18px 16px !important;
  box-shadow: 0 4px 20px rgba(74,55,40,0.10) !important;
}
.hb-bottom-title {
  text-align: center !important;
  font-size: 13px !important;
  font-weight: bold !important;
  color: #4a3728 !important;
  margin-bottom: 14px !important;
  padding-bottom: 10px !important;
  border-bottom: 1px solid #ece8e2 !important;
  display: block !important;
}
</style>


<!--シンシア-->
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KKZVB2XK');</script>
<!-- End Google Tag Manager -->


<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KKZVB2XK');</script>
<!-- End Google Tag Manager -->


<!--Zacksトラッキング-->
<script id="zafscript_landing" type="text/javascript" src="//get.mobu.jp.eimg.jp/js/conv/lp.min.js" async></script>

<!--ギークハッシュトラッキング-->
<script>
(function acsKeep(){
var PK = "p";
var IMK = "im";
var LKEYS = {cid : ["cid", "CL_", "ACT_"], gclid : ["plid", "PL_", "APT_"]};
var DKEYS = ["gclid", "fbclid", "yclid", "ttclid", "msi"];
var PDIR = "./";
var durl = "https://asp.geekhash.jp/direct.php";
function saveCookies(data){ var p = data[PK]; var out = Object.keys(LKEYS).reduce(function(ret, k){ if(k in data && data[k]) ret[k] = data[k]; return ret; }, {}); if(!p || !Object.keys(out).length) return;
var purl = PDIR + "lptag.php?p=" + p; Object.keys(out).forEach(function(k){ purl += "&" + LKEYS[k][0] + "=" + out[k]; localStorage.setItem(LKEYS[k][1] + p, out[k]); });
var xhr = new XMLHttpRequest(); var args = "; expires=" + new Date(new Date().getTime() + 63072000000).toUTCString() + "; path=/; SameSite=None; Secure"; xhr.open("GET", purl);
xhr.onloadend = function(){ if(xhr.status === 200) return; Object.keys(out).forEach(function(k){ document.cookie = LKEYS[k][1] + p + "=" + decodeURIComponent(out[k]) + args; if(LKEYS[k][2]) document.cookie = LKEYS[k][2] + p + "=js" + args; }); }; xhr.send(); }
var data = location.search.substring(1).split("&").reduce(function(ret, s){ var kv = s.split("="); if(kv[1]) ret[kv[0]] = kv[1]; return ret; }, {}); if(!(IMK in data)){ saveCookies(data); return; }
durl += "?im=" + data[IMK] + "&navi=" + performance.navigation.type; DKEYS.forEach(function(k){ if(!(k in data)) return; durl += "&" + k + "=" + data[k]; });
var xhr = new XMLHttpRequest(); xhr.open("GET", durl); function merge(a, b){ return Object.keys(LKEYS).reduce(function(ret, k){ if(k in b && !(k in a)) ret[k] = b[k]; return ret; }, a); }
xhr.onloadend = function(){ if(xhr.status !== 200) return; try{ var xhr_data = JSON.parse(xhr.responseText); if(PK != "p"){ xhr_data[PK] = xhr_data["p"]; } saveCookies(merge(xhr_data, data)); }catch(_){ } }; xhr.send(); })();
</script>

<!--afb-->
<script>
if (!window.afblpcvLpConf) {
  window.afblpcvLpConf = [];
}
window.afblpcvLpConf.push({
  siteId: "aca69051"
});
window.afblpcvLinkConf = {
  siteId: "aca69051",
  mode: "all"
};
</script>
<script src="https://t.afi-b.com/jslib/lpcv.js?cid=aca69051&pid=614192A" async="async"></script>
<!--afb終わり-->


<!-- ここから Crib Notesユニバーサルタグのコード -->

<script>(function (b, f, d, a, c) {var e = b.createElement(f);e.src = c + "/" + a + "/atm.js";e.id = d;e.async = true;b.getElementsByTagName(f)[0].parentElement.appendChild(e)})(document,"script","__cribnotesTagMgrCmd","0af9ca19-a995-4e07-b9a8-1786ce4c32a3","https://tag.cribnotes.jp/container_manager");</script>

<!-- ここまで Crib Notesユニバーサルタグのコード -->


<!--poiful-->
<script>
(function(){
var uqid = "23eb1696991dbb77";
var gid  = "39";
var a=document.createElement("script");
a.dataset.uqid=uqid;a.dataset.gid=gid;a.id="afadfpc-23eb1696991dbb77gid39-"+Date.now();
a.src="//ac.pointfun.jp/fpc/cookie_js.php?scriptId="+encodeURIComponent(a.id);
document.head.appendChild(a);
})();
</script>
<!--poiful終わり-->


<!--hapitas-->
<script>
window.acs_cbs = window.acs_cbs || [];
(function acsKeep(){
var PK = "p";
var IMK = "im";
var LKEYS = {cid : ["cid", "CL_", "ACT_"], gclid : ["plid", "PL_", "APT_"]};
var DKEYS = ["gclid", "msclkid", "fbclid", "yclid", "ttclid", "ldtag_cl", "ss", "msi"];
var PDIR = "./";
var durl = "https://ozasp.jp/direct.php";
function saveCookies(data){ var p = data[PK]; var out = Object.keys(LKEYS).reduce(function(ret, k){ if(k in data && data[k]) ret[k] = data[k]; return ret; }, {}); if(!p || !Object.keys(out).length) return;
var purl = PDIR + "lptag.php?p=" + p; Object.keys(out).forEach(function(k){ purl += "&" + LKEYS[k][0] + "=" + out[k]; localStorage.setItem(LKEYS[k][1] + p, out[k]); });
var xhr = new XMLHttpRequest(); var args = "; expires=" + new Date(new Date().getTime() + 63072000000).toUTCString() + "; path=/; SameSite=None; Secure"; xhr.open("GET", purl);
xhr.onloadend = function(){ if(xhr.status === 200 && xhr.response === ""){ window.acs_cbs.forEach(function(cb){ cb(); }); return; } Object.keys(out).forEach(function(k){ document.cookie = LKEYS[k][1] + p + "=" + decodeURIComponent(out[k]) + args; if(LKEYS[k][2]) document.cookie = LKEYS[k][2] + p + "=js" + args; }); window.acs_cbs.forEach(function(cb){ cb(); }); }; xhr.send(); }
var data = location.search.substring(1).split("&").reduce(function(ret, s){ var kv = s.split("="); if(kv[1]) ret[kv[0]] = kv[1]; return ret; }, {}); if(!(IMK in data)){ saveCookies(data); return; }
durl += "?im=" + data[IMK] + "&navi=" + performance.navigation.type; DKEYS.forEach(function(k){ if(!(k in data)) return; durl += "&" + k + "=" + data[k]; });
var xhr = new XMLHttpRequest(); xhr.open("GET", durl); function merge(a, b){ return Object.keys(LKEYS).reduce(function(ret, k){ if(k in b && !(k in a)) ret[k] = b[k]; return ret; }, a); }
xhr.onloadend = function(){ if(xhr.status !== 200) return; try{ var xhr_data = JSON.parse(xhr.responseText); if(PK != "p"){ xhr_data[PK] = xhr_data["p"]; } saveCookies(merge(xhr_data, data)); }catch(_){ } }; xhr.send(); })();
</script>
<!--hapitas終わり-->


<!-- LINE Tag Base Code -->
<!-- Do Not Modify -->
<script>
(function(g,d,o){
  g._ltq=g._ltq||[];g._lt=g._lt||function(){g._ltq.push(arguments)};
  var h=location.protocol==='https:'?'https://d.line-scdn.net':'http://d.line-cdn.net';
  var s=d.createElement('script');s.async=1;
  s.src=o||h+'/n/line_tag/public/release/v1/lt.js';
  var t=d.getElementsByTagName('script')[0];t.parentNode.insertBefore(s,t);
    })(window, document);
_lt('init', {
  customerType: 'lap',
  tagId: '9464cb4f-0e99-4f43-b9ff-2251f21d106d'
});
_lt('send', 'pv', ['9464cb4f-0e99-4f43-b9ff-2251f21d106d']);
</script>
<noscript>
  <img height="1" width="1" style="display:none"
       src="https://tr.line.me/tag.gif?c_t=lap&t_id=9464cb4f-0e99-4f43-b9ff-2251f21d106d&e=pv&noscript=1" />
</noscript>
<!-- End LINE Tag Base Code -->

<!-- start Promolayer JS code-->
 <script type="module" src="https://modules.promolayer.io/index.js" data-pluid="2eb69912-8328-424c-8da8-ab728a6f74dc" data-workspace="2rrLemvF1OECk9ZkzzyI" crossorigin async></script>
<!-- end Promolayer JS code-->

</head>

<body id="planPage">


<!--excrie-->
<script>
(function(){
var uqid = "S10dX6bed35e454X";
var gid  = "621";

var a=document.createElement("script");
a.dataset.uqid=uqid;a.dataset.gid=gid;a.id="afadfpc-"+uqid+"-"+Date.now();
a.src="//ac.dmtag.jp/fpc/cookie_js.php?scriptId="+encodeURIComponent(a.id);
document.body.appendChild(a);
})();
</script>

<!--End excrie-->



<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KKZVB2XK"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->



<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KKZVB2XK"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<!--LPタグ-->
<script language='javascript' src='https://ad.fe-ts.jp/ad/js/lpjs.js'></script>


<?php require_once TEMP_DIR.'/sp_header.php'; ?>

<style>

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
  background: #faf8f5;
  max-width: 480px;
  margin: 0 auto;
  color: #222;
}

/* ヘッダー */
.header {
  background: #fff;
  padding: 12px 16px;
  border-bottom: 1px solid #ece8e2;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.header-logo {
  font-size: 11px;
  color: #999;
  line-height: 1.5;
}
.header-logo strong {
  display: block;
  font-size: 17px;
  color: #4a3728;
  letter-spacing: 0.5px;
}
.header-tel {
  font-size: 11px;
  color: #777;
  text-align: right;
  line-height: 1.5;
}
.header-tel strong {
  display: block;
  font-size: 14px;
  color: #4a3728;
}

/* ヒーロー */
.hero {
  position: relative;
  overflow: hidden;
  height: 260px;
}
.hero-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center center;
  display: block;
}
.hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(0,0,0,0.1) 0%,
    rgba(20,10,5,0.45) 55%,
    rgba(20,10,5,0.75) 100%
  );
}
.hero-content {
  position: absolute;
  bottom: 28px;
  left: 20px;
  right: 20px;
  z-index: 2;
}
.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: #ffcc00;
  color: #333;
  font-size: 15px;
  font-weight: bold;
  letter-spacing: 1px;
  padding: 7px 18px;
  border-radius: 30px;
  margin-bottom: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.hero-title {
  font-size: 22px;
  font-weight: bold;
  color: #fff;
  line-height: 1.55;
  margin-bottom: 8px;
  text-shadow: 0 1px 6px rgba(0,0,0,0.4);
}
.hero-title em {
  font-style: normal;
  color: #ffcc80;
}
.hero-desc {
  font-size: 12px;
  color: rgba(255,255,255,0.85);
  line-height: 1.8;
  text-shadow: 0 1px 4px rgba(0,0,0,0.4);
}

/* 丸みある下端 */
.hero::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 28px;
  background: #faf8f5;
  border-radius: 28px 28px 0 0;
  z-index: 3;
}

/* フォームカード */
.form-card {
  background: #fff;
  margin: 0 14px;
  border-radius: 16px;
  padding: 20px 18px;
  box-shadow: 0 6px 24px rgba(74,55,40,0.12);
  position: relative;
  z-index: 4;
  margin-top: -14px;
}
.form-badge {
  display: flex;
  gap: 6px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}
.badge {
  font-size: 13px;
  font-weight: bold;
  padding: 6px 12px;
  border-radius: 20px;
}
.badge-free { background: #2e7d32; color: #fff; }
.badge-safe { background: #2e7d32; color: #fff; }
.badge-no-spam { background: #2e7d32; color: #fff; }

.form-label {
  font-size: 15px;
  font-weight: bold;
  color: #333;
  margin-bottom: 12px;
  line-height: 1.5;
}
.form-label span {
  color: #c0392b;
  font-size: 11px;
  background: #fdecea;
  padding: 2px 6px;
  border-radius: 4px;
  margin-left: 6px;
  vertical-align: middle;
}

.select-wrap {
  position: relative;
  margin-bottom: 12px;
}
.select-wrap::after {
  content: '▼';
  position: absolute;
  right: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 10px;
  color: #c06030;
  pointer-events: none;
}
.select-wrap select {
  width: 100%;
  padding: 13px 40px 13px 14px;
  font-size: 15px;
  border: 1.5px solid #ddd;
  border-radius: 10px;
  color: #333;
  background: #fdfbf9;
  appearance: none;
  outline: none;
}
.select-wrap select:focus {
  border-color: #c06030;
}

.submit-btn {
  width: 100%;
  padding: 15px;
  font-size: 16px;
  font-weight: bold;
  background: #c0392b;
  color: #fff;
  border: none;
  border-radius: 10px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(192,57,43,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.submit-btn::after { content: '→'; font-size: 16px; }

.form-note {
  font-size: 11px;
  color: #999;
  text-align: center;
  margin-top: 10px;
  line-height: 1.6;
}

/* 信頼ポイント */
.trust-row {
  display: flex;
  gap: 0;
  padding: 20px 14px 0;
  background: transparent;
}
.trust-item {
  flex: 1;
  background: linear-gradient(160deg, #fffbe6 0%, #ffd54f 100%);
  padding: 16px 6px;
  text-align: center;
  position: relative;
  border-top: 4px solid #f9a825;
}
.trust-item:first-child { border-radius: 12px 0 0 12px; }
.trust-item:last-child { border-radius: 0 12px 12px 0; }
.trust-item + .trust-item::before {
  content: '';
  position: absolute;
  left: 0; top: 16px; bottom: 16px;
  width: 1px;
  background: #f9a825;
}
.trust-row {
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  border-radius: 12px;
  overflow: hidden;
}
.trust-icon {
  margin-bottom: 8px;
  display: flex;
  justify-content: center;
  align-items: center;
}
.trust-label {
  font-size: 13px;
  font-weight: bold;
  color: #4a3000;
  line-height: 1.4;
  display: block;
}
.trust-sub {
  font-size: 10px;
  color: #7a6000;
  margin-top: 4px;
  line-height: 1.5;
  display: block;
}

/* セクション */
.section { padding: 28px 14px 0; }
.section-heading {
  font-size: 16px;
  font-weight: bold;
  color: #4a3728;
  margin-bottom: 14px;
  padding-left: 10px;
  border-left: 3px solid #c0392b;
}

.img-block {
  background: #f0ebe4;
  border-radius: 12px;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #b09080;
  font-size: 13px;
}

/* LINE */
.line-banner {
  margin: 20px 14px 0;
  background: linear-gradient(135deg, #06c755, #04a844);
  border-radius: 14px;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  box-shadow: 0 4px 16px rgba(6,199,85,0.2);
  text-decoration: none;
}
.line-icon {
  width: 46px; height: 46px;
  background: #fff;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 26px;
  flex-shrink: 0;
}
.line-text .line-main { font-size: 14px; font-weight: bold; color: #fff; }
.line-text .line-sub { font-size: 12px; color: rgba(255,255,255,0.8); margin-top: 3px; }

/* お客様の声 */
.voice-card {
  background: #fff;
  border-radius: 12px;
  margin-bottom: 10px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.voice-img {
  background: linear-gradient(135deg, #fdf3e8, #f5dfc0);
  height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #c09060;
  font-size: 13px;
  font-weight: bold;
}

/* 下部フォーム */
.bottom-form {
  margin: 28px 14px 0;
  background: #fff;
  border-radius: 16px;
  padding: 20px 18px;
  box-shadow: 0 4px 20px rgba(74,55,40,0.10);
}
.bottom-form-title {
  text-align: center;
  font-size: 14px;
  font-weight: bold;
  color: #4a3728;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #ece8e2;
}

.footer-space { height: 36px; }

</style>

<!-- ヒーロー画像 -->
<img class="hb-hero-img" src="/vendors/images/hero_main.png" alt="地域の工務店にまとめて資料請求できます">

<!-- メインフォームカード -->
<div class="hb-form-card">
  <div class="hb-badge-row">
    <span class="hb-badge">完全無料</span>
    <span class="hb-badge">審査済み工務店のみ</span>
    <span class="hb-badge">しつこい営業なし</span>
  </div>
  <div class="hb-form-label">
    まずは建築予定地・希望地の都道府県を選択
    <span>必須</span>
  </div>
  <div class="hb-select-wrap">
    <form action="" method="get" enctype="multipart/form-data" class="chokusetsu">
      <?=$vClass->getProvinceDDL("province", $pid)?>
      <?=$vClass->getHiddens("vid", $vid)?>
      <div class="hb-arrow">
        <svg viewBox="0 0 40 24" width="36" height="22" xmlns="http://www.w3.org/2000/svg">
          <polygon points="0,0 40,0 20,24" fill="#c0392b" opacity="0.85"/>
        </svg>
      </div>
      <button type="submit" name="send_quotes" value="ok" class="hb-submit-btn">続いて市区町村を選ぶ</button>
    </form>
  </div>
  <div class="hb-form-note">入力時間の目安：約3分 ／ 費用は一切かかりません</div>
</div>



<!-- anshin画像 -->
<div class="hb-section">
  <div class="hb-section-heading">安心して利用できる理由</div>
  <img src="/vendors/images/anshin3.png" alt="安心して利用できる理由">
</div>

<!-- LINE -->
<a class="hb-line-banner" href="https://lin.ee/JPBDDLi">
  <div class="hb-line-icon">💬</div>
  <div>
    <div class="hb-line-main">LINEでも相談できます</div>
    <div class="hb-line-sub">家づくりの疑問・不安、気軽に聞けます →</div>
  </div>
</a>

<!-- お客様の声 -->
<div class="hb-section">
  <div class="hb-section-heading">実際に資料請求した方の声</div>
  <div class="hb-voice-card"><img src="/vendors/images/voice_nagatoro.png" alt="お客様の声"></div>
  <div class="hb-voice-card"><img src="/vendors/images/voice_funabashi.png" alt="お客様の声"></div>
  <div class="hb-voice-card"><img src="/vendors/images/voice_tateyama.png" alt="お客様の声"></div>
</div>

<!-- 下部フォーム -->
<div class="hb-bottom-form">
  <div class="hb-bottom-title">📋 もう一度、資料請求はこちらから</div>
  <div class="hb-select-wrap">
    <form action="" method="get" enctype="multipart/form-data" class="chokusetsu">
      <?=$vClass->getProvinceDDL("province", $pid)?>
      <?=$vClass->getHiddens("vid", $vid)?>
      <button type="submit" name="send_quotes" value="ok" class="hb-submit-btn" style="margin-top:12px;">続いて市区町村を選ぶ</button>
    </form>
  </div>
</div>

<div style="height:36px;"></div>

</div>

<?php require_once TEMP_DIR.'/sp_footer.php'; ?>

<script type="text/javascript" src="js/sp.js"></script>




<!-- レントラックス　ASP ITP対応トラッキングタグの設置 -->
<script type="text/javascript">
(function(callback){
var script = document.createElement("script");
script.type = "text/javascript";
script.src = "https://www.rentracks.jp/js/itp/rt.track.js?t=" + (new Date()).getTime();
if ( script.readyState ) {
    script.onreadystatechange = function() {
        if ( script.readyState === "loaded" || script.readyState === "complete" ) {
            script.onreadystatechange = null;
            callback();
        }
    };
} else {
    script.onload = function() {
        callback();
    };
}
document.getElementsByTagName("head")[0].appendChild(script);
}(function(){}));
</script>

<!-- ネットマイル ITP対応トラッキングタグの設置 -->
<script type="text/javascript">
function _fdaitptag(u, f) {
    var s = document.createElement('script');
    s.type = 'text/javascript';
    s.src = u;
    if (s.readyState) {
        s.onreadystatechange = function() {f();}
    } else {
        s.onload = function() {f();}
    }
    document.body.appendChild(s);
}
_fdaitptag('//www.adfactory.io/adtr/resources/fdack2.js', function() {
    fdacalling();
})
</script>

<?php require_once TEMP_DIR.'/sp_footer_scripts.php'; ?>



	
	<!--crossoverr-->
	<script>
(function acsKeep(){
var PK = "p";
var IMK = "im";
var LKEYS = {cid : ["cid", "CL_", "ACT_"]};
var DKEYS = [];
var PDIR = "./";
var durl = "https://s16.aspservice.jp/co/direct.php";
function saveCookies(data){ var p = data[PK]; var out = Object.keys(LKEYS).reduce(function(ret, k){ if(k in data && data[k]) ret[k] = data[k]; return ret; }, {}); if(!p || !Object.keys(out).length) return;
var purl = PDIR + "lptag.php?p=" + p; Object.keys(out).forEach(function(k){ purl += "&" + LKEYS[k][0] + "=" + out[k]; localStorage.setItem(LKEYS[k][1] + p, out[k]); });
var xhr = new XMLHttpRequest(); var args = "; expires=" + new Date(new Date().getTime() + 63072000000).toUTCString() + "; path=/; SameSite=None; Secure"; xhr.open("GET", purl);
xhr.onloadend = function(){ if(xhr.status === 200) return; Object.keys(out).forEach(function(k){ document.cookie = LKEYS[k][1] + p + "=" + decodeURIComponent(out[k]) + args; if(LKEYS[k][2]) document.cookie = LKEYS[k][2] + p + "=js" + args; }); }; xhr.send(); }
var data = location.search.substring(1).split("&").reduce(function(ret, s){ var kv = s.split("="); if(kv[1]) ret[kv[0]] = kv[1]; return ret; }, {}); if(!(IMK in data)){ saveCookies(data); return; }
durl += "?im=" + data[IMK] + "&navi=" + performance.navigation.type; DKEYS.forEach(function(k){ if(!(k in data)) return; durl += "&" + k + "=" + data[k]; });
var xhr = new XMLHttpRequest(); xhr.open("GET", durl); function merge(a, b){ return Object.keys(LKEYS).reduce(function(ret, k){ if(k in b && !(k in a)) ret[k] = b[k]; return ret; }, a); }
xhr.onloadend = function(){ if(xhr.status !== 200) return; try{ var xhr_data = JSON.parse(xhr.responseText); if(PK != "p"){ xhr_data[PK] = xhr_data["p"]; } saveCookies(merge(xhr_data, data)); }catch(_){ } }; xhr.send(); })();
</script>
	<!--crossoverr終了-->
	
	
	<!--DENOO-->
<script>
document.addEventListener('DOMContentLoaded', function()
{
  var _CTIDV  = "chrjwj5kemom";
   var _DATA = {"args":"<?php echo $_GET['subsId']; ?>"};
  var sc = document.createElement("script");
  sc.id = _CTIDV ; sc.async = true;
  sc.src = "https://platinum.denoo.co.jp/tag.php?c=" + _CTIDV  + "&url=" + encodeURIComponent(location.href) + "&ref=" + encodeURIComponent(document.referrer) +"&data=" + encodeURIComponent(JSON.stringify(_DATA));
  document.body.appendChild(sc);
});
</script>
<!--終了-->

<!--zucks20250410-->
<script src='https://k.z.mobu.jp/ad/js/lpjs.js'></script>
<!--終了-->


<!--asplayタグ-->
<script>
window.acs_cbs = window.acs_cbs || [];
(function acsKeep(){
var PK = "p";
var IMK = "im";
var LKEYS = {cid : ["cid", "CL_", "ACT_"], gclid : ["plid", "PL_", "APT_"]};
var DKEYS = ["gclid", "msclkid", "fbclid", "yclid", "ttclid", "ldtag_cl", "msi"];
var PDIR = "./";
var durl = "https://asplay.biz/direct.php";
function saveCookies(data){ var p = data[PK]; var out = Object.keys(LKEYS).reduce(function(ret, k){ if(k in data && data[k]) ret[k] = data[k]; return ret; }, {}); if(!p || !Object.keys(out).length) return;
var purl = PDIR + "lptag.php?p=" + p; Object.keys(out).forEach(function(k){ purl += "&" + LKEYS[k][0] + "=" + out[k]; localStorage.setItem(LKEYS[k][1] + p, out[k]); });
var xhr = new XMLHttpRequest(); var args = "; expires=" + new Date(new Date().getTime() + 63072000000).toUTCString() + "; path=/; SameSite=None; Secure"; xhr.open("GET", purl);
xhr.onloadend = function(){ if(xhr.status === 200 && xhr.response === ""){ window.acs_cbs.forEach(function(cb){ cb(); }); return; } Object.keys(out).forEach(function(k){ document.cookie = LKEYS[k][1] + p + "=" + decodeURIComponent(out[k]) + args; if(LKEYS[k][2]) document.cookie = LKEYS[k][2] + p + "=js" + args; }); window.acs_cbs.forEach(function(cb){ cb(); }); }; xhr.send(); }
var data = location.search.substring(1).split("&").reduce(function(ret, s){ var kv = s.split("="); if(kv[1]) ret[kv[0]] = kv[1]; return ret; }, {}); if(!(IMK in data)){ saveCookies(data); return; }
durl += "?im=" + data[IMK] + "&navi=" + performance.navigation.type; DKEYS.forEach(function(k){ if(!(k in data)) return; durl += "&" + k + "=" + data[k]; });
var xhr = new XMLHttpRequest(); xhr.open("GET", durl); function merge(a, b){ return Object.keys(LKEYS).reduce(function(ret, k){ if(k in b && !(k in a)) ret[k] = b[k]; return ret; }, a); }
xhr.onloadend = function(){ if(xhr.status !== 200) return; try{ var xhr_data = JSON.parse(xhr.responseText); if(PK != "p"){ xhr_data[PK] = xhr_data["p"]; } saveCookies(merge(xhr_data, data)); }catch(_){ } }; xhr.send(); })();
</script>
<!--asplay終了-->	

<!--piaraタグ-->
<script>
    (function() {
    var uqid = "cf26S373Sef9aai9";
    var gid  = 1683;
    var a=document.createElement("script");
    a.dataset.uqid=uqid;a.dataset.gid=gid;a.id="afadfpc-cf26S373Sef9aai9gid1683-"+Date.now();
    a.src="//ad.resultplus2.jp/fpc/cookie_js.php?scriptId="+encodeURIComponent(a.id);
    document.head.appendChild(a);
    })();
    </script>
    <!--piara終了-->



<!--チャットボット（自社版）-->
<link rel="stylesheet" href="/js/chatbot/chatbot.css">
<script src="/js/chatbot/chatbot.js"></script>
<!--チャットボット終了-->

</body>
</html>
