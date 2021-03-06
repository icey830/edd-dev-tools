<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EDD_DT_Customers {

	private static $instance;

	private function __construct() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->hooks();
	}

	static public function instance() {
		if ( !self::$instance ) {
			self::$instance = new EDD_DT_Customers();
		}

		return self::$instance;
	}

	private function hooks() {
		add_filter( 'edd_customer_tabs', array( $this, 'register_tab' ), 999, 1 );
		add_filter( 'edd_customer_views', array( $this, 'register_view' ), 10, 1 );
	}

	public function register_tab( $tabs ) {
		$tabs['meta'] = array( 'dashicon' => 'dashicons-networking', 'title' => _x( 'Meta', 'Customer Meta tab title', 'edd-dev-tools' ) );
		return $tabs;
	}

	public function register_view( $views ) {
		$views['meta'] = 'EDD_DT_Customers::display_meta_tab';
		return $views;
	}

	public static function display_meta_tab( $customer ) {
		global $wpdb;
		ini_set( 'xdebug.var_display_max_depth', 5 );
		ini_set( 'xdebug.var_display_max_children', 256 );
		ini_set( 'xdebug.var_display_max_data', 1024 );
		$meta_sql      = "SELECT meta_id, meta_key, meta_value FROM {$wpdb->prefix}edd_customermeta WHERE customer_id = $customer->id";
		$customer_meta = $wpdb->get_results( $meta_sql );
		?>

		<div id="edd-item-notes-wrapper">
			<div class="edd-item-notes-header">
				<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo $customer->name; ?></span>
			</div>
			<h3><?php _e( 'Customer Meta', 'edd-dev-tools' ); ?></h3>

			<div>
				<table class="wp-list-table widefat striped downloads">
					<thead>
						<tr>
							<th><?php _e( 'ID', 'edd-dev-tools' ); ?></th>
							<th><?php _e( 'Key', 'edd-dev-tools' ); ?></th>
							<th><?php _e( 'Value', 'edd-dev-tools' ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th><?php _e( 'ID', 'edd-dev-tools' ); ?></th>
							<th><?php _e( 'Key', 'edd-dev-tools' ); ?></th>
							<th><?php _e( 'Value', 'edd-dev-tools' ); ?></th>
						</tr>
					</tfoot>
					<tbody>
						<?php if ( ! empty( $customer_meta ) ) : ?>
							<?php foreach ( $customer_meta as $meta ) : ?>
								<tr>
									<td><?php echo $meta->meta_id; ?></td>
									<td><?php echo $meta->meta_key; ?></td>
									<td>

											<?php
											if ( is_serialized( $meta->meta_value ) ) {
												_e( 'Serialized Data', 'edd-dev-tools' );
												?>
												<span class="dashicons dashicons-visibility" title="<?php echo esc_attr( $meta->meta_value ); ?>"></span>
												<p>
													<?php edd_dev_tools()->print_pre( unserialize( $meta->meta_value ) ); ?>
												</p>
												<?php
											} else {
												?><code><?php echo $meta->meta_value; ?></code><?php
											}
											?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr><td colspan="3"><?php printf( __( 'No Customer Meta Found', 'edd-dev-tools' ), edd_get_label_plural() ); ?></td></tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>

		</div>

		<?php
	}

}

EDD_DT_Customers::instance();
