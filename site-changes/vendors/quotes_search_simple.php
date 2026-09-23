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

if (isMobile()) {
    $redirectUrl = SSL_URL.'vendors/quotes_search_sp.php';
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
    // ゴンドラ: http://www.housingbazar.jp/vendors/quotes_search_simple.php?p=1&in=go
    if (isset($_GET['a8'])) {
        $_SESSION['ad_company'] = 'a8';
        $_SESSION['ad_code'] = $_GET['a8'];
        $shouldDisplayChatbot = false;
    } elseif (isset($_GET['in'])) {
        $_SESSION['ad_company'] = $_GET['in'];
        $_SESSION['ad_code'] = 'Unknown';
        $shouldDisplayChatbot = false;
    } elseif (isset($_GET['utm_source']) && $_GET['utm_source'] === 'cw') {
        $_SESSION['ad_company'] = 'cw';
        $_SESSION['ad_code'] = 'Unknown';
    } elseif (isset($_GET['province']) && $_GET['province'] === '.php') {
        $_SESSION['ad_company'] = 'Unknown';
        $_SESSION['ad_code'] = 'Unknown';
    }
}

$pageTitle          = META_VENDORS_QUOTES_SEARCH;
$metaDescription    = META_VENDORS_QUOTES_SEARCH_DESC;
$metaKeywords       = META_VENDORS_QUOTES_SEARCH_KEY;
$meta_INDEX_H1      = "工務店へ資料請求するならハウジングバザール。<br />簡単、無料で工務店の資料請求を一括お取り寄せ。";

$vClass = new Vendors();
$pClass = new Provinces();

