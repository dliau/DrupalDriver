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
namespace Drupal\Driver\Remote;

use \Drupal\Driver\Remote\Client;

/**
 * Common API Interface fore drupalextension_remote based module.
 */
interface ApiInterface {
  public function __construct(Client $client);
  public function getPerPage();
  public function setPerPage($perPage);
}
