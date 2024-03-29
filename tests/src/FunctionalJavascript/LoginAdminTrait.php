<?php

namespace Drupal\Tests\paragraphs_paste\FunctionalJavascript;

/**
 * Test trait for logging admin in JS tests.
 *
 * @todo Remove after paragraphs release > 1.13.
 */
trait LoginAdminTrait {

  /**
   * Creates an user with admin permissions and log in.
   *
   * @param array $additional_permissions
   *   Additional permissions that will be granted to admin user.
   * @param bool $reset_permissions
   *   Flag to determine if default admin permissions will be replaced by
   *   $additional_permissions.
   *
   * @return object
   *   Newly created and logged in user object.
   */
  public function loginAsAdmin(array $additional_permissions = [], $reset_permissions = FALSE) {

    $permissions = [
      'administer content types',
      'administer node fields',
      'administer paragraphs types',
      'administer node form display',
      'administer paragraph fields',
      'administer paragraph form display',
      'bypass node access',
    ];

    if ($reset_permissions) {
      $permissions = $additional_permissions;
    }
    elseif (!empty($additional_permissions)) {
      $permissions = array_merge($permissions, $additional_permissions);
    }

    $this->admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->admin_user);
    return $this->admin_user;
  }

}
