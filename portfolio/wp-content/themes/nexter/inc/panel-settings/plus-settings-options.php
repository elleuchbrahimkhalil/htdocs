<?php 
/**
 * Nexter Settings Panel
 *
 * @package	Nexter
 * @since	1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}
/**
 * White Label Content
 */
function nexter_white_label_content(){
	echo '<div class="nxt-pro-note-title"><p style="margin-bottom:50px;">'.esc_html__('White Label our plugin and setup client\'s branding all around. You can update name, description, Icon and even hide the menu from dashboard. Get our pro version to have access of this feature.','nexter').'</p></div>
		<div style="text-align:center;">
			<img style="width:55%;" src="'.esc_url(NXT_THEME_URI .'assets/images/white-lable.png').'" alt="'.esc_attr__('White Label','nexter').'" class="panel-plus-white-lable" />
		</div>';
}
add_action('nexter_white_label_notice', 'nexter_white_label_content' );

/**
 * Activate Content
 */
function nexter_activate_content(){
	echo '<div class="nxt-active-notice-pro">
			<img style="width:55%;" src="'.esc_url(NXT_THEME_URI .'assets/images/activate.png').'" alt="'.esc_attr__('Activate','nexter').'" class="panel-plus-activate" />
			<div class="nxt-pro-active-msg">Have you already bought a <a href="'.esc_url('https://nexterwp.com/pricing/').'" target="_blank" rel="noreferrer noopener">PRO</a> version? Visit store <a href="'.esc_url('https://store.posimyth.com/download/').'" target="_blank" rel="noreferrer noopener">https://store.posimyth.com/download/</a> to download latest pro plugin</div>
		</div>';
}
add_action('nexter_activate_notice', 'nexter_activate_content' );

/**
 * Performance Notice Content
 */
function nexter_site_performance_notice_content(){
	echo '<div class="nxt-pro-note-title"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/free-notice.svg').'" alt="free-pro-notice"/><p style="margin-bottom:40px;">'.esc_html__('Sorry! You have to install & activate “Nexter Extension” plugin to use available option.','nexter').'</p></div>
		<div class="nxt-pro-note-link">'.wp_kses_post(nexter_ext_plugin_load_notice()).'</div>';
}
add_action('nexter_site_performance_notice', 'nexter_site_performance_notice_content' );

/**
 * Security Notice Content
 */
function nexter_site_security_notice_content(){
	echo '<div class="nxt-pro-note-title"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/free-notice.svg').'" alt="free-pro-notice"/><p style="margin-bottom:40px;">'.esc_html__('Sorry! You have to install & activate “Nexter Extension” plugin to use available option.','nexter').'</p></div>
		<div class="nxt-pro-note-link">'.wp_kses_post(nexter_ext_plugin_load_notice()).'</div>';
}
add_action('nexter_site_security_notice', 'nexter_site_security_notice_content' );

/**
 * Nexter Extensions load Notice
 */
function nexter_ext_plugin_load_notice() {
	$plugin = 'nexter-extension/nexter-extension.php';
	$output = '';
	$installed_plugins = get_plugins();
	if ( isset( $installed_plugins[ $plugin ] ) ) {
		if ( ! current_user_can( 'activate_plugins' ) ) { return; }
		$activation_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin );
		$output .= sprintf( '<a href="%s">%s</a>', $activation_url, esc_html__( 'Activate Nexter Extensions', 'nexter' ) );
	} else {
		if ( ! current_user_can( 'install_plugins' ) ) { return; }
		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=nexter-extension' ), 'install-plugin_nexter-extension' );
		$output .= sprintf( '<a href="%s">%s</a>', $install_url, esc_html__( 'Install Nexter Extension', 'nexter' ) );
	}
	return wp_kses_post($output);
}

/*
 * Extra options Config
 * @since 1.1.0
 */
function nexter_extension_option_config(){
	$config = [
		'google-recaptcha' => [
			'title' => esc_html__( 'Google reCAPTCHA', 'nexter' ),
			'description' => esc_html__( 'Stop Spammers and Bad bots from visiting your site. Add Google reCAPTCHA on your WP-Admin, comments etc.', 'nexter' ),
			'type' => 'free',
			'svg' => NXT_THEME_URI.'assets/images/panel-icon/google-recaptch.svg',
			'priority' => 5,
			'button' => true,
		],
		'wp-replace-url' => [
			'title' => esc_html__( 'Replace URL & Text', 'nexter' ),
			'description' => esc_html__( 'Facing HTTPS issues or moved staging to live?  Replace your olddomain.com with newdomain.com from completely from database.', 'nexter' ),
			'type' => 'free',
			'svg' => NXT_THEME_URI.'assets/images/panel-icon/replace-url.svg',
			'priority' => 7,
			'button' => false,
		],
		'wp-duplicate-post' => [
			'title' => esc_html__( 'Duplicate Post', 'nexter' ),
			'description' => esc_html__( 'This option gives you to duplicate any post types including taxonomies & custom fields.', 'nexter' ),
			'type' => 'free',
			'svg' => NXT_THEME_URI.'assets/images/panel-icon/duplicate-post.svg',
			'priority' => 8,
			'button' => true,
		],
	];

	return apply_filters('nexter-extension-extra-option-config', $config );
}

/**
 * Extra Options Import Export Customizer
 * @since 1.0.11
 */
function nexter_extra_options_content(){
	$config_option = nexter_extension_option_config();

	$extension = get_option( 'nexter_extra_ext_options' );
	echo '<div class="nxt-extra-opt-wrap nxt-mt-50">';
		echo '<div class="nxt-panel-row">';
			if( !empty($config_option) ){
				$columns = array_column($config_option, 'priority');
				array_multisort($columns, SORT_ASC, $config_option);

				foreach($config_option as $name => $data){
					echo '<div class="nxt-panel-col nxt-panel-col-33">';
						echo '<div class="nxt-panel-sec nxt-'.esc_attr($name).' nxt-p-20">';
							echo '<div class="nxt-extra-icon"><img src="'.esc_url($data['svg']).'" alt="'.esc_attr($name).'" /></div>';
							if(isset($data['beta']) && !empty($data['beta'])){
								echo '<div class="nxt-beta-ext">'.esc_html__('Beta','nexter').'</div>';
							}
							echo '<div class="nxt-extra-title">';
								echo wp_kses_post($data['title']);
								echo '<span class="nxt-desc-icon" >';
									echo '<img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="'.esc_attr($name).'" /> ';
									echo '<div class="nxt-tooltip">'.wp_kses_post($data['description']).'</div>';
								echo '</span>';
							echo '</div>';
							if($data['button'] == true){
								if($name == 'regenerate-thumbnails' ){
									echo '<button class="nxt-ext-btn nxt-ext-active" data-ext="'.esc_attr($name).'" data-enable-disable="active"><span>'.esc_html__( 'Regenerate', 'nexter' ).'</span></button>';
//                                    continue;
								}
								else if( !empty($extension) && !empty($extension[ $name ]) && !empty($extension[ $name ]['switch'])){
									echo '<button class="nxt-ext-btn nxt-ext-deactivate" data-ext="'.esc_attr($name).'" data-enable-disable="deactive"><span>'.esc_html__( 'Deactivate', 'nexter' ).'</span></button>';
									echo '<button class="nxt-ext-btn nxt-ext-settings" data-ext="'.esc_attr($name).'"><span><img src ="'.esc_url(NXT_THEME_URI.'assets/images/panel-icon/setting.svg').'" alt="Setting" /></span></button>';
								}else{
									echo '<button class="nxt-ext-btn nxt-ext-active" data-ext="'.esc_attr($name).'" data-enable-disable="active"><span>'.esc_html__( 'Enable', 'nexter' ).'</span></button>';
								}
							}else{
								if($name == 'branded-wp-admin'){
									echo '<button class="nxt-ext-coming-soon" data-ext="'.esc_attr($name).'"><span>'.esc_html__( 'Coming Soon', 'nexter' ).'</span></button>';
								}

                                else{
									echo '<button class="nxt-ext-btn nxt-ext-settings" data-ext="'.esc_attr($name).'"><span>'.esc_html__( 'Settings', 'nexter' ).'</span></button>';
								}
							}
						echo '</div>';
					echo '</div>';
				}
			}
		echo '</div>';
	echo '</div>';
	?>
	<div class="nxt-customizer-import-export">
		<div class="nxt-sec-title nxt-ie-heading-title"><?php echo esc_html__( 'Import & Export of Theme Customizer Settings', 'nexter' ); ?></div>
		<div class="nxt-customizer-export-wrap">
			<h3 class="nxt-cust-ie-title"><span><svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="file-download" class="svg-inline--fa fa-file-download" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="25"><path fill="currentColor" d="M369.9 97.98L286.02 14.1c-9-9-21.2-14.1-33.89-14.1H47.99C21.5.1 0 21.6 0 48.09v415.92C0 490.5 21.5 512 47.99 512h288.02c26.49 0 47.99-21.5 47.99-47.99V131.97c0-12.69-5.1-24.99-14.1-33.99zM256.03 32.59c2.8.7 5.3 2.1 7.4 4.2l83.88 83.88c2.1 2.1 3.5 4.6 4.2 7.4h-95.48V32.59zm95.98 431.42c0 8.8-7.2 16-16 16H47.99c-8.8 0-16-7.2-16-16V48.09c0-8.8 7.2-16.09 16-16.09h176.04v104.07c0 13.3 10.7 23.93 24 23.93h103.98v304.01zM208 216c0-4.42-3.58-8-8-8h-16c-4.42 0-8 3.58-8 8v88.02h-52.66c-11 0-20.59 6.41-25 16.72-4.5 10.52-2.38 22.62 5.44 30.81l68.12 71.78c5.34 5.59 12.47 8.69 20.09 8.69s14.75-3.09 20.09-8.7l68.12-71.75c7.81-8.2 9.94-20.31 5.44-30.83-4.41-10.31-14-16.72-25-16.72H208V216zm42.84 120.02l-58.84 62-58.84-62h117.68z"></path></svg></span><?php echo esc_html__( 'Export Settings', 'nexter' ); ?></h3>
			<p><?php echo esc_html__( 'Export all your theme customizer settings using below button.', 'nexter' ); ?></p>
			<form method="post">
				<input type="hidden" name="nxt_customizer_export_action" value="nxt_export_cust" />
				<p style="margin-bottom:0">
					<?php wp_nonce_field( 'nexter_export_cust_nonce', 'nexter_export_cust_nonce' ); ?>
					<?php submit_button( __( 'Export Settings', 'nexter' ), 'nxt-cust-ie-btn', 'submit', false, array( 'id' => '' ) ); ?>
				</p>
			</form>
		</div>
		<div class="nxt-customizer-import-wrap">
			<h3 class="nxt-cust-ie-title"><span><svg aria-hidden="true" focusable="false" data-prefix="fal" data-icon="file-upload" class="svg-inline--fa fa-file-upload fa-w-12" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="25"><path fill="currentColor" d="M369.9 97.98L286.02 14.1c-9-9-21.2-14.1-33.89-14.1H47.99C21.5.1 0 21.6 0 48.09v415.92C0 490.5 21.5 512 47.99 512h288.02c26.49 0 47.99-21.5 47.99-47.99V131.97c0-12.69-5.1-24.99-14.1-33.99zM256.03 32.59c2.8.7 5.3 2.1 7.4 4.2l83.88 83.88c2.1 2.1 3.5 4.6 4.2 7.4h-95.48V32.59zm95.98 431.42c0 8.8-7.2 16-16 16H47.99c-8.8 0-16-7.2-16-16V48.09c0-8.8 7.2-16.09 16-16.09h176.04v104.07c0 13.3 10.7 23.93 24 23.93h103.98v304.01zm-180.1-247.32l-68.12 71.75c-7.81 8.2-9.94 20.31-5.44 30.83 4.41 10.31 14 16.72 25 16.72H176V424c0 4.42 3.58 8 8 8h16c4.42 0 8-3.58 8-8v-88.02h52.66c11 0 20.59-6.41 25-16.72 4.5-10.52 2.38-22.62-5.44-30.81l-68.12-71.78c-10.69-11.19-29.51-11.2-40.19.02zm-38.75 87.29l58.84-62 58.84 62H133.16z"></path></svg></span><?php echo esc_html__( 'Import Settings', 'nexter' ); ?></h3>
			<p><?php echo esc_html__( 'Import file to get all your customizer settings.', 'nexter' ); ?></p>
			<form method="post" enctype="multipart/form-data">
				<p><input type="file" name="nxt_import_file"/></p>
				<p style="margin-bottom:0">
					<input type="hidden" name="nxt_customizer_import_action" value="nxt_import_cust" />
					<?php wp_nonce_field( 'nexter_import_cust_nonce', 'nexter_import_cust_nonce' ); ?>
					<?php submit_button( __( 'Import', 'nexter' ), 'nxt-cust-ie-btn', 'submit', false, array( 'id' => '' ) ); ?>
				</p>
			</form>
			<?php 
				$imported = ( isset($_GET['status_customizer']) && !empty($_GET['status_customizer']) ) ? sanitize_text_field($_GET['status_customizer']) : '';
				if(!empty($imported) && $imported=='success'){
					echo '<div class="nxt-import-success-msg">'.esc_html__( 'Success! All Settings are Imported. Check that in Theme Customer.', 'nexter' ).'</div>';
				}
			?>
		</div>
	</div>
	<?php
}
add_action('nexter_extra_options_render', 'nexter_extra_options_content' );

