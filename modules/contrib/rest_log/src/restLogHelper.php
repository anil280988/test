<?php

namespace Drupal\rest_log;

use Psy\Exception\ErrorException;
use Drupal\Component\Serialization\Json;
class restLogHelper {

  public static function request($method, $uri = '', array $options = []) {

    $request = \Drupal::request();

    $headers = $request->headers->all();
    foreach($headers as $headerKey => $header) {
      if (is_array($header) && count($header) === 1) {
        $headers[$headerKey] = $header[0];
      }
    }

    $log = [
      'request_header' => Json::encode($headers),
      'request_method' => $method,
      'request_uri' => $uri,
      'request_payload' => Json::encode($options),
    ];

    try {
      // get request content.
      $response = \Drupal::httpClient()->request($method, $uri, $options)
        ->getBody()->getContents();
      $log['response_body'] = $response;
      \Drupal::entityTypeManager()->getStorage('rest_log')->create($log)->save();
      return $response;

    } catch(ErrorException $error) {

      $log['response_body'] = '';
      $log['error_message'] = $error->getMessage();
      $log['error_code'] = $error->getCode();
      \Drupal::entityTypeManager()->getStorage('rest_log')->create($log)->save();
    }
  }

}