<?php

namespace Drupal\entity_reference_confirmation\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\dynamic_ownership\UserOwnershipAccessManager;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "entity_reference_confirm_autocomplete",
 *   label = @Translation("Autocomplete"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference_confirm"
 *   }
 * )
 */
class EntityReferenceConfirmWidget extends EntityReferenceAutocompleteWidget {

  /**
   * User Ownership Access Manager.
   *
   * @var \Drupal\dynamic_ownership\UserOwnershipAccessManager
   */
  protected UserOwnershipAccessManager $userOwnershipAccessManager;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\dynamic_ownership\UserOwnershipAccessManager $user_ownership_access_manager
   *    User Ownership Access Manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    UserOwnershipAccessManager $user_ownership_access_manager
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->userOwnershipAccessManager = $user_ownership_access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('dynamic_ownership.access.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $referenced_entities = $items->referencedEntities();

    $selection_settings = [];
    // Append the match operation to the selection settings.
    if ($this->getFieldSetting('handler_settings') !== NULL) {
      $selection_settings = $this->getFieldSetting('handler_settings');
    }
    $selection_settings += [
      'match_operator' => $this->getSetting('match_operator'),
      'match_limit' => $this->getSetting('match_limit'),
    ];

    // Append the entity if it is already created.
    if (!$entity->isNew()) {
      $selection_settings['entity'] = $entity;
    }

    $allow_edit = !empty($items[$delta]->get('target_id')->getValue());
    $element += [
      '#type' => 'entity_autocomplete',
      '#target_type' => $this->getFieldSetting('target_type'),
      '#selection_handler' => $this->getFieldSetting('handler'),
      '#selection_settings' => $selection_settings,
      // Entity reference field items are handling validation themselves via
      // the 'ValidReference' constraint.
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => $referenced_entities[$delta] ?? NULL,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#disabled' => $allow_edit,
    ];

    if ($bundle = $this->getAutocreateBundle()) {
      $element['#autocreate'] = [
        'bundle' => $bundle,
        'uid' => ($entity instanceof EntityOwnerInterface) ? $entity->getOwnerId() : $this->currentUser->id(),
      ];
    }

    $elements = [];
    if (!empty($items[$delta]->get('state')->getValue())) {
      $elements['status'] = [
        '#type' => 'markup',
        '#markup' => $this->t('State: @state', ['@state' => $items[$delta]->get('state')->getValue()]),
      ];
    }

    $elements['target_id'] = $element;

    if ($this->checkConfirmWidgetAccess($items)) {
      $elements['auto_approve'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Auto approve the relation'),
        '#default_value' => !$allow_edit,
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkConfirmWidgetAccess(FieldItemListInterface $items) {
    $permissions = ['change any entity reference relation state'];

    if ($is_new = ($entity = $items->getEntity())->isNew()) {
      $permissions[] = 'change own entity reference relation state';
    }

    return $this->userOwnershipAccessManager->dynamicAccessCheck($permissions, $is_new ? NULL : $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      // The entity_autocomplete form element returns an array when an entity
      // was "autocreated", so we need to move it up a level.
      if (is_array($value['target_id'])) {
        unset($values[$key]['target_id']);
        $values[$key] += $value['target_id'];
      }
    }

    return $values;
  }

}
