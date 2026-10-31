<?php

/******************************************
 * Created on : 2009/04/28                 *
 * Author     : Yoshinori Iketani          *
 * Copyright  : SkyAvy, Inc.               *
 * URL        : www.skyayv.com             *
 ******************************************/
require_once '../includes/config.php';

$vClass = new Vendors();
$pClass = new Provinces();
$cc = new Cities();
$error['flag'] = true;

$trim_inputs = array(
    'name',
    'name2',
    'name_read',
    'name_read2',
    'zip',
    'addr_city',
    'address_street',
    'address_room',
    'tel1',
    'tel2',
    'tel3',
    'age',
    'email'
);
foreach ($trim_inputs as $value) {
    if (isset($_POST['contact'][$value])) {
        $_POST['contact'][$value] = FormHelper::trimValue($_POST['contact'][$value]);
    }
}
if (
    $issetDate = isset($_POST['contact']['date'])
    || isset($_POST['contact']['time'])
) {
    if ($issetDate && isset($_POST['contact']['time'])) {
        $_POST['contact']['option32'] = $_POST['contact']['date'] . '日 ' . $_POST['contact']['time'] . '時頃';
    } else {
        if ($issetDate) {
            $_POST['contact']['option32'] = $_POST['contact']['date'] . '日';
        } else {
            $_POST['contact']['option32'] = $_POST['contact']['time'] . '時頃';
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
        "Content-Length: " . strlen($data),
    );

    $options = array(
        'http' => array(
            'method' => 'POST',
            "header" => implode("\r\n", $header),
            'content' => $data,
        )
    );

    $url_path = 'apis/mailgate/check';
    $postUrl = $isDev ? $dev . $url_path : $production . $url_path;
    //  $postUrl = 'http://controlpanel.dev.groundstep.jp/apis/mailgate/check';
    $response = file_get_contents($postUrl, false, stream_context_create($options));
    if ($response === false) {
        return null;
    }

    file_put_contents('./api_ajax_current.log', date('Y/m/d H:i:s') . " ---- \r\n" . $response);

    echo $response;
    return;
}

$contact = array();
if (!empty($_POST) && isset($_POST['action']) && $_POST['action'] == "complete" && !empty($_POST['submit'])) {
    $contact = $_POST['contact'];

    $contact['tel'] = $contact['tel1'] . '-' . $contact['tel2'] . '-' . $contact['tel3'];
    $contact['reemail'] = $contact['email'];

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
        //  print_r($vids);exit;
        $already = "";
        $brochure = "";
        for ($i = 0; $i < count($contact['already']); $i++) {
            $already .= $contact['already'][$i] . "、";
        }
        for ($i = 0; $i < count($contact['brochure']); $i++) {
            $brochure .= $contact['brochure'][$i] . "、";
        }
        for ($i = 0; $i < count($contact['option31']); $i++) {
            $option31 .= $contact['option31'][$i] . "、";
        }
        $contact['already'] = $already;
        $contact['brochure'] = $brochure;
        $contact['option31'] = $option31;

        session_start();
        if (isset($_SESSION['memo'])) {
            $contact['memo'] = $_SESSION['memo'];
        }

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

        $error = $vClass->sendQuoteMails($vids, $contact, "資料請求");
        // コンパネでのmb_send_mailのエラー文章も入ってしまうので必要な部分を切り出す
        $subsId = substr($GLOBALS['subsId'], -6);
        $http_params = array(
            'subsId' => $subsId
        );
        header('Location: ' . SSL_URL . 'vendors/quotes_send_complete_sp.php?' . http_build_query($http_params, '', '&'));

        unset($_SESSION['memo']);
        unset($_SESSION['ad_company']);
        unset($_SESSION['ad_code']);
        exit;
    }
} elseif (!empty($_POST) && isset($_POST['action']) && $_POST['action'] == "confirm") {
    @session_start();

    $contact = $_POST['contact'];

    $contact['tel'] = $contact['tel1'] . '-' . $contact['tel2'] . '-' . $contact['tel3'];
    $contact['reemail'] = $contact['email'];

    if (!empty($contact['address_street'])) {
        $addressStr = $contact['address_street'];
        if (!empty($contact['address_room']) && $contact['address_room'] !== 'なし') {
            $addressStr .= ' ' . $contact['address_room'];
        }
        $contact['address'] = $addressStr;
    }

    mb_regex_encoding(mb_internal_encoding());
    //  $contact['address'] = mb_ereg_replace('[－]+', '-', $contact['address']);

    $error = $vClass->checkQuotesForm($contact);

    if (empty($contact['tel1']) || empty($contact['tel2']) || empty($contact['tel3'])) {
        $error['tel'] = '<span class="error_text">TELを入力してください。</span>';
        $error['flag'] = true;
    } elseif (preg_match('/([0-9])\1{3,}/', $contact['tel1'] . $contact['tel2'] . $contact['tel3'])) {
        $error['tel'] = '<span class="error_text">TELを正しく入力してください。</span>';
        $error['flag'] = true;
    }

    // address_streetとaddress_roomの個別チェック
    if (empty($contact['address_street'])) {
        $error['address_street'] = '<span class="error_text">その他住所・番地を入力してください。</span>';
        $error['flag'] = true;
    }
    // 建物名・部屋番号は任意入力のためチェックなし
    // エラーがない場合、$_POSTも更新（確認画面用）
    if (!isset($error['flag']) || !$error['flag']) {
        if (!empty($contact['address_street']) && !empty($contact['address_room'])) {
            $_POST['contact']['address'] = $contact['address_street'] . ' ' . $contact['address_room'];
        }
    }
} elseif (!empty($_POST) && isset($_POST['action']) && $_POST['action'] == "complete" && !empty($_POST['back'])) {
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
        header('Location: ' . SSL_URL . 'vendors/quotes_search_sp.php');
        exit;
    } else {
        $pid = intval($_POST['province']);
    }
} else {
    $pid = intval($_GET['province']);
}
if (empty($_GET['quotes']['city']) || !is_numeric($_GET['quotes']['city'])) {
    if (empty($_POST['city']) || !is_numeric($_POST['city'])) {
        header('Location: ' . SSL_URL . 'vendors/quotes_search_sp.php');
        exit;
    } else {
        $cid = intval($_POST['city']);
    }
} else {
    $cid = intval($_GET['quotes']['city']);
}
$vid = null;
if (isset($_GET['vid'])) {
    $vid = $_GET['vid'];
} elseif (isset($_POST['vid'])) {
    $vid = $_POST['vid'];
}
// $provinceName = $pClass->getNameById($provindeId);
$cityName = $cc->getCityName($cid);

