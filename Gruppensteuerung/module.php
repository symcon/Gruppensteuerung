<?php

declare(strict_types=1);

    class Gruppensteuerung extends IPSModule
    {
        public function Create()
        {
            //Never delete this line!
            parent::Create();

            //Properties
            $this->RegisterPropertyBoolean('Active', false);
            $this->RegisterPropertyString('Variables', '[]');
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

            $computedStatus = $this->computeStatus();
            $this->SetStatus($computedStatus);
            //Return if instance has issues
            if ($computedStatus != 102) {
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

            //Register variable of needed type with correct profile
            $referenceID = $variables[0]['VariableID'];
            $variableProfile = $this->getProfile($referenceID);
            $statusVariableID = @$this->GetIDForIdent('Status');
            $referenceType = $this->getType($referenceID);
            //StatusVariableID could be false. In order to prevent false beeing used in VariableExist we use intval() and convert it to 0
            if (IPS_VariableExists(intval($statusVariableID)) && ($referenceType != $this->getType($statusVariableID))) {
                $this->UnregisterVariable('Status');
            }
            switch ($referenceType) {
                case 0:
                    $this->RegisterVariableBoolean('Status', 'Status', $variableProfile, 0);
                    break;
                case 1:
                    $this->RegisterVariableInteger('Status', 'Status', $variableProfile, 0);
                    break;
                case 2:
                    $this->RegisterVariableFloat('Status', 'Status', $variableProfile, 0);
                    break;
                case 3:
                    $this->RegisterVariableString('Status', 'Status', $variableProfile, 0);
                    break;
            }
            $this->EnableAction('Status');
        }

        public function MessageSink($Timestamp, $SenderID, $MessageID, $Data)
        {
            $this->SendDebug('MessageSink', IPS_GetName($SenderID), 0);
            $this->RequestAction('Status', $Data[0]);
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

        private function computeStatus()
        {
            //Active
            if (!$this->ReadPropertyBoolean('Active')) {
                return 104;
            }

            $variables = json_decode($this->ReadPropertyString('Variables'), true);

            //List empty
            if (count($variables) <= 0) {
                return 204;
            }

            //Exist
            foreach ($variables as $variable) {
                if (!IPS_VariableExists($variable['VariableID'])) {
                    return 203;
                }
            }
            //ReferenceVariable
            $referenceVariableID = $variables[0]['VariableID'];

            //Same type
            $referenceType = $this->getType($referenceVariableID);
            foreach ($variables as $variable) {
                if ($this->getType($variable['VariableID']) != $referenceType) {
                    return 200;
                }
            }

            //Same profile
            $referenceProfile = $this->getProfile($referenceVariableID);
            foreach ($variables as $variable) {
                if ($this->getProfile($variable['VariableID']) != $referenceProfile) {
                    return 201;
                }
            }

            //Have action
            foreach ($variables as $variable) {
                if (!HasAction($variable['VariableID'])) {
                    return 202;
                }
            }

            //Everything ok
            return 102;
        }

        private function SwitchGroup($value)
        {
            $computedStatus = $this->computeStatus();
            if ($computedStatus != $this->GetStatus()) {
                $this->SetStatus($computedStatus);
            }
            if (($computedStatus == 102) && ($value != $this->GetValue('Status'))) {
                $this->SetValue('Status', $value);
                $variables = json_decode($this->ReadPropertyString('Variables'), true);

                foreach ($variables as $variable) {
                    if (GetValue($variable['VariableID']) != $value && HasAction($variable['VariableID'])) {
                        RequestAction($variable['VariableID'], $value);
                    } elseif (!HasAction($variable['VariableID'])) {
                        throw new Exception('One variable has no action.');
                    }
                }
            }
        }
    }
