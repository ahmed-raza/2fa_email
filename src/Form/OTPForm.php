<?php

namespace Drupal\custom_login\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class OTPForm.
 */
class OTPForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'o_t_p_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('custom_login_email');
    $form['otp'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OTP'),
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
      '#value' => $tempstore->get('email') ?? $tempstore->get('email')
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // \Drupal::messenger()->addMessage($key.' : '.$value);
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('custom_login_email');
    $account = user_load_by_mail($tempstore->get('email'));
    $tempstore->delete('email');
    user_login_finalize($account);
  }

}
