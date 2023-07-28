<?php

namespace Drupal\Tests\mix\Functional;

use Drupal\system\Entity\Menu;
use Drupal\Tests\menu_ui\Functional\MenuUiTest;

/**
 * Tests content sync of the Mix module.
 *
 * @group mix
 */
class MixContentSyncMenuTest extends MenuUiTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'config',
    'serialization',
    'mix',
  ];

  /**
   * The contents of the config export tarball, held between test methods.
   *
   * @var string
   */
  protected $tarball;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalLogin($this->rootUser);

    // Create a custom menu programmatically.
    $this->menu = $this->addCustomMenu();
  }

  /**
   * Creates a custom menu.
   *
   * @return \Drupal\system\Entity\Menu
   *   The custom menu that has been created.
   */
  public function addCustomMenu() {
    // Try adding a menu using a menu_name that is too long.
    $this->drupalGet('admin/structure/menu/add');
    $id = 'custom-menu';
    $label = 'Custom Menu';
    $edit = [
      'id' => $id,
      'description' => '',
      'label' => $label,
    ];
    $this->drupalGet('admin/structure/menu/add');
    $this->submitForm($edit, 'Save');

    // Enable the block.
    $block = $this->drupalPlaceBlock('system_menu_block:' . $id);
    $this->blockPlacements[$id] = $block->id();
    return Menu::load($id);
  }

  /**
   * Adds a menu link using the UI.
   *
   * @param string $parent
   *   Optional parent menu link id.
   * @param string $path
   *   The path to enter on the form. Defaults to the front page.
   * @param string $menu_name
   *   Menu name. Defaults to 'tools'.
   * @param bool $expanded
   *   Whether or not this menu link is expanded. Setting this to TRUE should
   *   test whether it works when we do the authenticatedUser tests. Defaults
   *   to FALSE.
   * @param string $weight
   *   Menu weight. Defaults to 0.
   *
   * @return \Drupal\menu_link_content\Entity\MenuLinkContent
   *   A menu link entity.
   */
  public function addMenuLink($parent = '', $path = '/', $menu_name = 'tools', $expanded = FALSE, $weight = '0') {
    // View add menu link page.
    $this->drupalGet("admin/structure/menu/manage/$menu_name/add");
    $title = '!link_' . $this->randomMachineName(16);
    $edit = [
      'link[0][uri]' => $path,
      'title[0][value]' => $title,
      'description[0][value]' => '',
      'enabled[value]' => 1,
      'expanded[value]' => $expanded,
      'menu_parent' => $menu_name . ':' . $parent,
      'weight[0][value]' => $weight,
    ];
    // Add menu link.
    $this->submitForm($edit, 'Save');
    $menu_links = \Drupal::entityTypeManager()->getStorage('menu_link_content')->loadByProperties(['title' => $title]);
    $menu_link = reset($menu_links);
    return $menu_link;
  }

  /**
   * Test callback.
   */
  public function testContentSyncMenu() {

    // Add menu links.
    $menu_id = $this->menu->id();
    $node1 = $this->drupalCreateNode(['type' => 'article']);
    $node2 = $this->drupalCreateNode(['type' => 'article']);
    $node3 = $this->drupalCreateNode(['type' => 'article']);
    $item1 = $this->addMenuLink('', '/node/' . $node1->id(), $menu_id, TRUE);
    $item2 = $this->addMenuLink($item1->getPluginId(), '/node/' . $node2->id(), $menu_id, FALSE);
    $item3 = $this->addMenuLink($item2->getPluginId(), '/node/' . $node3->id(), $menu_id);

    // Assert links in block.
    $this->assertSession()->linkByHrefExists('/node/1');
    $this->assertSession()->linkByHrefExists('/node/2');
    $this->assertSession()->linkByHrefNotExists('/node/3');

    // Assert links in menu manage page.
    $this->drupalGet('admin/structure/menu/manage/' . $menu_id);
    $this->assertSession()->linkByHrefExists('/node/3');

    // Enable content sync.
    $config = \Drupal::configFactory()->getEditable('mix.settings');
    $config->set('show_content_sync_id', TRUE)->save();
    // Rebulid all to load the serializer service.
    $this->rebuildAll();

    // Assert the sync link.
    $this->drupalGet('admin/structure/menu/manage/' . $menu_id);
    $this->assertSession()->linkExists('No');

    // Add UUID.
    $id1 = 'menu_link_content.' . $item1->uuid();
    $id2 = 'menu_link_content.' . $item2->uuid();
    $id3 = 'menu_link_content.' . $item3->uuid();
    $config->set('content_sync_ids', [$id1, $id2, $id3])->save();
    // Clear cache to update the sync link in menu item list.
    drupal_flush_all_caches();

    // Assert content_sync_ids.
    $content_sync_ids = $config->get('content_sync_ids');
    $this->assertTrue(in_array($id1, $content_sync_ids));
    $this->assertTrue(in_array($id2, $content_sync_ids));
    $this->assertTrue(in_array($id3, $content_sync_ids));

    // Assert the stop sync link.
    $this->drupalGet('admin/structure/menu/manage/' . $menu_id);
    $this->assertSession()->linkExists('Yes');

    // Export the configuration.
    // @see ConfigExportImportUITest::testExportImport().
    $this->drupalGet('admin/config/development/configuration/full/export');
    $this->submitForm([], 'Export');
    $this->tarball = $this->getSession()->getPage()->getContent();

    // Delete the block.
    $this->menu->delete();
    $this->drupalGet('admin/structure/menu');
    // Block should be removed.
    $this->assertSession()->linkByHrefNotExists('/node/1');
    // Menu management link should be removed.
    $this->assertSession()->linkByHrefNotExists('admin/structure/menu/manage/' . $menu_id);

    // Import the configuration.
    // @see ConfigExportImportUITest::testExportImport().
    $filename = 'temporary://' . $this->randomMachineName();
    file_put_contents($filename, $this->tarball);
    $this->drupalGet('admin/config/development/configuration/full/import');
    $this->submitForm(['files[import_tarball]' => $filename], 'Upload');
    $this->submitForm([], 'Import all');

    // Generate content.
    $this->drupalGet('admin/config/mix');
    $this->submitForm([], 'Generate missing contents');
    $this->assertSession()->pageTextContains('was generated successfully.');

    // Block content shows up.
    $this->drupalGet('admin/structure/menu/manage/' . $menu_id);
    $this->assertSession()->linkByHrefExists('/node/1');
    $this->assertSession()->linkByHrefExists('/node/2');
  }

}
