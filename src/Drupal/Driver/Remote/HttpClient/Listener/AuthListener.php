<?php

/**
 * @file
 * Defines Drupal\Driver\Remote\HttpClient\Listener
 *
 * This driver is based on the work of Nathan Kirschbaum and Alfred Nutile
 * https://github.com/kirschbaum/drupal-behat-remote-api-driver.
 *
 * Additional changes courtesy of Unipro LTD / Daniel Munn.
 */

namespace Drupal\Driver\Remote\HttpClient\Listener;

use Guzzle\Common\Event;
use Drupal\Driver\Remote\Client;
/**
 * Class AuthListener
 *
 * @package Drupal\Driver\Remote\HttpClient\Listener
 */
class AuthListener  {
  /**
   * @var string
   */
  private $tokenOrLogin;

  /**
   * @var null|string
   */
  private $password;

  /**
   * @var string
   */
  private $method;

  /**
   * @var string
   */
  private $requestCookie;

  /**
   * Create a new Authentication provider.
   *
   * @param string $tokenOrLogin
   *   Login information.
   * @param string $password
   *   Password (for login).
   * @param $method
   *   Method of authentication.
   * @param null $requestCookie
   *   Cookie information if required.
   */
  public function __construct($tokenOrLogin, $password = null, $method, $requestCookie = null) {
    $this->tokenOrLogin = $tokenOrLogin;
    $this->password = $password;
    $this->method = $method;
    $this->requestCookie = $requestCookie;
  }

  /**
   * Authentication adaptation of request (adds headers).
   *
   * @param \Guzzle\Common\Event $event
   *   The event being raised.
   */
  public function onRequestBeforeSend(Event $event) {
    // Skip by default
    if (null === $this->method) {
      return;
    }
    switch ($this->method) {

      case Client::AUTH_HTTP_PASSWORD:
        $event['request']->setHeader(
          'Authorization',
          sprintf('Basic %s', base64_encode($this->tokenOrLogin . ':' . $this->password))
        );
        break;

      case Client::AUTH_HTTP_TOKEN:
        $event['request']->setHeader('Authorization', sprintf('token %s', $this->tokenOrLogin));
        break;

      case Client::AUTH_URL_CLIENT_ID:
        $url = $event['request']->getUrl();

        $parameters = array(
          'client_id'     => $this->tokenOrLogin,
          'client_secret' => $this->password,
        );

        $url .= (false === strpos($url, '?') ? '?' : '&');
        $url .= utf8_encode(http_build_query($parameters, '', '&'));

        $event['request']->setUrl($url);
        break;

      case Client::AUTH_URL_TOKEN:
        $url = $event['request']->getUrl();
        $url .= (false === strpos($url, '?') ? '?' : '&');
        $url .= utf8_encode(http_build_query(array('access_token' => $this->tokenOrLogin), '', '&'));

        $event['request']->setUrl($url);
        break;

      case Client::AUTH_HTTP_DRUPAL:
        $event['request']->setHeader(
          'Drupal-Auth',
          base64_encode($this->tokenOrLogin . ':' . $this->password)
        );
        $this->addOptionalRequestCookie($event);
        break;

      default:
        throw new \RuntimeException(sprintf('%s not yet implemented', $this->method));
        break;
    }
  }

  /**
   * Internal function to add cookie header to request.
   *
   * @param \Guzzle\Common\Event $event
   */
  private function addOptionalRequestCookie(Event $event) {
    if (isset($this->requestCookie)) {
      $event['request']->setHeader('Cookie', $this->requestCookie);
    }
  }
}
