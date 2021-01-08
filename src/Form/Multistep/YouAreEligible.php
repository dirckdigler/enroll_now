<?php
/**
 * @file
 * Contains \Drupal\demo\Form\Multistep\YouAreEligible.
 */
namespace Drupal\enroll_now\Form\Multistep;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
class YouAreEligible extends MultistepFormBase {
  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'enroll_now_you_are_eligible';
  }
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $this->store->set('access', FALSE);
    $form['step'] = [
      '#type' => 'hidden',
      '#value' => 3,
    ];
    // Is not working properly with the propertie #url,
    // using #markup as solution to render anchor link.
    // @see https://www.drupal.org/project/webform/issues/2884372.
    $previous_step_link = Url::fromRoute('enroll_now.my_personal_info',
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
    $form['label'] = [
      '#prefix' => '<div class="content-top-description"><h2 class="title">' .
        '<span>' . $this->t("Great news!") . '</span><span>' .
        $this->t("You are eligible") . '</span></h2>',
      '#markup' => '<p class="description">' . $this->t("Do you want to earn a ")  .
        '<strong>' . $this->t('new') . '</strong>' . $this->t(" Apple Watch through the program?") .
        '</p>',
      '#suffix' => '</div>'
    ];
    $form['get_product'] = [
      '#type' => 'radios',
      '#options' => [
        'yes' => '<h3 class="title">' . $this->t('Yes') . '</h3><p class="description">' .
          $this->t('I want to earn a new Apple Watch.') . '</p>',
        'no' => '<h3 class="title">' . $this->t('No') . '</h3><p class="description">' .
          $this->t('I still want to get rewards but I already have an Apple Watch.') . '</p>',
      ],
      '#default_value' => $this->store->get('get_product') ?
        $this->store->get('get_product') : '',
      '#required' => TRUE,
    ];
    $form['#theme'] = 'enroll_now_you_are_eligible';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#attributes' => [
        'disabled' => ['true'],
      ],
    ];
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step', $form_state->getValue('step'));
    $this->store->set('get_product', $form_state->getValue('get_product'));
    $this->store->set('access', TRUE);
    $form_state->setRedirect('enroll_now.your_reward_plan');
  }
}
