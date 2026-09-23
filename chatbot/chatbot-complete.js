/*!
 * ハウジングバザール 一括資料請求チャットボット：送信完了画面用
 *
 * 送信完了画面にだけ読み込む。チャットボット経由で送信された場合だけ
 *   ・window.HB_CHATBOT_CV = true にする
 *   ・計測イベント hb_chatbot_complete を送る（Googleタグマネージャー / Googleアナリティクス）
 *   ・window.hbChatbotConversion が用意されていれば呼ぶ（広告の成果タグを入れる場所）
 * 同じ申込みで2回数えないよう、印は1回使ったら消す。
 */
(function () {
  'use strict';
  var KEY = 'hb_chatbot_cv';
  var LIMIT = 60 * 60 * 1000; // チャットで送信してから1時間以内の完了だけ数える
  var t = null;
  try { t = +localStorage.getItem(KEY); localStorage.removeItem(KEY); } catch (e) { return; }
  if (!t || Date.now() - t > LIMIT) return;

  window.HB_CHATBOT_CV = true;
  try {
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: 'hb_chatbot_complete' });
    if (typeof window.gtag === 'function') window.gtag('event', 'hb_chatbot_complete');
  } catch (e) { /* 無視 */ }

  function fire() {
    if (typeof window.hbChatbotConversion === 'function') {
      try { window.hbChatbotConversion(); } catch (e) { /* 無視 */ }
    }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fire);
  else fire();
})();
