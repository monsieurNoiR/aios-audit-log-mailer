<?php
/**
 * 管理画面クラス
 *
 * WordPress管理画面に設定ページを追加する
 *
 * @package AIOS_Audit_Log_Mailer
 */

// 直接アクセスを防止
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理画面クラス
 */
class AIOS_ALM_Admin_Page {

	/**
	 * シングルトンインスタンス
	 *
	 * @var AIOS_ALM_Admin_Page
	 */
	private static $instance = null;

	/**
	 * シングルトンインスタンスを取得
	 *
	 * @return AIOS_ALM_Admin_Page
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_aios_alm_manual_export', array( $this, 'handle_manual_export' ) );
	}

	/**
	 * 管理メニューに設定ページを追加
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'AIOS監査ログメーラー設定', 'aios-audit-log-mailer' ),
			__( 'AIOS監査ログメーラー', 'aios-audit-log-mailer' ),
			'manage_options',
			'aios-audit-log-mailer',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * 設定を登録
	 */
	public function register_settings() {
		register_setting(
			'aios_alm_settings_group',
			AIOS_Audit_Log_Mailer::OPTION_NAME,
			array( $this, 'validate_settings' )
		);
	}

	/**
	 * 設定ページをレンダリング
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'このページにアクセスする権限がありません。', 'aios-audit-log-mailer' ) );
		}

		$settings = get_option( AIOS_Audit_Log_Mailer::OPTION_NAME );
		$last_execution = get_option( 'aios_alm_last_execution' );
		$next_scheduled = wp_next_scheduled( AIOS_Audit_Log_Mailer::CRON_HOOK );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'aios_alm_settings_group' );
				?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="enabled"><?php esc_html_e( '自動送信を有効化', 'aios-audit-log-mailer' ); ?></label>
						</th>
						<td>
							<input type="checkbox" id="enabled" name="<?php echo esc_attr( AIOS_Audit_Log_Mailer::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( isset( $settings['enabled'] ) && $settings['enabled'], true ); ?>>
							<p class="description"><?php esc_html_e( '月次での自動送信を有効にします。', 'aios-audit-log-mailer' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="email_addresses"><?php esc_html_e( '送信先メールアドレス', 'aios-audit-log-mailer' ); ?></label>
						</th>
						<td>
							<input type="text" id="email_addresses" name="<?php echo esc_attr( AIOS_Audit_Log_Mailer::OPTION_NAME ); ?>[email_addresses]" value="<?php echo esc_attr( isset( $settings['email_addresses'] ) ? $settings['email_addresses'] : '' ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( '複数のメールアドレスを指定する場合は、カンマ区切りで入力してください。例: mail1@example.com, mail2@example.com', 'aios-audit-log-mailer' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="schedule_day"><?php esc_html_e( '実行日', 'aios-audit-log-mailer' ); ?></label>
						</th>
						<td>
							<select id="schedule_day" name="<?php echo esc_attr( AIOS_Audit_Log_Mailer::OPTION_NAME ); ?>[schedule_day]">
								<?php
								$schedule_day = isset( $settings['schedule_day'] ) ? intval( $settings['schedule_day'] ) : 1;
								for ( $i = 1; $i <= 28; $i++ ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $i ),
										selected( $schedule_day, $i, false ),
										esc_html( $i . '日' )
									);
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( '毎月何日に実行するか設定します。（1〜28日）', 'aios-audit-log-mailer' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="schedule_time"><?php esc_html_e( '実行時刻', 'aios-audit-log-mailer' ); ?></label>
						</th>
						<td>
							<?php
							$schedule_hour = isset( $settings['schedule_hour'] ) ? intval( $settings['schedule_hour'] ) : 2;
							$schedule_minute = isset( $settings['schedule_minute'] ) ? intval( $settings['schedule_minute'] ) : 0;
							?>
							<select id="schedule_hour" name="<?php echo esc_attr( AIOS_Audit_Log_Mailer::OPTION_NAME ); ?>[schedule_hour]">
								<?php
								for ( $i = 0; $i < 24; $i++ ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $i ),
										selected( $schedule_hour, $i, false ),
										esc_html( sprintf( '%02d', $i ) )
									);
								}
								?>
							</select>
							:
							<select id="schedule_minute" name="<?php echo esc_attr( AIOS_Audit_Log_Mailer::OPTION_NAME ); ?>[schedule_minute]">
								<?php
								for ( $i = 0; $i < 60; $i += 5 ) {
									printf(
										'<option value="%s" %s>%s</option>',
										esc_attr( $i ),
										selected( $schedule_minute, $i, false ),
										esc_html( sprintf( '%02d', $i ) )
									);
								}
								?>
							</select>
							<p class="description"><?php esc_html_e( '実行する時刻を設定します。（サーバー時刻）', 'aios-audit-log-mailer' ); ?></p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="export_days"><?php esc_html_e( 'エクスポート対象期間', 'aios-audit-log-mailer' ); ?></label>
						</th>
						<td>
							<input type="number" id="export_days" name="<?php echo esc_attr( AIOS_Audit_Log_Mailer::OPTION_NAME ); ?>[export_days]" value="<?php echo esc_attr( isset( $settings['export_days'] ) ? $settings['export_days'] : 30 ); ?>" min="1" max="365" class="small-text">
							日
							<p class="description"><?php esc_html_e( '過去何日分のログをエクスポートするか設定します。（1〜365日）', 'aios-audit-log-mailer' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( '設定を保存', 'aios-audit-log-mailer' ) ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( '実行状況', 'aios-audit-log-mailer' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( '次回実行予定', 'aios-audit-log-mailer' ); ?></th>
					<td>
						<?php
						if ( $next_scheduled ) {
							echo esc_html( date_i18n( 'Y年m月d日 H:i:s', $next_scheduled ) );
						} else {
							echo '<span style="color: #999;">' . esc_html__( 'スケジュールされていません', 'aios-audit-log-mailer' ) . '</span>';
						}
						?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( '最終実行日時', 'aios-audit-log-mailer' ); ?></th>
					<td>
						<?php
						if ( $last_execution && isset( $last_execution['time'] ) ) {
							echo esc_html( $last_execution['time'] );
						} else {
							echo '<span style="color: #999;">' . esc_html__( 'まだ実行されていません', 'aios-audit-log-mailer' ) . '</span>';
						}
						?>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( '最終実行結果', 'aios-audit-log-mailer' ); ?></th>
					<td>
						<?php
						if ( $last_execution && isset( $last_execution['success'] ) ) {
							if ( $last_execution['success'] ) {
								echo '<span style="color: green;">✓ ' . esc_html__( '成功', 'aios-audit-log-mailer' ) . '</span>';
							} else {
								echo '<span style="color: red;">✗ ' . esc_html__( '失敗', 'aios-audit-log-mailer' ) . '</span>';
							}

							if ( isset( $last_execution['message'] ) ) {
								echo '<br><span style="color: #666;">' . esc_html( $last_execution['message'] ) . '</span>';
							}
						} else {
							echo '<span style="color: #999;">' . esc_html__( 'まだ実行されていません', 'aios-audit-log-mailer' ) . '</span>';
						}
						?>
					</td>
				</tr>
			</table>

			<hr>

			<h2><?php esc_html_e( '手動実行', 'aios-audit-log-mailer' ); ?></h2>
			<p><?php esc_html_e( 'テスト目的で手動でエクスポートとメール送信を実行できます。', 'aios-audit-log-mailer' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aios_alm_manual_export">
				<?php wp_nonce_field( 'aios_alm_manual_export', 'aios_alm_nonce' ); ?>
				<?php submit_button( __( '今すぐ実行', 'aios-audit-log-mailer' ), 'secondary', 'submit', false ); ?>
			</form>

			<hr>

			<h2><?php esc_html_e( 'システム情報', 'aios-audit-log-mailer' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'プラグインバージョン', 'aios-audit-log-mailer' ); ?></th>
					<td><?php echo esc_html( AIOS_ALM_VERSION ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'WordPressバージョン', 'aios-audit-log-mailer' ); ?></th>
					<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'PHPバージョン', 'aios-audit-log-mailer' ); ?></th>
					<td><?php echo esc_html( phpversion() ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'サーバー時刻', 'aios-audit-log-mailer' ); ?></th>
					<td><?php echo esc_html( current_time( 'Y-m-d H:i:s' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'All in One Security', 'aios-audit-log-mailer' ); ?></th>
					<td>
						<?php
						// plugin.phpの読み込み
						if ( ! function_exists( 'is_plugin_active' ) ) {
							require_once ABSPATH . 'wp-admin/includes/plugin.php';
						}
						if ( is_plugin_active( 'all-in-one-wp-security-and-firewall/wp-security.php' ) || is_plugin_active( 'all-in-one-wp-security/wp-security.php' ) ) {
							echo '<span style="color: green;">✓ ' . esc_html__( '有効', 'aios-audit-log-mailer' ) . '</span>';
						} else {
							echo '<span style="color: red;">✗ ' . esc_html__( '無効', 'aios-audit-log-mailer' ) . '</span>';
						}
						?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '監査ログテーブル', 'aios-audit-log-mailer' ); ?></th>
					<td>
						<?php
						global $wpdb;
						$table_name = $wpdb->prefix . 'aiowps_audit_log';
						if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
							$table_name_escaped = esc_sql( $table_name );
							// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is safely escaped using esc_sql()
							$count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$table_name_escaped}`" );
							echo '<span style="color: green;">✓ ' . esc_html__( '存在', 'aios-audit-log-mailer' ) . '</span>';
							echo ' (' . esc_html( number_format( $count ) ) . ' ' . esc_html__( '件のログ', 'aios-audit-log-mailer' ) . ')';
						} else {
							echo '<span style="color: red;">✗ ' . esc_html__( '存在しません', 'aios-audit-log-mailer' ) . '</span>';
						}
						?>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * 設定値のバリデーション
	 *
	 * @param array $input 入力値
	 * @return array 検証済みの設定値
	 */
	public function validate_settings( $input ) {
		$validated = array();

		// 有効/無効
		$validated['enabled'] = isset( $input['enabled'] ) && $input['enabled'] == 1;

		// メールアドレス
		if ( isset( $input['email_addresses'] ) ) {
			$addresses = array_map( 'trim', explode( ',', $input['email_addresses'] ) );
			$valid_addresses = array();

			foreach ( $addresses as $address ) {
				if ( is_email( $address ) ) {
					$valid_addresses[] = sanitize_email( $address );
				}
			}

			if ( empty( $valid_addresses ) ) {
				add_settings_error(
					AIOS_Audit_Log_Mailer::OPTION_NAME,
					'invalid_email',
					__( '有効なメールアドレスを入力してください。', 'aios-audit-log-mailer' ),
					'error'
				);
				$validated['email_addresses'] = get_option( 'admin_email' );
			} else {
				$validated['email_addresses'] = implode( ', ', $valid_addresses );
			}
		}

		// スケジュール日
		$validated['schedule_day'] = isset( $input['schedule_day'] ) ? intval( $input['schedule_day'] ) : 1;
		if ( $validated['schedule_day'] < 1 || $validated['schedule_day'] > 28 ) {
			$validated['schedule_day'] = 1;
		}

		// スケジュール時刻
		$validated['schedule_hour'] = isset( $input['schedule_hour'] ) ? intval( $input['schedule_hour'] ) : 2;
		if ( $validated['schedule_hour'] < 0 || $validated['schedule_hour'] > 23 ) {
			$validated['schedule_hour'] = 2;
		}

		$validated['schedule_minute'] = isset( $input['schedule_minute'] ) ? intval( $input['schedule_minute'] ) : 0;
		if ( $validated['schedule_minute'] < 0 || $validated['schedule_minute'] > 59 ) {
			$validated['schedule_minute'] = 0;
		}

		// エクスポート日数
		$validated['export_days'] = isset( $input['export_days'] ) ? intval( $input['export_days'] ) : 30;
		if ( $validated['export_days'] < 1 || $validated['export_days'] > 365 ) {
			$validated['export_days'] = 30;
		}

		// 設定保存後、Cronスケジュールを更新
		add_action( 'shutdown', array( AIOS_Audit_Log_Mailer::get_instance(), 'schedule_cron_event' ) );

		return $validated;
	}

	/**
	 * 手動エクスポートの処理
	 */
	public function handle_manual_export() {
		// 権限チェック
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'このページにアクセスする権限がありません。', 'aios-audit-log-mailer' ) );
		}

		// Nonceチェック
		if ( ! isset( $_POST['aios_alm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aios_alm_nonce'] ) ), 'aios_alm_manual_export' ) ) {
			wp_die( esc_html__( '不正なリクエストです。', 'aios-audit-log-mailer' ) );
		}

		// エクスポートとメール送信を実行
		AIOS_Audit_Log_Mailer::get_instance()->execute_export_and_send();

		// リダイレクト
		wp_safe_redirect( add_query_arg(
			array(
				'page'    => 'aios-audit-log-mailer',
				'message' => 'manual_export_done',
			),
			admin_url( 'options-general.php' )
		) );
		exit;
	}
}
