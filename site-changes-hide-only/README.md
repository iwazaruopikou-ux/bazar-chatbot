# 今のチャットボット（ugchatform）を外すだけのファイル

新しいチャットボットは入れずに、ugchatform のタグだけを外したものです。
サイトの `vendors/` の同じ名前のファイルと置き換えると、チャットボットが表示されなくなります。

元のファイルとの違いは ugchatform のタグを削除したことだけです。

| ファイル | 変更内容 |
|---|---|
| `quotes_search_simple.php` | ugchatform のタグを削除（パソコン版のチャット） |
| `quotes_search_sp.php` | ugchatform のタグを削除（スマホ版のチャット） |
| `quotes_send.php` | ugchatform のタグを削除 |
| `quotes_send_sp.php` | ugchatform のタグを削除 |
| `quotes_send_complete.php` | ugchatform の成果計測タグを削除 |
| `quotes_send_complete_sp.php` | ugchatform の成果計測タグを削除（2か所） |

新しいチャットボットを入れるときは、`site-changes/vendors/` のファイルで置き換えてください。
