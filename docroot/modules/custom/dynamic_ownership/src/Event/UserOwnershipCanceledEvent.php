<?php

namespace Drupal\dynamic_ownership\Event;

/**
 * Event that is fired when a user_ownership is Canceled.
 */
class UserOwnershipCanceledEvent extends UserOwnershipEvent {

  const EVENT_NAME = 'user_ownership_canceled_event';
}
