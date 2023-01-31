<?php

namespace Drupal\Tests\mix\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group mix
 */
class MixDevelopmentModeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'ajax_test', 'mix'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set up the test here.
  }

  /**
   * Test hide revision field.
   *
   * @covers Drupal\mix\EventSubscriber\MixSubscriber
   * @covers Drupal\mix\MixServiceProvider
   */
  public function testDevelopmentMode() {

    // Before enable development mode.
    // Anonymous user.
    $testPath = '/ajax-test/insert-block-wrapper';
    // First visit.
    $pageHtml = $this->drupalGet($testPath);
    // No caches.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Miss');
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Miss');
    // No twig debug info.
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $pageHtml, 'Twig debug markup not found in page source code when development mode is not enabled.');
    // CSS/JS not aggregated.
    $this->assertSession()->elementExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/js.module.css")]');
    // No dev mode message.
    $this->assertSession()->linkNotExists('Go online.');

    // Second visit.
    $this->drupalGet($testPath);
    // Page cache hit.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Hit');
    // No dynamic page cache for anonymous user.
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Miss');

    // Login as root user.
    $this->drupalLogin($this->rootUser);
    // First visit.
    $pageHtml = $this->drupalGet($testPath);
    // No page cache header for authenticated user.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    // Dynamic page cache MISS on first visit.
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Miss');
    // No twig debug info.
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $pageHtml, 'Twig debug markup not found in page source code when development mode is not enabled.');
    // CSS/JS not aggregated.
    $this->assertSession()->elementExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/js.module.css")]');
    // No dev mode message.
    $this->assertSession()->linkNotExists('Go online.');

    // Second visit.
    $pageHtml = $this->drupalGet($testPath);
    // No page cache for authenticated user.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    // Dynamic page cache HIT on second visit.
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Hit');

    // Enable development mode.
    $this->drupalGet('admin/config/mix');
    $edit = [];
    $edit['dev_mode'] = 1;
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);

    // After enable development mode.
    // First visit.
    $pageHtml = $this->drupalGet($testPath);
    // No dynamic page cache.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Dynamic-Cache');
    // No page cache.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    // Twig debug enabled.
    $this->assertStringContainsString('<!-- THEME DEBUG -->', $pageHtml, 'Twig debug markup found in page source code when development mode is enabled.');
    // CSS/JS not aggregated.
    $this->assertSession()->elementExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/js.module.css")]');
    // Dev mode message.
    $this->assertSession()->linkExists('Go online.');

    // Second visit.
    $pageHtml = $this->drupalGet($testPath);
    // Still no dynamic page cache.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Dynamic-Cache');
    // Still no page cache.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    // Twig debug still enabled.
    $this->assertStringContainsString('<!-- THEME DEBUG -->', $pageHtml, 'Twig debug markup found in page source code when development mode is enabled.');
    // CSS/JS still not aggregated.
    $this->assertStringContainsString('/core/modules/system/css/components/js.module.css', $pageHtml, 'js.module.css not found in page source code when development mode is enabled.');
    $this->assertStringContainsString('/core/misc/drupal.js', $pageHtml, 'drupal.js not found in page source code when development mode is enabled.');

    // Disabled development mode (switch to Prod mode).
    $this->drupalGet('admin/config/mix');
    $edit = [];
    $edit['dev_mode'] = 0;
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->statusCodeEquals(200);

    // After disable development mode.
    // First visit.
    $pageHtml = $this->drupalGet($testPath);
    // No page cache header for authenticated user.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    // Dynamic page cache MISS on first visit.
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Miss');
    // No twig debug info.
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $pageHtml, 'Twig debug markup not found in page source code when development mode is not enabled.');
    // CSS/JS aggregated.
    $this->assertSession()->elementNotExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/js.module.css")]');
    // No dev mode message.
    $this->assertSession()->linkNotExists('Go online.');

    // Second visit.
    $pageHtml = $this->drupalGet($testPath);
    // No page cache for authenticated user.
    $this->assertSession()->responseHeaderDoesNotExist('X-Drupal-Cache');
    // Dynamic page cache HIT on second visit.
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Hit');

    // Logout.
    $this->drupalLogout();

    // Anonymous user.
    // First visit.
    $pageHtml = $this->drupalGet($testPath);
    // No caches.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Miss');
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Miss');
    // No twig debug info.
    $this->assertStringNotContainsString('<!-- THEME DEBUG -->', $pageHtml, 'Twig debug markup not found in page source code when development mode is not enabled.');
    // CSS/JS aggregated.
    $this->assertSession()->elementNotExists('xpath', '//script[contains(@src, "/core/misc/drupal.js")]');
    $this->assertSession()->elementNotExists('xpath', '//link[contains(@href, "/core/modules/system/css/components/js.module.css")]');

    // Second visit.
    $this->drupalGet($testPath);
    // Page cache hit.
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache', 'Hit');
    // No dynamic page cache for anonymous user.
    $this->assertSession()->responseHeaderContains('X-Drupal-Dynamic-Cache', 'Miss');
  }

}
