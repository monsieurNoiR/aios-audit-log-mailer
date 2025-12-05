=== All in One Security Audit Log Mailer ===
Contributors: yourusername
Tags: security, audit log, email, export, all-in-one-security
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

All in One Security & Firewallの監査ログを月次で自動的にCSVエクスポートし、メール送信するプラグインです。

== Description ==

このプラグインは、All in One Security & Firewallプラグインが記録した監査ログを定期的にエクスポートし、CSV形式でメール送信します。セキュリティ管理者やサイト管理者が、サイトのセキュリティイベントを定期的に確認するのに役立ちます。

= 主な機能 =

* 月次自動エクスポート: 指定した日時に自動的に監査ログをエクスポート
* CSV形式: Excelなどで開きやすいCSV形式でエクスポート
* メール送信: 複数のメールアドレスに同時送信可能
* 柔軟な設定: 実行日時、対象期間などを自由に設定可能
* 手動実行: テスト用に即座に実行できる機能
* 実行履歴: 最終実行日時と結果を管理画面で確認可能

= 動作要件 =

* All in One Security & Firewallプラグインがインストールされ、有効化されている必要があります

= 対応言語 =

* 日本語

== Installation ==

= 自動インストール =

1. WordPress管理画面で「プラグイン」→「新規追加」をクリック
2. 検索ボックスに「All in One Security Audit Log Mailer」と入力
3. このプラグインを見つけたら「今すぐインストール」をクリック
4. インストール完了後、「有効化」をクリック

= 手動インストール =

1. プラグインのzipファイルをダウンロード
2. WordPress管理画面で「プラグイン」→「新規追加」→「プラグインのアップロード」をクリック
3. zipファイルを選択してアップロード
4. 「今すぐインストール」をクリック
5. インストール完了後、「プラグインを有効化」をクリック

= 初期設定 =

1. WordPress管理画面で「設定」→「AIOS監査ログメーラー」を開く
2. 送信先メールアドレスを入力
3. 実行日時とエクスポート対象期間を設定
4. 「設定を保存」をクリック
5. 「今すぐ実行」ボタンでテスト送信して動作確認

== Frequently Asked Questions ==

= All in One Securityプラグインが必須ですか? =

はい、このプラグインはAll in One Security & Firewallプラグインの監査ログを使用するため、必須です。

= メールが届きません =

以下を確認してください:
* メールアドレスが正しく入力されているか
* WordPressのメール機能が正常に動作しているか（WP Mail SMTPなどのプラグインの使用を推奨）
* 迷惑メールフォルダに振り分けられていないか

= 実行日時を変更したい =

設定ページで実行日（1〜28日）と実行時刻を自由に変更できます。設定保存後、次回実行予定が自動的に更新されます。

= 複数のメールアドレスに送信できますか? =

はい、カンマ区切りで複数のメールアドレスを指定できます。例: mail1@example.com, mail2@example.com

= CSVファイルの文字コードは何ですか? =

UTF-8（BOM付き）です。ExcelでもMacの Numbers でも正しく開くことができます。

= 手動で即座に実行したい =

設定ページの「手動実行」セクションにある「今すぐ実行」ボタンをクリックしてください。

== Screenshots ==

1. 設定画面 - メールアドレス、実行日時、対象期間などを設定
2. 実行状況 - 次回実行予定、最終実行結果を表示
3. システム情報 - プラグインの動作状態を確認
4. メール本文 - 送信されるメールのサンプル

== Changelog ==

= 1.0.0 =
* 初回リリース
* 月次自動エクスポート機能
* CSV形式でのエクスポート
* メール送信機能
* 管理画面での設定機能
* 手動実行機能

== Upgrade Notice ==

= 1.0.0 =
初回リリースです。

== Technical Specifications ==

= データベーステーブル =

All in One Securityの監査ログテーブル (`{$wpdb->prefix}aiowps_audit_log`) を使用します。

= Cronスケジュール =

WordPress標準のCron機能 (`wp_schedule_event`) を使用して月次実行します。

= 一時ファイル =

CSVファイルは `/wp-content/uploads/aios-alm-temp/` に一時保存され、メール送信後に削除されます。7日以上前のファイルは自動的にクリーンアップされます。

== Support ==

サポートが必要な場合は、プラグインのサポートフォーラムで質問してください。

== Privacy Policy ==

このプラグインは、監査ログデータをCSVファイルとして生成し、指定されたメールアドレスに送信します。監査ログにはユーザー名、IPアドレス、イベント情報などが含まれる場合があります。メールの送信先は管理者が設定したアドレスのみです。

プラグイン自体は外部サーバーへのデータ送信は行いません。
