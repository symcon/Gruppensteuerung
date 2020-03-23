<?php

	class Gruppensteuerung extends IPSModule {

		public function Create()
		{
			//Never delete this line!
			parent::Create();

			//Properties
			$this->RegisterPropertyString('Variables', '[]');
			$this->RegisterAttributeString('UpdateValue', '');
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
			
			$this->SendDebug('VariableList', print_r($variables, true), 0);
			
			$statusVariableReferenceID = $variables[0]['VariableID'];
			$variableType = IPS_GetVariable($statusVariableReferenceID)['VariableType'];
			$variableProfile = IPS_GetVariable($statusVariableReferenceID)['VariableCustomProfile'];
			$this->SendDebug('VarProfile', print_r($variableProfile, true), 0);
			$this->UnregisterVariable('Status');
			switch($variableType) {
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
			//Register message
			foreach ($variables as $variable) {
				$this->RegisterMessage($variable['VariableID'], VM_UPDATE);
			}
			$this->SendDebug('MessageList', json_encode($this->GetMessageList()), 0);
			
        }
		
		public function MessageSink ($Timestamp, $SenderID, $MessageID, $Data) 
		{
			$this->SendDebug('MessageSink', IPS_GetName($SenderID), 0);
			SetValue($this->GetIDForIdent('Status'), $Data[0]);
			$this->SwitchGroup($Data[0], $SenderID);
		}

		public function RequestAction($Ident, $Value)
		{
			switch($Ident)
			{
				case 'Status':
					$this->SendDebug('Status', 'triggered', 0);
					$this->SetValue($Ident, $Value);
					$this->SwitchGroup($Value, $this->GetIDForIdent('Status'));
					break;
				default:
					throw new Exception('InvalidIdent');
			}
		}

		private function SwitchGroup($value, $sender)
		{
			$variables = json_decode($this->ReadPropertyString('Variables'), true);
			$this->SendDebug('SingleVariable', json_encode($variables), 0);


			foreach ($variables as $variable)
			{
				if ($variable['VariableID'] != $sender && GetValue($variable['VariableID']) != $value)
				{
					RequestAction($variable['VariableID'], $value);
				}
			}
		}

	}