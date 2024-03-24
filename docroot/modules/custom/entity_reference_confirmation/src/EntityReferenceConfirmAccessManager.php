<?php

namespace Drupal\entity_reference_confirmation;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_reference_confirmation\Plugin\Field\FieldType\EntityReferenceConfirmItem;

/**
 * User ownership access manager.
 */
class EntityReferenceConfirmAccessManager {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * EntityReferenceConfirmAccessManager constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *    The module handler.
   * @param \Drupal\Core\Session\AccountProxyInterface $accountProxy
   *   Current user account.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    AccountProxyInterface $accountProxy
  ) {
    $this->moduleHandler = $module_handler;
    $this->currentUser = $accountProxy->getAccount();
  }

  /**
   * Checks relation access.
   *
   * @param \Drupal\entity_reference_confirmation\Plugin\Field\FieldType\EntityReferenceConfirmItem $item
   *  The item to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *  Related entity.
   * @param mixed $delta
   *  Item delta.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The result of a check
   */
  public function relationAccessCheck(
    EntityReferenceConfirmItem $item,
    EntityInterface $entity,
    $delta,
  ): AccessResultInterface {
    $accessResult = AccessResult::forbidden();
    if ((
        $item->state == 'active'
      ) || (
        $this->currentUser->hasPermission('view any entity reference relation')
      ) || (
        $entity->getOwner()->id() == $this->currentUser->id()
        && $this->currentUser->hasPermission('view own entity reference relation')
    )) {
      $accessResult = AccessResult::allowed();
    }

    if (!empty($accesses = $this->moduleHandler->invokeAll('entity_reference_confirmation_access', [$item, $entity, $delta]))) {
      foreach ($accesses as $other) {
        $accessResult = $accessResult->orIf($other);
      }
    }

    return $accessResult;
  }
}
