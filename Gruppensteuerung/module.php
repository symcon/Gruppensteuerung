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

            if (!$this->ReadPropertyBoolean('Active')) {
                $this->SetStatus(104);
                return;
            }

            if (count($variables) <= 0) {
                $this->SetStatus(204);
            }
            //Register references
            foreach ($this->GetReferenceList() as $referenceID) {
                $this->UnregisterReference($referenceID);
            }

            foreach ($variables as $variable) {
                $this->RegisterReference($variable['VariableID']);
            }

            //Check if all variables are the same type
            $referenceVariable = IPS_GetVariable($variables[0]['VariableID']);
            $referenceType = $referenceVariable['VariableType'];
            if (count($variables) > 1) {
                foreach ($variables as $variable) {
                    if (($referenceType != IPS_GetVariable($variable['VariableID'])['VariableType'])
                    ) {
                        $this->SetStatus(200);
                        return;
                    }
                }
            }

            //Check if all variables have the same profile
            foreach ($variables as $variable) {
                if (!$this->sameProfile($variable['VariableID'])) {
                    $this->SetStatus(201);
                }
            }

            //Check if all variables have an action
            foreach ($variables as $variable) {
                if (!HasAction($variable['VariableID'])) {
                    $this->SetStatus(202);
                    return;
                }
            }

            //Check if all variables exist
            foreach ($variables as $variable) {
                if (!IPS_VariableExists($variable['VariableID'])) {
                    $this->SetStatus(203);
                    return;
                }
            }

            //Register messages
            $messageList = array_keys($this->GetMessageList());
            foreach ($messageList as $message) {
                $this->UnregisterMessage($message, VM_UPDATE);
            }
            foreach ($variables as $variable) {
                $this->RegisterMessage($variable['VariableID'], VM_UPDATE);
            }

            $this->SetStatus(102);

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
            SetValue($this->GetIDForIdent('Status'), $Data[0]);
            $this->SwitchGroup($Data[0], $SenderID);
        }

        public function RequestAction($Ident, $Value)
        {
            switch ($Ident) {
                case 'Status':
                    $this->SetValue($Ident, $Value);
                    $this->SwitchGroup($Value, $this->GetIDForIdent('Status'));
                    break;
                default:
                    throw new Exception('InvalidIdent');
            }
        }

        private function sameProfile($variableID)
        {
            $referenceVariable = json_decode($this->ReadPropertyString('Variables'), true)[0]['VariableID'];
            $referenceVariableData = IPS_GetVariable($referenceVariable);
            $referenceCustomProfile = $referenceVariableData['VariableCustomProfile'];
            $referenceProfile = $referenceVariableData['VariableProfile'];
            $variableData = IPS_GetVariable($variableID);
            $customProfile = $variableData['VariableCustomProfile'];
            $profile = $variableData['VariableProfile'];
            if (($referenceCustomProfile == $customProfile || $referenceCustomProfile == $profile) ||
                ($referenceProfile == $referenceCustomProfile || $referenceProfile == $profile)) {
                return false;
            }
            return true;
        }

        private function SwitchGroup($value, $sender)
        {
            if ($this->GetStatus() == 102) {
                $variables = json_decode($this->ReadPropertyString('Variables'), true);
                $this->SendDebug('SingleVariable', json_encode($variables), 0);

                foreach ($variables as $variable) {
                    if ($variable['VariableID'] != $sender && GetValue($variable['VariableID']) != $value && HasAction($variable['VariableID'])) {
                        RequestAction($variable['VariableID'], $value);
                    }
                }
            }
        }
    }
