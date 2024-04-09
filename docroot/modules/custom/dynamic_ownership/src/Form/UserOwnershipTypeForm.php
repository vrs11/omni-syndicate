<?php

namespace Drupal\dynamic_ownership\Form;

use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class UserOwnershipTypeForm.
 */
class UserOwnershipTypeForm extends EntityForm {

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
   * Process a form to add role settings.
   */
  protected function addRolesSettings(array &$form, FormStateInterface $form_state) {
    $user_ownership_type = $this->entity;

    if (
      !$form_state->isRebuilding() ||
      empty($values = $form_state->getValues()['target_roles'])
    ) {
      $values = $user_ownership_type->getTargetRoles();
    }

    $cnt = 1;
    if (!empty($values)) {
      $cnt = count($values);
    }

    if (
      $form_state->isValidationComplete() &&
      !empty($element = $form_state->getTriggeringElement()) &&
      $element['#parents'][0] == 'roles_add_more'
    ) {
      $cnt++;
    }

    $form['target_roles'] = [
      '#tree' => TRUE,
      '#type' => 'container',
      '#attributes' => [
        'id' => 'roles_container',
      ],
    ];

    for ($i = 0; $i < $cnt; $i++) {
      $form['target_roles'][$i] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('User role'),
        '#target_type' => 'user_role',
        '#default_value' => $this->entityTypeManager->getStorage('user_role')->load($values[$i]),
        '#maxlength' => 60,
      ];
    }

    $form['roles_add_more'] = [
      '#type' => 'button',
      '#value' => $this->t('Add a role'),
      '#href' => '',
      '#ajax' => [
        'callback' => [$this, 'addMoreRoleCallback'],
        'wrapper' => 'roles_container',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $user_ownership_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $user_ownership_type->label(),
      '#description' => $this->t("Label for the User ownership type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $user_ownership_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dynamic_ownership\Entity\UserOwnershipType::load',
      ],
      '#disabled' => !$user_ownership_type->isNew(),
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
    $form['target_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Available bundles'),
      '#options' => $bundle_options,
      '#default_value' => $user_ownership_type->getTargetBundle(),
      '#required' => TRUE,
      '#size' => 6,
      '#multiple' => FALSE,
    ];

    $conflict_bundles = $this->entityTypeBundleInfo->getBundleInfo('user_ownership');
    $conflict_bundles_options = [];
    foreach ($conflict_bundles as $bundle_name => $bundle_info) {
      if ($user_ownership_type->id() == $bundle_name) {
        continue;
      }

      $conflict_bundles_options[$bundle_name] = $bundle_info['label'];
    }
    natsort($conflict_bundles_options);
    $form['conflicts_with'] = [
      '#tree' => TRUE,
      '#type' => 'select',
      '#title' => $this->t('Conflicts with'),
      '#options' => $conflict_bundles_options,
      '#default_value' => $user_ownership_type->getConflictBundles(),
      '#size' => 6,
      '#multiple' => TRUE,
    ];

    $form['limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Limit'),
      '#default_value' => $user_ownership_type->getLimit(),
      '#description' => $this->t('A maximum number of ownerships per a user'),
    ];

    $this->addRolesSettings($form, $form_state);

    $form['entity_owner'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make a user an owner of an entity?'),
      '#default_value' => $user_ownership_type->isMakingEntityOwner(),
      '#description' => $this->t("Use this type of relations for claims, in case of many."),
    ];

    $form['default_relation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is Default?'),
      '#default_value' => $user_ownership_type->isDefaultRelation(),
      '#description' => $this->t("Use this type of relations for claims, in case of many."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addMoreRoleCallback($form, $form_state) {
    return $form['target_roles'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $user_ownership_type = $this->entity;
    $status = $user_ownership_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label User ownership type.', [
          '%label' => $user_ownership_type->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label User ownership type.', [
          '%label' => $user_ownership_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($user_ownership_type->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   *
   * This is the default entity object builder function. It is called before any
   * other submit handler to build the new entity object to be used by the
   * following submit handlers. At this point of the form workflow the entity is
   * validated and the form state can be updated, this way the subsequently
   * invoked handlers can retrieve a regular entity object to act on. Generally
   * this method should not be overridden unless the entity requires the same
   * preparation for two actions, see \Drupal\comment\CommentForm for an example
   * with the save and preview actions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove button and internal Form API values from submitted values.
    $values = $form_state->getValue('target_roles');
    $not_empty = [];
    foreach ($values as $value) {
      if (empty($value)) {
        continue;
      }

      $not_empty[] = $value;
    }
    $form_state->setValue('target_roles', $not_empty);

    parent::submitForm($form, $form_state);
  }
}
