<?php
/**
 * @file
 * Defines the Drupal\Driver\Remote\HttpClient\Message\ResponseMediator class.
 *
 * This driver is based on the work of Nathan Kirschbaum and Alfred Nutile
 * https://github.com/kirschbaum/drupal-behat-remote-api-driver.
 *
 * Additional changes courtesy of Unipro LTD / Daniel Munn.
 */
namespace Drupal\Driver\Remote\HttpClient\Message;

use Guzzle\Http\Message\Response;
use Drupal\Driver\Remote\Exception\ApiLimitExceedException;

class ResponseMediator {

  /**
   * Retrieve the body from the request (JSON or plain).
   *
   * @param \Guzzle\Http\Message\Response $response
   *   Response object.
   *
   * @return \Guzzle\Http\EntityBodyInterface|mixed|string
   *   Representation of response payload.
   */
  public static function getContent(Response $response) {
    $body    = $response->getBody(true);
    $content = json_decode($body, true);

    if (JSON_ERROR_NONE !== json_last_error()) {
      return $body;
    }
    return $content;
  }

  /**
   * Identify any pagination in response.
   *
   * @param \Guzzle\Http\Message\Response $response
   *   Response object.
   *
   * @return array|null
   *   Pagination information.
   */
  public static function getPagination(Response $response)  {
    $header = $response->getHeader('Link');

    if (empty($header)) {
      return null;
    }

    $pagination = array();
    foreach (explode(',', $header) as $link) {
      preg_match('/<(.*)>; rel="(.*)"/i', trim($link, ','), $match);

      if (3 === count($match)) {
        $pagination[$match[2]] = $match[1];
      }
    }

    return $pagination;
  }

  /**
   * Retrieve the API Limit (if any).
   *
   * @param \Guzzle\Http\Message\Response $response
   *   Response object.
   *
   * @throws \Drupal\Driver\Remote\Exception\ApiLimitExceedException
   *   Exception if API limit has been exceeded.
   */
  public static function getApiLimit(Response $response)  {
    $remainingCalls = $response->getHeader('X-RateLimit-Remaining');

    if (null !== $remainingCalls && 1 > $remainingCalls) {
      throw new ApiLimitExceedException($remainingCalls);
    }
  }
}
