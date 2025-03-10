<?php
/**
 * Plugin Name: WC Check Payments
 * Plugin URI: https://rayflores.com
 * Description: A simple plugin to add check payments to WooCommerce.
 * Version: 1.1.2
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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes_woocommerce_page_wc-orders', array( $this, 'add_order_meta_box' ) );
		add_action( 'wp_ajax_process_check_payment', array( $this, 'process_check_payment' ) );

		add_action( 'init', array( $this, 'add_check_payments_cpt' ) );
		$this->config = json_decode( $this->config, true );
		$this->process_cpts();
		add_action( 'add_meta_boxes', array( $this, 'add_cpt_meta_boxes' ) );
		add_action( 'admin_head', array( $this, 'admin_head' ) );
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
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box( $post ) {
		$order    = wc_get_order( get_the_ID() );
		$order_id = $order->get_id();
		echo 'This is the order ID: ' . esc_html( $order_id );
		include plugin_dir_path( __FILE__ ) . 'views/html-check-payments-meta-box.php';
	}

	/**
	 * Get the Payments
	 */
	public function payments() {
	}

	/**
	 * Process the check and save the data.
	 */
	public function process_check_payment() {
		if ( ! isset( $_POST['wc_check_payment_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wc_check_payment_nonce'] ) ), 'wc_check_payment' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$order_id     = isset( $_POST['order_id'] ) ? sanitize_text_field( wp_unslash( $_POST['order_id'] ) ) : '';
		$check_number = isset( $_POST['check_number'] ) ? sanitize_text_field( wp_unslash( $_POST['check_number'] ) ) : '';
		$check_date   = isset( $_POST['check_date'] ) ? sanitize_text_field( wp_unslash( $_POST['check_date'] ) ) : '';
		$check_amount = isset( $_POST['check_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['check_amount'] ) ) : '';

		$order = wc_get_order( $order_id );

		$check_payment     = array(
			'post_title'  => $order->get_order_number(),
			'post_status' => 'publish',
			'post_type'   => 'check_payment',
		);
		$new_check_payment = wp_insert_post( $check_payment );

		update_post_meta( $new_check_payment, 'check_number', $check_number );
		update_post_meta( $new_check_payment, 'check_date', $check_date );
		update_post_meta( $new_check_payment, 'check_amount', $check_amount );

		$order->calculate_totals();
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
/**
 * Version check.
 */
if ( is_admin() ) {
	define( 'GH_REQUEST_URI', 'https://api.github.com/repos/%s/%s/releases' );
	define( 'GHPU_USERNAME', 'rayflores' );
	define( 'GHPU_REPOSITORY', 'wc-check-payments' );
	define( 'GHPU_AUTH_TOKEN', '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' );

	include_once plugin_dir_path( __FILE__ ) . '/ghpluginupdater.php';

	$updater = new GhPluginUpdater( __FILE__ );
	$updater->init();
}
