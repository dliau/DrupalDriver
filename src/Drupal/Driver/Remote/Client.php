<?php
/**
 * @file
 * Defines the Drupal\Driver\Remote\Client class.
 *
 * This driver is based on the work of Nathan Kirschbaum and Alfred Nutile
 * https://github.com/kirschbaum/drupal-behat-remote-api-driver.
 *
 * Additional changes courtesy of Unipro LTD / Daniel Munn.
 */
namespace Drupal\Driver\Remote;

use Drupal\Driver\Remote\ApiInterface;
use Drupal\Driver\Remote\HttpClient\HttpClient;
use Drupal\Driver\Remote\HttpClient\HttpClientInterface;
use Drupal\Driver\Remote\HttpClient\Listener\LoggingListener;

/**
 * Simple yet very cool PHP GitHub client
 *
 * @original_author Joseph Bielawski <stloyd@gmail.com>
 *
 * Website: http://github.com/KnpLabs/php-github-api
 */
class Client {
  /**
   * Constant for authentication method. Indicates the default, but deprecated
   * login with username and token in URL.
   */
  const AUTH_URL_TOKEN = 'url_token';

  /**
   * Constant for authentication method. Not indicates the new login, but allows
   * usage of unauthenticated rate limited requests for given client_id + client_secret
   */
  const AUTH_URL_CLIENT_ID = 'url_client_id';

  /**
   * Constant for authentication method. Indicates the new favored login method
   * with username and password via HTTP Authentication.
   */
  const AUTH_HTTP_PASSWORD = 'http_password';

  /**
   * Constant for authentication method. Indicates the new login method with
   * with username and token via HTTP Authentication.
   */
  const AUTH_HTTP_TOKEN = 'http_token';

  /**
   * Constant for authentication method. Indicates the new login method with
   * with username and token via custom HTTP header.
   */
  const AUTH_HTTP_DRUPAL = 'http_drupal_login';

  /**
   * @var array
   */
  private $options = array(
    'base_url'    => 'https://some-url.com/',
    'user_agent'  => 'drupal-behat-remote-api-driver (https://github.com/kirschbaum/drupal-behat-remote-api-driver)',
    'timeout'     => 10,
    'api_limit'   => 5000,
    'api_version' => 'v1',
    'curl.options' => [
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_SSL_VERIFYPEER => false,
    ],
    'cache_dir'   => null
  );

  /**
   * @var \Drupal\Driver\Remote\HttpClient\HttpClientInterface|null
   */
  private $httpClient;

  /**
   * Instantiate a new client.
   *
   * @param null|HttpClientInterface $httpClient http client
   */
  public function __construct(HttpClientInterface $httpClient = null) {
    $this->httpClient = $httpClient;
  }

  /**
   * FACTORY: Return the correct API Handler based on api type.
   *
   * @param string $name
   *   The name of the api section, eg "user".
   *
   * @return ApiInterface
   *   Returns an API Oject based on the ApiInterface.
   *
   * @throws InvalidArgumentException
   *   API executor not available.
   */
  public function api($name) {
    switch ($name) {
      case 'node':
      case 'nodes':
        $api = new Api\Node($this);
        break;
      case 'term':
      case 'terms':
        $api = new Api\Term($this);
        break;
      case 'user':
      case 'users':
        $api = new Api\User($this);
        break;
      case 'cache':
        $api = new Api\Cache($this);
        break;
      default:
        if (class_exists($name)) {
          // TODO: Can we use reflection here.
          $api = new $name($this);
          if (!($api instanceof ApiInterface)) {
            // Well that was a waste.
            $api = NULL;
          }
        }
    }

    if (!$api) {
      throw new \InvalidArgumentException(sprintf('Undefined api instance called: "%s"', $name));
    }

    return $api;
  }

  /**
   * Authenticate a user for all requests.
   *
   * @param string $tokenOrLogin GitHub private token/username/client ID
   * @param null|string $password GitHub password/secret (optionally can contain $authMethod)
   * @param null|string $authMethod One of the AUTH_* class constants
   * @param null $requestCookie
   *
   * @throws InvalidArgumentException
   */
  public function authenticate($tokenOrLogin, $password = null, $authMethod = null, $requestCookie = null) {
    if (null === $password && null === $authMethod) {
      throw new InvalidArgumentException('You need to specify authentication method!');
    }

    if (null === $authMethod && in_array($password, array(self::AUTH_URL_TOKEN, self::AUTH_URL_CLIENT_ID, self::AUTH_HTTP_PASSWORD, self::AUTH_HTTP_TOKEN))) {
      $authMethod = $password;
      $password   = null;
    }

    if (null === $authMethod) {
      $authMethod = self::AUTH_HTTP_PASSWORD;
    }

    $this->getHttpClient()->authenticate($tokenOrLogin, $password, $authMethod, $requestCookie);
  }

  /**
   * {@inheritDoc}
   */
  public function logging() {
    $this->getHttpClient()->addListener('request.before_send', array(
      new LoggingListener(), 'onRequestBeforeSend'
    ));
  }

  /**
   * Return the HTTP Client.
   *
   * @return \Drupal\Driver\Remote\HttpClient\HttpClientInterface|null
   */
  public function getHttpClient()
  {
    if (null === $this->httpClient) {
      $this->httpClient = new HttpClient($this->options);
    }
    return $this->httpClient;
  }

  /**
   * Set the HTTP Client.
   *
   * @param HttpClientInterface $httpClient
   */
  public function setHttpClient(HttpClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * Clears used headers.
   */
  public function clearHeaders() {
    $this->getHttpClient()->clearHeaders();
  }

  /**
   * Retrieve headers from request.
   *
   * @return array
   */
  public function getHeaders() {
    $this->getHttpClient()->getHeaders();
  }

  /**
   * Set headers on the request.
   *
   * @param array $headers
   */
  public function setHeaders(array $headers)
  {
    $this->getHttpClient()->setHeaders($headers);
  }

  /**
   * Retrieve options (by name) on request.
   *
   * @param string $name
   *
   * @return mixed
   *
   * @throws \InvalidArgumentException
   */
  public function getOption($name) {
    if (!array_key_exists($name, $this->options)) {
      throw new \InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
    }

    return $this->options[$name];
  }

  /**
   * Set an option on the request.
   *
   * @param string $name
   * @param mixed $value
   *
   * @throws \InvalidArgumentException
   */
  public function setOption($name, $value) {
    if (!array_key_exists($name, $this->options)) {
      throw new \InvalidArgumentException(sprintf('Undefined option called: "%s"', $name));
    }
    $supportedApiVersions = $this->getSupportedApiVersions();
    if ('api_version' == $name && !in_array($value, $supportedApiVersions)) {
      throw new \InvalidArgumentException(sprintf('Invalid API version ("%s"), valid are: %s', $name, implode(', ', $supportedApiVersions)));
    }

    $this->options[$name] = $value;
  }

  /**
   * Returns an array of valid API versions supported by this client.
   *
   * @return array
   */
  public function getSupportedApiVersions() {
    return array('v1');
  }

  public function getBasePath() {
    return '/api/' . $this->options['api_version'];
  }
}