function nexter_help_actions_render(){
	echo '<div class="nxt-welcome-support-toggle" title="'.esc_attr__('Need Help?','nexter').'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M202.021 0C122.202 0 70.503 32.703 29.914 91.026c-7.363 10.58-5.093 25.086 5.178 32.874l43.138 32.709c10.373 7.865 25.132 6.026 33.253-4.148 25.049-31.381 43.63-49.449 82.757-49.449 30.764 0 68.816 19.799 68.816 49.631 0 22.552-18.617 34.134-48.993 51.164-35.423 19.86-82.299 44.576-82.299 106.405V320c0 13.255 10.745 24 24 24h72.471c13.255 0 24-10.745 24-24v-5.773c0-42.86 125.268-44.645 125.268-160.627C377.504 66.256 286.902 0 202.021 0zM192 373.459c-38.196 0-69.271 31.075-69.271 69.271 0 38.195 31.075 69.27 69.271 69.27s69.271-31.075 69.271-69.271-31.075-69.27-69.271-69.27z"/></svg></div>';

	echo '<div class="nxt-welcome-support-content">';
		echo '<div class="nxt-sup-top">';
				echo '<h3 class="nxt-quick-sup-title">'.esc_html__('Quick support','nexter').'</h3>';
				if(defined('NXT_PRO_EXT') && class_exists('Nexter_Pro_Ext_Activate')){
					$active_status = Nexter_Pro_Ext_Activate::nexter_ext_pro_activate_msg();
					if(!empty($active_status) && isset($active_status['status']) && $active_status['status']=='valid'){
						echo '<a class="nxt-sup-free-btn pro-activated" ><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/diamond.svg').'" />'.esc_html__('PRO ACTIVATED','nexter').'</a>';
					}
				}else{
					echo '<a class="nxt-sup-free-btn">'.esc_html__('FREE','nexter').'</a>';
				}
		echo '</div>';
		echo '<div class="nxt-support-inner">';
			echo '<ul class="nxt-support-list">';
					echo '<li><a href="'.( (!defined('NXT_PRO_EXT')) ? esc_url('https://wordpress.org/support/theme/nexter/') : esc_url('https://store.posimyth.com/helpdesk/')).'"  target="_blank" rel="noopener noreferrer"><span class="support-title-wrap"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/free-support-icon.svg').'" />'.( (!defined('NXT_PRO_EXT')) ? esc_html__('Get Free Support','nexter') : esc_html__('Get Premium Support','nexter') ).'</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" fill="none" stroke="#22379c"><path class="arrow-one" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-two" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-three" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>';
					echo '<li><a href="'.( (!defined('NXT_PRO_EXT')) ? esc_url('https://roadmap.nexterwp.com/updates?filter=Free+Theme') : esc_url('https://roadmap.nexterwp.com/updates')).'"  target="_blank" rel="noopener noreferrer"><span class="support-title-wrap sup-latest-update"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/support-latest-update.svg').'" />'.esc_html__('Latest Updates','nexter').'</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" fill="none" stroke="#22379c"><path class="arrow-one" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-two" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-three" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>';
					echo '<li><a href="'.esc_url('https://www.facebook.com/groups/139678088029161/').'" target="_blank" rel="noopener noreferrer"><span class="support-title-wrap"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/support-join-fb.svg').'" />'.esc_html__('Join Facebook Community','nexter').'</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" fill="none" stroke="#22379c"><path class="arrow-one" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-two" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-three" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>';
					echo '<li><a href="'.esc_url('https://roadmap.nexterwp.com/boards/feature-requests').'" target="_blank" rel="noopener noreferrer"><span class="support-title-wrap"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/support-new-fea.svg').'" />'.esc_html__('Suggest New Features','nexter').'</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" fill="none" stroke="#22379c"><path class="arrow-one" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-two" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-three" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>';
					echo '<li><a href="'.esc_url('https://roadmap.nexterwp.com/boards/bug').'" target="_blank" rel="noopener noreferrer"><span class="support-title-wrap"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/support-report-bug.svg').'" />'.esc_html__('Report Bug','nexter').'</span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="16" fill="none" stroke="#22379c"><path class="arrow-one" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-two" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path  class="arrow-three" d="M1 15l7-7-7-7" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>';
			echo '</ul>';
			if( !defined('NXT_PRO_EXT') ){
				echo '<a href="'.esc_url('https://nexterwp.com/pricing/').'" class="sup-upgrade-get-pro"><img src="'.esc_url(NXT_THEME_URI.'/assets/images/panel-icon/sup-upgrade-pro.svg').'" />'.esc_html__('Upgrade to PRO Version','nexter').'</a>';
			}
			echo '<div class="nxt-panel-row" style="justify-content: space-between;">';
					echo '<a href="'.esc_url('https://www.youtube.com/c/POSIMYTHInnovations/?sub_confirmation=1').'" class="nxt-panel-col sup-social-link" title="'.esc_html__('Youtube','nexter').'" target="_blank" rel="noopener noreferrer"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" ><path d="M13.685 4.09c-.161-.604-.633-1.08-1.234-1.242C11.363 2.555 7 2.555 7 2.555s-4.363 0-5.451.293C.948 3.01.476 3.485.315 4.09.024 5.185.024 7.47.024 7.47s0 2.285.292 3.38c.16.604.633 1.06 1.234 1.222 1.088.293 5.451.293 5.451.293s4.363 0 5.451-.293c.6-.162 1.073-.617 1.234-1.222.292-1.095.292-3.38.292-3.38s0-2.285-.292-3.38zM5.573 9.544V5.395L9.22 7.47 5.573 9.544z" fill="#22379b"/></svg></span></a>';
					echo '<a href="'.esc_url('https://twitter.com/posimyth').'" class="nxt-panel-col sup-social-link" title="'.esc_html__('Twitter','nexter').'" target="_blank" rel="noopener noreferrer"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" ><path d="M12.561 4.148l.009.373c0 3.793-2.887 8.164-8.164 8.164A8.11 8.11 0 0 1 0 11.397c.231.027.453.036.693.036 1.341 0 2.576-.453 3.562-1.226-1.261-.027-2.319-.853-2.683-1.99a3.62 3.62 0 0 0 .542.044c.258 0 .515-.036.755-.098A2.87 2.87 0 0 1 .569 5.348v-.036a2.89 2.89 0 0 0 1.297.364C1.093 5.161.586 4.282.586 3.287a2.85 2.85 0 0 1 .391-1.448C2.39 3.58 4.513 4.717 6.893 4.841a3.24 3.24 0 0 1-.071-.657 2.87 2.87 0 0 1 2.869-2.869 2.86 2.86 0 0 1 2.096.906 5.65 5.65 0 0 0 1.821-.693 2.86 2.86 0 0 1-1.261 1.581A5.75 5.75 0 0 0 14 2.665a6.17 6.17 0 0 1-1.439 1.483z" fill="#22379b"/></svg></span></a>';
					echo '<a href="'.esc_url('https://www.facebook.com/nexterwp').'" class="nxt-panel-col sup-social-link" title="'.esc_html__('Facebook','nexter').'" target="_blank" rel="noopener noreferrer"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" ><path d="M10.257 7.875l.389-2.534H8.215V3.697c0-.693.34-1.369 1.428-1.369h1.105V.171S9.746 0 8.787 0C6.785 0 5.476 1.214 5.476 3.41v1.931H3.25v2.534h2.226V14h2.739V7.875h2.042z" fill="#22379b"/></svg></span></a>';
					echo '<a href="'.esc_url('https://www.instagram.com/posimyth').'" class="nxt-panel-col sup-social-link" title="'.esc_html__('Instagram','nexter').'" target="_blank" rel="noopener noreferrer"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" ><path d="M7.003 3.855c-1.739 0-3.142 1.403-3.142 3.142s1.403 3.142 3.142 3.142 3.142-1.403 3.142-3.142-1.403-3.142-3.142-3.142zm0 5.184c-1.124 0-2.043-.916-2.043-2.043s.916-2.043 2.043-2.043 2.043.916 2.043 2.043S8.127 9.04 7.003 9.04zm4.003-5.313a.73.73 0 1 1-1.466 0c0-.405.328-.733.733-.733s.733.328.733.733zm2.081.744c-.046-.982-.271-1.851-.99-2.568S10.511.962 9.529.913C8.518.856 5.485.856 4.473.913c-.979.046-1.848.271-2.568.987S.965 3.486.916 4.468C.859 5.48.859 8.512.916 9.524c.046.982.271 1.851.99 2.568s1.586.941 2.568.99c1.012.057 4.044.057 5.056 0 .982-.046 1.851-.271 2.568-.99s.941-1.586.99-2.568c.057-1.012.057-4.041 0-5.053zm-1.307 6.139c-.213.536-.626.949-1.165 1.165-.807.32-2.721.246-3.612.246s-2.808.071-3.612-.246a2.07 2.07 0 0 1-1.165-1.165c-.32-.807-.246-2.721-.246-3.612s-.071-2.808.246-3.612c.213-.536.626-.949 1.165-1.165.807-.32 2.721-.246 3.612-.246s2.808-.071 3.612.246a2.07 2.07 0 0 1 1.165 1.165c.32.807.246 2.721.246 3.612s.074 2.808-.246 3.612z" fill="#22379b"/></svg></span></a>';
					echo '<a href="'.esc_url('https://nexterwp.com/blog').'" class="nxt-panel-col sup-social-link" title="'.esc_html__('Blogs','nexter').'" target="_blank" rel="noopener noreferrer"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" ><path d="M1.574 4.815a.66.66 0 0 0-.698.61c-.023.361.251.674.612.697 3.284.215 6.171 3.082 6.387 6.388.023.35.311.615.63.615l.044-.002c.361-.023.636-.336.612-.697C8.93 8.485 5.515 5.07 1.574 4.815zM1.75.875c-.483 0-.875.391-.875.875s.392.875.875.875a9.64 9.64 0 0 1 9.625 9.625c0 .484.392.875.875.875s.875-.391.875-.875A11.39 11.39 0 0 0 1.75.875zm.85 8.747c-.943 0-1.725.785-1.725 1.753s.783 1.75 1.725 1.75a1.77 1.77 0 0 0 1.752-1.75c0-.967-.76-1.753-1.752-1.753z" fill="#22379b"/></svg></span></a>';
					echo '<a href="'.esc_url('https://store.posimyth.com/join-affiliate/').'" class="nxt-panel-col sup-social-link" title="'.esc_html__('Join Affliate','nexter').'" target="_blank" rel="noopener noreferrer"><span><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" ><path d="M7.031 11.874c-.653 0-1.241-.079-1.764-.238-.523-.168-1.022-.457-1.498-.868-.121-.103-.215-.219-.28-.35a.87.87 0 0 1-.098-.392.79.79 0 0 1 .224-.56c.159-.168.355-.252.588-.252a.74.74 0 0 1 .476.168c.355.289.705.509 1.05.658.355.149.789.224 1.302.224.345 0 .663-.051.952-.154.289-.112.523-.257.7-.434.177-.187.266-.397.266-.63a1.06 1.06 0 0 0-.252-.714c-.168-.196-.425-.359-.77-.49-.345-.14-.784-.247-1.316-.322-.504-.075-.947-.187-1.33-.336-.383-.159-.705-.355-.966-.588a2.45 2.45 0 0 1-.574-.84c-.131-.327-.196-.691-.196-1.092 0-.607.154-1.125.462-1.554.317-.429.742-.756 1.274-.98s1.12-.336 1.764-.336c.607 0 1.167.093 1.68.28.523.177.947.406 1.274.686.271.215.406.462.406.742 0 .205-.079.392-.238.56s-.345.252-.56.252c-.14 0-.266-.042-.378-.126-.149-.131-.35-.252-.602-.364a4.52 4.52 0 0 0-.798-.294c-.28-.084-.541-.126-.784-.126-.401 0-.742.051-1.022.154-.271.103-.476.243-.616.42s-.21.383-.21.616c0 .28.079.513.238.7.168.177.406.322.714.434a9.02 9.02 0 0 0 1.106.28c.56.103 1.05.224 1.47.364.429.14.784.322 1.064.546a1.98 1.98 0 0 1 .63.826c.14.327.21.728.21 1.204 0 .607-.168 1.129-.504 1.568s-.779.775-1.33 1.008c-.541.233-1.129.35-1.764.35zm1.008.756c0 .243-.084.443-.252.602-.159.168-.359.252-.602.252-.233 0-.429-.084-.588-.252-.159-.159-.238-.359-.238-.602V1.374c0-.243.079-.443.238-.602.168-.168.373-.252.616-.252s.439.084.588.252c.159.159.238.359.238.602V12.63z" fill="#22379c"/></svg></span></a>';
			echo '</div>';
		echo '</div>';
		echo '<a href="'.esc_url('https://posimyth.com/').'" class="nxt-support-bottom" target="_blank" rel="noopener noreferrer">'.esc_html__('Powered by POSIMYTH Innovations','nexter').'</a>';
	echo '</div>';
}
add_action('nexter_help_actions', 'nexter_help_actions_render');

/**
 * Import Data Content
 */