$method = array(
    "直接相談したい",
    "まずは電話で相談したい"
);
$place = array(
    "自宅まで来て欲しい",
    "展示場・ショールーム・貴社事務所の予約をしたい"
);
$brochure = array(
    "パンフレットや資料　（特長やこだわり、商品等）",
    "ショールーム・見学会・イベント等の情報が欲しい",
    "建築例の資料が見たい　（パンフレット・リーフレット・チラシ等）",
    "家づくりの進め方の分かる資料が欲しい　（パンフレット・リーフレット・チラシ等）"
);
$already = array(
    "既に契約している。契約に向けて打合せ進行中",
    "詳しい内容を知らなかったので今後検討してみたいと思った"
);
$family_num = array(
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    "10"
);
$construct_budget = array(
    "1000～2000万円",
    "2000～3000万円",
    "3000万円～",
    "まだ検討中"
);
$construct_time = array(
    "今すぐ",
    "半年以内",
    "1年以内",
    "1～2年以内",
    "2～3年以内",
    "検討中"
);
$houseType = array(
    "持ち家（本人名義）",
    "持ち家（親名義）",
    "マンション",
    "賃貸",
    "その他"
);
$buildYears = array(
    "10～15年",
    "15～20年",
    "20～25年",
    "25年以上"
);
$buildType = array(
    "建て替え希望",
    "別の土地で新築希望"
);
//$contactDates = array("月曜日", "火曜日","木曜日","金曜日","土曜日");
$contactDates = array("平日");
$contactTimes = array(
    "10:00～12:00",
    "12:00～14:00",
    "14:00～16:00",
    "16:00～18:00"
);
$provinces = array(
    "北海道",
    "青森県",
    "岩手県",
    "宮城県",
    "秋田県",
    "山形県",
    "福島県",
    "茨城県",
    "栃木県",
    "群馬県",
    "埼玉県",
    "千葉県",
    "東京都",
    "神奈川県",
    "新潟県",
    "富山県",
    "石川県",
    "福井県",
    "山梨県",
    "長野県",
    "岐阜県",
    "静岡県",
    "愛知県",
    "三重県",
    "滋賀県",
    "京都府",
    "大阪府",
    "兵庫県",
    "奈良県",
    "和歌山県",
    "鳥取県",
    "島根県",
    "岡山県",
    "広島県",
    "山口県",
    "徳島県",
    "香川県",
    "愛媛県",
    "高知県",
    "福岡県",
    "佐賀県",
    "長崎県",
    "熊本県",
    "大分県",
    "宮崎県",
    "鹿児島県",
    "沖縄県"
);
$option15 = array("20坪以下", "20～30坪", "30坪～40坪", "40坪～50坪", "50坪～60坪", "60坪～");
$option18 = array("２LDK", "３LDK", "４LDK", "５LDK", "２世帯住宅");
$option31 = array("建築会社での打合せを希望", "自宅での打合せを希望", "オンラインでの打合せを希望");
$time = array('8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21');

