<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stated_entity_reference\Entity\StatedEntityReferenceType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for Stated entity reference type forms.
 */
final class StatedEntityReferenceTypeForm extends BundleEntityFormBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * UserOwnershipTypeForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type manager.
   */
  public function __construct (
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfo $entity_type_bundle_info
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    if ($this->operation === 'edit') {
      $form['#title'] = $this->t('Edit %label Stated entity reference type', ['%label' => $this->entity->label()]);
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('The human-readable name of this Stated entity reference type.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#machine_name' => [
        'exists' => [StatedEntityReferenceType::class, 'load'],
        'source' => ['label'],
      ],
      '#description' => $this->t('A unique machine-readable name for this Stated entity reference type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $bundle_options = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $id => $type) {
      if (!$type instanceof ContentEntityType) {
        continue;
      }

      if (empty($type->getKey('bundle'))) {
        $bundle_options[$id] = $type->getLabel();
        continue;
      }

      $bundles = $this->entityTypeBundleInfo->getBundleInfo($id);
      foreach ($bundles as $bundle_id => $bundle_info) {
        $bundle_options["{$id}:{$bundle_id}"] = "{$type->getBundleLabel()} ({$bundle_info['label']})";
      }
    }
    natsort($bundle_options);

    $form['source_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Source bundle'),
      '#options' => $bundle_options,
      '#default_value' => $this->entity->getTargetBundle(),
      '#required' => TRUE,
      '#size' => 6,
      '#multiple' => FALSE,
    ];

    $form['target_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Target bundle'),
      '#options' => $bundle_options,
      '#default_value' => $this->entity->getTargetBundle(),
      '#required' => TRUE,
      '#size' => 6,
      '#multiple' => FALSE,
    ];

    return $this->protectBundleIdElement($form);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save Stated entity reference type');
    $actions['delete']['#value'] = $this->t('Delete Stated entity reference type');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $result = parent::save($form, $form_state);

    $message_args = ['%label' => $this->entity->label()];
    $this->messenger()->addStatus(
      match($result) {
        SAVED_NEW => $this->t('The Stated entity reference type %label has been added.', $message_args),
        SAVED_UPDATED => $this->t('The Stated entity reference type %label has been updated.', $message_args),
      }
    );
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $result;
  }

}
