<?php

declare(strict_types=1);

define('VM_UPDATE', 10603);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class GruppensteuerungFunctionalityTest extends TestCase
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

    public function testBaseFunctionality()
    {
        $instanceID = IPS_CreateInstance('{F197064F-791F-0964-2B8E-563136E9B7B4}');
        $interface = IPS\InstanceManager::getInstanceInterface($instanceID);

        $integer1 = IPS_CreateVariable(1);
        $this->addAction($integer1);

        $integer2 = IPS_CreateVariable(1);
        $this->addAction($integer2);

        IPS_SetConfiguration($instanceID, json_encode([
            'Variables' => json_encode([
                [
                    'VariableID' => $integer1
                ],
                [
                    'VariableID' => $integer2
                ]
            ])
        ]));

        IPS_ApplyChanges($instanceID);
        $statusVar = IPS_GetObjectIDByIdent('Status', $instanceID);

        //Switching using status variable
        RequestAction($statusVar, 100);

        $this->assertEquals(100, GetValue($integer1));
        $this->assertEquals(100, GetValue($integer2));
        $this->assertEquals(100, GetValue($statusVar));

        //Switching variable and simulating a send message
        RequestAction($integer1, 50);
        $interface->MessageSink(strtotime('01.01.2000'), $integer1, VM_UPDATE, [50]);

        $this->assertEquals(50, GetValue($integer1));
        $this->assertEquals(50, GetValue($integer2));
        $this->assertEquals(50, GetValue($statusVar));
    }

    private function addAction(int $variableID)
    {
        $scriptID = IPS_CreateScript(0 /* PHP */);
        IPS_SetScriptContent($scriptID, 'SetValue($_IPS[\'VARIABLE\'], $_IPS[\'VALUE\']);');
        IPS_SetVariableCustomAction($variableID, $scriptID);
    }
}