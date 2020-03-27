<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class GruppensteuerungValidationTest extends TestCaseSymconValidation
{
    public function testValidateGruppensteuerung(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateGruppensteuerungModule(): void
    {
        $this->validateModule(__DIR__ . '/../Gruppensteuerung');
    }
}