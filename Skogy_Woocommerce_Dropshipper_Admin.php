<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
class Skogy_Woocommerce_Dropshipper_Admin {
	private static $handle = 'skogy_woocommerce_dropshipper_admin';
	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}

		/*
			// Add your custom order status action button (for orders with "processing" status)
add_filter( 'woocommerce_admin_order_actions', 'add_custom_order_status_actions_button', 100, 2 );
function add_custom_order_status_actions_button( $actions, $order ) {
    // Display the button for all orders that have a 'processing' status
    if ( $order->has_status( array( 'processing' ) ) ) {

        // The key slug defined for your action button
        $action_slug = 'parcial';

        // Set the action button
        $actions[$action_slug] = array(
            'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=woocommerce_mark_order_status&status=parcial&order_id=' . $order->get_id() ), 'woocommerce-mark-order-status' ),
            'name'      => __( 'Envio parcial', 'woocommerce' ),
            'action'    => $action_slug,
        );
    }
    return $actions;
}

// Set Here the WooCommerce icon for your action button
add_action( 'admin_head', 'add_custom_order_status_actions_button_css' );
function add_custom_order_status_actions_button_css() {
    $action_slug = "parcial"; // The key slug defined for your action button

    echo '<style>.wc-action-button-'.$action_slug.'::after { font-family: woocommerce !important; content: "\e029" !important; }</style>';
}
		*/
	}

	public static function init_hooks() {
		self::$initiated = true;
		add_action( 'admin_menu', array( 'Skogy_Woocommerce_Dropshipper_Admin', 'add_menu' ) );

		if (is_admin() ) {
			add_action( 'admin_enqueue_scripts', array('Skogy_Woocommerce_Dropshipper_Admin', 'load_custom_wp_admin_style' ));
		}
	}
	public function load_custom_wp_admin_style() {
        wp_enqueue_script('jquery');

		wp_enqueue_script('jq_postmessage', SKOGY_WOOCOMMERCE_DROPSHIPPER__PLUGIN_URL.'resources/jquery.postmessage.min.js');
		wp_enqueue_script('jq_serializejson', SKOGY_WOOCOMMERCE_DROPSHIPPER__PLUGIN_URL.'resources/jquery.serializejson.js');
		wp_enqueue_script('ohsnap', SKOGY_WOOCOMMERCE_DROPSHIPPER__PLUGIN_URL.'resources/ohsnap.min.js');

		wp_enqueue_script( 'jquery-ui-dialog' ); 
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	public static function install() {
		
	}
	public static function uninstall() {
		
	}

	public function add_menu() {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			add_submenu_page( 'woocommerce', 'Skogy Dropshipping', 'Skogy Dropshipping', 'manage_woocommerce', self::$handle, array( 'Skogy_Woocommerce_Dropshipper_Admin', 'load_iframe' ) );
		}
	}

	public function load_iframe() {
		$params = array(
			"app" => self::$handle,
			"tab" => ((isset($_GET['tab'])) ? $_GET['tab']:''),
			"shop" => get_site_url(),
			"email" => wp_get_current_user()->user_email,
			"currency" => get_woocommerce_currency(),
			"country" => wc_get_base_location()['country'],
			"locale" => get_locale(),
		);

		$consumer_key = self::_get_consumer_key();

		if (array_key_exists("consumer_key", $consumer_key)) {
			$params['onboarding'] = 1;
			$params['key'] = $consumer_key['consumer_key'];
			$params['secret'] = $consumer_key['consumer_secret'];
			$params['truncated_key'] = $consumer_key['truncated_key'];
		}
		ELSE {
			$params['secret'] = $consumer_key['consumer_secret'];
			$params['truncated_key'] = $consumer_key['truncated_key'];
		}

		$base_url = '?page='.self::$handle;

		$default_tab_handle = "products";

		$tabs = array(
			array(
				"handle" => "products",
				"label" => "Product Catalog",
			),
			array(
				"handle" => "my_products",
				"label" => "My Products",
			),
			array(
				"handle" => "categories",
				"label" => "My Categories",
			),
			array(
				"handle" => "orders",
				"label" => "Orders",
			),
			array(
				"handle" => "account",
				"label" => "My Account",
			)
		);

	    echo '
<style>
	.alert {
	  padding: 15px;
	  margin-bottom: 20px;
	  border: 1px solid #eed3d7;
	  border-radius: 4px;
	  position: absolute;
	  bottom: 0px;
	  right: 21px;
	  /* Each alert has its own width */
	  float: right;
	  clear: right;
	  background-color: white;
	  white-space: nowrap;
	}

	.alert-red {
	  color: white;
	  background-color: #DA4453;
	}
	.alert-green {
	  color: white;
	  background-color: #37BC9B;
	}
	#ohsnap {
	    text-shadow: none;
	}
	#ohsnap {
	    position: fixed;
	    bottom: 5px;
	    right: 5px;
	    margin-left: 5px;
	    z-index: 99;
	}
</style>
<div id="ohsnap"></div>
<div id="skogy-dialog" class="hidden" style="max-width:800px;padding:0px;">
  <iframe id="skogyDialogIframe" name="skogyDialogIframe" src="" style="height:314px;width:640px;"></iframe>
</div>

