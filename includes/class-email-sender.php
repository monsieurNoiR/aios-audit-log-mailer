<?php
/**
 * メール送信クラス
 *
 * CSVファイルを添付してメール送信を行う
 *
 * @package AIOS_Audit_Log_Mailer
 */

// 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * メール送信クラス
 */
class AIOS_ALM_Email_Sender {

	/**
	 * メールを送信
	 *
	 * @param string $email_addresses メールアドレス（カンマ区切り）
	 * @param string $csv_file_path   CSVファイルのパス
	 * @return bool|WP_Error 成功時はtrue、失敗時はWP_Error
	 */
	public function send( $email_addresses, $csv_file_path ) {
		// メールアドレスの検証
		$recipients = $this->parse_email_addresses( $email_addresses );
		if ( empty( $recipients ) ) {
			return new WP_Error(
				'invalid_email',
				__( '有効なメールアドレスが指定されていません。', 'aios-audit-log-mailer' )
			);
		}

		// CSVファイルの存在確認
		if ( ! file_exists( $csv_file_path ) ) {
			return new WP_Error(
				'file_not_found',
				__( 'CSVファイルが見つかりません。', 'aios-audit-log-mailer' )
			);
		}

		// メール件名
		$subject = sprintf(
			__( '[%s] All in One Security 監査ログレポート - %s', 'aios-audit-log-mailer' ),
			get_bloginfo( 'name' ),
			date( 'Y年m月' )
		);

		// メール本文
		$message = $this->generate_email_body( $csv_file_path );

		// メールヘッダー
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		// 添付ファイル
		$attachments = array( $csv_file_path );

		// メール送信
		$sent = wp_mail( $recipients, $subject, $message, $headers, $attachments );

		if ( ! $sent ) {
			return new WP_Error(
				'mail_send_failed',
				__( 'メール送信に失敗しました。SMTPの設定を確認してください。', 'aios-audit-log-mailer' )
			);
		}

		return true;
	}

	/**
	 * メールアドレスを解析して配列にする
	 *
	 * @param string $email_addresses メールアドレス（カンマ区切り）
	 * @return array 検証済みメールアドレスの配列
	 */
	private function parse_email_addresses( $email_addresses ) {
		if ( empty( $email_addresses ) ) {
			return array();
		}

		// カンマで分割
		$addresses = array_map( 'trim', explode( ',', $email_addresses ) );
		$valid_addresses = array();

		foreach ( $addresses as $address ) {
			// メールアドレスの検証
			if ( is_email( $address ) ) {
				$valid_addresses[] = sanitize_email( $address );
			}
		}

		return array_unique( $valid_addresses );
	}

	/**
	 * メール本文を生成
	 *
	 * @param string $csv_file_path CSVファイルのパス
	 * @return string HTML形式のメール本文
	 */
	private function generate_email_body( $csv_file_path ) {
		$site_name = get_bloginfo( 'name' );
		$site_url  = get_bloginfo( 'url' );
		$file_size = size_format( filesize( $csv_file_path ) );
		$settings  = get_option( 'aios_alm_settings' );
		$export_days = isset( $settings['export_days'] ) ? intval( $settings['export_days'] ) : 30;

		// ログ件数を取得
		$log_count = $this->count_csv_lines( $csv_file_path );

		ob_start();
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset="UTF-8">
			<style>
				body {
					font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', Meiryo, sans-serif;
					line-height: 1.6;
					color: #333;
				}
				.container {
					max-width: 600px;
					margin: 0 auto;
					padding: 20px;
				}
				.header {
					background-color: #0073aa;
					color: white;
					padding: 20px;
					text-align: center;
					border-radius: 5px 5px 0 0;
				}
				.content {
					background-color: #f9f9f9;
					padding: 20px;
					border: 1px solid #ddd;
					border-top: none;
					border-radius: 0 0 5px 5px;
				}
				.info-table {
					width: 100%;
					margin: 20px 0;
					border-collapse: collapse;
				}
				.info-table th,
				.info-table td {
					padding: 10px;
					text-align: left;
					border-bottom: 1px solid #ddd;
				}
				.info-table th {
					background-color: #f0f0f0;
					width: 30%;
				}
				.footer {
					margin-top: 20px;
					padding-top: 20px;
					border-top: 1px solid #ddd;
					font-size: 12px;
					color: #666;
				}
			</style>
		</head>
		<body>
			<div class="container">
				<div class="header">
					<h1>All in One Security 監査ログレポート</h1>
				</div>
				<div class="content">
					<p><?php echo esc_html( $site_name ); ?>の監査ログレポートをお送りします。</p>

					<table class="info-table">
						<tr>
							<th>サイト名</th>
							<td><?php echo esc_html( $site_name ); ?></td>
						</tr>
						<tr>
							<th>サイトURL</th>
							<td><a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_url ); ?></a></td>
						</tr>
						<tr>
							<th>レポート期間</th>
							<td>過去<?php echo esc_html( $export_days ); ?>日間</td>
						</tr>
						<tr>
							<th>ログ件数</th>
							<td><?php echo esc_html( number_format( $log_count ) ); ?>件</td>
						</tr>
						<tr>
							<th>ファイルサイズ</th>
							<td><?php echo esc_html( $file_size ); ?></td>
						</tr>
						<tr>
							<th>生成日時</th>
							<td><?php echo esc_html( current_time( 'Y年m月d日 H:i:s' ) ); ?></td>
						</tr>
					</table>

					<p><strong>添付ファイル:</strong> <?php echo esc_html( basename( $csv_file_path ) ); ?></p>

					<p>このメールは自動送信されています。返信しないでください。</p>

					<div class="footer">
						<p>
							このメールは「All in One Security Audit Log Mailer」プラグインによって自動送信されました。<br>
							設定の変更や配信停止は、WordPressの管理画面から行ってください。
						</p>
					</div>
				</div>
			</div>
		</body>
		</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * CSVファイルの行数をカウント（ヘッダーを除く）
	 *
	 * @param string $csv_file_path CSVファイルのパス
	 * @return int 行数
	 */
	private function count_csv_lines( $csv_file_path ) {
		$count = 0;
		$file  = fopen( $csv_file_path, 'r' );

		if ( $file ) {
			// ヘッダー行をスキップ
			fgets( $file );

			while ( fgets( $file ) !== false ) {
				$count++;
			}

			fclose( $file );
		}

		return $count;
	}
}
