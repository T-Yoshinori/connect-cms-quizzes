# 数式入力支援オプション

Connect-CMSの共通WYSIWYGへ、数式入力ダイアログ、入力中プレビュー、LaTeXコード挿入ボタンを追加します。

## 注意

- 対応確認環境はConnect-CMS 1.39.0です。
- `resources/views/plugins/common/wysiwyg.blade.php`を上書きします。
- 導入前に既存ファイルをバックアップしてください。
- Connect-CMSを更新した場合は、公式ファイルとの差分を再確認してください。

## 導入

`overlay`以下の内容をConnect-CMSのルートへ重ねて配置し、`php artisan view:clear`を実行します。

## 解除

導入前に保存した`wysiwyg.blade.php`を復元し、`php artisan view:clear`を実行します。
