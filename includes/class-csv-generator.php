<?php
/**
 * CSV生成クラス
 *
 * All in One Securityの監査ログをCSVファイルとして生成する
 *
 * @package AIOS_Audit_Log_Mailer
 */

// 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSV生成クラス
 */
class AIOS_ALM_CSV_Generator {

	/**
	 * 監査ログをCSVファイルとして生成
	 *
	 * @param int $days 過去何日分のログを取得するか
	 * @return string|WP_Error CSVファイルのパス、またはエラー
	 */
	public function generate( $days = 30 ) {
		global $wpdb;

		// テーブル名
		$table_name = $wpdb->prefix . 'aiowps_audit_log';

		// テーブルの存在確認
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			return new WP_Error(
				'table_not_found',
				__( '監査ログテーブルが見つかりません。', 'aios-audit-log-mailer' )
			);
		}

		// ログデータの取得（全件取得してPHP側でフィルタリング）
		$query = "SELECT * FROM `{$wpdb->prefix}aiowps_audit_log` ORDER BY id DESC";
		$all_logs = $wpdb->get_results( $query, ARRAY_A );

		if ( empty( $all_logs ) ) {
			return new WP_Error(
				'no_data',
				__( '監査ログが見つかりませんでした。', 'aios-audit-log-mailer' )
			);
		}

		// 日付範囲でフィルタリング
		$cutoff_timestamp = strtotime( "-{$days} days" );
		$filtered_logs = array();

		foreach ( $all_logs as $log ) {
			// 日付時刻カラムを特定（created または Date and time などの可能性）
			$date_value = null;
			if ( isset( $log['created'] ) ) {
				$date_value = $log['created'];
			} elseif ( isset( $log['Date and time'] ) ) {
				$date_value = $log['Date and time'];
			}

			if ( $date_value ) {
				$log_timestamp = $this->parse_japanese_datetime( $date_value );

				// パースに失敗した場合は数値かチェック
				if ( $log_timestamp === false && is_numeric( $date_value ) ) {
					$log_timestamp = intval( $date_value );
				}

				// フィルタリング
				if ( $log_timestamp !== false && $log_timestamp >= $cutoff_timestamp ) {
					$filtered_logs[] = $log;
				}
			}
		}

		if ( empty( $filtered_logs ) ) {
			return new WP_Error(
				'no_data',
				__( '指定期間内に監査ログが見つかりませんでした。', 'aios-audit-log-mailer' )
			);
		}

		$logs = $filtered_logs;

		// 一時ファイルの作成
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/aios-alm-temp';

		// 一時ディレクトリが存在しない場合は作成
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		// CSVファイル名
		$filename = 'audit-log-' . date( 'Y-m-d-His' ) . '.csv';
		$filepath = $temp_dir . '/' . $filename;

		// CSVファイルの作成
		$file = fopen( $filepath, 'w' );
		if ( ! $file ) {
			return new WP_Error(
				'file_creation_failed',
				__( 'CSVファイルの作成に失敗しました。', 'aios-audit-log-mailer' )
			);
		}

		// BOM（Byte Order Mark）を追加してExcelで文字化け防止
		fputs( $file, "\xEF\xBB\xBF" );

		// CSVヘッダー
		$header = array(
			'ID',
			__( '日時', 'aios-audit-log-mailer' ),
			__( 'ユーザー名', 'aios-audit-log-mailer' ),
			__( 'IPアドレス', 'aios-audit-log-mailer' ),
			__( 'レベル', 'aios-audit-log-mailer' ),
			__( 'イベントタイプ', 'aios-audit-log-mailer' ),
			__( '詳細', 'aios-audit-log-mailer' ),
		);
		fputcsv( $file, $header );

		// データ行の追加
		foreach ( $logs as $log ) {
			// 日付時刻の取得
			$datetime = '';
			if ( isset( $log['created'] ) ) {
				$datetime = $this->format_datetime( $log['created'] );
			} elseif ( isset( $log['Date and time'] ) ) {
				$datetime = $log['Date and time'];
			}

			$row = array(
				isset( $log['id'] ) ? $log['id'] : '',
				$datetime,
				isset( $log['username'] ) ? $log['username'] : ( isset( $log['ユーザー名'] ) ? $log['ユーザー名'] : '' ),
				isset( $log['ip'] ) ? $log['ip'] : ( isset( $log['IP'] ) ? $log['IP'] : '' ),
				isset( $log['level'] ) ? $this->format_level( $log['level'] ) : ( isset( $log['Level'] ) ? $log['Level'] : '' ),
				isset( $log['event_type'] ) ? $log['event_type'] : ( isset( $log['Event'] ) ? $log['Event'] : '' ),
				isset( $log['details'] ) ? $this->format_details( $log['details'] ) : ( isset( $log['Details'] ) ? $log['Details'] : '' ),
			);
			fputcsv( $file, $row );
		}

