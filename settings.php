<?php
namespace GPLSCore\GPLS_WSM_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings Class
 */
class GPLS_WSM_Settings {

	/**
	 * Plugin Info
	 *
	 * @var [type]
	 */
	public $plugin_info;

	/**
	 * Settings Tab Key
	 *
	 * @var string
	 */
	protected $settings_tab_key;

	/**
	 * Settings Tab name
	 *
	 * @var array
	 */
	protected $settings_tab;


	/**
	 * Current Settings Active Tab.
	 *
	 * @var string
	 */
	protected $current_active_tab;


	/**
	 * Settings Array.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Settings Tab Fields
	 *
	 * @var Array
	 */
	protected $fields = array();

	/**
	 * Core Object
	 *
	 * @var object
	 */
	protected $core;

	/**
	 * Class Constructor.
	 */
	public function __construct( $core, $plugin_info ) {
		$this->core               = $core;
		$this->plugin_info        = $plugin_info;
		$this->settings_tab_key   = $this->plugin_info['options_page'];
		$this->settings_tab       = array( $this->settings_tab_key => __( 'Maintenance Mode', 'woocommrece' ) );
		$this->current_active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'general';
		$this->register_hooks();
	}

	/**
	 * Register Settings Hooks.
	 *
	 * @return void
	 */
	public function register_hooks() {
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 100, 1 );

		foreach ( array_keys( $this->settings_tab ) as $name ) {
			add_action( 'woocommerce_settings_' . $name, array( $this, 'settings_tab_action' ), 10 );
			add_action( 'woocommerce_update_options_' . $name, array( $this, 'save_settings' ), 10 );
		}

		add_filter( 'admin_enqueue_scripts', array( $this, 'add_settings_assets' ), 1000 );
		add_action( 'woocommerce_sections_' . $this->settings_tab_key, array( $this->core, 'default_footer_section' ) );
		add_action( 'woocommerce_sections_' . $this->settings_tab_key, array( $this, 'settings_tabs' ), 100 );
		add_filter( 'admin_footer_text', '__return_false', PHP_INT_MAX );

		add_action( 'wp_ajax_' . $this->plugin_info['name'] . '-custom-redirect-link-select', array( $this, 'ajax_custom_redirect_link_select' ) );

		add_action( 'woocommerce_admin_field_horizontal_line', array( $this, 'settings_horizontal_line' ) );
		add_action( 'woocommerce_admin_field_' . $this->plugin_info['name'] . '-pro-title-message', array( $this, 'pro_settings_notice' ), 100 );

		add_action( 'woocommerce_admin_field_' . $this->plugin_info['name'] . '-strict-mode-by-cat', array( $this, 'strict_mode_categories_select' ), 100, 1 );
		add_action( 'woocommerce_admin_field_' . $this->plugin_info['name'] . '-custom-redirect', array( $this, 'redirect_tab_settings' ), 100, 1 );
		add_action( 'woocommerce_admin_field_' . $this->plugin_info['name'] . '-notice-title-message', array( $this, 'notice_tab_settings' ), 100, 1 );
		add_action( 'woocommerce_admin_field_' . $this->plugin_info['name'] . '-popup-title-message', array( $this, 'popup_tab_settings' ), 100, 1 );

		add_action( 'woocommerce_update_options_' . $this->plugin_info['options_page'], array( $this, 'save_tabs_custom_settings' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . $this->plugin_info['name'] . '-maintenance-mode-by-categories', array( $this, 'save_strict_mode_categories_select' ), 100, 3 );

		add_action( 'plugin_action_links_' . $this->plugin_info['basename'], array( $this, 'settings_link' ), 5, 1 );
	}

	/**
	 * Settings Link.
	 *
	 * @param array $links Plugin Row Links.
	 * @return array
	 */
	public function settings_link( $links ) {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->plugin_info['options_page'] ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
		$links[] = '<a target="_blank" href="' . esc_url( $this->plugin_info['pro_link'] ) . '"><b style="color:#31cf1e;font-weight:bolder;">' . esc_html__( 'Premium' ) . '</b></a>';
		return $links;
	}

	/**
	 * Pro Settings Notice.
	 *
	 * @return void
	 */
	public function pro_settings_notice() {
		?>
		<h3 class="p-3 bg-light text-center my-3"><?php esc_html_e( 'This feature is part of Pro Version', 'ultimate-maintenance-mode-for-woocommerce' ); ?> <?php $this->core->pro_btn(); ?></h3>
		<?php
	}

	/**
	 * Settings Assets
	 *
	 * @return void
	 */
	public function add_settings_assets() {
		if ( ! empty( $_GET['tab'] ) && in_array( wp_unslash( $_GET['tab'] ), array_keys( $this->settings_tab ) ) ) {
			wp_enqueue_style( $this->plugin_info['name'] . '-settings-bootstrap', $this->core->core_assets_lib( 'bootstrap', 'css' ), array(), 'all' );
			wp_enqueue_media();
			wp_enqueue_editor();

			if ( ! wp_style_is( 'select2' ) ) {
				wp_enqueue_style( 'select2' );
			}

			if ( ! wp_script_is( 'jquery', 'enqueued' ) ) {
				wp_enqueue_script( 'jquery' );
			}

			wp_enqueue_code_editor(
				array(
					'type' => 'text/css',
				)
			);

			wp_enqueue_script( 'wp-theme-plugin-editor' );
			wp_enqueue_style( 'wp-codemirror' );

			wp_enqueue_script( $this->plugin_info['name'] . '-core-admin-bootstrap-js', $this->core->core_assets_lib( 'bootstrap.bundle', 'js' ), array( 'jquery' ), $this->plugin_info['version'], true );
			wp_enqueue_script( $this->plugin_info['name'] . '-settings-script', $this->plugin_info['url'] . 'assets/dist/js/admin/settings-actions.min.js', array( 'jquery', 'wp-i18n' ), $this->plugin_info['version'], true );

			wp_localize_script(
				$this->plugin_info['name'] . '-settings-script',
				$this->plugin_info['localize_var'],
				array(
					'ajaxUrl'                     => admin_url( 'admin-ajax.php' ),
					'nonce'                       => wp_create_nonce( $this->plugin_info['name'] . '_nonce' ),
					'prefix'                      => $this->plugin_info['name'],
					'custom_redirect_link_action' => $this->plugin_info['name'] . '-custom-redirect-link-select',
				)
			);
		}
	}

