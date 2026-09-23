<?php
include_once '../includes/config.php';

$pageTitle          = '一括資料請求｜ハウジングバザール';
$metaDescription    = $pageTitle;
$metaKeywords       = $pageTitle;
?>

<!DOCTYPE html>
<html lang="ja">
<head>

<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','GTM-KKZVB2XK');</script>
<!-- End Google Tag Manager -->

<?php require_once TEMP_DIR . '/sp_head.php'; ?>
<style>
html, body {
    height: 100%;
}
body {
    margin: 0;
    display: flex;
    flex-direction: column;
    background-image: none;
    padding-top: unset;
}
#header {
    box-sizing: border-box;
    width: 100%;
    height: 4em;
    border: solid 1px #aaa;
    padding: 5px;
    display: flex;
    position: unset !important;
    background: -webkit-gradient(linear, left top, left bottom, from(rgb(254,255,251)), to(rgb(215,255,133)));
    background: -webkit-linear-gradient(top, rgb(254,255,251) 0%, rgb(215,255,133) 100%);
    background: linear-gradient(top, rgb(254,255,251) 0%, rgb(215,255,133) 100%);
}
#header p {
    margin: auto;
}
#content {
    display: flex;
    flex: 1;
}
main{
    flex: 1;
    padding: 5px;
}
</style>
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
<script>
_lt('send', 'cv', {
  type: 'Conversion'
},['9464cb4f-0e99-4f43-b9ff-2251f21d106d']);
</script>

<!--Zacks-->
<script id="zafscript" type="text/javascript" src="//get.mobu.jp.eimg.jp/js/conv/conv.min.js?actcd=26205ac73996a07df258b24e78ab637d&opt=<?= $_GET['subsId'] ?>"></script>
<!--Zacks end-->

<!--ギークハッシュ-->
<script>
(function acsTrack(){
var PV = "ph5hwahw0ooo";
var KEYS = {cid : ["CL_", "ACT_", "cid_auth_get_type"], plid : ["PL_", "APT_", "plid_auth_get_type"]};
var turl = "https://asp.geekhash.jp/track.php?p=" + PV;
var cks = document.cookie.split("; ").reduce(function(ret, s){ var kv = s.split("="); if(kv[0] && kv[1]) ret[kv[0]] = kv[1]; return ret; }, []);
turl = Object.keys(KEYS).reduce(function(url, k){ var vk = KEYS[k][0] + PV; var tk = KEYS[k][1] + PV; var v = "", t = ""; if(cks[vk]){ v = cks[vk]; if(cks[tk]) t = cks[tk]; }else if(localStorage.getItem(vk)){ v = localStorage.getItem(vk); t = "ls"; } if(v) url += "&" + k + "=" + v; if(t) url += "&" + KEYS[k][2] + "=" + t; return url; }, turl);
var xhr = new XMLHttpRequest(); xhr.open("GET", turl); xhr.send(); })();
</script>

<!-- -->
<script>
(function acsTrack(){
var PV = "phbj716cn0ki";
var _ARGSV = '<?= $_GET['subsId'] ?>';
var KEYS = {cid : ["CL_", "ACT_", "cid_auth_get_type"], plid : ["PL_", "APT_", "plid_auth_get_type"]};
var turl = "https://virgin-ad.com/track.php?p=" + PV + "&args=" + _ARGSV;
var cks = document.cookie.split("; ").reduce(function(ret, s){ var kv = s.split("="); if(kv[0] && kv[1]) ret[kv[0]] = kv[1]; return ret; }, []);
turl = Object.keys(KEYS).reduce(function(url, k){ var vk = KEYS[k][0] + PV; var tk = KEYS[k][1] + PV; var v = "", t = ""; if(cks[vk]){ v = cks[vk]; if(cks[tk]) t = cks[tk]; }else if(localStorage.getItem(vk)){ v = localStorage.getItem(vk); t = "ls"; } if(v) url += "&" + k + "=" + v; if(t) url += "&" + KEYS[k][2] + "=" + t; return url; }, turl);
var xhr = new XMLHttpRequest(); xhr.open("GET", turl); xhr.send(); })();
</script>

