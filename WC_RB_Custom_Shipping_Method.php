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
          $this->method_title = __('Custom Shipping', 'rb_custom_shipping');
          $this->method_description = __('Custom Shipping Method for Zones', 'rb_custom_shipping');
          $this->supports = array(
            'shipping-zones',
            'instance-settings',
          );

          $this->init();

          $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
          $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Custom Shipping for Zones', 'rb_custom_shipping');

        }

        function init()
        {
          $this->init_form_fields();
          $this->init_settings();
          add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        function init_form_fields()
        {
          $this->form_fields = array(
            'enabled' => array(
              'title' => __('Enable', 'rb_custom_shipping'),
              'type' => 'checkbox',
              'default' => 'yes',
            ),
            'weight' => array(
              'title' => __('Weight (kg)', 'rb_custom_shipping'),
              'type' => 'number',
              'default' => 50,
            ),
            'title' => array(
              'title' => __('Title', 'rb_custom_shipping'),
              'type' => 'text',
              'default' => __('Custom Zone Shipping', 'rb_custom_shipping'),
            ),
          );
        }

        public function rrus_shipping_calculator($package)
        {
          $weight = 0;
          $cost = 0;
          $country = $package["destination"]["country"];

          foreach ($package['contents'] as $item_id => $values) {
            $_product = $values['data'];
            $weight = $weight + $_product->get_weight() * $values['quantity'];
          }

          $weight = wc_get_weight($weight, 'kg');

          if ($weight <= 5) {
            $cost = 0;
          } elseif ($weight <= 25) {
            $cost = 5;
          } elseif ($weight <= 45) {
            $cost = 10;
          } else {
            $cost = 15;
          }

          $countryZones = array(
            'ES' => 2,
            'GB' => 2,
            'US' => 3,
          );

          $zonePrices = array(
            2 => 50,
            3 => 70,
          );

          $zoneFromCountry = $countryZones[$country];
          $priceFromZone = $zonePrices[$zoneFromCountry];
          $cost += $priceFromZone;

          $rate = array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $cost,
          );

          $this->add_rate($rate);

        }
      }
    }
  }

  add_action('woocommerce_shipping_init', 'rb_custom_shipping_method_init');

  function add_rb_custom_shipping_method($methods)
  {
    $methods['rb_custom_shipping_method'] = 'WC_RB_Custom_Shipping_Method';
    return $methods;
  }

  add_filter('woocommerce_shipping_methods', 'add_rb_custom_shipping_method');

  function rb_custom_validate_order($posted)
  {
    $packages = WC()->shipping->get_packages();
    $chosen_methods = WC()->session->get('chosen_shipping_methods');

    if (is_array($chosen_methods) && in_array('rb_custom_shipping', $chosen_methods)) {
      foreach ($packages as $i => $package) {

        if ($chosen_methods[$i] != "rb_custom_shipping_method") {
          continue;
        }

        $WC_RB_Custom_Shipping_Method = new WC_RB_Custom_Shipping_Method();
        $weightLimit = (int) $WC_RB_Custom_Shipping_Method->settings['weight'];
        $weight = 0;

        foreach ($package['contents'] as $item_id => $values) {
          $_product = $values['data'];
          $weight = $weight + $_product->get_weight() * $values['quantity'];
        }

        $weight = wc_get_weight($weight, 'kg');

        if ($weight > $weightLimit) {
          $message = sprintf(__('OOPS, %d kg increase the maximum weight of %d kg for %s', 'rb_custom_shipping'), $weight, $weightLimit, $WC_RB_Custom_Shipping_Method->title);
          $messageType = "error";

          if (!wc_has_notice($message, $messageType)) {
            wc_add_notice($message, $messageType);
          }

        }

      }
    }
  }

  add_action('woocommerce_review_order_before_cart_contents', 'rb_custom_validate_order', 10);
  add_action('woocommerce_after_checkout_validation', 'rb_custom_validate_order', 10);
}
