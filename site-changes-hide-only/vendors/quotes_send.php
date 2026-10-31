<?php
require_once '../includes/config.php';

$vClass = new Vendors();
$pClass = new Provinces();
$cc = new Cities();
$error['flag'] = true;
/*
if (isset($_GET['set']) && $_GET['set'] == "finalize"
&& (Login::getInstance()->isUserAuthenticated()|| Login::getInstance()->isVendorAuthenticated())) {
    $contact = DirectQuotes::getInstance()->getAttributes();
    $vids = $contact['vids'];
    unset($contact['vids']);
    //print_r($_SESSION['DirectQuotes']);
    //print_r($contact);exit;
    $error = $vClass->sendQuoteMails($vids, $contact);
    header('Location: '.SSL_URL.'vendors/quotes_send_complete.php?subsId='.$GLOBALS['subsId']);
    exit;
}
*/
$trim_inputs = array(
    'name', 'name2', 'name_read', 'name_read2',
    'zip', 'addr_city', 'address', 'address_room',
    'tel1', 'tel2', 'tel3', 'age', 'email',
    'date', 'time',
);
foreach ($trim_inputs as $value) {
    $_POST['contact'][$value] = FormHelper::trimValue($_POST['contact'][$value]);
}
if ($issetDate = isset($_POST['contact']['date'])
|| isset($_POST['contact']['time'])) {
    if ($issetDate && isset($_POST['contact']['time'])) {
        $_POST['contact']['option32'] = $_POST['contact']['date'].'日 '.$_POST['contact']['time'].'時頃';
    } else {
        if ($issetDate) {
            $_POST['contact']['option32'] = $_POST['contact']['date'].'日';
        } else {
            $_POST['contact']['option32'] = $_POST['contact']['time'].'時頃';
        }
    }
}

$isDev = false;
if (isset($_SERVER['SERVER_ADDR']) && ($_SERVER['SERVER_ADDR'] !== '202.181.101.78')) {
    $isDev = true;
}
$production = 'http://202.181.101.101/';
$dev = 'http://192.168.33.60/';

if (isset($_POST['ajax'])) {
    $formId = 'ATJbMANgBjM=';
    // $formId = 'TCu2b+sRDqmXNz3KjtYpkm+CZu5IpmXprBlIbdXsQkZmZXpxABiK1vb3WtH+qtCsFH9VHHGGGHLvZCYimdsvZA==';

    $data = $_POST['contact'];
    if (isset($_POST['form_name'])) {
        $data['form_name'] = preg_replace('/.*\[(.*)\]/', '\1', $_POST['form_name']);
    }

    $params = array(
        'FID' => $formId,
        'remoteip' => $_SERVER['REMOTE_ADDR'],
        'useragent' => $_SERVER['HTTP_USER_AGENT'],
    );
    $params += $data;
    $data = http_build_query($params);

    $header = array(
        "Content-Type: application/x-www-form-urlencoded",
        "Content-Length: ". strlen($data),
    );

    $options = array('http' => array(
        'method' => 'POST',
        "header"  => implode("\r\n", $header),
        'content' => $data,
    ));

    $url_path = 'apis/mailgate/check';
    $postUrl = $isDev ? $dev.$url_path : $production.$url_path;
    // $postUrl = 'http://controlpanel.dev.groundstep.jp/apis/mailgate/check';
    $response = file_get_contents($postUrl, false, stream_context_create($options));
    if ($response === false) {
        return null;
    }

    file_put_contents('./api_ajax_current.log', date('Y/m/d H:i:s') . " ---- \r\n" . $response);

    echo $response;
    return;
}

