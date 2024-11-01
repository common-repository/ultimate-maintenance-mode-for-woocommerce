<?php
namespace GPLSCore\GPLS_WSM;

use GPLSCore\GPLS_WSM\Modules\Services\Helpers;
use GPLSCore\GPLS_WSM\Modules\Services\Pro_Tab;


defined( 'ABSPATH' ) || exit();

/**
 * Core Class
 */
class Core {

	use Helpers;

	/**
	 * Plugins Main Options Key.
	 *
	 * @var string
	 */
	public $plugins_main_options = 'gpls_core_plugins_main_options';

	/**
	 * Plugin Info
	 *
	 * @var array
	 */
	protected $plugin_info;

	/**
	 * Core Path
	 *
	 * @var string
	 */
	public $core_path;

	/**
	 * Core URL
	 *
	 * @var string
	 */
	public $core_url;

	/**
	 * Core Assets PATH
	 *
	 * @var string
	 */
	public $core_assets_path;

	/**
	 * Core Assets URL
	 *
	 * @var string
	 */
	public $core_assets_url;

	/**
	 * Constructor.
	 *
	 * @param array $plugin_info
	 */
	public function __construct( $plugin_info ) {
		$this->init( $plugin_info );
		$this->hooks();
		$this->init_modules();
	}

	/**
	 * Init constants and other variables.
	 *
	 * = Set the Plugin Update URL
	 *
	 * @return void
	 */
	public function init( $plugin_info ) {
		$this->plugin_info      = $plugin_info;
		$this->core_path        = plugin_dir_path( __FILE__ );
		$this->core_url         = plugin_dir_url( __FILE__ );
		$this->core_assets_path = $this->core_path . 'assets';
		$this->core_assets_url  = $this->core_url . 'assets';
	}

	/**
	 * Initialize Module Classes
	 *
	 * @return void
	 */
	public function init_modules() {
		Pro_Tab::init( $this->plugin_info, $this );
	}

	/**
	 * Core Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 100, 1 );
	}

	/**
	 * Core Admin Scripts.
	 *
	 * @param string $hook_prefix
	 * @return void
	 */
	public function admin_scripts( $hook_suffix ) {
		if ( ! empty( $_GET['page'] ) && $this->plugin_info['options_page'] === sanitize_text_field( $_GET['page'] ) ) {
			wp_enqueue_style( $this->plugin_info['name'] . '-admin-main-styles', $this->core_assets_file( 'style', 'css', 'css' ), array(), 'all' );
			wp_enqueue_script( $this->plugin_info['name'] . '-admin-main-scripts', $this->core_assets_file( 'scripts', 'js', 'js' ), array(), '1.0.0', true );
		}
	}

	/**
	 * Pro Tab Link
	 *
	 * @param string $main_page
	 * @param boolean $echo
	 *
	 * @return string
	 */
	public function pro_tab( $main_page, $echo = false ) {
		return Pro_Tab::init( $this->plugin_info, $this )->pro_tab( $main_page, $echo );
	}

	/**
	 * Get Core assets file
	 *
	 * @param string $asset_file    Assets File Name
	 * @param string $type          Assets File Folder Type [ js / css /images / etc.. ]
	 * @param string $suffix        Assets File Type [ js / css / png /jpg / etc ... ]
	 * @param string $prefix        [ .min ]
	 * @return string
	 */
	public function core_assets_file( $asset_file, $type, $suffix, $prefix = 'min' ) {
		return $this->core_assets_url . '/dist/' . $type . '/' . $asset_file . ( ! empty( $prefix ) ? ( '.' . $prefix ) : '' ) . '.' . $suffix;
	}


	/**
	 * Get Core assets lib file
	 *
	 * @param string $asset_file    Assets File Name
	 * @param string $suffix        Assets File Type [ js / css / png /jpg / etc ... ]
	 * @param string $prefix        [ .min ]
	 * @return string
	 */
	public function core_assets_lib( $asset_file, $suffix, $prefix = 'min' ) {
		return $this->core_assets_url . '/libs/' . $asset_file . ( ! empty( $prefix ) ? ( '.' . $prefix ) : '' ) . '.' . $suffix;
	}

