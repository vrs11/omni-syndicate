<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use http\Exception\RuntimeException;

/**
 * Form controller for the Stated entity reference entity edit forms.
 */
final class StatedEntityReferenceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function processEntityEndpoints(&$form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->entity;
    $type = $entity->type->entity;
    $source_bundle = explode(':', $type->getSourceBundle());
    $target_bundle = explode(':', $type->getTargetBundle());

    foreach (['source', 'target'] as $key) {
      $bundle = ${"{$key}_bundle"};
      if (empty($bundle) && count($bundle) > 2) {
        throw new RuntimeException("Wrong {$key} type configs");
      }

      $identifier = "{$key}_entity_id";
      $form[$identifier]['widget'][0]['target_type']['#options'] = array_intersect_key($form[$identifier]['widget'][0]['target_type']['#options'] ?? [], array_flip([$bundle[0]]));
      $form[$identifier]['widget'][0]['target_id']['#target_type'] = $bundle[0];
      $form[$identifier]['widget'][0]['target_id']['#selection_handler'] = "default:{$bundle[0]}";

      if (empty($bundle[1])) {
        continue;
      }

      $form[$identifier]['widget'][0]['target_id']['#selection_settings']['target_bundles'] = [$bundle[1]];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\dynamic_ownership\Entity\UserOwnership $entity */
    $form = parent::buildForm($form, $form_state);

    $this->processEntityEndpoints($form, $form_state);

    $entity = $form_state->getFormObject()->entity;
    if (
      $entity->isNew()
      && AccessResult::allowedIfHasPermissions($this->currentUser(), [
        'approve stated_entity_reference',
        'administer stated_entity_reference types',
      ], 'OR')
    ) {
      $form['auto_approve'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Auto approve the relation'),
        '#default_value' => TRUE,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    if ($form_state->getValue('auto_approve')) {
      $this->entity->set('state', 'active');
    }
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->toLink()->toString()];
    $logger_args = [
      '%label' => $this->entity->label(),
      'link' => $this->entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New Stated entity reference %label has been created.', $message_args));
        $this->logger('stated_entity_reference')->notice('New Stated entity reference %label has been created.', $logger_args);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The Stated entity reference %label has been updated.', $message_args));
        $this->logger('stated_entity_reference')->notice('The Stated entity reference %label has been updated.', $logger_args);
        break;

      default:
        throw new \LogicException('Could not save the entity.');
    }

    $form_state->setRedirectUrl($this->entity->toUrl());

    return $result;
  }

}
