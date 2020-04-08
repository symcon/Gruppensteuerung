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
            $variableProfile = $referenceVariable['VariableCustomProfile'];
            $statusVariableID = @$this->GetIDForIdent('Status');
            if (!$statusVariableID) {
            } elseif (IPS_VariableExists($statusVariableID) && ($referenceType != IPS_GetVariable($statusVariableID)['VariableType'])) {
                $this->UnregisterVariable('Status');
            } else {
                return;
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

        private function computeStatus()
        {
            //Active
            if (!$this->ReadPropertyBoolean('Active')) {
                return 104;
            }

            $variables = json_decode($this->ReadPropertyString('Variables'), true);
            $referenceVariableID = $variables[0]['VariableID'];

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

            //Same type
            if (count($variables) > 1) {
                foreach ($variables as $variable) {
                    if (($this->getType($referenceVariableID) != $this->getType($variable['VariableID']))) {
                        return 200;
                    }
                }
            }

            //Same profile
            foreach ($variables as $variable) {
                if ($this->getProfile($variable['VariableID']) != $this->getProfile($referenceVariableID)) {
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
            if (($this->computeStatus() == 102) && ($value != $this->GetValue('Status'))) {
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
