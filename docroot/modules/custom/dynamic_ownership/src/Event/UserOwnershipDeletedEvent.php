<?php

namespace Drupal\dynamic_ownership\Event;

/**
 * Event that is fired when a user_ownership is Deleted.
 */
class UserOwnershipDeletedEvent extends UserOwnershipEvent {

  const EVENT_NAME = 'user_ownership_deleted_event';
}