if (!empty($_POST) && $_POST['action'] == "complete" && !empty($_POST['submit'])) {
    $contact = $_POST['contact'];
    $contact['tel'] = $contact['tel1'] . '-' . $contact['tel2'] . '-' . $contact['tel3'];
    if (!empty($contact['address_room']) && $contact['address_room'] !== 'なし') {
        $contact['address'] = $contact['address'] . ' ' . $contact['address_room'];
    }
    $vids = $_POST['vid'];
    $cityId = intval($_POST['city']);
    $contact['city'] = $cityId;
    $error = $vClass->checkQuotesForm($contact);
    if (!$error['flag']) {
        $vs = $vClass->getAllByCityId($cityId);
        if (count($vs) > 0) {
            foreach ($vs as $v) {
                $vids[] = $v['id'];
            }
            $vids = array_unique($vids);
        }
        // print_r($vids);exit;
        $already = "";
        $brochure = "";
        for ($i=0; $i<count($contact['already']); $i++) {
            $already .= $contact['already'][$i] . "、";
        }
        for ($i=0; $i<count($contact['brochure']); $i++) {
            $brochure .= $contact['brochure'][$i] . "、";
        }
        for ($i=0; $i<count($contact['option31']); $i++) {
            $option31 .= $contact['option31'][$i] . "、";
        }
        $contact['already'] = $already;
        $contact['brochure'] = $brochure;
        $contact['option31'] = $option31;

        // T-POINT用
        @session_start();
        if (isset($_SESSION['tp'])) {
            $contact['tp'] = $_SESSION['tp'];
        }
        // Ad判定用
        if (isset($_SESSION['ad_company'])) {
            $contact['ad_company'] = $_SESSION['ad_company'];
        }
        if (isset($_SESSION['ad_code'])) {
            $contact['ad_code'] = $_SESSION['ad_code'];
        }
        if ($contact['email'] === 'sikeda@g-rexjapan.co.jp') {
            $error = $vClass->sendQuoteMailsDev($vids, $contact, "資料請求");
        } else {
            $error = $vClass->sendQuoteMails($vids, $contact, "資料請求");
        }
        // コンパネでのmb_send_mailのエラー文章も入ってしまうので必要な部分を切り出す
        $subsId = substr($GLOBALS['subsId'], -6);
        $http_params = array(
            'msg' => 1,
            'subsId' => $subsId
        );
        header('Location: '.SSL_URL.'vendors/quotes_send_complete.php?'.http_build_query($http_params, '', '&'));

        unset($_SESSION['ad_company']);
        unset($_SESSION['ad_code']);
        exit;
    }
} elseif (! empty($_POST) && $_POST['action'] == "confirm") {
    $contact = $_POST['contact'];
    @session_start();

    if (isset($_POST['movetocart'])) {
        $_POST = array_merge($_POST, $contact);
        $_POST['const_location_yes_no'] = isset($_POST['const_location_yes_no']) == 1 ? '有' : '無し';
        $_POST['email1'] = $_POST['email'];
        $_POST['email2'] = $_POST['email'];
        $_POST['check'] = 1;
        unset($_POST['action']);
        $_SESSION['province'] = $_POST['province'];
        $_SESSION['city'] = $_POST['city'];

        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
        $ids = array();
        foreach ($cart['vendors'] as $vid) {
            $ids[] = 'v:' . $vid;
        }
        foreach ($cart['candidate'] as $vid) {
            if (! in_array($vid, $cart['vendors'])) {
                $ids[] = 'v:' . $vid;
            }
        }
        if (isset($cart['papers'])) {
            foreach ($cart['papers'] as $pid) {
                $ids[] = 'p:' . $pid;
            }
        }
        $vs = $vClass->getAllByCityId($_POST['city']);
        foreach ($vs as $val) {
            if (! in_array('v:' . $val['id'], $ids)) {
                $ids[] = 'v:' . $val['id'];
            }
        }
        $_POST['related_ids'] = $ids;
        $_SESSION['related_ids'] = $_POST['related_ids'];

        require_once('./cart_form.php');
        exit;
    }

    $contact['tel'] = $contact['tel1'] . '-' . $contact['tel2'] . '-' . $contact['tel3'];

    mb_regex_encoding(mb_internal_encoding());
    // $contact['address'] = mb_ereg_replace('[－]+', '-', $contact['address']);

    $error = $vClass->checkQuotesForm($contact);

    if (empty($contact['tel1']) || empty($contact['tel2']) || empty($contact['tel3'])) {
        $error['tel'] = '<span class="error_text">TELを入力してください。</span>';
        $error['flag'] = true;
    } elseif (! preg_match('/^0([0-9]){9,10}$/', $contact['tel1'].$contact['tel2'].$contact['tel3'])) {
        $error['tel'] = '<span class="error_text">TELを正しく入力してください。</span>';
        $error['flag'] = true;
    } else {
        $numberLength = strlen($contact['tel1'].$contact['tel2'].$contact['tel3']);
        $areacode11s = array('020', '050', '070', '080', '090');
        if (in_array($contact['tel1'], $areacode11s)) {
            if ($numberLength != 11) {
                $error['tel'] = '<span class="error_text">TELを正しく入力してください。</span>';
                $error['flag'] = true;
            }
        } elseif ($numberLength != 10) {
            $error['tel'] = '<span class="error_text">TELを正しく入力してください。</span>';
            $error['flag'] = true;
        }
    }

    if ($contact['family'] == 1) {
        if (empty($contact['const_use'])) {
            $error['const_use'] = '<span class="error_text">建築後のご利用用途を選択してください。</span>';
            $error['flag'] = true;
        }
    }

    if (empty($contact['address_room'])) {
    // 建物名・部屋番号は任意入力 elseif (!isset($error['flag']) || !$error['flag']) {
        $_POST['contact']['address'] = $_POST['contact']['address'] . ' ' . $_POST['contact']['address_room'];
    }
} elseif (!empty($_POST) && $_POST['action'] == "complete" && !empty($_POST['back'])) {
    $contact = $_POST['contact'];
    $error['flag'] = true;
} else {
    if (Login::getInstance()->isUserAuthenticated() || Login::getInstance()->isVendorAuthenticated()) {
        $uClass = new Users();
        $uid = Login::getInstance()->getId();
        $user = $uClass->getOneById($uid);
        $contact = $user;
    }
}

if (empty($_GET['province']) || !is_numeric($_GET['province'])) {
    if (empty($_POST['province']) || !is_numeric($_POST['province'])) {
        header('location: '.SSL_URL.'vendors/quotes_search.php');
        exit;
    } else {
        $pid = intval($_POST['province']);
    }
} else {
    $pid = intval($_GET['province']);
}
if (empty($_GET['quotes']['city']) || !is_numeric($_GET['quotes']['city'])) {
    if (empty($_POST['city']) || !is_numeric($_POST['city'])) {
        header('location: '.SSL_URL.'vendors/quotes_search.php');
        exit;
    } else {
        $cid = intval($_POST['city']);
    }
} else {
    $cid = intval($_GET['quotes']['city']);
}
$vid = '';
if (isset($_GET['vid'])) {
    $vid = $_GET['vid'];
} elseif (isset($_POST['vid'])) {
    $vid = $_POST['vid'];
}
// $provinceName = $pClass->getNameById($provindeId); // 使用されていない
$cityName = $cc->getCityName($cid);

