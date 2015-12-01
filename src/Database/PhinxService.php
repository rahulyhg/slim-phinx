<?php
namespace SlimApi\Phinx\Database;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use SlimApi\Interfaces\GeneratorServiceInterface;
use SlimApi\Migration\MigrationInterface;

class PhinxService implements MigrationInterface, GeneratorServiceInterface
{
    public $commands = [];
    private $application;
    private $types = ['string', 'text', 'integer', 'biginteger', 'float', 'decimal', 'datetime', 'timestamp', 'time', 'date', 'binary', 'boolean', 'reference'];

    public function __construct($phinxApp)
    {
        $this->application = $phinxApp;
    }

    public function init($directory)
    {
        $this->run('init', ['path' => $directory]);
    }

    public function processCommand($type, ...$arguments)
    {
        switch ($type) {
            case 'create':
                $this->createTable(strtolower($arguments[0]));
                break;
            case 'addColumn':
                $arguments = array_pad($arguments, 7, null);
                $this->addColumn(...$arguments);
                break;
            case 'finalise':
                $this->finalise();
                break;
            default:
                throw new \Exception('Invalid migration command.');
                break;
        }
    }

    public function create($name)
    {
        PhinxMigration::$commands = $this->commands;
        $this->run('create', ['command' => 'create', 'name' => $name, '--class' => 'SlimApi\Phinx\Database\PhinxMigration']);
    }

    public function targetLocation($name)
    {
        return '';
    }

    private function createTable($name)
    {
        $command          = '$table = $this->table("$name");';
        $command          = strtr($command, ['$name' => $name]);
        $this->commands[] = $command;
    }

    private function run($command, $args = [])
    {
        $defaultArgs = [];
        $command     = $this->application->find($command);
        $args        = array_merge($defaultArgs, $args);
        $input       = new ArrayInput($args);
        $output      = new NullOutput();
        $command->run($input, $output);
    }

    private function addColumn($name, $type, $limit, $nullable, $unique, $fkDelete, $fkUpdate)
    {
        if (!in_array($type, $this->types)) {
            throw new \Exception("Type not valid.");
        }

        switch ($type) {
            case 'reference':
                if (in_array(strtoupper($fkDelete), ['SET_NULL', 'NO_ACTION', 'CASCADE', 'RESTRICT'])) {
                    $fkDelete = strtoupper($fkDelete);
                } else {
                    $fkDelete = 'SET_NULL';
                }

                if (in_array(strtoupper($fkUpdate), ['SET_NULL', 'NO_ACTION', 'CASCADE', 'RESTRICT'])) {
                    $fkUpdate = strtoupper($fkUpdate);
                } else {
                    $fkUpdate = 'CASCADE';
                }

                if ('SET_NULL' === $fkDelete || 'SET_NULL' === $fkUpdate) {
                    $nullable = 'true';
                }

                // $refTable->addColumn('tag_id', 'integer')
                //  ->addForeignKey("tag_id", "tags", "id", array("delete"=> "SET_NULL", "update"=> "NO_ACTION"))
                $this->commands[] = sprintf('$table->addColumn("%s_id", "integer"%s);', $name, $this->getExtrasStr($limit, $nullable));
                $this->commands[] = sprintf('$table->addForeignKey("%s_id", "%s", "id", array("delete" => "%s", "update" => "%s"));', $name, $name, $fkDelete, $fkUpdate);
                break;

            default:
                $this->commands[] = sprintf('$table->addColumn("%s", "%s"%s);', $name, $type, $this->getExtrasStr($limit, $nullable));
                break;
        }

        // if ('true' === $unique || 'true' === $index) {
        if ('true' === $unique) {
            $this->commands[] = sprintf('$table->addIndex(["%s"], ["unique" => %s]);', $name, $unique);
        }
    }

    private function getExtrasStr($limit, $nullable)
    {
        $extras = [];

        if (!is_null($limit)) {
            $extras[] = sprintf('"limit" => %d', $limit);
        }

        if ('false' === $nullable || 'true' === $nullable) {
            $extras[] = sprintf('"null" => %s', $nullable);
        }

        if (count($extras) === 0) {
            return '';
        }

        $extrasStrPreFix  = ", [";
        $extrasStrPostFix = "]";
        return $extrasStrPreFix.implode(", ", $extras).$extrasStrPostFix;
    }

    private function finalise()
    {
        // need to add updated_at, created_at columns manually
        $this->processCommand('addColumn', 'updated_at', 'timestamp');
        $this->processCommand('addColumn', 'created_at', 'timestamp');

        $command          = '$table->create();';
        $this->commands[] = $command;
    }
}
