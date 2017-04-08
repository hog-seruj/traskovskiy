<?php

namespace Drupal\Tests\views\Unit\Plugin\Block;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\Block\ViewsBlock;

// @todo Remove this once the constant got converted.
if (!defined('BLOCK_LABEL_VISIBLE')) {
  define('BLOCK_LABEL_VISIBLE', 'visible');
}

/**
 * @coversDefaultClass \Drupal\views\Plugin\block\ViewsBlock
 * @group views
 */
class ViewsBlockTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executable;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $executableFactory;

  /**
   * The view entity.
   *
   * @var \Drupal\views\ViewEntityInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $view;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $storage;

  /**
   * The mocked user account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * The mocked display handler.
   *
   * @var \Drupal\views\Plugin\views\display\Block|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $displayHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp(); // TODO: Change the autogenerated stub
    $condition_plugin_manager = $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $condition_plugin_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array()));
    $container = new ContainerBuilder();
    $container->set('plugin.manager.condition', $condition_plugin_manager);
    \Drupal::setContainer($container);

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->setMethods(['buildRenderable', 'setDisplay', 'setItemsPerPage'])
      ->getMock();
    $this->executable->expects($this->any())
      ->method('setDisplay')
      ->with('block_1')
      ->will($this->returnValue(TRUE));
    $this->executable->expects($this->any())
      ->method('getShowAdminLinks')
      ->willReturn(FALSE);

    $this->executable->display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $this->view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $this->view->expects($this->any())
      ->method('id')
      ->willReturn('test_view');
    $this->executable->storage = $this->view;

    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($this->view)
      ->will($this->returnValue($this->executable));

    $this->displayHandler = $this->getMockBuilder('Drupal\views\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->getMock();

    $this->displayHandler->expects($this->any())
      ->method('blockSettings')
      ->willReturn([]);

    $this->displayHandler->expects($this->any())
      ->method('getPluginId')
      ->willReturn('block');

    $this->displayHandler->expects($this->any())
      ->method('getHandlers')
      ->willReturn([]);

    $this->executable->display_handler = $this->displayHandler;

    $this->storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->any())
      ->method('load')
      ->with('test_view')
      ->will($this->returnValue($this->view));
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
  }

  /**
   * Tests the build method.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuild() {
    $output = $this->randomMachineName(100);
    $build = array('view_build' => $output, '#view_id' => 'test_view', '#view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Block', '#view_display_show_admin_links' => FALSE, '#view_display_plugin_id' => 'block', '#pre_rendered' => TRUE);
    $this->executable->expects($this->once())
      ->method('buildRenderable')
      ->with('block_1', [])
      ->willReturn($build);

    $block_id = 'views_block:test_view-block_1';
    $config = array();
    $definition = array();

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);

    $this->assertEquals($build, $plugin->build());
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuildEmpty() {
    $build = ['view_build' => [], '#view_id' => 'test_view', '#view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Block', '#view_display_show_admin_links' => FALSE, '#view_display_plugin_id' => 'block', '#pre_rendered' => TRUE, '#cache' => ['contexts' => ['user']]];
    $this->executable->expects($this->once())
      ->method('buildRenderable')
      ->with('block_1', [])
      ->willReturn($build);

    $block_id = 'views_block:test_view-block_1';
    $config = [];
    $definition = [];

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);

    $this->assertEquals(array_intersect_key($build, ['#cache' => TRUE]), $plugin->build());
  }

  /**
   * Tests the build method with a failed execution.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuildFailed() {
    $output = FALSE;
    $this->executable->expects($this->once())
      ->method('buildRenderable')
      ->with('block_1', [])
      ->willReturn($output);

    $block_id = 'views_block:test_view-block_1';
    $config = array();
    $definition = array();

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);

    $this->assertEquals(array(), $plugin->build());
  }

}

// @todo https://www.drupal.org/node/2571679 replace
//   views_add_contextual_links().
namespace Drupal\views\Plugin\Block;

if (!function_exists('views_add_contextual_links')) {
  function views_add_contextual_links() {
  }
}
