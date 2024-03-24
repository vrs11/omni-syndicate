<?php

namespace Drupal\entity_reference_confirmation\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Defines the 'entity_reference_confirm' entity field type (Extends cores EntityReferenceItem).
 *
 * @see Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
 *
 * @FieldType(
 *   id = "entity_reference_confirm",
 *   label = @Translation("Entity reference (With confirmation)"),
 *   description = @Translation("An entity field containing an entity reference. Refference has to be approved by a target owner"),
 *   category = "reference",
 *   default_widget = "entity_reference_confirm_autocomplete",
 *   default_formatter = "entity_reference_confirm_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 */
class EntityReferenceConfirmItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);

    $schema['columns']['requested_by'] = [
      'description' => 'The ID of the User who requested an entity connection.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    $schema['columns']['requested_at'] = [
      'description' => 'The timestamp of the requested entity connection.',
      'type' => 'int',
    ];

    $schema['columns']['state'] = [
      'type' => 'varchar_ascii',
      'length' => 255,
    ];

    $schema['columns']['updated_by'] = [
      'description' => 'The ID of the User who updated an entity connection.',
      'type' => 'int',
      'unsigned' => TRUE,
    ];

    $schema['columns']['updated_at'] = [
      'description' => 'The timestamp of the requested entity connection.',
      'type' => 'int',
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['requested_by'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Requested by'))
      ->setSetting('unsigned', TRUE);
    $properties['requested_at'] = DataDefinition::create('timestamp')
      ->setLabel(t('Timestamp value'));
    $properties['updated_by'] = DataReferenceTargetDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Updated by'))
      ->setSetting('unsigned', TRUE);
    $properties['updated_at'] = DataDefinition::create('timestamp')
      ->setLabel(t('Timestamp value'));


    $properties['state'] = DataDefinition::create('string')
      ->setLabel(t('State'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    foreach ([
      'requested_by',
      'requested_at',
      'state',
      'updated_by',
      'updated_at',
      'state',
    ] as $field) {
      if ($value = $values[$field] ?? $this->get($field)->getValue()) {
        $this->writePropertyValue($field, $value);
        $values[$field] = $value;
      }
    }

    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();

    if (empty($this->requested_by)) {
      $this->set('requested_by', $values["requested_by"] ?? \Drupal::currentUser()->id());
    }

    if (empty($this->requested_at)) {
      $this->set('requested_at', $values["requested_at"] ?? (new DrupalDateTime())->getTimestamp());
    }

    if (empty($this->state)) {
      $this->set('state', $values["state"] ?? 'new');
    }

    if ($this->auto_approve && $this->state != 'active') {
      $this->set('updated_by', \Drupal::currentUser()->id());
      $this->set('updated_at', (new DrupalDateTime())->getTimestamp());
      $this->set('state', 'active');
    }
  }
}
