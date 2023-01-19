<?php

namespace Drupal\Tests\mix\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests the UI of node revision field.
 *
 * @group mix
 */
class MixTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $editor;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mix'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create users.
    $this->editor = $this->drupalCreateUser([
      'create page content',
      'edit own page content',
    ]);
  }

  /**
   * Test hide revision field.
   */
  public function testHideRevisionField() {

    $addPagePath = 'node/add/page';
    $xpath = "//*[@id='edit-revision-information']";

    // Editor can see revision field by default.
    $this->drupalLogin($this->editor);
    $this->drupalGet($addPagePath);
    $this->assertSession()->elementExists('xpath', $xpath);

    // After hide_revision_field enabled, hide revision field to everyone except UID 1.
    $this->config('mix.settings')->set('hide_revision_field', TRUE)->save();
    $this->drupalGet($addPagePath);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // UID 1 still can see the revision field.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet($addPagePath);
    $this->assertSession()->elementExists('xpath', $xpath);
  }

  /**
   * Test environment indicator.
   */
  public function testEnvironmentIndicator() {

    $this->drupalLogin($this->rootUser);

    // No Mix environment indicator by default.
    $adminPath = '/admin';
    $xpath = "//div[@id='mix-environment-indicator']";
    $this->drupalGet($adminPath);
    $this->assertSession()->elementNotExists('xpath', $xpath);

    // Set environment indicator text.
    $text = 'Development Indicator';
    \Drupal::state()->set('mix.environment_indicator', $text);

    // Mix environment indicator shows up.
    $this->drupalGet($adminPath);
    $this->assertSession()->elementExists('xpath', $xpath);
    $this->assertSession()->elementTextEquals('xpath', $xpath . '/text()', $text);

    // Clear text to hide the indicator.
    \Drupal::state()->set('mix.environment_indicator', '');
    $this->drupalGet($adminPath);
    $this->assertSession()->elementNotExists('xpath', $xpath);
  }

}
