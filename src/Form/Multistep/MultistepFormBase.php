<?php

/**
 * @file
 * Contains \Drupal\enroll_now\Form\Multistep\MultistepFormBase.
 */

namespace Drupal\enroll_now\Form\Multistep;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Egulias\EmailValidator\EmailValidator;

abstract class MultistepFormBase extends FormBase {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $store;

  /**
    * The email validator.
    *
    * @var \Egulias\EmailValidator\EmailValidator
  */
  protected $emailValidator;

  /**
   * Config data from enroll_now.settings
   */
  protected $config;

  /**
   * Constructs a \Drupal\enroll_now\Form\Multistep\MultistepFormBase.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory  $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, 
                              SessionManagerInterface $session_manager, 
                              AccountInterface $current_user,
                              EmailValidator $email_validator
                              ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;
    $this->emailValidator = $email_validator;
    $this->store = $this->tempStoreFactory->get('enroll_now');
    $this->config = \Drupal::config('enroll_now.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('session_manager'),
      $container->get('current_user'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    if ($this->currentUser->isAnonymous() && !isset($_SESSION['multistep_form_holds_session'])) {
      $_SESSION['multistep_form_holds_session'] = true;
      $this->sessionManager->start();
    }

    $form = [];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    ];
    $form['#cache'] = ['max-age' => 0];
    $form['#attributes']['autocomplete'] = 'off';
    $form['#attached']['library'][] = 'enroll_now/enroll_now';

    return $form;
  }

}
