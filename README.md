# All in One Security Audit Log Mailer

All in One Security & Firewallプラグインの監査ログを月次で自動的にCSVエクスポートし、指定したメールアドレスに送信するWordPressプラグインです。

## プラグイン概要

このプラグインは、セキュリティ管理者やサイト運営者が定期的に監査ログを確認できるよう、All in One Security & Firewallの監査ログを自動的にエクスポートしてメールで送信します。

- **バージョン**: 1.0.0
- **作者**: studioNoiR
- **ライセンス**: GPL v2 or later
- **動作確認環境**:
  - WordPress 6.x
  - PHP 7.4以上
  - All in One Security & Firewall（有効化必須）

## 主な機能

### 自動エクスポート
- 月次で自動的に監査ログをCSVファイルとしてエクスポート
- 指定した日時に自動実行（WordPress Cron使用）
- 実行日は月の実際の日数に応じて自動調整（例: 31日設定時、2月は28日または29日に実行）

### 柔軟な設定
- 送信先メールアドレス（複数指定可能、カンマ区切り）
- 実行日（毎月1〜28日）
- 実行時刻（24時間制）
- エクスポート対象期間（1〜365日）

### 手動実行
- テスト用の即時実行機能
- 管理画面から「今すぐ実行」ボタンで即座に実行可能

### 実行状況の確認
- 次回実行予定日時の表示
- 最終実行日時と結果の表示
- システム情報の確認機能

## インストール方法

### 方法1: zipファイルからインストール

1. WordPress管理画面にログイン
2. 「プラグイン」→「新規追加」→「プラグインのアップロード」をクリック
3. `aios-audit-log-mailer.zip` を選択してアップロード
4. 「今すぐインストール」をクリック
5. インストール完了後、「プラグインを有効化」をクリック

### 方法2: 手動インストール

1. `aios-audit-log-mailer` フォルダを `/wp-content/plugins/` ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」メニューからプラグインを有効化

### 前提条件

このプラグインを使用するには、以下が必要です:

- All in One Security & Firewallプラグインがインストールされ、有効化されていること
- All in One Securityで監査ログ機能が有効になっていること

## 使い方

### 初期設定

1. WordPress管理画面で「設定」→「AIOS監査ログメーラー」を開く
2. 以下の項目を設定:

#### 基本設定
- **自動送信を有効化**: チェックを入れると月次での自動送信が有効になります
- **送信先メールアドレス**: メールを受信するアドレスを入力
  ```
  例: admin@example.com, security@example.com
  ```
- **実行日**: 毎月何日に実行するか（1〜28日）
- **実行時刻**: 実行する時刻（サーバー時刻）
  ```
  例: 02:00（午前2時）
  ```
- **エクスポート対象期間**: 過去何日分のログを出力するか（1〜365日）
  ```
  例: 30（過去30日分）
  ```

3. 「設定を保存」ボタンをクリック

### 手動実行（テスト）

設定が正しく動作するかテストする場合:

1. 設定ページの「手動実行」セクションまでスクロール
2. 「今すぐ実行」ボタンをクリック
3. 設定したメールアドレスにメールが届くことを確認

### 自動実行スケジュール

プラグインは以下のように動作します:

1. 設定した日時になると自動的に実行
2. 監査ログをデータベースから取得
3. CSVファイルとして生成
4. 指定したメールアドレスに送信
5. 次回実行日を自動的にスケジュール

#### 月末日の扱い

31日設定の場合:
- 1月31日: 1月31日に実行
- 2月28日: 2月28日に実行（2月は31日が存在しないため）
- 3月31日: 3月31日に実行

## 設定項目の説明

### メールアドレス

複数のメールアドレスを指定する場合は、カンマで区切って入力します:

```
mail1@example.com, mail2@example.com, mail3@example.com
```

各メールアドレスは自動的にバリデーションされます。

### 実行日

毎月1日〜28日の範囲で設定できます。28日以下に制限しているのは、すべての月に存在する日付であることを保証するためです。

### 実行時刻

サーバー時刻で設定します。WordPressの設定で使用しているタイムゾーンが適用されます。

現在のサーバー時刻は、設定ページの「システム情報」セクションで確認できます。

### エクスポート対象期間

過去1日〜365日の範囲で設定できます。デフォルトは30日です。

## CSVファイルの内容

エクスポートされるCSVファイルには以下の情報が含まれます:

- **ID**: ログID
- **日時**: イベント発生日時
- **ユーザー名**: アクションを実行したユーザー
- **IPアドレス**: アクセス元IPアドレス
- **レベル**: ログレベル（情報/警告/エラー/重大）
- **イベントタイプ**: イベントの種類
- **詳細**: イベントの詳細情報

### ファイル形式

