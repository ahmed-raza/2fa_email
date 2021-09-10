<?php

namespace Drupal\custom_login\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class LoginRedirectSubscriber.
 */
class LoginRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new LoginRedirectSubscriber object.
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['loginRedirect'];
    return $events;
  }

  /**
   * This method is called when the login_redirect is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function loginRedirect(GetResponseEvent $event) {
    $current_path = \Drupal::service('path.current')->getPath();
    $logged_in = \Drupal::currentUser()->isAuthenticated();

    if (($current_path == '/general-login' || $current_path == '/general-login/otp') && $logged_in) {
      $redirect = new RedirectResponse('/user');
      return $redirect->send();
    }
  }

}
