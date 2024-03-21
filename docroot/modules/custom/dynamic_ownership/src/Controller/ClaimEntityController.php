<?php

namespace Drupal\dynamic_ownership\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\dynamic_ownership\Entity\UserOwnershipType;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class ClaimNodeController.
 */
class ClaimEntityController extends ControllerBase {

  /**
   * Provide new user ownership entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity we want to claim to.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function claim(EntityInterface $entity): RedirectResponse {
    $uid = $this->currentUser()->id();
    $id = $entity->id();

    $bundle = $entity->bundle();
    $type_id = $entity->getEntityTypeId();
    $ownership_type = UserOwnershipType::loadDefault("{$type_id}:{$bundle}");

    $storage = $this->entityTypeManager()->getStorage('user_ownership');
    $ownership = $storage->create([
      'type' => $ownership_type->id(),
      'user_id' => $uid,
    ]);
    $ownership->entity_id->target_id = $id;
    $ownership->entity_id->target_type = $type_id;
    $ownership->save();

    $msg = $this->t('The claim has been placed. Please wait a confirmation.');
    $this->moduleHandler()->alter('claim_placed_data', $msg,  $ownership);
    $this->messenger()->addMessage($msg);

    return new RedirectResponse(Url::fromRoute("entity.{$type_id}.canonical", [$type_id => $entity->id()])->toString());
  }
}
