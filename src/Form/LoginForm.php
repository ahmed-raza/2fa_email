<?php

namespace Drupal\custom_login\Form;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\user\Entity\User;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Class LoginForm.
 */
class LoginForm extends FormBase {
  protected $messenger;
  protected $loggerFactory;
  private $tempStoreFactory;

  public function __construct(MessengerInterface $messenger, LoggerChannelFactoryInterface $logger_factory, PrivateTempStoreFactory $tempStoreFactory) {
    $this->messenger = $messenger;
    $this->loggerFactory = $logger_factory;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('logger.factory'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email'),
      '#description' => $this->t('Enter your profile email.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE
    ];
    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('Enter the password that accompanies your email.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#ajax' => [
        'callback' => '::ajaxFormCallback',
        'disable-refocus' => TRUE,
        'event' => 'click',
        'wrapper' => 'login-form',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Please wait...')
        ]
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      if ($key == 'email') {
        $valid_email = \Drupal::service('email.validator')->isValid($value);
        if (!$valid_email) {
          $form_state->setErrorByName('email', $this->t('Email is not valid, please enter a valid email address.'));
        }
        if ($valid_email && !user_load_by_mail($value)) {
          $form_state->setErrorByName('email', $this->t('Your email address or password is incorrect.'));
        }
      }
      if ($key == 'password') {
        $password_service = \Drupal::service('password');
        $mail = $form_state->getValue('email');
        $database = \Drupal::database();
        $results = $database->query("SELECT `pass` FROM `users_field_data` WHERE `mail`='$mail'");
        $result = $results->fetchAll();
        if (!$password_service->check($value, $result[0]->pass)) {
          $form_state->setErrorByName('email', $this->t('Your email address or password is incorrect.'));
        }
      }
    }
    parent::validateForm($form, $form_state);
  }

  public function ajaxFormCallback(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    if ($form_state->getErrors()) {
      unset($form['#prefix']);
      unset($form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $form_state->setRebuild();
      $response->addCommand(new ReplaceCommand('.login-form', $form));
      return $response;
    }

    $email = $form_state->getValue('email');
    $tempstore = $this->tempStoreFactory->get('custom_login_email');
    $tempstore->delete('email');
    try {
      $tempstore->set('email', $email);
    } catch (\Exception $error) {
      $this->loggerFactory->get('custom_login_email')->alert(t('@err', ['@err' => $error]));
      $this->messenger->addWarning(t('Unable to proceed, please try again.'));
    }

    $to           = \Drupal::service('tempstore.private')->get('custom_login_email')->get('email');
    $mailManager  = \Drupal::service('plugin.manager.mail');
    $module       = 'custom_login';
    $send         = true;
    $params['message'] = $this->t('Hello there, this is your Login Code: 675426');

    $mailManager->mail($module, 'custom_login_2fa', $to, 'en', $params, NULL, $send);

    $redirect_command = new RedirectCommand('/general-login/otp');
    $response->addCommand($redirect_command);
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
