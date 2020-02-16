<?php

/**
 * Plugin Name: Custom Woocommerce Shipping Method For Zones
 * Description: A Custom Shipping Method for different zones in Woocommerce.
 * Author: Ryan Behague
 */

if (!defined('WPINC')) {
  die('No Direct Access');
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

  function rb_custom_shipping_method_init()
  {

    if (!class_exists('WC_RB_Custom_Shipping_Method')) {

      class WC_RB_Custom_Shipping_Method extends WC_Shipping_Method
      {

        public function __construct($instance_id = 0)
        {
          $this->id = 'rb_custom_shipping_method';
          $this->instance_id = absint($instance_id);
          $this->method_title = __('Custom Shipping Method for Zones', 'rb_custom_shipping');
          $this->method_description = __('A Custom Shipping Method configurable for individual zones', 'rb_custom_shipping');
          $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
          );

          
          $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Custom Shipping for Zones', 'rb_custom_shipping');
        
          $this->init();

        }

        function init()
        {
          $this->init_form_fields();
          $this->init_settings();

          $this->enabled = $this->get_option( 'enabled' );
          $this->title = $this->get_option( 'title' ); 

          add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        function init_form_fields()
        {
          $this->instance_form_fields = array(
            'enabled' => array(
              'title' => __('Enable', 'rb_custom_shipping'),
              'type' => 'checkbox',
              'default' => 'yes',
            ),
            'title' => array(
              'title' => __('Title', 'rb_custom_shipping'),
              'type' => 'text',
              'default' => __('Custom Zone Shipping', 'rb_custom_shipping'),
              'description' => __( 'Visible on the front end.', 'rb_custom_shipping' ),
            ),
            'cost' => array(
              'title' => __('Cost', 'rb_custom_shipping'),
              'type' => 'number',
              'description' => __( 'Cost of shipping', 'rb_custom_shipping' ),
              'default' => 4
            ),
            'minweight' => array(
              'title' => __('Min Weight (kg)', 'rb_custom_shipping'),
              'type' => 'number',
              'description' => __( 'Minimum Weight Limit', 'rb_custom_shipping' ),
              'default' => 50,
            ),
            'maxweight' => array(
              'title' => __('Max Weight (kg)', 'rb_custom_shipping'),
              'type' => 'number',
              'description' => __( 'Maximum Weight Limit', 'rb_custom_shipping' ),
              'default' => 50,
            )

          );
        }

        public function calculate_shipping( $package = array() )
        {
          $instance_settings =  $this->instance_settings;
          
          $minweight = $instance_settings['minweight'];
          $maxweight = $instance_settings['maxweight'];

          $weight = 0;

          $country = $package["destination"]["country"]; // example get package info

          foreach ($package['contents'] as $item_id => $values) {
            $_product = $values['data'];
            $weight = $weight + $_product->get_weight() * $values['quantity'];
          }

          $weight = wc_get_weight($weight, 'kg');

          if ($weight >= $minweight && $weight <= $maxweight) {

            $rate = array(
              'label'   => $this->get_option( 'title' ),
              'cost'    => $this->get_option( 'cost' ),
              'taxes'   => 'per_order'
            );
  
            $this->add_rate($rate);

          }
        }
      }
    }
  }

  // Add to the list of Woocommerce Shipping Methods
  add_action('woocommerce_shipping_init', 'rb_custom_shipping_method_init');

  function add_rb_custom_shipping_method($methods)
  {
    $methods['rb_custom_shipping_method'] = 'WC_RB_Custom_Shipping_Method';
    return $methods;
  }

  add_filter('woocommerce_shipping_methods', 'add_rb_custom_shipping_method');

}
