<?php
/**
 * Plugin Name: All in One Security Audit Log Mailer
 * Plugin URI: https://github.com/yourusername/aios-audit-log-mailer
 * Description: All in One Security & Firewallの監査ログを月次で自動エクスポート・メール送信するプラグイン
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aios-audit-log-mailer
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// プラグイン定数の定義
define( 'AIOS_ALM_VERSION', '1.0.0' );
define( 'AIOS_ALM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIOS_ALM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AIOS_ALM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// 必要なファイルの読み込み
require_once AIOS_ALM_PLUGIN_DIR . 'includes/class-csv-generator.php';
require_once AIOS_ALM_PLUGIN_DIR . 'includes/class-email-sender.php';
require_once AIOS_ALM_PLUGIN_DIR . 'includes/class-admin-page.php';

/**
 * メインプラグインクラス
 */
class AIOS_Audit_Log_Mailer {

	/**
	 * シングルトンインスタンス
	 *
	 * @var AIOS_Audit_Log_Mailer
	 */
	private static $instance = null;

	/**
	 * Cronイベントのフック名
	 *
	 * @var string
	 */
	const CRON_HOOK = 'aios_alm_monthly_export';

	/**
	 * オプション名
	 *
	 * @var string
	 */
	const OPTION_NAME = 'aios_alm_settings';

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return AIOS_Audit_Log_Mailer
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * コンストラクタ
	 */
	private function __construct() {
		// プラグイン有効化・無効化フック
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// 翻訳ファイルの読み込み
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Cronイベントのフック
		add_action( self::CRON_HOOK, array( $this, 'execute_export_and_send' ) );

		// 管理画面の初期化
		if ( is_admin() ) {
			AIOS_ALM_Admin_Page::get_instance();
		}

		// 管理画面通知
		add_action( 'admin_notices', array( $this, 'check_dependencies' ) );
	}

	/**
	 * テキストドメインの読み込み
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'aios-audit-log-mailer', false, dirname( AIOS_ALM_PLUGIN_BASENAME ) . '/languages' );
	}

	/**
	 * プラグイン有効化時の処理
	 */
	public function activate() {
		// デフォルト設定の保存
		$default_settings = array(
			'email_addresses' => get_option( 'admin_email' ),
			'schedule_day'    => 1,
			'schedule_hour'   => 2,
			'schedule_minute' => 0,
			'export_days'     => 30,
			'enabled'         => true,
		);

		if ( ! get_option( self::OPTION_NAME ) ) {
			add_option( self::OPTION_NAME, $default_settings );
		}

		// Cronイベントのスケジュール
		$this->schedule_cron_event();
	}

	/**
	 * プラグイン無効化時の処理
	 */
	public function deactivate() {
		// Cronイベントの削除
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Cronイベントをスケジュール
	 */
	public function schedule_cron_event() {
		// 既存のスケジュールを削除
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}

		// 設定を取得
		$settings = get_option( self::OPTION_NAME );
		if ( ! $settings || ! isset( $settings['enabled'] ) || ! $settings['enabled'] ) {
			return;
		}

		// 次回実行時刻を計算
		$next_run = $this->calculate_next_run_time( $settings );

		// 一回限りのイベントをスケジュール
		wp_schedule_single_event( $next_run, self::CRON_HOOK );
	}

	/**
	 * 次回実行時刻を計算
	 *
	 * @param array $settings 設定値
	 * @return int 次回実行時刻（UNIXタイムスタンプ）
	 */
	private function calculate_next_run_time( $settings ) {
		$schedule_day    = isset( $settings['schedule_day'] ) ? intval( $settings['schedule_day'] ) : 1;
		$schedule_hour   = isset( $settings['schedule_hour'] ) ? intval( $settings['schedule_hour'] ) : 2;
		$schedule_minute = isset( $settings['schedule_minute'] ) ? intval( $settings['schedule_minute'] ) : 0;

		// 今月の指定日時
		$year  = intval( current_time( 'Y' ) );
		$month = intval( current_time( 'm' ) );

		// 指定日が今月に存在するかチェック
		$days_in_month = intval( date_i18n( 't', mktime( 0, 0, 0, $month, 1, $year ) ) );
		$actual_day    = min( $schedule_day, $days_in_month );

		$schedule_time = mktime( $schedule_hour, $schedule_minute, 0, $month, $actual_day, $year );

		// 既に過ぎている場合は来月に設定
		if ( $schedule_time < time() ) {
			$month++;
			if ( $month > 12 ) {
				$month = 1;
				$year++;
			}

			// 来月の日数をチェック
			$days_in_next_month = intval( date_i18n( 't', mktime( 0, 0, 0, $month, 1, $year ) ) );
			$actual_day         = min( $schedule_day, $days_in_next_month );

			$schedule_time = mktime( $schedule_hour, $schedule_minute, 0, $month, $actual_day, $year );
		}

		return $schedule_time;
	}

