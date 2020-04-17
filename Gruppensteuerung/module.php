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

            $this->setUpModule();

            if ($this->GetStatus() != 102) {
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

        private function setUpModule()
        {
            //Active
            if (!$this->ReadPropertyBoolean('Active')) {
                $this->SetStatus(104);
                return;
            }

            $variables = json_decode($this->ReadPropertyString('Variables'), true);

            //List empty
            if (count($variables) <= 0) {
                $this->SetStatus(204);
                return;
            }

            //Exist
            foreach ($variables as $variable) {
                if (!IPS_VariableExists($variable['VariableID'])) {
                    $this->SetStatus(203);
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
                    $this->SetStatus(200);
                    return;
                }
            }

            //Same profile
            foreach ($variables as $variable) {
                if ($this->getProfile($variable['VariableID']) != $referenceProfile) {
                    $this->SetStatus(201);
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
                    $this->SetStatus(202);
                    return;
                }
            }

            //Everything ok
            $this->SetStatus(102);
            return;
        }

        private function SwitchGroup($value)
        {
            $this->setUpModule();
            $instanceStatus = $this->GetStatus();
            if (($instanceStatus == 102) && ($value != $this->GetValue('Status'))) {
                $this->SetValue('Status', $value);
                $variables = json_decode($this->ReadPropertyString('Variables'), true);

                foreach ($variables as $variable) {
                    if (GetValue($variable['VariableID']) != $value && HasAction($variable['VariableID'])) {
                        RequestAction($variable['VariableID'], $value);
                    } elseif (!HasAction($variable['VariableID'])) {
                        throw new Exception('One variable has no action.');
                    }
                }
            } elseif ($instanceStatus != 102) {
                $statuscodes = [];
                $statusForm = json_decode(IPS_GetConfigurationForm($this->InstanceID), true)['status'];
                foreach ($statusForm as $status) {
                    $statuscodes[$status['code']] = $status['caption'];
                }
                $this->LogMessage($this->Translate($statuscodes[$instanceStatus]), KL_ERROR);
            }
        }
    }
