<?php
namespace SlimApi\PhinxTest\Database;

use SlimApi\Phinx\Database\PhinxService;
use Symfony\Component\Console\Application;
use org\bovigo\vfs\vfsStream;

class PhinxApplicationMock {public function run(){return true;} public function find($name) { $class = '\Phinx\Console\Command\\'.ucfirst($name); return new $class; }}
class PhinxServiceTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        // $phinxApplicationMock = $this->getMockBuilder('Symfony\Component\Console\Application')
        //     ->disableOriginalConstructor()
        //     ->getMock();
        // $phinxApplicationMock->method('run')
        //     ->willReturn(true);
        $phinxApplicationMock = $this->getMockBuilder('stdClass')
            ->getMock();
        $phinxApplicationMock->method('run')
            ->willReturn(true);
        $this->phinxService = new PhinxService($phinxApplicationMock);
    }

    public function testInvalidMigrationType()
    {
        $this->setExpectedException('Exception', 'Invalid migration command.');
        $this->phinxService->processCommand('foo');
    }

    public function testCreateTable()
    {
        $this->phinxService->processCommand('create', 'test');
        $this->assertEquals(1, count($this->phinxService->commands));
        $this->assertEquals('$table = $this->table("test");', $this->phinxService->commands[0]);
    }

    public function testAddColumnIncorrectType()
    {
        $this->setExpectedException('Exception', 'Type not valid.');
        $this->phinxService->processCommand('addColumn', 'test', 'int');
    }

    public function testAddColumnSimple()
    {
        $this->phinxService->processCommand('addColumn', 'test', 'integer');
        $this->assertEquals(1, count($this->phinxService->commands));
        $this->assertEquals('$table->addColumn("test", "integer");', $this->phinxService->commands[0]);
    }

    public function testAddColumnWithLimit()
    {
        $this->phinxService->processCommand('addColumn', 'test', 'integer', '30');
        $this->assertEquals(1, count($this->phinxService->commands));
        $this->assertEquals('$table->addColumn("test", "integer", ["limit" => 30]);', $this->phinxService->commands[0]);
    }

    public function testAddColumnWithNull()
    {
        $this->phinxService->processCommand('addColumn', 'test', 'integer', null, 'true');
        $this->assertEquals(1, count($this->phinxService->commands));
        $this->assertEquals('$table->addColumn("test", "integer", ["null" => true]);', $this->phinxService->commands[0]);
    }

    public function testAddColumnWithUnique()
    {
        $this->phinxService->processCommand('addColumn', 'test', 'integer', null, null, 'true');
        $this->assertEquals(1, count($this->phinxService->commands));
        $this->assertEquals('$table->addColumn("test", "integer", ["unique" => true]);', $this->phinxService->commands[0]);
    }

    public function testAddColumnWithEverything()
    {
        $this->phinxService->processCommand('addColumn', 'test', 'integer', '30', 'false', 'true');
        $this->assertEquals(1, count($this->phinxService->commands));
        $this->assertEquals('$table->addColumn("test", "integer", ["limit" => 30, "null" => false, "unique" => true]);', $this->phinxService->commands[0]);
    }

    public function testFinaliseTable()
    {
        $this->phinxService->processCommand('finalise');
        $this->assertEquals(3, count($this->phinxService->commands));
        $this->assertEquals(['$table->addColumn("updated_at", "timestamp");', '$table->addColumn("created_at", "timestamp");', '$table->create();'], $this->phinxService->commands);
    }

    /**
     * @runInSeparateProcess
     */
    public function testModelExists()
    {
        $phinxService = new PhinxService(new \Phinx\Console\PhinxApplication);

        chdir(__DIR__.'/../output');
        if (file_exists('project0')) {
            exec('rm -rf project0');
        }
        mkdir('project0');

        $this->assertFalse(is_file('project0/phinx.yml'));
        $phinxService->init('project0/');
        $this->assertTrue(is_file('project0/phinx.yml'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testMigrationCreation()
    {
        $phinxService = new PhinxService(new \Phinx\Console\PhinxApplication);
        $phinxStr =<<<'EOT'
paths:
    migrations: %%PHINX_CONFIG_DIR%%/migrations

environments:
    default_migration_table: phinxlog
    default_database: development
    development:
        adapter: mysql
        host: localhost
        name: development_db
        user: root
        pass: ''
        port: 3306
        charset: utf8
EOT;
        chdir(__DIR__.'/../output');
        if (file_exists('project0')) {
            exec('rm -rf project0');
        }
        mkdir('project0');
        mkdir('project0/migrations');
        file_put_contents('project0/phinx.yml', $phinxStr);
        chdir('project0');

        $this->assertCount(0, glob('migrations/*test.php'));
        $phinxService->processCommand('create', 'test');
        $phinxService->processCommand('finalise');
        $phinxService->create('Test');
        $this->assertCount(1, glob('migrations/*test.php'));
    }

    public function testTargetLocation()
    {
        $this->assertEquals('', $this->phinxService->targetLocation('Foo'));
    }
}