<!-- ここから Crib Notes コンバージョンタグのコード -->
<script src="https://tag.cribnotes.jp/support/ntm.js"></script>
<script>
  window.crib.setItem('transaction_id', '<?php echo $_GET['subsId']; ?>');
  window.crib.setItem('thanks_id', '410838');
</script>

<script>(function (b, f, d, a, c) {var e = b.createElement(f);e.src = c + "/" + a + "/atm.js";e.id = d;e.async = true;b.getElementsByTagName(f)[0].parentElement.appendChild(e)})(document,"script","__cribnotesTagMgrCmd","0af9ca19-a995-4e07-b9a8-1786ce4c32a3","https://tag.cribnotes.jp/container_manager");</script>
<!-- ここまで Crib Notes コンバージョンタグのコード -->

<!-- ここから Dairin コンバージョンタグのコード -->
<script>
    (function(w,f){w[f]=w[f]||function(){w[f].q=w[f].q||[];w[f].q.push(arguments);};})(window,'dairin');
    dairin("complete", {
        customer_uid:"<?= $_GET['subsId'] ?>",
        // customer_uid:'user@example.com',
        // sales_amount:0,
        // event_id:'',
        campaign_code:'lj32Lh7Jdc'
    });
</script>
<!-- ここまで Dairin コンバージョンタグのコード -->

<!--poiful-->
<script>
(function(){
var uqid   = "23eb1696991dbb77";
var gid    = "39";
var uid   = "<?= $_GET['subsId'] ?>";
var uid2   = "";
var af   = "";
var pid    = "";
var amount = "";
var a=document.createElement("script");
a.src="//ac.pointfun.jp/ac/action_js.php";
a.id="afadaction-"+Date.now();
a.addEventListener("load",function(){(new fpcAction(a.id)).groupAction(gid, af, [uid, uid2], pid, amount, uqid)});
document.head.appendChild(a)})();
</script>





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
<script>
_lt('send', 'cv', {
  type: 'Conversion'
},['9464cb4f-0e99-4f43-b9ff-2251f21d106d']);
</script>

</head>

<body id="paperPage">

<!--excrie-->
<script>
(function(){
var uqid   = "S10dX6bed35e454X";
var gid    = "621";
var uid   = "<?= $_GET['subsId'] ?>";
var uid2   = "";
var af   = "";
var pid    = "";
var amount = "";

var a=document.createElement("script");
a.src="//ac.dmtag.jp/ac/action_js.php";
a.id="afadaction-"+Date.now();
a.addEventListener("load",function(){(new fpcAction(a.id)).groupAction(gid, af, [uid, uid2], pid, amount, uqid)});
document.body.appendChild(a)})();
</script>
<!--End excrie-->

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KKZVB2XK"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<?php
// 2019.09.09 アクセス解析ツール設置
require_once $_SERVER['DOCUMENT_ROOT'].'/templates/scripts_after_body.html';
?>


<header id="header">
    <p>一括資料請求</p>
</header>

