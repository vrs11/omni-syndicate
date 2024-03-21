<?php

namespace Drupal\dynamic_ownership\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a form for deleting User ownership entities.
 *
 * @ingroup dynamic_ownership
 */
class UserOwnershipDeleteForm extends ContentEntityDeleteForm {
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('view.ownerships_list.ownerships');
  }

  /**
   * Returns the URL where the user should be redirected after deletion.
   *
   * @return \Drupal\Core\Url
   *   The redirect URL.
   */
  protected function getRedirectUrl() {
    return Url::fromRoute('view.ownerships_list.ownerships');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();

    return $this->t('Claiming the @type profile @label has been canceled.', [
      '@type' => $entity->bundle(),
      '@label' => $entity->getEntity()->getTitle(),
    ]);
  }
}
