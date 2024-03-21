<?php

namespace Drupal\dynamic_ownership\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use http\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for User ownership edit forms.
 *
 * @ingroup dynamic_ownership
 */
class UserOwnershipForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function processRoles(&$form, FormStateInterface $form_state) {
    $options = [];
    if (
      empty($entity = $form_state->getFormObject()->entity) ||
      empty($type = $entity->type->entity) ||
      empty($roles = $type->getTargetRoles())
    ) {
      $form['role_id']['widget']['#options'] = $options;
      return;
    }

    foreach ($form['role_id']['widget']['#options'] as $id => $option) {
      if ($id !== '_none' && !in_array($id, $roles)) {
        continue;
      }

      $options[$id] = $option;
    }

    $form['role_id']['widget']['#options'] = $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function processEntityEndpoint(&$form, FormStateInterface $form_state) {
    $entity = $form_state->getFormObject()->entity;
    $type = $entity->type->entity;
    $bundle = explode(':', $type->getTargetBundle());

    if (empty($bundle) && count($bundle) > 2) {
      throw new RuntimeException('Wrong target type configs');
    }

    $form['entity_id']['widget'][0]['target_type']['#options'] = array_intersect_key($form['entity_id']['widget'][0]['target_type']['#options'], array_flip([$bundle[0]]));
    $form['entity_id']['widget'][0]['target_id']['#target_type'] = $bundle[0];
    $form['entity_id']['widget'][0]['target_id']['#selection_handler'] = "default:{$bundle[0]}";

    if (empty($bundle[1])) {
      return;
    }

    $form['entity_id']['widget'][0]['target_id']['#selection_settings']['target_bundles'] = [$bundle[1]];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\dynamic_ownership\Entity\UserOwnership $entity */
    $form = parent::buildForm($form, $form_state);

    $this->processRoles($form, $form_state);
    $this->processEntityEndpoint($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $entity = $this->buildEntity($form, $form_state);
    if (
      count($form_state->getErrors()) ||
      $entity->isSaveAvailable()
    ) {
      return;
    }

    $form_state->setErrorByName('entity_id', $this->t('The current ownership cannot be saved'));
    $form_state->setErrorByName('user_id', $this->t('The current ownership cannot be saved'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label User ownership.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label User ownership.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.user_ownership.canonical', ['user_ownership' => $entity->id()]);
  }

}