		fclose( $file );

		return $filepath;
	}

	/**
	 * 日本語日付形式をタイムスタンプに変換
	 *
	 * @param string $datetime_str 日本語日付文字列 (例: "2025年12月4日 1:49 PM")
	 * @return int|false タイムスタンプ、またはfalse
	 */
	private function parse_japanese_datetime( $datetime_str ) {
		// 既にタイムスタンプの場合
		if ( is_numeric( $datetime_str ) ) {
			return intval( $datetime_str );
		}

		// 日本語形式をパース: "2025年12月4日 1:49 PM"
		$pattern = '/^(\d{4})年(\d{1,2})月(\d{1,2})日\s+(\d{1,2}):(\d{2})\s+(AM|PM)$/u';
		if ( preg_match( $pattern, trim( $datetime_str ), $matches ) ) {
			$year   = intval( $matches[1] );
			$month  = intval( $matches[2] );
			$day    = intval( $matches[3] );
			$hour   = intval( $matches[4] );
			$minute = intval( $matches[5] );
			$ampm   = $matches[6];

			// 12時間制を24時間制に変換
			if ( $ampm === 'PM' && $hour !== 12 ) {
				$hour += 12;
			} elseif ( $ampm === 'AM' && $hour === 12 ) {
				$hour = 0;
			}

			return mktime( $hour, $minute, 0, $month, $day, $year );
		}

		// 標準的な日時形式も試す
		$timestamp = strtotime( $datetime_str );
		if ( $timestamp !== false ) {
			return $timestamp;
		}

		return false;
	}

	/**
	 * タイムスタンプを読みやすい日時形式に変換
	 *
	 * @param int|string $timestamp タイムスタンプ
	 * @return string フォーマット済み日時
	 */
	private function format_datetime( $timestamp ) {
		if ( is_numeric( $timestamp ) ) {
			return date( 'Y-m-d H:i:s', $timestamp );
		}
		return $timestamp;
	}

	/**
	 * ログレベルを日本語に変換
	 *
	 * @param string $level ログレベル
	 * @return string 日本語のログレベル
	 */
	private function format_level( $level ) {
		$levels = array(
			'info'    => __( '情報', 'aios-audit-log-mailer' ),
			'warning' => __( '警告', 'aios-audit-log-mailer' ),
			'error'   => __( 'エラー', 'aios-audit-log-mailer' ),
			'critical' => __( '重大', 'aios-audit-log-mailer' ),
		);

		return isset( $levels[ $level ] ) ? $levels[ $level ] : $level;
	}

	/**
	 * 詳細情報をフォーマット
	 *
	 * @param string $details 詳細情報
	 * @return string フォーマット済み詳細情報
	 */
	private function format_details( $details ) {
		// JSON形式の場合はデコードして読みやすくする
		$decoded = json_decode( $details, true );
		if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
			$formatted_parts = array();
			foreach ( $decoded as $key => $value ) {
				if ( is_array( $value ) ) {
					$value = json_encode( $value, JSON_UNESCAPED_UNICODE );
				}
				$formatted_parts[] = "{$key}: {$value}";
			}
			return implode( ' | ', $formatted_parts );
		}

		return $details;
	}

	/**
	 * 一時ファイルをクリーンアップ
	 *
	 * 7日以上前の一時ファイルを削除
	 */
	public static function cleanup_temp_files() {
		$upload_dir = wp_upload_dir();
		$temp_dir   = $upload_dir['basedir'] . '/aios-alm-temp';

		if ( ! file_exists( $temp_dir ) ) {
			return;
		}

		$files = glob( $temp_dir . '/audit-log-*.csv' );
		$now   = time();

		foreach ( $files as $file ) {
			if ( is_file( $file ) && ( $now - filemtime( $file ) > 7 * DAY_IN_SECONDS ) ) {
				unlink( $file );
			}
		}
	}
}

// 一時ファイルの定期クリーンアップ（週1回）
add_action( 'wp_scheduled_delete', array( 'AIOS_ALM_CSV_Generator', 'cleanup_temp_files' ) );
