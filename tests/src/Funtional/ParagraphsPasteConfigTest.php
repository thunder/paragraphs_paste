<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that Paragraphs module can be uninstalled.
 *
 * @group paragraphs
 */
class ParagraphsPasteConfigTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['paragraphs_paste_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that paragraphs_paste can be installed.
   */
  public function testInstall() {}

}
