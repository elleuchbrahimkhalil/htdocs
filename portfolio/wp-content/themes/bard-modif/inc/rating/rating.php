<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class BardRatingNotice {
    private $past_date;

    public function __construct() {
        global $pagenow;
        $this->past_date = strtotime( '-'.get_option('bard_random_number').'days' );

        if ( current_user_can('administrator') ) {
            if ( empty(get_option('bard_rating_dismiss_notice', false)) && empty(get_option('bard_rating_already_rated', false)) ) {
                add_action( 'admin_init', [$this, 'check_theme_install_time'] );
            }
        }

        if ( is_admin() ) {
            add_action( 'admin_head', [$this, 'enqueue_scripts' ] );
        }

        add_action( 'wp_ajax_bard_rating_dismiss_notice', [$this, 'bard_rating_dismiss_notice'] );
        add_action( 'wp_ajax_bard_rating_already_rated', [$this, 'bard_rating_already_rated'] );
        add_action( 'wp_ajax_bard_rating_need_help', [$this, 'bard_rating_need_help'] );
        add_action( 'wp_ajax_bard_rating_maybe_later', [$this, 'bard_rating_maybe_later'] );
    }

    public function check_theme_install_time() {   
        $install_date = get_option('bard_activation_time');

        if ( false !== $install_date && $this->past_date >= $install_date ) {
            add_action( 'admin_notices', [$this, 'render_rating_notice' ]);
        }
    }

    public function bard_rating_maybe_later() {
        update_option('bard_random_number', 10);
        update_option('bard_activation_time', strtotime('now'));
    }
    
    public function bard_rating_dismiss_notice() {
        update_option( 'bard_rating_dismiss_notice', true );
    }

    function bard_rating_already_rated() {    
        update_option( 'bard_rating_already_rated' , true );
    }

    public function bard_rating_need_help() {
        // Reset Activation Time if user Needs Help
        update_option('bard_random_number', 10);
        update_option( 'bard_activation_time', strtotime('now') );
    }

    public function render_rating_notice() {
        global $pagenow;

        if ( is_admin() ) {

            echo '<div class="notice bard-rating-notice is-dismissible" style="border-left-color: #0073aa!important; display: flex; align-items: center;">
                        <div class="bard-rating-notice-logo">
                        <img class="bard-logo" src="'.get_theme_file_uri().'/assets/images/bard-blog.png">
                        </div>
                        <div>
                            <h3>Thank you for using Bard Theme to build this website!</h3>
                            <p>Could you please do us a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.</p>
                            <p>
                                <a href="https://wordpress.org/support/theme/bard/reviews/#new-post" target="_blank" class="bard-you-deserve-it button button-primary">OK, you deserve it!</a>
                                <a class="bard-maybe-later"><span class="dashicons dashicons-clock"></span> Maybe Later</a>
                                <a class="bard-already-rated"><span class="dashicons dashicons-yes"></span> I Already did</a>
                                <a href="https://wp-royal-themes.com/contact/#!/cform" target="_blank" class="bard-need-support"><span class="dashicons dashicons-sos"></span> I need support!</a>
                                <a class="bard-notice-dismiss-2"><span class="dashicons dashicons-thumbs-down"></span> NO, not good enough</a>
                            </p>
                        </div>
                </div>';
        }
    }

    public function enqueue_scripts() { 
        echo "
        <script>
        jQuery( document ).ready( function() {

            jQuery(document).on( 'click', '.bard-notice-dismiss-2', function() {
                jQuery(document).find('.bard-rating-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'bard_rating_dismiss_notice',
                    }
                })
            });

            jQuery(document).on( 'click', '.bard-maybe-later', function() {
                jQuery(document).find('.bard-rating-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'bard_rating_maybe_later',
                    }
                })
            });
        
            jQuery(document).on( 'click', '.bard-already-rated', function() {
                jQuery(document).find('.bard-rating-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'bard_rating_already_rated',
                    }
                })
            });
        
            jQuery(document).on( 'click', '.bard-need-support', function() {
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'bard_rating_need_help',
                    }
                })
            });
        });
        </script>

        <style>
            .bard-rating-notice {
              padding: 0 15px;
            }

            .bard-rating-notice-logo {
                margin-right: 20px;
                width: 100px;
                height: 100px;
            }

            .bard-rating-notice-logo img {
                max-width: 100%;
            }

            .bard-rating-notice h3 {
              margin-bottom: 0;
            }

            .bard-rating-notice p {
              margin-top: 3px;
              margin-bottom: 15px;
            }

            .bard-already-rated,
            .bard-need-support,
            .bard-notice-dismiss-2,
            .bard-maybe-later {
              text-decoration: none;
              margin-left: 12px;
              font-size: 14px;
              cursor: pointer;
            }

            .bard-already-rated .dashicons,
            .bard-need-support .dashicons,
            .bard-maybe-later .dashicons {
              vertical-align: sub;
            }

            .bard-notice-dismiss-2 .dashicons {
              vertical-align: middle;
            }

            .bard-rating-notice .notice-dismiss {
                display: none;
            }

            .bard-logo {
                height: 100%;
                width: auto;
            }

        </style>
        ";
    }
}

if ( 'Bard' === wp_get_theme()->get('Name')) {
    new BardRatingNotice();
}