<?php

namespace Drupal\custom_login\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class LoginController.
 */
class LoginController extends ControllerBase {

  /**
   * Login.
   *
   * @return string
   *   Return Hello string.
   */
  public function login() {
    return [
      '#theme'   => 'custom-login',
      '#form' => \Drupal::formBuilder()->getForm(\Drupal\custom_login\Form\LoginForm::class)
    ];
  }

}
