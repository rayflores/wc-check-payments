<?php
/**
 * Plugin Name: WC Check Payments
 * Plugin URI: https://rayflores.com
 * Description: A simple plugin to add check payments to WooCommerce.
 * Version: 1.1.3
 * Author: Ray Flores
 * Author URI: https://rayflores.com
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-check-payments
 *
 * @package WC_Check_Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Class WC_Check_Payments
 *
 * @package WC_Check_Payments
 */
class WC_Check_Payments {
	/**
	 * Plugin instance.
	 *
	 * @var WC_Check_Payments
	 */
	protected static $instance = null;

	/**
	 * CPT configuration.
	 *
	 * @var string
	 */
	private $config = '{"title":"Check Payment Data","description":"All Data about the check payment","prefix":"cp_","domain":"check-payments","class_name":"WC_Check_Payments","post-type":["post"],"context":"normal","priority":"high","cpt":"check-payment","fields":[{"type":"text","label":"Check Number","id":"cp_check-number"},{"type":"date","label":"Check Date","id":"cp_check-date"},{"type":"text","label":"Check Amount","id":"cp_check-amount"}]}';

	/**
	 * Payments.
	 *
	 * @var array
	 */
	private $payments = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( $this, 'add_order_meta_box' ) );
		add_action( 'add_meta_boxes_shop_order', array( $this, 'add_order_meta_box' ) );

		add_action( 'wp_ajax_process_check_payment', array( $this, 'process_check_payment' ) );

		add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'update_total_amount' ), 9999, 2 );
		add_action( 'woocommerce_admin_order_totals_after_total', array( $this, 'display_payments' ), 10, 1 );

		add_action( 'init', array( $this, 'add_check_payments_cpt' ) );
		$this->config = json_decode( $this->config, true );
		$this->process_cpts();
		add_action( 'add_meta_boxes', array( $this, 'add_cpt_meta_boxes' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
		add_action( 'save_post_check-payments', array( $this, 'save_post_payments' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_scripts' ) );
	}

	/**
	 * Display the payments.
	 *
	 * @param WC_Order $order The order.
	 */
	public function display_payments( $order_id ) {
		echo '<tr><td class="label">Check Payments:</td><td width="1%"></td><td class="total"><ul style="margin:0">';
		$payments = $this->payments( $order_id );
		if ( ! empty( $payments ) ) {
			foreach ( $payments as $payment ) {
				echo '<li style="margin:0"><a href="' . get_edit_post_link( $payment->id ) . '">' . wc_price( $payment->check_amount ) . '</a> (' . $payment->check_date . ')</li>';
			}
		}
	}
	/**
	 * Enqueue the scripts.
	 */
	public function admin_styles_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( wc_get_page_screen_id( 'shop-order' ) === $screen_id || wc_get_page_screen_id( 'woocommerce_page_wc-orders' ) === $screen_id ) {
			wp_enqueue_style( 'wc-check-payments', plugin_dir_url( __FILE__ ) . 'assets/css/style.css', array(), filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/style.css' ), 'all' );
			wp_enqueue_script( 'wc-check-payments', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array( 'jquery' ), filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/script.js' ), true );
			wp_localize_script(
				'wc-check-payments',
				'wcCP',
				array(
					'ajax_url'        => admin_url( 'admin-ajax.php' ),
					'cpnonce'         => wp_create_nonce( 'wc_check_payment' ),
					'currency'        => get_woocommerce_currency(),
					'currency_sumbol' => get_woocommerce_currency_symbol(),

				)
			);
		}
	}

	/**
	 * Save the payment metadata.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_post_payments( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// Save the metadata.
		foreach ( $this->config['fields'] as $field ) {
			switch ( $field['type'] ) {
				case 'checkbox':
					update_post_meta( $post_id, $field['id'], isset( $_POST[ $field['id'] ] ) ? $_POST[ $field['id'] ] : '' );
					break;
				default:
					if ( isset( $_POST[ $field['id'] ] ) ) {
						$sanitized = sanitize_text_field( $_POST[ $field['id'] ] );
						update_post_meta( $post_id, $field['id'], $sanitized );
					}
			}
		}
	}
	/**
	 * Add the meta box.
	 */
	public function add_order_meta_box() {
		add_meta_box(
			'wc_check_payment',
			'Check Payment',
			array( $this, 'render_meta_box' ),
			array( 'shop_order', 'woocommerce_page_wc-orders' ),
			'advanced',
			'high'
		);
	}

	/**
	 * Add the check payments CPT.
	 */
	public function add_check_payments_cpt() {
		$supports_array = array( 'title' );
		$args           = array(
			'labels'          => array(
				'name'          => 'Check Payments',
				'singular_name' => 'Check Payment',
				'plural_name'   => 'Check Payments',
			),
			'capability_type' => 'shop_order',
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => current_user_can( 'edit_others_shop_orders' ) ? 'woocommerce' : false,
			'supports'        => $supports_array,
			'rewrite'         => false,
			'menu_icon'       => 'dashicons-cart',
			'menu_position'   => 3,
			'show_in_rest'    => true,
		);
		register_post_type( 'check-payment', $args );
	}
	/**
	 * Render the meta box.
	 */
	public function render_meta_box() {
		$order    = wc_get_order( get_the_ID() );
		$order_id = $order->get_id();
		$this->payments( $order_id );
		include plugin_dir_path( __FILE__ ) . 'views/html-check-payments-meta-box.php';
	}

	/**
	 * Get the Payments
	 */
	public function payments( $order_id ) {
		$payments        = get_posts(
			array(
				'post_type'   => 'check-payment',
				'post_status' => 'publish',
				'numberposts' => -1,
				'title'       => $order_id,
				'fields'      => 'ids',
			)
		);
		$payment_objects = array();

		if ( ! empty( $payments ) ) {
			foreach ( $payments as $payment_id ) {
				$post_payment          = get_post( $payment_id );
				$payment               = new stdClass();
				$payment->id           = $post_payment->ID;
				$payment->title        = $post_payment->post_title;
				$payment->check_number = get_post_meta( $payment_id, 'cp_check-number', true );
				$payment->check_date   = get_post_meta( $payment_id, 'cp_check-date', true );
				$payment->check_amount = get_post_meta( $payment_id, 'cp_check-amount', true );
				$payment_objects[]     = $payment;
			}
		}
		$this->payments = $payment_objects;
		return $this->payments;
	}
	/**
	 * Update new total amount.
	 */
	public function update_total_amount( $and_taxes, $order ) {
		$payments = $this->payments( $order->get_id() );
		$total    = $order->get_total();
		$paid     = 0.00;
		foreach ( $payments as $payment ) {
			$paid += $payment->check_amount;
		}
		$new_total = $total - $paid;
		if ( $new_total <= 0 ) {
			$order->set_status( 'completed' );
		}
		$order->set_total( $new_total );
		if ( $new_total > 0 ) {
			$order->set_status( 'pending' );
		}
		$order->save();
	}

	/**
	 * Process the check and save the data.
	 */
	public function process_check_payment() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wc_check_payment' ) ) {
			return;
		}

		$order_id     = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
		$check_number = isset( $_POST['check_number'] ) ? sanitize_text_field( wp_unslash( $_POST['check_number'] ) ) : '';
		$check_date   = isset( $_POST['check_date'] ) ? sanitize_text_field( wp_unslash( $_POST['check_date'] ) ) : '';
		$check_amount = isset( $_POST['check_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['check_amount'] ) ) : '';

		$order = wc_get_order( $order_id );

		$check_payment     = array(
			'post_title'  => $order_id,
			'post_status' => 'publish',
			'post_type'   => 'check-payment',
		);
		$new_check_payment = wp_insert_post( $check_payment );

		update_post_meta( $new_check_payment, 'cp_check-number', $check_number );
		update_post_meta( $new_check_payment, 'cp_check-date', $check_date );
		update_post_meta( $new_check_payment, 'cp_check-amount', $check_amount );

		$order->calculate_totals();
		$order->save();
		wp_send_json_success(
			array(
				'success' => true,
				'paid'    => true,
			)
		);
	}

	/**
	 * Register the custom post type.
	 */
	public function process_cpts() {
		if ( ! empty( $this->config['cpt'] ) ) {
			if ( empty( $this->config['post-type'] ) ) {
				$this->config['post-type'] = array();
			}
			$parts                     = explode( ',', $this->config['cpt'] );
			$parts                     = array_map( 'trim', $parts );
			$this->config['post-type'] = array_merge( $this->config['post-type'], $parts );
		}
	}

	/**
	 * Add the cpt meta box.
	 */
	public function add_cpt_meta_boxes() {
		foreach ( $this->config['post-type'] as $screen ) {
			add_meta_box(
				sanitize_title( $this->config['title'] ),
				$this->config['title'],
				array( $this, 'add_cpt_meta_box_callback' ),
				$screen,
				$this->config['context'],
				$this->config['priority']
			);
		}
	}

	/**
	 * Add styles to the admin head.
	 */
	public function admin_head() {
		global $typenow;
		if ( in_array( $typenow, $this->config['post-type'] ) ) {
			?><style>.rwp-description {
					margin-bottom: 1rem;
				}</style>
				<?php
		}
	}

	/**
	 * Add the cpt meta box callback.
	 */
	public function add_cpt_meta_box_callback() {
		echo '<div class="rwp-description">' . esc_html( $this->config['description'] ) . '</div>';
		$this->fields_table();
	}

	/**
	 * Render the fields table.
	 */
	private function fields_table() {
		?>
		<table class="form-table" role="presentation">
			<tbody>
			<?php
			foreach ( $this->config['fields'] as $field ) {
				?>
					<tr>
						<th scope="row"><?php $this->label( $field ); ?></th>
						<td><?php $this->field( $field ); ?></td>
					</tr>
					<?php
			}
			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render the label.
	 *
	 * @param array $field The field.
	 */
	private function label( $field ) {
		switch ( $field['type'] ) {
			default:
				printf(
					'<label class="" for="%s">%s</label>',
					esc_attr( $field['id'] ),
					esc_html( $field['label'] )
				);
		}
	}

	/**
	 * Render the field.
	 *
	 * @param array $field The field.
	 */
	private function field( $field ) {
		switch ( $field['type'] ) {
			case 'date':
				$this->input_minmax( $field );
				break;
			default:
				$this->input( $field );
		}
	}

	/**
	 * Render the input.
	 *
	 * @param array $field The field.
	 */
	private function input( $field ) {
		printf(
			'<input class="regular-text %s" id="%s" name="%s" %s type="%s" value="%s">',
			isset( $field['class'] ) ? esc_attr( $field['class'] ) : '',
			esc_attr( $field['id'] ),
			esc_attr( $field['id'] ),
			isset( $field['pattern'] ) ? "pattern='" . esc_attr( $field['pattern'] ) . "'" : '',
			esc_attr( $field['type'] ),
			esc_attr( $this->value( $field ) )
		);
	}

	/**
	 * Render the input with min and max.
	 *
	 * @param array $field The field.
	 */
	private function input_minmax( $field ) {
		printf(
			'<input class="regular-text" id="%s" %s %s name="%s" %s type="%s" value="%s">',
			esc_attr( $field['id'] ),
			isset( $field['max'] ) ? "max='" . esc_attr( $field['max'] ) . "'" : '',
			isset( $field['min'] ) ? "min='" . esc_attr( $field['min'] ) . "'" : '',
			esc_attr( $field['id'] ),
			isset( $field['step'] ) ? "step='" . esc_attr( $field['step'] ) . "'" : '',
			esc_attr( $field['type'] ),
			esc_attr( $this->value( $field ) )
		);
	}

	/**
	 * Get the value.
	 *
	 * @param array $field The field.
	 */
	private function value( $field ) {
		global $post;
		if ( metadata_exists( 'post', $post->ID, $field['id'] ) ) {
			$value = get_post_meta( $post->ID, $field['id'], true );
		} elseif ( isset( $field['default'] ) ) {
			$value = $field['default'];
		} else {
			return '';
		}
		return str_replace( '\u0027', "'", $value );
	}

	/**
	 * Get the plugin instance.
	 *
	 * @return WC_Check_Payments
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

/**
 * Initialize the plugin.
 */
function wc_check_payments() {
	return WC_Check_Payments::get_instance();
}
add_action( 'plugins_loaded', 'wc_check_payments' );

