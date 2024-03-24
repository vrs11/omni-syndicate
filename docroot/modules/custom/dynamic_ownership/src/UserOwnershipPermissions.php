<?php

namespace Drupal\dynamic_ownership;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\dynamic_ownership\Entity\UserOwnership;
use Drupal\dynamic_ownership\Entity\UserOwnershipType;


/**
 * Provides dynamic permissions for User ownership of different types.
 *
 * @ingroup dynamic_ownership
 *
 */
class UserOwnershipPermissions{

  use StringTranslationTrait;

  /**
   * Returns an array of node type permissions.
   *
   * @return array
   *   The UserOwnership by bundle permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function generatePermissions() {
    $perms = [];

    foreach (UserOwnershipType::loadMultiple() as $type) {
      $perms += $this->buildCommonFieldsPermissions() + $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of node permissions for a given node type.
   *
   * @param \Drupal\dynamic_ownership\Entity\UserOwnershipType $type
   *   The UserOwnership type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(UserOwnershipType $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    $permissions = [
      "create ownership: $type_id" => [
        'title' => $this->t('Create new ownership of type: %type_name', $type_params),
      ],
    ];

    foreach (['view', 'edit', 'delete'] as $op) {
      foreach (['own', 'any'] as $scope) {
        $permissions["$op $scope ownership: $type_id"] = [
          'title' => $this->t(
            "%op %scope ownership of type: %type_name", $type_params + [
              '%op' => ucwords($op),
              '%scope' => $scope,
            ]),
        ];
      }
    }

    foreach (UserOwnership::DEFAULT_FIELDS as $id => $field) {
      $permissions["edit own ownership: $type_id field $field"] = [
        'title' => $this->t('Edit own ownership: %type_name field %field', $type_params + ['%field' => $field]),
      ];
    }

    return $permissions;
  }

  /**
   * Returns a list of entity field permissions for a given entity type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildCommonFieldsPermissions() {
    $permissions = [];

    foreach (UserOwnership::DEFAULT_FIELDS as $id => $field) {
      $permissions["edit ownership field $field"] = [
        'title' => $this->t('Edit ownership field %field', ['%field' => $field]),
      ];
    }

    return $permissions;
  }

}