function nexter_import_data_content(){
	echo '<div class="nxt-import-data-content">
			<div class="nxt-import-steps import-step-1 active" data-step="step-1">
				<div class="nxt-import-heading">'.esc_html__('Select Your Page Builder :','nexter').'</div>
				<div class="nxt-select-builder textleft">
					<input type="radio" name="nxt-select-build" id="builder-gutenberg" value="gutenberg" />
					<label class="nxt-builder-select" for="builder-gutenberg">
						<span><img src="'.esc_url(NXT_THEME_URI .'assets/images/gutenberg.png').'" alt="'.esc_attr__('Gutenberg','nexter').'" /></span>
						<span>'.esc_html__('Gutenberg','nexter').'</span>
					</label>
					<input type="radio" name="nxt-select-build" id="builder-elementor" value="elementor" />
					<label class="nxt-builder-select" for="builder-elementor">
						<span><img src="'.esc_url(NXT_THEME_URI .'assets/images/elementor.png').'" alt="'.esc_attr__('Elementor','nexter').'" /></span>
						<span>'.esc_html__('Elementor','nexter').'</span>
					</label>
					<input type="radio" name="nxt-select-build" id="builder-beaver" value="beaver" />
					<label class="nxt-builder-select" for="builder-beaver">
						<span><img src="'.esc_url(NXT_THEME_URI .'assets/images/beaver.png').'" alt="'.esc_attr__('Beaver','nexter').'" /></span>
						<span>'.esc_html__('Beaver','nexter').'</span>
					</label>
				</div>
			</div>
			<div class="nxt-import-step-btn"><a href="#" class="import-step-next" data-step="step-2">'.esc_html__('Next','nexter').'</a></div>
		</div>';
}
add_action('nexter_import_data_render', 'nexter_import_data_content' );

class Nexter_Settings_Panel {
	
	/**
     * Option key, and option page slug
     */
    private $key = 'nexter_settings_opts';
	
	/**
     * Array of meta boxes/fields
     * @var array
     */
    protected $option_metabox = array();
    
	/**
     * Setting Name/Title
     * @var string
     */
    protected $setting_name = '';

	/**
     * Array of recaptch version
     * @var string
     */

	protected $nxtrecpVer,$nxtrecpForm,$recaptheme;

	/**
     * Constructor
     * @since 1.0.0
     */
    public function __construct() {
		
		if(defined('NXT_PRO_EXT')){
			$options = get_option( 'nexter_white_label' );
			$this->setting_name = (!empty($options['brand_name'])) ? $options['brand_name'].esc_html__(' Settings', 'nexter') : esc_html__('Nexter Settings', 'nexter');
		}else{
			$this->setting_name = esc_html__('Nexter Settings', 'nexter');
		}
		
        $this->fields = array();

		$this->nxtrecpVer  = array( 
			'v2' => [ 
				'title' => sprintf( '%s 2', __( 'Version', 'nexter' ) ) , 
				'desc' => sprintf( '%s 2', __( 'reCAPTCHA v2 is added below the form fields, where user has to check the box to verify the human authenticity and if found suspicious it prompts verification challenge.', 'nexter' ) ) , 
			],
			'invisible'	=> [
				'title' => sprintf( '%s', __( 'v2 (Invisible)', 'nexter' ) ),
				'desc' => sprintf( '%s 3', __( 'Invisible reCAPTCHA is a better version of v2, where no checkbox verification is required. It works on a score of user interaction and if found suspicious it asks to submit a challenge.', 'nexter' ) ),
			],
			'v3' => [
				'title' => sprintf( '%s 3', __( 'Version', 'nexter' ) ),
				'desc' => sprintf( '%s 3', __( 'reCAPTCHA v3 is a user-friendly spam protection. It verifies the visitor on his score, which is based on overall website interaction. It is shown at the bottom left-side of the website.', 'nexter' ) ),
			],
		);

		$this->nxtrecpForm  = array(
			'login_form'				=> sprintf( '%s', __( 'Login Form', 'nexter' ) ),
			'registration_form'			=> sprintf( '%s', __('Registration Form', 'nexter' ) ),
			'reset_pwd_form'			=> sprintf( '%s', __( 'Reset Password Form', 'nexter' ) ),
			'comments_form'				=> sprintf( '%s', __( 'Comments Form', 'nexter' ) ),
		);

		$this->recaptheme = array(
			'light'        => sprintf( '%s', __( 'Light', 'nexter' ) ),
			'dark'        => sprintf( '%s', __('Dark', 'nexter' ) ),
		);
    }
	