	/**
	 * エクスポートとメール送信を実行
	 */
	public function execute_export_and_send() {
		$settings = get_option( self::OPTION_NAME );

		// CSVの生成
		$csv_generator = new AIOS_ALM_CSV_Generator();
		$export_days   = isset( $settings['export_days'] ) ? intval( $settings['export_days'] ) : 30;
		$csv_file      = $csv_generator->generate( $export_days );

		if ( is_wp_error( $csv_file ) ) {
			// エラーログに記録
			error_log( 'AIOS Audit Log Mailer: CSV generation failed - ' . $csv_file->get_error_message() );
			$this->update_last_execution( false, $csv_file->get_error_message() );
			return;
		}

		// メールの送信
		$email_sender = new AIOS_ALM_Email_Sender();
		$email_addresses = isset( $settings['email_addresses'] ) ? $settings['email_addresses'] : '';
		$result = $email_sender->send( $email_addresses, $csv_file );

		// CSVファイルの削除
		if ( file_exists( $csv_file ) ) {
			$upload_dir = wp_upload_dir();
			$temp_dir = $upload_dir['basedir'] . '/aios-alm-temp';

			// パスが想定ディレクトリ内にあることを確認
			$real_csv_path = realpath( $csv_file );
			$real_temp_dir = realpath( $temp_dir );

			if ( $real_csv_path && $real_temp_dir && strpos( $real_csv_path, $real_temp_dir ) === 0 ) {
				if ( ! wp_delete_file( $csv_file ) ) {
					error_log( 'AIOS ALM: Failed to delete temporary file: ' . $csv_file );
				}
			}
		}

		// 最終実行情報を更新
		if ( is_wp_error( $result ) ) {
			error_log( 'AIOS Audit Log Mailer: Email sending failed - ' . $result->get_error_message() );
			$this->update_last_execution( false, $result->get_error_message() );
		} else {
			$this->update_last_execution( true, __( 'Successfully sent', 'aios-audit-log-mailer' ) );
		}

		// 次回のスケジュールを再設定
		$this->schedule_cron_event();
	}

	/**
	 * 最終実行情報を更新
	 *
	 * @param bool   $success 成功かどうか
	 * @param string $message メッセージ
	 */
	private function update_last_execution( $success, $message ) {
		update_option( 'aios_alm_last_execution', array(
			'time'    => current_time( 'mysql' ),
			'success' => $success,
			'message' => $message,
		) );
	}

	/**
	 * All in One Securityプラグインの依存関係をチェック
	 */
	public function check_dependencies() {
		// plugin.phpの読み込み
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// All in One Securityプラグインがアクティブかチェック
		if ( ! is_plugin_active( 'all-in-one-wp-security-and-firewall/wp-security.php' ) &&
		     ! is_plugin_active( 'all-in-one-wp-security/wp-security.php' ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'AIOS Audit Log Mailer:', 'aios-audit-log-mailer' ); ?></strong>
					<?php esc_html_e( 'このプラグインを使用するには、All in One Security & Firewallプラグインがインストールされ、有効化されている必要があります。', 'aios-audit-log-mailer' ); ?>
				</p>
			</div>
			<?php
		}

		// 監査ログテーブルの存在確認
		global $wpdb;
		$table_name = $wpdb->prefix . 'aiowps_audit_log';
		if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) !== $table_name ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'AIOS Audit Log Mailer:', 'aios-audit-log-mailer' ); ?></strong>
					<?php esc_html_e( '監査ログテーブルが見つかりません。All in One Securityプラグインで監査ログ機能が有効になっているか確認してください。', 'aios-audit-log-mailer' ); ?>
				</p>
			</div>
			<?php
		}
	}
}

// プラグインの初期化
AIOS_Audit_Log_Mailer::get_instance();
