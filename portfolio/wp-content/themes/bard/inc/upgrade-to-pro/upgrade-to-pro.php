<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class bardUpdateToProNotice {
    private $compare_date;

    public function __construct() {
        global $pagenow;
        $this->compare_date = (int) strtotime('now');

        if ( current_user_can('administrator') ) {
            if ( empty(get_option('bard_update_to_pro_dismiss_notice', false)) ) {
                add_action( 'admin_init', [$this, 'check_theme_install_time'] );
            }
        }

        if ( is_admin() ) {
            add_action( 'admin_head', [$this, 'enqueue_scripts' ] );
        }

        add_action( 'wp_ajax_bard_update_to_pro_dismiss_notice', [$this, 'bard_update_to_pro_dismiss_notice'] );
        add_action( 'wp_ajax_bard_update_to_pro_maybe_later', [$this, 'bard_update_to_pro_maybe_later'] );
    }

    public function check_theme_install_time() {   
        $install_date = (int) get_option('bard_activation_time_update_to_pro');

        if ( get_option('bard_update_to_pro_maybe_later_time') != false && !(($this->compare_date - $install_date) >= 172800) ) {
            return;
        }

        add_action( 'admin_notices', [$this, 'render_update_to_pro_notice' ]);

        if ( get_option('bard_update_to_pro_maybe_later_time') != false && (($this->compare_date - $install_date) >= 172800) ) {
            delete_option('bard_update_to_pro_maybe_later_time');
        }
    }
    
    public function bard_update_to_pro_dismiss_notice() {
        update_option( 'bard_update_to_pro_dismiss_notice', true );
    }

    public function bard_update_to_pro_maybe_later() {
        update_option('bard_update_to_pro_maybe_later_time', true);
        delete_option('bard_activation_time_update_to_pro');
    }

    public function render_update_to_pro_notice() {
        global $pagenow;

        if ( is_admin() ) {

            echo '<div class="notice bard-update-to-pro-notice is-dismissible" style="border-left-color: #0073aa!important; display: flex; align-items: center;">
                        <div class="bard-update-to-pro-notice-logo">
                        <img class="bard-logo" src="'.get_theme_file_uri().'/assets/images/bard-blog.png">
                        </div>
                        <div>
                            <h3>Important Information!</a></h3>
                            <p>
                                Dear Bard Theme users, our website <strong>wp-royal-themes.com</strong> is currently not available, we are migrating our servers to much better and faster infrastructure and this might take a few days. During this period if you will need support you can post your questions <a target="_blank" href="https://wordpress.org/support/theme/bard/"><strong>here</strong></a>, or if you are a PRO user you can contact us via this email <strong>info.wproyal@gmail.com</strong>.
                            </p>
                            <p>
                                If you are interested to <strong>Upgrade to the Bard PRO</strong> version just follow the button below. After purchasing you will get the Bard PRO theme and License key via your email.
                                <br>
                                Thanks for Understanding.
                            </p>

                            <p>
                                <a href="https://checkout.freemius.com/mode/dialog/theme/1965/plan/2930/" target="_blank" class="bard-you-deserve-it button button-primary">Upgrade to Pro</a>
                                <a class="bard-maybe-later"><span class="dashicons dashicons-clock"></span> Remind Me In 2 Days</a>
                                <a class="bard-notice-dismiss-2"></a>
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
                jQuery(document).find('.bard-update-to-pro-notice').slideUp();
                jQuery.post({
                    url: ajaxurl,
                    data: {
                        action: 'bard_update_to_pro_dismiss_notice',
                    }
                })
            });
        });

        jQuery(document).on( 'click', '.bard-maybe-later', function() {
            jQuery(document).find('.bard-update-to-pro-notice').slideUp();
            jQuery.post({
                url: ajaxurl,
                data: {
                    action: 'bard_update_to_pro_maybe_later',
                }
            })
        });
        </script>

        <style>
            .bard-update-to-pro-notice {
              padding: 0 15px;
            }

            .bard-update-to-pro-notice-logo {
                margin-right: 20px;
            }

            .bard-update-to-pro-notice-logo img {
                max-width: 100%;
            }

            .bard-update-to-pro-notice h3 {
              margin-bottom: 10px;
            }

            bard-update-to-pro-notice h3 a {
                text-decoration: none;
            }

            .bard-update-to-pro-notice p {
              margin-top: 3px;
              margin-bottom: 10px;
            }

            .bard-update-to-pro-notice .bard-notice-dismiss-2:before {
                content: '\\f153';
                background: 0 0;
                color: #787c82;
                display: block;
                font: normal 16px/20px dashicons;
                speak: never;
                text-align: center;
                height: 20px;
                width: 20px;
            }

            .bard-update-to-pro-notice {
                position: relative;
            }

            .bard-update-to-pro-notice .bard-notice-dismiss-2 {
                position: absolute;
                top: 0;
                right: 0;
                padding: 9px;
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

            .bard-update-to-pro-notice .notice-dismiss {
                display: none;
            }

        </style>
        ";
    }
}

if ( 'Bard' === wp_get_theme()->get('Name')) {
    new bardUpdateToProNotice();
}