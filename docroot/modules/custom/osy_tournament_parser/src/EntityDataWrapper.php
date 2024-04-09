<?php

namespace Drupal\osy_tournament_parser;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;

class EntityDataWrapper {
  public function __construct(
    protected array &$data,
    protected EntityInterface $entity
  ) {}

  public static function wrap(array &$data, EntityInterface $entity) {
    return new static($data, $entity);
  }

  /**
   * @param string $field
   * @param string|callable $attribute
   * @param bool $direct
   *
   * @return $this
   */
  public function set(string $field, $attribute, $direct = FALSE) {
    if (is_callable($attribute)) {
      return $this->set($field, $attribute(), TRUE);
    }

    $value = $direct ? $attribute : (is_array($attribute) ? NestedArray::getValue($this->data, $attribute) : $this->data[$attribute]);

    if (empty($value)) {
      return $this;
    }

    $this->entity->$field = $value;

    return $this;
  }

  /**
   * Gets the value of an attribute.
   *
   * @param string|array $attribute
   *   The attribute name or path.
   *
   * @return mixed
   *   The value of the attribute.
   */
  public function get($attribute) {
    return is_array($attribute) ? NestedArray::getValue($this->data, $attribute) : $this->data[$attribute];
  }

}