	/**
     * Initiate hooks
	 * @since 1.0.11
     */
	public function hooks() {
        add_action('admin_init', array( $this,'init' ) );
        add_action('admin_menu', array( $this, 'nxt_add_menu_page' ));
		
		add_action( 'wp_ajax_nexter_import_data_step', [ $this, 'nexter_import_data_step_2' ] );
		add_action( 'wp_ajax_nexter_import_data_step_3', [ $this, 'nexter_import_data_step_3'] );
		add_action( 'wp_ajax_nexter_import_activate_builder', [ $this, 'nexter_import_activate_builder_ajax'] );
		
		add_action( 'admin_init', [ $this, 'nxt_customizer_export_data' ] );
		add_action( 'admin_init', [ $this, 'nxt_customizer_import_data' ] );

		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'wp_ajax_nexter_extra_ext_active', [ $this, 'nexter_extra_ext_active_ajax'] );
			add_action( 'wp_ajax_nexter_extra_ext_deactivate', [ $this, 'nexter_extra_ext_deactivate_ajax'] );
			add_action( 'wp_ajax_nexter_ext_wp_replace_url_settings', [ $this, 'nexter_ext_wp_replace_url_settings_ajax'] );
			add_action( 'wp_ajax_nexter_ext_wp_duplicate_post_settings', [ $this, 'nexter_ext_wp_duplicate_post_settings_ajax'] );
			add_action( 'wp_ajax_nexter_ext_save_data', [ $this, 'nexter_ext_save_data_ajax']);
			add_action( 'wp_ajax_nexter_ext_google_recaptcha', [ $this, 'nexter_ext_google_recaptcha'] );
		}

		// Add Extra attr to script tag
		add_filter( 'script_loader_tag', [ $this,'nxt_async_attribute' ], 10, 2 );

	}
	
	/*
	 * Save Nexter Extension Data
	 * @since 1.1.0
	 */
	public function nexter_ext_save_data_ajax(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		
		$ext = ( isset( $_POST['extension_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['extension_type'] ) ) : '';
		$fonts = ( isset( $_POST['fonts'] ) ) ? wp_unslash( $_POST['fonts'] ) : '';
		$adminHide = ( isset( $_POST['adminHide'] ) ) ? wp_unslash( $_POST['adminHide'] ) : '';
		$recapData = ( isset( $_POST['recapData'] ) ) ? wp_unslash( $_POST['recapData'] ) : '';
		$wpDisableSet = ( isset( $_POST['wpDisableSet'] ) ) ? wp_unslash( $_POST['wpDisableSet'] ) : '';
		$wpEmailNotiSet = ( isset( $_POST['wpEmailNotiSet'] ) ) ? wp_unslash( $_POST['wpEmailNotiSet'] ) : '';
		$wpLoginWL = ( isset( $_POST['wpLoginWL'] ) ) ? wp_unslash( $_POST['wpLoginWL'] ) : '';
		$performance = ( isset( $_POST['advanceperfo'] ) ) ? wp_unslash( $_POST['advanceperfo'] ) : '';
		$commdata = ( isset( $_POST['discomment'] ) ) ? wp_unslash( $_POST['discomment'] ) : '';
		$wpDupPostSet = ( isset( $_POST['wpDupPostSet'] ) ) ? wp_unslash( $_POST['wpDupPostSet'] ) : '';
		$wpWLSet = ( isset( $_POST['wpWLSet'] ) ) ? wp_unslash( $_POST['wpWLSet'] ) : '';
		$securData = ( isset( $_POST['securData'] ) ) ? wp_unslash( $_POST['securData'] ) : '';
		$nxtctmLogin = ( isset( $_POST['nxtctmLogin'] ) ) ? wp_unslash( $_POST['nxtctmLogin'] ) : '';
		$image_size = ( isset( $_POST['image_size'] ) ) ? wp_unslash( $_POST['image_size'] ) : '';
		$new_custom_image_size = ( isset( $_POST['new_custom_size'] ) ) ? wp_unslash( $_POST['new_custom_size'] ) : '';
		$new_custom_image_size = (array)json_decode($new_custom_image_size);
		$ele_icons = ( isset( $_POST['ele_icons'] ) ) ? wp_unslash( $_POST['ele_icons'] ) : '';
        $disabled_image_sizes = get_option('nexter_disabled_images');

        if(!empty($ext) && $ext==='disabled-image-sizes'){
	        //Convert $image_size to Array
	        $image_size = explode(",",$image_size);
            update_option('nexter_disabled_images',$image_size);
	        wp_send_json_success();
        }else if(!empty($ext) && $ext==='disable-elementor-icons'){
	        $ele_icons = explode(",",$ele_icons);
            update_option('nexter_elementor_icons',$ele_icons);
	        wp_send_json_success();
        }else if(!empty($ext) && $ext ==='nexter-custom-image-sizes'){
            $all_custom_image_sized = get_option('nexter_custom_image_sizes',array());
            if(isset($all_custom_image_sized[$new_custom_image_size['name']])){
                wp_send_json_error();
            }
            $all_custom_image_sized[$new_custom_image_size['name']] = $new_custom_image_size;
            
	        $all_custom_image_sized2 = get_option('nexter_custom_image_sizes',array());
            if(update_option('nexter_custom_image_sizes', $all_custom_image_sized)){
	            wp_send_json_success(
		            array(
			            'content'	=> $new_custom_image_size,
		            )
                );

            } else{
	            wp_send_json_error();
            }
	        //Todo Logic to store Newly created Image Size
        }
		$option_page = 'nexter_extra_ext_options';
		$get_option = get_option($option_page);

		$perforoption = 'nexter_site_performance';
		$getperoption = get_option($perforoption);

		$secr_opt = 'nexter_site_security';
		$getSecopt = get_option($secr_opt);

		$wlOption = 'nexter_white_label';
		$get_wl_option = get_option($wlOption);

		if( !empty( $ext ) && $ext==='local-google-font' && !empty($fonts)){
			if( !empty( $get_option ) && isset($get_option[ $ext ]) ){
				$get_option[ $ext ]['values'] = json_decode($fonts);
				update_option( $option_page, $get_option );
				Nexter_Font_Families_Listing::get_local_google_font_data();
			}
			wp_send_json_success();
		}else if(!empty( $ext ) && $ext==='custom-upload-font' && !empty($fonts)){
			if( !empty( $get_option ) && isset($get_option[ $ext ]) ){
				$get_option[ $ext ]['values'] = json_decode($fonts, true);
				update_option( $option_page, $get_option );
			}
			wp_send_json_success();
		}else if(!empty( $ext ) && $ext==='disable-admin-setting' && !empty($adminHide)){
			if( !empty( $get_option ) && isset($get_option[ $ext ]) ){
				$get_option[ $ext ]['values'] = json_decode($adminHide);
				update_option( $option_page, $get_option );
			}
			wp_send_json_success();
		}else if( !empty( $ext ) && $ext==='google-recaptcha' && !empty($recapData)){
			if( !empty( $get_option ) && isset($get_option[ $ext ]) ){
				$get_option[ $ext ]['values'] = json_decode($recapData, true);
				update_option( $option_page, $get_option );
			}
			wp_send_json_success();
		}else if(!empty( $ext ) && $ext==='wp-login-white-label' && !empty($wpLoginWL)){
			if( !empty( $get_option ) && isset($get_option[ $ext ]) ){
				$wpLoginDE = json_decode($wpLoginWL, true);
				$get_option[ $ext ]['values'] = $wpLoginDE;
				$get_option[ $ext ]['css'] = Nexter_Ext_Wp_Login_White_Label::nxtWLCSSGenerate($wpLoginDE);
				update_option( $option_page, $get_option );
			}
			wp_send_json_success();
		}else if( !empty( $ext ) && ( $ext==='advance-performance' && !empty($performance) ) || ($ext==='disable-comments' && !empty($commdata) ) ){
			$advanceData =  json_decode($performance);
			$disableComm = (array) json_decode($commdata);
			
			if( False === $getperoption ){	
				if(!empty($advanceData) ){
					add_option($perforoption,$advanceData);
				}else{
					add_option($perforoption,$disableComm);
				}
			}else{
				$get_option = get_option($perforoption);
				if(!empty($get_option)){
					if( $ext==='advance-performance'){
						$old_comment = [];
						$old_comment['disable_comments'] = (isset($get_option['disable_comments']) ? $get_option['disable_comments'] : '');
						$old_comment['disble_custom_post_comments'] = (isset($get_option['disble_custom_post_comments']) ? $get_option['disble_custom_post_comments'] : [] );

						$new = array_merge($old_comment,$advanceData);
					}else if($ext==='disable-comments'){
						if(isset($get_option['disable_comments'])){
							unset($get_option['disable_comments']);
						}
						if(isset($get_option['disable_comments'])){
							unset($get_option['disble_custom_post_comments']);
						}
						$new = array_merge($get_option,$disableComm);
					}
					update_option( $perforoption, $new );
				}
			}
			wp_send_json_success();
		}else if( !empty( $ext ) && ( $ext==='advance-security' && !empty($securData) ) || ( $ext==='custom-login' && !empty($nxtctmLogin) ) || ( $ext==='wp-right-click-disable' && !empty($wpDisableSet) ) || ($ext==='email-login-notification' && !empty($wpEmailNotiSet) ) || $ext==='2-fac-authentication' ){

			$securData = (array) json_decode($securData);
			$nxtctmLogin = (array) json_decode($nxtctmLogin);
			$disrightclick = (array) json_decode($wpDisableSet,true);
			$emailNotiSet = (array) json_decode($wpEmailNotiSet);
			
			if( False === $getSecopt ){	
				if(!empty($securData) ){
					add_option($secr_opt,$securData);
				}else if(!empty($nxtctmLogin)){
					if(isset($nxtctmLogin['custom_login_url']) && !empty($nxtctmLogin['custom_login_url'])){
						$nxtctmLogin['custom_login_url'] = sanitize_key($nxtctmLogin['custom_login_url']);
					}
					add_option($secr_opt,$nxtctmLogin);
				}else if(!empty($disrightclick)){
					
					$disValue[ $ext ]['values'] = $disrightclick;
					$disValue[ $ext ]['css'] = Nexter_Ext_Right_Click_Disable::nxtrClickCSSGenerate($disrightclick);
					add_option($secr_opt,$disValue);
				}else if(!empty($emailNotiSet)){
					$emailVal[ $ext ]['values'] = $emailNotiSet;
					$emailVal[ $ext ]['switch'] = true;
					update_option( $secr_opt, $emailVal );
				}
			}else{
				$get_option = get_option($secr_opt);
				if(!empty($get_option)){

					if($ext==='advance-security'){

						if( false !== array_search('disable_xml_rpc', $get_option)){
							unset($get_option[array_search('disable_xml_rpc', $get_option)]);
						}
						if( false !== array_search('disable_wp_version', $get_option)){
							unset($get_option[array_search('disable_wp_version', $get_option)]);
						}
						if( false !== array_search('disable_rest_api_links', $get_option)){
							unset($get_option[array_search('disable_rest_api_links', $get_option)]);
						}
						if(false !== array_search('disable_file_editor', $get_option)){
							unset($get_option[array_search('disable_file_editor' , $get_option)]);
						}
						if(false !== array_search('disable_wordpress_application_password', $get_option)){
							unset($get_option[array_search('disable_wordpress_application_password' , $get_option)]);
						}
						if(false !== array_search('redirect_user_enumeration', $get_option)){
							unset($get_option[array_search('redirect_user_enumeration' , $get_option)]);
						}
						if(false !== array_search('remove_meta_generator', $get_option)){
							unset($get_option[array_search('remove_meta_generator' , $get_option)]);
						}
						if(false !== array_search('remove_css_version', $get_option)){
							unset($get_option[array_search('remove_css_version' , $get_option)]);
						}
						if(false !== array_search('remove_js_version', $get_option)){
							unset($get_option[array_search('remove_js_version' , $get_option)]);
						}
						if(false !== array_search('hide_wp_include_folder', $get_option)){
							unset($get_option[array_search('hide_wp_include_folder' , $get_option)]);
						}
						if(false !== array_search('disable_rest_api', $get_option)){
							unset($get_option[array_search('disable_rest_api' , $get_option)]);
						}
						if(false !== array_search('secure_cookies', $get_option)){
							unset($get_option[array_search('secure_cookies' , $get_option)]);
						}
						if(false !== array_search('iframe_security', $get_option)){
							unset($get_option[array_search('iframe_security' , $get_option)]);
						}
						if(false !== array_search('xss_protection', $get_option)){
							unset($get_option[array_search('xss_protection' , $get_option)]);
						}
						
						$newArr = array_merge($get_option,$securData);

					}else if($ext==='custom-login'){
						if(isset($get_option['custom_login_url'])){
							unset($get_option['custom_login_url']);
						}
						if(isset($get_option['disable_login_url_behavior'])){
							unset($get_option['disable_login_url_behavior']);
						}
						if(isset($get_option['login_page_message'])){
							unset($get_option['login_page_message']);
						}
						if(isset($nxtctmLogin['custom_login_url']) && !empty($nxtctmLogin['custom_login_url'])){
                            $nxtctmLogin['custom_login_url'] = sanitize_key($nxtctmLogin['custom_login_url']);
                        }
						$newArr = array_merge($get_option,$nxtctmLogin);
					}else if( $ext==='wp-right-click-disable' ){
						if(isset($get_option[ $ext ]['values']) && !empty($get_option[ $ext ]['values']) ){
							unset($get_option[ $ext ]['values']);
						}
						$newdata[ $ext ]['values'] =  $disrightclick;
						$newdata[ $ext ]['css'] = Nexter_Ext_Right_Click_Disable::nxtrClickCSSGenerate($disrightclick);
						$newArr = array_merge($get_option,$newdata);
					}else if($ext==='email-login-notification'){
						// if(isset($get_option[ $ext ]['values']) && !empty($get_option[ $ext ]['values']) ){
						// 	unset($get_option[ $ext ]['values']);
						// }
						$newdata[ $ext ]['values'] =  $emailNotiSet;
						$newdata[ $ext ]['switch'] =  true;
						$newArr = array_merge($get_option,$newdata);
					}
                    else if($ext === '2-fac-authentication'){
	                    $allowed_2fa_roles = ( isset( $_POST['allowed_2fa_roles'] ) ) ? wp_unslash( $_POST['allowed_2fa_roles'] ) : '';
	                    $allowed_2fa_roles = json_decode($allowed_2fa_roles, true);
	                    $email_customisation = array();
	                    $email_customisation['subject'] = ( isset( $_POST["customEmailSubject"] ) ) ? sanitize_text_field( wp_unslash( $_POST['customEmailSubject'] ) ) : '';
	                    $email_customisation['body'] = ( isset( $_POST["customEmailBody"] ) ) ? sanitize_textarea_field( ( $_POST['customEmailBody'] ) ) : '';
                        $temp_roles[$ext]['values']['allowed_2fa_roles'] = $allowed_2fa_roles;
                        $temp_roles[$ext]['values']['email_customisations'] = $email_customisation;
                        $temp_roles[$ext]['switch'] = true;
                        $newArr = array_merge($get_option,$temp_roles);
                    }
                    //todo improve code quality
					$temp = update_option( $secr_opt, $newArr );
				}
			}
			wp_send_json_success();

		}else if( !empty( $ext ) && $ext==='wp-duplicate-post' && !empty($wpDupPostSet)){
			if( !empty( $get_option ) && isset($get_option[ $ext ]) ){
				$get_option[ $ext ]['values'] = (array) json_decode($wpDupPostSet);
				update_option( $option_page, $get_option );
			}
			wp_send_json_success();
		}else if(!empty( $ext ) && $ext==='white-label' && !empty($wpWLSet)){
			$whiteLabelData =  (array) json_decode($wpWLSet);
			if( !empty($whiteLabelData) && isset($whiteLabelData['theme_screenshot_id']) && !empty($whiteLabelData['theme_screenshot_id']) ){
				$fileName = basename(get_attached_file($whiteLabelData['theme_screenshot_id']));
				if(!empty($fileName)){
					$filetype = wp_check_filetype($fileName);
					if(!empty($filetype) && isset($filetype['type'])){
						if(!(strpos($filetype['type'], 'image') !== false)) {
							$whiteLabelData['theme_screenshot'] = '';
							$whiteLabelData['theme_screenshot_id'] = '';
						}
					}
				}
			}
			if( !empty($whiteLabelData) && isset($whiteLabelData['theme_logo_id']) && !empty($whiteLabelData['theme_logo_id']) ){
				$fileName = basename(get_attached_file($whiteLabelData['theme_logo_id']));
				if(!empty($fileName)){
					$filetype = wp_check_filetype($fileName);
					if(!empty($filetype) && isset($filetype['type'])){
						if(!(strpos($filetype['type'], 'image') !== false)) {
							$whiteLabelData['theme_logo'] = '';
							$whiteLabelData['theme_logo_id'] = '';
						}
					}
				}
			}
			if( False === $get_wl_option ){
				add_option($wlOption,$whiteLabelData);
			}else{
				update_option( $wlOption, $whiteLabelData );
			}
			wp_send_json_success();
		}

		wp_send_json_error();
	}

	/*
	 * Nexter WP Replace URL Settings
	 * @since 1.1.0
	 */
	public function nexter_ext_wp_replace_url_settings_ajax(){

		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_success(
				array(
					'content'	=> __( 'Insufficient permissions.', 'nexter' ),
				)
			);
		}
		global $wpdb;
		$tables = '';
		if (function_exists('is_multisite') && is_multisite()) {
			if(is_main_site()){
				$tables 	= $wpdb->get_col('SHOW TABLES');
			}else{
				$blog_id 	= get_current_blog_id();
				$tables 	= $wpdb->get_col('SHOW TABLES LIKE "'.$wpdb->base_prefix.absint( $blog_id ).'\_%"');
			}
		}else{
			$tables = $wpdb->get_col('SHOW TABLES');
		}

		// $sizes 	= array();
		$sizes 	= [];
		$tablesNN	= $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A );
		if ( is_array( $tablesNN ) && ! empty( $tablesNN ) ) {
			foreach ( $tablesNN as $table ) {
				$size = round( $table['Data_length'] / 1024 / 1024, 2 );
				$sizes[$table['Name']] = sprintf( __( '(%s MB)', 'nexter' ), $size );
			}
		}

		$output = '';
		$output .= '<div class="nxt-ext-modal-content">';
			$output .= '<div class="nxt-modal-title-wrap">';
				$output .= '<div class="nxt-modal-title">'.esc_html__( 'Replace URL & Text', 'nexter' ).'</div>';
				//$output .= '<div class="nxt-modal-desc">'.esc_html__( 'This option gives you to replace media url and text. ', 'nexter' ).'</div>';
			$output .= '</div>';

			$output .= '<div class="nxt-replace-loader"></div>';
			
			$output .= '<div class="nxt-replace-url-wrap">';
				$output .= '<div class="nxt-replace-url-note"><strong>'.esc_html__('Important:', 'nexter').'</strong>'.esc_html__( ' We strongly recommend that you ', 'nexter' ).'<a href="https://wordpress.org/support/article/wordpress-backups/" target="_blank" rel="noopener noreferrer">'.esc_html__('backup your database', 'nexter').'</a>'.esc_html__( ' before using Replace URL & Text.', 'nexter' ).'</div>';

				$output .= '<div class="nxt-replace-url-table-wrap">';
				$output .= '<span class="nxt-r-table-title">'.esc_html__('Select Tables', 'nexter').'</span>';
					$output .= '<select class="nxt-replace-url-table" id="nxt-replace-url-table" name="dbtable[]" multiple="multiple" style="width: 100%; max-width: 100%;">';
					foreach($tables as $tab){
						$table_size = isset( $sizes[$tab] ) ? $sizes[$tab] : '';
						$output .= '<option value="'.esc_attr($tab).'" selected>'.esc_html($tab).' '.esc_html($table_size).'</option>';
					}
					$output .= '</select>';
				$output .= '</div>';
				
				$output .= '<label class="nxt-old-title">'.esc_html__('Old URL/Text', 'nexter').'<input type="text" class="nxt-old-url" placeholder="http://oldurl.com or old text"/></label>';
				$output .= '<label class="nxt-new-title">'.esc_html__('New URL/Text', 'nexter').'<input type="text" class="nxt-new-url" placeholder="http://newurl.com or new text"/></label>';
				$output .= '<div class="nxt-replace-case-wrap"><input type="checkbox" id="case_sensitive_toggle" name="case_sensitive_toggle" value="true"/><label for="case_sensitive_toggle"></label>'.esc_html__('Case Sensitive', 'nexter').'</div>';

				$output .= '<div class="nxt-replace-guid-wrap"><input type="checkbox" id="guid_toggle" name="guid_toggle" value="true"/><label for="case_sensitive_toggle"></label>'.esc_html__('Replace GUIDs', 'nexter').'<a href="https://wordpress.org/documentation/article/changing-the-site-url/#important-guid-note" target="_blank"><img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="wp-replace-url-guid"></a></div>';
				
				$output .='<div class="nxt-replace-search-limit">'.esc_html__('Search Limit', 'nexter').'<input id="search-limit" type="number" min="500" max="50000" step="100" value="20000"/><span class="nxt-desc-icon"><img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="wp-replace-url-limit"> <div class="nxt-tooltip">'.esc_html__('Decrease the value if you face any server timeout.', 'nexter').'</div></span></div>';
				
				$output .= '<div class="nxt-replace-note-wrap"><p class="nxt-replace-url-notice"></p></div>';
				
				$output .= '<div class="nxt-replace-url-btn-wrap">';
					$output .= '<button class="nxt-replace-url-btn"><span>'.esc_html__('Replace', 'nexter').'</span></button>';
					$output .= '<button class="nxt-replace-url-confirm-btn"><span>'.esc_html__('Confirm', 'nexter').'</span></button>';
				$output .= '</div>';

			$output .= '</div>';
		$output .= '</div>';
					
		wp_send_json_success(
			array(
				'content'	=> $output,
			)
		);
	}


	/*
	 * Nexter WP Duplicate Post Settings
	 * @since 1.1.0
	 */
	public function nexter_ext_wp_duplicate_post_settings_ajax(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		$output = '';

		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_success(
				array(
					'content'	=> __( 'Insufficient permissions.', 'nexter' ),
				)
			);
		}

		$ext = ( isset( $_POST['extension_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['extension_type'] ) ) : '';
		$extension_option = get_option( 'nexter_extra_ext_options' );
		if( !empty( $ext ) && $ext == 'wp-duplicate-post' ){

			$all_users=$current_author=$original_date='checked';

			$original_user=$original_author=$current_date='';

			$sSame=$sDraft=$sPublised=$sPending=$sPrivate='';

			$postfixText= 'Copy'; $slugText='copy';
			$wpDupPostSet = (!empty($extension_option) && isset($extension_option['wp-duplicate-post']) && !empty($extension_option['wp-duplicate-post']['values']) ) ? $extension_option['wp-duplicate-post']['values'] : '';

			if(!empty($wpDupPostSet)){
				if(!empty($wpDupPostSet['nxt-duppost-access']) && $wpDupPostSet['nxt-duppost-access']=='original_user'){
					$original_user = 'checked';
					$all_users ='';
				}
				if(!empty($wpDupPostSet['nxt-duppost-author']) && $wpDupPostSet['nxt-duppost-author']=='original_author'){
					$original_author = 'checked';
					$current_author ='';
				}
				if(!empty($wpDupPostSet['nxt-duppost-date']) && $wpDupPostSet['nxt-duppost-date']=='current_date'){
					$current_date = 'checked';
					$original_date ='';
				}
				
				if(!empty($wpDupPostSet['nxt-duppost-status'])){
					if($wpDupPostSet['nxt-duppost-status']=='same'){
						$sSame='selected';
					}else if($wpDupPostSet['nxt-duppost-status']=='draft'){
						$sDraft='selected';
					}else if($wpDupPostSet['nxt-duppost-status']=='publish'){
						$sPublised='selected';
					}else if($wpDupPostSet['nxt-duppost-status']=='pending'){
						$sPending='selected';
					}else if($wpDupPostSet['nxt-duppost-status']=='private'){
						$sPrivate='selected';
					}
				}

				$postfixText = (!empty($wpDupPostSet['nxt-duplicate-postfix'])) ? $wpDupPostSet['nxt-duplicate-postfix'] : '';
				$slugText = (!empty($wpDupPostSet['nxt-duplicate-slug'])) ? $wpDupPostSet['nxt-duplicate-slug'] : '';

			}
			
			$output .= '<div class="nxt-ext-modal-content">';
				$output .= '<div class="nxt-modal-title-wrap">';
					$output .= '<div class="nxt-modal-title">'.esc_html__( 'Duplicate Post', 'nexter' ).'</div>';
					//$output .= '<div class="nxt-modal-desc">'.esc_html__( 'This option gives you to duplicate any post types including taxonomies & custom fields.', 'nexter' ).'</div>';
				$output .= '</div>';

				
				$output .='<div class="nxt-dup-post-row">';
					$output .='<div class="nxt-dup-post-column">';
						$output .='<div class="nxt-wp-duplicate-post-wrap" style="flex-direction: row; align-items: center;">
							<span class="nxt-wp-dppost-set-title">'.esc_html__('Who Can Duplicate','nexter').'</span>
							<div class="nxt-duppost-access">
								<input type="radio" value="all_users" name="nxt-who-dp" id="dp-all-users" '.$all_users.'/>
								<label for="dp-all-users">All Users</label>
								<input type="radio" value="original_user" name="nxt-who-dp" id="dp-original-user" '.$original_user.'/>
								<label for="dp-original-user">Original Author</label>
							</div>
						</div>';

						$output .='<div class="nxt-wp-duplicate-post-wrap" style="flex-direction: row; align-items: center;">
							<span class="nxt-wp-dppost-set-title">'.esc_html__('Post Author','nexter').'</span>
							<div class="nxt-duppost-author">
								<input type="radio" value="current_author" name="nxt-author-dp" id="dp-current-author" '.$current_author.'/>
								<label for="dp-current-author">Current User</label>
								<input type="radio" value="original_author" name="nxt-author-dp" id="dp-original-author" '.$original_author.'/>
								<label for="dp-original-author">Original Author</label>
							</div>
						</div>';

						$output .='<div class="nxt-wp-duplicate-post-wrap" style="flex-direction: row; align-items: center;">
							<span class="nxt-wp-dppost-set-title">'.esc_html__('Post Date','nexter').'</span>
							<div class="nxt-duppost-date">
								<input type="radio" value="original_date" name="nxt-date-dp" id="dp-original-date" '.$original_date.'/>
								<label for="dp-original-date">Duplicate Time</label>
								<input type="radio" value="current_date" name="nxt-date-dp" id="dp-current-date" '.$current_date.'/>
								<label for="dp-current-date">Current Time</label>
							</div>
						</div>';

						$output .='<div class="nxt-wp-duplicate-post-wrap" style="flex-direction: row; align-items: center; ">
							<span class="nxt-wp-dppost-set-title nxt-dis-style-title">'.esc_html__('Post Status','nexter').'</span>
							<select class="duplicate-post-status" style="margin-left: 10px">
								<option value="same" '.$sSame.'>Same as Original</option>
								<option value="draft" '.$sDraft.'>Draft</option>
								<option value="publish" '.$sPublised.'>Published</option>
								<option value="pending" '.$sPending.'>Pending</option>
								<option value="private" '.$sPrivate.'>Private</option>
							</select>
						</div>';

						$output .='<div class="nxt-wp-duplicate-post-wrap" style="flex-direction: row; align-items: center; ">
							<span class="nxt-wp-dppost-set-title">'.esc_html__('Postfix Text','nexter').'</span>
							<input class="nxt-duplicate-postfix" type="text" value="'.esc_attr($postfixText).'"/>
						</div>';
						$output .='<div class="nxt-wp-duplicate-post-wrap" style="flex-direction: row; align-items: center; ">
							<span class="nxt-wp-dppost-set-title">'.esc_html__('Slug Text','nexter').'</span>
							<input class="nxt-duplicate-slug" type="text" value="'.esc_attr($slugText).'"/>
						</div>';


					$output .= '</div>';
				$output .= '</div>';

				$output .= '<button type="button" class="nxt-duplicate-post-set"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#fff" stroke-width=".781" stroke-linejoin="round" ><path d="M15.833 17.5H4.167c-.442 0-.866-.176-1.179-.488s-.488-.736-.488-1.179V4.167c0-.442.176-.866.488-1.179S3.725 2.5 4.167 2.5h9.167L17.5 6.667v9.167c0 .442-.176.866-.488 1.179s-.736.488-1.179.488z"/><path d="M14.167 17.5v-6.667H5.833V17.5m0-15v4.167H12.5" stroke-linecap="round"/></svg>'.esc_html( 'Save', 'nexter' ).'</button>';
			$output .= '</div>';
						
			wp_send_json_success(
				array(
					'content'	=> $output,
				)
			);

		}
	}



	/*
	 * Nexter Google Recaptcha
	 * @since 1.1.0
	 */
	public function nexter_ext_google_recaptcha() {
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_success(
				array(
					'content'	=> __( 'Insufficient permissions.', 'nexter' ),
				)
			);
		}
		$ext = ( isset( $_POST['extension_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['extension_type'] ) ) : '';
		$option = get_option( 'nexter_extra_ext_options' );
		$reoption = ( isset( $option['google-recaptcha'] ) && !empty($option['google-recaptcha']) && isset( $option['google-recaptcha']['values'] ) &&  !empty( $option['google-recaptcha']['values'] ) ) ? $option['google-recaptcha']['values'] : '' ;

		$output = '';
		if( !empty( $ext ) && $ext == 'google-recaptcha' ){
			$output .= '<div class="nxt-ext-modal-content">';
				$output .= '<div class="nxt-modal-title-wrap">';
					$output .= '<div class="nxt-modal-title">'.esc_html__( 'GOOGLE reCAPTCHA', 'nexter' ).'</div>';
					//$output .= '<div class="nxt-modal-desc">'.esc_html__( 'Serve your chosen Google Fonts from your own web server. This will increase the loading speed and makes sure your website complies with the privacy regulations.', 'nexter' ).'</div>';
				$output .= '</div>';
				$output .= '<div class="nxt-recaptch-wrap">';
					$output .= '<div class="nxt-recaptch-inner">';
						$output .= '<label class="upload-font-label">'.esc_html__( 'reCAPTCHA Version', 'nexter' ).'</label>';
						$output .= '<ul class="nxt-check-list">';
							foreach ( $this->nxtrecpVer as $version => $version_name ) {
								$output .= '<li>';
									$output .= '<input type="radio" id="'.$version.'" name="nexter_recaptcha_version" value="'.$version.'" '.( isset($reoption['version']) && !empty($reoption['version']) &&  $reoption['version'] == $version ? 'checked' : '' ).'  >';
									$output .= '<label for="'.$version.'">';
										$output .= $version_name['title'] ;
										$output .= '<span class="nxt-desc-icon" >';
											$output .= '<img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="'.esc_attr($version_name['title']).'" /> ';
											$output .= '<div class="nxt-tooltip bottom">'.wp_kses_post($version_name['desc']).'</div>';
										$output .= '</span>';
									$output .= '</label>';
								$output .= '</li>';
								
							}
						$output .= '</ul>'; 
					$output .= '</div>';
					$output .= '<div class="nxt-recaptch-inner nxt-spce-bet">';
						
						$output .= '<div class="nxt-recaptch-field">';
						$output .= '<label class="upload-font-label">'.esc_html__( 'Site Key', 'nexter' );
							$output .= '<span class="nxt-desc-icon" >';
								$output .= '<img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="'.esc_html__( 'Site Key', 'nexter' ).'" /> ';
								$output .= '<div class="nxt-tooltip right">';
                                    $output .= esc_html__( 'Copy your Site Key from your Google reCAPTCHA Account. ' , 'nexter' );
                                    $output .= sprintf('<a href="'.esc_url('https://www.google.com/recaptcha/admin#list').'" target="_blank" rel="noopener noreferrer" style="color : #fff;" >'.esc_html__('Get Keys' , 'nexter').'</a>');
                                $output .= '</div>';
							$output .= '</span>';
						$output .= '</label>';
						$output .= '<span class="dashicons dashicons-yes nxt-verify-icon '.( !isset($reoption['keyverify']) || true != $reoption['keyverify'] ? 'hidden' : '' ).' " ></span>';
							$output .= '<input type="text" class="nxt-recap-input" name="nexter_re_public_key" placeholder="'.esc_html('Please Enter Site Key','nexter').'"  value="'.( isset($reoption['siteKey']) && !empty($reoption['siteKey']) ? $reoption['siteKey'] : '' ).'" />';
						$output .= '</div>'; 

						$output .= '<div class="nxt-recaptch-field">';
							$output .= '<label class="upload-font-label">'.esc_html__( 'Secret Key', 'nexter' );
							$output .= '<span class="nxt-desc-icon" >';
								$output .= '<img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="'.esc_html__( 'Site Key', 'nexter' ).'" /> ';
								$output .= '<div class="nxt-tooltip right">';
                                    $output .= esc_html__( 'Copy your Secret Key from your Google reCAPTCHA Account. ' , 'nexter' );
                                    $output .= sprintf('<a href="'.esc_url('https://www.google.com/recaptcha/admin#list').'" target="_blank" rel="noopener noreferrer" style="color : #fff;" >'.esc_html__('Get Keys' , 'nexter').'</a>');
                                $output .= '</div>';
							$output .= '</span>';
							$output .= '</label>'; 
							$output .= '<span class="dashicons dashicons-yes nxt-verify-icon '.( !isset($reoption['keyverify']) || true != $reoption['keyverify'] ? 'hidden' : '' ).' " ></span>';
							$output .= '<input type="text" class="nxt-recap-input" name="nexter_re_private_key" placeholder="'.esc_html('Please Enter Secret Key','nexter').'" value="'.( isset($reoption['secretKey']) && !empty($reoption['secretKey']) ? $reoption['secretKey'] : '' ).'" />';
						$output .= '</div>';
					$output .= '</div>'; 

					if( ( isset($reoption['siteKey']) && !empty($reoption['siteKey']) ) && ( isset($reoption['secretKey']) && !empty($reoption['secretKey']) ) ){
						$output .= '<div class="nxt-recaptch-inner">';
							$output .= '<div class="nxt-recaptch-field">';
								$output .= '<button class="nxt-test-recaptch" id="nxtrecapver">';
									$output .= '<svg width="11" height="14" viewBox="0 0 11 14" stroke="white" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.25 6.56697C10.5833 6.75942 10.5833 7.24054 10.25 7.43299L1.25 12.6291C0.916666 12.8216 0.499999 12.581 0.499999 12.1961L0.5 1.80383C0.5 1.41893 0.916666 1.17836 1.25 1.37082L10.25 6.56697Z" /></svg>';
 									$output .= esc_html__( 'Test reCAPTCHA', 'nexter' );
								$output .=  '</button>';
								$output .= '<div class="nxtcptch-test-results"></div>';
							$output .= '</div>'; 
						$output .= '</div>';
					}

					$output .= '<div class="nxt-recaptch-inner">';
						$output .= '<label class="upload-font-label">'.esc_html__( 'Enable reCAPTCHA for', 'nexter' ).'</label>';
						$output .= '<ul class="nxt-check-list">';
							foreach ( $this->nxtrecpForm as $form => $form_name ) {
								$output .= '<li>';
									$output .= '<input type="checkbox" id="'.esc_attr($form).'" name="nexter_recaptcha_enable" value="'.esc_attr($form).'" '.( isset($reoption['formType']) && !empty($reoption['formType']) && in_array($form,$reoption['formType'])  ? 'checked' : '' ).' >';
									$output .= '<label for="'.esc_attr($form).'">'.esc_html( $form_name).'</label>';
								$output .= '</li>';
							}
						$output .= '</ul>'; 
					$output .= '</div>';
						
					if( isset($reoption['version']) && !empty($reoption['version']) &&  $reoption['version'] == 'v2' ){
						$output .= '<div class="nxt-recaptch-inner">';
							$output .= '<label class="upload-font-label">'.esc_html__( 'reCaptcha Theme', 'nexter' ).'</label>';
							$output .= '<ul class="nxt-check-list">';
								foreach ( $this->recaptheme as $theme => $theme_name ) {
									$output .= '<li>';
										$output .= '<input type="radio" id="'.esc_attr($theme).'" name="nexter_recaptcha_theme" value="'.esc_attr($theme).'" '.( isset($reoption['recapTheme']) && !empty($reoption['recapTheme']) &&  $reoption['recapTheme'] == $theme ? 'checked' : ( isset($reoption['recapTheme']) && empty($reoption['recapTheme']) && $theme == 'light' ? 'checked' : ''  ) ).' >';
										$output .= '<label for="'.esc_attr($theme).'">'.esc_html( $theme_name).'</label>';
									$output .= '</li>';
									
								}
							$output .= '</ul>'; 
						$output .= '</div>';
					}

					if( isset( $reoption['version'] ) && !empty( $reoption['version'] )  && ($reoption['version'] == 'v3' || $reoption['version'] == 'invisible' )){
						$output .= '<div class="nxt-recaptch-inner">';
							$output .= '<div class="nxt-recaptch-field">';
								$output .= '<label class="nxt-hide-recap">';
									$output .= '<input type="checkbox" name="nexter_recaptcha_invisi" value="1"'.( isset($reoption['invisi']) && !empty($reoption['invisi'])  ? 'checked' : '' ).' >';
									$output .= '<span class="nxt-recap-desc">';
										$output .= '<label class="upload-font-label">'.esc_html__( 'Hide reCaptcha Badge', 'nexter' ).'</label>';
										$output .= '<span>'.esc_html__( 'Enable to hide reCAPTCHA Badge for version 3 and invisible reCAPTCHA.', 'nexter' ). '</span>' ;
									$output .= '</span>';
								$output .= '</label>';
							$output .= '</div>';
						$output .= '</div>';
					}
				$output .= '</div>'; 
				$output .= '<button type="button" class="nxt-recaptcha-save"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="#fff" stroke-width=".781" stroke-linejoin="round" ><path d="M15.833 17.5H4.167c-.442 0-.866-.176-1.179-.488s-.488-.736-.488-1.179V4.167c0-.442.176-.866.488-1.179S3.725 2.5 4.167 2.5h9.167L17.5 6.667v9.167c0 .442-.176.866-.488 1.179s-.736.488-1.179.488z"/><path d="M14.167 17.5v-6.667H5.833V17.5m0-15v4.167H12.5" stroke-linecap="round"/></svg>'.esc_html( 'Save', 'nexter' ).'</button>';
			$output .= '</div>'; 

			wp_send_json_success(
				array(
					'content'	=> $output,
				)
			);
		}
		wp_send_json_error();
	}

	/**
	 * Add the "async" attribute to our registered script.
	*/
	public function nxt_async_attribute( $tag, $handle ) {
		if ( 'nexter_recaptcha_api' == $handle ) {
			$tag = str_replace( ' src', ' data-cfasync="false" async="async" defer="defer" src', $tag );
		}
		return $tag;
	}

	/*
	 * Nexter Extra Option Active Extension
	 * @since 1.1.0
	 */
	public function nexter_extra_ext_active_ajax(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 
					'content' => __( 'Insufficient permissions.', 'nexter' ),
				)
			);
		}
		$type = ( isset( $_POST['extension_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['extension_type'] ) ) : '';
		self::nxt_extra_active_deactive($type, 'active');
	}

	/*
	 * Nexter Extra Option DeActivate Extension
	 * @since 1.1.0
	 */
	public function nexter_extra_ext_deactivate_ajax(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 
					'content' => __( 'Insufficient permissions.', 'nexter' ),
				)
			);
		}
		$type = ( isset( $_POST['extension_type'] ) ) ? sanitize_text_field( wp_unslash( $_POST['extension_type'] ) ) : '';
		self::nxt_extra_active_deactive($type, 'deactive');
	}

	public static function nxt_extra_active_deactive( $data = '', $switch = ''){
		if( empty( $data ) && empty($switch) ){
			wp_send_json_error(
				array( 
					'content' => __( 'server not found..', 'nexter' ),
				)
			);
		}else if( !empty( $data ) && !empty( $switch ) ){
			$option_page = '';
			if($data=='email-login-notification'){
				$option_page = 'nexter_site_security';
			}
            if($data=='2-fac-authentication'){
                //todo
                $option_page = 'nexter_site_security';
            }else{
				$option_page = 'nexter_extra_ext_options';
			}
			if ( FALSE === get_option($option_page) ){
				$default_value = [ 
					$data => [
						'switch' => ($switch=='active') ? true : false,
					],
				];
				add_option($option_page,$default_value);
			}else{
				$get_option = get_option($option_page);
				if( !empty( $get_option ) ){
					$get_option[ $data ]['switch'] = ($switch=='active') ? true : false;
                    /**
                     * Todo
                     * This Code snippet can be impoved with json success only when it actually does
                     */
                    update_option( $option_page, $get_option );

				}
			}
			wp_send_json_success(
				array(
					'content'	=> ($switch=='active') ? __( 'Activated', 'nexter' ) : __( 'DeActivate', 'nexter' ),
				)
			);
		}
	}


	public function nexter_import_activate_builder_ajax(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		
		if ( ! current_user_can( 'install_plugins' ) || ! isset( $_POST['file'] ) || ! sanitize_text_field( wp_unslash( $_POST['file'] ) ) ) {
			wp_send_json_error(
				array(
					'success' => false,
					'content' => __( 'No plugin..', 'nexter' ),
				)
			);
		}

		$file = ( isset( $_POST['file'] ) ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : '';

		$activate = activate_plugin( $file, '', false, true );

		if ( is_wp_error( $activate ) ) {
			wp_send_json_error(
				array(
					'success'	=> false,
					'content'	=> $activate->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'success'	=> true,
				'content'	=> __( 'Activated', 'nexter' ),
			)
		);

	}
	
	public function nexter_import_data_step_2(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		
		if(current_user_can( 'install_plugins' ) && isset($_POST['builder']) && !empty($_POST['builder'])){
			$build_thumb = $build_name = $build_button = '';
			$plugin_slug = $plugin_file = '';
			$plugin_process = $plugin_status = '';
			$installed_plugins = get_plugins();
			
			if($_POST['builder'] == 'elementor'){
				$build_thumb = 'tpae';
				$build_name = 'Elementor';
				$plugin_file = 'the-plus-addons-for-elementor-page-builder/theplus_elementor_addon.php';
				$plugin_slug = 'the-plus-addons-for-elementor-page-builder';
				if(!defined("L_THEPLUS_VERSION")){
					if ( isset( $installed_plugins[ $plugin_file ] ) ) {
						$build_button = 'Activate Plugin';
						$plugin_process = 'Activating..';
						$plugin_status = 'nxt-active-builder-plugin';
					}else{
						$build_button = 'Install Plugin';
						$plugin_process = 'Installing..';
						$plugin_status = 'nxt-install-builder-plugin';
					}
				}else if(defined("L_THEPLUS_VERSION")){
					$build_button = 'Activated';
					$plugin_process = 'Activated';
					$plugin_status = 'nxt-activated-builder-plugin';
				}
			}else if($_POST['builder'] == 'gutenberg'){
				$build_thumb = 'tpag';
				$build_name = 'Gutenberg';
				$plugin_file = 'the-plus-addons-for-block-editor/the-plus-addons-for-block-editor.php';
				$plugin_slug = 'the-plus-addons-for-block-editor';
				if(!defined("TPGB_VERSION")){
					if ( isset( $installed_plugins[ $plugin_file ] ) ) {
						$build_button = 'Activate Plugin';
						$plugin_process = 'Activating..';
						$plugin_status = 'nxt-active-builder-plugin';
					}else{
						$build_button = 'Install Plugin';
						$plugin_process = 'Installing..';
						$plugin_status = 'nxt-install-builder-plugin';
					}
				}else if(defined("TPGB_VERSION")){
					$build_button = 'Activated';
					$plugin_process = 'Activated';
					$plugin_status = 'nxt-activated-builder-plugin';
				}
			}
			
			$nxt_ext_file = 'nexter-extension/nexter-extension.php';
			$nxt_build_button = $nxt_plugin_process = $nxt_plugin_status = '';
			if(!defined("NEXTER_EXT_VER")){
				if ( isset( $installed_plugins[ $nxt_ext_file ] ) ) {
					$nxt_build_button = 'Activate Plugin';
					$nxt_plugin_process = 'Activating..';
					$nxt_plugin_status = 'nxt-active-builder-plugin';
				}else{
					$nxt_build_button = 'Install Plugin';
					$nxt_plugin_process = 'Installing..';
					$nxt_plugin_status = 'nxt-install-builder-plugin';
				}
			}else if(defined("NEXTER_EXT_VER")){
				$nxt_build_button = 'Activated';
				$nxt_plugin_process = 'Activated';
				$nxt_plugin_status = 'nxt-activated-builder-plugin';
			}
			
			$output = '<div class="nxt-import-steps import-step-2 active" data-step="step-2">
				<div class="nxt-import-heading">'.esc_html__('Install & Activate Required Plugins.','nexter').'</div>
				<div class="nxt-panel-row">
					<div class="nxt-panel-col nxt-panel-col-50">
						<div class="nxt-builder-install-activate">
							<div class="builder-thumb" style="background-image:url('.esc_url(NXT_THEME_URI .'assets/images/'.$build_thumb.'.png').')"></div>
							<div class="builder-name">'.esc_html__('The Plus Addons For ','nexter').esc_html($build_name).'</div>
						</div>
						<a href="#" class="builder-install-activate-plugin '.esc_attr($plugin_status).'" data-builder="'.esc_attr($_POST['builder']).'" data-builder-process="'.esc_attr($plugin_process).'" data-slug="'.esc_attr($plugin_slug).'" data-file="'.$plugin_file.'">'.esc_html($build_button).'</a>
					</div>
					<div class="nxt-panel-col nxt-panel-col-50">
						<div class="nxt-builder-install-activate nxt-ext-plugin">
							<div class="builder-thumb" style="background-image:url('.esc_url(NXT_THEME_URI .'assets/images/nexter-ext.png').')"></div>
							<div class="builder-name">'.esc_html__('Nexter Extension','nexter').'</div>
						</div>
						<a href="#" class="builder-install-activate-plugin '.esc_attr($nxt_plugin_status).'" data-builder="nexter-extension" data-builder-process="'.esc_attr($nxt_plugin_process).'" data-slug="nexter-extension" data-file="'.$nxt_ext_file.'">'.esc_html($nxt_build_button).'</a>
					</div>
				</div>
			</div>';
			
			echo wp_json_encode( [ 'success'=> true, 'content'=> $output, 'build_status' => $build_button, 'extension_status' => $nxt_build_button] );
			exit;
		}else{
			echo wp_json_encode( [ 'success'=> false, 'content'=> '', 'build_status' => $build_button, 'extension_status' => $nxt_build_button ] );
			exit;
		}
	}
	
	public function nexter_import_data_step_3(){
		check_ajax_referer( 'nexter_admin_nonce', 'nexter_nonce' );
		
		$builder = ( isset( $_POST['builder'] ) ) ? sanitize_text_field( wp_unslash( $_POST['builder'] ) ) : '';
		
		$success = true;
		$builder_template = [];
		if(!empty($builder) && $builder=='gutenberg'){
			if(!defined("TPGB_VERSION")){
				$success = false;
			}else{
				$builder_template['template-1'] = [ 
					'thumb' => 'gutenberg-temp-1.png',
					'title' => esc_html__('Builder Template','nexter'),
					'desc' => esc_html__('Header, Footer, Single Posts Template, Archive Template...','nexter'),
					'tag' => 'Free',
				];
				$builder_template['posts'] = [ 
					'thumb' => 'gutenberg-temp-1.png',
					'title' => esc_html__('Basic Posts','nexter'),
					'desc' => esc_html__('Normal basic posts demo','nexter'),
					'tag' => 'Free',
				];
				
				$option_page = 'tpgb_normal_blocks_opts';
				$sub_option = 'enable_normal_blocks';
				$merge_opt = [ 'tp-row','tp-post-title', 'tp-post-author', 'tp-post-comment', 'tp-post-content', 'tp-post-image', 'tp-post-listing', 'tp-post-meta', 'tp-site-logo' ];
				if ( FALSE === get_option($option_page) ){
					$default_value = [ $sub_option => $merge_opt ];
					add_option($option_page,$default_value);
				}else{
					$get_option = get_option($option_page);
					if( !empty( $get_option ) && isset( $get_option[ $sub_option ] ) ){
						$get_sub_opt = $get_option[ $sub_option ];
						
						if( is_array( $get_sub_opt ) && !empty( $get_sub_opt ) ){
							$new_val = array_merge($get_sub_opt,$merge_opt);
							$update_value = array( $sub_option => $new_val);
						}else if(empty( $get_sub_opt ) ){
							$update_value = array( $sub_option => $merge_opt );
						}else{
							$update_value = array( $sub_option => $get_sub_opt);
						}
						update_option( $option_page, $update_value );
					}
				}
				
			}
		}else if(!empty($builder) && $builder=='elementor'){
			if(!defined("L_THEPLUS_VERSION")){
				$success = false;
			}else{
				$builder_template['template-1'] = [ 
					'thumb' => 'gutenberg-temp-1.png',
					'title' => esc_html__('Build Template','nexter'),
					'desc' => esc_html__('Header, Footer, Single Posts Template, Archive Template...','nexter'),
					'tag' => 'Free',
				];
				$builder_template['posts'] = [ 
					'thumb' => 'gutenberg-temp-1.png',
					'title' => esc_html__('Basic Posts','nexter'),
					'desc' => esc_html__('Normal basic posts demo','nexter'),
					'tag' => 'Free',
				];
				
				$option_page = 'theplus_options';
				$sub_option1 = 'check_elements';
				$sub_option2 = 'extras_elements';
				$merge_opt = [ 'tp_blog_listout', 'tp_navigation_menu_lite', 'tp_post_search', 'tp_post_title', 'tp_post_content', 'tp_post_featured_image', 'tp_post_meta', 'tp_post_author', 'tp_post_comment' ];
				if ( FALSE === get_option($option_page) ){
					$default_value = [ $sub_option1 => $merge_opt, $sub_option2 => '' ];
					add_option($option_page,$default_value);
				}else{
					$get_option = get_option($option_page);
					if( !empty( $get_option ) ){
					
						$old_sub_opt1 = $get_option[ $sub_option1 ];
						$old_sub_opt2 = $get_option[ $sub_option2 ];
						$update_value = [];
						if( is_array( $old_sub_opt1 ) && !empty( $old_sub_opt1 ) ){
							$new_val = array_merge($old_sub_opt1,$merge_opt);
							$update_value[ $sub_option1 ] = $new_val;
						}else if(empty( $old_sub_opt1 ) ){
							$update_value[ $sub_option1 ] = $merge_opt;
						}else{
							$update_value[ $sub_option1 ] = $old_sub_opt1;
						}
						$update_value[ $sub_option2 ] = $old_sub_opt2;
						update_option( $option_page, $update_value );
					}
				}
			}
		}
		
		if(!defined("NEXTER_EXT_VER")){
			$success = false;
		}
		
		$output = '<div class="nxt-import-steps import-step-3 active" data-step="step-3">
					<div class="nxt-import-heading">'.esc_html__('Import Demo Data','nexter').'</div>
					<div class="nxt-panel-row nxt-import-demo-data">';
						if(!empty($builder_template)){
							foreach($builder_template as $key => $value){
								$output .= '<div class="nxt-panel-col">';
									$output .= '<div class="nxt-import-template">';
										if(!empty($value['thumb'])){
											$output .= '<img src="'.esc_url(NXT_THEME_URI .'assets/images/'.$value['thumb']).'" class="template-thumb" />';
										}
										$output .= '<div class="nxt-temp-title-wrap">';
											if(!empty($value['title'])){
												$output .= '<div class="template-name">'.esc_html($value['title']);
												if(!empty($value['desc'])){
													$output .= '<span class="nxt-desc-icon" ><img src="'.esc_url( NXT_THEME_URI.'assets/images/panel-icon/desc-icon.svg').'" alt="'.esc_attr__('template-name','nexter').'" /><div class="nxt-tooltip">'.wp_kses_post($value['desc']).'</div></span>';
												}
												$output .= '</div>';
											}
											if(!empty($value['desc'])){
												//$output .= '<div class="template-desc">'.esc_html($value['desc']).'</div>';
											}
											if( !empty($key) ){
												$output .= '<a href="#" class="nxt-template-import-btn" data-builder="'.esc_attr($builder).'" data-tag="'.esc_attr($value['tag']).'" data-template="'.esc_attr($key).'">'.esc_html__('Import','nexter').'</a>';
											}
										$output .= '</div>';
									$output .= '</div>';
								$output .= '</div>';
							}
						}
		$output .= '</div>
				</div>';
				
		if( $success ){
			wp_send_json_success( [ 'success' => true, 'content' => $output ] );
		}else{
			wp_send_json_error( [ 'success'	=> false, 'content'	=> '' ] );
		}
	}
	
	/**
     * Register nexter setting to WP
     */
    public function init() {
        $option_tabs = self::option_fields();
		
        foreach ($option_tabs as $index => $option_tab) {
            register_setting($option_tab['id'], $option_tab['id']);
        }
    }
	
	/**
     * Add menu options page
     */
    public function nxt_add_menu_page() {
		$option_tabs = self::option_fields();
		global $_registered_pages, $submenu;
		
		unset($submenu['themes.php'][20]);
		unset($submenu['themes.php'][15]);
		
		foreach ($option_tabs as $index => $option_tab) {
			if($index == 0){
				add_theme_page($this->setting_name, $this->setting_name, 'manage_options', $option_tab['id'], array(
					$this,
					'admin_page_display'
				));
			}else{
				if ( ! current_user_can( 'manage_options' ) ) {
					return false;
				}
				if(isset($option_tabs) && $option_tab['id'] != "nexter_white_label" && $option_tab['id'] != "nexter_activate"){
					$function_name = array( $this,'admin_page_display');
					$hookname = get_plugin_page_hookname( $option_tab['id'], $option_tabs[0]['id'] );
					if ( ! empty( $function_name ) && ! empty( $hookname ) ) {
						add_action( $hookname, $function_name );
					}
					$_registered_pages[ $hookname ] = true;	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				}else{
					$label_options=get_option( 'nexter_white_label' );	
					if( ((empty($label_options['nxt_hidden_label']) || $label_options['nxt_hidden_label']!='on') && ($option_tab['id'] == "nexter_white_label" || $option_tab['id'] == "nexter_activate")) || !defined('NXT_PRO_EXT_VER')){
						$function_name = array( $this,'admin_page_display');
						$hookname = get_plugin_page_hookname( $option_tab['id'], $option_tabs[0]['id'] );
						if ( ! empty( $function_name ) && ! empty( $hookname ) ) {
							add_action( $hookname, $function_name );
						}
						$_registered_pages[ $hookname ] = true;	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
					}
				}
			}
		}
    }
	
	/**
     * Theplus Gutenberg Display Page
     * @since  1.0.0
     */
    public function admin_page_display() {
		$option_tabs = self::option_fields();
		$tab_forms   = array();
		
		$output ='';
		
		$output .='<div class="'.esc_attr($this->key).'">';
			
			$output .='<div id="nxt-setting-header-wrapper">';
				$output .='<div class="nxt-head-inner">';
				
					$options = get_option( 'nexter_white_label' );
					if(defined('NXT_PRO_EXT') && (!empty($options['theme_logo']))){
						$output .='<img src="'.esc_url($options['theme_logo']).'" style="max-width:150px;"/>';
					}else{
						$output .='<svg xmlns="http://www.w3.org/2000/svg" width="202" height="65" fill="none"><g fill="#fff" clip-path="url(#a)"><path d="M45.61 22.466 35.309.178h-6.902v63.227h6.902V11.51l10.288 22.284.015 29.612h6.902V.178H45.61v22.288ZM104.905 38.569c0 .449-.27.798-.808 1.052l-4.368 1.69-4.374-1.664c-.54-.247-.808-.603-.808-1.055V.026L87.652 0v38.694c0 2.166 1.313 3.881 3.938 5.147l1.236.589-1.236.591c-2.626 1.266-3.938 2.978-3.938 5.138v13.066h6.914V50.331c0-.466.27-.845.808-1.1l4.355-1.683 4.35 1.684c.538.254.808.624.808 1.099v12.894h6.928V50.16c0-2.158-1.314-3.87-3.94-5.138l-1.237-.591 1.237-.59c2.623-1.265 3.937-2.98 3.94-5.146V0h-6.91v38.569ZM119.4 5.578l8.314-.007v57.834h6.884l.023-57.834h8.302V.178h-23.516l-.007 5.4ZM197.181 51.037c2.92-.986 4.379-2.799 4.376-5.438V6.08c0-1.631-.753-3.028-2.241-4.193C197.828.722 196.72.133 194.673.133H178.06v63.232h6.902v-9.627h7.035c.058 0 .107.02.161.028.304.032.602.105.887.217.093.032.177.07.263.109l.07.033c.459.205.835.56 1.067 1.006l.027.05c.043.092.079.186.108.283.021.063.04.128.055.193.054.229.081.463.082.699l-.033 7.011h6.91v-6.846c0-2.643-1.481-4.474-4.402-5.482l-.011-.002Zm-2.497-4.972a1.977 1.977 0 0 1-.866 1.6c-.6.459-1.34.697-2.096.675h-6.753V5.57h8.109c.406-.008.803.119 1.129.361a1.072 1.072 0 0 1 .487.902l-.01 39.232ZM60.107 63.398h19.955v-5.405H67.028v-10.83h9.238v-5.399h-9.238V5.571h13.034V.173H60.107v63.225ZM150.51 5.578l13.041.007v14.12h-9.236v5.404h9.229v32.896H150.51v5.4h19.962V.178H150.51v5.4ZM10.36 57.176l-.032 7.196H0v-9.995h7.47c.066 0 .115.023.17.03.32.036.633.114.932.233.096.033.193.075.284.114.023.017.05.026.072.042a2.33 2.33 0 0 1 1.13 1.071l.032.056c.047.097.085.197.114.3.025.07.044.14.056.212.06.242.09.49.089.739M10.328 4.05v42.08c0 .657-.3 1.225-.918 1.714a3.46 3.46 0 0 1-2.233.722H0V2.69h8.616c.433-.009.856.128 1.202.389a1.165 1.165 0 0 1 .51.966"/></g><defs><clipPath id="a"><path fill="#fff" d="M0 0h201.571v64.371H0z"/></clipPath></defs></svg>';
					}
					$output .='<div class="nxt-panel-head-inner">';
						$output .='<h2 class="nxt-head-setting-panel">'.esc_html__('Theme Settings','nexter').'</h2>';
						$output .='<div class="nxt-current-version"> '.esc_html__('Version','nexter').' '.NXT_VERSION.'</div>';
					$output .='</div>';
				$output .='</div>';
				
				$output .='<div class="nxt-nav-tab-wrapper">';
					$output .='<div class="nav-tab-wrapper">';
						ob_start();
						foreach ($option_tabs as $option_tab):
							$tab_slug  = $option_tab['id'];
							$nav_class = 'nav-tab';
							if (isset($_GET['page']) && $tab_slug == $_GET['page']) {
								$nav_class .= ' nav-tab-active'; //add active class to current tab
								$tab_forms[] = $option_tab; //add current tab to forms to be rendered
							}
							$navicon = $nav_url ='';
							
							if($tab_slug == "nexter_settings_welcome" || $tab_slug == "nexter_import_data"){
								wp_enqueue_script( 'nexter-panel-setting' );
							}
							if($tab_slug == "nexter_settings_welcome"){
								$nav_url = menu_page_url($tab_slug, false);
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.559 1.5a.668.668 0 0 1 .883 0L16 4.64v-.973c0-.552-.054-.667.5-.667h4c.555 0 .5.115.5.667v5.497l2.5 2.166c.275.242.656.549.403.89-.204.275-.628.28-.903 0l-2-1.72v8.834C21 21.175 19.842 22 18 22H6c-1.84 0-3-.825-3-2.666V10.5l-2 1.72c-.276.28-.627.275-.87 0-.244-.312.094-.648.37-.89L11.56 1.5ZM20 8.3V4h-3v1.817L20 8.3ZM4 9.62v9.713C4 20.438 4.896 21 6 21h3v-6.333c0-.738.263-.667 1-.667h4c.738 0 1-.07 1 .667V21h3c1.105 0 2-.562 2-1.666V9.62l-8-6.96-8 6.96Zm6 5.38v6h4v-6h-4Z"/></svg>';
							}
							if($tab_slug == "nexter_activate"){
								$nav_url = admin_url( 'admin.php?page=' . $tab_slug );
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g clip-path="url(#clip0_1467_14917)"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.236 10.161a7.004 7.004 0 1 1 5.592 4.765l-.007-.002a.573.573 0 0 0-.052-.005.5.5 0 0 0-.412.143l-2.448 2.447H9.116a.5.5 0 0 0-.5.5v2.5h-2.5a.5.5 0 0 0-.5.5v2h-4.5v-4.293l8-8a.498.498 0 0 0 .146-.33.548.548 0 0 0-.024-.218l-.002-.007Zm10.07 5.082a8 8 0 0 1-4.413.697l-2.423 2.423a.5.5 0 0 1-.354.146h-2.5v2.5a.5.5 0 0 1-.5.5h-2.5v2a.5.5 0 0 1-.5.5h-5.5a.5.5 0 0 1-.5-.5v-5a.5.5 0 0 1 .147-.353L8.21 10.21a8 8 0 1 1 11.095 5.032ZM18.02 7.296a1 1 0 1 1-1.415-1.414 1 1 0 0 1 1.415 1.414Zm.707.707a2 2 0 1 1-2.829-2.828 2 2 0 0 1 2.829 2.828Z"/></g><defs><clipPath id="clip0_1467_14917"><path d="M0 0h24v24H0z"/></clipPath></defs></svg>';
							}
							if($tab_slug == "nexter_site_performance"){
								$nav_url = admin_url( 'admin.php?page=' . $tab_slug );
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 1c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10ZM6.016 11.506v-.003a6.004 6.004 0 0 1 8.545-4.934l.003.002a.528.528 0 0 0 .687-.202.471.471 0 0 0-.19-.665 7.248 7.248 0 0 0-.075-.035l-.054-.025A7 7 0 0 0 5.03 11.36l-.005.06a6.951 6.951 0 0 0-.006.082.471.471 0 0 0 .48.497.528.528 0 0 0 .518-.494Zm11.456-1.977-.001-.003a.528.528 0 0 1 .213-.684.471.471 0 0 1 .662.203l.034.075.024.054a7 7 0 0 1 .567 2.187l.005.06.006.082a.471.471 0 0 1-.48.497.528.528 0 0 1-.518-.494v-.003a6.005 6.005 0 0 0-.512-1.974ZM12 13a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm0 1a2 2 0 0 0 1.585-3.22l3.719-3.72a.5.5 0 1 0-.707-.706l-3.808 3.808A2 2 0 1 0 12 14Z"/></svg>';
							}
							if($tab_slug == "nexter_site_security"){
								$nav_url = admin_url( 'admin.php?page=' . $tab_slug );
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.824 1.532a.5.5 0 0 1 .352 0l8 3A.5.5 0 0 1 20.5 5v7c0 3.223-2.14 5.85-4.17 7.626a22.18 22.18 0 0 1-4.008 2.77c-.03.017-.054.029-.071.037l-.02.01-.005.003h-.002v.001L12 22l-.224.447-.002-.001-.005-.003-.02-.01a8.814 8.814 0 0 1-.335-.18 22.174 22.174 0 0 1-3.743-2.628C5.64 17.851 3.5 15.224 3.5 12.001V5a.5.5 0 0 1 .324-.468l8-3ZM12 22l.224.447a.5.5 0 0 1-.448 0L12 22Zm0-.565a19.593 19.593 0 0 1-.985-.577 21.199 21.199 0 0 1-2.686-1.984C6.36 17.15 4.5 14.777 4.5 12V5.346L12 2.534l7.5 2.813V12c0 2.777-1.86 5.15-3.83 6.874a21.194 21.194 0 0 1-3.67 2.56ZM12 11a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm-.5.937V14.5a.5.5 0 0 0 1 0v-2.563a2 2 0 1 0-1 0Z"/></svg>';
							}
							
							if($tab_slug == "nexter_extra_options"){
								$nav_url = admin_url( 'admin.php?page=' . $tab_slug );
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.5 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm.959-.5a3 3 0 0 0-5.918 0H2a.5.5 0 0 0 0 1h2.541a3 3 0 0 0 5.918 0H22a.5.5 0 0 0 0-1H10.459ZM13.5 18a2 2 0 1 0-4 0 2 2 0 0 0 4 0Zm-4.959-.5H2a.5.5 0 0 0 0 1h6.541a3 3 0 0 0 5.918 0H22a.5.5 0 0 0 0-1h-7.541a3 3 0 0 0-5.918 0ZM1.5 12a.5.5 0 0 1 .5-.5h14.041a3 3 0 1 1 0 1H2a.5.5 0 0 1-.5-.5ZM19 14a2 2 0 1 1 0-4 2 2 0 0 1 0 4Z"/></svg>';
							}
							
							if($tab_slug == "nexter_white_label"){
								$nav_url = admin_url( 'admin.php?page=' . $tab_slug );
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M18.181 7.933a.938.938 0 1 1-.334-.827 1.782 1.782 0 0 0 .72-.7l-.018-.002-3.764-.224a5.625 5.625 0 0 0-4.02 1.365l-6.298 5.461a.656.656 0 0 0-.057.937l5.522 6.094c.24.264.647.289.917.055l6.364-5.518a5.625 5.625 0 0 0 1.919-3.76l.296-3.393a.932.932 0 0 0-.087-.483 2.728 2.728 0 0 1-1.138 1.038v.001l-.022-.044Zm1.547-2.234c-.004.07-.011.141-.02.211.446.38.709.963.654 1.593l-.296 3.392a6.562 6.562 0 0 1-.895 2.78v6.669a.844.844 0 0 1-.844.843h-7.804a1.591 1.591 0 0 1-1.285-.52l-5.523-6.095a1.594 1.594 0 0 1 .138-2.274l6.299-5.462a6.563 6.563 0 0 1 4.16-1.602 2.72 2.72 0 0 1 5.417.465Zm-7.63 14.551h6.136v-5.351c-.13.133-.266.261-.407.384l-5.73 4.967Zm6.508-15.52c.119.236.183.496.19.76a1.895 1.895 0 0 0-.19-.022l-3.353-.2a1.782 1.782 0 0 1 3.353-.538ZM6.533 12.644a.469.469 0 0 1 .663.03l1.858 2.042a.469.469 0 0 1-.693.631l-1.859-2.041a.469.469 0 0 1 .031-.662Z"/></svg>';
							}
							
							if($tab_slug == "nexter_import_data"){
								$nav_url = admin_url( 'admin.php?page=' . $tab_slug );
								$navicon = '<svg class="tab-nav-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9.5 1A2.5 2.5 0 0 0 7 3.5V12H6V3.5A3.5 3.5 0 0 1 9.5 0h5.757a3.5 3.5 0 0 1 2.475 1.025l5.243 5.243A3.5 3.5 0 0 1 24 8.743V20.5a3.5 3.5 0 0 1-3.5 3.5h-11A3.5 3.5 0 0 1 6 20.5V18h1v2.5A2.5 2.5 0 0 0 9.5 23h11a2.5 2.5 0 0 0 2.5-2.5V9h-5.5A2.5 2.5 0 0 1 15 6.5V1H9.5Zm6.5.113V6.5A1.5 1.5 0 0 0 17.5 8h5.387a2.5 2.5 0 0 0-.62-1.025l-5.242-5.243A2.5 2.5 0 0 0 16 1.112Zm-5.828 9.851 3.182 3.182a.5.5 0 0 1 0 .708l-3.182 3.181a.5.5 0 1 1-.708-.707L11.793 15H.5a.5.5 0 0 1 0-1h11.293l-2.329-2.328a.5.5 0 1 1 .708-.708Z"/></svg>';
							}
							$label_options=get_option( 'nexter_white_label' );
							if( (empty($label_options['nxt_hidden_label']) || $label_options['nxt_hidden_label']!='on') && ($tab_slug == "nexter_white_label" || $tab_slug == "nexter_activate") ){
								echo '<a class="'.esc_attr($nav_class).'" href="'.esc_url($nav_url).'">';
									echo '<span>'.$navicon.'</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo '<span>'.esc_html($option_tab['title']).'</span>';
								echo '</a>';
							}else if(($tab_slug != "nexter_white_label" && $tab_slug != "nexter_activate") || !defined('NXT_PRO_EXT_VER')){
								echo '<a class="'.esc_attr($nav_class).'" href="'.esc_url($nav_url).'">';
									echo '<span>'.$navicon.'</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo '<span>'.esc_html($option_tab['title']).'</span>';
								echo '</a>';
							}
						endforeach;
						$out = ob_get_clean();
						$output .= $out;
					$output .='</div>';
				$output .='</div>';
			
			$output .='</div>';
			
			
		
			/*Content Options*/
			$output .='<div class="nxt-settings-form-wrapper form-'.esc_attr($tab_forms[0]['id']).'">';
			
				if(!empty($tab_forms)){
					ob_start();
					foreach ($tab_forms as $tab_form):
						
						if($tab_form['id']=='nexter_white_label'){
							do_action('nexter_white_label_notice');
						}else if($tab_form['id']=='nexter_activate'){
							do_action('nexter_activate_notice');
						}else if($tab_form['id']=='nexter_import_data'){
							do_action('nexter_import_data_render');
						}else if($tab_form['id']=='nexter_extra_options'){
							do_action('nexter_extra_options_render');
						}else if(!defined('NEXTER_EXT') && $tab_form['id']=='nexter_site_performance'){
							do_action('nexter_site_performance_notice');
						}else if(!defined('NEXTER_EXT') && $tab_form['id']=='nexter_site_security'){
							do_action('nexter_site_security_notice');
						}
						
						if( ( defined('NXT_PRO_EXT') && $tab_form['id']=='nexter_white_label' ) || ( defined('NEXTER_EXT') && ($tab_form['id']=='nexter_site_performance' || $tab_form['id']=='nexter_site_security' )) ){
							do_action('nexter_ext_extra_option' , $tab_form['id'] );
						}else if($tab_form['id']=='nexter_settings_welcome'){
							include_once NXT_THEME_DIR . 'inc/panel-settings/welcome-page.php';
						}
					endforeach;
						do_action('nexter_help_actions');
					$out = ob_get_clean();
					$output .= $out;
				}
			$output .='</div>';
			
		$output .='</div>';
		
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		
	}
	
	/**
     * Nexter Settings fields configuration
     */
    public function option_fields() {
		// Only need to initiate the array once per page-load
        if (!empty($this->option_metabox)) {
            return $this->option_metabox;
        }
		
		$this->option_metabox[] = array(
            'id' => 'nexter_settings_welcome',
            'title' => esc_html__('Welcome', 'nexter'),
            'show_on' => array(
                'key' => 'options-page',
                'value' => array(
                    'nexter_settings_welcome'
                )
            ),
            'show_names' => true,
            'fields' => ''
        );
		
		$this->option_metabox[] = array(
            'id' => 'nexter_import_data',
            'title' => esc_html__('Import', 'nexter'),
            'show_on' => array(
                'key' => 'options-page',
                'value' => array(
                    'nexter_import_data'
                )
            ),
            'show_names' => true,
            'fields' => ''
        );
		$this->option_metabox[] = array(
			'id' => 'nexter_extra_options',
			'title' => esc_html__('Extra Options', 'nexter'),
			'show_on' => array(
				'key' => 'options-page',
				'value' => array(
					'nexter_extra_options'
				)
			),
			'show_names' => true,
			'fields' => '',
		);
		
		$performance_options=[];
		if(has_filter('nexter_site_performance_options')) {
			$performance_options = apply_filters('nexter_site_performance_options', $performance_options);
		}
		
		$this->option_metabox[] = array(
			'id' => 'nexter_site_performance',
			'title' => esc_html__('Performance', 'nexter'),
			'show_on' => array(
				'key' => 'options-page',
				'value' => array(
					'nexter_site_performance'
				)
			),
			'show_names' => true,
			'fields' => $performance_options,
		);
		
		$security_options=[];
		if(has_filter('nexter_site_security_options')) {
			$security_options = apply_filters('nexter_site_security_options', $security_options);
		}
		$this->option_metabox[] = array(
			'id' => 'nexter_site_security',
			'title' => esc_html__('Security', 'nexter'),
			'show_on' => array(
				'key' => 'options-page',
				'value' => array(
					'nexter_site_security'
				)
			),
			'show_names' => true,
			'fields' => $security_options,
		);
		$this->option_metabox[] = array(
			'id' => 'nexter_activate',
			'title' => esc_html__('Activate', 'nexter'),
			'show_on' => array(
				'key' => 'options-page',
				'value' => array(
					'nexter_activate'
				)
			),
			'show_names' => true,
			'fields' => '',
		);
		
		$this->option_metabox[] = array(
			'id' => 'nexter_white_label',
			'title' => esc_html__('White Label', 'nexter'),
			'show_on' => array(
				'key' => 'options-page',
				'value' => array(
					'nexter_white_label'
				)
			),
			'show_names' => true,
			'fields' => '',
		);
		
		return $this->option_metabox;
	}
	
	/**
     * Public getter method for retrieving protected/private variables
     * @since  1.0.0
     * @param  string	$field Field to retrieve
     * @return mixed	Field value or exception is thrown
     */
    public function __get($field) {
        
        // Allowed fields to retrieve
        if (in_array($field, array('key','fields','title','options_page'), true)) {
            return $this->{$field};
        }
        if ('option_metabox' === $field) {
            return $this->option_fields();
        }
        /* translators: Invalid property: Fields */
        throw new Exception( sprintf( esc_html__( 'Invalid property: %1$s', 'nexter' ), $field ) );
    }
	
	/**
	 * Export Customizer options.
	 *
	 * @since 1.0.11
	 */
	public static function nxt_customizer_export_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( !isset( $_POST['nexter_export_cust_nonce'] ) || !wp_verify_nonce( $_POST['nexter_export_cust_nonce'], 'nexter_export_cust_nonce' ) ) {
			return;
		}
		if ( empty( $_POST['nxt_customizer_export_action'] ) || $_POST['nxt_customizer_export_action'] !== 'nxt_export_cust' ) {
			return;
		}

		// Get Customizer options
		$customizer_options = Nexter_Customizer_Options::get_options();

		$customizer_options = apply_filters( 'nexter_customizer_export_data', $customizer_options );
		nocache_headers();
		
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=nexter-customizer-export-' . gmdate( 'm-d-Y' ) . '.json' );
		header( 'Expires: 0' );
		echo wp_json_encode( $customizer_options );
		die();
	}
	
	/**
	 * Import Customizer options.
	 *
	 * @since 1.0.11
	 */
	public static function nxt_customizer_import_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( !isset( $_POST['nexter_import_cust_nonce'] ) || !wp_verify_nonce( $_POST['nexter_import_cust_nonce'], 'nexter_import_cust_nonce' ) ) {
			return;
		}
		if ( empty( $_POST['nxt_customizer_import_action'] ) || $_POST['nxt_customizer_import_action'] !== 'nxt_import_cust' ) {
			return;
		}
		
		$filename = $_FILES['nxt_import_file']['name'];

		if ( empty( $filename ) ) {
			return;
		}
		
		$file_extension  = explode( '.', $filename );
		$extension = end( $file_extension );

		if ( $extension !== 'json' ) {
			wp_die( esc_html__( 'Valid .json file extension', 'nexter' ) );
		}

		$nxt_import_file = $_FILES['nxt_import_file']['tmp_name'];

		if ( empty( $nxt_import_file ) ) {
			wp_die( esc_html__( 'Please upload a file', 'nexter' ) );
		}

		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			WP_Filesystem();
		}
		
		$get_contants = $wp_filesystem->get_contents( $nxt_import_file );
		$customizer_options      = json_decode( $get_contants, 1 );
		if ( !empty( $customizer_options ) ) {
			update_option( 'nxt-theme-options', $customizer_options );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'nexter_extra_options',
					'status_customizer' => 'success',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
	
}

// Get it started
$Nexter_Settings_Panel = new Nexter_Settings_Panel();
$Nexter_Settings_Panel->hooks();