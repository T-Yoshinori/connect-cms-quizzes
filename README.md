# YuyuQuizzes — Connect-CMS Ver.2対応β版

- 配布版: 0.9.0-cms2.beta.1
- 対象: Connect-CMS 2.44.1 / PHP 8.3（移行確認環境: 8.3.36）
- ブランチ: `connect-cms-v2`
- [CMS Ver.1版](https://github.com/T-Yoshinori/connect-cms-quizzes/tree/main)
- [Ver.2用インストールZIP](downloads/connect-cms-yuyuquizzes-0.9.0-cms2.beta.1.zip)

小テストの作成・受験、自動採点・手動採点、結果集計、CSV出力、カテゴリー別評価、
手書き回答を提供する非公式プラグインです。開発・提供: ゆうゆう企画。

## 導入対象
正常稼働するConnect-CMS 2.44.1への新規導入用β版です。
Ver.1のデータ移行・旧Quizzesからの変換ツールは含みません。
今回の統合移行版を導入して検証済みのサイトには、再配置・再インストールは不要です。

## 導入手順
1. CMSのファイルとデータベースをバックアップ。
2. ZIP内の `app`、`database`、`resources` をCMS設置先に同じ構成で配置。
   READMEやLICENSE等はCMSルートへコピー不要です。
3. CMS設置先で `composer dump-autoload` を実行。
4. [このプラグイン専用のマイグレーションコマンド](docs/migrate-cms2.md)を実行。
   23ファイルを指定し、他プラグインの未実行マイグレーションは含めません。
5. 次を実行:

```sh
php artisan db:seed --class=YuyuQuizzesPluginSeeder
php artisan view:clear
```

6. プラグイン管理で「小テスト」の表示状態を確認し、ページへ配置。
7. フレーム設定の「新規作成」から小テストを作成し、公開設定・問題登録へ進みます。

Seederは小テストの登録だけを行い、テストユーザー・問題・回答は作成しません。
既存登録がある場合は追加せず、既存の表示設定を保持します。
既存テーブルがあるのにマイグレーション履歴がない場合は、再実行せず環境を確認してください。

## 依存関係・配布範囲
- CMS標準: ユーザー・グループ・フレーム・バケット・権限・WYSIWYG・共通Blade部品。
- 他の独自プラグインは必須ではありません。YuyuLearning/YuyuPortalは同梱しません。
- 追加のComposerパッケージ、独自テーマの指定、CMS標準ファイルの上書きは不要。
- 公開開始・終了日時はCMS共通datetimepickerを使用。
- 手書き画像はLaravelのlocalディスクへ保存。公開ディスクへの切替やstorage:linkは不要。
  保存先とstorageの書込権限を確認してください。画像は認可付きアクションから配信します。
- MathJaxは既存コードどおり外部CDNから読み込みます。オフライン環境では別途対応が必要。
- Ver.1用の数式入力支援オプション（共通wysiwyg上書き）は同梱せず、Ver.2対応確認済みとは扱いません。
  通常版にもMathJax表示機能があります。

## Ver.2版で反映した修正
- 設定画面の入口指定と、共通フレーム編集からの設定タブ読み込み。
- 公開開始・終了日時の入力をCMS標準に統一。
- 日付入力変更時に発生したBladeブロックの不整合を修正。
- 手書き画像モデルと関連マイグレーションを同梱。
- 単独導入用Seederを追加。統合環境の一括Seederは同梱しません。

## 確認状況
利用者からVer.2環境で一通りのテスト完了の報告を受けています。
操作別の全件記録や全ブラウザー・全権限条件の検証を保証するものではありません。
今回の整理ではソースのGit blob一致、参照クラス・Blade部品の存在、配布ZIPと元ファイルの一致を確認。
この単独ZIPによる新規サイトへのインストール、追加Seederの実行は今回未検証です。

## 問い合わせ
不具合はこのリポジトリのIssuesへ。CMS/PHP/配布版、再現手順、期待結果・実際の結果を記載してください。
個人情報・認証情報・回答内容は公開しないでください。[セキュリティ方針](SECURITY.md)も参照してください。

## 開発・ライセンス
`python3 scripts/build_package.py` で配布ZIPを再生成できます。
MIT License。CMS本体のライセンスは配布元の条件に従います。
Connect-CMS開発元による公式提供・認定・保証を受けたものではありません。
設計・実装にはChatGPT（OpenAI）の開発支援を利用しています。

## デモデータの初期化（任意）

管理者向けの `quizzes:reset-demo` コマンドを追加しました。通常利用では実行不要です。
[使い方・復元条件](docs/demo-reset.md) ／ [Ver.1・Ver.2共通の任意機能ZIP](downloads/connect-cms-yuyuquizzes-demo-reset-1.0.0.zip)

現在の標準版ZIPとは別配布です。利用する場合だけ任意機能ZIP内のappを追加配置してください。
