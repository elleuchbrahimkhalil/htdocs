<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) exit();

UNINSTALL_jci_plugin_options();

function UNINSTALL_jci_plugin_options() {
    global $wpdb;
    if (function_exists('is_multisite') && is_multisite()) {
      $blogIdCurrent = $wpdb->blogid;  // retrieve blogIds
      $blogIdArr = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
      foreach ($blogIdArr as $blogid) {
        switch_to_blog($blogid);
        UNINSTALL_jci_options();
      }
      switch_to_blog($blogIdCurrent);
      return;
    }
    UNINSTALL_jci_options();
	delete_option( "jci_uninstall_deleteall" );
}

function UNINSTALL_jci_options() {
  if (get_option('jci_uninstall_deleteall')==1) {
    delete_option( "jci_json_url" );
	delete_option( "jci_enable_cache" );
    delete_option( "jci_cache_time" );
    delete_option( "jci_cache_time_format" );
    delete_option( "jci_oauth_bearer_access_key" );
    delete_option( "jci_http_header_default_useragent" );
    delete_option( "jci_gutenberg_off" );
    delete_option( "jci_sslverify_off" );
    delete_option( "jci_api_errorhandling" );
  }
}

UNINSTALL_jci_plugin_cacher();

function UNINSTALL_jci_plugin_cacher() {
	$cacheFolder = WP_CONTENT_DIR.'/cache/jsoncontentimporter/';
	return clearCacheFolder($cacheFolder);
}

function clearCacheFolder($cacheFolder) {
		if (!preg_match("/jsoncontentimporter\/$/", $cacheFolder)) {
			return FALSE;			
		}
		$cachefiles = glob($cacheFolder.'*'); 
        foreach($cachefiles as $file){
            if(is_file($file)) {
                if (unlink($file)) {
				}
            }
        }
		rmdir($cacheFolder);
		return TRUE;
	}
?>