$ps = $pClass->getAll();
// getters
if (!empty($_GET['txt'])) {
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
if (!empty($_GET['vid'])) {
    $vid = $_GET['vid'];
    $vid = array_unique($vid);
}
if (!empty($_GET['del_id']) && is_numeric($_GET['del_id'])) {
    $del_id = intval($_GET['del_id']);
    unset($vid[array_search($del_id, $vid)]);
} else {
    if (count($vs) > 0) {
        foreach ($vs as $v) {
            $vid[] = $v['id'];
        }
    }
}

if (!empty($_GET['send_quotes'])) {
    header('location: '.SSL_URL.'vendors/quotes_city_select.php?'.$_SERVER['QUERY_STRING']);
    exit;
}

if (isset($_GET['utm_source'])) {
    setcookie('utm_source', $_GET['utm_source'], time() + 3600 * 6);
}

$bread = array(META_VENDORS_QUOTES_SEARCH);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php require_once TEMP_DIR.'/head.php'; ?>
<style>
.selectForm { position: relative; }
.selectForm select[name=province] { top: 178px; left: 135px; width: 150px; height: 28px; position: absolute; }
.selectForm select[name="quotes[city]"] { top: 178px; left: 310px; width: 150px; height: 28px; position: absolute; }
.selectForm input[name=send_quotes] { top: 145px; left: 484px; width: 170px; height: 65px; position: absolute; background: transparent; border: none; cursor: pointer; }
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

<!-- AFRo トラッキング -->
<script src="https://www.cross-a.net/act/afrolp.js"></script>

<!--Zucksトラッキング-->
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
<!--ギークハッシュトラッキング終わり-->


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

<body style="background:url(../images/head_wrap2.gif) repeat-x top;">


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

<!--<div class="wrap3">-->
<div class="wrap">
<?php require_once TEMP_DIR.'/header2.php'; ?>

<div class="content_wrap">

    <div class="s_content">

        <div class="bread">
            <?=Helper::getBreadcrumb($bread)?>
        </div>

        <div class="h2_wrap">
            <h2>建築予定地やご希望の地域の工務店へ一括資料請求</h2>
        </div>

        <div style="text-align: center;">
            <img src="<?=SSL_URL?>vendors/images/ikkatsu_main230827.png" >
            <img src="<?=SSL_URL?>vendors/images/ikkatsu_respondent.png" >
            <p style="font-size:16px;padding-top:20px;" >
                家づくりの第一歩は、建築予定地や建築を希望する地域の会社から資料を取り寄せることから始まります。まずはじっくり資料を見て、自分の好みや想いをまとめてみましょう。<br />その上で、信頼できる建築会社を選ぶことが大切です。
            </p>
            <img src="<?=SSL_URL?>vendors/images/ikkatsu_arrow.png" width="600px">
        </div>

        <div class="selectForm">
            <img src="images/select3.png">
            <form name="form1" action="quotes_send2.php" method="get" target="sendform">
                <select name="province" id="province_loc_id" onchange="javascript:callCities();">
                    <option value="">選択してください。</option>
                    <option value="1">北海道</option>
                    <option value="2">青森県</option>
                    <option value="3">岩手県</option>
                    <option value="4">宮城県</option>
                    <option value="5">秋田県</option>
                    <option value="6">山形県</option>
                    <option value="7">福島県</option>
                    <option value="8">茨城県</option>
                    <option value="9">栃木県</option>
                    <option value="10">群馬県</option>
                    <option value="11">埼玉県</option>
                    <option value="12">千葉県</option>
                    <option value="13">東京都</option>
                    <option value="14">神奈川県</option>
                    <option value="15">新潟県</option>
                    <option value="16">富山県</option>
                    <option value="17">石川県</option>
                    <option value="18">福井県</option>
                    <option value="19">山梨県</option>
                    <option value="20">長野県</option>
                    <option value="21">岐阜県</option>
                    <option value="22">静岡県</option>
                    <option value="23">愛知県</option>
                    <option value="24">三重県</option>
                    <option value="25">滋賀県</option>
                    <option value="26">京都府</option>
                    <option value="27">大阪府</option>
                    <option value="28">兵庫県</option>
                    <option value="29">奈良県</option>
                    <option value="30">和歌山県</option>
                    <option value="31">鳥取県</option>
                    <option value="32">島根県</option>
                    <option value="33">岡山県</option>
                    <option value="34">広島県</option>
                    <option value="35">山口県</option>
                    <option value="36">徳島県</option>
                    <option value="37">香川県</option>
                    <option value="38">愛媛県</option>
                    <option value="39">高知県</option>
                    <option value="40">福岡県</option>
                    <option value="41">佐賀県</option>
                    <option value="42">長崎県</option>
                    <option value="43">熊本県</option>
                    <option value="44">大分県</option>
                    <option value="45">宮崎県</option>
                    <option value="46">鹿児島県</option>
                    <option value="47">沖縄県</option>
                </select>
                <select name="quotes[city]" id="const_location_city">
                    <option value="">都道府県を先に選択してください。</option>
                </select>

                <input type="image" name="send_quotes" value="&nbsp;" src="images/resist_button0317.png">
            </form>
        </div>

        <iframe id="sendform" name="sendform" src="" style="display:none;width: 100%;height: 2800px;border: 0;" frameborder="0"></iframe>

        <div style="text-align: center;">
            <a href="https://www.housingbazar.jp/renovefudosan"><img src="<?=SSL_URL?>vendors/images/renovation_banner.png" width="100%" style="margin:20px 0 0;"></a>
            <a href="https://lin.ee/JPBDDLi"><img src="<?=SSL_URL?>vendors/images/line.png" width="100%" style="margin:20px 0 0;"></a>
            <img src="<?=SSL_URL?>vendors/images/ikkatsu_anshin.jpg">
            <img src="<?=SSL_URL?>vendors/images/ikkatsu_reason.jpg">
            <img src="<?=SSL_URL?>vendors/images/ikkatsu_reason2.jpg">
        </div>

        <p style="font-size:16px;padding-top:20px;" >
            ハウジングバザールでは、木の家や自然素材を使った住まいをつくる地域の優良工務店や、工務店ネットワークをご紹介しています。いずれも会社審査を経て掲載しておりますので、安心して工務店へ資料請求して下さい。
        </p>

        <div style="text-align: center;">
            <img src="<?=SSL_URL?>images/flow.gif" alt="1:一括資料請求→2:比較・検討→3:相談・契約・着工→4:完成" width="670" style="padding:10px 0 20px 0" />
        </div>

    </div><!-- s_content// -->

    <?php require_once TEMP_DIR.'/left2.php'; ?>

</div><!-- content_wrap// -->
</div><!-- wrap// -->
<?php require_once TEMP_DIR.'/footer.php'; ?>
</div>

<script>
function callCities() {
    province = $('#province_loc_id').val();

    if (province.length != 0) {
        $('#const_location_city').empty();
        $('#const_location_city').append(
            $('<option></option>').attr('value', '').append("ローディング中・・・")
        );
        $.get(
            '../js/city.php',
            {selected:province},
            function(data){
                $('#const_location_city').empty();
                $('#const_location_city').append(
                    $('<option></option>').attr('value', '').append("選択してください")
                );
                for (var i in data.result){
                    $('#const_location_city').append(
                        $('<option></option>').attr('value', i).append(data.result[i])
                    );
                }
            },
            'json'
        );
    } else {
        $('#const_location_city').empty();
        $('#const_location_city').append(
            $('<option></option>').attr('value', '').append("エラーが発生しました。")
        );
    }
}
$(document).ready(function(){
    $("input[name=send_quotes]").click(function() {
        if ($("#const_location_city").val().length > 0) {
            $("#sendform").slideDown();
            setTimeout(function() {
                $('html,body').animate({
                    scrollTop : $("#sendform").offset().top - 150
                }, 'fast');
            }, 500);
        }
        return true;
    });
});
</script>


<!-- レントラックス　ASP ITP対応トラッキングタグの設置 -->
<script type="text/javascript">
(function(callback){
var script = document.createElement("script");
script.type = "text/javascript";
script.src = "https://www.rentracks.jp/js/itp/rt.track.js?t=" + (new Date()).getTime();
if (script.readyState) {
    script.onreadystatechange = function() {
        if (script.readyState === "loaded" || script.readyState === "complete") {
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

<!-- リマーケティング タグの Google コード -->
<!--------------------------------------------------
リマーケティング タグは、個人を特定できる情報と関連付けることも、デリケートなカテゴリに属するページに設置することも許可されません。タグの設定方法については、こちらのページをご覧ください。
http://google.com/ads/remarketingsetup
--------------------------------------------------->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 983530922;
var google_custom_params = window.google_tag_params;
var google_remarketing_only = true;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/983530922/?value=0&amp;guid=ON&amp;script=0"/>
</div>
</noscript>

<!-- リマーケティング タグの Google コード -->
<!--------------------------------------------------
リマーケティング タグは、個人を特定できる情報と関連付けることも、デリケートなカテゴリに属するページに設置することも許可されません。タグの設定方法については、こちらのページをご覧ください。
http://google.com/ads/remarketingsetup
--------------------------------------------------->
<script type="text/javascript">
/* <![CDATA[ */
var google_conversion_id = 1033459668;
var google_custom_params = window.google_tag_params;
var google_remarketing_only = true;
/* ]]> */
</script>
<script type="text/javascript" src="//www.googleadservices.com/pagead/conversion.js">
</script>
<noscript>
<div style="display:inline;">
<img height="1" width="1" style="border-style:none;" alt="" src="//googleads.g.doubleclick.net/pagead/viewthroughconversion/1033459668/?value=0&amp;guid=ON&amp;script=0"/>
</div>
</noscript>



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

<!--フォームタグ-->
<script type="text/javascript">
window.ebSettings_2 = {eb_appId: 'ed7bb2c3c5eabd6cff34c4e3e3443cf6',eb_email: 'input[name="contact[email]"]'};
</script>
<script src="https://chasy.jp/static/js/starting.min.js"></script>
<script type="text/javascript">
window.ebSettings_2 = {eb_appId: '09657dabaebad73dec81804498296c5e',eb_tel: 'input[name="contact[tel1]"],input[name="contact[tel2]"],input[name="contact[tel3]"]'};
</script>
<script src="https://chasy.jp/static/js/starting.min.js"></script>
<script type="text/javascript">
window.ebSettings_2 = {eb_appId: '7e92e8d4eaba19bc263fef7c99ad353c',eb_name: 'input[name="contact[name]"],input[name="contact[name2]"]'};
</script>
<script src="https://chasy.jp/static/js/starting.min.js"></script>


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
