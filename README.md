# ハウジングバザール 一括資料請求チャットボット

「工務店へ資料請求」ページ（`/vendors/quotes_search_simple.php`）の資料請求フォームを、チャット形式で入力できるようにするものです。
今使っている外部サービス（ugchatform）の代わりに、自社のファイルだけで動きます。

## しくみ

ページを開くと、地域選択と長いフォームの代わりにチャットが大きく表示されます。
元の地域選択とフォームは画面の外に隠してあるだけで、裏ではそのまま動いています。

1. チャットで1問ずつ質問する（2問目で地域を聞き、元の地域選択に入れて下にフォームを読み込む）
2. 答えを、ページ内にある**元の資料請求フォームの同じ欄**に自動で書き込む
3. 最後に、元のフォームの「入力内容を確認する」ボタンを押す

送信されるのは元のフォームそのものなので、**受け取る側（PHP・データベース・通知メール）は何も変える必要がありません**。

## ファイル

| ファイル | 内容 |
|---|---|
| `chatbot/chatbot.js` | チャットボット本体。質問の文言・順番もここ（`STEPS`）で変更します |
| `chatbot/chatbot.css` | 見た目（色・大きさ） |
| `chatbot/chatbot-complete.js` | 送信完了画面用。チャット経由の申込みを広告の成果として数える |
| `demo/` | 試し用のページ。実際には送信されません |

## 試し方（デモ）

`demo/index.html`（パソコン版）と `demo/sp.html`（スマホ版）をサーバー上で開くと、本物のページに近い形で動作を確認できます。
最後のボタンを押すと、送信される予定の内容が画面に表示されます（実際には送信されません）。

## 本番サイトへの設置

同じファイルがパソコン版・スマホ版の両方で動きます（ページを見て自動で切り替わります）。

| | 地域選択のページ | フォーム | 送信完了画面 |
|---|---|---|---|
| パソコン | `quotes_search_simple.php` | `quotes_send2.php`（ページ内の iframe） | `quotes_send_complete.php` |
| スマホ | `quotes_search_sp.php` | `quotes_city_select_sp.php` → `quotes_send_sp.php`（チャットが裏で開く） | `quotes_send_complete_sp.php` |

1. `chatbot` フォルダを、サイトの `/js/chatbot/` にアップロードする
2. `site-changes/vendors/` の6つのファイルで、サイトの `vendors/` の同じ名前のファイルを置き換える

   | ファイル | 変更内容 |
   |---|---|
   | `quotes_search_simple.php` | ugchatform のタグを削除し、新しいチャットボットの2行を追加 |
   | `quotes_search_sp.php` | 同上（スマホ版） |
   | `quotes_send.php` | ugchatform のタグを削除 |
   | `quotes_send_sp.php` | 同上（スマホ版） |
   | `quotes_send_complete.php` | ugchatform の成果計測タグを、新しい計測タグに置き換え |
   | `quotes_send_complete_sp.php` | 同上（スマホ版。ugchatform のタグが2か所あったので両方削除） |

   変更したのはチャットボットのタグの部分だけで、ほかは預かったファイルのままです。
   預かった後にサーバー側のファイルを書き換えていた場合は、置き換えずに同じ部分だけ手で直してください。
3. チャットで最後まで入力し、確認画面・完了画面まで進むことを確かめる（パソコンとスマホの両方）

> いきなり本番ページを書き換えず、ページのコピー（例：`quotes_search_simple_test.php`）で先に試すのがおすすめです。

## 広告の成果計測（チャット経由の申込みを数える）

チャットで送信すると印が残り、**送信完了画面**でその印があるときだけ成果として数えます。
同じ申込みが2回数えられることはありません。

1. 送信完了画面（`vendors/quotes_send_complete.php` と `vendors/quotes_send_complete_sp.php`）の `</body>` の直前に、次を追加する
   ```html
   <script>
   window.hbChatbotConversion = function () {
     /* ここに広告の成果タグ（コンバージョンタグ）の中身を貼る */
   };
   </script>
   <script src="/js/chatbot/chatbot-complete.js"></script>
   ```
2. Googleタグマネージャーを使う場合は、上の「ここに貼る」は空のままにします。代わりに、カスタムイベント `hb_chatbot_complete` をきっかけに成果タグを出す設定をします

途中の離脱を調べられるように、次のイベントも送っています（Googleアナリティクス / タグマネージャー）。

| イベント | タイミング |
|---|---|
| `hb_chatbot_open` | チャットを開いた |
| `hb_chatbot_step` | 各質問に答えた（何問目かが `chatbot_step` に入る） |
| `hb_chatbot_submit` | 確認画面へ進んだ |
| `hb_chatbot_complete` | 送信完了画面まで進んだ（成果） |

### アイコン画像について

チャットのアイコンは `chatbot/icon.png`（今の ugchatform で使っている画像を保存したもの）です。
別の画像にする場合は、このファイルを差し替えてください。

## よくある変更

- **質問の文言や順番を変える** … `chatbot.js` の `STEPS` を編集します。上から順に表示されます
- **色を変える** … `chatbot.css` の `#e8731e`（オレンジ）を好きな色に置き換えます
- **右下の小さな窓で表示する（元のフォームも見せる）** … `chatbot.js` の `mode` を `'popup'` にします
- **フォームに切り替えるリンクの文言** … `chatbot.js` の `fallbackLabel` を書き換えます
- **元のフォームに項目が増えた** … `STEPS` に、その項目の `name` を持つ入力欄を追加します
