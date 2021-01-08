<?php

/**
 * @file
 * Contains \Drupal\enroll_now\Form\Multistep\MyPersonalInfo.
 */
namespace Drupal\enroll_now\Form\Multistep;
use Drupal\Core\Form\FormStateInterface;
use Drupal\enroll_now\ValidateEnrollProcess;
use Drupal\Core\Url;

class MyPersonalInfo extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'enroll_now_my_personal_info';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // Message error inline validation.
    $requiredFieldMessage = $this->t('The field is required');
    $maxLengthMessage = $this->t('You have reached maximum limit of 50 characters');
    $commonInputValidationMessages = [
      'data-too-long' => $maxLengthMessage,
      'data-pattern-mismatch' => $this->t('Please remove numbers or special characters'),
      'data-value-missing' => $requiredFieldMessage,
    ];
    $idValidationMessages = [
      'data-too-long' => $maxLengthMessage,
      'data-pattern-mismatch' => $this->t('Please remove any special characters'),
      'data-value-missing' => $requiredFieldMessage,
    ];
    $requiredInputValidationMessages = [
      'data-value-missing' => $requiredFieldMessage,
    ];
    $emailValidationMessages = [
      'data-value-missing' => $requiredFieldMessage,
      'data-pattern-mismatch' => $this->t('Please enter valid email address'),
    ];
    $noSpecialCharactersPattern = '[a-zA-ZñÑáéíóúÁÉÍÓÚ\' ]+';
    $idNumberPattern = '[a-zA-Z0-9]+';   

    $form['step'] = [
      '#type' => 'hidden',
      '#value' => 2,
    ];
    // Is not working properly with the propertie #url,
    // using #markup as solution to render anchor link.
    // @see https://www.drupal.org/project/webform/issues/2884372.
    $previous_step_link = Url::fromRoute('enroll_now.check_your_eligibility', 
      ['iv' => TRUE])->toString();
    $form['actions']['previous'] = [
      '#type' => 'link',
      '#attributes' => [
        'class' => ['enroll-previous-button'],
      ],
      '#markup' => '<a href=" ' . $previous_step_link . '" 
        class="enroll-previous-button" data-drupal-selector="edit-previous" 
        id="edit-previous"></a>',
    ];
    $description = NULL !== $this->config->get('second_step_body') ? 
      $this->config->get('second_step_body') : '';
    
    $fist_name = NULL !== $this->store->get('name') ? 
      $this->store->get('name') : '';
    
    $form['label'] = [
      '#type' => 'link',
      '#prefix' => '<div class="content-top-description"><div class="title-description"><h2>' .
        $this->t("Hello, " . $fist_name) . '</h2>',
      '#markup' => '<div class="description-page">' .
        $this->t($description) . '</div></div>',
      '#suffix' => '</div>'
    ];
    $form['my_insurance'] = [
      '#prefix' => '<div class="category-section">',
      '#markup' => '<h3>' . $this->t('My LD Extras info') . '</h3>',
      '#suffix' => '</div>'
    ];
    $form['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('phone_number'),
      '#attributes' => [
        'readonly' => 'readonly',
        'class' => ['field_readonly'],
        'disabled' => 'disabled',
      ],
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('email'),
      '#attributes' => [
        'readonly' => 'readonly',
        'class' => ['field_readonly'],
        'disabled' => 'disabled',
      ],
    ];
    $form['personal_info'] = [
      '#prefix' => '<div class="category-section">',
      '#markup' => '<h3>' . $this->t('My personal info') . '</h3>',
      '#suffix' => '</div>'
    ];
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $fist_name,
      '#title_display' => 'after',
      '#maxlength' => 50,
      '#pattern' => $noSpecialCharactersPattern,
      '#required' => TRUE,
      '#attributes' => $commonInputValidationMessages,
    ];
    $form['lastname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('lastname'),
      '#maxlength' => 50,
      '#pattern' => $noSpecialCharactersPattern,
      '#required' => TRUE,
      '#attributes' => $commonInputValidationMessages,
    ];
    // Validate if birthday value exist to convert it
    // with format correct, to populate the field, 
    // something like '01/30/1990'.
    $birthdate = NULL !== $this->store->get('birthdate') ? 
      $this->store->get('birthdate') : '';
    if (!empty($birthdate)) {
      $birthdate = ValidateEnrollProcess::convertDateFormat(
        $birthdate, 'm-d-Y'
      );
    }
    $form['birthdate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Birth date'),
      '#title_display' => 'after',
      '#default_value' => $birthdate,
      '#required' => TRUE,
      '#pattern' => '^\d{1,2}\/\d{1,2}\/\d{4}$',
      '#attributes' => [
        'class' => ['datepicker'],
        'data-custom-error' => $this->t('Date should be in the past'),
        'data-pattern-mismatch' => 'mm/dd/yyyy',
        'data-value-missing' => $requiredFieldMessage,
      ],
    ];
    // Get first character gender value 
    // and convert in uppercase the string.
    $gender = NULL !== $this->store->get('sex') ? 
      $this->store->get('sex') : '';
    if (!empty($gender)) {
      $gender = strtoupper(mb_substr($gender, 0, 1, "UTF-8"));
    }
    $form['sex'] = [
      '#type' => 'select',
      '#title' => $this->t('Sex'),
      '#title_display' => 'after',
      '#default_value' => $gender,
      '#options' => [
        'M' => $this->t('Male'),
        'F' => $this->t('Female'),
      ],
     '#required' => TRUE,
     '#attributes' => [
       'data-value-missing' => $requiredFieldMessage,
      ],
    ];
    // Get all terms of the vocabulary country.
    $options_country = ValidateEnrollProcess::getTermsVocabulary('country');
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Country'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('country'),
      '#options' => $options_country,
      '#required' => TRUE,
      '#attributes' => [
        'data-value-missing' => $requiredFieldMessage,
      ],
    ];
    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#title_display' => 'after',
      '#default_value' => $this->store->get('city'),
      '#maxlength' => 50,
      '#pattern' => $noSpecialCharactersPattern,
      '#required' => TRUE,
      '#attributes' => $commonInputValidationMessages,
    ];
    $form['#theme'] = 'enroll_now_my_personal_info';
    $form['#attached']['library'][] = 'enroll_now/enroll_now';
    $form['#attributes'] = [
      'novalidate' => ['true'],
      'class' => ['form-with-validations'],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('My personal info is correct'),
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
    // Convert birthdate to international format like: 'Y-m-d'.
    $birthdate = NULL !== $form_state->getValue('birthdate') ? 
      $form_state->getValue('birthdate') : NULL;
    
    if ($birthdate) {
      $new_format_birthdate = ValidateEnrollProcess::convertDateFormat(
        $birthdate, 'international'
      );
      $this->store->set('international_format_birthdate', $new_format_birthdate);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = ['step', 'phone_number', 'email', 'name', 'lastname',
      'birthdate', 'sex', 'city', 'country'];
    
      foreach($data as $field) {
      $this->store->set($field, $form_state->getValue($field));
    }
    $this->store->set('access', TRUE);
    // Move to the next step.
    $form_state->setRedirect('enroll_now.you_are_eligible');
  }
}