<script>
jQuery(function($) {
  var buttonEvent = null;
  $(\'#skogy-dialog\').dialog({
    title: \'My Dialog\',
    dialogClass: \'wp-dialog\',
    autoOpen: false,
    draggable: false,
    width: 640,
    height: 450,
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: "center",
      at: "center",
      of: window
    },
    buttons: {
        "Tete": function() {
            sendToIframe({modal_click:buttonEvent});
        }
    },
    open: function () {
      // close dialog by clicking the overlay behind it
      $(\'.ui-widget-overlay\').bind(\'click\', function(){
        $(\'#skogy-dialog\').dialog(\'close\');
      })
    },
    create: function () {
      // style fix for WordPress admin
      $(\'.ui-dialog-titlebar-close\').removeClass(\'ui-dialog-titlebar-close\');
    },
  });
  // bind a button or a link to open the dialog
  $(\'a.open-skogy-dialog\').click(function(e) {
    e.preventDefault();
    
  });
});
	function openSkogyDialog(title, url, button) {
		//TODO: Add 70px to iframe height if no button
		var ob = {};
		if (button) {
			buttonEvent = button.event;
			ob[(button.label)] = function() {
		        sendToIframe({modal_click:buttonEvent});
		    };
		}
		jQuery("#skogy-dialog").dialog("option", "buttons", ob);
		jQuery("#skogy-dialog").dialog("option", "title", title);
		jQuery("#skogyDialogIframe").attr("src", url);
		jQuery("#skogy-dialog").dialog("open");
	}
	function closeSkogyDialog() {
		jQuery("#skogy-dialog").dialog("close");	
	}
</script>
	    <div class="wrap">

	    <h2 class="nav-tab-wrapper skogy-tabs">
	    	';
	    	foreach ($tabs as $tab) {
	    		$active = false;
	    		if (( ! empty( $_GET['tab'] ) && $_GET['tab'] == $tab['handle']) || ((empty($_GET['tab']) && ($tab['handle'] == $default_tab_handle))))  {
	    			$active = true;
	    		}
	    		echo '<a href="'.esc_url($base_url . '&tab=' . $tab['handle']).'" class="nav-tab '.(($active) ? 'nav-tab-active':'').'">'.esc_html($tab['label']).'</a>';
	    	}
	    echo '
	    </h2>
	    </div>
	    <script>
	    	function sendToIframe(message) {
		  		jQuery.postMessage(
				    JSON.stringify(message),
				    "https://www.skogy.com",
				    window.frames["skogy_iframe"]
				);
		  	}
	    	jQuery(function($) {
				$.receiveMessage(
					function(e){
						var obj = $.parseJSON(e.data);

						if (obj.height) {
							$(\'#skogy_iframe\').css(\'height\', obj.height);
						}
						else if (obj.notification) {
							ohSnap(obj.notification.message, obj.notification.options);
						}
						else if (obj.init) {
							window.scrollTo(0, 0);
						}
						else if (obj.redirect) {
							window.location.href = obj.redirect;
						}
						else if (obj.modal) {
							openSkogyDialog(obj.modal.title, obj.modal.src, obj.modal.button);
						}
						else if (obj.append) {
							$(\'#appendstuff\').append(obj.append);
						}
						else if (obj.modal_close) {
							closeSkogyDialog();
						}
					},
					\'https://www.skogy.com\'
				);
			});
	    </script>
	    <iframe id="skogy_iframe" name="skogy_iframe" src="https://www.skogy.com/dropshipping/woocommerce/auth?'.http_build_query($params).'" style="width:100%;min-height:300px;" /></iframe>
	    <div id="appendstuff"></div>';
	}
	public function _get_consumer_key() {
		global $wpdb;

		// Get the API key
		$search = "AND description LIKE '" . esc_sql( $wpdb->esc_like( wc_clean( self::$handle ) ) ) . "%' ";
		$query  = "SELECT truncated_key FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search} ORDER BY key_id DESC LIMIT 1";
		$consumer_key = $wpdb->get_var( $query );

		if (!$consumer_key) {
			return self::_create_consumer_key();
		}
		ELSE {
			$query  = "SELECT consumer_secret FROM {$wpdb->prefix}woocommerce_api_keys WHERE 1 = 1 {$search} ORDER BY key_id DESC LIMIT 1";
			$consumer_secret = $wpdb->get_var( $query );
		}

		return array("truncated_key" => $consumer_key, "consumer_secret" => $consumer_secret);
	}
	public function _remove_consumer_key($key_id) {
		global $wpdb;

		$wpdb->remove(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				"key_id" => $key_id
			),
			array(
				'%d'
			)
		);
	}
	public function rand_hash() {
		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			return bin2hex( openssl_random_pseudo_bytes( 20 ) ); // @codingStandardsIgnoreLine
		} else {
			return sha1( wp_rand() );
		}
	}
	public function _create_consumer_key() {
		global $wpdb;

		$consumer_key    = 'ck_' . self::rand_hash();
		$consumer_secret = 'cs_' . self::rand_hash();

		$app = array(
			'user_id'         => get_current_user_id(),
			'permissions'     => 'read_write',
			'consumer_key'    => hash_hmac( 'sha256', $consumer_key, 'wc-api'),
			'description' 	  => self::$handle,
			'consumer_secret' => $consumer_secret,
			'truncated_key'   => substr( $consumer_key, -7 )
		);

		$wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			$app,
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s'
			)
		);

		$app['consumer_key'] = $consumer_key;

		return $app;
	}
}
