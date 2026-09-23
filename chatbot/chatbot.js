/*!
 * ハウジングバザール 一括資料請求チャットボット
 *
 * 既存の資料請求フォーム（quotes_send2.php）を、チャット形式で入力できるようにするスクリプト。
 * チャットで受け取った回答を元のフォームの同じ項目に書き込み、最後に元の
 * 「入力内容を確認する」ボタンを押すだけなので、受信側（PHP・DB・メール）は変更不要。
 *
 * 設置方法は README.md を参照。質問の内容・順番は下の STEPS を編集する。
 */
(function () {
  'use strict';

  /* ================================================================
   * 設定
   * ================================================================ */
  var CONFIG = {
    title: '一括資料請求はこちらから！',
    launcherLabel: '一括資料請求',
    icon: 'https://ui.ugchatform.net/sgs/files/grex/chatform/icon/guide2_icon2.png',
    frameName: 'sendform',        // 元のフォームが表示される iframe の name
    searchFormName: 'form1',      // 地域選択フォームの name
    submitName: 'confirm',        // 元のフォームの「入力内容を確認する」ボタンの name
    storageKey: 'hb_chatbot_v1',  // 入力途中の回答を保存するキー（ページ再読み込み対策）
    autoOpenDelay: 1500,          // ページ表示から自動で開くまでの時間（ミリ秒）。0 で自動では開かない
    cvKey: 'hb_chatbot_cv',       // チャット経由で送信したことを完了画面に伝える印（chatbot-complete.js が読む）

    // 表示のしかた
    //   'inline' : ページの中にチャットを大きく表示し、元の地域選択・フォームは見えなくする（裏では動いている）
    //   'popup'  : 右下に小さな窓で表示する
    mode: 'inline',
    mountBefore: 'form[name="form1"]',                          // inline の時、チャットを置く場所（この要素の直前）
    hideInInline: ['form[name="form1"]', 'iframe[name="sendform"]'], // inline の時に見えなくする要素
    fallbackLabel: 'チャットではなく、フォームで入力したい方はこちら',
    backLabel: 'チャットで入力する'
  };

  var PREFS = ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県', '茨城県', '栃木県', '群馬県',
    '埼玉県', '千葉県', '東京都', '神奈川県', '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
    '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県', '鳥取県', '島根県',
    '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県', '熊本県',
    '大分県', '宮崎県', '鹿児島県', '沖縄県'];

  /*
   * 質問の一覧（上から順に表示）
   *
   *   bot     : ボットの吹き出し（HTML 可）
   *   fields  : 入力欄。name は元のフォームの name 属性と同じにする
   *   type    : checkbox / radio / select / text / textarea / agree / area（地域選択）
   *   showIf  : 表示条件。'visible' なら元のフォームでその項目が表示されている時だけ聞く
   *   when    : 入力欄ごとの表示条件。{ name: 別の欄の name, in: [値...] } の答えの時だけ表示する
   */
  var STEPS = [
    {
      id: 'brochure',
      bot: ['ご訪問ありがとうございます。<div>こちらで<span class="hbc-red">一括資料請求のご案内</span>をいたします！</div>',
        '最初に、ご要望の資料について選択してください！'],
      fields: [
        { name: 'contact[brochure][]', type: 'checkbox', required: true, unlessFilled: 'contact[brochure_other]', options: [
          'パンフレットや資料　（特長やこだわり、商品等）',
          'ショールーム・見学会・イベント等の情報が欲しい',
          '建築例の資料が見たい　（パンフレット・リーフレット・チラシ等）',
          '家づくりの進め方の分かる資料が欲しい　（パンフレット・リーフレット・チラシ等）'] },
        { name: 'contact[brochure_other]', type: 'textarea', label: 'その他(上記の他にお求めの資料があれば、ご入力下さい。)',
          placeholder: '上記の他にお求めの資料があれば、ご入力下さい。',
          note: '(例)二世帯住宅のカタログがほしい<br>　省エネ住宅についての資料を下さい。' }
      ]
    },
    {
      id: 'area',
      type: 'area',
      bot: ['建築予定地、またはご希望の地域を選択してください。<div>その地域の工務店へ資料請求します。</div>']
    },
    {
      id: 'name',
      bot: ['お名前を教えてください。'],
      fields: [
        { name: 'contact[name]', type: 'text', label: '姓', required: true, placeholder: '住宅', half: true },
        { name: 'contact[name2]', type: 'text', label: '名', required: true, placeholder: '太郎', half: true },
        { name: 'contact[name_read]', type: 'text', label: 'セイ', required: true, placeholder: 'ジュウタク', half: true, rule: 'kana' },
        { name: 'contact[name_read2]', type: 'text', label: 'メイ', required: true, placeholder: 'タロウ', half: true, rule: 'kana' }
      ]
    },
    {
      id: 'address',
      bot: ['{name}様、ありがとうございます。<div>資料のお届け先のご住所を教えてください。</div>'],
      fields: [
        { name: 'contact[zip]', type: 'text', label: '郵便番号（ハイフン無し）', required: true, placeholder: '2900056', rule: 'zip', inputmode: 'numeric' },
        { name: 'contact[addr_province]', type: 'select', label: '都道府県', required: true, options: PREFS },
        { name: 'contact[addr_city]', type: 'text', label: '市区町村', required: true, placeholder: '市原市' },
        { name: 'contact[address]', type: 'text', label: 'その他住所・番地', required: true, placeholder: '五井２４３７−２' },
        { name: 'contact[address_room]', type: 'text', label: '建物名・部屋番号', placeholder: 'ホマレヤハイツ１０１',
          note: '＊持ち家や戸建て賃貸で部屋番号がない場合は「なし」とご入力下さい。' }
      ]
    },
    {
      id: 'tel',
      bot: ['お電話番号を教えてください。'],
      fields: [
        { name: 'contact[tel1]', type: 'text', required: true, placeholder: '090', rule: 'num', inputmode: 'numeric', tel: true },
        { name: 'contact[tel2]', type: 'text', required: true, placeholder: '1234', rule: 'num', inputmode: 'numeric', tel: true },
        { name: 'contact[tel3]', type: 'text', required: true, placeholder: '5678', rule: 'tel', inputmode: 'numeric', tel: true }
      ]
    },
    {
      id: 'email',
      bot: ['メールアドレスを教えてください。<div>（携帯メールアドレスも可）</div>'],
      fields: [
        { name: 'contact[email]', type: 'text', label: 'Emailアドレス', required: true, rule: 'email', inputmode: 'email', placeholder: 'example@housingbazar.jp' },
        { name: 'contact[reemail]', type: 'text', label: 'Emailアドレス（確認のため再入力）', required: true, rule: 'reemail', inputmode: 'email' }
      ]
    },
    {
      id: 'profile',
      bot: ['あなたについて教えてください。'],
      fields: [
        { name: 'contact[age]', type: 'text', label: '年齢（才）', required: true, rule: 'age', inputmode: 'numeric', half: true },
        { name: 'contact[family]', type: 'select', label: '住む予定の家族人数（将来の予定含む）', required: true, half: true,
          options: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'], suffix: '人' },
        { name: 'contact[work_location]', type: 'text', label: '勤務先（任意）' }
      ]
    },
    {
      // 元のフォームで「建築後のご利用用途」欄が表示される条件の時だけ聞く
      id: 'const_use',
      showIf: { name: 'contact[family]', in: ['1'] },   // quotes_send.php が家族人数1人の時に必須にしている
      bot: ['建築後のご利用用途をお知らせ下さい。'],
      fields: [
        { name: 'contact[const_use]', type: 'radio', required: true, options: ['別荘として使用', '自宅として入居', '投資・資産運用の為', 'その他'] },
        { name: 'contact[const_use_etc]', type: 'text', label: 'その他の場合はご記入ください',
          when: { name: 'contact[const_use]', in: ['その他'] } }
      ]
    },
    {
      id: 'plan',
      bot: ['家づくりのご計画について教えてください。'],
      fields: [
        { name: 'contact[const_status]', type: 'radio', label: '現在のお住まい', required: true,
          options: ['持ち家（本人名義）', '持ち家（親名義）', 'マンション', '賃貸', 'その他'] },
        { name: 'contact[house_type]', type: 'text', label: '現在のお住まい（その他）', required: true,
          when: { name: 'contact[const_status]', in: ['その他'] } },
        { name: 'contact[build_year]', type: 'radio', label: '築年数', required: true,
          when: { name: 'contact[const_status]', in: ['持ち家（本人名義）', '持ち家（親名義）'] },
          note: '当サイトでは築年数が10年以内の場合、家の法定長期保証の関係上、現在の家を建てた業者さん以外でのリフォームや増築をおすすめしておりません。',
          options: ['10～15年', '15～20年', '20～25年', '25年以上'] },
        { name: 'contact[const_info]', type: 'radio', label: '建築内容', required: true,
          when: { name: 'contact[const_status]', in: ['持ち家（本人名義）', '持ち家（親名義）'] },
          options: ['建て替え希望', '別の土地で新築希望'] },
        { name: 'contact[const_location_yes_no]', type: 'radio', label: '建築予定地（土地）', required: true,
          options: [{ value: '1', text: '有り' }, { value: '2', text: '無し' }] },
        { name: 'contact[const_start]', type: 'radio', label: '建築予定時期', required: true,
          options: ['今すぐ', '半年以内', '1年以内', '1～2年以内', '2～3年以内', '検討中'] },
        { name: 'contact[const_budget]', type: 'radio', label: '建築予算', required: true,
          options: ['1000～2000万円', '2000～3000万円', '3000万円～', 'まだ検討中'] }
      ]
    },
    {
      id: 'confirm',
      bot: ['最後の質問です！<div>入力された情報について確認させてください。</div>'],
      fields: [
        { name: 'contact[relation]', type: 'radio', label: '当フォーム入力者情報とあなたとのご関係', required: true,
          options: ['ご本人', '配偶者', 'ご家族', 'その他'] },
        { name: 'contact[relationetc]', type: 'text', label: 'ご関係と入力者様のお名前', placeholder: '友人　住宅　太郎', required: true,
          when: { name: 'contact[relation]', in: ['その他'] } },
        { name: 'contact[comment1]', type: 'textarea', label: 'その他ご要望（任意）' },
        { name: 'contact[agree]', type: 'agree', value: '同意', required: true, label: '上記の注意事項を確認しました',
          note: '1.お問い合わせにご記入いただいた内容について当サイトよりご確認の連絡を差し上げる場合がありますので、予めご了承ください。<br>' +
            '2.ご記入いただいた内容に不備がある場合、関係機関を通じて情報の確認や捜索等を行うことがありますので、ご記入内容に誤りがないか今一度ご確認ください。<br>' +
            '3.同業者及びそれに準ずる方の御利用は固く禁じております。又、故意と思われる内容の錯誤（いたずら等）につきましては、所轄関係機関を通じての情報確認、及び法的手続きを行なう事がございます。' }
      ]
    }
  ];

  var MESSAGES = {
    required: 'この項目は必須です',
    kana: '全角カタカナで入力してください',
    zip: '7桁の数字で入力してください',
    num: '数字で入力してください',
    tel: '電話番号を正しく入力してください',
    email: 'メールアドレスの形式が正しくありません',
    reemail: 'メールアドレスが一致しません',
    age: '年齢を数字で入力してください',
    area: '地域を選択してください',
    finish: 'ご入力ありがとうございました！<div>下のボタンから<span class="hbc-red">確認画面</span>へお進みください。</div>',
    submit: '入力内容を確認する',
    notFound: '申し訳ございません。フォームの読み込みに失敗しました。ページを再読み込みしてお試しください。'
  };

  /* ================================================================
   * 計測（Googleタグマネージャー / Googleアナリティクス）
   *   イベント名: hb_chatbot_open（開いた） / hb_chatbot_step（各質問に進んだ） / hb_chatbot_submit（確認画面へ）
   * ================================================================ */
  function track(name, params) {
    params = params || {};
    try {
      window.dataLayer = window.dataLayer || [];
      var ev = { event: 'hb_chatbot_' + name };
      for (var k in params) ev[k] = params[k];
      window.dataLayer.push(ev);
      if (typeof window.gtag === 'function') window.gtag('event', 'hb_chatbot_' + name, params);
    } catch (e) { /* 計測に失敗してもチャットは止めない */ }
  }

  /* ================================================================
   * 元のフォームとのやりとり
   * ================================================================ */
  function formDoc() {
    var frame = document.querySelector('iframe[name="' + CONFIG.frameName + '"]');
    if (frame) {
      try {
        var d = frame.contentDocument;
        if (d && d.getElementsByName('contact[name]').length) return d;
      } catch (e) { /* 別ドメインの場合は触れない */ }
      return null;
    }
    return document.getElementsByName('contact[name]').length ? document : null;
  }

  function fire(el, type) {
    var ev;
    try { ev = new Event(type, { bubbles: true }); } catch (e) { ev = document.createEvent('HTMLEvents'); ev.initEvent(type, true, true); }
    el.dispatchEvent(ev);
  }

  function writeField(doc, name, value) {
    var els = doc.getElementsByName(name);
    if (!els.length) return;
    var values = Array.isArray(value) ? value : [value == null ? '' : String(value)];
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      if (el.type === 'checkbox' || el.type === 'radio') {
        var on = values.indexOf(el.value) !== -1;
        if (el.checked !== on) { el.checked = on; fire(el, 'click'); }
        fire(el, 'change');
      } else {
        el.value = values[0] || '';
        fire(el, 'input');
        fire(el, 'change');
        if (el.tagName === 'SELECT') fire(el, 'click');   // quotes_send2.php は家族人数を click で判定している
      }
    }
  }

  function writeStep(doc, step) {
    (step.fields || []).forEach(function (f) {
      if (f.name in state.answers) writeField(doc, f.name, state.answers[f.name]);
    });
  }

  function writeAll(doc) {
    STEPS.forEach(function (s) { if (!isSkipped(s)) writeStep(doc, s); });
  }

  function isVisible(el) {
    for (var n = el; n && n.nodeType === 1; n = n.parentNode) {
      var st = n.ownerDocument.defaultView.getComputedStyle(n);
      if (st.display === 'none' || st.visibility === 'hidden') return false;
    }
    return true;
  }

  function isSkipped(step) {
    if (step.type === 'area') return !searchForm();
    if (step.showIf && step.showIf.name) {
      return step.showIf.in.indexOf(state.answers[step.showIf.name]) === -1;
    }
    if (step.showIf && step.showIf.visible) {
      var doc = formDoc();
      if (!doc) return true;
      var el = doc.getElementsByName(step.showIf.visible)[0];
      return !el || !isVisible(el);
    }
    return false;
  }

  function searchForm() {
    return document.forms[CONFIG.searchFormName] || null;
  }

  function waitFor(test, timeout, cb) {
    var start = Date.now();
    (function loop() {
      if (test()) return cb(true);
      if (Date.now() - start > timeout) return cb(false);
      setTimeout(loop, 150);
    })();
  }

  /* ================================================================
   * 入力チェック
   * ================================================================ */
  function toHalf(s) {
    return String(s).replace(/[０-９Ａ-Ｚａ-ｚ＠．＿－ー−]/g, function (c) {
      if (c === 'ー' || c === '−' || c === '－') return '-';
      return String.fromCharCode(c.charCodeAt(0) - 0xFEE0);
    });
  }

  function normalize(f, v) {
    if (f.rule === 'zip' || f.rule === 'num' || f.rule === 'tel' || f.rule === 'age') return toHalf(v).replace(/[^0-9]/g, '');
    if (f.rule === 'email' || f.rule === 'reemail') return toHalf(v).trim();
    if (f.rule === 'kana') return v.replace(/[ぁ-ゖ]/g, function (c) { return String.fromCharCode(c.charCodeAt(0) + 0x60); }).trim();
    return typeof v === 'string' ? v.trim() : v;
  }

  function check(f, v, values) {
    var empty = Array.isArray(v) ? !v.length : !v;
    if (empty && f.unlessFilled && values[f.unlessFilled]) return '';   // 資料はチェックか「その他」の記入のどちらかでよい
    if (empty) return f.required ? MESSAGES.required : '';
    switch (f.rule) {
      case 'kana': return /^[ァ-ヶー　 ]+$/.test(v) ? '' : MESSAGES.kana;
      case 'zip': return /^\d{7}$/.test(v) ? '' : MESSAGES.zip;
      case 'num': return /^\d+$/.test(v) ? '' : MESSAGES.num;
      case 'tel': {
        // quotes_send.php と同じ判定：0始まり10〜11桁、携帯・IP電話（020/050/070/080/090）は11桁
        if (!/^\d+$/.test(v)) return MESSAGES.num;
        var t1 = values['contact[tel1]'] || '', all = t1 + (values['contact[tel2]'] || '') + v;
        if (!/^0\d{9,10}$/.test(all)) return MESSAGES.tel;
        var mobile = ['020', '050', '070', '080', '090'].indexOf(t1) !== -1;
        return (mobile ? all.length === 11 : all.length === 10) ? '' : MESSAGES.tel;
      }
      case 'email': return /^\w+([-+.\w]+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/.test(v) ? '' : MESSAGES.email;   // Vendors.class.php と同じ
      case 'reemail': return v === values['contact[email]'] ? '' : MESSAGES.reemail;
      case 'age': return /^\d{1,3}$/.test(v) && +v > 0 && +v < 130 ? '' : MESSAGES.age;
    }
    return '';
  }

  /* ================================================================
   * 画面
   * ================================================================ */
  var state = { answers: {}, index: 0, area: null };
  var ui = {};

  function h(tag, attrs, html) {
    var el = document.createElement(tag);
    for (var k in attrs || {}) {
      if (k === 'class') el.className = attrs[k];
      else if (k === 'text') el.textContent = attrs[k];
      else el.setAttribute(k, attrs[k]);
    }
    if (html != null) el.innerHTML = html;
    return el;
  }

  function esc(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function save() {
    try { sessionStorage.setItem(CONFIG.storageKey, JSON.stringify(state)); } catch (e) { /* 保存できなくても動作は継続 */ }
  }

  function load() {
    try {
      var s = JSON.parse(sessionStorage.getItem(CONFIG.storageKey) || 'null');
      if (s && s.answers) state = s;
    } catch (e) { /* 無視 */ }
  }

  function build() {
    if (CONFIG.mode === 'inline' && document.querySelector(CONFIG.mountBefore)) return buildInline();
    CONFIG.mode = 'popup';
    ui.launcher = h('button', { class: 'hbc-launcher', type: 'button' },
      '<img src="' + CONFIG.icon + '" alt=""><span>' + esc(CONFIG.launcherLabel) + '</span>');
    ui.win = h('div', { class: 'hbc-window', role: 'dialog', 'aria-label': CONFIG.title });
    ui.win.innerHTML =
      '<div class="hbc-header"><img src="' + CONFIG.icon + '" alt=""><span class="hbc-title">' + esc(CONFIG.title) + '</span>' +
      '<button type="button" class="hbc-close" aria-label="閉じる">×</button></div>' +
      '<div class="hbc-progress"><div class="hbc-progress-bar"></div><div class="hbc-progress-label"></div></div>' +
      '<div class="hbc-body"></div>';
    ui.body = ui.win.querySelector('.hbc-body');
    ui.bar = ui.win.querySelector('.hbc-progress-bar');
    ui.label = ui.win.querySelector('.hbc-progress-label');
    ui.launcher.addEventListener('click', open);
    ui.win.querySelector('.hbc-close').addEventListener('click', close);
    document.body.appendChild(ui.launcher);
    document.body.appendChild(ui.win);
  }

  // ページ内に埋め込む表示。元の地域選択とフォームは画面の外へ移すだけで、消さない（裏で使うため）
  function buildInline() {
    var mount = document.querySelector(CONFIG.mountBefore);
    ui.wrap = h('div', { class: 'hbc-inline-wrap' });
    ui.win = h('div', { class: 'hbc-window hbc-inline', role: 'region', 'aria-label': CONFIG.title });
    ui.win.innerHTML =
      '<div class="hbc-header"><img src="' + CONFIG.icon + '" alt=""><span class="hbc-title">' + esc(CONFIG.title) + '</span></div>' +
      '<div class="hbc-progress"><div class="hbc-progress-bar"></div><div class="hbc-progress-label"></div></div>' +
      '<div class="hbc-body"></div>';
    ui.body = ui.win.querySelector('.hbc-body');
    ui.bar = ui.win.querySelector('.hbc-progress-bar');
    ui.label = ui.win.querySelector('.hbc-progress-label');
    var toForm = h('button', { type: 'button', class: 'hbc-switch' }, esc(CONFIG.fallbackLabel));
    ui.wrap.appendChild(ui.win);
    ui.wrap.appendChild(toForm);
    mount.parentNode.insertBefore(ui.wrap, mount);

    var toChat = h('button', { type: 'button', class: 'hbc-switch hbc-hidden' }, esc(CONFIG.backLabel));
    mount.parentNode.insertBefore(toChat, mount);

    var hidden = [];
    CONFIG.hideInInline.forEach(function (sel) {
      document.querySelectorAll(sel).forEach(function (el) { el.classList.add('hbc-offscreen'); hidden.push(el); });
    });
    toForm.addEventListener('click', function () {
      hidden.forEach(function (el) { el.classList.remove('hbc-offscreen'); });
      ui.wrap.classList.add('hbc-hidden');
      toChat.classList.remove('hbc-hidden');
      track('fallback');
    });
    toChat.addEventListener('click', function () {
      hidden.forEach(function (el) { el.classList.add('hbc-offscreen'); });
      ui.wrap.classList.remove('hbc-hidden');
      toChat.classList.add('hbc-hidden');
      ui.wrap.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }

  function open() {
    if (!ui.win.classList.contains('hbc-open')) track('open');
    ui.win.classList.add('hbc-open');
    if (ui.launcher) ui.launcher.classList.add('hbc-hidden');
    if (!ui.body.childNodes.length) replay();
  }

  function close() {
    if (CONFIG.mode === 'inline') return;
    ui.win.classList.remove('hbc-open');
    ui.launcher.classList.remove('hbc-hidden');
  }

  function scrollDown() {
    setTimeout(function () { ui.body.scrollTop = ui.body.scrollHeight; }, 30);
  }

  // 入力欄が長い時も、質問の吹き出しが画面から切れないようにする
  function scrollToQuestion(firstBotRow) {
    setTimeout(function () {
      var top = firstBotRow.offsetTop - 10;
      ui.body.scrollTop = Math.min(top, ui.body.scrollHeight);
    }, 40);
  }

  function botSay(html) {
    var row = h('div', { class: 'hbc-row hbc-left' });
    row.appendChild(h('img', { class: 'hbc-icon', src: CONFIG.icon, alt: '' }));
    row.appendChild(h('div', { class: 'hbc-balloon' }, html.replace('{name}', esc(state.answers['contact[name]'] || ''))));
    ui.body.appendChild(row);
    scrollDown();
    return row;
  }

  function userSay(html, stepIndex) {
    var row = h('div', { class: 'hbc-row hbc-right' });
    var b = h('div', { class: 'hbc-balloon hbc-answer' }, html);
    var edit = h('button', { type: 'button', class: 'hbc-edit' }, '修正する');
    edit.addEventListener('click', function () { goTo(stepIndex); });
    b.appendChild(edit);
    row.appendChild(b);
    ui.body.appendChild(row);
    scrollDown();
  }

  function visibleSteps() {
    return STEPS.filter(function (s) { return !s.showIf; });
  }

  function progress() {
    var list = visibleSteps();
    var done = 0;
    for (var i = 0; i < state.index && i < STEPS.length; i++) if (!STEPS[i].showIf) done++;
    var left = list.length - done - 1;   // 今の質問を除いた残り（元のチャットボットと同じ数え方）
    ui.bar.style.width = Math.max(4, (done + 1) / (list.length + 1) * 100) + '%';
    ui.label.textContent = done >= list.length ? '入力完了' : left > 0 ? 'あと' + left + '問' : '最後の質問です';
  }

  // 最初から現在の質問までを描き直す（再読み込み・修正時）
  function replay() {
    ui.body.innerHTML = '';
    for (var i = 0; i < state.index && i < STEPS.length; i++) {
      if (state.skipped && state.skipped[STEPS[i].id]) continue;
      STEPS[i].bot.forEach(botSay);
      userSay(summary(STEPS[i]), i);
    }
    show();
  }

  function goTo(i) {
    state.index = i;
    save();
    replay();
  }

  function show() {
    progress();
    if (state.index >= STEPS.length) return finish();
    var step = STEPS[state.index];
    state.skipped = state.skipped || {};
    if (isSkipped(step)) {
      state.skipped[step.id] = true;
      (step.fields || []).forEach(function (f) { delete state.answers[f.name]; });
      state.index++;
      save();
      return show();
    }
    delete state.skipped[step.id];
    var first = null;
    step.bot.forEach(function (m) { var r = botSay(m); first = first || r; });
    ui.questionRow = first;
    if (step.type === 'area') renderArea(step);
    else renderCard(step);
  }

  function next() {
    var s = STEPS[state.index];
    if (s) track('step', { chatbot_step: state.index + 1, chatbot_step_id: s.id });
    state.index++;
    save();
    show();
  }

  /* ---------- 通常の質問カード ---------- */
  function renderCard(step) {
    var row = h('div', { class: 'hbc-row hbc-right' });
    var card = h('div', { class: 'hbc-card' });
    var grid = h('div', { class: 'hbc-grid' });
    var inputs = {};

    step.fields.forEach(function (f) {
      var box = h('div', { class: 'hbc-field' + (f.half ? ' hbc-half' : '') + (f.tel ? ' hbc-tel' : '') });
      var saved = state.answers[f.name];
      if (f.label && f.type !== 'agree') box.appendChild(h('label', { class: 'hbc-label' }, esc(f.label) + (f.required ? ' <span class="hbc-req">必須</span>' : '')));

      if (f.type === 'checkbox' || f.type === 'radio') {
        var list = h('div', { class: 'hbc-options' + (f.type === 'radio' ? ' hbc-radio' : '') });
        f.options.forEach(function (o) {
          var val = typeof o === 'string' ? o : o.value;
          var text = typeof o === 'string' ? o : o.text;
          var on = Array.isArray(saved) ? saved.indexOf(val) !== -1 : saved === val;
          var lab = h('label', { class: 'hbc-option' + (on ? ' hbc-on' : '') });
          var inp = h('input', { type: f.type, name: 'hbc-' + f.name, value: val });
          inp.checked = on;
          inp.addEventListener('change', function () {
            list.querySelectorAll('.hbc-option').forEach(function (l) { l.classList.toggle('hbc-on', l.firstChild.checked); });
            validate(false);
          });
          lab.appendChild(inp);
          lab.appendChild(document.createTextNode(text));
          list.appendChild(lab);
        });
        box.appendChild(list);
        inputs[f.name] = function () {
          var v = [];
          list.querySelectorAll('input:checked').forEach(function (i) { v.push(i.value); });
          return f.type === 'radio' ? (v[0] || '') : v;
        };
      } else if (f.type === 'agree') {
        if (f.note) box.appendChild(h('div', { class: 'hbc-terms' }, f.note));
        var al = h('label', { class: 'hbc-option hbc-agree' + (saved ? ' hbc-on' : '') });
        var ai = h('input', { type: 'checkbox' });
        ai.checked = !!saved;
        ai.addEventListener('change', function () { al.classList.toggle('hbc-on', ai.checked); validate(false); });
        al.appendChild(ai);
        al.appendChild(document.createTextNode(f.label));
        box.appendChild(al);
        inputs[f.name] = function () { return ai.checked ? f.value : ''; };
      } else if (f.type === 'select') {
        var sel = h('select', { class: 'hbc-input' });
        sel.appendChild(h('option', { value: '' }, '選択してください'));
        f.options.forEach(function (o) { sel.appendChild(h('option', { value: o }, esc(o) + (f.suffix || ''))); });
        sel.value = saved || (f.name === 'contact[addr_province]' && state.area ? state.area.prefName : '') || '';
        sel.addEventListener('change', function () { validate(false); });
        box.appendChild(sel);
        inputs[f.name] = function () { return sel.value; };
      } else {
        var el = h(f.type === 'textarea' ? 'textarea' : 'input', { class: 'hbc-input', placeholder: f.placeholder || '' });
        if (f.type !== 'textarea') el.type = 'text';
        if (f.type === 'textarea') el.rows = 3;
        if (f.inputmode) el.setAttribute('inputmode', f.inputmode);
        el.value = saved || '';
        el.addEventListener('input', function () { validate(false); });
        el.addEventListener('blur', function () { validate(true); });
        box.appendChild(el);
        inputs[f.name] = function () { return el.value; };
      }

      if (f.note && f.type !== 'agree') box.appendChild(h('div', { class: 'hbc-note' }, f.note));
      box.appendChild(h('div', { class: 'hbc-error' }));
      grid.appendChild(box);
    });

    card.appendChild(grid);
    var btns = h('div', { class: 'hbc-buttons' });
    var ok = h('button', { type: 'button', class: 'hbc-next' }, '次へ');
    btns.appendChild(ok);
    card.appendChild(btns);
    row.appendChild(card);
    ui.body.appendChild(row);
    scrollToQuestion(ui.questionRow);

    function active(f) {
      if (!f.when) return true;
      var v = inputs[f.when.name] ? inputs[f.when.name]() : state.answers[f.when.name];
      return f.when.in.indexOf(v) !== -1;
    }

    // 条件に合わない欄は空にして送る（前に入れた値が残らないように）
    function collect() {
      var values = {};
      step.fields.forEach(function (f) {
        values[f.name] = active(f) ? normalize(f, inputs[f.name]()) : (f.type === 'checkbox' ? [] : '');
      });
      return values;
    }

    // showErrors=false の時はボタンの有効/無効だけ切り替える
    function validate(showErrors) {
      var values = collect();
      var okAll = true;
      step.fields.forEach(function (f, i) {
        var on = active(f);
        grid.children[i].style.display = on ? '' : 'none';
        var msg = on ? check(f, values[f.name], values) : '';
        if (msg) okAll = false;
        var errEl = grid.children[i].querySelector('.hbc-error');
        if (showErrors || !msg) errEl.textContent = showErrors ? msg : '';
      });
      ok.disabled = !okAll;
      return okAll ? values : null;
    }

    ok.addEventListener('click', function () {
      var values = validate(true);
      if (!values) return;
      for (var k in values) state.answers[k] = values[k];
      // 条件付きの質問（表示・非表示）を判定できるよう、回答をすぐ元のフォームにも反映
      var doc = formDoc();
      if (doc) writeStep(doc, step);
      row.remove();
      userSay(summary(step), state.index);
      next();
    });

    validate(false);
  }

  function summary(step) {
    if (step.type === 'area') return state.area ? esc(state.area.prefName + ' ' + state.area.cityName) : '';
    var parts = [];
    step.fields.forEach(function (f) {
      var v = state.answers[f.name];
      if (v == null || v === '' || (Array.isArray(v) && !v.length)) return;
      if (f.type === 'agree') return parts.push('注意事項を確認しました');
      if (f.options && typeof f.options[0] === 'object') {
        f.options.forEach(function (o) { if (o.value === v) v = o.text; });
      }
      var text = Array.isArray(v) ? v.map(function (x) { return '・' + esc(x); }).join('<br>') : esc(v) + (f.suffix || '');
      parts.push(f.label && f.type !== 'checkbox' ? '<span class="hbc-sum-label">' + esc(f.label) + '</span>' + text : text);
    });
    if (step.id === 'tel') return esc([state.answers['contact[tel1]'], state.answers['contact[tel2]'], state.answers['contact[tel3]']].join('-'));
    if (step.id === 'name') {
      return esc(state.answers['contact[name]'] + ' ' + state.answers['contact[name2]'] + '（' +
        state.answers['contact[name_read]'] + ' ' + state.answers['contact[name_read2]'] + '）');
    }
    return parts.join('<br>');
  }

  /* ---------- 地域選択（ページ上の地域選択フォームを操作） ---------- */
  function renderArea(step) {
    var form = searchForm();
    var srcPref = form.elements['province'];
    var srcCity = form.elements['quotes[city]'];
    var row = h('div', { class: 'hbc-row hbc-right' });
    var card = h('div', { class: 'hbc-card' });
    card.innerHTML =
      '<div class="hbc-field"><label class="hbc-label">都道府県 <span class="hbc-req">必須</span></label><select class="hbc-input hbc-pref"></select></div>' +
      '<div class="hbc-field"><label class="hbc-label">市区町村 <span class="hbc-req">必須</span></label><select class="hbc-input hbc-city" disabled><option value="">先に都道府県を選択してください</option></select></div>' +
      '<div class="hbc-error"></div><div class="hbc-buttons"><button type="button" class="hbc-next" disabled>次へ</button></div>';
    var pref = card.querySelector('.hbc-pref');
    var city = card.querySelector('.hbc-city');
    var ok = card.querySelector('.hbc-next');
    var err = card.querySelector('.hbc-error');
    copyOptions(srcPref, pref);
    row.appendChild(card);
    ui.body.appendChild(row);
    scrollToQuestion(ui.questionRow);

    function loadCities(keep) {
      city.disabled = true;
      ok.disabled = true;
      if (!pref.value) return;
      var before = optionKey(srcCity);
      srcPref.value = pref.value;
      fire(srcPref, 'change');
      city.innerHTML = '<option value="">読み込み中…</option>';
      // 市区町村の一覧はページ側で都道府県に合わせて差し替わるので、それを待ってから写す
      waitFor(function () { return optionKey(srcCity) !== before; }, 3000, function () {
        copyOptions(srcCity, city);
        city.disabled = false;
        if (keep) city.value = keep;
        ok.disabled = !city.value;
      });
    }

    pref.addEventListener('change', function () { loadCities(null); });
    city.addEventListener('change', function () { ok.disabled = !city.value; });

    if (state.area) {
      pref.value = state.area.pref;
      if (srcPref.value === pref.value) { copyOptions(srcCity, city); city.disabled = false; city.value = state.area.city; ok.disabled = !city.value; }
      else loadCities(state.area.city);
    }

    ok.addEventListener('click', function () {
      if (!pref.value || !city.value) { err.textContent = MESSAGES.area; return; }
      ok.disabled = true;
      ok.textContent = '読み込み中…';
      srcPref.value = pref.value;
      srcCity.value = city.value;
      state.area = {
        pref: pref.value, city: city.value,
        prefName: pref.options[pref.selectedIndex].text,
        cityName: city.options[city.selectedIndex].text
      };
      // 地域フォームを送信 → 下の iframe に、その地域の工務店と元のフォームが表示される
      var frame = document.querySelector('iframe[name="' + CONFIG.frameName + '"]');
      var loaded = false;
      if (frame) frame.addEventListener('load', function onLoad() { loaded = true; frame.removeEventListener('load', onLoad); });
      if (frame) quietFrame(frame);
      var btn = form.querySelector('input[type="image"], input[type="submit"], button[type="submit"]');
      if (btn) btn.click(); else form.submit();
      waitFor(function () { return loaded && formDoc(); }, 15000, function (found) {
        if (!found) { err.textContent = MESSAGES.notFound; ok.disabled = false; ok.textContent = '次へ'; return; }
        writeAll(formDoc());   // 地域を変えて読み込み直した場合、それまでの回答を入れ直す
        row.remove();
        userSay(summary(step), state.index);
        next();
      });
    });
  }

  // mgform.js の「入力が完了していません」確認を、チャットによる読み込み直しでは出さない
  function quietFrame(frame) {
    try { frame.contentWindow.ignorePageConfirm = true; frame.contentWindow.isChanged = false; } catch (e) { /* 無視 */ }
  }

  function optionKey(sel) {
    var s = '';
    for (var i = 0; i < sel.options.length; i++) s += sel.options[i].value + '|';
    return s;
  }

  function copyOptions(from, to) {
    to.innerHTML = '';
    for (var i = 0; i < from.options.length; i++) {
      var o = from.options[i];
      to.appendChild(h('option', { value: o.value }, esc(o.value ? o.text : '選択してください')));
    }
  }

  /* ---------- 最後：元のフォームの確認ボタンを押す ---------- */
  function finish() {
    botSay(MESSAGES.finish);
    var row = h('div', { class: 'hbc-row hbc-right' });
    var card = h('div', { class: 'hbc-card' });
    var err = h('div', { class: 'hbc-error' });
    var btn = h('button', { type: 'button', class: 'hbc-next hbc-submit' }, esc(MESSAGES.submit));
    card.appendChild(err);
    card.appendChild(btn);
    row.appendChild(card);
    ui.body.appendChild(row);
    scrollDown();

    btn.addEventListener('click', function () {
      btn.disabled = true;
      ensureArea(function () { btn.disabled = false; submitForm(); });
    });

    function submitForm() {
      var doc = formDoc();
      if (!doc) { err.textContent = MESSAGES.notFound; return; }
      writeAll(doc);
      var submit = doc.getElementsByName(CONFIG.submitName)[0];
      if (!submit) { err.textContent = MESSAGES.notFound; return; }
      btn.disabled = true;
      btn.textContent = '送信中…';
      try { sessionStorage.removeItem(CONFIG.storageKey); } catch (e) { /* 無視 */ }
      // 完了画面で「チャット経由の申込み」と分かるように印を残す（1時間で無効）
      try { localStorage.setItem(CONFIG.cvKey, String(Date.now())); } catch (e) { /* 無視 */ }
      track('submit');
      submit.click();   // 元フォームの「入力内容を確認する」→ 確認画面へ
    }
  }

  // ページを再読み込みした場合などに、選んだ地域のフォームが表示されているか確認し、違えば読み込み直す
  function ensureArea(cb) {
    var form = searchForm();
    var frame = document.querySelector('iframe[name="' + CONFIG.frameName + '"]');
    if (!form || !frame || !state.area) return cb();
    var url = '';
    try { url = decodeURIComponent(frame.contentWindow.location.search); } catch (e) { /* 無視 */ }
    if (formDoc() && url.indexOf('province=' + state.area.pref + '&') !== -1 && url.indexOf('[city]=' + state.area.city + '&') !== -1) return cb();
    var srcPref = form.elements['province'];
    var srcCity = form.elements['quotes[city]'];
    var before = optionKey(srcCity);
    srcPref.value = state.area.pref;
    fire(srcPref, 'change');
    waitFor(function () { return optionKey(srcCity) !== before || srcPref.value === state.area.pref; }, 3000, function () {
      waitFor(function () { for (var i = 0; i < srcCity.options.length; i++) if (srcCity.options[i].value === state.area.city) return true; return false; }, 3000, function () {
        srcCity.value = state.area.city;
        var loaded = false;
        frame.addEventListener('load', function onLoad() { loaded = true; frame.removeEventListener('load', onLoad); });
        quietFrame(frame);
        var b = form.querySelector('input[type="image"], input[type="submit"], button[type="submit"]');
        if (b) b.click(); else form.submit();
        waitFor(function () { return loaded && formDoc(); }, 15000, cb);
      });
    });
  }

  /* ================================================================
   * 起動
   * ================================================================ */
  function init() {
    if (!searchForm() && !formDoc() && !document.querySelector('iframe[name="' + CONFIG.frameName + '"]')) return;
    load();
    build();
    if (CONFIG.mode === 'inline' || state.index > 0) open();
    else if (CONFIG.autoOpenDelay > 0 && window.innerWidth > 768) setTimeout(open, CONFIG.autoOpenDelay);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  window.HBChatbot = { open: open, close: close, reset: function () { state = { answers: {}, index: 0, area: null }; save(); replay(); } };
})();
