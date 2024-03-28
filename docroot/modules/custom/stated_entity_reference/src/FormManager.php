<?php

declare(strict_types=1);

namespace Drupal\stated_entity_reference;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\stated_entity_reference\Entity\StatedEntityReference;

/**
 * Handles all form modifications.
 */
class FormManager {

  const MAIN_SUBMIT_BUTTON = 'submit';

  /**
   * Constructs a FormManager object.
   */
  public function __construct(
    private readonly ElementInfoManagerInterface $elementInfoManager,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function processEntityForm(&$form, FormStateInterface $form_state, $path, StatedEntityReference $ref, EntityInterface $source_entity): void {
    if (is_null(NestedArray::getValue($form, $path))) {
      NestedArray::setValue($form, $path, []);
    }
    $entry = &NestedArray::getValue($form, $path);
    $form['#stated_entity_reference'][] = $path;

    $inner_form_object = $this->entityTypeManager
      ->getFormObject('stated_entity_reference', 'edit')
      ->setEntity($ref);

    $form_state->set([md5(implode($path)), 'form_object'], $inner_form_object);
    $inner_form_state = static::createInnerFormState($form_state, $inner_form_object, $path);
    $inner_form = ['#parents' => $path];
    $entry = $inner_form_object->buildForm($inner_form, $inner_form_state, $source_entity);
    $entry["target_entity_id"]["widget"][0]["target_id"]["#required"] = FALSE;
    $entry['#type'] = 'container';
    $entry['#theme_wrappers'] = $this->elementInfoManager->getInfoProperty('container', '#theme_wrappers', []);
    unset($entry['form_token']);

    if (!empty($entry['#process'])) {
      $inner_form_state->set('#process', $entry['#process']);
      unset($entry['#process']);
    }
    else {
      $inner_form_state->set('#process', []);
    }

    if (!empty($entry['actions'])) {
      if (isset($entry['actions'][static::MAIN_SUBMIT_BUTTON])) {
        $entry['#submit'] = $entry['actions'][static::MAIN_SUBMIT_BUTTON]['#submit'];
      }

      unset($entry['actions']);
    }

    unset($entry['footer']);
    $form['actions'][static::MAIN_SUBMIT_BUTTON]['#validate'][] = [__CLASS__, 'validateForm'];
    $form['actions'][static::MAIN_SUBMIT_BUTTON]['#submit'][] = [__CLASS__, 'submitForm'];
  }

  /**
   * {@inheritdoc}
   */
  public static function processForm(array &$element, FormStateInterface &$form_state, array &$complete_form) {
    foreach ($element['#stated_entity_reference'] as $path) {
      if (empty($inner_form_state = static::getInnerFormState($form_state, $path))) {
        continue;
      }

      $entry = &NestedArray::getValue($element, $path);
      foreach ($inner_form_state->get('#process') as $callback) {
        NestedArray::setValue($element, $path, call_user_func_array(
          $inner_form_state->prepareCallback($callback),
          [&$entry, &$inner_form_state, &$complete_form]
        ));
      }
    }

    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form['#stated_entity_reference'] as $path) {
      if (
        empty($inner_form_state = static::getInnerFormState($form_state, $path))
        || empty($inner_form_state->getValue([...$path, 'target_entity_id', '0', 'target_id']))
      ) {
        continue;
      }

      $entry = &NestedArray::getValue($form, $path);
      $inner_form_object = $form_state->get([md5(implode($path)), 'form_object']);
      $inner_form_object->validateForm($entry, $inner_form_state);
      static::formValidator()->validateForm($inner_form_object->getFormId(), $entry, $inner_form_state);

      foreach ($inner_form_state->getErrors() as $error_element_path => $error) {
        $form_state->setErrorByName(implode('][', $path) . '][' . $error_element_path, $error);
      }

    }

    if (!empty($form_state->getErrors())) {
      return;
    }

    $form_state->setTemporaryValue('entity_validated', TRUE);
  }

  /**
   * {@inheritDoc}
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form['#stated_entity_reference'] as $path) {
      if (empty($inner_form_state = static::getInnerFormState($form_state, $path))) {
        continue;
      }

      if (empty($inner_form_state->getValue([...$path, 'target_entity_id', '0', 'target_id']))) {
        return;
      }
      $inner_form_state->setSubmitted();
      $entry = &NestedArray::getValue($form, $path);
      static::formSubmitter()->doSubmitForm($entry, $inner_form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static function getInnerFormState(FormStateInterface $form_state, $key) {
    if (empty($inner_form_state = $form_state->get([md5(implode($key)), 'form_state']))) {
      return NULL;
    }

    /** @var FormStateInterface $inner_form_state */
    $inner_form_state->setCompleteForm($form_state->getCompleteForm());
    $inner_form_state->setValues($form_state->getValues() ? : []);
    $inner_form_state->setUserInput($form_state->getUserInput() ? : []);
    return $inner_form_state;
  }

  /**
   * {@inheritdoc}
   */
  protected static function createInnerFormState(FormStateInterface $form_state, FormInterface $form_object, $key) {
    $inner_form_state = new FormState();
    $inner_form_state->setFormObject($form_object);
    $form_state->set([md5(implode($key)), 'form_state'], $inner_form_state);
    return $inner_form_state;
  }

  protected static function formValidator() {
    return \Drupal::service('form_validator');
  }
  protected static function formSubmitter() {
    return \Drupal::service('form_submitter');
  }

}
