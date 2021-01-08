<?php

namespace Drupal\enroll_now\Service;

use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ManageSessionData.
 */
class ManageSessionData {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Constructs a \Drupal\enroll_now\Service\ManageSessionData.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->store = $this->tempStoreFactory->get('enroll_now'); 
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }


  /**
   * Retrieve every form field of session stored.
   * If parameter is empty, this will return all fields.
   *  
   * @param array $data
   * @return array
   * 
   */
  public function retrieveSessionFields($data = []) {
    $fields = FALSE;
    
    if (empty($data)) {
      $data_session_store = $this->dataSessionStore();
      $data = isset($data_session_store) ? reset($data_session_store) : NULL; 
    } 
    if ($data) {
      foreach ($data as $key) {
        $fields[$key] = $this->store->get($key);
      }
    }
    return $fields;

  }

  /**
   * Set array data in private session.
   * If parameter is empty, this will search
   * dataSessionStore by default.
   *  
   * @param array $data
   * 
   */
  public function setSessionFields($data = []) {
    $fields = FALSE;
    
    if (empty($data)) {
      $data_session_store = $this->dataSessionStore();
      $data = isset($data_session_store) ? reset($data_session_store) : NULL; 
    } 
    if ($data) {
      foreach ($data as $key => $item) {
        $this->store->set($key, $item);
      }
    }
  }

  /**
   * Removes all the keys from the store collection used for
   * the multistep form.
   */
  public function deleteSessionStore() {
    $data = $this->dataSessionStore();
   
    foreach (reset($data) as $key) {
      $this->store->delete($key);
    }
    \Drupal::logger('enroll_now')->notice('User session data was removed completely');

  }

  /**
   * Store the fields names for the enroll process form.
   */
  private function dataSessionStore() {
    return [
      $this->data = [
        'step', 'member_id', 'get_product', 'enrollment-status', 'name', 'lastname',
        'birthdate', 'international_format_birthdate', 'sex', 'city', 'country', 'state', 
        'email', 'access', 'phone_number', 'email_original'
      ],
    ];

  }

}
