<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the Stated entity reference entity class.
 *
 * @ContentEntityType(
 *   id = "stated_entity_reference",
 *   label = @Translation("Stated entity reference"),
 *   label_collection = @Translation("Stated entity references"),
 *   label_singular = @Translation("Stated entity reference"),
 *   label_plural = @Translation("Stated entity references"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Stated entity references",
 *     plural = "@count Stated entity references",
 *   ),
 *   bundle_label = @Translation("Stated entity reference type"),
 *   handlers = {
 *     "storage" = "Drupal\stated_entity_reference\StatedEntityReferenceStorage",
 *     "list_builder" = "Drupal\stated_entity_reference\StatedEntityReferenceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\stated_entity_reference\StatedEntityReferenceAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\stated_entity_reference\Form\StatedEntityReferenceForm",
 *       "edit" = "Drupal\stated_entity_reference\Form\StatedEntityReferenceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "stated_entity_reference",
 *   admin_permission = "administer stated_entity_reference types",
 *   entity_keys = {
 *     "id" = "rid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/entity-reference-state",
 *     "add-form" = "/admin/stated-entity-reference/add/{stated_entity_reference_type}",
 *     "add-page" = "/admin/stated-entity-reference/add",
 *     "canonical" = "/admin/stated-entity-reference/{stated_entity_reference}",
 *     "edit-form" = "/admin/stated-entity-reference/{stated_entity_reference}/edit",
 *     "delete-form" = "/admin/stated-entity-reference/{stated_entity_reference}/delete",
 *     "delete-multiple-form" = "/admin/content/entity-reference-state/delete-multiple",
 *   },
 *   bundle_entity_type = "stated_entity_reference_type",
 *   field_ui_base_route = "entity.stated_entity_reference_type.edit_form",
 * )
 */
class StatedEntityReference extends ContentEntityBase implements StatedEntityReferenceInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getSourceEntity()?->label() . " > " . $this->getTargetEntity()?->label();
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(\Drupal::currentUser()->id());
    }

    Cache::invalidateTags([
      'node:' . $this->getSourceEntity()->id(),
      'node:' . $this->getTargetEntity()->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getWorkflowId() {
    return 'stated_entity_reference';
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    return $this->get('source_entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setSourceEntity(EntityInterface $entity) {
    $settings = $this->getFieldDefinition('source_entity_id')->getSettings();
    $type = $entity->getEntityTypeId();

    if (
      !empty($settings['exclude_entity_types']) &&
      !in_array($type, $settings['entity_type_ids'])
    ) {
      return $this;
    }

    $reference_array = [
      'target_id' => $entity->id(),
      'target_type' => $type,
    ];

    $this->set('source_entity_id', $reference_array);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity() {
    return $this->get('target_entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntity(EntityInterface $entity) {
    $settings = $this->getFieldDefinition('target_entity_id')->getSettings();
    $type = $entity->getEntityTypeId();

    if (
      !empty($settings['exclude_entity_types']) &&
      !in_array($type, $settings['entity_type_ids'])
    ) {
      return $this;
    }

    $reference_array = [
      'target_id' => $entity->id(),
      'target_type' => $type,
    ];

    $this->set('target_entity_id', $reference_array);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $fields['source_entity_id'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Source entity'))
      ->setDescription(t('The source entity of the relation.'))
      ->setSetting('handler', 'default')
      ->setSetting('exclude_entity_types', FALSE)
      ->setSetting('entity_type_ids', array_keys($entity_types))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'dynamic_entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_default',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'match_limit' => 10,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(1)
      ->setRequired(TRUE);

    $fields['target_entity_id'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Target entity'))
      ->setDescription(t('The target entity of the relation.'))
      ->setSetting('handler', 'default')
      ->setSetting('exclude_entity_types', FALSE)
      ->setSetting('entity_type_ids', array_keys($entity_types))
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'dynamic_entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_default',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'match_limit' => 10,
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(1)
      ->setRequired(TRUE);

    $fields['state'] = BaseFieldDefinition::create('state')
      ->setLabel(t('State'))
      ->setDescription(t('The relation state.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'state_transition_form',
        'weight' => 50,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setSetting('workflow_callback', [__CLASS__, 'getWorkflowId']);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the Stated entity reference was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the Stated entity reference was last edited.'));

    return $fields;
  }

}
