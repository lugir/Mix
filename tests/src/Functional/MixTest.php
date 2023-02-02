<?php

namespace Drupal\Tests\mix\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests the UI of node revision field.
 *
 * @covers \Drupal\mix\Form\SettingsForm
 * @group mix
 */
class MixTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Editor user account.
   *
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
   *
   * @covers ::mix_form_alter
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
   *
   * @covers ::mix_page_top
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

  /**
   * Test hide revision field.
   *
   * @covers ::mix_page_attachments_alter
   */
  public function testRemoveGenerator() {

    // Disable browser cache.
    $this->config('system.performance')->set('cache.page.max_age', 0)->save();

    // Default behavior.
    $this->drupalGet('');
    // X-Generator exist.
    $this->assertSession()->responseHeaderExists('X-Generator');
    $this->assertSession()->responseContains('<meta name="Generator" content="Drupal');

    // Enable "remove_x_generator".
    $this->config('mix.settings')->set('remove_x_generator', 1)->save();

    $this->drupalLogin($this->rootUser);
    // No HTTP header X-Generator.
    $this->assertSession()->responseHeaderDoesNotExist('X-Generator');
    // No meta Generator.
    $this->assertSession()->responseNotContains('<meta name="Generator" content="Drupal');
  }

}
