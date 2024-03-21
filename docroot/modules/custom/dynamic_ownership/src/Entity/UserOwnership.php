<?php

namespace Drupal\dynamic_ownership\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Defines the User ownership entity.
 *
 * @ingroup dynamic_ownership
 *
 * @ContentEntityType(
 *   id = "user_ownership",
 *   label = @Translation("User ownership"),
 *   bundle_label = @Translation("User ownership type"),
 *   handlers = {
 *     "storage" = "Drupal\dynamic_ownership\UserOwnershipStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\dynamic_ownership\Entity\UserOwnershipViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\dynamic_ownership\Form\UserOwnershipForm",
 *       "add" = "Drupal\dynamic_ownership\Form\UserOwnershipForm",
 *       "edit" = "Drupal\dynamic_ownership\Form\UserOwnershipForm",
 *       "delete" = "Drupal\dynamic_ownership\Form\UserOwnershipDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\dynamic_ownership\UserOwnershipHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\dynamic_ownership\UserOwnershipAccessControlHandler",
 *   },
 *   base_table = "user_ownership",
 *   translatable = FALSE,
 *   permission_granularity = "bundle",
 *   admin_permission = "administer user ownership entities",
 *   entity_keys = {
 *     "id" = "oid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "canonical" = "/user_ownership/{user_ownership}",
 *     "add-page" = "/admin/structure/user_ownership/add",
 *     "add-form" = "/admin/structure/user_ownership/add/{user_ownership_type}",
 *     "edit-form" = "/admin/structure/user_ownership/{user_ownership}/edit",
 *     "delete-form" = "/admin/structure/user_ownership/{user_ownership}/delete",
 *     "collection" = "/admin/structure/user_ownership",
 *   },
 *   bundle_entity_type = "user_ownership_type",
 *   field_ui_base_route = "entity.user_ownership_type.edit_form"
 * )
 */
class UserOwnership extends ContentEntityBase implements UserOwnershipInterface {

  use EntityChangedTrait;

  const DEFAULT_FIELDS = [
    'user_id' => 'user',
    'entity_id' => 'node',
    'role_id' => 'role',
  ];

  /**
   * {@inheritdoc}
   */
  public function getName() {
    if (
      empty($entity = $this->getEntity()) ||
      empty($user = $this->getUser())
    ) {
      return "broken relatives";
    }

    return implode(" ", [
      $user->get('name')->value,
      '<->',
      $entity->getTitle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getWorkflowId() {
    return 'user_ownership';
  }

  /**
   * {@inheritdoc}
   */
  public function getUser() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setUser(UserInterface $user) {
    $this->set('user_id', $user->id());
    return $this;
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
  public function getEntity() {
    return $this->get('entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    $settings = $this->getFieldDefinition('entity_id')->getSettings();
    $bundle = $entity->bundle();

    if (
      !empty($settings['exclude_entity_types']) &&
      !in_array($bundle, $settings['entity_type_ids'])
    ) {
      return $this;
    }

    $reference_array = [
      'target_id' => $entity->id(),
      'target_type' => $bundle,
    ];

    $this->set('entity_id', $reference_array);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRole() {
    return $this->get('role_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setRole(RoleInterface $node) {
    $this->set('role_id', $node->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $permission) {
    $roles = $this->getUser()->getRoles();

    if (!empty($role = $this->getRole())) {
      $roles = array_unique($roles + [$role->id()]);
    }

    return $this->entityTypeManager()->getStorage('user_role')->isPermissionInRoles($permission, $roles);
  }

  /**
   * Checks if entity can be saved.
   *
   * @return bool
   *   Available or not.
   */
  public function isSaveAvailable() {
    $storage = $this->entityTypeManager()->getStorage($this->entityTypeId);
    return $storage->isSaveAvailable($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['oid'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(new TranslatableMarkup('UUID'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel($entity_type->getBundleLabel())
      ->setSetting('target_type', $entity_type->getBundleEntityType())
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User endpoint'))
      ->setDescription(t('The user ID of this relation.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(1)
      ->setRequired(TRUE);

    $entity_types = \Drupal::entityTypeManager()->getDefinitions();
    $fields['entity_id'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Related entity'))
      ->setDescription(t('The entity ID of this relation.'))
      ->setRevisionable(TRUE)
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

    $fields['role_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Provided role'))
      ->setDescription(t('The role ID for this relation.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_entity_view',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(1)
      ->setRequired(FALSE);

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
      ->setSetting('workflow_callback', ['Drupal\dynamic_ownership\Entity\UserOwnership', 'getWorkflowId']);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
