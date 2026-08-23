# デモ用小テストの初期化

`quizzes:reset-demo` は、指定した小テストの受験・解答・採点データだけを保存済みの初期状態へ戻すコマンドです。

次のデータは変更しません。

- ページ、フレーム、固定記事、Q&A、問い合わせ
- 小テスト本体、出題ページ、問題、選択肢
- ユーザー、権限
- 指定していない小テストの受験データ

## 1. 小テストIDを確認する

小テスト一覧またはデータベースの `quizzes.id` で、一般公開デモに使用する小テストIDを確認します。以下では例として `1` を使用します。

## 2. 初期状態を保存する

受験者3人分の解答と管理者による採点を完成させてから、Connect-CMSのルートディレクトリで実行します。

```bash
php artisan quizzes:reset-demo 1 --create-baseline
```

確認に回答すると、次へ保存されます。

```text
storage/app/quizzes-demo/baselines/quiz-1.json
```

初期状態を作り直して上書きする場合も、同じコマンドを実行します。

## 3. 初期状態へ戻す

```bash
php artisan quizzes:reset-demo 1
```

確認に回答すると、小テストID `1` の現在の受験データだけを削除し、保存済みの状態へ戻します。削除直前の状態は次へ自動保存されます。

```text
storage/app/quizzes-demo/safety/
```

## 4. cronから実行する

対話確認を表示できないcronでは、`--force`を付けます。

```bash
cd /www/quiz-demo/connect-cms-1.39.0 && php artisan quizzes:reset-demo 1 --force
```

毎日午前4時に実行する例です。

```cron
0 4 * * * cd /www/quiz-demo/connect-cms-1.39.0 && php artisan quizzes:reset-demo 1 --force >> storage/logs/quizzes-demo-reset.log 2>&1
```

問題作成デモ用の小テストは、一般公開デモとは別のIDにします。上記コマンドにそのIDを指定しない限り、問題作成デモのデータは初期化されません。

## 安全対策

- 小テストIDの指定は必須です。
- 存在しないIDでは処理しません。
- 初期状態ファイルがなければ初期化しません。
- 初期状態ファイルの小テストIDとチェックサムを検証します。
- DBトランザクション内で削除と復元を行います。
- 復元件数が一致しなければロールバックします。
- 初期化直前の受験データを安全バックアップとして保存します。
