<?php

namespace Drupal\dynamic_ownership\EventSubscriber;

use Drupal\dynamic_ownership\Event\UserOwnershipCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Basic ownership events
 */
class DynamicOwnershipBasics implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      UserOwnershipCreatedEvent::EVENT_NAME => ['userOwnershipCreated']
    ];
  }

  /**
   * User Ownership Created
   *
   * @param \Drupal\dynamic_ownership\Event\UserOwnershipCreatedEvent $event
   *   The event we subscribed to.
   */
  public function userOwnershipCreated(UserOwnershipCreatedEvent $event): void {
    $ownership = $event->getOwnerShip();
    $state = $ownership->getState();

    if ($state == 'active') {
      return;
    }

    /**
     * TODO: Send email to inform a owner of the target entity.
     */
  }

}
