<?php

namespace Drupal\Tests\paragraphs_paste\FunctionalJavascript;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\dblog\Controller\DbLogController;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;

/**
 * Base class for Javascript tests for paragraphs_paste module.
 *
 * @package Drupal\Tests\paragraphs_paste\FunctionalJavascript
 */
abstract class ParagraphsPasteJavascriptTestBase extends WebDriverTestBase {

  use LoginAdminTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs_paste_test',
    'field_ui',
    'dblog',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Content processing mode.
   *
   * @var bool
   *   Use experimental mode.
   */
  protected $experimentalMode = FALSE;

  /**
   * Simulate paste event.
   *
   * @param string $selector
   *   The CSS selector.
   * @param string $text
   *   Text to copy.
   */
  public function simulatePasteEvent($selector, $text) {
    if ($this->experimentalMode) {
      $this->getSession()
        ->executeScript("document.querySelector('{$selector}').dispatchEvent(new MouseEvent('mousedown')); var pasteData = new DataTransfer(); pasteData.setData('text/plain', '{$text}'); document.activeElement.contentDocument.querySelector('.cke_editable').dispatchEvent(new ClipboardEvent('paste', {clipboardData: pasteData}));");
    }
    else {
      $this->getSession()->executeScript("var pasteData = new DataTransfer(); pasteData.setData('text/plain', '{$text}'); document.querySelector('{$selector}').dispatchEvent(new ClipboardEvent('paste', {clipboardData: pasteData}));");
    }
  }

  /**
   * Set content processing mode.
   *
   * @param bool $experimental_mode
   *   Use experimental mode.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures, an exception is thrown.
   */
  public function setPasteMethod($experimental_mode) {
    $form_display_id = implode('.', ['node', 'article', 'default']);
    $form_display = EntityFormDisplay::load($form_display_id);
    $component = $form_display->getComponent('field_paragraphs');
    $component['third_party_settings']['paragraphs_paste']['experimental'] = $experimental_mode;
    $form_display->setComponent('field_paragraphs', $component);
    $form_display->save();

    $this->experimentalMode = $experimental_mode;
  }

  /**
   * Wait for element to be present.
   *
   * @param string $selector
   *   The CSS selector.
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 1000.
   * @param string $message
   *   (Optional) Message to pass to assertJsCondition().
   */
  public function waitForElementPresent($selector, $timeout = 1000, $message = '') {
    $this->assertJsCondition("document.querySelector('{$selector}')", $timeout, $message);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = \Drupal::database()->select('watchdog', 'w')
      ->fields('w', ['message', 'variables']);
    $andGroup = $query->andConditionGroup()
      ->condition('severity', 6, '<')
      ->condition('type', 'php');
    $group = $query->orConditionGroup()
      ->condition('severity', 4, '<')
      ->condition($andGroup);
    $query->condition($group);
    $query->groupBy('w.message');
    $query->groupBy('w.variables');

    $controller = DbLogController::create($this->container);

    // Check that there are no warnings in the log after installation.
    // $this->assertEqual($query->countQuery()->execute()->fetchField(), 0);.
    if ($query->countQuery()->execute()->fetchField()) {
      // Output all errors for modules tested.
      $errors = [];
      foreach ($query->execute()->fetchAll() as $row) {
        $errors[] = Unicode::truncate(Html::decodeEntities(strip_tags($controller->formatMessage($row))), 256, TRUE, TRUE);
      }
      throw new \Exception(print_r($errors, TRUE));
    }

    parent::tearDown();
  }

}
