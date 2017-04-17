<?php

/**
 * @file
 * Contains \Drupal\multisite_manager.
 */

namespace Drupal\multisite_manager;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Cache\Cache;

/**
 * View builder handler for Domain Entities.
 *
 * @ingroup Domain
 */
class DomainEntityViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    parent::buildComponents($build, $entities, $displays, $view_mode);
    foreach ($entities as $id => $entity) {
      $domain = $entity->get('domain')->__get('value');
      $domain_id = $entity->get('id')->__get('value');

      $build[$id]['status'] = [
        '#type' => 'details',
        '#title' => t('Domain status'),
        '#markup' => $this->getStatus($domain, $domain_id),
      ];
      $build[$id]['manager'] = [
        '#type' => 'details',
        '#title' => t('Multisite manager actions'),
        '#prefix' => '<div class="multisite-manager">',
        '#suffix' => '</div>',
      ];
      $build[$id]['manager']['form'] =  \Drupal::formBuilder()->getForm('Drupal\multisite_manager\Form\MultisiteManagerForm', $domain, $domain_id);
    }
  }

  public function getStatus($domain, $domain_id) {
    $cid = 'multisite_manager:status:domain' . $domain_id;
    $data = NULL;
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {
      $command = $domain ? ' -l ' . $domain : '';
      exec("/home/guilherme/.composer/vendor/drush/drush/drush.php status --format=php" . $command . ' 2>&1', $status);
      if(count($status)) {
        $items = unserialize($status[0]);
        foreach ($items as $key => $value) {
          if(is_array($value)) {
            $value = implode(', ', $value);
            $items[$key] = $value;
          }
          $items[$key] = strtoupper($key) . ': ' . $value;
        }

        $items_list = [
          '#theme' => 'item_list',
          '#items' =>  $items,
        ];
        $data = drupal_render($items_list);
      }
      \Drupal::cache()->set($cid, $data, Cache::PERMANENT, ['domain_entity:' . $domain_id]);
    }

    return $data;
  }

}