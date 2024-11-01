<?php
/**
 * Plugin Name:     Maintenance Mode For WooCommerce [[GrandPlugins]]
 * Description:     allow maintenance mode for shops, adds redirects - general notice.
 * Author:          GrandPlugins
 * Author URI:      https://grandplugins.com
 * Plugin URI:      https://grandplugins.com/product/woocommerce-shop-maintenance-pro/?utm_source=free
 * Text Domain:     ultimate-maintenance-mode-for-woocommerce
 * Std Name:        gpls-woo-shop-maintenance
 * Version:         1.0.1
 *
 * @package         Maintenance_Mode_for_WooCommerce
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'GPLS_WSM_Class' ) ) :


	/**
	 * Main Class.
	 */
	class GPLS_WSM_Class {

		/**
		 * The class Single Instance.
		 *
		 * @var object
		 */
		private static $instance;

		/**
		 * Plugin Info
		 *
		 * @var array
		 */
		private static $plugin_info;

		/**
		 * Settings Object
		 *
		 * @var object
		 */
		private $settings_obj;

		/**
		 * Is Preview.
		 *
		 * @var boolean
		 */
		public $is_preview = false;

		/**
		 * Plugin Settings Array.
		 *
		 * @var array
		 */
		private $settings;

		/**
		 * Plugin Main Settings Array.
		 *
		 * @var array
		 */
		private $main_settings;

		/**
		 * Core Object
		 *
		 * @var object
		 */
		private static $core;

		/**
		 * Initialize the class instance.
		 *
		 * @return object
		 */
		public static function init() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Core Actions Hook.
		 *
		 * @return void
		 */
		public static function core_actions( $action_type ) {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
			self::$core = new GPLSCore\GPLS_WSM\Core( self::$plugin_info );
			if ( 'activated' === $action_type ) {
				self::$core->plugin_activated();
			} elseif ( 'deactivated' === $action_type ) {
				self::$core->plugin_deactivated();
			} elseif ( 'uninstall' === $action_type ) {
				self::$core->plugin_uninstalled();
			}
		}

		/**
		 * Plugin Activated Function
		 *
		 * @return void
		 */
		public static function plugin_activated() {
			self::setup_plugin_info();
			if ( is_plugin_active( 'gpls-woo-shop-maintenance/' . self::$plugin_info['name'] . '-pro.php' ) ) {
				deactivate_plugins( 'gpls-woo-shop-maintenance/' . self::$plugin_info['name'] . '-pro.php' );
			}

			if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				deactivate_plugins( self::$plugin_info['basename'] );
				wp_die( __( 'WooCommerce plugin is required in order to activate the plugin', 'ultimate-maintenance-mode-for-woocommerce' ) );
			}

			self::core_actions( 'activated' );
			register_uninstall_hook( __FILE__, array( 'GPLS_WSM_Class', 'plugin_uninstalled' ) );
		}

		/**
		 * Plugin Deactivated Hook.
		 *
		 * @return void
		 */
		public static function plugin_deactivated() {
			self::setup_plugin_info();
			self::core_actions( 'deactivated' );
		}

		/**
		 * Plugin Installed hook.
		 *
		 * @return void
		 */
		public static function plugin_uninstalled() {
			self::setup_plugin_info();
			self::core_actions( 'uninstall' );
		}

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			self::setup_plugin_info();
			$this->load_languages();
			$this->includes();
			self::$core          = new GPLSCore\GPLS_WSM\Core( self::$plugin_info );
			$this->settings_obj  = new GPLSCore\GPLS_WSM_Settings\GPLS_WSM_Settings( self::$core, self::$plugin_info );
			$this->main_settings = $this->settings_obj->get_main_settings();
			$this->hooks();
		}

		/**
		 * Define Constants
		 *
		 * @param string $key
		 * @param string $value
		 * @return void
		 */
		public function define( $key, $value ) {
			if ( ! defined( $key ) ) {
				define( $key, $value );
			}
		}

		/**
		 * Set Plugin Info
		 *
		 * @return array
		 */
		public static function setup_plugin_info() {
			$plugin_data = get_file_data(
				__FILE__,
				array(
					'Version'     => 'Version',
					'Name'        => 'Plugin Name',
					'URI'         => 'Plugin URI',
					'SName'       => 'Std Name',
					'text_domain' => 'Text Domain',
				),
				false
			);

			self::$plugin_info = array(
				'id'             => 14,
				'basename'       => plugin_basename( __FILE__ ),
				'version'        => $plugin_data['Version'],
				'name'           => $plugin_data['SName'],
				'text_domain'    => $plugin_data['text_domain'],
				'file'           => __FILE__,
				'plugin_url'     => $plugin_data['URI'],
				'public_name'    => $plugin_data['Name'],
				'path'           => trailingslashit( plugin_dir_path( __FILE__ ) ),
				'url'            => trailingslashit( plugin_dir_url( __FILE__ ) ),
				'options_page'   => $plugin_data['SName'] . '-settings-tab',
				'localize_var'   => str_replace( '-', '_', $plugin_data['SName'] ) . '_localize_data',
				'type'           => 'free',
				'general_prefix' => 'gpls-plugins-general-prefix',
				'classes_prefix' => 'gpls-wsm',
				'review_link'    => 'https://wordpress.org/support/plugin/ultimate-maintenance-mode-for-woocommerce/reviews/#new-post',
				'pro_link'       => 'https://grandplugins.com/product/woocommerce-maintenance-mode-pro/?utm_source=free',
			);
		}

		/**
		 * Include plugin files
		 *
		 * @return void
		 */
		public function includes() {
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'core/bootstrap.php';
			require_once trailingslashit( plugin_dir_path( __FILE__ ) ) . 'settings.php';
		}

		/**
		 * Load languages Folder.
		 *
		 * @return void
		 */
		public function load_languages() {
			load_plugin_textdomain( self::$plugin_info['text_domain'], false, self::$plugin_info['path'] . 'languages/' );
		}

		/**
		 * register Plugin Hooks.
		 *
		 * @return void
		 */
		public function hooks() {

			if ( defined( 'DOING_CRON' ) ) {
				return;
			}

			if ( 'yes' === $this->main_settings['activate'] || ( ! empty( $_GET['gpls-wsm-preview'] ) && ( 'true' == wp_unslash( $_GET['gpls-wsm-preview'] ) ) && ! empty( $_GET['nonce'] ) ) ) {

				$this->is_preview = false;

				// End Date Schedule.
				if ( ! $this->is_preview && ! empty( $this->main_settings['end_date'] ) && ! empty( strtotime( $this->main_settings['end_date'] ) ) ) {
					if ( time() >= strtotime( $this->main_settings['end_date'] ) ) {
						return;
					}
				}

				if ( ! empty( $_GET['gpls-wsm-preview'] ) && ( 'true' == wp_unslash( $_GET['gpls-wsm-preview'] ) ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), self::$plugin_info['name'] . '-popup-preview' ) ) {
					$this->is_preview = true;
				}

				$roles_settings = $this->settings_obj->get_roles_settings();
				// User Roles Exception.
				if ( ! $this->is_preview && 'yes' === $roles_settings['roles_exception'] ) {
					if ( is_multisite() && is_super_admin( get_current_user_id() ) && in_array( 'administrator', $roles_settings['roles'] ) ) {
						return;
					} else {
						$user = wp_get_current_user();
						if ( ! empty( array_intersect( $roles_settings['roles'], $user->roles ) ) ) {
							return;
						}
					}
				}

				// == Disable Add To Cart - Empty the Cart == //

				add_filter( 'woocommerce_is_purchasable', array( $this, 'make_product_unpurchasable' ), PHP_INT_MAX, 2 );

				// Loop Add_to_cart Button Link.
				add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'filter_loop_add_to_cart_button' ), PHP_INT_MAX, 3 );

				// Single Product Page Add_to_cart Button Link.
				add_action( 'woocommerce_single_product_summary', array( $this, 'filter_single_product_add_to_cart_button' ), 10 );

				if ( ! $this->is_preview ) {

					// Zeros The Cart Quantity.
					add_filter( 'woocommerce_add_to_cart_quantity', array( $this, 'disable_add_to_cart' ), PHP_INT_MAX, 2 );

					// Empty the User Cart.
					add_action( 'wp_loaded', array( $this, 'empty_user_cart_for_maintenance' ), 11 );

				}

				// Maintenance Notice.
				add_action( 'woocommerce_before_main_content', array( $this, 'handle_notice' ), 15 );
				add_action( 'woocommerce_before_account_navigation', array( $this, 'handle_notice' ), 15 );
				add_action( 'woocommerce_check_cart_items', array( $this, 'handle_notice' ), 15 );

				// Handle Redirects.
				if ( ! $this->is_preview ) {
					add_action( 'template_redirect', array( $this, 'handle_redirects' ) );
				}

				// Custom CSS.
				add_action( 'wp_head', array( $this, 'custom_css' ) );
			}
		}

		/**
		 * Filter Whether Let the Add to cart Link Button or not.
		 *
		 * @param string $link_html
		 * @param object $_product_obj
		 * @param array  $args
		 * @return string
		 */
		public function filter_loop_add_to_cart_button( $link_html, $_product_obj, $args ) {
			if ( $this->settings_obj->is_strict_mode() ) {
				if ( $this->handle_strict( $_product_obj->get_id() ) ) {
					return '';
				}
			} else {
				return '';
			}

			return $link_html;
		}

		/**
		 * Filter Single Product Page Add to cart button.
		 *
		 * @return void
		 */
		public function filter_single_product_add_to_cart_button() {
			global $product;

			if ( $this->settings_obj->is_strict_mode() ) {
				if ( ! $this->handle_strict( $product->get_id() ) ) {
					return;
				}
			}

			remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		}

		/**
		 * Make the product unpurchasble.
		 *
		 * @param boolean     $is_purchasable
		 * @param \WC_Product $_product
		 * @return boolean
		 */
		public function make_product_unpurchasable( $is_purchasable, $_product ) {
			if ( $this->settings_obj->is_strict_mode() ) {
				if ( $this->handle_strict( $_product->get_id() ) ) {
					return false;
				}
			} else {
				return false;
			}
			return $is_purchasable;
		}

		/**
		 * Maintenance Mode Notice Function
		 *
		 * @return string
		 */
		public function handle_notice() {
			if ( is_admin() ) {
				return;
			}

			$notice_settings = $this->settings_obj->get_notices_settings();

			if ( ! $this->is_preview && 'yes' !== $notice_settings['notice_status'] ) {
				return;
			}

			$notice_content = '';

			if ( 'general' === $notice_settings['notice_type'] ) {
				$notice_content = $notice_settings['general_notice'];
			}

			if ( empty( $notice_content ) ) {
				return;
			}

			return $this->message_html( $notice_content, true );
		}

		/**
		 * Handle Strict Maintenance Mode Condition.
		 *
		 * @param int $post_id
		 * @return boolean
		 */
		public function handle_strict( $post_id ) {
			if ( $this->settings_obj->is_strict_mode() ) {

				$strict_settings = $this->settings_obj->get_strict_mode_settings();

				// By Post Types.
				if ( ! empty( $strict_settings['strict_mode_product_type'] ) ) {
					$_product = wc_get_product( $post_id );
					if ( ! is_wp_error( $_product ) && is_object( $_product ) ) {
						if ( in_array( $_product->get_type(), $strict_settings['strict_mode_product_type'] ) ) {
							return true;
						}
					}
				}

				// By Specific Posts.
				if ( ! empty( $strict_settings['strict_mode_selected'] ) ) {
					if ( in_array( $post_id, $strict_settings['strict_mode_selected'] ) ) {
						return true;
					}
				}

				// By Categories.
				if ( ! empty( $strict_settings['strict_mode_cat'] ) ) {
					if ( has_term( $strict_settings['strict_mode_cat'], 'product_cat', $post_id ) ) {
						return true;
					}
				}

				// By Tags.
				if ( ! empty( $strict_settings['strict_mode_tag'] ) ) {
					if ( has_term( $strict_settings['strict_mode_tag'], 'product_tag', $post_id ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Handle Strict Mode for Cart.
		 *
		 * @param array $cart_items
		 *
		 * @return void
		 */
		public function handle_strict_for_cart( $cart_items ) {
			global $woocommerce;

			if ( $this->settings_obj->is_strict_mode() ) {

				$strict_settings = $this->settings_obj->get_strict_mode_settings();

				// By Post Types.
				if ( ! empty( $strict_settings['strict_mode_product_type'] ) ) {
					foreach ( $cart_items as $cart_item_key => $product_id ) {
						$_product = wc_get_product( $product_id );
						if ( ! is_wp_error( $_product ) && is_object( $_product ) && in_array( $_product->get_type(), $strict_settings['strict_mode_product_type'] ) ) {
							$woocommerce->cart->remove_cart_item( $cart_item_key );
							unset( $cart_items[ $cart_item_key ] );
						}
					}
				}

				// By Specific Posts.
				if ( ! empty( $strict_settings['strict_mode_selected'] ) ) {
					foreach ( $cart_items as $cart_item_key => $product_id ) {
						if ( in_array( $product_id, $strict_settings['strict_mode_selected'] ) ) {
							$woocommerce->cart->remove_cart_item( $cart_item_key );
							unset( $cart_items[ $cart_item_key ] );
						}
					}
				}

				// By Categories.
				if ( ! empty( $strict_settings['strict_mode_cat'] ) ) {
					foreach ( $cart_items as $cart_item_key => $product_id ) {
						if ( has_term( $strict_settings['strict_mode_cat'], 'product_cat', $product_id ) ) {
							$woocommerce->cart->remove_cart_item( $cart_item_key );
							unset( $cart_items[ $cart_item_key ] );
						}
					}
				}

				// By Tags.
				if ( ! empty( $strict_settings['strict_mode_tag'] ) ) {
					foreach ( $cart_items as $cart_item_key => $product_id ) {
						if ( has_term( $strict_settings['strict_mode_tag'], 'product_tag', $product_id ) ) {
							$woocommerce->cart->remove_cart_item( $cart_item_key );
							unset( $cart_items[ $cart_item_key ] );
						}
					}
				}
			}
		}

		/**
		 * Always return for product Quantity.
		 *
		 * @param int $quantity
		 * @param int $product_id
		 *
		 * @return int
		 */
		public function disable_add_to_cart( $quantity, $product_id ) {
			if ( $this->settings_obj->is_strict_mode() ) {
				if ( $this->handle_strict( $product_id ) ) {
					return 0;
				} else {
					return $quantity;
				}
			}

			return 0;
		}

		/**
		 * Maintenance Notice HTML.
		 *
		 * @param boolean $is_echo Wether to echo the message
		 *
		 * @return void|string
		 */
		public function message_html( $message, $is_echo = true ) {
			if ( empty( $message ) ) {
				return '';
			}

			if ( false === $is_echo ) :
				ob_start();
				$result = '';
			endif;
			?>

			<div class="<?php echo esc_attr( self::$plugin_info['name'] ); ?>-notice-wrapper" >
				<?php echo apply_filters( 'the_content', $message ); ?>
			</div>

			<?php
			if ( false === $is_echo ) :
				$result = ob_get_clean();
				return $result;
			endif;
		}

		/**
		 * Custom Css
		 *
		 * @return void
		 */
		public function custom_css() {
			$custom_css = $this->settings_obj->get_custom_css();
			if ( empty( $custom_css ) ) {
				return '';
			}
			?>
			<style>
				<?php echo wp_strip_all_tags( $custom_css ); ?>
			</style>
			<?php
		}

		/**
		 * Redirect Cart and Checkout Pages to other Page.
		 *
		 * @return void
		 */
		public function handle_redirects() {
			$redirect_settings = $this->settings_obj->get_redirect_settings();
			if ( 'yes' !== $redirect_settings['redirect_status'] ) {
				return;
			}

			if ( is_admin() ) {
				return;
			}

			// Check Maintenance Mode.
			if ( ( ! $this->is_preview ) && ( 'yes' !== $redirect_settings['redirect_status'] ) ) {
				return;
			}

			// Handle Redirect.
			if ( 'general' === $redirect_settings['redirect_type'] && ! empty( $redirect_settings['general_redirect'] ) ) {
				if ( is_woocommerce() || is_cart() || is_checkout() ) {
					wp_redirect( esc_url( $redirect_settings['general_redirect'] ) );
					die();
				}
			} elseif ( 'custom' === $redirect_settings['redirect_type'] ) {
				$redirects = $redirect_settings['redirects'];

				// Is Page.
				if ( is_page() || is_shop() ) {
					foreach ( $redirects as $redirect_option ) {
						if ( 'page' === $redirect_option['type'] ) {
							if ( is_shop() && in_array( wc_get_page_id( 'shop' ), $redirect_option['target'] ) && ! empty( $redirect_option['link'] ) ) {
								wp_redirect( esc_url( $redirect_option['link'] ) );
								die();
							} elseif ( in_array( get_queried_object_id(), $redirect_option['target'] ) && ! empty( $redirect_option['link'] ) ) {
								wp_redirect( esc_url( $redirect_option['link'] ) );
								die();
							}
						}
					}
				} elseif ( is_single() ) {
					foreach ( $redirects as $redirect_option ) {
						if ( 'post' === $redirect_option['type'] && in_array( get_queried_object_id(), $redirect_option['target'] ) && ! empty( $redirect_option['link'] ) ) {
							wp_redirect( esc_url( $redirect_option['link'] ) );
							die();
						}
					}

					foreach ( $redirects as $redirect_option ) {
						if ( 'cat' === $redirect_option['type'] && has_term( $redirect_option['target'], 'product_cat', get_queried_object_id() ) && ! empty( $redirect_option['link'] ) ) {
							wp_redirect( esc_url( $redirect_option['link'] ) );
							die();
						}

						if ( 'tag' === $redirect_option['type'] && has_term( $redirect_option['target'], 'product_tag', get_queried_object_id() ) && ! empty( $redirect_option['link'] ) ) {
							wp_redirect( esc_url( $redirect_option['link'] ) );
							die();
						}
					}
				} elseif ( is_tax( 'product_cat' ) ) {
					foreach ( $redirects as $redirect_option ) {
						if ( 'cat' === $redirect_option['type'] && in_array( get_queried_object_id(), $redirect_option['target'] ) && ! empty( $redirect_option['link'] ) ) {
							wp_redirect( esc_url( $redirect_option['link'] ) );
							die();
						}
					}
				} elseif ( is_tax( 'product_tag' ) ) {
					foreach ( $redirects as $redirect_option ) {
						if ( 'tag' === $redirect_option['type'] && in_array( get_queried_object_id(), $redirect_option['target'] ) && ! empty( $redirect_option['link'] ) ) {
							wp_redirect( esc_url( $redirect_option['link'] ) );
							die();
						}
					}
				}
			}

		}

		/**
		 * Empty User Cart.
		 *
		 * @return void
		 */
		public function empty_user_cart_for_maintenance() {
			global $woocommerce;
			if ( isset( $woocommerce ) && isset( $woocommerce->cart ) ) {
				if ( ! $woocommerce->cart->is_empty() ) {
					if ( ! $this->settings_obj->is_strict_mode() ) {
						$woocommerce->cart->empty_cart();
					} else {
						$cart_items = wp_list_pluck( $woocommerce->cart->get_cart_contents(), 'product_id' );
						$this->handle_strict_for_cart( $cart_items );
					}
				}
			}
		}
	}

	add_action( 'plugins_loaded', array( 'GPLS_WSM_Class', 'init' ), 1 );
	register_activation_hook( __FILE__, array( 'GPLS_WSM_Class', 'plugin_activated' ) );
	register_deactivation_hook( __FILE__, array( 'GPLS_WSM_Class', 'plugin_deactivated' ) );
endif;