	/**
	 * Plugin Activation Hub function
	 *
	 * @return void
	 */
	public function plugin_activated() {
		// set the main options value.
		$main_options = get_option( $this->plugins_main_options );
		if ( ! $main_options ) {
			$main_options                         = array();
			$main_options['installed_plugins']    = array();
			$main_options['plugins_update_check'] = array(
				'timestamp' => microtime( true ),
			);
		}
		$main_options['installed_plugins'][ $this->plugin_info['name'] ] = array(
			'public_name' => $this->plugin_info['public_name'],
			'id'          => $this->plugin_info['id'],
			'name'        => $this->plugin_info['name'],
			'type'        => $this->plugin_info['type'],
			'status'      => 'active',
		);
		update_option( $this->plugins_main_options, $main_options, false );
	}

	/**
	 * Plugin Deactivation Hub function
	 *
	 * @return void
	 */
	public function plugin_deactivated() {
		$main_options = get_option( $this->plugins_main_options );
		$main_options['installed_plugins'][ $this->plugin_info['name'] ]['status'] = 'inactive';
		update_option( $this->plugins_main_options, $main_options, false );
	}

	/**
	 * Uninstall the plugin hook.
	 *
	 * @return void
	 */
	public function plugin_uninstalled() {
		if ( ! is_plugin_active( $this->plugin_info['text_domain'] . '-pro/' . $this->plugin_info['name'] . '-pro.php' ) ) {
			$main_options = get_option( $this->plugins_main_options );
			unset( $main_options['installed_plugins'][ $this->plugin_info['name'] ] );
			if ( empty( $main_options['installed_plugins'] ) ) {
				delete_option( $this->plugins_main_options );
			} else {
				update_option( $this->plugins_main_options, $main_options, false );
			}
		}
	}

	/**
	 * Default Footer Section
	 *
	 * @return void
	 */
	public function default_footer_section() {
		?>
		<style>
		#wpfooter {display: block !important;}
		.wrap.woocommerce {position: relative;}
		.gpls-contact {position: absolute; bottom: 0px; right: 20px; max-width: 350px; z-index: 1000;}
		.gpls-contact .link { color: #acde86!important; }
		.gpls-contact .text { background-color: #176875!important; }
		</style>
		<div class="gpls-contact">
		  <p class="p-3 bg-light text-center text text-white">in case you want to report a bug, submit a new feature or request a custom plugin, Please <a class="link" target="_blank" href="https://grandplugins.com/contact-us"> contact us </a></p>
		</div>
		<?php
	}

	/**
	 * Pro Button.
	 *
	 * @param string $pro_link
	 * @param string $btn_title
	 * @param string $additional_classes
	 * @param string $additional_css
	 * @return void
	 */
	public function pro_btn( $pro_link = '', $btn_title = 'Pro', $additional_classes = '', $additional_css = '', $return = false ) {

		if ( empty( $pro_link ) && empty( $this->plugin_info['pro_link'] ) ) {
			return;
		}
		$pro_link = empty( $pro_link ) ? $this->plugin_info['pro_link'] : $pro_link;

		if ( $return ) {
			ob_start();
		}
		?>
		<a target="_blank" class="ms-2 btn gpls-permium-btn-wave btn-primary <?php echo esc_attr( $additional_classes ); ?>" href="<?php echo esc_url_raw( $pro_link ); ?>" style="<?php echo esc_attr( $additional_css ); ?>">
			<span class="pro-title" style="position:relative;z-index:10;color:#FFF;"><?php printf( esc_html__( '%s' ), $btn_title ); ?></span>
			<span class="wave"></span>
		</a>
		<?php
		if ( $return ) {
			return ob_get_clean();
		}
	}


	/**
	 * Review Link.
	 *
	 * @param string $review_link
	 * @return void
	 */
	public function review_notice( $review_link = '', $is_dismissible = true ) {
		if ( empty( $review_link ) && empty( $this->plugin_info['review_link'] ) ) {
			return;
		}
		$review_link = ! empty( $review_link ) ? $review_link : $this->plugin_info['review_link'];
		?>
		<p class="notice notice-success p-4 <?php echo esc_attr( $is_dismissible ? 'is-dismissible' : '' ); ?>">
			<?php esc_html_e( 'We would love your feedback. leaving ' ); ?>
			<a class="text-decoration-none" href="<?php echo esc_url_raw( $review_link ); ?>" target="_blank">
				<u><?php esc_html_e( 'a review is much appreciated' ); ?></u>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
				<span class="dashicons dashicons-star-filled"></span>
			</a>
			<?php esc_html_e( ':) Thanks!' ); ?>
		</p>
		<?php
	}
}
