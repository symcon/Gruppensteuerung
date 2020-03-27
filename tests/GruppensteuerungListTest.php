<?php

declare(strict_types=1);

define('VM_UPDATE', 10603);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class GruppensteuerungListTest extends TestCase
{
    protected function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();

        //Register our core stubs for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/CoreStubs/library.json');

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        parent::setUp();
    }

    public function testEmptyList()
    {
        $instanceID = IPS_CreateInstance('{F197064F-791F-0964-2B8E-563136E9B7B4}');
        IPS_ApplyChanges($instanceID);
        $this->assertEquals(104, IPS\InstanceManager::getStatus($instanceID));
    }

    public function testDifferentVariables()
    {
        $instanceID = IPS_CreateInstance('{F197064F-791F-0964-2B8E-563136E9B7B4}');

        $boolean1 = IPS_CreateVariable(0);
        $this->addAction($boolean1);

        $integer1 = IPS_CreateVariable(1);
        $this->addAction($integer1);

        $this->assertTrue(true);
        IPS_SetConfiguration($instanceID, json_encode([
            'Variables' => json_encode([
                [
                    'VariableID' => $boolean1
                ],
                [
                    'VariableID' => $integer1
                ]
            ])
        ]));

        IPS_ApplyChanges($instanceID);
        $this->assertTrue(true);
        $this->assertEquals(200, IPS\InstanceManager::getStatus($instanceID));
    }

    public function testNormalList()
    {
        $instanceID = IPS_CreateInstance('{F197064F-791F-0964-2B8E-563136E9B7B4}');

        $boolean1 = IPS_CreateVariable(0);
        $this->addAction($boolean1);

        $boolean2 = IPS_CreateVariable(0);
        $this->addAction($boolean2);

        $this->assertTrue(true);
        IPS_SetConfiguration($instanceID, json_encode([
            'Variables' => json_encode([
                [
                    'VariableID' => $boolean1
                ],
                [
                    'VariableID' => $boolean2
                ]
            ])
        ]));

        IPS_ApplyChanges($instanceID);
        $this->assertEquals(102, IPS\InstanceManager::getStatus($instanceID));
    }

    private function addAction(int $variableID)
    {
        $scriptID = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($scriptID, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');
        IPS_SetVariableCustomAction($variableID, $scriptID);
    }
}