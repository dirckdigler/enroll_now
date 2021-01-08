<?php

namespace Drupal\enroll_now\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class EnrollNowConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_now_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'enroll_now.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('enroll_now.settings');
    // Multistep
    $form['first_step'] = [
      '#type' => 'details',
      '#title' => $this->t('Step 1 - Check eligibility'),
      '#group' => 'bootstrap',
    ];
    $form['first_step']['first_step_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('first_step_body'),
    ];
    $form['second_step'] = [
      '#type' => 'details',
      '#title' => $this->t('Step 2 - Personal info'),
      '#group' => 'bootstrap',
    ];
    $form['second_step']['second_step_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('second_step_body'),
    ];
    $form['third_step'] = [
      '#type' => 'details',
      '#title' => $this->t("Step 3 - You are Elegible"),
      '#group' => 'bootstrap',
    ];
    $form['third_step']['third_step_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('third_step_body'),
    ];
    $form['fourth_step'] = [
      '#type' => 'details',
      '#title' => $this->t('Step 4 - Reward-plan'),
      '#group' => 'bootstrap',
    ];
    $form['fourth_step']['fourth_step_body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description stage YES'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('fourth_step_body'),
    ];
    $form['fourth_step']['fourth_step_body_exception'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description stage NO'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('fourth_step_body_exception'),
    ];
    // Exceptional cases.
    $form['exceptional_cases'] = [
      '#type' => 'details',
      '#title' => $this->t('Exceptional cases'),
      '#group' => 'bootstrap',
    ];
    $form['exceptional_cases']['can_not_join_program_page'] = [
      '#type' => 'details',
      '#title' => $this->t("Can't join the program page"),
      '#group' => 'bootstrap',
    ];
    $form['exceptional_cases']['can_not_join_program_page']
      ['body_cant_join_program'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error message body'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('body_cant_join_program'),
    ];
    $form['exceptional_cases']['can_not_join_program_page']['link_ld_extra'] = [
      '#type' => 'url',
      '#title' => $this->t('Link LD Extra Member'),
      '#default_value' => $config->get('link_ld_extra'),
    ];
    $form['exceptional_cases']['not_match'] = [
      '#type' => 'details',
      '#title' => $this->t("Credentials don't match"),
      '#group' => 'bootstrap',
    ];
    $form['exceptional_cases']['not_match']['body_credentials'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error message body'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('body_credentials'),
    ];
    $form['exceptional_cases']['already_enrolled'] = [
      '#type' => 'details',
      '#title' => $this->t("Already enrolled"),
      '#group' => 'bootstrap',
    ];
    $form['exceptional_cases']['already_enrolled']['body_already_enrolled'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error message body'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('body_already_enrolled'),
    ];
    $form['exceptional_cases']['time_out'] = [
      '#type' => 'details',
      '#title' => $this->t("Time out"),
      '#group' => 'bootstrap',
    ];
    $form['exceptional_cases']['time_out']['image_timeout'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Image'),
      '#description' => $this->t('Manage image for time out page here. Only <em>.svg</em> is allowed.'),
      '#default_value' => $config->get('image_timeout'),
      '#multiple' => FALSE,
      // Uploading an image to a AWS bucket.
      '#upload_location' => 's3://drupal-behealthy/s3fs-public/',
      '#upload_validators' => [
        'file_validate_extensions' => ['svg'],
        'file_validate_size' => [2560000]
      ],
    ];
    $form['exceptional_cases']['time_out']['body_timeout'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Error message body'),
      '#rows' => 4,
      '#cols' => 5,
      '#format' => 'full_html',
      '#default_value' => $config->get('body_timeout'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('enroll_now.settings')
      ->set('first_step_body', $form_state->getValue('first_step_body')['value'])
      ->set('second_step_body', $form_state->getValue('second_step_body')['value'])
      ->set('third_step_body', $form_state->getValue('third_step_body')['value'])
      ->set('fourth_step_body', $form_state->getValue('fourth_step_body')['value'])
      ->set('fourth_step_body_exception', $form_state->getValue('fourth_step_body_exception')['value'])
      ->set('body_cant_join_program', $form_state->getValue('body_cant_join_program')['value'])
      ->set('link_ld_extra', $form_state->getValue('link_ld_extra'))
      ->set('body_credentials', $form_state->getValue('body_credentials')['value'])
      ->set('body_already_enrolled', $form_state->getValue('body_already_enrolled')['value'])
      ->set('image_timeout', $form_state->getValue('image_timeout'))
      ->set('body_timeout', $form_state->getValue('body_timeout')['value'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