	/**
	 * General Settings Page.
	 *
	 * @param array $field_props
	 *
	 * @return void
	 */
	public function strict_mode_categories_select( $field_props ) {
		$selected_cats    = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-categories', array() );
		$include_children = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-categories-include-children', false );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo $this->plugin_info['name'] . '-maintenance-mode-by-categories'; ?>"><?php echo __( 'Categories', 'ultimate-maintenance-mode-for-woocommerce' ); ?> <?php echo wc_help_tip( __( 'The maintenance mode will be applied on products that has selected categories', 'ultimate-maintenance-mode-for-woocommerce' ) ); ?></label>
			</th>
			<td class="forminp forminp-multiselect" >
				<select name="<?php echo $this->plugin_info['name'] . '-maintenance-mode-by-categories[]'; ?>" id="<?php echo $this->plugin_info['name'] . '-maintenance-mode-by-categories'; ?>" multiple="multiple">
					<?php foreach ( $this->get_products_categories() as $_cat_id => $_cat_name ) : ?>
					<option value="<?php echo esc_attr( $_cat_id ); ?>" <?php echo wc_selected( $_cat_id, $selected_cats ); ?> ><?php echo esc_attr( $_cat_name ); ?></option>
					<?php endforeach; ?>
				</select>
				<fieldset>
					<label for="">
						<input type="checkbox" name="<?php echo $this->plugin_info['name'] . '-maintenance-mode-by-categories-include-children'; ?>" <?php checked( $include_children, true, true ); ?> >
						<?php _e( 'Include Children Categories?', 'ultimate-maintenance-mode-for-woocommerce' ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save Strict Mode By Categories Select.
	 *
	 * @param array $option
	 *
	 * @return void
	 */
	public function save_strict_mode_categories_select( $value, $option, $raw_value ) {
		$result           = array();
		$_cats            = $this->get_products_categories();
		$include_children = false;
		// Filter selected tags.
		if ( ! empty( $value ) ) {
			$result = array_intersect( array_keys( $_cats ), array_map( 'intval', (array) $value ) );
		}

		// Include children categories
		if ( ! empty( $_POST[ $this->plugin_info['name'] . '-maintenance-mode-by-categories-include-children' ] ) ) {
			$include_children = true;
			$cats_children    = array();

			foreach ( $result as $_cat_id ) {
				$cat_children  = get_term_children( $_cat_id, 'product_cat' );
				$cats_children = array_unique( array_merge( $cats_children, $cat_children ) );
			}

			$result = array_unique( array_merge( $cats_children, array_values( $result ) ) );
		}

		update_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-categories', $result, $option['autoload'] );
		update_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-categories-include-children', $include_children, $option['autoload'] );

		return null;
	}

	/**
	 * Redirect Settings Page.
	 *
	 * @param array $field_props
	 *
	 * @return void
	 */
	public function redirect_tab_settings( $field_props ) {
		$tab                   = $field_props['tab'];
		$redirects_mapping     = (array) json_decode( get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-mapping', '' ), true );
		$redirect_type         = get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-type-radio', 'general' );
		$redirect_general_link = get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-general', '' );
		?>
		<div id="<?php echo $this->plugin_info['name']; ?>-redirect-accordion" class="redirect-accordion mt-5 container-fluid mb-4">
			<!-- General Redirect -->
			<input type="hidden" name="<?php echo $this->plugin_info['name'] . '-settings-tab-name'; ?>" value="<?php echo esc_attr( $field_props['tab'] ); ?>" />
			<div class="w-100">
				<div class="card-header overflow-hidden">
					<input class="form-check-input" type="radio" name="<?php echo $this->plugin_info['name']; ?>-custom-redirect-type-radio" data-toggle="collapse" data-target="#<?php echo $this->plugin_info['name']; ?>-redirect-all-wrapper" aria-expanded="true" aria-controls="<?php $this->plugin_info['name']; ?>-redirect-all-wrapper" value="general" <?php checked( $redirect_type, 'general', true ); ?> >
					<span class="desc ml-4 font-weight-bold">
						<h5><?php _e( 'General Redirect', '' ); ?></h5>
						<h6 class="dscription ml-3"><?php _e( 'Redirect All WooCommerce Pages, Archives and Products to a single redirect link', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h6>
					</span>
				</div>
				<div id="<?php echo $this->plugin_info['name']; ?>-redirect-all-wrapper" class="<?php echo ( 'general' === $redirect_type ? 'show' : '' ); ?> collapse overflow-hidden my-3 py-2" data-parent="#<?php echo $this->plugin_info['name']; ?>-redirect-accordion">
					<div class="container">
						<div class="col">
							<input type="url" class="w-100 form-input" name="<?php echo $this->plugin_info['name']; ?>-custom-redirect-general" placeholder="<?php _e( 'redirect Link...', '' ); ?>" value="<?php echo esc_url( $redirect_general_link ); ?>">
						</div>
					</div>
				</div>
			</div>
			<!-- Custom Redirects -->
			<div class="w-100">
				<div class="card-header overflow-hidden">
					<input class="form-check-input" type="radio" name="<?php echo $this->plugin_info['name']; ?>-custom-redirect-type-radio" data-toggle="collapse" data-target="#<?php echo $this->plugin_info['name']; ?>-redirect-custom-wrapper" aria-expanded="true" aria-controls="<?php echo $this->plugin_info['name']; ?>-redirect-custom-wrapper" value="custom" <?php checked( $redirect_type, 'custom', true ); ?> >
					<span class="desc ml-4 font-weight-bold">
						<h5><?php _e( 'Custom Redirect', '' ); ?></h5>
						<h6 class="dscription ml-3"><?php _e( 'use custom redirects by categories/tags, single posts or pages', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h6>
					</span>
				</div>
				<div id="<?php echo $this->plugin_info['name']; ?>-redirect-custom-wrapper" class="collapse <?php echo ( 'custom' === $redirect_type ? 'show' : '' ); ?>" data-parent="#<?php echo $this->plugin_info['name']; ?>-redirect-accordion" >
					<div class="container-fluid">
						<div class="custom-redirect-btns mt-3 mb-4">
							<button class="w-auto mr-3 btn-sm btn btn-primary redirect-by-cat settings-custom-btn" data-tab="redirect" data-label="Categories" data-type="cat"><?php _e( 'Select By Categories', '' ); ?></button>
							<button class="w-auto mr-3 btn-sm btn btn-primary redirect-by-tag settings-custom-btn" data-tab="redirect" data-label="Tags" data-type="tag"><?php _e( 'Select By Tags', '' ); ?></button>
							<button class="w-auto mr-3 btn-sm btn btn-primary redirect-by-post settings-custom-btn" data-tab="redirect" data-label="Products" data-type="post"><?php _e( 'Select products', '' ); ?></button>
							<button class="w-auto mr-3 btn-sm btn btn-primary redirect-by-page settings-custom-btn" data-tab="redirect" data-label="Pages" data-type="page"><?php _e( 'Select pages', '' ); ?></button>
						</div>
						<div class="custom-main-wrapper mt-5">
							<?php
							if ( ! empty( $redirects_mapping ) ) :
								$redirects_mapping = $this->prepare_custom_settings( $redirects_mapping, 'redirects' );
								foreach ( $redirects_mapping as $index => $redirect_row ) :
									?>
								<div id="custom-settings-wrapper-<?php echo esc_attr( $index ); ?>" class="custom-settings-wrapper d-flex align-items-stretch mb-3 d-block" data-index="<?php echo esc_attr( $index ); ?>">
									<input type="hidden" name="<?php echo $this->plugin_info['name']; ?>-custom-type[<?php echo esc_attr( $index ); ?>]" value="<?php echo esc_attr( $redirect_row['type'] ); ?>"/>
									<span class="text-weight-bold align-self-center mr-2" style="min-width:150px;"><?php echo esc_attr( $redirect_row['label'] ); ?></span>
									<select id="custom-redirect-select-<?php echo esc_attr( $index ); ?>" name="<?php echo $this->plugin_info['name']; ?>-custom-redirect-select[<?php echo esc_attr( $index ); ?>][]" class="mx-3 custom-redirect-link-select custom-redirect-link-select-new" data-tab="redirect" data-type="<?php echo esc_attr( $redirect_row['type'] ); ?>" data-label="<?php echo esc_attr( $redirect_row['label'] ); ?>" multiple required >
										<?php foreach ( $redirect_row['target'] as $redirect_id => $redirect_title ) : ?>
										<option value="<?php echo esc_html( $redirect_id ); ?>" selected><?php echo esc_html( $redirect_title ); ?></option>
										<?php endforeach; ?>
									</select>
									<input style="min-width: 600px;" type="url" name="<?php echo $this->plugin_info['name']; ?>-custom-redirect-update[<?php echo esc_attr( $index ); ?>]" class="custom-redirect-link mx-3" placeholder="Redirect Link..." value="<?php echo esc_url( $redirect_row['link'] ); ?>" required>
									<span style="cursor:pointer;" class="dashicons dashicons-dismiss custom-settings-row-remove custom-redirect-link-remove mx-2 align-self-center"></span>
								</div>
									<?php
								endforeach;
							endif;
							?>
							<!-- Custom Redirect Links Here -->
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Notice Custom Settings.
	 *
	 * @return void
	 */
	public function notice_tab_settings( $field_props ) {
		$tab                   = $field_props['tab'];
		$redirects_mapping     = (array) json_decode( get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-mapping', '' ), true );
		$redirect_type         = get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-type-radio', 'general' );
		$redirect_general_link = get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-general', '' );

		?>
		<div id="<?php echo $this->plugin_info['name']; ?>-redirect-accordion" class="redirect-accordion mt-5 container-fluid mb-4">
			<!-- General Notice -->
			<input type="hidden" name="<?php echo $this->plugin_info['name'] . '-settings-tab-name'; ?>" value="<?php echo esc_html( $field_props['tab'] ); ?>" />
			<div class="w-100">
				<div class="card-header overflow-hidden">
					<input class="form-check-input" type="radio" name="<?php echo $this->plugin_info['name']; ?>-custom-<?php echo esc_attr( $field_props['tab'] ); ?>-type-radio" data-toggle="collapse" data-target="#<?php echo $this->plugin_info['name']; ?>-redirect-all-wrapper" aria-expanded="true" aria-controls="<?php $this->plugin_info['name']; ?>-redirect-all-wrapper" value="general" <?php checked( $redirect_type, 'general', true ); ?> >
					<span class="desc ml-4 font-weight-bold">
						<h5><?php _e( 'General Notice', '' ); ?></h5>
						<h6 class="dscription ml-3"><?php _e( 'Show the notice on all WooCommerce Pages, Archives and Single products pages', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h6>
					</span>
				</div>
				<div id="<?php echo $this->plugin_info['name']; ?>-redirect-all-wrapper" class="<?php echo ( 'general' === $redirect_type ? 'show' : '' ); ?> collapse overflow-hidden my-3 py-2" data-parent="#<?php echo $this->plugin_info['name']; ?>-redirect-accordion">
					<div class="container">
						<div class="col">
							<textarea class="w-100 <?php echo $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general'; ?>" name="<?php echo $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general'; ?>" id="<?php echo $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general'; ?>" cols="30" rows="10"><?php echo apply_filters( 'the_editor_content', $redirect_general_link ); ?></textarea>
						</div>
					</div>
				</div>
			</div>
			<!-- Custom Notices -->
			<div class="w-100">
				<div class="card-header overflow-hidden bg-muted disabled" disabled>
					<input class="form-check-input disabled" type="radio" disabled name="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-notice-type-radio' ); ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-custom-wrapper' ); ?>" aria-expanded="true" aria-controls="<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-custom-wrapper' ); ?>" value="custom" <?php checked( $redirect_type, 'custom', true ); ?> >
					<span class="desc ml-4 font-weight-bold">
						<h5><?php esc_html_e( 'Custom Notices', '' ); ?> <?php $this->core->pro_btn(); ?></h5>
						<h6 class="dscription ml-3"><?php esc_html_e( 'use custom notices by categories/tags archives, single posts or pages', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h6>
					</span>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Popup Custom Settings.
	 *
	 * @return void
	 */
	public function popup_tab_settings( $field_props ) {
		$tab                   = $field_props['tab'];
		$redirects_mapping     = (array) json_decode( get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-mapping', '' ), true );
		$redirect_type         = get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-type-radio', 'general' );
		$redirect_general_link = (array) json_decode( get_option( $this->plugin_info['name'] . '-custom-' . $tab . '-general', '' ), true );
		?>
		<div id="<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-accordion' ); ?>" class="redirect-accordion mt-5 container-fluid">
			<!-- General Popup -->
			<div class="w-100">
				<div class="card-header overflow-hidden">
					<span class="desc ml-4 font-weight-bold">
						<h5><?php esc_html_e( 'General Popup', '' ); ?></h5>
						<h6 class="dscription ml-3"><?php esc_html_e( 'Show the Popup on all WooCommerce Pages, Archives and Single products pages', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h6>
					</span>
				</div>
				<div id="<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-all-wrapper' ); ?>" class="<?php echo esc_attr( 'general' === $redirect_type ? 'show' : '' ); ?> collapse overflow-hidden" data-bs-parent="#<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-accordion' ); ?>">
					<div class="row my-3 py-2">
						<div class="col">
							<h4 class="my-3"><?php esc_html_e( 'Popup Title', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h4>
							<textarea disabled class="w-100 disabled <?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general' ); ?>" name="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general[title]' ); ?>" id="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general-title' ); ?>" cols="30" rows="10"><?php echo wp_kses_post( ! empty( $redirect_general_link['title'] ) ? $redirect_general_link['title'] : '' ); ?></textarea>
						</div>
						<div class="col">
							<h4 class="my-3"><?php esc_html_e( 'Popup Body', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h4>
							<textarea disabled class="w-100 disabled <?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general' ); ?>" name="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general[body]' ); ?>" id="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general-body' ); ?>" cols="30" rows="10"><?php echo wp_kses_post( ! empty( $redirect_general_link['body'] ) ? $redirect_general_link['body'] : '' ); ?></textarea>
						</div>
						<div class="col">
							<h4 class="my-3"><?php esc_html_e( 'Popup Footer', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h4>
							<textarea disabled class="w-100 disabled <?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general' ); ?>" name="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general[footer]' ); ?>" id="<?php echo esc_attr( $this->plugin_info['name'] . '-custom-' . $field_props['tab'] . '-general-footer' ); ?>" cols="30" rows="10"><?php echo wp_kses_post( ! empty( $redirect_general_link['footer'] ) ? $redirect_general_link['footer'] : '' ); ?></textarea>
						</div>
					</div>
				</div>
			</div>

			<!-- Custom Popups -->
			<div class="w-100">
				<div class="card-header overflow-hidden">
					<span class="desc ml-4 font-weight-bold">
						<h5><?php esc_html_e( 'Custom Popups', '' ); ?></h5>
						<h6 class="dscription ml-3"><?php esc_html_e( 'Use custom popups by categories/tags archives, single posts or pages', 'ultimate-maintenance-mode-for-woocommerce' ); ?></h6>
					</span>
				</div>
				<div id="<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-custom-wrapper' ); ?>" class="collapse <?php echo esc_attr( 'custom' === $redirect_type ? 'show' : '' ); ?>" data-bs-parent="#<?php echo esc_attr( $this->plugin_info['name'] . '-redirect-accordion' ); ?>" >
					<div class="container-fluid">
						<div class="custom-redirect-btns mt-3 mb-4">
							<button class="w-auto mr-3 btn-sm btn btn-primary popup-by-cat settings-custom-btn" data-tab="popup" data-label="Categories" data-type="cat"><?php esc_html_e( 'Select By Categories', '' ); ?></button>
							<button class="w-auto mr-3 btn-sm btn btn-primary popup-by-tag settings-custom-btn" data-tab="popup" data-label="Tags" data-type="tag"><?php esc_html_e( 'Select By Tags', '' ); ?></button>
							<button class="w-auto mr-3 btn-sm btn btn-primary popup-by-post settings-custom-btn" data-tab="popup" data-label="Products" data-type="post"><?php esc_html_e( 'Select products', '' ); ?></button>
							<button class="w-auto mr-3 btn-sm btn btn-primary popup-by-page settings-custom-btn" data-tab="popup" data-label="Pages" data-type="page"><?php esc_html_e( 'Select pages', '' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Prepare Custom Redirect Mappings for Listing in Settings.
	 *
	 * @param array $redirects_mapping
	 * @return array
	 */
	public function prepare_custom_settings( $redirects_mapping, $type ) {
		foreach ( $redirects_mapping as &$redirect_mapping ) {
			if ( 'cat' === $redirect_mapping['type'] || 'tag' === $redirect_mapping['type'] ) {
				$search_type                = ( 'cat' === $redirect_mapping['type'] ? 'product_cat' : 'product_tag' );
				$label                      = ( 'cat' === $redirect_mapping['type'] ? 'Categories' : 'Tags' );
				$redirect_mapping['target'] = get_terms(
					array(
						'include'          => $redirect_mapping['target'],
						'taxonomy'         => $search_type,
						'fields'           => 'id=>name',
						'hide_empty'       => false,
						'suppress_filters' => true,
					)
				);
			} elseif ( 'post' === $redirect_mapping['type'] ) {
				$label                   = 'Products';
				$redirected_posts        = wc_get_products(
					array(
						'include' => (array) $redirect_mapping['target'],
					)
				);
				$redirected_posts_titles = array();
				foreach ( $redirected_posts as $redirected_post ) {
					$redirected_posts_titles[ $redirected_post->get_id() ] = $redirected_post->get_title();
				}
				$redirect_mapping['target'] = $redirected_posts_titles;

			} elseif ( 'page' === $redirect_mapping['type'] ) {
				$label                  = 'Pages';
				$redirects_pages_mapped = array();
				$redirected_pages       = get_pages(
					array(
						'include' => (array) $redirect_mapping['target'],
						'number'  => count( (array) $redirect_mapping['target'] ),
					)
				);

				foreach ( $redirected_pages as $redirected_page ) {
					$redirects_pages_mapped[ $redirected_page->ID ] = $redirected_page->post_title;
				}

				$redirect_mapping['target'] = $redirects_pages_mapped;

			}
			$redirect_mapping['label'] = $label;
		}

		return $redirects_mapping;
	}

	/**
	 * Custom Redirect Link Select Ajax Handle.
	 *
	 * @return void
	 */
	public function ajax_custom_redirect_link_select() {
		global $wpdb;

		if ( ! empty( $_POST['term'] ) && ! empty( $_POST['tab'] ) && ! empty( $_POST['type'] ) && ! empty( $_POST['security'] ) && wp_verify_nonce( $_POST['security'], $this->plugin_info['name'] . '_nonce' ) ) {

			$results     = array();
			$type        = sanitize_text_field( wp_unslash( $_POST['type'] ) );
			$term        = sanitize_text_field( wp_unslash( $_POST['term'] ) );
			$tab         = sanitize_text_field( wp_unslash( $_POST['tab'] ) );
			$search_type = '';

			if ( 'cat' === $type || 'tag' === $type ) {
				if ( 'cat' === $type ) {
					$search_type = 'product_cat';
				} elseif ( 'tag' === $type ) {
					$search_type = 'product_tag';
				}

				$results = get_terms(
					array(
						'taxonomy'   => $search_type,
						'name__like' => $term,
						'fields'     => 'id=>name',
						'hide_empty' => false,
					)
				);
			} elseif ( 'post' === $type ) {
				$data_store      = \WC_Data_Store::load( 'product' );
				$ids             = $data_store->search_products( $term, '', false, false, absint( apply_filters( 'woocommerce_json_search_limit', 30 ) ), array(), array() );
				$product_objects = array_filter( array_map( 'wc_get_product', $ids ), 'wc_products_array_filter_readable' );
				$products        = array();

				foreach ( $product_objects as $product_object ) {
					$formatted_name = $product_object->get_formatted_name();
					$products[]     = array(
						'id'    => $product_object->get_id(),
						'title' => rawurldecode( $formatted_name ),
						'url'   => get_permalink( $product_object->get_id() ),
					);
				}

				$results = $products;
			} elseif ( 'page' === $type ) {
				$pages_mapped = array();
				$pages        = $wpdb->get_results(
					"SELECT
						ID, post_title
					FROM
						$wpdb->posts
					WHERE
						post_type = 'page'
					AND
						post_title LIKE '%{$wpdb->esc_like( $term )}%'
					",
					ARRAY_A
				);

				if ( ! empty( $pages ) ) {
					foreach ( $pages as $_page ) {
						$pages_mapped[] = array(
							'id'    => $_page['ID'],
							'title' => $_page['post_title'],
							'url'   => get_permalink( $_page['ID'] ),
						);
					}
					$results = $pages_mapped;
				}
			}

			wp_send_json( $results );
		}
	}

	/**
	 * Save Tabs Custom Settings.
	 */
	public function save_tabs_custom_settings() {

		if ( ! empty( $_POST[ $this->plugin_info['name'] . '-settings-tab-name' ] ) ) {

			$tab = sanitize_text_field( $_POST[ $this->plugin_info['name'] . '-settings-tab-name' ] );

			if ( ! empty( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-type-radio' ] ) ) {

				$redirect_type = sanitize_text_field( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-type-radio' ] );

				if ( 'general' === $redirect_type ) {
					if ( isset( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-general' ] ) ) {
						if ( 'notice' === $tab ) {
							$general_update = wp_unslash( sanitize_post_field( 'post_content', $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-general' ], 0, 'db' ) );
						} else {
							$general_update = sanitize_text_field( wp_unslash( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-general' ] ) );
						}
						update_option( $this->plugin_info['name'] . '-custom-' . $tab . '-general', $general_update );
					}
				} elseif ( 'custom' === $redirect_type ) {
					$custom_type           = array();
					$custom_targets        = array();
					$custom_targets_update = array();

					if ( 'redirect' == $tab ) {

						if ( ! empty( $_POST[ $this->plugin_info['name'] . '-custom-type' ] ) ) {
							$custom_type = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $this->plugin_info['name'] . '-custom-type' ] ) );
						}

						if ( ! empty( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-select' ] ) ) {
							$custom_targets = wp_unslash( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-select' ] );
						}

						if ( ! empty( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-update' ] ) ) {
							$custom_targets_update = wp_unslash( $_POST[ $this->plugin_info['name'] . '-custom-' . $tab . '-update' ] );
							$custom_targets_update = array_map( 'sanitize_text_field', $custom_targets_update );
						}
					}

					$custom_mapping = $this->filter_custom_settings( $custom_type, $custom_targets, $custom_targets_update, $tab );
					update_option( $this->plugin_info['name'] . '-custom-' . $tab . '-mapping', json_encode( $custom_mapping ) );
				}

				update_option( $this->plugin_info['name'] . '-custom-' . $tab . '-type-radio', $redirect_type );
			}
		}

	}

	/**
	 * Filter Custom Settings
	 *
	 * @param array $custom_type [ cat, tag, post, page ].
	 * @param array $custom_redirects     array of IDs of [ Tags, categories, specific posts ].
	 * @param array $custom_redirects_links  array of links.
	 * @return void
	 */
	public function filter_custom_settings( $custom_type, $custom_targets, $custom_targets_update, $tab = '' ) {
		$mapping = array();

		foreach ( $custom_type as $index => $redirect_type ) {

			if ( ! empty( $custom_targets[ $index ] ) && ! empty( $custom_targets_update[ $index ] ) ) {

				$custom_targets[ $index ] = array_map( 'absint', $custom_targets[ $index ] );

				if ( ! empty( $custom_targets[ $index ] ) ) {
					$mapping[] = array(
						'type'   => $redirect_type,
						'target' => $custom_targets[ $index ],
						'link'   => $custom_targets_update[ $index ],
					);
				}
			}
		}

		return $mapping;
	}

	/**
	 * Update Settings Options.
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	private function update_settings_options( $key, $value ) {
		global $wpdb;

		$wpdb->update(
			$wpdb->options,
			array(
				'option_value' => $value,
			),
			array(
				'option_name' => $key,
			)
		);
	}

	/**
	 * Settings Fields Horizontal Line
	 *
	 * @return void
	 */
	public function settings_horizontal_line() {
		?>
		<span class="d-block w-50 mx-auto d-light shadow-sm my-4" >
			<hr/>
		</span>
		<?php
	}

	/**
	 * Settings Tabs.
	 *
	 * @return void
	 */
	public function settings_tabs() {
		?>
		<nav class="nav-tab-wrapper woo-nav-tab-wrapper wp-clearfix">
			<!-- General -->
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_tab_key ) ); ?>" class="nav-tab<?php echo ( ! isset( $_GET['action'] ) || isset( $_GET['action'] ) && 'general' == $_GET['action'] ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'ultimate-maintenance-mode-for-woocommerce' ); ?></a>

			<!-- Redirect -->
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_tab_key . '&action=redirects' ) ); ?>" class="nav-tab<?php echo ( isset( $_GET['action'] ) && 'redirects' == $_GET['action'] ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Redirects', 'ultimate-maintenance-mode-for-woocommerce' ); ?></a>

			<!-- Notices Tab -->
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_tab_key . '&action=notices' ) ); ?>" class="nav-tab<?php echo ( isset( $_GET['action'] ) && 'notices' == $_GET['action'] ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Notice', 'ultimate-maintenance-mode-for-woocommerce' ); ?></a>

			<!-- Popup -->
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_tab_key . '&action=popups' ) ); ?>" class="nav-tab<?php echo esc_attr( ( isset( $_GET['action'] ) && 'popups' == $_GET['action'] ) ? ' nav-tab-active' : '' ); ?>"><?php esc_html_e( 'Popup [Pro]', 'ultimate-maintenance-mode-for-woocommerce' ); ?></a>

			<!-- Pro -->
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=' . $this->settings_tab_key . '&action=pro' ) ); ?>" class="nav-tab<?php echo ( isset( $_GET['action'] ) && 'pro' == $_GET['action'] ) ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( 'Pro', 'ultimate-maintenance-mode-for-woocommerce' ); ?></a>
		</nav>
		<?php
	}


	/**
	 * Clear Emails
	 *
	 * @return void
	 */
	public function clear_emails() {
		delete_option( $this->plugin_info['name'] . '-maintenance-notify-emails' );
	}

	/**
	 * Get User Roles
	 *
	 * @return array
	 */
	private function get_user_roles() {
		$roles     = wp_roles();
		$roles_arr = array();
		foreach ( $roles->roles as $role_key => $role_arr ) {
			$roles_arr[ $role_key ] = $role_arr['name'];
		}
		return $roles_arr;
	}

	/**
	 * Get WooCommerce Products Categories.
	 *
	 * @return array
	 */
	private function get_products_categories() {
		$terms_arr = array(
			'' => __( '&mdash; Select &mdash;' ),
		);
		$terms     = get_terms(
			array(
				'hide_empty' => false,
				'orderby'    => 'count',
				'taxonomy'   => 'product_cat',
			)
		);
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $_term ) {
				$terms_arr[ $_term->term_id ] = $_term->name;
			}
		}

		return $terms_arr;
	}


	/**
	 * Get WooCommerce Products Categories.
	 *
	 * @return void
	 */
	private function get_products_tags() {
		$terms_arr = array(
			'' => __( '&mdash; Select &mdash;' ),
		);
		$terms     = get_terms(
			array(
				'hide_empty' => false,
				'taxonomy'   => 'product_tag',
			)
		);

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $_term ) {
				$terms_arr[ $_term->term_id ] = $_term->name;
			}
		}

		return $terms_arr;
	}

	/**
	 * Get Specific Products.
	 *
	 * @return void
	 */
	private function get_specific_products() {
		$product_id_title_mapping = array();
		$specific_products        = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-selected-products' );
		if ( ! empty( $specific_products ) ) {
			foreach ( (array) $specific_products as $_product_id ) {
				$product_id_title_mapping[ $_product_id ] = get_the_title( $_product_id );
			}
		}

		return $product_id_title_mapping;
	}

	/**
	 * Get WooCommerce Products Categories.
	 *
	 * @return void
	 */
	private function get_products_types() {
		return array_merge( array( '' => __( '&mdash; Select &mdash;' ) ), wc_get_product_types() );
	}

	/**
	 * Create the Tab Fields
	 *
	 * @return void
	 */
	public function create_settings_fields() {

		// General Tab //
		$this->fields[ $this->plugin_info['name'] ]['general'] = array(
			array(
				'name' => __( 'Settings', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type' => 'title',
				'id'   => $this->plugin_info['name'] . '-main-title',
			),
			array(
				'name'     => __( 'Activate', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip' => 'Activate Maintenance Mode',
				'id'       => $this->plugin_info['name'] . '-activate',
				'type'     => 'checkbox',
				'class'    => 'input-checkbox',
				'autoload' => false,
			),
			array(
				'name'     => __( 'Schedule end date', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'       => $this->plugin_info['name'] . '-maintenance-mode-schedule-end-date',
				'desc_tip' => __( 'Select a Date to automatically end the maintenance mode at', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'     => 'date',
				'autoload' => false,
			),
			array(
				'name' => '',
				'type' => 'sectionend',
			),

			array(
				'name' => '',
				'type' => 'horizontal_line',
			),
		);

		// Advanced //
		$this->fields[ $this->plugin_info['name'] ]['general'] = array_merge(
			$this->fields[ $this->plugin_info['name'] ]['general'],
			array(
				array(
					'title' => __( 'Advanced', 'ultimate-maintenance-mode-for-woocommerce' ),
					'type'  => 'title',
					'id'    => $this->plugin_info['name'] . '-main-advanced-title',
				),
				array(
					'title'    => __( 'Enable User Roles Exception', 'ultimate-maintenance-mode-for-woocommerce' ),
					'desc_tip' => 'Disable the maintenance mode to specific user roles ( This option is helpful to debug and test the store while in maintenance mode )',
					'id'       => $this->plugin_info['name'] . '-maintenance-mode-exception',
					'type'     => 'checkbox',
					'class'    => 'input-checkbox',
					'autoload' => false,
				),
				array(
					'title'    => __( 'User Roles', 'ultimate-maintenance-mode-for-woocommerce' ),
					'id'       => $this->plugin_info['name'] . '-maintenance-mode-exception-roles',
					'desc_tip' => 'Select user roles to be excepted from the maintenance mode ( Hold ctrl or Drag for multiselect )',
					'type'     => 'multiselect',
					'options'  => $this->get_user_roles(),
					'autoload' => false,
				),
				array(
					'title' => '',
					'type'  => 'sectionend',
				),
				array(
					'type' => 'horizontal_line',
					'id'   => '',
				),
				array(
					'title'    => __( 'Custom Maintenance Mode', 'ultimate-maintenance-mode-for-woocommerce' ),
					'type'     => 'title',
					'desc_tip' => __( 'Remove the Add to cart option from specific posts or posts by ( type - categories - tags )', 'ultimate-maintenance-mode-for-woocommerce' ),
					'id'       => $this->plugin_info['name'] . '-specific-maintenance-mode-title',
				),
				array(
					'title'    => __( 'Enable Custom-Maintenance Mode', 'ultimate-maintenance-mode-for-woocommerce' ),
					'id'       => $this->plugin_info['name'] . '-specific-maintenance-mode-enable',
					'desc'     => __( 'Apply the maintenance mode on specific products by category/ tag/ type/ etc..', 'ultimate-maintenance-mode-for-woocommerce' ),
					'desc_tip' => __( 'Remove the Add to cart options from specific products only', 'ultimate-maintenance-mode-for-woocommerce' ),
					'type'     => 'checkbox',
					'class'    => 'input-checkbox',
					'autoload' => false,
				),
				array(
					'id'       => $this->plugin_info['name'] . '-maintenance-mode-by-categories',
					'name'     => '',
					'type'     => $this->plugin_info['name'] . '-strict-mode-by-cat',
					'tab'      => 'general',
					'autoload' => false,
				),
				array(
					'title'    => __( 'Tags', 'ultimate-maintenance-mode-for-woocommerce' ),
					'id'       => $this->plugin_info['name'] . '-specific-maintenance-mode-by-tags',
					'desc_tip' => __( 'The maintenance mode will be applied on products that has selected tags', 'ultimate-maintenance-mode-for-woocommerce' ),
					'type'     => 'multiselect',
					'options'  => $this->get_products_tags(),
					'autoload' => false,
				),
				array(
					'title'    => __( 'Products types', 'ultimate-maintenance-mode-for-woocommerce' ),
					'id'       => $this->plugin_info['name'] . '-specific-maintenance-mode-by-product-type',
					'desc_tip' => __( 'The maintenance mode will be applied on selected products types', 'ultimate-maintenance-mode-for-woocommerce' ),
					'type'     => 'multiselect',
					'options'  => $this->get_products_types(),
					'autoload' => false,
				),
				array(
					'title'    => __( 'Specific products', 'ultimate-maintenance-mode-for-woocommerce' ),
					'id'       => $this->plugin_info['name'] . '-specific-maintenance-mode-selected-products',
					'desc_tip' => __( 'The maintenance mode will be applied on selected products', 'ultimate-maintenance-mode-for-woocommerce' ),
					'type'     => 'multiselect',
					'options'  => $this->get_specific_products(),
					'autoload' => false,
				),
				array(
					'name' => '',
					'type' => 'sectionend',
				),
			)
		);

		// Redirects Tab //
		$redirects_fields                                        = array(
			array(
				'title' => __( 'Redirect', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-redirect-title',
			),
			array(
				'title'    => __( 'Enable Redirects', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip' => __( 'Enable / Disable Redirects', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'       => $this->plugin_info['name'] . '-redirect-status',
				'type'     => 'checkbox',
				'class'    => 'input-checkbox',
				'autoload' => false,
			),
			array(
				'name' => '',
				'type' => 'sectionend',
			),
			array(
				'type' => $this->plugin_info['name'] . '-custom-redirect',
				'tab'  => 'redirect',
			),
			array(
				'name' => '',
				'type' => 'sectionend',
			),
		);
		$this->fields[ $this->plugin_info['name'] ]['redirects'] = $redirects_fields;

		// Notices Tab //
		$notices_fields = array(

			// Notices
			array(
				'title' => __( 'Notice', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-notice-title',
			),
			array(
				'title'    => __( 'Enable notices', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip' => __( 'Enable / Disable notices', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'       => $this->plugin_info['name'] . '-notice-status',
				'type'     => 'checkbox',
				'class'    => 'input-checkbox',
				'autoload' => false,
			),
			array(
				'name' => '',
				'type' => 'sectionend',
			),

			array(
				'title' => __( 'Notice', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => $this->plugin_info['name'] . '-notice-title-message',
				'tab'   => 'notice',
			),

			// Custom CSS.
			array(
				'title' => __( 'Styles', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-notice-title-style-message',
			),

			array(
				'title'             => __( 'Custom CSS', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip'          => 'add Custom Css for the maintenance notice, Maintenance mode form or Post form message here..',
				'desc'              => '<p class="description" >Notice Class:&nbsp;&nbsp; .' . $this->plugin_info['name'] . '-notice-wrapper</p>',
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-custom-css',
				'type'              => 'textarea',
				'placeholder'       => 'Custom CSS ...',
				'css'               => 'width: 100%',
				'custom_attributes' => array(
					'rows' => 10,
				),
				'autoload'          => false,
			),

			array(
				'name' => '',
				'type' => 'sectionend',
			),
		);
		$this->fields[ $this->plugin_info['name'] ]['notices'] = $notices_fields;

		$popups_fields = array(
			array(
				'type' => $this->plugin_info['name'] . '-pro-title-message',
				'tab'  => 'popup',
			),
			array(
				'title' => esc_html__( 'Popup', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-popup-title',
			),
			array(
				'title'             => esc_html__( 'Enable Popups', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip'          => esc_html__( 'Enable / Disable popups', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'                => $this->plugin_info['name'] . '-popup-status',
				'type'              => 'checkbox',
				'class'             => 'input-checkbox',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
			array(
				'title'             => esc_html__( 'Auto Hide', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-popup-options[auto_hide]',
				'desc_tip'          => esc_html__( 'Auto hide the popup', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'              => 'checkbox',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),

			array(
				'title'             => esc_html__( 'Auto hide after ( in seconds )', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-popup-options[hide_after]',
				'desc_tip'          => esc_html__( 'Auto hide the popup after ( in seconds ) ', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'              => 'number',
				'default'           => 5,
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),

			array(
				'title'             => esc_html__( 'Popup Position', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-popup-options[popup_position]',
				'desc_tip'          => esc_html__( 'Popup Position on the screen', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'              => 'select',
				'options'           => array(
					'top'          => 'Top Center',
					'top-start'    => 'Top Left',
					'top-end'      => 'Top Right',
					'center'       => 'Center',
					'center-start' => 'Center Left',
					'center-end'   => 'Center Right',
					'bottom'       => 'Bottom Center',
					'bottom-start' => 'Bottom Left',
					'bottom-end'   => 'Bottom Right',
				),
				'default'           => 'center',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),

			array(
				'title'             => esc_html__( 'Popup Frequency', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-popup-options[popup_interval]',
				'desc_tip'          => esc_html__( 'use cookies to track the frequency of showing the Popup ( leave it zeros to disable it )', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'              => $this->plugin_info['name'] . '-maintenance-mode-popup-interval',
				'default'           => 0,
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),

			array(
				'title'             => esc_html__( 'Stop after subscribe', 'ultimate-maintenance-mode-for-woocommerce' ),
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-popup-options[popup_remove_after_subscribe]',
				'desc'              => esc_html__( 'The popup will stop showing after the user is subscribed to the Maintenance mode form', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip'          => esc_html__( 'Stop showing the popup after the user subscribe to the maintenance mode form', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'              => 'checkbox',
				'autoload'          => false,
				'custom_attributes' => array(
					'disabled' => 'disabled',
				),
			),
			array(
				'name' => '',
				'type' => 'sectionend',
				'id'   => '',
			),
			array(
				'title' => esc_html__( 'Popups', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => $this->plugin_info['name'] . '-popup-title-message',
				'tab'   => 'popup',
			),

			array(
				'name' => '',
				'type' => 'horizontal_line',
				'id'   => '',
			),

			array(
				'title' => esc_html__( 'Maintenance mode form', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-subscribe-form-title-message',
				'css'   => 'width: 100%',
				'desc'  => esc_html__( 'Add a subscribe form shortcode to the notice / popup message so the clients can add their email and will be notified when the maintenance mode is finished { The form shortcode [gpls_wsm_maintenance_mode_subscribe_form] }. Subscribed Emails wil be listed in ' ) . '<b>' . esc_html__( 'Emails Tab', 'ultimate-maintenance-mode-for-woocommerce' ) . '</b>',
			),

			array(
				'name' => '',
				'type' => 'sectionend',
			),
			array(
				'name' => '',
				'type' => 'horizontal_line',
			),

			// Custom CSS.
			array(
				'title' => esc_html__( 'Styles', 'ultimate-maintenance-mode-for-woocommerce' ),
				'type'  => 'title',
				'id'    => $this->plugin_info['name'] . '-notice-title-style-message',
			),

			array(
				'title'             => esc_html__( 'Custom CSS', 'ultimate-maintenance-mode-for-woocommerce' ),
				'desc_tip'          => 'add Custom Css for the maintenance notice, Maintenance mode form or Post form message here..',
				'desc'              => '<dl class="row" ><dt class="col-sm-3" >Notice Class:</dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-notice-wrapper</dd><dt class="col-sm-3" >subscribe Form Class:</dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-subscribe-form </dd><dt class="col-sm-3" >After subscribe Form Submit Class: </dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-post-notify-submit </dd><dt class="col-sm-3" >Popup Class:</dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-popup</dd> <dt class="col-sm-3" >Popup Header Class:</dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-popup-header</dd> <dt class="col-sm-3" >Popup Content Class:</dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-popup-content</dd> <dt class="col-sm-3" >Popup Footer Class:</dt> <dd class="col-sm-9">.' . $this->plugin_info['name'] . '-popup-footer</dd></dl>',
				'id'                => $this->plugin_info['name'] . '-maintenance-mode-custom-css',
				'type'              => 'textarea',
				'placeholder'       => esc_html__( 'Custom CSS ...', 'ultimate-maintenance-mode-for-woocommerce' ),
				'css'               => 'width: 100%',
				'custom_attributes' => array(
					'rows' => 10,
				),
				'autoload'          => false,
			),

			array(
				'name' => '',
				'type' => 'sectionend',
			),
		);
		$this->fields[ $this->plugin_info['name'] ]['popups'] = $popups_fields;

	}

	/**
	 * IS Strict Mode.
	 *
	 * @return boolean
	 */
	public function is_strict_mode() {
		$strict_mode = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-enable' ) ?: '';
		return 'yes' == $strict_mode ? true : false;
	}


	/**
	 * Get Main Values.
	 *
	 * @return array
	 */
	public function get_main_settings() {
		$settings = array();
		// General.
		$settings['activate'] = get_option( $this->plugin_info['name'] . '-activate' ) ?: '';
		$settings['end_date'] = get_option( $this->plugin_info['name'] . '-maintenance-mode-schedule-end-date' ) ?: '';
		return $settings;
	}

	/**
	 * Get Roles Settings.
	 *
	 * @return array
	 */
	public function get_roles_settings() {
		$settings                    = array();
		$settings['roles_exception'] = get_option( $this->plugin_info['name'] . '-maintenance-mode-exception' ) ?: '';
		$settings['roles']           = get_option( $this->plugin_info['name'] . '-maintenance-mode-exception-roles' ) ?: '';
		return $settings;
	}


	/**
	 * Get notice Settings.
	 *
	 * @return array
	 */
	public function get_notices_settings() {
		$settings                   = array();
		$settings['notice_status']  = get_option( $this->plugin_info['name'] . '-notice-status' ) ?: false;
		$settings['notice_type']    = get_option( $this->plugin_info['name'] . '-custom-notice-type-radio' ) ?: 'general';
		$settings['general_notice'] = get_option( $this->plugin_info['name'] . '-custom-notice-general' ) ?: '';
		$settings['notices']        = json_decode( get_option( $this->plugin_info['name'] . '-custom-notice-mapping', '' ), true );
		return $settings;
	}

	/**
	 * Get Strict Mode Settings.
	 *
	 * @return array
	 */
	public function get_strict_mode_settings() {
		$settings                             = array();
		$settings['strict_mode_cat']          = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-categories' ) ?: '';
		$settings['strict_mode_tag']          = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-tags' ) ?: '';
		$settings['strict_mode_product_type'] = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-by-product-type' ) ?: '';
		$settings['strict_mode_selected']     = get_option( $this->plugin_info['name'] . '-specific-maintenance-mode-selected-products' ) ?: '';
		return $settings;
	}

	/**
	 * Get redirect Settings.
	 *
	 * @return array
	 */
	public function get_redirect_settings() {
		$settings                     = array();
		$settings['redirect_status']  = get_option( $this->plugin_info['name'] . '-redirect-status', false );
		$settings['redirect_type']    = get_option( $this->plugin_info['name'] . '-custom-redirect-type-radio' ) ?: '';
		$settings['redirects']        = json_decode( get_option( $this->plugin_info['name'] . '-custom-redirect-mapping', '' ), true );
		$settings['general_redirect'] = get_option( $this->plugin_info['name'] . '-custom-redirect-general', '' );
		return $settings;
	}

	/**
	 * get Custom CSS
	 *
	 * @return string
	 */
	public function get_custom_css() {
		return get_option( $this->plugin_info['name'] . '-maintenance-mode-custom-css', '' );
	}

	/**
	 * Plugin Settings Tab in WordPress Settings Page.
	 *
	 * @return array
	 */
	public function add_settings_tab( $settings_tabs ) {
		foreach ( array_keys( $this->settings_tab ) as $name ) {
			$settings_tabs[ $name ] = $this->settings_tab[ $name ];
		}
		return $settings_tabs;
	}

	/**
	 * SHow the Settings Tab Fields.
	 *
	 * @return void
	 */
	public function settings_tab_action() {
		$this->create_settings_fields();
		$this->admin_styles();
		if ( ! empty( $_GET['action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_GET['action'] ) );

			if ( 'redirects' === $action ) {
				$this->redirects_tab();
			} elseif ( 'notices' === $action ) {
				$this->notices_tab();
			} elseif ( 'popups' === $action ) {
				$this->popups_tab();
			} elseif ( 'pro' === $action ) {
				$GLOBALS['hide_save_button'] = true;
				do_action( $this->plugin_info['name'] . '-pro-tab-content' );
			}
		} else {
			$this->get_user_roles();
			woocommerce_admin_fields( $this->fields[ $this->plugin_info['name'] ]['general'] );
		}
	}

	/**
	 * Admin Styles
	 *
	 * @return void
	 */
	public function admin_styles() {
		?>
		<style>
			.CodeMirror-wrap pre {
				text-align: left !important;
			}
		</style>
		<?php
	}

	/**
	 * Redirects Tab
	 *
	 * @return void
	 */
	public function redirects_tab() {
		woocommerce_admin_fields( $this->fields[ $this->plugin_info['name'] ]['redirects'] );
	}

	/**
	 * Notices Tab
	 */
	public function notices_tab() {
		woocommerce_admin_fields( $this->fields[ $this->plugin_info['name'] ]['notices'] );
	}

	/**
	 * Popups Tab
	 *
	 * @return void
	 */
	public function popups_tab() {
		woocommerce_admin_fields( $this->fields[ $this->plugin_info['name'] ]['popups'] );
	}

	/**
	 * Check if Is Settings Page
	 *
	 * @return boolean
	 */
	private function is_settings_page() {
		if ( is_admin() && ! empty( $_GET['page'] ) && 'wc-settings' === sanitize_text_field( wp_unslash( $_GET['page'] ) ) && ! empty( $_GET['tab'] ) && $this->plugin_info['name'] === sanitize_text_field( wp_unslash( $_GET['tab'] ) ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Save Tab Settings.
	 *
	 * @return void
	 */
	public function save_settings() {
		$action = '';
		$this->create_settings_fields();
		if ( empty( $_GET['action'] ) ) {
			$action = 'general';
		}

		if ( ! empty( $_GET['action'] ) && in_array( sanitize_text_field( $_GET['action'] ), array_keys( $this->fields[ $this->plugin_info['name'] ] ) ) ) {
			$action = sanitize_text_field( $_GET['action'] );
		}

		woocommerce_update_options( $this->fields[ $this->plugin_info['name'] ][ $action ] );
	}

	/**
	 * Get The Site Pages as dropdown.
	 *
	 * @return array
	 */
	public function site_pages() {
		$excluded_pages = array();
		$checkout_page  = get_page_by_path( 'checkout' );

		if ( $checkout_page ) {
			$excluded_pages[] = $checkout_page->ID;
		}

		$pages_dropdown = array();
		$pages          = get_pages(
			array(
				'exclude' => $excluded_pages,
			)
		);

		foreach ( $pages as $page ) {
			$pages_dropdown[ $page->ID ] = esc_html( $page->post_title );
		}
		return $pages_dropdown;
	}

}
