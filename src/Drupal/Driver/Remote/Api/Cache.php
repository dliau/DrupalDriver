<?php namespace Drupal\Driver\Remote\Api;

class Cache extends BaseDrupalRemoteAPI {

  /**
   * Initiate a remote cache clear.
   *
   * @throws \Drupal\Driver\Remote\Exception\DrupalResponseCodeException
   *   When there was an error clearing the cache.
   */
  public function clearCache() {
    $response = $this->get('/drupal-remote-api/cache');
    $this->confirmResponseStatusCodeIs200($response);
  }

}
