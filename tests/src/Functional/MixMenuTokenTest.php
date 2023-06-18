<?php

namespace Drupal\Tests\mix\Functional;

use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests advanced menu settings.
 *
 * @group mix
 */
class MixMenuTokenTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'menu_ui', 'menu_link_content', 'mix'];

  /**
   * Array of placed menu blocks keyed by block ID.
   *
   * @var array
   */
  protected $blockPlacements;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests menu item visibility by role.
   */
  public function testMenuToken() {

    // Hierarchy
    // <$menu>
    // - item1 (menu token)
    $menu = $this->addCustomMenu();

    // Change site name.
    $this->config('system.site')->set('name', 'Drupal Test')->save();

    // A normal parent menu link.
    $this->addMenuLink('', '[current-user:name]', '/user/[current-user:uid]', $menu->id(), TRUE, 0);
    $this->addMenuLink('', '[site:name]', '/', $menu->id(), TRUE, 0);

    // Test menu token [current-user:name] and [current-user:uid].
    $this->assertSession()->linkExists('admin');
    $this->assertSession()->linkByHrefExists('/user/1');

    // Create accounts to test menu token.
    $account2_name = 'User 2';
    $account2 = $this->createUser([], $account2_name, FALSE, ['uid' => 2]);
    $account3_name = 'User 3';
    $account3 = $this->createUser([], $account3_name, FALSE, ['uid' => 3]);

    // Login $account2.
    $this->drupalLogin($account2);

    // Menu token test.
    // $this->drupalGet('<front>');
    // Test menu token [current-user:name] and [current-user:uid].
    $this->assertSession()->linkExists($account2_name);
    $this->assertSession()->linkByHrefExists('/user/2');
    // Test menu token [site:name].
    $this->assertSession()->linkExists('Drupal Test');

    $this->drupalLogin($account3);
    // $this->drupalGet('<front>');
    // Test menu token [current-user:name] and [current-user:uid].
    $this->assertSession()->linkExists($account3_name);
    $this->assertSession()->linkByHrefExists('/user/3');
  }

  /**
   * Creates a custom menu.
   *
   * @return \Drupal\system\Entity\Menu
   *   The custom menu that has been created.
   */
  public function addCustomMenu() {
    // Add a custom menu.
    $this->drupalGet('admin/structure/menu/add');
    $menu_name = strtolower($this->randomMachineName());
    $label = $this->randomMachineName();
    $edit = [
      'id' => $menu_name,
      'description' => '',
      'label' => $label,
    ];
    $this->submitForm($edit, 'Save');

    // Enable the block.
    $block = $this->drupalPlaceBlock('system_menu_block:' . $menu_name);
    $this->blockPlacements[$menu_name] = $block->id();

    return Menu::load($menu_name);
  }

  /**
   * Add a menu link.
   */
  public function addMenuLink($parent = '', $title = 'link', $path = '/', $menu_name = 'custom', $expanded = FALSE, $weight = 0, $advancedSettings = []) {
    // Go to add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $edit = [
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' => $menu_name . ':' . $parent,
      'weight[0][value]' => $weight,
    ];
    $edit += $advancedSettings;

    $this->submitForm($edit, 'Save');

    $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $title]);
    $menu_link = reset($menu_links);
    return $menu_link;
  }

}
