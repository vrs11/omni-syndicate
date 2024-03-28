<?php

namespace Drupal\stated_entity_reference\EventSubscriber;


use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\stated_entity_reference\Entity\StatedEntityReferenceInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Basic ownership events
 */
class WorkflowStateChangeSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      'state_machine.post_transition' => ['onWorkflowTransition']
    ];
  }

  /**
   * Invalidate cache on workflow state transition.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function onWorkflowTransition(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();
    if ($entity instanceof StatedEntityReferenceInterface) {
      stated_entity_reference_stated_entity_reference_presave($entity);
    }
  }

}
