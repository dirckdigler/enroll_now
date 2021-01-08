<?php

/**
 * @file
 * Contains \Drupal\enroll_now\Form\Multistep\CheckYourEligibility.
 */

namespace Drupal\enroll_now\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\enroll_now\ValidateEnrollProcess;
use Webmozart\Assert\Assert;

class CheckYourEligibility extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'enroll_now_check_your_eligibility';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Message error inline validation.
    $requiredFieldMessage = $this->t('The field is required');
    $maxLengthMessage = $this->t('You have reached maximum limit of 50 characters');
    $idValidationMessages = [
      'data-too-long' => $maxLengthMessage,
      'data-pattern-mismatch' => $this->t('Please remove text or special characters'),
      'data-value-missing' => $requiredFieldMessage,
      'data-too-short' => $this->t('Should be a 10-digit number'),
    ];
    $emailValidationMessages = [
      'data-value-missing' => $requiredFieldMessage,
      'data-pattern-mismatch' => $this->t('Please enter valid email address'),
    ];
    $isNumeric = '^\d+$';

    // Flag to validate if it's a current URL form.
    $flag_access = $this->store->get('access');
    $flag_param = isset($_GET['iv']) ? TRUE : FALSE;
    // Depending the flag, remove data session.
    if ($flag_param == FALSE || $flag_access == FALSE) {
      $manage_session_data = \Drupal::service('enroll_now.manage_session_data');
      $manage_session_data->deleteSessionStore();
    }
    $this->store->set('access', FALSE);

    $form['step'] = [
      '#type' => 'hidden',
      '#value' => 1,
    ];
    
    // Retrieve enroll_now.settings form to first step.
    $description = NULL !== $this->config->get('first_step_body') ? 
      $this->config->get('first_step_body') : '';
    $form['label'] = [
      '#prefix' => '<div class="content-top-description"><div class="title-description"><h2>' .
        $this->t("Let’s check your eligibility") . '</h2>',
      '#markup' => '<div class="description-page">' .
        $this->t($description) . '</div></div>',
      '#suffix' => '</div>'
    ];
    $form['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('phone_number') ?
        $this->store->get('phone_number') : '',
      '#maxlength' => 10,
      '#pattern' => $isNumeric,
      '#required' => TRUE,
      '#attributes' => array_merge($idValidationMessages, [
        'class' => ['with-tooltip'],
        'minlength' => ['10'],
      ]),
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('email') ?
        $this->store->get('email') : '',
      '#maxlength' => 254,
      '#pattern' => '[a-zA-Z0-9][\w\-\.]{0,}[\w]{1}@[a-zA-Z0-9][\w\-]{1,}\.[a-z]{2,4}(\.[a-z]{2})?',
      '#attributes' => array_merge(
        ['autocomplete' => ['new-password']],
        $emailValidationMessages
      ),
      '#required' => TRUE,
    ];

    $internal_link = '<p>' .
      t('I agree with <a data-toggle="modal" data-target="#triggerPersonalData" 
        href="@administer-page">the processing of personal data</a>', [
        '@administer-page' => '#',
      ]) . '</p>';

    $form['check'] = [
      '#title' => t(""),
      '#default_value' => $this->store->get('check') ?
        $this->store->get('check') : '',
      '#type' => 'checkbox',
      '#suffix' => $internal_link,
      '#required' => TRUE,
    ];

    $form['#theme'] = 'enroll_now_check_your_eligibility';
    $form['#attributes'] = [
      'novalidate' => ['true'],
      'class' => ['form-with-validations'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check eligibility'),
      '#attributes' => [
        'disabled' => ['true'],
      ]
    ];

    return $form;

  }

  /**
   * {@inheritdoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // initialize flag error variable.
    $error = FALSE;
    // Validate Phone number.
    $min_numbers = 10;
    try {
      // Using 'Webmozart Assert' library to test the input Phone number,
      // reducing amount of coding.
      Assert::minLength($form_state->getValue('phone_number'), $min_numbers);
    } 
    catch (\InvalidArgumentException $e) {
      \Drupal::logger('enroll_now')->error($e->getMessage());
      $form_state->setErrorByName('phone_number',
      $this->t('Expected a value to contain at least %min numbers. Got: %phone_number ', [
        '%min' => $min_numbers, 
        '%phone_number' => $form_state->getValue('phone_number')
      ]));
    }
    // Validate email.
    $email = trim($form_state->getValue('email'));
    $email_validate = trim($form_state->getValue('email_validate'));
    if (!$this->emailValidator->isValid($email)) {
      $error = TRUE;
      $form_state->setErrorByName('emailaddress',
      $this->t('%emailaddress invalid format for e-email address.',
        ['%emailaddress' => $email]
      ));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('access', TRUE);
    $this->store->set('phone_number', $form_state->getValue('phone_number'));
    $this->store->set('email_original', $form_state->getValue('email'));
    // Check through the 'enroll_now.check_member_eligibility' controller, 
    // if member is elegibility.
    $form_state->setRedirect('enroll_now.check_member_eligibility');
  }

}
