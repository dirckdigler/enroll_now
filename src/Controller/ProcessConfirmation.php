<?php

/**
 * @file
 * Contains \Drupal\enroll_now\Controller\ProcessConfirmation
 *
 */
namespace Drupal\enroll_now\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\enroll_now\Service\ManageSessionData;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\be_healthy_api\Service\BeHealthyApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines.
 */
class ProcessConfirmation extends ControllerBase {

  /**
   * @var \Drupal\enroll_now\Service\ManageSessionData
   */
  protected $ManageSessionData;

  /**
   * The kill switch service.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Consume services API.
   *
   * @var \Drupal\be_healthy_api\Service\BeHealthyApiClient
   */
  protected $beHealthyApiClient;

  /**
   * Config data from enroll_now.settings
   */
  protected $config;

  /**
   * ProcessConfirmation Constructor.
   *
   * @param \Drupal\enroll_now\Service\ManageSessionData $manage_session_data
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $kill_switch
   */
  public function __construct(ManageSessionData $manage_session_data,
                              KillSwitch $kill_switch,
                              BeHealthyApiClient $be_healthy_api_client
                              ) {
    $this->ManageSessionData = $manage_session_data;
    $this->killSwitch = $kill_switch;
    $this->beHealthyApiClient = $be_healthy_api_client;
    $this->config = \Drupal::config('enroll_now.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('enroll_now.manage_session_data'),
      $container->get('page_cache_kill_switch'),
      $container->get('be_healthy_api.client'),
    );
  }

  /**
   * Constructs a final page to the Enroll Process.
   *
   * The router _controller callback, maps the path
   *
   * _controller callbacks return a renderable array for the content area of the
   * page. The theme system will later render and surround the content.
   *
   * Depending the response service, the output will have the specific theme.
   */
  public function view() {
    // Remove the page cache to be killed in certain circumstances.
    $this->killSwitch->trigger();
    $response = FALSE;
    $fields = $this->ManageSessionData->retrieveSessionFields();
    // Data based on temporal storage.
    $get_product = isset($fields['get_product']) ? $fields['get_product'] : NULL;
    $name = isset($fields['name']) ? $fields['name'] : NULL;
    $email = isset($fields['email_original']) ? $fields['email_original'] : NULL;
    $member_id = isset($fields['member_id']) ? $fields['member_id'] : NULL;

    // Validate if session data is not NULL.
    if (!empty($get_product) && !empty($name) && !empty($email) && !empty($member_id)) {
      // Canonical form URL.
      $link_back_to_form = Url::fromRoute('enroll_now.your_reward_plan')->toString();

      $output = [
        '#name' => $name,
        '#get_product' => $get_product,
        '#email' => $email,
        '#link_back_to_form' => $link_back_to_form,
      ];
      // Call service account to create account on Admin Console.
      $response = $this->beHealthyApiClient->createAccountAdminPublicPost();
      // If all the enroll process was succesfuly.
      if (isset($response['account-id']) && $response['account-id']) {
        if ($get_product !== NULL && $get_product == 'yes') {
          $output['#theme'] = 'enroll_now_process_confirmation_success';
          // Delete data session.
          $this->ManageSessionData->deleteSessionStore();
        }
        else {
          $output['#theme'] = 'enroll_now_process_confirmation_fail';
          // Delete data session.
          $this->ManageSessionData->deleteSessionStore();
        }
      }
      // If the service takes a long time to respond.
      elseif (isset($response['networking_error']) && $response['networking_error']) {
        // Retrieve values from enroll_now.settings Form.
        $body_error_time_out = NULL !== $this->config->get('body_timeout') ? 
          $this->config->get('body_timeout') : '';
        // Retrieve file ID and get the url file
        // to  render into the twig.
        $fid_image_timeout = NULL !== $this->config->get('image_timeout') ? 
          $this->config->get('image_timeout') : '';
        if (!empty($fid_image_timeout['0'])) {
          $file = File::load($fid_image_timeout['0']);
          $file_url = file_create_url($file->getFileUri());
          $output['#image'] = $file_url;
        }
       
        $output['#body_error'] = $body_error_time_out;
        $output['#theme'] = 'enroll_now_timed_out';
      }
      else {
        // Finally, if somenthing is wrong to enroll,
        // well, show message saying: too bad.
        $output['#theme'] = 'enroll_now_something_went_wrong';
      }
      return $output;
    }
    else {
       $front_page = Url::fromRoute('<front>')->toString();
      // Redirect to the homepage,
      // if the user chooses to leave the current page.
      return $response = new RedirectResponse($front_page);
    }

  }

  /**
   * Removes all the session data used for
   * the multistep form.
   * Independent logic because it's called from a controller.
   */
  public function triggerDeleteSessionStore() {
    $step_one = Url::fromRoute('enroll_now.check_your_eligibility')->toString();
    // Redirect to the step one,
    // if the user chooses to leave the current page.
    $response = new RedirectResponse($step_one);
    return $response->send();

  }

}