- **文字コード**: UTF-8（BOM付き）
- **区切り文字**: カンマ（,）
- **エンコーディング**: Excel、Numbers、Google スプレッドシートで正しく開けます

## 技術仕様

### 対応する日付形式

このプラグインは、All in One Securityの以下の日付形式に対応しています:

1. **日本語形式**: `2025年12月4日 1:49 PM`
2. **タイムスタンプ**: UNIXタイムスタンプ
3. **標準形式**: `Y-m-d H:i:s` 形式

日付のパースは以下の順序で試行されます:
1. 日本語形式の正規表現マッチ
2. 数値としてのタイムスタンプ
3. PHPの`strtotime()`関数

### データベーステーブル

- **テーブル名**: `{$wpdb->prefix}aiowps_audit_log`

対応するカラム名:
- 日付: `created` または `Date and time`
- ユーザー名: `username` または `ユーザー名`
- IP: `ip` または `IP`
- レベル: `level` または `Level`
- イベント: `event_type` または `Event`
- 詳細: `details` または `Details`

### Cron実装方式

このプラグインは`wp_schedule_single_event()`を使用した動的スケジューリングを採用しています:

1. 固定間隔（30日）ではなく、実際の月の日数に応じて次回実行日を計算
2. 各実行後に次回のスケジュールを自動的に再設定
3. 月末日（29-31日）の扱いも考慮し、存在しない日付の場合は月の最終日に実行

### WordPressオプション

プラグインが使用するWordPressオプション:

**`aios_alm_settings`**
```php
array(
    'enabled'         => true,              // 自動送信の有効/無効
    'email_addresses' => 'admin@example.com', // 送信先メールアドレス
    'schedule_day'    => 1,                 // 実行日（1-28）
    'schedule_hour'   => 2,                 // 実行時（0-23）
    'schedule_minute' => 0,                 // 実行分（0-59）
    'export_days'     => 30,                // エクスポート対象日数（1-365）
)
```

**`aios_alm_last_execution`**
```php
array(
    'time'    => '2025-12-05 02:00:00', // 実行日時
    'success' => true,                   // 成功/失敗
    'message' => 'Successfully sent',    // メッセージ
)
```

### 一時ファイル

CSVファイルは以下の場所に一時保存されます:

- **保存場所**: `/wp-content/uploads/aios-alm-temp/`
- **ファイル名形式**: `audit-log-YYYY-MM-DD-HHmmss.csv`
- **クリーンアップ**: 7日以上前のファイルは自動削除（WordPress標準の`wp_scheduled_delete`フックを使用）

## セキュリティ対策

### SQLインジェクション対策

すべてのデータベースクエリで`$wpdb->prepare()`を使用:

```php
$wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) )
```

### Nonce検証

フォーム送信時にNonceトークンによるCSRF対策を実施:

```php
wp_verify_nonce( $_POST['aios_alm_nonce'], 'aios_alm_manual_export' )
```

### 権限チェック

管理者権限（`manage_options`）を持つユーザーのみが設定変更可能:

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( __( 'このページにアクセスする権限がありません。' ) );
}
```

### 入力値のサニタイズ

すべての入力値を適切にサニタイズとバリデーション:

```php
$valid_addresses[] = sanitize_email( $address );
```

### ファイルの安全な削除

エラーが発生しても処理を継続:

```php
if ( file_exists( $csv_file ) ) {
    @unlink( $csv_file );
}
```

## トラブルシューティング

### メールが届かない場合

#### 原因1: メールアドレスの設定ミス
- 設定ページでメールアドレスが正しく入力されているか確認
- 複数アドレスの場合、カンマ区切りになっているか確認

#### 原因2: WordPressのメール機能が動作していない
このプラグインはWordPress標準の`wp_mail()`関数を使用しています。

**確認方法:**
1. 他のプラグイン（例: WP Mail SMTP）でテストメールを送信
2. サーバーのメール送信機能が有効か確認

**推奨対策:**
- WP Mail SMTPなどのSMTPプラグインを導入
- 外部SMTPサーバー（Gmail、SendGrid等）を設定

#### 原因3: 迷惑メールフォルダに振り分けられている
- 受信トレイではなく、迷惑メールフォルダを確認
- ドメイン認証（SPF、DKIM）の設定を推奨

### Cronが動作しない場合

#### 原因1: WordPress Cronの仕組み
WordPressのCronは、サイトへのアクセスがあった時に実行されます。アクセスが少ないサイトでは実行が遅れることがあります。

**確認方法:**
```bash
# 次回実行予定を確認
wp cron event list
```

**推奨対策:**
システムCronを使用してWordPress Cronを確実に実行:

```bash
# crontabに追加
*/5 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

または

