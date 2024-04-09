<?php

declare(strict_types=1);

namespace Drupal\dynamic_ownership\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\UserSession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @todo Add description for this subscriber.
 */
final class DynamicOwnershipAuthenticationSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an authentication subscriber.
   */
  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly AccountProxyInterface $accountProxy
  ) {}

  /**
   * Kernel request event handler.
   */
  public function onKernelRequestAuthenticate(RequestEvent $event): void {
    if (
      !$event->isMainRequest()
      || empty($account = $this->accountProxy->getAccount())
    ) {
      return;
    }

    $ownerships = \Drupal::entityTypeManager()->getStorage('user_ownership')->loadByProperties([
      'user_id' => $account->id(),
      'state' => 'active',
    ]);

    if (empty($ownerships)) {
      return;
    }

    $user_session_array = json_decode(str_replace('\\u0000*\\u0000', '', json_encode((array)($account))), true);
    foreach ($ownerships as $ownership) {
      if (empty($role = $ownership->role_id->target_id)) {
        continue;
      }

      $user_session_array["roles"][] = $role;
    }
    $dynamic_user_session = new UserSession($user_session_array);
    $this->accountProxy->setAccount($dynamic_user_session);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onKernelRequestAuthenticate', '299'],
    ];
  }

}
