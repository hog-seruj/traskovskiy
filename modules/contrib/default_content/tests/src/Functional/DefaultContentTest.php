<?php

namespace Drupal\Tests\default_content\Functional;

use Drupal\simpletest\BrowserTestBase;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * Test import of default content.
 *
 * @group default_content
 */
class DefaultContentTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('rest', 'taxonomy', 'hal', 'default_content');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(array('type' => 'page'));
  }

  /**
   * Test importing default content.
   */
  public function testImport() {
    // Login as admin.
    $this->drupalLogin($this->drupalCreateUser(array_keys(\Drupal::moduleHandler()->invokeAll(('permission')))));
    // Enable the module and import the content.
    \Drupal::service('module_installer')->install(array('default_content_test'), TRUE);

    $this->rebuildContainer();
    $node = $this->getNodeByTitle('Imported node');
    $this->assertEquals($node->body->value, 'Crikey it works!');
    $this->assertEquals($node->getType(), 'page');
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple();
    $term = reset($terms);
    $this->assertTrue(!empty($term));
    $this->assertEquals($term->name->value, 'A tag');
    $term_id = $node->field_tags->target_id;
    $this->assertTrue(!empty($term_id), 'Term reference populated');
  }

  /**
   * Test re-importing default content.
   */
  public function testReImport() {
    // Login as admin.
    $this->drupalLogin($this->drupalCreateUser(array_keys(\Drupal::moduleHandler()->invokeAll(('permission')))));

    // Enable the module and import the content.
    \Drupal::service('module_installer')->install(['default_content_test'], TRUE);
    $this->rebuildContainer();
    $original_nodes = \Drupal::entityTypeManager()->getListBuilder('node')->getStorage()->loadByProperties(['type' => 'page']);

    // Change the node content.
    $node = $this->getNodeByTitle('Imported node');
    $node->title = 'Updated node';
    $node->save();

    // Re-import the content and check there are no changes.
    \Drupal::service('default_content.manager')->importContent('default_content_test');
    $new_nodes = \Drupal::entityTypeManager()->getListBuilder('node')->getStorage()->loadByProperties(['type' => 'page']);
    $this->assertSame(array_keys($new_nodes), array_keys($original_nodes), 'No new content has been imported.');
    $node = $this->getNodeByTitle('Imported node');
    $this->assertEmpty($node, "Imported content has not been updated.");

    // Re-import the content and check the content has been updated.
    \Drupal::service('default_content.manager')->importContent('default_content_test', TRUE);
    $node = $this->getNodeByTitle('Imported node');
    $this->assertNotEmpty($node, "Imported content has been updated.");
  }

}
