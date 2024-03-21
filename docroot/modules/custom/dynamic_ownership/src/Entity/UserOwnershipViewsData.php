<?php

namespace Drupal\dynamic_ownership\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for User ownership entities.
 */
class UserOwnershipViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['user_ownership']['under_control'] = [
      'title' => $this->t('Is this ownership is under user control'),
      'help' => $this->t('Filters out ownership if the current user cannot control it.'),
      'filter' => [
        'field' => 'oid',
        'id' => 'ownership_inherited_control',
        'label' => $this->t('Ownership is under control of entity owner.'),
      ],
    ];

    return $data;
  }

}