<div id="content" style="font-size:105%;">
  <div style="text-align:center;margin:0 auto;">
    <img src="<?=SSL_URL?>images/staff.png" style="padding:10px 0 10px;" width="70%">

    <main>
      <p class="thanks">
        ハウジングバザール資料請求サービスを<br />ご利用頂きまして、有難うございました。<br />ご登録された入力内容について、<br />ご連絡させて頂く場合がございます。<br />
        <br />
        電話番号はこちらです▶︎<span style="color:#ff3300;font-size:120%;">0436-63-3015</span><br /><br />
        またご不明な点などありましたら、<br />上記の番号でご連絡を受け付けております。
      </p>


      <div style="text-align:center; margin:40px 0 10px 0;">
        <img src="<?=SSL_URL?>vendors/images/ikkatsu_thanks_line.png">
      </div>
      <div style="text-align:center;">
        <a href="https://lin.ee/7tw75mU"><img src="https://scdn.line-apps.com/n/line_add_friends/btn/ja.png" alt="友だち追加" height="36" border="0"></a>
      </div>

    <!--
    <p class="thanks">私たちのサービスをアフィリエイターとして<br />PRして下さる方を募集しています！<br />
    ご紹介頂いた方に、成果報酬をお支払いしています。<br  /><br />ブログやウェブサイトをお持ちの方は、<br />是非ご登録下さい！</p>
    -->

    <!--
    <div style="text-align:center;margin:20px 0 40px 0;">
      <a href="https://omoikanebooks.wixsite.com/bazaraffiliate">
        <img src="<?=SSL_URL?>images/affili_button.png" width="320px">
      </a>
    </div>
    -->

    </main>
  </div>
</div>

<!--サンクスページタグ-->
<script type="text/javascript">
window.ebSettings_2 = {eb_appId: 'ed7bb2c3c5eabd6cff34c4e3e3443cf6',};
</script>
<script src="https://chasy.jp/static/js/ending.min.js"></script>
<script type="text/javascript">
window.ebSettings_2 = {eb_appId: '09657dabaebad73dec81804498296c5e',};
</script>
<script src="https://chasy.jp/static/js/ending.min.js"></script>
<script type="text/javascript">
window.ebSettings_2 = {eb_appId: '7e92e8d4eaba19bc263fef7c99ad353c',};
</script>
<script src="https://chasy.jp/static/js/ending.min.js"></script>

<!--成果タグ-->
<script src='https://ad.fe-ts.jp/ad/js/cv.js'></script>
<script>
   FE_TS_CV.cv('admage_fe_ts_xuid', 'https://ad.fe-ts.jp/ad', 'advertiser=72&ad=119&_buid=<?= $_GET['subsId'] ?>');
</script>
<noscript>
   <img src='https://ad.fe-ts.jp/ad/p/cv?advertiser=72&ad=119&_buid=<?= $_GET['subsId'] ?>' width='1' height='1' />
</noscript>


<!--A8 新トラッキング方式-->
<span id="a8sales"></span>

<?php require_once TEMP_DIR.'/sp_footer.php'; ?>
<?php require_once TEMP_DIR.'/sp_footer_scripts.php'; ?>

<!--A8 新トラッキング方式-->
<script type="text/javascript">
<!--
a8sales({
  "pid": "s00000011766001",
  "order_number": "<?= $_GET['subsId'] ?>", //注文番号
  "currency": "JPY",  //通貨コード、JPY/USD/EURが利用可能
  "items": [
    {
      "code": "a8",   //商品コード
      "price": 1,     //固定値
      "quantity": 1,  //固定値
    },
  ],
  "total_price": 1,   //固定値
});
-->
</script>
<!--A8 新トラッキング方式 終わり-->

<!--A8 microad_blade_track CVタグ-->
<script type="text/javascript" class="microad_blade_track">
//<!--
var microad_blade_jp = microad_blade_jp || { 'params' : new Array(), 'complete_map' : new Object() };
(function() {
var param = {'co_account_id' : '8986', 'group_id' : 'convtrack25610', 'country_id' : '1', 'ver' : '2.1.0'};
microad_blade_jp.params.push(param);

var src = (location.protocol == 'https:')
? 'https://d-track.send.microad.jp/js/blade_track_jp.js' : 'http://d-cache.microad.jp/js/blade_track_jp.js';

var bs = document.createElement('script');
bs.type = 'text/javascript'; bs.async = true;
bs.charset = 'utf-8'; bs.src = src;

var s = document.getElementsByTagName('script')[0];
s.parentNode.insertBefore(bs, s);
})();
//-->
</script>
<!--A8 microad_blade_track CVタグ終わり
-->

