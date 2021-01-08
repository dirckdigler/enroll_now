<?php

/**
 * @file
 * Contains \Drupal\enroll_now\ValidateEnrollProcess.
 */

namespace Drupal\enroll_now;

/**
 * Helper functions to validate or improve form functionalities.
 */
class ValidateEnrollProcess {

  /**
   * Get terms of the vocabulary passed through parameter.
   * 
   * @param string $vocabulary
   * @return array
   */
	public static function getTermsVocabulary($vocabulary) {
    $options_country = NULL;
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vocabulary);
    $tids = $query->execute();
    if($tids) {
      $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
      $options_country = [];
      foreach ($terms as $term) {
        if ($term->hasField('description')) {
          // Get values of the field description.
          // These values are the abbreviation of every country term.
          // Build array of countrys and the key is the abbreviation.
          $country_abbreviation = trim($term->get('description')->value);
          $options_country[$country_abbreviation] = $term->toLink()->getText();
        }
      }
    }

    return $options_country;
  }

  /**
   * Convert date to any format depending the kind
   * of parameter.
   * 
   * @param string $date
   * @param string $type
   * @return string
   */
  public static function convertDateFormat($date, $type) {
    $new_date = NULL;
    // First, convert date to timestamp.
    $timestamp = strtotime($date);
    switch ($type) {
      case 'international':
        $new_date = date("Y-m-d", $timestamp);
        break;
      case 'F d, Y h:i:s A':
        $new_date = date('F d, Y h:i:s A');
        break;
      case 'm-d-Y':
        $new_date = date('m/d/Y', $timestamp);
        break;
    }

    return $new_date;
    
  }
  
}