if ((isset($_GET['nologin']) && $_GET['nologin'] == "Y") || (isset($_POST['nologin']) && $_POST['nologin'] == "Y")) {
    $isBrochure = true;
    $pageTitle = META_VENDORS_QUOTES_SEND;
    $metaDescription = META_VENDORS_QUOTES_SEND_DESC;
    $metaKeywords = META_VENDORS_QUOTES_SEND_KEY;
    $bread = array(
        SSL_URL . "vendors/quotes_search.php?province=" . $pid => META_VENDORS_QUOTES_SEARCH,
        META_VENDORS_QUOTES_SEND
    );
} else {
    $isBrochure = false;
    $pageTitle = META_VENDORS_QUOTES_SEND;
    $metaDescription = META_VENDORS_QUOTES_SEND_DESC;
    $metaKeywords = META_VENDORS_QUOTES_SEND_KEY;
    $bread = array(
        SSL_URL . "vendors/quotes_search.php?province=" . $pid => META_VENDORS_QUOTES_SEARCH,
        META_VENDORS_QUOTES_SEND
    );
}
?>

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <title>資料一括請求｜ハウジングバザール</title>
    <meta name="viewport"
        content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="format-detection" content="telephone=no">
    <!-- Google Tag Manager -->
    <script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || [];
            w[l].push({
                'gtm.start': new Date().getTime(),
                event: 'gtm.js'
            });
            var f = d.getElementsByTagName(s)[0],
                j = d.createElement(s),
                dl = l != 'dataLayer' ? '&l=' + l : '';
            j.async = true;
            j.src =
                'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);
        })(window, document, 'script', 'dataLayer', 'GTM-NKDXW78');
    </script>
    <!-- End Google Tag Manager -->
    <script src="./jquery-1.8.2.min.js"></script>
    <script src="./jquery.validationEngine-ja.js" type="text/javascript" charset="utf-8"></script>
    <script src="./jquery.validationEngine.js" type="text/javascript" charset="utf-8"></script>
    <link rel="stylesheet" href="./validationEngine.jquery.css" type="text/css" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.js"></script>
    <link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/ui-lightness/jquery-ui.css"
        rel="stylesheet" />
    <script type="text/javascript"
        src="https://ajax.googleapis.com/ajax/libs/jqueryui/1/i18n/jquery.ui.datepicker-ja.min.js"></script>
    <script src="./ajaxzip2/ajaxzip2.js" charset="UTF-8"></script>
    <script type="text/javascript" src="js/zansu_ikkatu.js"></script>
    <!-- <script type="text/javascript" src="https://ajaxzip3.github.io/ajaxzip3.js" charset="UTF-8"></script> -->
    <script type="text/javascript" src="./mgform_grex.js"></script>
    <script type="text/javascript" src="js/jquery.autoKana.js"></script>
    <script src="js/jquery.email-autocomplete.js"></script>
    <script src="//statics.a8.net/a8sales/a8sales.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $("input[name='contact[zip]']").keyup(function () {
                AjaxZip2.zip2addr(this, 'contact[addr_province]', 'contact[addr_city]', null, 'contact[address]');
            });

            $("input[name='contact[const_status]']").click(function () {
                if ($(this).val().indexOf('持ち家') >= 0) {
                    $('.houseHolder').slideDown('slow');
                } else {
                    $('.houseHolder').hide();
                }
            });
            if ($("input[name='contact[const_status]']:checked").length > 0 &&
                $("input[name='contact[const_status]']:checked").val().indexOf('持ち家') >= 0) {
                $('.houseHolder').show();
            }
        });
        $(function () {
            // テキストボックスにフォーカス時、フォームの背景色を変化
            $('input, select, textarea')
                .focusin(function (e) {
                    $(this).css('background-color', '#CEF6F5');
                })
                .focusout(function (e) {
                    $(this).css('background-color', '');

                });
            $.fn.autoKana('#userName', '#userNameKana', {
                katakana: true //true：カタカナ、false：ひらがな（デフォルト）
            });
            $.fn.autoKana('#userName2', '#userNameKana2', {
                katakana: true //true：カタカナ、false：ひらがな（デフォルト）
            });
        });

        function nextField(i, n, m) {
            if (i.value.length >= m) {
                i.form.elements[n].focus();
            }
        }
        $(function () {
            var $win = $(window),
                $main = $('#main'),
                $nav = $('#statusbar'),
                navHeight = $nav.outerHeight(),
                navPos = $nav.offset().top,
                fixedClass = 'is-fixed';

            $win.on('load scroll', function () {
                var value = $(this).scrollTop();
                if (value > navPos) {
                    $nav.addClass(fixedClass);
                    $main.css('margin-top', navHeight);
                } else {
                    $nav.removeClass(fixedClass);
                    $main.css('margin-top', '0');
                }
            });
        });
        (function ($) {
            $(function () {
                $(".email").emailautocomplete({
                    domains: ["example.com"] //add your own domains
                });
            });
        }(jQuery));
        $(function () {
            $(".datepicker").datepicker();
        });
    </script>
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="stylesheet" href="css/styles.css" type="text/css">
    <link rel="stylesheet" href="css/form.css" type="text/css">
    <style>
        /* .req { background-color: #ffdede !important; } */
        input[name="contact[name]"],
        input[name="contact[name2]"],
        input[name="contact[name_read]"],
        input[name="contact[name_read2]"] {
            width: 8em;
        }

        input[name="contact[zip]"] {
            width: 7em;
        }

        input[name="contact[tel1]"],
        input[name="contact[tel2]"],
        input[name="contact[tel3]"] {
            width: 4em;
        }

        input[name="contact[age]"] {
            width: 3em;
        }

        body {
            overflow-x: hidden;
        }

        .profile h2 {
            box-sizing: border-box;
            width: 97%;
            color: #000;
            line-height: 1.2;
            font-size: 16px;
            border: none;
            border-bottom: solid 2px orange;
            margin: 5px;
            /* padding: 5px 5px; */
            margin-top: 2px;
            background-color: #fff;
        }

        .confirm h2 {
            box-sizing: border-box;
            width: 97%;
            color: #000;
            line-height: 1.2;
            font-size: 16px;
            border: none;
            /* border-bottom: solid 2px orange; */
            margin: 5px;
            /* padding: 5px 5px; */
            margin-top: 2px;
            background-color: #ffe5cc;
        }

        .req {
            background-color: #fff5de;
        }

        .require {
            box-sizing: border-box;
            padding: 6px;
            width: 4em;
            height: 27px;
            text-align: center;
            vertical-align: text-middle;
            color: #fff;
            background-color: #c00;
            font-weight: bold;
            font-size: 12px;
            -webkit-border-radius: 5px;
            border-radius: 5px;
        }

        .require2 {
            box-sizing: border-box;
            padding: 6px;
            float: right;
            margin-right: 10px;
            width: 4em;
            height: 27px;
            text-align: center;
            vertical-align: text-middle;
            background-color: #c00;
            color: #fff;
            font-weight: bold;
            font-size: 12px;
            -webkit-border-radius: 5px;
            border-radius: 5px;
        }

        .question {
            width: 290px;
        }

        #submitbutton {
            border: none;
            background: #00A7EA;
            color: #fff;
            cursor: pointer;
            border-radius: 0.3em;
            font-size: 30px;
            width: 300px;
        }

        #submitbutton :hover {
            background: #ccc;
        }

        .is-fixed {
            position: fixed !important;
            position: absolute;
            top: 0px;
            margin: auto;
            text-align: center;
            z-index: 999;
            width: 100%;
            float: right;
        }

        .profile {
            margin-top: 0px;
        }

        .email {
            width: 280px;
        }

        .formtextlines {
            border: 1px solid #000;
            /* 枠線 */
            border-radius: 0.67em;
            /* 角丸 */
            padding: 0.5em;
            /* 内側の余白量 */

            width: 100%;
            /* 横幅 */
            box-sizing: border-box;
            height: 160px;
            /* 高さ */
            font-size: 1em;
            /* 文字サイズ */
            line-height: 1.2;
            /* 行の高さ */
        }
    </style>


    <!--poiful-->
    <script>
        (function () {
            var uqid = "23eb1696991dbb77";
            var gid = "39";
            var a = document.createElement("script");
            a.dataset.uqid = uqid; a.dataset.gid = gid; a.id = "afadfpc-23eb1696991dbb77gid39-" + Date.now();
            a.src = "//ac.pointfun.jp/fpc/cookie_js.php?scriptId=" + encodeURIComponent(a.id);
            document.head.appendChild(a);
        })();
    </script>
    <!--poiful終わり-->


    <!-- LINE Tag Base Code -->
    <!-- Do Not Modify -->
    <script>
        (function (g, d, o) {
            g._ltq = g._ltq || []; g._lt = g._lt || function () { g._ltq.push(arguments) };
            var h = location.protocol === 'https:' ? 'https://d.line-scdn.net' : 'http://d.line-cdn.net';
            var s = d.createElement('script'); s.async = 1;
            s.src = o || h + '/n/line_tag/public/release/v1/lt.js';
            var t = d.getElementsByTagName('script')[0]; t.parentNode.insertBefore(s, t);
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

<body id="paperPage">


    <!--excrie-->
    <script>
        (function () {
            var uqid = "S10dX6bed35e454X";
            var gid = "621";

            var a = document.createElement("script");
            a.dataset.uqid = uqid; a.dataset.gid = gid; a.id = "afadfpc-" + uqid + "-" + Date.now();
            a.src = "//ac.dmtag.jp/fpc/cookie_js.php?scriptId=" + encodeURIComponent(a.id);
            document.body.appendChild(a);
        })();
    </script>

    <!--End excrie-->


    <!--フォームタグ-->
    <script type="text/javascript">
        window.ebSettings_2 = { eb_appId: 'ed7bb2c3c5eabd6cff34c4e3e3443cf6', eb_email: 'input[name="contact[email]"]' };
    </script>
    <script src="https://chasy.jp/static/js/starting.min.js"></script>
    <script type="text/javascript">
        window.ebSettings_2 = { eb_appId: '09657dabaebad73dec81804498296c5e', eb_tel: 'input[name="contact[tel1]"],input[name="contact[tel2]"],input[name="contact[tel3]"]' };
    </script>
    <script src="https://chasy.jp/static/js/starting.min.js"></script>
    <script type="text/javascript">
        window.ebSettings_2 = { eb_appId: '7e92e8d4eaba19bc263fef7c99ad353c', eb_name: 'input[name="contact[name]"],input[name="contact[name2]"]' };
    </script>
    <script src="https://chasy.jp/static/js/starting.min.js"></script>

    <?php
    // 2019.09.09 アクセス解析ツール設置
    require_once $_SERVER['DOCUMENT_ROOT'] . '/templates/scripts_after_body.html';
    ?>
    <!--
<p><a href="https://m.facebook.com/messages/compose?ids=146030258781161" target="_blank">Facebookから問い合わせ</a></p>
<p><a href="https://m.facebook.com/messages/compose?ids=146030258781161" target="_blank">Twitterから問い合わせ（工事中）</a></p>
-->

    <div id="container">
        <form action="" method="post" enctype="multipart/form-data">

            <?php if ((isset($error['flag']) && !$error['flag']) || !isset($error['flag'])): ?>
                <!-- ▼ profile ▼ -->
                <p style="margin-top:20px;font-size:130%;text-align:center;">
                    入力内容確認
                </p>
                <p style="text-align:center; font-size:125%; font-weight:600;">
                    お電話でも受け付けております<br />
                    <span style="color:#680618; font-size:135%;"><a href="tel:0436633015">0436-63-3015</a></span><br />
                    受付時間 10:00〜17:00<br />
                    （定休：水・日・祝祭日）
                </p>

                <div id="profile_">
                    <p class="red" style="text-align: center;color: red; margin: 10px;">
                        入力した内容を確認し、「送信」をクリックして下さい。入力した内容に間違いがある場合は、「戻る」をクリックして前のページにお戻り下さい。
                    </p>

                    <section class="profile">
                        <h2 class="confirm"><span class="question">資料請求</span><span>
                                <div class="require">必須</div>
                            </span></h2>
                        <div class="inner">
                            <?= FormHelper::getPostDoubleArrayCommaBrSeparate("contact", "brochure") ?><br />
                            <?php if (!empty($_POST['contact']['brochure_other'])): ?>
                                <br><br>
                                <div>
                                    その他 （上記の他にお求めの資料があれば、詳しいご希望をご入力下さい。）<br>
                                    （例）二世帯住宅のカタログがほしい。<br>
                                    省エネ住宅についての資料を下さい。<br>
                                </div>
                            </div>
                            <div class="inner">
                                <?= nl2br(FormHelper::getPostArrayDefault("contact", "brochure_other")) ?>
                            <?php endif; ?>
                        </div>

                        <h2 class="confirm"><span class="question">お名前</span><span>
                                <div class="require">必須</div>
                            </span></h2>
                        <div class="inner">
                            <div class="col">
                                <?= FormHelper::getPostArrayDefault("contact", "name") ?>
                                <?= FormHelper::getPostArrayDefault("contact", "name2") ?>
                            </div>
                            <div class="col">
                                <?= FormHelper::getPostArrayDefault("contact", "name_read") ?>
                                <?= FormHelper::getPostArrayDefault("contact", "name_read2") ?>
                            </div>
                        </div>

                        <h2 class="confirm"><span>ご住所</span><span>
                                <div class="require">必須</div>
                            </span></h2>
                        <div class="inner">
                            <dl>
                                <dt><span class="bold">郵便番号</span> 半角数字　ハイフン無し</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "zip") ?></dd>


                                <dt><span class="bold">都道府県</span> 全角</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "addr_province") ?></dd>


                                <dt><span class="bold">市区町村・その他住所</span> 全角</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "addr_city") ?></dd>


                                <dt><span class="bold">その他住所・番地</span>　全角</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "address_street") ?></dd>

                                <dt><span class="bold">建物名・部屋番号</span>　全角</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "address_room") ?></dd>
                            </dl>
                        </div>

                        <h2 class="confirm"><span class="question">連絡先</span><span>
                                <div class="require">必須</div>
                            </span></h2>
                        <div class="inner">
                            <dl>
                                <dt><span class="bold">メールアドレス</span>　半角英数字</dt>
                                <dd class="email">
                                    <?= FormHelper::getPostArrayDefault("contact", "email") ?>
                                </dd>
                                <dt><span class="bold">電話番号</span>　半角数字</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "tel1") ?>-<?= FormHelper::getPostArrayDefault("contact", "tel2") ?>-<?= FormHelper::getPostArrayDefault("contact", "tel3") ?>
                                </dd>
                            </dl>
                        </div>

                        <h2><span class="question">ユーザー様情報</span><span>
                                <div class="require">必須</div>
                            </span></h2>
                        <div class="inner">
                            <dl>
                                <dt><span class="bold">年齢</span>　半角数字</dt>
                                <dd><?php $age = FormHelper::getPostArrayDefault("contact", "age"); ?>
                                    <?php if (!empty($age)): ?>
                                        <?= FormHelper::getPostArrayDefault("contact", "age") ?>才
                                    <?php endif; ?>
                                </dd>

                                <dt><span class="bold">家族人数</span>　半角数字</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "family") ?></dd>
                            </dl>
                        </div>

                        <h2 class="confirm"><span class="question">建設予定について</span><span>
                                <div class="require">必須</div>
                            </span></h2>
                        <div class="inner">
                            <dl>
                                <dt><span class="bold">現在のお住まい</span></dt>
                                <dd><?= nl2br(FormHelper::getPostArrayDefault("contact", "const_status")) ?>&nbsp;<?= FormHelper::getPostArrayDefault("contact", "house_type") ?>
                                    </dt>
                                    <?php if (strpos($contact['const_status'], '持ち家') !== false) { ?>
                                    <dt><span class="bold">築年数</span></dt>
                                    <dd><?= nl2br(FormHelper::getPostArrayDefault("contact", "build_year")) ?>&nbsp;</dd>
                                    <dt><span class="bold">建築内容</span></dt>
                                    <dd><?= nl2br(FormHelper::getPostArrayDefault("contact", "const_info")) ?>&nbsp;</dd>
                                <?php } ?>
                                <dt><span class="bold">建築用の土地の有無</span></dt>
                                <dd><?= $vClass->constLocationYesNo[FormHelper::getPostArrayDefault("contact", "const_location_yes_no")] ?>
                                </dd>
                                <dt><span class="bold">建築予定時期</span></dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "const_start") ?>&nbsp;</dd>
                                <dt><span class="bold">建築予算</span></dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "const_budget") ?></dd>
                            </dl>
                        </div>

                        <h2 class="confirm"><span>その他</span></h2>
                        <div class="inner">
                            <dl>
                                <dt><span class="bold">勤務先</span>　全角</dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "work_location") ?></dd>
                                <dt><span class="bold">その他ご要望</dt>
                                <dd><?= nl2br(FormHelper::getPostArrayDefault("contact", "comment1")) ?></dd>
                            </dl>
                        </div>

                        <h2 class="confirm">
                            <span class="question">当フォーム入力者情報とあなたとのご関係</span><span>
                                <div class="require">必須</div>
                            </span>
                        </h2>
                        <div class="inner">
                            <?= FormHelper::getPostArrayDefault("contact", "relation") ?>
                            <?= FormHelper::getPostArrayDefault("contact", "relationetc") ?>
                        </div>

                        <h2 class="confirm"><span>注意事項</span><span class="require">必須</span></h2>
                        <div class="inner">
                            <span class="error_text">※ご記入内容に誤りはございませんか？</span>
                            <?= isset($error['agree']) ? $error['agree'] : '' ?>
                            <textarea class="ta" readonly="readonly" rows="4" style="width:100%">
            1.お問い合わせにご記入いただいた内容について当サイトよりご確認の連絡を差し上げる場合がありますので、予めご了承ください。
            2.ご記入いただいた内容に不備がある場合、関係機関を通じて情報の確認や捜索等を行うことがありますので、ご記入内容に誤りがないか今一度ご確認ください。
            3.同業者及びそれに準ずる方の御利用は固く禁じております。又、故意と思われる内容の錯誤（いたずら等）につきましては、所轄関係機関を通じての情報確認、及び法的手続きを行なう事がございます。
                        </textarea>
                            <div>
                                <td>同意</td>
                            </div>

                            <p style="margin-top:20px;color:#ff3300;">送信ボタンをタップした後、送信完了までに時間がかかる場合があります。<br />
                        送信ボタンをタップ後、完了画面に切り替わるまでしばらくお待ち下さい。</p>

                        </div>

                        <!--
                        <div>
                            <p style="color:#ff0000;font-weight:bold;text-align:center;">無料でもらえる！住まいの提案書を<br />取得してみませんか？
                            </p>
                            <p>建築会社のパンフレットだけでは、どれ位の建築予算がかかるのか、
                                希望の間取りができるのかなどは、分からないものです。
                                あなたの考えや希望を書いて、住まいの提案書を取得してみませんか。<br />
                                <br />
                                家づくりが、<span style="font-size:120%;font-weight:bold;">ぐっと</span>現実に近づく、あなただけの住まいの提案書作成
                                は家づくりを、より現実的なものに引き寄せてくれます。<br />
                                <br />
                                もちろん、提案書は無料で取得できますし、提案書をもらったから
                                その会社で建てないといけないなんてことはありません。<br />
                                <br />
                                私共では、<b>【提案書を頂いた会社様への断り代行】</b>もしています。
                                今まで累計で８万人以上活用しているサービスですので
                                ご安心してご活用ください。
                            </p>
                        </div>
                        <div class="inner">
                            <dl>
                                <dt><span class="bold">建築予定の土地の大きさは何坪くらいですか？<br>※購入予定地でも結構です</span></dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "option15") ?></dd>
                                <dt><span class="bold">ご希望の部屋数は何LDKですか？</span></dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "option18") ?></dd>
                                <dt><span class="bold">打ち合わせ方法を選択してください</span></dt>
                                <dd><?= FormHelper::getPostDoubleArrayCommaBrSeparate("contact", "option31") ?></dd>
                                <dt><span class="bold">打ち合わせを希望される日時をお聞かせください</span></dt>
                                <dd><?= FormHelper::getPostArrayDefault("contact", "option32") ?></dd>
                            </dl>
                        </div>
                                    -->


                    </section>

                    <div class="submit">
                        <?= FormHelper::getHiddens("contact", $contact) ?>
                        <?= $vClass->getHiddens("vid", $vid) ?>
                        <input type="hidden" name="city" value="<?php echo $cid ?>" />
                        <input type="hidden" name="province" value="<?php echo $pid ?>" />
                        <input type="hidden" name="action" value="complete" />
                        <input type="submit" name="submit" value="　　送信　　" class="bt"
                            onclick='if(typeof pressed != "undefined"){return false;}pressed=1;' id="submitbutton" />
                    </div>
                </div><!-- ▲ profile ▲ -->

            <?php else: ?>
                <p
                    style="margin: 10px; padding: 15px; text-align: center; font-size: 110%; font-weight:500; background: #F57C00;color: #fff;font-weight:bold;">
                    一括資料請求フォーム【無料】
                </p>

                <div style="width:100%; background:#fff; border-bottom: 3px solid grey; margin-bottom:5px; text-align: center; margin:auto;"
                    id="statusbar">
                    <div class="fixheader" style="margin: auto; width:90%; margin-top:10px">
                        <div class="bar"
                            style="width: 100%; height: 15px; border: solid 1px #ccc; border-radius: 7px; margin: auto;">
                            <p style="width:100%; height:15px;background:skyblue; border-radius: 7px;"
                                id="restCheckBoxPercent"></p>
                        </div>
                        <p class="message">
                        <div class="number" style="width: 100%;text-align: center; margin: 7px 0px; color: #007fff;">.number
                        </div>
                        </p>
                    </div>
                </div>

                <div id="main">

                    <!-- ▼ profile ▼ -->
                    <div id="profile_">

                        <section class="profile">
                            <h2 class="confirm"><span class="question">資料請求</span><span>
                                    <div class="require">必須</div>
                                </span></h2>
                            <div class="inner">
                                <div style="background-color: #fff5de;">
                                    <?= isset($error['brochure']) ? $error['brochure'] : '' ?>
                                    <?= FormHelper::getCB1("contact", "brochure", $brochure, $contact) ?>
                                </div>
                                <br>
                                <div>
                                    その他 （上記の他にお求めの資料があれば、詳しいご希望をご入力下さい。）<br>
                                    （例）二世帯住宅のカタログがほしい。<br>
                                    省エネ住宅についての資料を下さい。<br>
                                </div>
                            </div>
                            <div class="inner">
                                <?= FormHelper::getTA("contact", "brochure_other", $contact) ?>
                            </div>

                            <h2 class="confirm"><span class="question">お名前</span><span>
                                    <div class="require">必須</div>
                                </span></h2>
                            <div class="inner">
                                <div class="col">
                                    <label><span class="bold">姓　</span>
                                        全角<br><?= FormHelper::getTF("contact", "name", $contact, 'tf req') ?></label>
                                    <label><span class="bold">名　</span>
                                        全角<br><?= FormHelper::getTF("contact", "name2", $contact, 'tf req') ?></label>
                                </div>
                                <?= isset($error['name']) ? $error['name'] : '' ?>
                                <?= isset($error['name2']) ? $error['name2'] : '' ?>
                                <span id="contactname_error" class="error_text"></span><span id="contactname2_error"
                                    class="error_text"></span>
                                <div class="col">
                                    <label><span class="bold">セイ</span>
                                        全角カタカナ<br><?= FormHelper::getTF("contact", "name_read", $contact, 'tf req') ?></label>
                                    <label><span class="bold">メイ</span>
                                        全角カタカナ<br><?= FormHelper::getTF("contact", "name_read2", $contact, 'tf req') ?></label>
                                </div>
                                <?= isset($error['name_read']) ? $error['name_read'] : '' ?>
                                <?= isset($error['name_read2']) ? $error['name_read2'] : '' ?>
                                <span id="contactname_read_error" class="error_text"></span><span
                                    id="contactname_read2_error" class="error_text"></span>
                            </div>

                            <h2 class="confirm"><span class="question">ご住所</span><span>
                                    <div class="require">必須</div>
                                </span></h2>
                            <div class="inner">
                                <dl>
                                    <dt><span class="bold">郵便番号</span> 半角数字 ハイフン無し<br /><span
                                            style="color:#696969;">（例）2900056</span></dt>
                                    <dd><?= isset($error['zip']) ? $error['zip'] : '' ?><?= FormHelper::getTF("contact", "zip", $contact, 'tf req') ?>
                                        <span id="contactzip_error" class="error_text"></span>
                                    </dd>

                                    <dt><span class="bold">都道府県</span></dt>
                                    <dd><?= isset($error['addr_province']) ? $error['addr_province'] : '' ?><?= FormHelper::getDDL1("contact", "addr_province", $provinces, $contact, 'req') ?><span
                                            style="color:#696969;"></dd>

                                    <dt><span class="bold">市区町村・その他住所</span> 全角<br /><span
                                            style="color:#696969;">（例）市原市五井</span></dt>
                                    <dd><?= isset($error['addr_city']) ? $error['addr_city'] : '' ?><?= FormHelper::getTF("contact", "addr_city", $contact, 'tf req') ?>
                                    </dd>

                                    <dt><span class="bold">その他住所・番地</span>　全角<br /><span
                                            style="color:#696969;">（例）2437-2</span></dt>
                                    <dd><?= isset($error['address_street']) ? $error['address_street'] : '' ?><?= FormHelper::getTF("contact", "address_street", $contact, 'tf req') ?>
                                    </dd>

                                    <dt><span class="bold">建物名・部屋番号</span>　全角<br /><span
                                            style="color:#696969;">（例）ホマレヤハイツ301</span></dt>
                                    <dd><?= isset($error['address_room']) ? $error['address_room'] : '' ?><?= FormHelper::getTF("contact", "address_room", $contact, 'tf') ?>
                                        <p style="color:#0000ff; padding-top:8px;">＊持ち家や戸建て賃貸で部屋番号がない場合は<br />「なし」とご入力下さい。
                                        </p>
                                    </dd>
                                </dl>
                            </div>

                            <h2 class="confirm"><span class="question">連絡先</span><span>
                                    <div class="require">必須</div>
                                </span></h2>
                            <div class="inner">
                                <dl>
                                    <dt><span class="bold">メールアドレス</span>　半角英数字</dt>
                                    <dd class="email">
                                        <?= isset($error['email']) ? $error['email'] : '' ?>
                                        <?= FormHelper::getTF("contact", "email", $contact, 'tf email req') ?>
                                        <span id="contactemail_error" class="error_text"></span>
                                    </dd>

                                    <dt><span class="bold">電話番号</span>　半角数字</dt>
                                    <dd><?= isset($error['tel']) ? $error['tel'] : '' ?><?= FormHelper::getTF("contact", "tel1", $contact, 'tf req') ?>-<?= FormHelper::getTF("contact", "tel2", $contact, 'tf req') ?>-<?= FormHelper::getTF("contact", "tel3", $contact, 'tf req') ?>
                                   

                                    </dd>
                                    <span id="contacttel1_error" class="error_text"></span><span id="contacttel2_error"
                                        class="error_text"></span><span id="contacttel3_error" class="error_text"></span>
                                </dl>
                            </div>

                            <h2 class="confirm"><span class="question">ユーザー様情報</span><span>
                                    <div class="require">必須</div>
                                </span></h2>
                            <div class="inner">
                                <dl>
                                    <dt><span class="bold">年齢</span>　半角数字</dt>
                                    <dd><?= isset($error['age']) ? $error['age'] : '' ?><?= FormHelper::getTF("contact", "age", $contact, 'tf req') ?>
                                        才</dd>

                                    <dt><span class="bold">家族人数</span>　半角数字</dt>
                                    <dd><?= isset($error['family']) ? $error['family'] : '' ?>
                                        <?= FormHelper::getDDL1("contact", "family", $family_num, $contact, 'req') ?>
                                    </dd>
                                </dl>
                            </div>

                            <h2 class="confirm"><span class="question">建設予定について</span><span>
                                    <div class="require">必須</div>
                                </span></h2>

                            <div class="inner">
                                <div style="background-color: #fff5de;">
                                    <dl>
                                        <dt><span class="bold">現在のお住まい</span></dt>
                                        <dd><?= isset($error['const_status']) ? $error['const_status'] : '' ?>
                                            <?= FormHelper::getRB4("contact", "const_status", $houseType, $contact) ?>
                                            <?= FormHelper::getTF("contact", "house_type", $contact, 'tf') ?>
                                        </dd>
                                        <dt class="houseHolder" style="display:none"><span class="bold">築年数</span></dt>
                                        <dd class="houseHolder" style="display:none">
                                            <?= isset($error['build_year']) ? $error['build_year'] : '' ?>
                                            <span
                                                style="color: rgb(223, 159, 35);">当サイトでは築年数が10年以内の場合、家の法定長期保証の関係上、現在の家を建てた業者さん以外でのリフォームや増築をおすすめしておりません。</span>
                                            <?= FormHelper::getRB4("contact", "build_year", $buildYears, $contact) ?>
                                        </dd>
                                        <dt class="houseHolder" style="display:none"><span class="bold">建築内容</span></dt>
                                        <dd class="houseHolder" style="display:none">
                                            <?= isset($error['const_info']) ? $error['const_info'] : '' ?>
                                            <?= FormHelper::getRB4("contact", "const_info", $buildType, $contact) ?>
                                            <span style="color:red; background:#fff9c3;">リフォーム・リノベーション希望の方は<a
                                                    href="<?= SSL_URL ?>renovefudosan/" target="_top"
                                                    style="color:red;">こちら</a>をご利用ください。</span>
                                        </dd>
                                        <dt><span class="bold">建築用の土地の有無</span></dt>
                                        <dd><?= isset($error['const_location_yes_no']) ? $error['const_location_yes_no'] : '' ?>
                                            <?= FormHelper::getRB5("contact", "const_location_yes_no", $vClass->constLocationYesNo) ?>
                                        </dd>
                                        <dt><span class="bold">建築予定時期</span></dt>
                                        <dd><?= isset($error['const_start']) ? $error['const_start'] : '' ?>
                                            <?= FormHelper::getRB4("contact", "const_start", $construct_time, $contact) ?>
                                        </dd>
                                        <dt><span class="bold">建築予算</span></dt>
                                        <dd><?= isset($error['const_budget']) ? $error['const_budget'] : '' ?>
                                            <?= FormHelper::getRB4("contact", "const_budget", $construct_budget, $contact) ?>
                                        </dd>
                                    </dl>
                                </div>
                            </div>

                            <h2 class="confirm"><span>その他</span></h2>
                            <div class="inner">
                                <dl>
                                    <dt><span class="bold">勤務先</span>　全角</dt>
                                    <dd><?= FormHelper::getTF("contact", "work_location", $contact) ?></dd>
                                    <dt><span class="bold">その他ご要望</span></dt>
                                    <dd><?= FormHelper::getTA("contact", "comment1", $contact) ?></dd>
                                </dl>
                            </div>

                            <h2><span class="question">当フォーム入力者情報とあなたとのご関係</span><span>
                                    <div class="require">必須</div>
                                </span></h2>

                            <div class="inner" style="background-color: #fff5de;height:110px;">
                                <?= isset($error['relation']) ? $error['relation'] : '' ?>

                                <ul style="float: left; width: auto;">
                                    <?php
                                    $relations = array(
                                        'ご本人',
                                        '配偶者',
                                        'ご家族',
                                        'その他'
                                    );
                                    foreach ($relations as $value) {
                                        $line = '<li>';
                                        if (isset($contact['relation'])) {
                                            $line .= '<label><input type="radio" name="contact[relation]" value="' . $value . '" ';
                                            $line .= $contact['relation'] == $value ? 'checked="checked"' : '';
                                            $line .= '/>' . $value . '</label>';
                                        } else {
                                            $line .= '<label><input type="radio" name="contact[relation]" value="' . $value . '" />' . $value . '</label>';
                                        }
                                        echo $line . '</li>';
                                    }
                                    ?>
                                </ul>
                            </div>

                            <div id="relation_text">
                                <div style="width: auto; float: right; margin: 0 0 0 10px;">
                                    「その他」を選ばれた場合は、ご本人との関係・入力者様のお名前をご入力下さい。<br>（例）友人　住宅 太郎</div>

                                <?= FormHelper::getTF("contact", "relationetc", $contact) ?>
                            </div>



                            <h2><span class="question">注意事項</span><span>
                                    <div class="require">必須</div>
                                </span></h2>
                            <div class="inner">
                                <span class="error_text">※ご記入内容に誤りはございませんか？</span>
                                <?= isset($error['agree']) ? $error['agree'] : '' ?>
                                <textarea class="ta" readonly="readonly" rows="4" style="width:90%">
            1.お問い合わせにご記入いただいた内容について当サイトよりご確認の連絡を差し上げる場合がありますので、予めご了承ください。
            2.ご記入いただいた内容に不備がある場合、関係機関を通じて情報の確認や捜索等を行うことがありますので、ご記入内容に誤りがないか今一度ご確認ください。
            3.同業者及びそれに準ずる方の御利用は固く禁じております。又、故意と思われる内容の錯誤（いたずら等）につきましては、所轄関係機関を通じての情報確認、及び法的手続きを行なう事がございます。
                        </textarea>
                                <div>
                                    <?php
                                    $isAgree = false;
                                    if (isset($contact['agree']) && $contact['agree'] == '同意') {
                                        $isAgree = true;
                                    }
                                    ?>
                                    <label style="cursor: pointer;">
                                        <input type="checkbox" name="contact[agree]" value="同意" <?php echo $isAgree ? 'checked="checked"' : ''; ?> />
                                        <span>上記の注意事項を確認しました</span>
                                    </label>
                                </div>
                            </div>

                            <!--
                            <div class="inner">
                                <div>
                                    <p style="font-size:120%;color:#ff0000;font-weight:bold;text-align:center;">
                                        無料でもらえる！住まいの提案書を<br />取得してみませんか？</p>
                                    <p>建築会社のパンフレットだけでは、どれ位の建築予算がかかるのか、
                                        希望の間取りができるのかなどは、分からないものです。
                                        あなたの考えや希望を書いて、住まいの提案書を取得してみませんか。<br />
                                        <br />
                                        家づくりが、<span
                                            style="font-size:120%;font-weight:bold;">ぐっと</span>現実に近づく、あなただけの住まいの提案書作成
                                        は家づくりを、より現実的なものに引き寄せてくれます。<br />
                                        <br />
                                        もちろん、提案書は無料で取得できますし、提案書をもらったから
                                        その会社で建てないといけないなんてことはありません。<br />
                                        <br />
                                        私共では、<b>【提案書を頂いた会社様への断り代行】</b>もしています。
                                        今まで累計で８万人以上活用しているサービスですので
                                        ご安心してご活用ください。
                                    </p>
                                    <div style="margin:10px 0 20px 0;"><img src="../images/teiansho3.png" width="100%">
                                    </div>
                                </div>
                                <dl>
                                    <dt><span class="bold">建築予定の土地の大きさは何坪くらいですか？<br>※購入予定地でも結構です</span></dt>
                                    <dd>
                                        <?= isset($error['option15']) ? $error['option15'] : '' ?>
                                        <?= FormHelper::getRB4("contact", "option15", $option15, $contact) ?>
                                    </dd>
                                    <dt><span class="bold">ご希望の部屋数は何LDKですか？</span></dt>
                                    <dd>
                                        <?= isset($error['option18']) ? $error['option18'] : '' ?>
                                        <?= FormHelper::getRB4("contact", "option18", $option18, $contact) ?>
                                    </dd>
                                    <dt><span class="bold">打ち合わせ方法を選択してください</span></dt>
                                    <dd>
                                        <?= isset($error['option31']) ? $error['option31'] : '' ?>
                                        <?= FormHelper::getCB1("contact", "option31", $option31, $contact) ?>
                                    </dd>
                                    <dt><span class="bold">打ち合わせを希望される日時をお聞かせください</span></dt>
                                    <dd>
                                        <?= isset($error['option32']) ? $error['option32'] : '' ?>
                                        日付<?= FormHelper::getTF("contact", "date", $contact, 'tf datepicker') ?>
                                        <?= FormHelper::getDDL1("contact", "time", $time, $contact) ?>時頃
                                    </dd>
                                </dl>
                                <div>
                                    <p style="color:#ff0000;">イタズラ、調査目的での不正利用防止の為、事務局から連絡が行く場合が御座います。予め御了承ください。<br>
                                        ＊不正利用と判断した場合には、法的処置を講じることも御座います。</p>
                                </div>
                            </div>
                                -->


                        </section>

                        <div class="submit">
                            <?= $vClass->getHiddens("vid", $vid) ?>
                            <input type="hidden" name="city" value="<?php echo $cid ?>" />
                            <input type="hidden" name="province" value="<?php echo $pid ?>" />
                            <input type="hidden" name="action" value="confirm" />
                            <input id="submitbutton" type="submit" name="check" value="入力内容を確認する">
                        </div>
                    </div><!-- ▲ profile ▲ -->
                </div><!-- ▲ main ▲ -->
            <?php endif; ?>

        </form>

    </div>

    <footer class="global-footer">
        <p class="copyright"><small>&nbsp;</small></p>
    </footer>

    <!--script type="text/javascript" src="js/sp.js"></script -->
    <?php require_once TEMP_DIR . '/sp_footer_scripts.php'; ?>



    <!--DENOO-->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var _CTIDV = "chrjwj5kemom";
            var _DATA = { "args": "<?php echo $_GET['subsId']; ?>" };
            var sc = document.createElement("script");
            sc.id = _CTIDV; sc.async = true;
            sc.src = "https://platinum.denoo.co.jp/tag.php?c=" + _CTIDV + "&url=" + encodeURIComponent(location.href) + "&ref=" + encodeURIComponent(document.referrer) + "&data=" + encodeURIComponent(JSON.stringify(_DATA));
            document.body.appendChild(sc);
        });
    </script>
    <!--終了-->






</body>

</html>