$method = array("直接相談したい", "まずは電話で相談したい");
$place = array("自宅まで来て欲しい", "展示場・ショールーム・貴社事務所の予約をしたい");
$brochure = array(
    "パンフレットや資料　（特長やこだわり、商品等）",
    "ショールーム・見学会・イベント等の情報が欲しい",
    "建築例の資料が見たい　（パンフレット・リーフレット・チラシ等）",
    "家づくりの進め方の分かる資料が欲しい　（パンフレット・リーフレット・チラシ等）"
);
$already = array("既に契約している。契約に向けて打合せ進行中", "詳しい内容を知らなかったので今後検討してみたいと思った");
$family_num = array("1","2","3","4","5","6","7","8","9","10");
$constUses = array("別荘として使用", "自宅として入居", "投資・資産運用の為", "その他");
$construct_budget = array("1000～2000万円","2000～3000万円","3000万円～","まだ検討中");
$construct_time = array("今すぐ","半年以内","1年以内", "1～2年以内","2～3年以内", "検討中" );
$houseType = array("持ち家（本人名義）","持ち家（親名義）","マンション","賃貸","その他");
$buildYears = array("10～15年", "15～20年", "20～25年", "25年以上");
$buildType = array("建て替え希望", "別の土地で新築希望");
//$contactDates = array("月曜日", "火曜日","木曜日","金曜日","土曜日");
$contactDates = array("平日");
$contactTimes = array("10:00～12:00", "12:00～14:00", "14:00～16:00", "16:00～18:00");
$provinces = array(
    "北海道","青森県","岩手県","宮城県","秋田県","山形県","福島県",
    "茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県",
    "新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県",
    "三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県",
    "鳥取県","島根県","岡山県","広島県","山口県","徳島県","香川県","愛媛県","高知県",
    "福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"
);
$option15 = array("20坪以下", "20～30坪", "30坪～40坪" ,"40坪～50坪" ,"50坪～60坪" ,"60坪～");
$option18 = array("２LDK" ,"３LDK" ,"４LDK" ,"５LDK" ,"２世帯住宅");
$option31 = array("建築会社での打合せを希望", "自宅での打合せを希望", "オンラインでの打合せを希望");
$time = array('8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21');

if ((isset($_GET['nologin']) && $_GET['nologin'] == "Y") || (isset($_POST['nologin']) && $_POST['nologin'] == "Y")) {
    $isBrochure = true;
    $pageTitle= META_VENDORS_QUOTES_SEND;
    $metaDescription = META_VENDORS_QUOTES_SEND_DESC;
    $metaKeywords = META_VENDORS_QUOTES_SEND_KEY;
    $bread = array(
        SSL_URL."vendors/quotes_search.php?province=".$pid => META_VENDORS_QUOTES_SEARCH,
        META_VENDORS_QUOTES_SEND
    );
} else {
    $isBrochure = false;
    $pageTitle= META_VENDORS_QUOTES_SEND;
    $metaDescription = META_VENDORS_QUOTES_SEND_DESC;
    $metaKeywords = META_VENDORS_QUOTES_SEND_KEY;
    $bread = array(
        SSL_URL."vendors/quotes_search.php?province=".$pid => META_VENDORS_QUOTES_SEARCH,
        META_VENDORS_QUOTES_SEND
    );
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php require_once TEMP_DIR.'/head.php'; ?>

<script src="./jquery-1.8.2.min.js"></script>
<script src="./jquery.validationEngine-ja.js" type="text/javascript" charset="utf-8"></script>
<script src="./jquery.validationEngine.js" type="text/javascript" charset="utf-8"></script>
<link rel="stylesheet" href="./validationEngine.jquery.css" type="text/css"/>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.js"></script>
<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/ui-lightness/jquery-ui.css" rel="stylesheet" />
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
<script type="text/javascript" src="js/zansu_ikkatu.js"></script>
<!-- <script type="text/javascript" src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script> -->
<script type="text/javascript" src="./mgform_grex.js"></script>
<script src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script>
<script type="text/javascript" src="js/jquery.autoKana.js"></script>
<script src="js/jquery.email-autocomplete.js"></script>
<script type="text/javascript">
var zipaction = null;
$(document).ready(function(){
    $("input[name='contact[zip]']").keyup(function() {
        if (zipaction != null) {
            clearTimeout(zipaction);
        }
        zipaction = setTimeout(function() {
            AjaxZip3.zip2addr('contact[zip]',null,'contact[addr_province]','contact[addr_city]','contact[address]');
            zipaction = null;
        }, 1000);
    });
    $("select[name='contact[family]']").change(function() {
        var family_val = $("select[name='contact[family]'] option:selected").val();
        if (family_val == "1") {
            $('.familyAdditional').slideDown('slow');
        } else {
            $('.familyAdditional').hide();
        }
    });
    if ($("select[name='contact[family]']").val() == "1") {
        $('.familyAdditional').show();
    }
    $("input[name='contact[const_status]']").click(function() {
        if ($(this).val().indexOf('持ち家') >= 0) {
            $('.houseHolder').show();
            $('.houseHolder div').slideDown('slow');
        } else {
            $('.houseHolder').hide();
            $('.houseHolder div').hide();
        }
    });
    if ($("input[name='contact[const_status]']:checked").length > 0
    && $("input[name='contact[const_status]']:checked").val().indexOf('持ち家') >= 0) {
        $('.houseHolder').show();
        $('.houseHolder div').show();
    }

    var error_pos = -1;
    $(".error_text").each(function() {
        if ($(this).html().length > 0) {
            var pos = $(this).offset().top - 200;
            if (error_pos == -1 || error_pos > pos) {
                error_pos = pos;
            }
        }
    });
    if (error_pos > 0) {
        setTimeout(function() {
            $('html,body').animate({
                scrollTop : error_pos
            }, 'fast');
        }, 500);
    }
});

$(function() {
  // テキストボックスにフォーカス時、フォームの背景色を変化
  $('input, select, textarea')
    .focusin(function(e) {
      $(this).css('background-color', '#CEF6F5');
    })
    .focusout(function(e) {
      $(this).css('background-color', '');
    });
　  $.fn.autoKana('#userName', '#userNameKana', {
        katakana : true  //true：カタカナ、false：ひらがな（デフォルト）
    });
    $.fn.autoKana('#userName2', '#userNameKana2', {
        katakana : true  //true：カタカナ、false：ひらがな（デフォルト）
    });　
});
function nextField(i, n, m) {
  if (i.value.length >= m) {
    i.form.elements[n].focus();
  }
}
$(function() {
  var $win = $(window),
      $main = $('#main'),
      $nav = $('#statusbar'),
      navHeight = $nav.outerHeight(),
      navPos = $nav.offset().top,
      fixedClass = 'is-fixed';

  $win.on('load scroll', function() {
    var value = $(this).scrollTop();
    if ( value > navPos ) {
      $nav.addClass(fixedClass);
      $main.css('margin-top', navHeight);
    } else {
      $nav.removeClass(fixedClass);
      $main.css('margin-top', '0');
    }
  });
});
(function($){
    $(function() {
      $(".email").emailautocomplete({
        domains: ["example.com"] //add your own domains
      });
    });
  }(jQuery));

$(function() {
    $(".datepicker").datepicker();
});
</script>

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

</head>

<style>
.req {
    /* background-color: #ffdede; */
    background-color: #fffbde;
}
#submitbt {
    width: 680px;
    height: 70px;
    border: none;
    background: #00A7EA;
    color:#fff;
    cursor: pointer;
    border-radius: 0.3em;
    font-size:30px;
}
#submitbt:hover {
    background: #ccc;
}
.bt {
    width: 300px;
    height: 70px;
    border: none;
    background: #00A7EA;
    color:#fff;
    cursor: pointer;
    border-radius: 0.3em;
    font-size: 30px;
}
.bt:hover {
    background: #ccc;
}
.backbt{
    width: 300px;
    height: 70px;
    border: none;
    background: #ccc;
    color: #fff;
    cursor: pointer;
    border-radius: 0.3em;
    font-size: 30px;
}
.backbt:hover {
    background: #cce5ff;
}
.is-fixed {
    position: fixed !important;
    position: absolute;
    top: 100px;
    text-align: center;
    z-index: 999;
    width: 100%;
    float: right;
}
</style>

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

