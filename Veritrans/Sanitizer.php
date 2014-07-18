<?php

class Veritrans_Sanitizer {
  private $filters;

  public function __construct()
  {
    $this->filters = array();
  }

  public static function jsonRequest(&$json)
  {
    $keys = array('item_details', 'customer_details');
    foreach ($keys as $key) {
      if (!array_key_exists($key, $json)) continue;

      $camel = static::upperCamelize($key);
      $function = "field$camel";
      static::$function($json[$key]);
    }
  }

  private static function fieldItemDetails(&$items)
  {
    foreach ($items as &$item) {
	  $temp = (new self);
	  $temp = $temp -> maxLength(50);
	  $temp = $temp -> apply($item['id']);
	  $item['id'] = $temp;
	  
	  $temp = (new self);
	  $temp = $temp -> maxLength(50);
	  $temp = $temp -> apply($item['name']);
	  $item['name'] = $temp;
    }
  }

  private static function fieldCustomerDetails(&$field)
  {
	$temp = (new self);
	$temp = $temp -> maxLength(20);
	$temp = $temp -> apply($field['first_name']);;
    $field['first_name'] = $temp;
	
    if (array_key_exists('last_name', $field)) {
		$temp = (new self);
		$temp = $temp -> maxLength(20);
		$temp = $temp -> apply($field['last_name']);;
		$field['last_name'] = $temp;
    }
	$temp = (new self);
	$temp = $temp -> maxLength(45);
	$temp = $temp -> apply($field['email']);;
    $field['email'] = $temp;

    static::fieldPhone($field['phone']);

    $keys = array('billing_address', 'shipping_address');
    foreach ($keys as $key) {
      if (!array_key_exists($key, $field)) continue;

      $camel = static::upperCamelize($key);
      $function = "field$camel";
      static::$function($field[$key]);
    }
  }

  private static function fieldBillingAddress(&$field)
  {
    $fields = array(
        'first_name'   => 20,
        'last_name'    => 20,
        'address'      => 200,
        'city'         => 20,
        'country_code' => 10
      );

    foreach ($fields as $key => $value) {
      if (array_key_exists($key, $field)) {
		$temp = (new self);
		$temp = $temp -> maxLength($value);
		$temp = $temp -> apply($field[$key]);;
		$field[$key] = $temp;
      }
    }

    if (array_key_exists('postal_code', $field)) {
		$temp = (new self);
		$temp = $temp -> whitelist('A-Za-z0-9\\- ');
		$temp = $temp -> maxLength(10);
		$temp = $temp -> apply($field['postal_code']);;
		$field['postal_code'] = $temp;
    }
    if (array_key_exists('phone', $field)) {
      static::fieldPhone($field['phone']);
    }
  }

  private static function fieldShippingAddress(&$field)
  {
    static::fieldBillingAddress($field);
  }

  private static function fieldPhone(&$field)
  {
    $plus = substr($field, 0, 1) === '+' ? true : false;
	$temp = (new self);
	$temp = $temp -> whitelist('\\d\\-\\(\\) ');
	$temp = $temp -> maxLength(19);
	$temp = $temp -> apply($field);
    $field = $temp;

    if ($plus) $field = '+' . $field;
    $temp = (new self);
	$temp = $temp -> maxLength(19);
	$temp = $temp -> apply($field);
    $field = $temp;
  }

  private function maxLength($length)
  {
    $this->filters[] = function($input) use($length) {
      return substr($input, 0, $length);
    };
    return $this;
  }

  private function whitelist($regex)
  {
    $this->filters[] = function($input) use($regex) {
      return preg_replace("/[^$regex]/", '', $input);
    };
    return $this;
  }

  private function apply($input)
  {
    foreach ($this->filters as $filter) {
      $input = call_user_func($filter, $input);
    }
    return $input;
  }

  private static function upperCamelize($string)
  {
    return str_replace(' ', '',
        ucwords(str_replace('_', ' ', $string)));
  }
}