<!-- レントラックス　ASP ITP対応トラッキングタの設置 -->
<script type="text/javascript">
(function(){
  function loadScriptRTCV(callback){
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://www.rentracks.jp/js/itp/rt.track.js?t=' + (new Date()).getTime();
    if ( script.readyState ) {
      script.onreadystatechange = function() {
        if ( script.readyState === 'loaded' || script.readyState === 'complete' ) {
          script.onreadystatechange = null;
          callback();
        };
      };
    } else {
      script.onload = function() {
        callback();
      };
    };
    document.getElementsByTagName('head')[0].appendChild(script);
  }

  loadScriptRTCV(function(){
    _rt.sid = 3340;
    _rt.pid = 5052;
    _rt.price = 0;
    _rt.reward = -1;
    _rt.cname = '';
    _rt.ctel = '';
    _rt.cemail = '';
    _rt.cinfo = '<?= $_GET['subsId'] ?>';
    rt_tracktag();
  });
}(function(){}));
</script>
<!-- レントラックス　ASP ITP対応トラッキングタの設置 終わり -->

<!-- ネットマイル ITP対応トラッキングタの設置 -->
<script type="text/javascript">
function _fdacvtag(u, f) {
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
_fdacvtag('//www.adfactory.io/adtr/resources/fdacv2.js', function() {
  _fdacv.rid = '<?= $_GET['subsId'] ?>';
  _fdacv.cid = '31327';
  fdacvtag();
})
</script>
<!-- ネットマイル ITP対応トラッキングタの設置 終わり -->

<!--ゴンドラ CVタグ 新タグと旧タグを併記する-->
<img src="//cnt.threewave.jp/TW/Controller/ActionCountCmd?t=1000029878&u=<?= $_GET['subsId'] ?>" width="0" height="0">
<img src="//cdsjp2.veinteractive.com/DataReceiverService.asmx/Pixel?journeycode=2926FAD4-9417-4592-A61F-7F1354173B00"
width="1" height="1"/>
<!--ゴンドラ CVタグ終わり-->

<!--AFRo トラッキング-->
<script src="https://www.cross-a.net/act/afrotk.js?adid=7742&rn=1&u1=<?= $_GET['subsId'] ?>"></script>
<!--AFRo トラッキング 終わり-->

<!--afb トラッキング-->
<script>
if (!window.afblpcvCvConf) {
  window.afblpcvCvConf = [];
}
window.afblpcvCvConf.push({
  siteId: "aca69051",
  commitData: {
    pid: "614192A",
    u: "<?= $_GET['subsId'] ?>"
  }
});
</script>
<script src="https://t.afi-b.com/jslib/lpcv.js?cid=aca69051&pid=614192A" async="async"></script>
<!--afb トラッキング 終わり-->



<!--crossoverr-->
<script>
(function acsTrack(){
var PV = "phcgl1vamoyn";
var _ARGSV = "<?= $_GET['subsId'] ?>";
var KEYS = {cid : ["CL_", "ACT_", "cid_auth_get_type"]};
var turl = "https://s16.aspservice.jp/co/track.php?p=" + PV + "&args=" + _ARGSV;
var cks = document.cookie.split("; ").reduce(function(ret, s){ var kv = s.split("="); if(kv[0] && kv[1]) ret[kv[0]] = kv[1]; return ret; }, []);
turl = Object.keys(KEYS).reduce(function(url, k){ var vk = KEYS[k][0] + PV; var tk = KEYS[k][1] + PV; var v = "", t = ""; if(cks[vk]){ v = cks[vk]; if(cks[tk]) t = cks[tk]; }else if(localStorage.getItem(vk)){ v = localStorage.getItem(vk); t = "ls"; } if(v) url += "&" + k + "=" + v; if(t) url += "&" + KEYS[k][2] + "=" + t; return url; }, turl);
var xhr = new XMLHttpRequest(); xhr.open("GET", turl); xhr.send(); })();
</script>
<!--crossoverr終了-->


<!--hapitas-->
<script>
(function acsTrack(){
var PV = "pi2x7pz0guej";
var _ARGSV = "<?= $_GET['subsId'] ?>";
var KEYS = {cid : ["CL_", "ACT_", "cid_auth_get_type"], plid : ["PL_", "APT_", "plid_auth_get_type"]};
var turl = "https://ozasp.jp/track.php?p=" + PV + "&args=" + _ARGSV;
var cks = document.cookie.split("; ").reduce(function(ret, s){ var kv = s.split("="); if(kv[0] && kv[1]) ret[kv[0]] = kv[1]; return ret; }, []);
turl = Object.keys(KEYS).reduce(function(url, k){ var vk = KEYS[k][0] + PV; var tk = KEYS[k][1] + PV; var v = "", t = ""; if(cks[vk]){ v = cks[vk]; if(cks[tk]) t = cks[tk]; }else if(localStorage.getItem(vk)){ v = localStorage.getItem(vk); t = "ls"; } if(v) url += "&" + k + "=" + v; if(t) url += "&" + KEYS[k][2] + "=" + t; return url; }, turl);
var xhr = new XMLHttpRequest(); xhr.open("GET", turl); xhr.send(); })();
</script>
<!--hapitass終了-->
  


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
<script src='https://k.z.mobu.jp/ad/js/cv.js'></script>
<script>
   HEAD_CV.cv('zucks_xuid', 'https://k.z.mobu.jp/ad', 'advertiser=91&ad=372&_price=&_buid=<?= $_GET['subsId'] ?>');
</script>
<noscript>
   <img src='https://k.z.mobu.jp/ad/p/cv?advertiser=91&ad=372&_price=&_buid=<?= $_GET['subsId'] ?>' width='1' height='1' />
</noscript>
<!--終了-->


<!--asplay-->
<script>
(function acsTrack(){
var PV = "pi3o1su99pwf";
var _ARGSV = "<?php echo $_GET['subsId']; ?>";
var KEYS = {cid : ["CL_", "ACT_", "cid_auth_get_type"], plid : ["PL_", "APT_", "plid_auth_get_type"]};
var turl = "https://asplay.biz/track.php?p=" + PV + "&args=" + _ARGSV;
var cks = document.cookie.split("; ").reduce(function(ret, s){ var kv = s.split("="); if(kv[0] && kv[1]) ret[kv[0]] = kv[1]; return ret; }, []);
turl = Object.keys(KEYS).reduce(function(url, k){ var vk = KEYS[k][0] + PV; var tk = KEYS[k][1] + PV; var v = "", t = ""; if(cks[vk]){ v = cks[vk]; if(cks[tk]) t = cks[tk]; }else if(localStorage.getItem(vk)){ v = localStorage.getItem(vk); t = "ls"; } if(v) url += "&" + k + "=" + v; if(t) url += "&" + KEYS[k][2] + "=" + t; return url; }, turl);
var xhr = new XMLHttpRequest(); xhr.open("GET", turl); xhr.send(); })();
</script>
<!--終了-->

<!--piara-->
    <script>
    (function() {
    var uqid = "cf26S373Sef9aai9";
    var gid  = 1683;
    var uid = "<?php echo $_GET['subsId']; ?>";
    var uid2 = "";
    var af = "";
    var pid = "";
    var amount = "";
    var a = document.createElement("script");
    a.src = "//ad.resultplus2.jp/ac/action_js.php";
    a.id = "afadaction-" + Date.now();
    a.addEventListener("load", function () { (new fpcAction(a.id)).groupAction(gid, af, [uid, uid2], pid, amount, uqid) });
    document.head.appendChild(a)
    })();</script>
    
    <!--piara終了-->



<!--チャットボット経由の申込み計測（自社版）-->
<script>
window.hbChatbotConversion = function () {
  /* チャット経由の申込みだけ数えたい広告タグがあれば、ここに中身を貼る */
};
</script>
<script src="/js/chatbot/chatbot-complete.js"></script>
<!--チャットボット計測終了-->

</body>
</html>