<div class="wrap">
<?php require_once TEMP_DIR.'/header2.php'; ?>

<div class="content_wrap">

    <div class="s_content">

        <div class="bread">
            <?=Helper::getBreadcrumb($bread)?>
        </div>

        <div class="h2_wrap">
              <h2>無料資料請求</h2>
        </div>

        <form action="" method="post" enctype="multipart/form-data">
    <?php
    if ((isset($error['flag']) && !$error['flag']) || !isset($error['flag'])) :
        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
        $cartcount = isset($cart['vendors']) ? count($cart['vendors']) : 0;
        if ($cartcount > 0 && empty($_POST['ignorecart']) && empty($_SESSION['ignorecart'])) {
            ?>

            <div style="font-size:16px;text-align:center;">
                <p style="text-align:center;">
                    <b>
                        資料お取り寄せカートの中に資料が入っています<br/>
                        合わせてカート内の資料もお取り寄せしますか？
                    </b>
                </p>

                <div style="margin:0 auto;width:350px;">
                    <button type="submit" name="movetocart" value="1" style="height: 55px;width: 150px;float: left;margin: 3px;" onclick="flagConfirm=false;">カート内の資料も<br/>合わせて取り寄せる</button>
                    <a href="/vendors/cart_popup.php" style="display: block;position: relative;margin-left: 160px;" class="popup"><img src="/vendors/images/cratcheck2.png" style="padding:0 10px;" width="170">
                        <p id="cartin" style="position: absolute;top: 10px;left: 139px;font-size: 20px;font-weight: bolder;text-align: center;width: 28px;color: #000;"><?php echo $cartcount; ?></p>
                    </a>
                </div>

                <p style="text-align:center;margin-top:50px"><b>カート内の資料お取り寄せしない方はこちら</b></p>

                <div style="margin:0 auto;text-align:center;">
                    <button type="submit" name="ignorecart" value="1" style="height: 40px;width: 150px;margin: 3px;" onclick="flagConfirm=false;">入力内容を確認する</button>
                </div>

            </div>

            <?=FormHelper::getHiddens("contact", $contact)?>
            <?=$vid ? $vClass->getHiddens("vid", $vid) : ''?>
            <input type="hidden" name="city" value="<?php echo $cid?>"/>
            <input type="hidden" name="province" value="<?php echo $pid?>"/>
            <input type="hidden" name="action" value="confirm"/>

            <?php
        } else {
            unset($_SESSION['ignorecart']);
            // $_SESSION['ignorecart'] = true;
            ?>

            <p class="red">
                入力した内容を確認し、「送信」をクリックして下さい。入力した内容に間違いがある場合は、「戻る」をクリックして前のページにお戻り下さい。
            </p>

            <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                <tr>
                    <th valign="top">資料請求<img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td class="req">
                        <?=FormHelper::getPostDoubleArrayCommaBrSeparate("contact", "brochure")?><br/>
                    <?php if (!empty($_POST['contact']['brochure_other'])) : ?>
                        その他：<br>
                        <?=nl2br(FormHelper::getPostArrayDefault("contact", "brochure_other"))?>
                    <?php endif; ?>
                    </td>
                </tr>
            </table>

            <table border="0" cellpadding="0" cellspacing="0" class="table_form2">
                <tr>
                    <th valign="top">お名前 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3">
                        <?=FormHelper::getPostArrayDefault("contact", "name")?>
                        <?=FormHelper::getPostArrayDefault("contact", "name2")?>
                    </td>
                </tr>
                <tr>
                    <th valign="top">フリガナ <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3">
                        <?=FormHelper::getPostArrayDefault("contact", "name_read")?>
                        <?=FormHelper::getPostArrayDefault("contact", "name_read2")?>
                    </td>

                </tr>
                <tr>
                    <th valign="top">住所 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3"><?=FormHelper::getPostArrayDefault("contact", "zip")?><br/>
                    <?=FormHelper::getPostArrayDefault("contact", "addr_province")?> <?=FormHelper::getPostArrayDefault("contact", "addr_city")?> <?=FormHelper::getPostArrayDefault("contact", "address")?></td>
                </tr>
                <tr>
                    <th valign="top">TEL <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3"><?=FormHelper::getPostArrayDefault("contact", "tel1")?>-<?=FormHelper::getPostArrayDefault("contact", "tel2")?>-<?=FormHelper::getPostArrayDefault("contact", "tel3")?>&nbsp;</td>
                </tr>
                <tr>
                    <th valign="top">Emailアドレス <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3">
                        <?=FormHelper::getPostArrayDefault("contact", "email")?>&nbsp;
                    </td>
                </tr>
                <tr>
                    <th valign="top">勤務先</th>
                    <td colspan="3"><?=FormHelper::getPostArrayDefault("contact", "work_location")?>&nbsp;</td>
                </tr>
                <tr>
                    <th valign="top">年齢 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td>
                        <?php $age = FormHelper::getPostArrayDefault("contact", "age");
                        if (!empty($age)) : ?>
                            <?=FormHelper::getPostArrayDefault("contact", "age")?>才
                        <?php endif; ?>
                    </td>
                    <th valign="top">入居予定家族人数<img src="../vendors/req.gif" width="30px"style="float:right;"><br />（将来の予定含む）</th>
                    <td class="req"><?=FormHelper::getPostArrayDefault("contact", "family")?>人</td>
                </tr>
            <?php if ($contact['family'] == 1) { ?>
                <tr class="familyAdditional">
                    <th valign="top">建築後のご利用用途 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                    <td colspan="3" class="req">
                        <?=FormHelper::getPostArrayDefault("contact", "const_use")?>&nbsp;
                        <?=FormHelper::getPostArrayDefault("contact", "const_use_etc")?>
                    </td>
                </tr>
            <?php } ?>
                <tr>
                    <th valign="top">現在のお住まい <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3" class="req"><?=nl2br(FormHelper::getPostArrayDefault("contact", "const_status"))?>&nbsp;<?=FormHelper::getPostArrayDefault("contact", "house_type")?></td>
                </tr>
            <?php if (strpos($contact['const_status'], '持ち家') !== false) { ?>
                <tr>
                    <th valign="top">築年数 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3" class="req"><?=nl2br(FormHelper::getPostArrayDefault("contact", "build_year"))?>&nbsp;</td>
                </tr>
                <tr>
                    <th valign="top">建築内容 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3" class="req"><?=nl2br(FormHelper::getPostArrayDefault("contact", "const_info"))?>&nbsp;</td>
                </tr>
            <?php } ?>
                <tr>
                    <th valign="top">建築予定地 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td colspan="3" class="req">
                        土地:<?=$vClass->constLocationYesNo[FormHelper::getPostArrayDefault("contact", "const_location_yes_no")]?>
                    </td>
                </tr>
                <tr>
                    <th valign="top">建築予定時期 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td class="req"><?=FormHelper::getPostArrayDefault("contact", "const_start")?>&nbsp;</td>
                    <th valign="top">建築予算 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                    <td class="req"><?=FormHelper::getPostArrayDefault("contact", "const_budget")?></td>
                </tr>
                <tr>
                    <th valign="top">その他ご要望</th>
                    <td colspan="3"><?=nl2br(FormHelper::getPostArrayDefault("contact", "comment1"))?>&nbsp;</td>
                </tr>
            </table>

            <div class="h3_wrap">
                <h3>入力情報についてご確認ください</h3>
            </div>
            <div class="form_wrap">
                <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                    <tr>
                        <th valign="top">当フォーム入力者情報とあなたとのご関係 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                        <td class="req">
                            <?=FormHelper::getPostArrayDefault("contact", "relation")?>
                            <?=FormHelper::getPostArrayDefault("contact", "relationetc")?>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">注意事項 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                        <td class="req">同意</td>
                    </tr>
                </table>
            </div>

            <!--
            <div>
                <div class="h3_wrap">
                    <h3>アンケート</h3>
                </div>
                <div>
                    <p>※アンケートに回答して頂いた方へ、建築予定地の工務店から<span style="color:#ea0404;">「住まいの提案書」を無料で受けられるサービス</span>をご案内しております。なお、下記の提案資料をお求めの方のみ、アンケートにお答え下さい。</p>
                    <div style="margin:0 0 20px 0;"><img src="../images/teiansho3.png"></div>
                </div>
                <div class="form_wrap">
                    <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                        <tr>
                            <th valign="top">建築予定の土地の大きさは何坪くらいですか？<br>※購入予定地でも結構です</th>
                            <td colspan="3">
                                <?= FormHelper::getPostArrayDefault("contact", "option15") ?>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top">ご希望の部屋数は何LDKですか？</th>
                            <td colspan="3">
                                <?= FormHelper::getPostArrayDefault("contact", "option18") ?>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top">打ち合わせ方法を選択してください</th>
                            <td colspan="3">
                                <?= isset($error['option31']) ? $error['option31'] : '' ?>
                                <p>※複数選択可</p>
                                <?=FormHelper::getPostDoubleArrayCommaBrSeparate("contact", "option31")?>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top">打ち合わせを希望される日時をお聞かせください</th>
                            <td colspan="3">
                                <?= FormHelper::getPostArrayDefault("contact", "option32") ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            -->

            <div class="bt_wrap">
                <?=FormHelper::getHiddens("contact", $contact)?>
                <?=$vid ? $vClass->getHiddens("vid", $vid) : ''?>
                <input type="hidden" name="city" value="<?php echo $cid?>"/>
                <input type="hidden" name="province" value="<?php echo $pid?>"/>
                <input type="hidden" name="action" value="complete"/>
                <b><p style="color:red; font-size:18px; text-align:center;">※送信ボタンは１度だけ押して、お待ちください。
                <br />（完了画面に変わるまでに少々お時間がかかります。）</p></b>
                <br />
                <input type="submit" name="submit" value="　　送信　　" class="bt"
                　onclick='if(typeof pressed != "undefined"){return false;}pressed=1;'>
                <input type="submit" name="back" value="　　戻る　　" class="backbt"
                　onclick='if(typeof pressed != "undefined"){return false;}pressed=1;'>
            </div>

            <?php
        }
    else : ?>
            <div class="image_anshin2">
                <a href="<?= SSL_URL ?>request/check.php" title="どうしてもご心配な方は" target="_blank">
                    <img src="/images/fuan.png" style="padding:0 0 0 530px;" alt="どうしてもご心配な方は">
                </a>
            </div>

            <img src="/images/tel.png">

            <div class="contact_image_fax">
                <a href="<?= SSL_URL ?>brochure/fax.pdf" title="お問い合わせ用FAX送信書" target="_blank">
                    <img src="/images/contact_image_button.gif" alt="お問い合わせ用FAX送信書"style="padding:40px 0 0 440px;">
                </a>
            </div>


            <div style="width:680px; height:60px; background:#fff; border-bottom: 3px solid grey; margin-bottom:5px; text-align: center; clear: both;" id="statusbar">
                <div class="fixheader" style="margin: 0 0 0 0;">
                    <div class="bar" style="width: 598px; height: 15px; border: solid 1px #ccc; border-radius: 7px; margin: auto;">
                        <p style="width:0%; height:7px;background:#ff9932; border-radius: 7px;" id="restCheckBoxPercent"></p>
                    </div>
                    <p class="message" >
                        <div class="number" style="text-align: center; margin-top: 5px; color: #007fff;">.number</div>
                    </p>
                    <br />
                </div>
            </div>

            <div id="main">
                <div class="h3_wrap">
                    <h3>無料資料請求</h3>　
                </div>
                <p>
                    <b>※資料のお取り寄せは無料です<br />
                    ※個人情報の保護に関しましては、<a href="<?=SSL_URL?>privacy/" title="個人情報保護方針">個人情報保護方針</a>をご覧下さい。<br />
                    <img src="../vendors/req.gif" width="30px" style="padding:12px 5px 0 0;">の項目は必須項目です。　お問い合わせ、ご依頼に必要な情報を入力して下さい。</b><br />
                    <br />
                    資料請求をご希望の方は、チェックをして下さい。
                </p>
                <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                    <tr>
                        <th valign="top">資料請求 <img src="../vendors/req.gif" width="30px" style="float:right;"></th>
                        <td class="req">
                            <?= isset($error['brochure']) ? $error['brochure'] : '' ?>
                            <?=FormHelper::getCB1("contact", "brochure", $brochure, $contact)?><br/>
                          その他 （上記の他にお求めの資料があれば、詳しいご希望をご入力下さい。）<br />
                            （例）二世帯住宅のカタログがほしい。<br />
                            　　　省エネ住宅についての資料を下さい。
                          <?=FormHelper::getTA("contact", "brochure_other", $contact)?>
                        </td>
                    </tr>
                </table>

                <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                    <tr>
                        <th valign="top">お名前 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3">
                            <?= isset($error['name']) ? $error['name'] : '' ?>&nbsp;&nbsp;&nbsp;&nbsp;姓<?=FormHelper::getTF("contact", "name", $contact, 'tf req')?><?= isset($error['name2']) ? $error['name2'] : '' ?>&nbsp;名<?=FormHelper::getTF("contact", "name2", $contact, 'tf req')?>(全角)<br/>
                            <span id="contactname_error" class="error_text"></span><span id="contactname2_error" class="error_text"></span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">フリガナ <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3">
                            <?= isset($error['name_read']) ? $error['name_read'] : '' ?>&nbsp;セイ<?=FormHelper::getTF("contact", "name_read", $contact, 'tf req')?><?= isset($error['name_read2']) ? $error['name_read2'] : '' ?>&nbsp;メイ<?=FormHelper::getTF("contact", "name_read2", $contact, 'tf req')?>　(全角カナ)<br/>
                            <span id="contactname_read_error" class="error_text"></span><span id="contactname_read2_error" class="error_text"></span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">住所 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3">
                            <?= isset($error['zip']) ? $error['zip'] : '' ?>
                            郵便番号　（半角数字 ハイフン無し）<br/><?=FormHelper::getTF("contact", "zip", $contact, 'tf req')?><span style="color:#8e8d8d;"> 例)2900056</span><br/>
                            <span id="contactzip_error" class="error_text"></span>
                            <?= isset($error['addr_province']) ? $error['addr_province'] : '' ?>
                            都道府県　（全角）<br/>
                            <?=FormHelper::getDDL1("contact", "addr_province", $provinces, $contact)?><br/>
                            <?= isset($error['addr_city']) ? $error['addr_city'] : '' ?>
                            市区町村、その他住所　（全角）<br/>
                            <?=FormHelper::getTF("contact", "addr_city", $contact, 'tf req')?><span style="color:#8e8d8d;"> 例)市原市五井</span><br/>
                            <?= isset($error['address']) ? $error['address'] : '' ?>
                            <span class="tf_address2">番地、マンション名等　（全角）</span> <br/>
                            <?=FormHelper::getTF("contact", "address", $contact, 'tf req')?><span style="color:#8e8d8d;"> 例)2437-2 ホマレヤビル</span><br/>
                            <?= isset($error['address_room']) ? $error['address_room'] : '' ?>
                            <span class="tf_address2">部屋番号等　（全角）</span> <br/>
                            <?=FormHelper::getTF("contact", "address_room", $contact, 'tf')?><span style="color:#696969;">（例）301</span><br/>
                            <p style="color:#df9f23; padding-top:8px;">※住所の不備が多くなっております。ご入力の際に確認をお願いします。</p>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">TEL <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3">
                            <?= isset($error['tel']) ? $error['tel'] : '' ?>
                            <?=FormHelper::getTF("contact", "tel1", $contact, 'tf req')?>-<?=FormHelper::getTF("contact", "tel2", $contact, 'tf req')?>-<?=FormHelper::getTF("contact", "tel3", $contact, 'tf req')?>（半角数字）
                            <span id="contacttel1_error" class="error_text"></span><span id="contacttel2_error" class="error_text"></span><span id="contacttel3_error" class="error_text"></span>
                        </td>
                    </tr>

                <?php if (!empty($user)) : ?>
                    <tr>
                        <th valign="top">Emailアドレス <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3" class="tf_col3"><?=$contact['email']?>
                            <input type="hidden" name="contact[email]" value="<?=$contact['email']?>" />
                            <input type="hidden" name="contact[reemail]" value="<?=$contact['email']?>" />
                            <span id="contactemail_error" class="error_text"></span>
                        </td>
                    </tr>
                <?php else : ?>
                    <tr>
                        <th valign="top">Emailアドレス <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3" class="tf_col3">
                            <?= isset($error['email']) ? $error['email'] : '' ?>
                            <?=FormHelper::getTF("contact", "email", $contact, 'tf  email req')?> (半角英数字／携帯メールアドレス可)
                            <span id="contactemail_error" class="error_text"></span>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">Emailアドレス <img src="../vendors/req.gif" width="30px"style="float:right;"><br />(再入力)</th>
                        <td colspan="3" class="tf_col3">
                            <?= isset($error['reemail']) ? $error['reemail'] : '' ?>
                            <?=FormHelper::getTF("contact", "reemail", $contact, 'tf  email req')?>
                        </td>
                    </tr>
                <?php endif; ?>


                    <tr>
                        <th valign="top">年齢 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td>
                            <?= isset($error['age']) ? $error['age'] : '' ?>
                            <?=FormHelper::getTF("contact", "age", $contact, 'tf req')?>才　(半角数字)<br />
                            <span id="contactage_error" class="error_text"></span>
                        </td>
                        <th valign="top">入居予定家族人数<img src="../vendors/req.gif" width="30px"style="float:right;"><br />（将来の予定含む）</th>
                        <td class="req">
                            <?= isset($error['family']) ? $error['family'] : '' ?>
                            <?=FormHelper::getDDL1("contact", "family", $family_num, $contact)?>人　(半角数字)
                        </td>
                    </tr>
                    <tr class="familyAdditional" style="display:none">
                        <th valign="top">建築後のご利用用途をお知らせ下さい <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3" class="req">
                            <?= isset($error['const_use']) ? $error['const_use'] : '' ?>
                            <?=FormHelper::getRB4("contact", "const_use", $constUses, $contact)?>
                            <?=FormHelper::getTF("contact", "const_use_etc", $contact, 'tf')?>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">現在のお住まい <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3" class="req">
                            <?= isset($error['const_status']) ? $error['const_status'] : '' ?>
                            <?=FormHelper::getRB4("contact", "const_status", $houseType, $contact)?><?=FormHelper::getTF("contact", "house_type", $contact, 'tf')?>
                        </td>
                    </tr>
                    <tr class="houseHolder" style="display:none">
                        <th valign="top">
                            <div style="display:none">築年数 <img src="../vendors/req.gif" width="30px"style="float:right;"></div>
                        </th>
                        <td colspan="3" class="req">
                            <div style="display:none">
                                <?= isset($error['build_year']) ? $error['build_year'] : '' ?>
                                <span style="color: rgb(223, 159, 35);">当サイトでは築年数が10年以内の場合、家の法定長期保証の関係上、現在の家を建てた業者さん以外でのリフォームや増築をおすすめしておりません。</span>
                                <?=FormHelper::getRB4("contact", "build_year", $buildYears, $contact)?>
                            </div>
                        </td>
                    </tr>
                    <tr class="houseHolder" style="display:none">
                        <th valign="top">
                            <div style="display:none">建築内容 <img src="../vendors/req.gif" width="30px"style="float:right;"></div>
                        </th>
                        <td colspan="3" class="req">
                            <div style="display:none">
                                <?= isset($error['const_info']) ? $error['const_info'] : '' ?>
                                <?=FormHelper::getRB4("contact", "const_info", $buildType, $contact)?>
                                <span style="color:red; background:#fff9c3;">リフォーム・リノベーション希望の方は<a href="<?=SSL_URL?>renovefudosan/" target="_top" style="color:red;">こちら</a>をご利用ください。</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">建築予定地 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td colspan="3" class="req">
                            <?= isset($error['const_location_yes_no']) ? $error['const_location_yes_no'] : '' ?>
                            土地：<?=FormHelper::getRB5("contact", "const_location_yes_no", $vClass->constLocationYesNo)?>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">建築予定時期 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td class="req">
                            <?= isset($error['const_start']) ? $error['const_start'] : '' ?>
                            <?=FormHelper::getRB4("contact", "const_start", $construct_time, $contact)?>
                        </td>
                        <th valign="top">建築予算 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                        <td class="req">
                            <?= isset($error['const_budget']) ? $error['const_budget'] : '' ?>
                            <?=FormHelper::getRB4("contact", "const_budget", $construct_budget, $contact)?>
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">勤務先</th>
                        <td colspan="3"><?=FormHelper::getTF("contact", "work_location", $contact, 'tf')?>　（全角）
                        </td>
                    </tr>
                    <tr>
                        <th valign="top">その他ご要望</th>
                        <td colspan="3"><?=FormHelper::getTA("contact", "comment1", $contact)?></td>
                    </tr>
                </table>

                <div class="h3_wrap">
                    <h3>入力情報についてご確認ください</h3>
                </div>
                <div class="form_wrap">
                    <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                        <tr>
                            <th valign="top">当フォーム入力者情報とあなたとのご関係 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                            <td class="req">
                                <?= isset($error['relation']) ? $error['relation'] : '' ?>
                                <ul class="form_ul">
                                    <li><label><input type="radio" name="contact[relation]" value="ご本人" <?= isset($contact['relation']) && $contact['relation'] == 'ご本人' ? 'checked="checked"' : '' ?> />ご本人</label></li>
                                    <li><label><input type="radio" name="contact[relation]" value="配偶者" <?= isset($contact['relation']) && $contact['relation'] == '配偶者' ? 'checked="checked"' : '' ?> />配偶者</label></li>
                                    <li><label><input type="radio" name="contact[relation]" value="ご家族" <?= isset($contact['relation']) && $contact['relation'] == 'ご家族' ? 'checked="checked"' : '' ?> />ご家族</label></li>
                                    <li><label><input type="radio" name="contact[relation]" value="その他" <?= isset($contact['relation']) && $contact['relation'] == 'その他' ? 'checked="checked"' : '' ?> />その他</label>　 <?=FormHelper::getTF("contact", "relationetc", $contact)?>　<div style="float: right; margin-right: 20px; color: rgb(223, 159, 35);">ご関係と入力者様のお名前をご記入下さい<br>（例）甥　住宅 太郎</div></li>
                                </ul>
                            </td>
                        </tr>

                        <tr>
                            <th valign="top">注意事項 <img src="../vendors/req.gif" width="30px"style="float:right;"></th>
                            <td class="req">
                                <span style="color:red;">※ご記入内容に誤りはございませんか？</span>
                                <?= isset($error['agree']) ? $error['agree'] : '' ?>
                                <textarea class="ta" readonly="readonly">1.お問い合わせにご記入いただいた内容について当サイトよりご確認の連絡を差し上げる場合がありますので、予めご了承ください。