```bash
*/5 * * * * curl -s https://yoursite.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

#### 原因2: 設定が無効になっている
- 設定ページで「自動送信を有効化」にチェックが入っているか確認
- 設定を変更した場合は「設定を保存」ボタンをクリック

#### 原因3: スケジュールが登録されていない
設定ページで「設定を保存」をクリックすると、スケジュールが再登録されます。

### ログが見つからない場合

#### 原因1: All in One Securityプラグインが無効
- All in One Security & Firewallプラグインが有効化されているか確認
- システム情報セクションで「All in One Security」が「有効」になっているか確認

#### 原因2: 監査ログ機能が無効
All in One Securityの設定で監査ログ機能を有効にする:

1. All in One Securityの管理画面を開く
2. 「User Login」→「Audit Log」を選択
3. 監査ログ機能を有効化

#### 原因3: ログテーブルが存在しない
- システム情報セクションで「監査ログテーブル」が「存在」となっているか確認
- All in One Securityを一度無効化して再度有効化（テーブルが再作成されます）

#### 原因4: 対象期間内にログが存在しない
- エクスポート対象期間を長めに設定してみる（例: 90日）
- システム情報でログ件数を確認

### ファイルが作成できない場合

#### 原因: アップロードディレクトリの書き込み権限不足

**確認方法:**
```bash
# ディレクトリの権限を確認
ls -ld /path/to/wordpress/wp-content/uploads/
```

**対策:**
```bash
# 書き込み権限を付与（755または775）
chmod 755 /path/to/wordpress/wp-content/uploads/
```

## メール本文の内容

送信されるメールには以下の情報が含まれます:

- サイト名
- サイトURL
- レポート期間（過去X日間）
- ログ件数
- ファイルサイズ
- 生成日時
- 添付ファイル名

メールはHTML形式で、見やすくフォーマットされています。

## 開発者向け情報

### フック

プラグインは以下のWordPressフックを使用しています:

**アクションフック:**
- `plugins_loaded`: 翻訳ファイルの読み込み
- `admin_menu`: 管理メニューの追加
- `admin_init`: 設定の登録
- `admin_notices`: 依存関係チェック
- `admin_post_aios_alm_manual_export`: 手動実行の処理
- `aios_alm_monthly_export`: 月次エクスポートの実行（カスタムフック）
- `wp_scheduled_delete`: 一時ファイルのクリーンアップ

### カスタマイズ

翻訳ファイルを追加する場合は、`/languages/` ディレクトリに配置してください:

```
aios-audit-log-mailer/
└── languages/
    ├── aios-audit-log-mailer-ja.po
    └── aios-audit-log-mailer-ja.mo
```

テキストドメイン: `aios-audit-log-mailer`

## ライセンス

このプラグインはGPL v2以降のライセンスの下で配布されています。

```
Copyright (C) 2025 studioNoiR

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

## 変更履歴

### バージョン 1.0.0 (2025-12-05)

**初回リリース**

機能:
- 月次自動エクスポート機能
- CSV形式でのエクスポート
- メール送信機能（複数アドレス対応）
- 管理画面での設定機能
- 手動実行機能
- 実行状況の表示
- システム情報の表示

技術的な実装:
- WordPress Cron（`wp_schedule_single_event`）による動的スケジューリング
- 日本語日付形式（"2025年12月4日 1:49 PM"）のパース対応
- 複数のカラム名パターンに対応
- SQLインジェクション対策（`$wpdb->prepare()`使用）
- Nonce検証によるCSRF対策
- 管理者権限チェック
- エラーハンドリング
- 一時ファイルの自動クリーンアップ

セキュリティ:
- すべてのSQLクエリでprepared statementを使用
- 入力値のサニタイズとバリデーション
- 権限チェック
- Nonce検証

## サポート

問題が発生した場合や機能のリクエストがある場合は、以下の情報を含めてお問い合わせください:

1. WordPressのバージョン
2. PHPのバージョン
3. All in One Securityのバージョン
4. エラーメッセージ（あれば）
5. 実行時のログ（管理画面の「最終実行結果」）

## クレジット

このプラグインは、All in One Security & Firewallプラグインと連携して動作します。

- **All in One Security & Firewall**: https://wordpress.org/plugins/all-in-one-wp-security-and-firewall/

## プライバシーポリシー

このプラグインは以下のデータを処理します:

- 監査ログデータ（ユーザー名、IPアドレス、イベント情報など）
- 設定情報（メールアドレス、スケジュール設定）
- 実行履歴（実行日時、成功/失敗、エラーメッセージ）

これらのデータは:
- WordPressデータベースに保存されます
- 設定したメールアドレスにのみ送信されます
- 外部サーバーには送信されません
- 7日以上前の一時ファイルは自動削除されます

## 謝辞

このプラグインの開発にあたり、WordPress コミュニティとAll in One Security & Firewallプラグインの開発者に感謝いたします。
