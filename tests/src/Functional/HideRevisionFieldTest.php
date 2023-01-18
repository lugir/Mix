<?php

namespace Drupal\Tests\mix\Functional;

use Drupal\Tests\node\Functional\NodeTestBase;

/**
 * Tests the UI of node revision field.
 *
 * @group mix
 */
class HideRevisionFieldTest extends NodeTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

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
   * Test callback.
   */
  public function testHideRevisionField() {

    $addPagePath = 'node/add/page';
    $xpath = "//details[@id='edit-revision-information']";

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

}
