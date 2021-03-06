<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EDD_DT_Admin_Bar {

	private static $instance;

	private function __construct() {
		$this->hooks();
	}

	static public function instance() {
		if ( !self::$instance ) {
			self::$instance = new EDD_DT_Admin_Bar();
		}

		return self::$instance;
	}

	private function hooks() {
		// Add items to the admin bar
		add_action( 'admin_bar_menu', array( $this, 'blog_id' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'git_branch' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'empty_cart' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'delete_licenses' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'clear_jilt' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'view_payment' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'test_mode' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'is_checkout' ), 999 );

		// Capture actions from links added to the menu bar, if needed
		add_action( 'init', array( $this, 'process_empty_cart' ) );
		add_action( 'init', array( $this, 'process_delete_licenses' ) );
		add_action( 'init', array( $this, 'process_clear_jilt' ) );
	}

	public function blog_id( $wp_admin_bar ) {

		if ( ! is_multisite() ) {
			return;
		}

		$args = array(
			'id'    => 'blog_id',
			'title' => 'Blog #' . get_current_blog_id(),
		);
		$wp_admin_bar->add_node( $args );
	}

	public function git_branch( $wp_admin_bar ) {

		$git_info = @file( EDD_PLUGIN_DIR . '/.git/HEAD', FILE_USE_INCLUDE_PATH );
		if ( $git_info ) {
			$first_line    = $git_info[ 0 ];
			$branch_string = explode( '/', $first_line, 3 );
			$branch        = isset( $branch_string[ 2 ] ) ? $branch_string[ 2 ] : substr( $branch_string[ 0 ], 0, 7 );
		} else {
			$branch = 'EDD v' . EDD_VERSION;
		}

		$args = array(
			'id'    => 'edd_branch',
			'title' => sprintf( '<span class="ab-icon"></span> <span class="ab-label">%s</span>', $branch ),
		);
		$wp_admin_bar->add_node( $args );
	}

	public function empty_cart( $wp_admin_bar ) {
		$title = __( 'Empty Cart', 'edd-dev-tools' );
		$count = count( edd_get_cart_contents() );

		if ( ! empty( $count ) ) {
			$title .= '(' . $count . ')';
		}

		$args  = array(
			'id'     => 'edd_empty_cart',
			'title'  => $title,
			'href'   => add_query_arg( 'empty_cart', true ),
			'parent' => 'edd_branch',
		);
		$wp_admin_bar->add_node( $args );
	}

	public function process_empty_cart() {
		if ( isset( $_GET['empty_cart'] ) ) {
			if ( $_GET['empty_cart'] == '1' ) {
				edd_empty_cart();

				wp_redirect( remove_query_arg( 'empty_cart' ) );
				exit;
			}
		}
	}

	public function delete_licenses( $wp_admin_bar ) {
		if ( function_exists( 'edd_software_licensing' ) ) {
			$title = __( 'Delete Licenses', 'edd-dev-tools' );

			$args  = array(
				'id'     => 'edd_delete_licenses',
				'title'  => $title,
				'href'   => add_query_arg( 'delete_licenses', true ),
				'parent' => 'edd_branch',
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	public function process_delete_licenses() {
		global $wpdb;
		if ( isset( $_GET['delete_licenses'] ) ) {
			if ( $_GET['delete_licenses'] == '1' ) {
				$post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'edd_license'" );
				if ( ! empty( $post_ids ) ) {
					$post_ids = implode( ',', $post_ids );
					$wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN ({$post_ids})" );
					$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ({$post_ids})" );
				}

				wp_redirect( remove_query_arg( 'delete_licenses' ) );
				exit;
			}
		}
	}

	public function clear_jilt( $wp_admin_bar ) {
		if ( function_exists( 'edd_jilt' ) ) {
			$title = __( 'Clear Jilt', 'edd-dev-tools' );

			$args  = array(
				'id'     => 'edd_clear_jilt',
				'title'  => $title,
				'href'   => add_query_arg( 'clear_jilt', true ),
				'parent' => 'edd_branch',
			);
			$wp_admin_bar->add_node( $args );
		}
	}

	public function process_clear_jilt() {
		if ( isset( $_GET['clear_jilt'] ) ) {
			if ( $_GET['clear_jilt'] == '1' ) {
				delete_user_meta( get_current_user_id(), '_edd_jilt_cart_token' );
				delete_user_meta( get_current_user_id(), '_edd_jilt_order_id' );
				delete_user_meta( get_current_user_id(), '_edd_jilt_pending_recovery' );
				EDD()->session->set( 'edd_jilt_order_id', '' );
				EDD()->session->set( 'edd_jilt_cart_token', '' );
				EDD()->session->set( '_edd_jilt_pending_recovery', '' );

				wp_redirect( remove_query_arg( 'clear_jilt' ) );
				exit;
			}
		}
	}

	public function view_payment( $wp_admin_bar ) {
		if ( ! edd_is_success_page() ) {
			return;
		}

		global $edd_receipt_args;
		$payment   = new EDD_Payment( $edd_receipt_args['id'] );

		if ( empty( $payment->ID ) ) {
			return;
		}

		$title = __( 'View Payment', 'edd-dev-tools' );

		$args  = array(
			'id'     => 'edd_view_payment',
			'title'  => $title,
			'href'   => admin_url( 'edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment->ID ),
			'parent' => 'edd_branch',
		);
		$wp_admin_bar->add_node( $args );
	}

	public function test_mode( $wp_admin_bar ) {
		if ( edd_is_test_mode() ) {
			$title = '<span style="color: #FFFF00">Test Mode</span>';
		} else {
			$title = '<span style="color: #33FF00">Live Mode</span>';
		}

		$args  = array(
			'id'     => 'edd_test_mode',
			'title'  => $title,
			'href'   => add_query_arg( 'edd_toggle_setting', 'test_mode' ),
			'parent' => 'edd_branch',
		);
		$wp_admin_bar->add_node( $args );
	}

	public function is_checkout( $wp_admin_bar ) {
		if ( edd_is_checkout() ) {
			$title = '<span style="color: #33FF00">Is Checkout</span>';
		} else {
			$title = '<span style="color: #FF3333">Not Checkout</span>';
		}

		$args  = array(
			'id'     => 'edd_is_checkout',
			'title'  => $title,
			'parent' => 'edd_branch',
		);
		$wp_admin_bar->add_node( $args );
	}
}

EDD_DT_Admin_Bar::instance();
