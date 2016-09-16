<?php
/**
 * @file
 * Defines the Drupal\Driver\Remote\Api\BaseDrupalRemoteAPI class.
 *
 * This driver is based on the work of Nathan Kirschbaum and Alfred Nutile
 * https://github.com/kirschbaum/drupal-behat-remote-api-driver.
 *
 * Additional changes courtesy of Unipro LTD / Daniel Munn.
 */
namespace Drupal\Driver\Remote\Api;

use Drupal\Driver\Remote\Exception\DrupalResponseCodeException;
use Drupal\Driver\Remote\Exception\DrupalResponseException;

/**
 * Class BaseDrupalRemoteAPI
 *
 * @package Drupal\Driver\Remote\Api
 */
class BaseDrupalRemoteAPI extends AbstractApi {

  /**
   * Confirms that the status response is 200 (OK).
   *
   * @param $response
   *   Response Object
   *
   * @throws \Drupal\Driver\Remote\Exception\DrupalResponseCodeException
   */
  protected function confirmResponseStatusCodeIs200($response) {
    // Checking for response ID because RestWS does not return status code.
    // @TODO Add status code to RestWS response.
    if(!isset($response['id']) && isset($response['response_code']) && $response['response_code'] != 200){
      throw new DrupalResponseCodeException(sprintf('Remote API Exception: %s', $response['message']));
    }
  }

  /**
   * Confirms that the restws is returning a filter list.
   *
   * @param $response
   *   Response Object
   *
   * @throws \Drupal\Driver\Remote\Exception\DrupalResponseCodeException
   */
  protected function confirmRestWSFilterResponse($response) {
    if(!isset($response['list'])){
      throw new DrupalResponseCodeException(sprintf('Remote API Exception: RestWS filter list not present: %s', $response));
    }
  }

  /**
   * Confirms deletion in response, or throws an exception.
   *
   * @param $result
   *
   * @throws \Drupal\Driver\Remote\Exception\DrupalResponseException
   */
  protected function confirmDeletedResponse($result) {
    if($result != array()){
      throw new DrupalResponseException(sprintf('Remote API Exception: Deletion has failed: %s', $result));
    }
  }

}
