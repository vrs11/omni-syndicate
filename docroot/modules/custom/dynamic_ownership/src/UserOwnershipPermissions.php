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
    $permissions = $this->buildCommonFieldsPermissions();

    foreach (UserOwnershipType::loadMultiple() as $type) {
      $permissions += $this->buildPermissions($type);
    }

    return $permissions;
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
      "create user_ownership: $type_id" => [
        'title' => $this->t('Create new ownership of type: %type_name', $type_params),
      ],
      "view active user_ownership: $type_id" => [
        'title' => $this->t('View active user_ownership of type: %type_name', $type_params),
      ],
      "edit own user_ownership: $type_id field" => [
        'title' => $this->t('Edit own user_ownership: %type_name fields', $type_params),
      ],
      "edit any user_ownership: $type_id field" => [
        'title' => $this->t('Edit any user_ownership: %type_name fields', $type_params),
      ],
    ];

    foreach (['view', 'edit', 'delete'] as $op) {
      foreach (['own', 'any'] as $scope) {
        $permissions["$op $scope user_ownership: $type_id"] = [
          'title' => $this->t(
            "%op %scope user_ownership of type: %type_name", $type_params + [
              '%op' => ucwords($op),
              '%scope' => $scope,
            ]),
        ];
      }
    }

    foreach (['own', 'any'] as $scope) {
      foreach (UserOwnership::DEFAULT_FIELDS as $field) {
        $permissions["$op $scope user_ownership: $type_id field $field"] = [
          'title' => $this->t('edit %scope user ownership: %type_name field %field', $type_params + [
              '%field' => $field,
              '%scope' => $scope,
            ]),
        ];
      }
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

    foreach (UserOwnership::DEFAULT_FIELDS as $field) {
      $permissions["edit user_ownership field $field"] = [
        'title' => $this->t('Edit user ownership field %field', ['%field' => $field]),
      ];
      $permissions["edit new user_ownership: field $field"] = [
        'title' => $this->t('Edit new user ownership field %field', ['%field' => $field]),
      ];
    }

    return $permissions;
  }

}
