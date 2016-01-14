<?php

/**
 * @file
 * Contains \Drupal\Driver\DrupalRemoteDriver.
 *
 * This driver is based on the work of Nathan Kirschbaum and Alfred Nutile
 * https://github.com/kirschbaum/drupal-behat-remote-api-driver.
 *
 * Additional changes courtesy of Unipro LTD / Daniel Munn.
 */
namespace Drupal\Driver;

use Drupal\Driver\Remote\Api;
use Drupal\Component\Utility\Random;
use Drupal\Driver\Exception\BootstrapException;
use Drupal\Driver\Remote\Client;

class RemoteDriver extends BaseDriver implements DriverInterface {
  /**
   * @var DrupalRemoteClient
   */
  private $remote_client;

  /**
   * @var Random
   */
  private $random;

  /**
   * @var Remote Site Username
   */
  private $login_username;

  /**
   * @var Remote Site Password
   */
  private $login_password;

  /**
   * @var Remote Site URL
   */
  private $remote_site_url;

  /**
   * @var DrupalFilterFormat
   */
  private $drupal_filter_format;

  /**
   * @var Request Cookie
   */
  private $request_cookie;

  /**
   * @var bool Has class been bootstrapped?
   */
  protected $bootstrapped = FALSE;

  /**
   * Ultimately sets connection parameters for remote connection.
   *
   * @param string $base_url
   *   The base url of the site - eg: http://192.168.44.44/drupal.
   * @param string $login_username
   *   The username we will be connecting as.
   * @param string $login_password
   *   The password for the account being connected to.
   * @param string $request_cookie
   *   Request cookie?
   * @param string $remote_client
   *   Ability to change (extend) the connection client.
   * @param \Drupal\Component\Utility\Random|NULL $random
   *   Random Generator.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when a required parameter is missing.
   */
  public function __construct($base_url = NULL, $login_username = NULL, $login_password = NULL, $request_cookie = NULL, $remote_client = NULL, Random $random = NULL) {
    if (empty($base_url)) {
      throw new BootstrapException('A site base url is required.');
    }
    $this->remote_site_url = $base_url;
    $this->login_username = $login_username;
    $this->login_password = $login_password;
    $this->request_cookie = $request_cookie;
    $this->remote_client = $remote_client;

    // Ensure there is always something random.
    if (!isset($random)) {
      $random = new Random();
    }
    $this->random = $random;
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom() {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    if (!$this->remote_client || is_string($this->remote_client)) {
      $remote_class = $this->remote_client ? $this->remote_client : '\Drupal\Driver\Remote\Client';
      $drupalRemoteClient = new $remote_class();
      $drupalRemoteClient->setOption('base_url', $this->remote_site_url);
      $drupalRemoteClient->authenticate($this->login_username, $this->login_password, 'http_drupal_login', $this->request_cookie);
      $this->remote_client = $drupalRemoteClient;
    }
    $this->bootstrapped = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped() {
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user, array $config = array()) {
    try {
      return $this->api('user')->userCreate($user, $config);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user, array $config = array()) {
    try {
      return $this->api('user')->userDelete($user, $config);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role) {
    try {
      return $this->api('user')->userAddRole($user, $role);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    try {
      return $this->api('watchdog')->fetchWatchdog($count, $type, $severity);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($type = NULL) {
    try {
      return $this->api('cache')->clearCache($type);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * Set the remote API client.
   *
   * @param \Drupal\Driver\Remote\Client $client
   */
  public function setClient(Client $client) {
    $this->remote_client = $client;
  }

  /**
   * Retrieve the Client for communication.
   * @return \Drupal\Driver\DrupalRemoteClient
   */
  public function getClient() {
    return $this->remote_client;
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    // Be very very quiet.
  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    // TODO: How does this get handled?
    // This is needed for afterScenerio() cleanup.
    // Seems required to cleanup multiple users.
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    try {
      return $this->api('cron')->runCron();
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createNode($node, array $config = array()) {
    try {
      return $this->api('node')->createNode($node, $config);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    try {
      $this->api('node')->deleteNode($node);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term, array $config = array()) {
    try {
      return $this->api('term')->termCreate($term);
    }
    catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    try {
      $this->api('term')->termDelete($term);
    } catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }
  }

  public function api($api_name) {
    return $this->getClient()->api($api_name);
  }
}
