<?php

/**
 * @file
 * Contains \Drupal\enroll_now\Controller\CheckMemberEligibility
 *
 */

namespace Drupal\enroll_now\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\enroll_now\Service\ManageSessionData;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Url;
use Drupal\be_healthy_api\Service\BeHealthyApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller routines.
 */
class CheckMemberEligibility extends ControllerBase {

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
  public function validate() {
    // Remove the page cache to be killed in certain circumstances.
    $this->killSwitch->trigger();
    $response = FALSE;
    $fields = $this->ManageSessionData->retrieveSessionFields();
    // Data based on temporal storage.
    $email = isset($fields['email_original']) ? $fields['email_original'] : NULL;
    $phone_number = isset($fields['phone_number']) ? $fields['phone_number'] : NULL;
    
    $member_data = [
      'email' => $email,
      'phone-number' => $phone_number, 
    ];

    if (!empty($email) && !empty($phone_number)) {
      $link_back_to_form = Url::fromRoute('enroll_now.check_your_eligibility')
        ->toString();
      $front_page = Url::fromRoute('<front>')->toString();

      $output = [
        '#email' => $email,
        '#link_back_to_form' => $link_back_to_form,
        '#link_front_page' => $front_page,
      ];
      // Call service eligibility to check if user is a valid LD extras member.
      $response = $this->beHealthyApiClient->ckeckMemberEligibilityGet($member_data);
      // If service response is succesfully, 
      // should contain 'member-id' as key reference.
      if (isset($response['member-id'])) {
        // Replace array key, according to the 'enroll_now_my_personal_info' Form keys, 
        // to match at moment to use dataSessionStore function.
        $response = $this->replaceArrayKey($response, 'member-id', 'member_id');
        $response = $this->replaceArrayKey($response, 'first-name', 'name');
        $response = $this->replaceArrayKey($response, 'last-name', 'lastname');
        $response = $this->replaceArrayKey($response, 'birth-date', 'birthdate');
        $response = $this->replaceArrayKey($response, 'phone-number', 'phone_number');
        $response = $this->replaceArrayKey($response, 'gender', 'sex');

        // Set user data in a private temporal session.
        $this->ManageSessionData->setSessionFields($response);

        $step_two = Url::fromRoute('enroll_now.my_personal_info')->toString();
        // Redirect to the step two,
        $response = new RedirectResponse($step_two);
        return $response->send();
      }
      // If phone number or email don't match based on LD Extras.
      elseif (isset($response['credentials_error']) && $response['credentials_error']) {
        // Retrieve values from enroll_now.settings Form.
        $body_error_credentials = NULL !== $this->config->get('body_credentials') ? 
          $this->config->get('body_credentials') : '';
        $output['#body_error_credentials'] = $body_error_credentials;
        $output['#theme'] = 'enroll_now_credentials_dont_match';
  
        return $output;
  
      }
      // If user already enrolled on LD Extras program.
      elseif (isset($response['already_enroll_error']) && $response['already_enroll_error']) {
        // Retrieve values from enroll_now.settings Form.
        $body_error_already_enroll = NULL !== $this->config->get('body_already_enrolled') ?
          $this->config->get('body_already_enrolled') : '';
        $output['#phone_number'] = $phone_number;
        $output['#body_error'] = $body_error_already_enroll;
        $output['#theme'] = 'enroll_now_already_enrolled_error';

        return $output;
  
      }
      // If the service takes a long time to respond.
      elseif (isset($response['networking_error']) && $response['networking_error']) {
        // Retrieve values from enroll_now.settings Form.
        $body_error_time_out = NULL !== $this->config->get('body_timeout') ? 
          $this->config->get('body_timeout') : '';
        $output['#body_error'] = $body_error_time_out;
        $output['#theme'] = 'enroll_now_timed_out';
      }
      // If the service doesn't found customer.
      elseif (isset($response['customer_not_found']) && $response['customer_not_found']) {
        // Retrieve values from enroll_now.settings Form.
        $body_error = NULL !== $this->config->get('body_cant_join_program') ? 
          $this->config->get('body_cant_join_program') : '';
        $link_ld_extra = NULL !== $this->config->get('link_ld_extra') ? 
          $this->config->get('link_ld_extra') : '';
        $output['#body_error'] = $body_error;
        $output['#link_ld_custom'] = $link_ld_extra;
        $output['#theme'] = 'enroll_now_cant_join_the_program_error';
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
      return $response = new RedirectResponse($front_page);
   }
  }

  /**
   * Replace key array putting the array keys into a separate array, 
   * find and replace the key in that array and 
   * then combine it back with the values.
   * 
   * @param array $array
   * @param string $old_key
   * @param string $new_key
   * 
   * @return array
   */
  function replaceArrayKey($array, $old_key, $new_key) {
    // If the old key doesn't exist, we can't replace it...
    if(!isset($array[$old_key])){
        return $array;
    }
    // Get a list of all keys in the array.
    $array_keys = array_keys($array);
    // Replace the key in our $array_keys array.
    $old_Key_index = array_search($old_key, $array_keys);
    $array_keys[$old_Key_index] = $new_key;
    // Combine them back into one array.
    $new_array =  array_combine($array_keys, $array);
    
    return $new_array;
  }

}
