<?php

 /*
  * LLC Name Consulting
  * (c) All Right Reserved
  * admin@name.org.ua
  */

  namespace NAME;

  if(!defined('C_REST_WEB_HOOK_URL')) die();

  require_once(__DIR__.'/lib/crest.php');

  class Lead {

    private static function hook($hookName, $hookFields) {
      $result = \Bitrix\CRest::call($hookName, $hookFields);
      if(self::checkError($result)) die();
      return $result;
    }

    private static function result($result) {
      if($result && array_key_exists('result', $result) && $result['result']) return $result['result'];
      return false;
    }

    private static function checkError($result) {
      if(array_key_exists('error', $result) && !empty($result['error'])) {
        echo $result['error'];
        return true;
      }
      return false;
    }

    private static function findContact($phone) {
      $result = self::hook('crm.contact.list', [
        'filter' => [
          'PHONE' => $phone
        ],
        'select' => [
          'ID',
          'PHONE',
          'NAME',
          'LAST_NAME'
        ]
      ]);
      if(is_array($result['result']) && count($result['result'])) {
        foreach ($result['result'] as $contact) {
          if(is_array($contact['PHONE']) && count($result['result'])) {
            return $contact['ID'];
          }
        }
      }
      return false;
    }

    private static function newContact($name, $lastName, $secondName, $phoneNumber, $email) {
      $fields = [
        'NAME'           => $name,
        'LAST_NAME'      => $lastName,
        'SECOND_NAME'    => $secondName,
        'OPENED'         => 'Y',
        'ASSIGNED_BY_ID' => 1,
        "TYPE_ID"        => "CLIENT",
        "PHONE"          => [[
          'VALUE'        => $phoneNumber,
          'VALUE_TYPE'   => 'WORK'
        ]],
        "EMAIL"          => [[
          'VALUE'        => $email,
          'VALUE_TYPE'   => 'WORK'
        ]],
      ];
      $result =  self::hook('crm.contact.add', [
        'fields' => $fields
      ]);
      return self::result($result);
    }

    private static function findProduct($name) {
      $result = self::hook('crm.product.list', [
        'filter' => [
          'NAME' => $name
        ]
      ]);
      if(array_key_exists('total', $result) && $result['total']) {
        return $result['result'][0]['ID'];
      }
      return false;
    }

    private static function newProduct($product, $currencyID = 'UAH') {
      $result = self::hook('crm.product.add', [
        'fields' => [
          'NAME'        => $product['NAME'],
          'CURRENCY_ID' => $currencyID,
          'PRICE'       => $product['PRICE']
        ]
      ]);
      return self::result($result);
    }

    public static function new($title, $name, $lastName, $secondName, $phoneNumber, $email, $products = []) {
      $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
      $shortPhoneNumber = substr($phoneNumber, -10);
      $longPhoneNumber = '38'.$shortPhoneNumber;
      $contactID = self::findContact($longPhoneNumber) || self::findContact($shortPhoneNumber);
      if(!$contactID) {
        $contactID = self::newContact($name, $lastName, $secondName, $longPhoneNumber, $email);
      }
      $result = self::hook('crm.lead.add', [
        'fields' => [
          'TITLE'       => $title,
          'CONTACT_ID'  => $contactID
        ]
      ]);
      $leadID = self::result($result);

      if(!$leadID) return;
      
      $productrows = [];
      foreach ($products as &$product) {
        if(array_key_exists('NAME', $product)) {
          if(array_key_exists('PRICE', $product)) {
            if(array_key_exists('QUANTITY', $product)) {
              $productID = self::findProduct($product['NAME']);
              if(!$productID) {
                $productID = self::newProduct($product);
              }
              $product['PRODUCT_ID'] = $productID;
              unset($product['NAME']);
              $productrows[] = $product;
            }
          }
        }
      }

      return self::hook('crm.lead.productrows.set', [
        'id'   => $leadID,
        'rows' => $productrows
      ]);
    }

  }

?>