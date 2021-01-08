<?php

/**
 * @file
 * Contains \Drupal\demo\Form\Multistep\YourRewardPlan.
 */

namespace Drupal\enroll_now\Form\Multistep;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class YourRewardPlan extends MultistepFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'enroll_now_your_reward_plan';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $this->store->set('access', FALSE);
   
    $form['step'] = [
      '#type' => 'hidden',
      '#value' => 4,
    ];
    // Is not working properly with the propertie #url,
    // using #markup as solution to render anchor link.
    // @see https://www.drupal.org/project/webform/issues/2884372.
    $previous_step_link = Url::fromRoute('enroll_now.you_are_eligible', 
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
    // Get value from field "get_product" from the previous form,
    // depending on value, show different label.
    $get_product = (NULL ==! $this->store->get('get_product')) ? 
      $this->store->get('get_product') : NULL;
    // Retrieve enroll_now.settings form to fourth step.
    if (!empty($get_product) && $get_product == 'yes') {
      $description = NULL !== $this->config->get('fourth_step_body') ? 
      $this->config->get('fourth_step_body') : '';
    } 
    else {
      $description = NULL !== $this->config->get('fourth_step_body_exception') ? 
      $this->config->get('fourth_step_body_exception') : '';
    }

    $form['label'] = [
      '#prefix' => '<div class="content-top-description"><div class="title-description"><h2>' .
        $this->t("Your reward plan") .'</h2>',
      '#markup' => '<div class="description-page">' . $description . '</div></div>',
      '#suffix' => '</div>'
    ];
    
    $form['#theme'] = 'enroll_now_your_reward_plan';
    $form['actions']['submit']['#value'] = $this->t('Enroll me!');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('enroll_now.process_confirmation');
    $this->store->set('access', TRUE);
    $this->store->set('step', $form_state->getValue('step'));
  }
}