2.ご記入いただいた内容に不備がある場合、関係機関を通じて情報の確認や捜索等を行うことがありますので、ご記入内容に誤りがないか今一度ご確認ください。
3.同業者及びそれに準ずる方の御利用は固く禁じております。又、故意と思われる内容の錯誤（いたずら等）につきましては、所轄関係機関を通じての情報確認、及び法的手続きを行なう事がございます。</textarea>
                                <input type="checkbox" name="contact[agree]" value="同意" <?= isset($contact['agree']) &&  $contact['agree'] == '同意' ? 'checked="checked"' : '' ?>/>上記の注意事項を確認しました</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!--
                <div>
                    <div class="h3_wrap">
                        <h3>アンケート</h3>
                    </div>
                    <div>
                        <div style="margin:0 0 20px 0;"><img src="../images/teiansho3.png"></div>
                        <img src="../images/teiansho.png">
                    </div>
                    <table border="0" cellpadding="0" cellspacing="0" class="table_form">
                        <tr>
                            <th valign="top">建築予定の土地の大きさは何坪くらいですか？<br>※購入予定地でも結構です</th>
                            <td colspan="3">
                                <?= isset($error['option15']) ? $error['option15'] : '' ?>
                                <?=FormHelper::getRB4("contact", "option15", $option15, $contact)?>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top">ご希望の部屋数は何LDKですか？</th>
                            <td colspan="3">
                                <?= isset($error['option18']) ? $error['option18'] : '' ?>
                                <?=FormHelper::getRB4("contact", "option18", $option18, $contact)?>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top">打ち合わせ方法を選択してください</th>
                            <td colspan="3">
                                <?= isset($error['option31']) ? $error['option31'] : '' ?>
                                <p>※複数選択可</p>
                                <?=FormHelper::getCB1("contact", "option31", $option31, $contact)?><br/>
                            </td>
                        </tr>
                        <tr>
                            <th valign="top">打ち合わせを希望される日時をお聞かせください</th>
                            <td colspan="3">
                                <?= isset($error['option32']) ? $error['option32'] : '' ?>
                                日付<?= FormHelper::getTF("contact", "date", $contact, 'tf datepicker') ?>
                                <?= FormHelper::getDDL1("contact", "time", $time, $contact) ?>時頃
                            </td>
                        </tr>
                    </table>
                </div>
                -->


                <div class="bt_wrap">
                    <?=$vid ? $vClass->getHiddens("vid", $vid) : ''?>
                    <input type="hidden" name="city" value="<?php echo $cid?>"/>
                    <input type="hidden" name="province" value="<?php echo $pid?>"/>
                    <input type="hidden" name="action" value="confirm"/>
                    <input type="submit" name="confirm" value="　　入力内容を確認する　　" id="submitbt"
                        class="bt" onclick="flagConfirm=false;"/>
                </div>
                <div class="gs">
                    <span id="ss_img_wrapper_130-66_flash_ja">
                        <a href="https://jp.globalsign.com/" target=_blank>
                            <img alt="SSL　グローバルサインのサイトシール" border=0
                                id="ss_img" src="//seal.globalsign.com/SiteSeal/images/gs_noscript_130-66_ja.gif">
                        </a>
                    </span>
                    <script type="text/javascript" src="//seal.globalsign.com/SiteSeal/gs_flash_130-66_ja.js"></script>
                </div>
            </div>
    <?php endif; ?>

        </form>

    </div><!-- s_content// -->

<?php require_once TEMP_DIR.'/left2.php'; ?>

</div><!-- content_wrap// -->
</div><!-- wrap// -->
<?php require_once TEMP_DIR.'/footer.php'; ?>

<!-- リマーケティング タグの Google コード -->
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


</body>
</html>
