<?php

declare(strict_types=1);

class GroupControl extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyBoolean('Active', false);
        $this->RegisterPropertyString('Variables', '[]');
        $this->RegisterPropertyInteger('DebounceDelay', 200);
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $variables = json_decode($this->ReadPropertyString('Variables'), true);

        //Register references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        foreach ($variables as $variable) {
            $this->RegisterReference($variable['VariableID']);
        }

        $this->setUpModule();

        if ($this->GetStatus() != IS_ACTIVE) {
            return;
        }

        //Register messages
        $messageList = array_keys($this->GetMessageList());
        foreach ($messageList as $message) {
            $this->UnregisterMessage($message, VM_UPDATE);
        }
        foreach ($variables as $variable) {
            $this->RegisterMessage($variable['VariableID'], VM_UPDATE);
        }
    }

    public function MessageSink($Timestamp, $SenderID, $MessageID, $Data)
    {
        $this->SendDebug('MessageSink', IPS_GetName($SenderID) . " = " . $Data[0], 0);
        $this->SwitchGroup($Data[0]);
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Status':
                $this->SwitchGroup($Value);
                break;
            default:
                throw new Exception('InvalidIdent');
        }
    }

    private function getProfile($variableID)
    {
        $variableData = IPS_GetVariable($variableID);
        if ($variableData['VariableCustomProfile'] == '') {
            return $variableData['VariableProfile'];
        } else {
            return $variableData['VariableCustomProfile'];
        }
    }

    private function getType($variableID)
    {
        return IPS_GetVariable($variableID)['VariableType'];
    }

    private function setUpModule()
    {
        //Active
        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $variables = json_decode($this->ReadPropertyString('Variables'), true);

        //List empty
        if (count($variables) <= 0) {
            $this->SetStatus(IS_EBASE + 4);
            return;
        }

        //Exist
        foreach ($variables as $variable) {
            if (!IPS_VariableExists($variable['VariableID'])) {
                $this->SetStatus(IS_EBASE + 3);
                return;
            }
        }
        //ReferenceVariable
        $referenceVariableID = $variables[0]['VariableID'];
        $referenceType = $this->getType($referenceVariableID);
        $referenceProfile = $this->getProfile($referenceVariableID);

        //Same type
        foreach ($variables as $variable) {
            if ($this->getType($variable['VariableID']) != $referenceType) {
                $this->SetStatus(IS_EBASE);
                return;
            }
        }

        //Same profile
        foreach ($variables as $variable) {
            if ($this->getProfile($variable['VariableID']) != $referenceProfile) {
                $this->SetStatus(IS_EBASE + 1);
                return;
            }
        }

        //Register variable of needed type with correct profile
        $statusVariableID = @$this->GetIDForIdent('Status');
        //StatusVariableID could be false. In order to prevent false beeing used in VariableExist we use intval() and convert it to 0
        if (IPS_VariableExists(intval($statusVariableID)) && ($referenceType != $this->getType($statusVariableID))) {
            $this->UnregisterVariable('Status');
        }
        //Update profile of status if necessary
        switch ($referenceType) {
            case 0:
                $this->RegisterVariableBoolean('Status', $this->Translate('Status'), $referenceProfile, 0);
                break;

            case 1:
                $this->RegisterVariableInteger('Status', $this->Translate('Status'), $referenceProfile, 0);
                break;

            case 2:
                $this->RegisterVariableFloat('Status', $this->Translate('Status'), $referenceProfile, 0);
                break;

            case 3:
                $this->RegisterVariableString('Status', $this->Translate('Status'), $referenceProfile, 0);
                break;
        }
        $this->EnableAction('Status');

        //Have action
        foreach ($variables as $variable) {
            if (!HasAction($variable['VariableID'])) {
                $this->SetStatus(IS_EBASE + 2);
                return;
            }
        }

        //Everything ok
        $this->SetStatus(IS_ACTIVE);
    }

    private function SwitchGroup($value)
    {
        if (IPS_SemaphoreEnter("SwitchGroup" . $this->InstanceID, 10)) {
            try {
                $this->setUpModule();
                $instanceStatus = $this->GetStatus();
                if (($instanceStatus == IS_ACTIVE) && ($value != $this->GetValue('Status'))) {
                    $variables = json_decode($this->ReadPropertyString('Variables'), true);

                    // Validate all variables first so the group is never left half switched
                    foreach ($variables as $variable) {
                        if (!HasAction($variable['VariableID'])) {
                            throw new Exception('One variable has no action.');
                        }
                    }

                    $this->SetValue('Status', $value);
                    foreach ($variables as $variable) {
                        if (GetValue($variable['VariableID']) != $value) {
                            RequestAction($variable['VariableID'], $value);
                        }
                    }
                } elseif ($instanceStatus != IS_ACTIVE) {
                    $statuscodes = [];
                    $statusForm = json_decode(IPS_GetConfigurationForm($this->InstanceID), true)['status'];
                    foreach ($statusForm as $status) {
                        $statuscodes[$status['code']] = $status['caption'];
                    }
                    $this->LogMessage($this->Translate($statuscodes[$instanceStatus]), KL_ERROR);
                }
                IPS_Sleep($this->ReadPropertyInteger("DebounceDelay"));
            } finally {
                IPS_SemaphoreLeave("SwitchGroup" . $this->InstanceID);
            }
        }
    }
}
