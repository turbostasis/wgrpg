<?php

	include_once "RPGCombatHelper.php";
	include_once "RPGCombatStatusEffect.php";
	include_once "CombatSE/SEBurned.php";
	include_once "CombatSE/SEPoisoned.php";
	include_once "CombatSE/SEBleeding.php";
	include_once "RPGSkill.php";

	class RPGCombat{
	
		private $_intTurn; // turn counter
		private $_arrCombatMessage; // output to combat window
		private $_arrWaitTimes; // alive participant's wait times
		private $_objPlayerTeam; // player team data
		private $_objEnemyTeam; // enemy team data
		private $_strFirstTurn; // who gets to attack first
		private $_objTurnTaker; // specific entity who attacks next
		private $_strTurnTaker; // specific name of entity who attacks next
		private $_strCombatState; // in progress, victory, defeat, fled
		private $_strNextTurn; // team that attacks next
		private $_strPrevTurn; // team that attacked last
		private $_arrCombatStatusEffects; // all status effects in play
	
		public function __construct($objPlayerTeam, $objEnemyTeam, $strFirstTurn){
			$this->_intTurn = 1;
			$this->_objPlayerTeam = $objPlayerTeam;
			$this->_objEnemyTeam = $objEnemyTeam;
			$this->_objEnemyTeam->loadActiveSkillList();
			$this->_objPlayerTeam->loadActiveSkillList();
			$this->_strFirstTurn = $strFirstTurn;
			$this->_arrCombatMessage = array();
			$this->_arrCombatMessage["Action"] = array();
			$this->_arrCombatMessage["Entity"] = array();
			$this->_arrCombatMessage["System"] = array();
			$this->_arrWaitTimes = array();
			$this->_arrCombatStatusEffects = array();
		}
	
		public function initiateCombat(){
			
			$this->_strCombatState = 'In Progress';
			
			// reset skill cooldowns
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->resetAllCooldowns();
			}
			
			// initiate wait times and participant array
			$this->_arrWaitTimes["Player"] = $this->_objPlayerTeam->getPlayer()->getWaitTime("Standard");
			$this->_arrWaitTimes["Leader"] = $this->_objEnemyTeam->getLeader()->getWaitTime("Standard");
			if($this->_objPlayerTeam->getPartyOne() != null){
				$this->_arrWaitTimes["PartyOne"] = $this->_objPlayerTeam->getPartyOne()->getWaitTime("Standard");
			}
			if($this->_objPlayerTeam->getPartyTwo() != null){
				$this->_arrWaitTimes["PartyTwo"] = $this->_objPlayerTeam->getPartyTwo()->getWaitTime("Standard");
			}
			if($this->_objEnemyTeam->getEnemyOne() != null){
				$this->_arrWaitTimes["EnemyOne"] = $this->_objEnemyTeam->getEnemyOne()->getWaitTime("Standard");
			}
			if($this->_objEnemyTeam->getEnemyTwo() != null){
				$this->_arrWaitTimes["EnemyTwo"] = $this->_objEnemyTeam->getEnemyTwo()->getWaitTime("Standard");
			}
			
			if($this->_strFirstTurn == 'Player'){
				$this->_strNextTurn = 'Player';
				$this->_strPrevTurn = 'Player';
			}
			else if($this->_strFirstTurn == "Opponent"){
				$this->_strNextTurn = 'Opponent';
				$this->_strPrevTurn = 'Opponent';
				$this->_objTurnTaker = $this->_objEnemyTeam->getLeader();
				$this->_strTurnTaker = "Leader";
				$this->enemyTurn();
				$this->determineNextTurn();
			}
			else{
				$this->_strTurnTaker = $this->getNextInLine();
				// determine the next turn using AGI formula
				if($this->_strTurnTaker == "Player"){
					$this->_strNextTurn = 'Player';
				}
				else if($this->_strTurnTaker == "PartyOne" || $this->_strTurnTaker == "PartyTwo"){
					$this->_strNextTurn = 'Party';
					$this->_strPrevTurn = 'Party';
					$this->partyTurn();
					$this->determineNextTurn();
				}
				else{
					$this->_strNextTurn = 'Opponent';
					$this->_strPrevTurn = 'Opponent';
					$this->enemyTurn();
					$this->determineNextTurn();
				}
			}
		}
		
		public function determineNextTurn(){
			
			// remove any status effects where duration hits zero, otherwise trigger it
			foreach($this->_arrCombatStatusEffects as $intKey => $objStatusEffect){
				if($objStatusEffect->isTimeUp()){
					unset($this->_arrCombatStatusEffects[$intKey]);
				}
				else{
					$objStatusEffect->trigger();
				}
			}
			
			// check if anyone died
			if($this->_objPlayerTeam->getPlayer()->isDead() && isset($this->_arrWaitTimes["Player"])){
				$this->_intTurn++;
				$this->_arrCombatMessage["Entity"][] = "Player";
				$this->_arrCombatMessage["Action"][] = "You collapse to the ground in injury, defeated. ";
				unset($this->_arrWaitTimes["Player"]);
				$this->_objPlayerTeam->getPlayer()->removeKillBuffs();
			}
			if(isset($this->_arrWaitTimes["PartyOne"]) && $this->_objPlayerTeam->getPartyOne()->isDead()){
				$this->_arrCombatMessage["Entity"][] = "PartyOne";
				$this->_arrCombatMessage["Action"][] = $this->_objPlayerTeam->getPartyOne()->getNPCName() . " collapses to the ground in injury, defeated. ";
				unset($this->_arrWaitTimes["PartyOne"]);
				
			}
			if(isset($this->_arrWaitTimes["PartyTwo"]) && $this->_objPlayerTeam->getPartyTwo()->isDead()){
				$this->_arrCombatMessage["Entity"][] = "PartyTwo";
				$this->_arrCombatMessage["Action"][] = $this->_objPlayerTeam->getPartyTwo()->getNPCName() . " collapses to the ground in injury, defeated. ";
				unset($this->_arrWaitTimes["PartyTwo"]);
			}
			if(isset($this->_arrWaitTimes["Leader"]) && $this->_objEnemyTeam->getLeader()->isDead()){
				$this->_objPlayerTeam->getPlayer()->getQuests()->incrementKillCount($this->_objEnemyTeam->getLeader()->getNPCID());
				$this->_objPlayerTeam->getPlayer()->incrementKillBuffs();
				$this->_arrCombatMessage["Entity"][] = "Leader";
				$this->_arrCombatMessage["Action"][] = $this->_objEnemyTeam->getLeader()->getNPCName() . " collapses to the ground in injury, defeated. ";
				unset($this->_arrWaitTimes["Leader"]);
			}
			if(isset($this->_arrWaitTimes["EnemyOne"]) && $this->_objEnemyTeam->getEnemyOne()->isDead()){
				$this->_objPlayerTeam->getPlayer()->getQuests()->incrementKillCount($this->_objEnemyTeam->getEnemyOne()->getNPCID());
				$this->_objPlayerTeam->getPlayer()->incrementKillBuffs();
				$this->_arrCombatMessage["Entity"][] = "EnemyOne";
				$this->_arrCombatMessage["Action"][] = $this->_objEnemyTeam->getEnemyOne()->getNPCName() . " collapses to the ground in injury, defeated. ";
				unset($this->_arrWaitTimes["EnemyOne"]);
			}
			if(isset($this->_arrWaitTimes["EnemyTwo"]) && $this->_objEnemyTeam->getEnemyTwo()->isDead()){
				$this->_objPlayerTeam->getPlayer()->getQuests()->incrementKillCount($this->_objEnemyTeam->getEnemyTwo()->getNPCID());
				$this->_objPlayerTeam->getPlayer()->incrementKillBuffs();
				$this->_arrCombatMessage["Entity"][] = "EnemyTwo";
				$this->_arrCombatMessage["Action"][] = $this->_objEnemyTeam->getEnemyTwo()->getNPCName() . " collapses to the ground in injury, defeated. ";
				unset($this->_arrWaitTimes["EnemyTwo"]);
			}

			// check if everyone died
			if($this->_objEnemyTeam->allDead()){
				$this->_strCombatState = 'Victory';
				
				if($this->_objPlayerTeam->getPartyOne() != null && $this->_objPlayerTeam->getPartyOne()->isDead()){
					$this->_objPlayerTeam->setPartyOne(null);
				}
				if($this->_objPlayerTeam->getPartyTwo() != null && $this->_objPlayerTeam->getPartyTwo()->isDead()){
					$this->_objPlayerTeam->setPartyTwo(null);
				}
				
				// victory message
				$this->_arrCombatMessage["System"][] = "You have emerged victorious!";
				
				// exp gain
				$intExpGain = $this->_objEnemyTeam->getExperienceGiven();
				$this->_objPlayerTeam->gainExperience($intExpGain);
				$this->_arrCombatMessage["System"][] = 'You gained <b>' . $intExpGain . ' experience</b> from the battle.';
				
				// receive money
				if($this->_objEnemyTeam->getGoldDropMax() != 0){
					$intMoneyGain = mt_rand($this->_objEnemyTeam->getGoldDropMin(), $this->_objEnemyTeam->getGoldDropMax());
					$this->_objPlayerTeam->getPlayer()->receiveGold($intMoneyGain);
					$this->_arrCombatMessage["System"][] = 'You picked up <b>' . $intMoneyGain . ' gold</b> from the enemy\'s remains.';
				}
				
				// roll for loot
				$arrDrops = $this->_objEnemyTeam->getRandomDrops();
				
				foreach($arrDrops as $key => $value){
					$this->_objPlayerTeam->getPlayer()->giveItem($key);
					$this->_arrCombatMessage["System"][] = 'Loot received: ' . $value . '';
				}
				
			}
			else if($this->_objPlayerTeam->getPlayer()->isDead()){
				$this->_strCombatState = 'Defeat';
				$this->_arrCombatMessage["System"][] = 'You have lost the battle.';
			}
			else{	
				$this->_intTurn++;
				$this->_strTurnTaker = $this->getNextInLine();
				// determine the next turn using AGI formula
				
				if($this->_strTurnTaker == "Player"){
					$strTeamName = "Player";
					//status effect check for player
					if($this->_objPlayerTeam->getPlayer()->hasStatusEffect('Hypnotized')){
						//todo: random skills or action controlled by NPC?
						$this->playerFriendlyFire();
						$this->_objPlayerTeam->getPlayer()->tickStatusEffects();
						$this->determineNextTurn();
					}
					else if($this->_objPlayerTeam->getPlayer()->hasStatusEffect('Stuck')){
						$this->playerStuck();
						$this->_objPlayerTeam->getPlayer()->tickStatusEffects();
						$this->determineNextTurn();
					}
					else if($this->_objPlayerTeam->getPlayer()->hasStatusEffect('Knocked Down')){
						$this->playerKnockedDown();
						$this->_objPlayerTeam->getPlayer()->tickStatusEffects();
						$this->determineNextTurn();
					}
					else{
						$this->_strNextTurn = 'Player';
					}
				}
				else if($this->_strTurnTaker == "PartyOne" || $this->_strTurnTaker == "PartyTwo"){
					$this->_strNextTurn = 'Party';
					$strTeamName = "Player";
				}
				else{
					$this->_strNextTurn = 'Opponent';
					$strTeamName = "Enemy";
				}
				
			}
			
		}
		
		public function playerAttack($strTarget){
			$this->_arrCombatMessage["Entity"][] = "Player";
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->decrementCooldowns();
			}
			if($strTarget == "AllEnemy" || $strTarget == "AllPlayer"){
				$this->_arrCombatMessage["Action"][] = "Invalid target selected.";
			}
			else{
				$this->_strPrevTurn = "Player";
				$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime('Standard');
				
				$objTarget = $this->determineTarget($strTarget);
				
				$objRPGCombatHelper = new RPGCombatHelper();
				
				if($objTarget->hasStatusEffect("Parry Stance")){
					include_once "Skills/SkillPunish.php";
					$strAction = 'SkillPunish';
					$objSkill = new $strAction();
					$this->_arrCombatMessage["Action"][] = $objSkill->playerParry($objTarget, $this->_objTurnTaker);
				}
				else if($objTarget->hasStatusEffect("Decoy")){
					include_once "Skills/SkillDecoy.php";
					$strAction = 'SkillDecoy';
					$objSkill = new $strAction();
					$this->_arrCombatMessage["Action"][] = $objSkill->playerParry($objTarget, $this->_objTurnTaker);
				}
				else{
					$intDamage = $objTarget->takeDamage($objRPGCombatHelper->calculateDamage($this->_objPlayerTeam->getPlayer(), $objTarget));
					
					if(!$objRPGCombatHelper->getEvaded()){
						if($objRPGCombatHelper->getBlocked() && $objRPGCombatHelper->getCrit()){
							$this->_arrCombatMessage["Action"][] = "You land a critical blow on " . $objTarget->getNPCName() . " but they block the attack, sustaining " . $intDamage . " damage.";
						}
						
						
						else if($objRPGCombatHelper->getBlocked() && !$objRPGCombatHelper->getCrit()){
							$this->_arrCombatMessage["Action"][] = "You attack " . $objTarget->getNPCName() . " but they successfully block the attack, sustaining " . $intDamage . " damage.";
						}
						else if(!$objRPGCombatHelper->getBlocked() && $objRPGCombatHelper->getCrit()){
							$this->_arrCombatMessage["Action"][] = "You land a critical blow on " . $objTarget->getNPCName() . ", dealing " . $intDamage . " damage.";
						}
						else{
							$this->_arrCombatMessage["Action"][] = "You attack " . $objTarget->getNPCName() . " for " . $intDamage . " damage.";
						}
					}
					else{
						$this->_arrCombatMessage["Action"][] = "You attack " . $objTarget->getNPCName() . " but your attack is evaded.";
					}
				}
				
				if($intDamage && $this->_objPlayerTeam->getPlayer()->hasStatusEffect("Fat Counter")){
					$this->_arrCombatMessage["Action"][] = "Pulsating vines squeeze tight against your body, causing you to gasp for air. The pressure is further intensified as you feel your body expanding outward, growing fatter by the second!";
					include_once "Skills/SkillSapWeight.php";
					$strAction = 'SkillSapWeight';
					$objSkill = new $strAction();
					$objSkill->addWeight($this->_objPlayerTeam->getPlayer(), $intDamage);
				}
			}
		}
		
		public function playerSkill($strTarget, $intSkillID){
			$this->_arrCombatMessage["Entity"][] = "Player";
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->decrementCooldowns();
			}
			$objTempSkill = new RPGSkill($intSkillID);
			include_once "Skills/Skill" . $objTempSkill->getClassName() . ".php";
			$strAction = 'Skill' . $objTempSkill->getClassName();
			
			$objTarget = $this->determineTarget($strTarget);
			
			$objSkill = new $strAction();
			$intWaitTime = $objSkill->getWaitTime();
			$this->_strPrevTurn = "Player";
			$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime($intWaitTime);
			$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->applyCooldown($intSkillID);
			
			if($udfSkillReturn = $objTarget->hasStatusEffect("Parry Stance")){
				
				$strReturn = "";
				foreach($udfSkillReturn as $key => $strPartyFunc){
						
					$objTarget = $this->_objPlayerTeam->$strPartyFunc();
					include_once "Skills/SkillPunish.php";
					$strAction = 'SkillPunish';
					$objSkill = new $strAction();
					$strReturn .= $objSkill->playerParrySkill($objTarget, $this->_objTurnTaker, $objSkill);
				}
				$this->_arrCombatMessage["Action"][] = $strReturn;
				
			}
			else if($udfSkillReturn = $objTarget->hasStatusEffect("Decoy")){
				
				$strReturn = "";
				foreach($udfSkillReturn as $key => $strPartyFunc){
						
					$objTarget = $this->_objPlayerTeam->$strPartyFunc();
					include_once "Skills/SkillDecoy.php";
					$strAction = 'SkillDecoy';
					$objSkill = new $strAction();
					$strReturn .= $objSkill->playerParry($objTarget, $this->_objTurnTaker);
				}
				$this->_arrCombatMessage["Action"][] = $strReturn;
				
			}
			else{
				$this->_arrCombatMessage["Action"][] = $objSkill->castedByPlayer($this->_objPlayerTeam->getPlayer(), $objTarget);
			}
			
			$objRPGCombatHelper = new RPGCombatHelper();
			$intDamage = $objRPGCombatHelper->calculateDamage($this->_objPlayerTeam->getPlayer(), $this->_objEnemyTeam->getLeader());
			
			if($intDamage && $this->_objPlayerTeam->getPlayer()->hasStatusEffect("Fat Counter")){
				$this->_arrCombatMessage["Action"][] = "Pulsating vines squeeze tight against your body, causing you to gasp for air. The pressure is further intensified as you feel your body expanding outward, growing fatter by the second!";
				include_once "Skills/SkillSapWeight.php";
				$strAction = 'SkillSapWeight';
				$objSkill = new $strAction();
				$objSkill->addWeight($this->_objPlayerTeam->getPlayer(), $intDamage);
			}
		}
		
		public function playerWait(){
			$this->_arrCombatMessage["Entity"][] = "Player";
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->decrementCooldowns();
			}
			$this->_strPrevTurn = "Player";
			$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime('Standard');
			$this->_arrCombatMessage["Action"][] = "You stand still, doing absolutely nothing...";
		}
		
		public function playerFlee(){
			$this->_arrCombatMessage["Entity"][] = "Player";
			$intPlayerFleeRoll = mt_rand(1, $this->_objPlayerTeam->getPlayer()->getModifiedFleeRate());
			
			if($intPlayerFleeRoll >= $this->_objEnemyTeam->getLeader()->getModifiedFleeResistance()){
				$this->_arrCombatMessage["Action"][] = 'You fled from the battle.';
				$this->_strCombatState = 'Fled';	
			}
			else{
				$this->_strPrevTurn = "Player";
				$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime('Standard');
				$this->_arrCombatMessage["Action"][] = "You attempt to flee but fail, opening yourself up to an attack.";
			}
		}
		
		public function playerFriendlyFire(){
			$this->_arrCombatMessage["Entity"][] = "Player";
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->decrementCooldowns();
			}
			$this->_strPrevTurn = "Player";
			$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime('Standard');
			
			if($this->_objPlayerTeam->getPlayer()->getEquippedWeapon()->getStatDamage() === null || $this->_objPlayerTeam->getPlayer()->getEquippedWeapon()->getStatDamage() == 'Strength'){
				$intDamage = $this->_objPlayerTeam->getPlayer()->takeDamage(round($this->_objPlayerTeam->getPlayer()->getModifiedDamage()));
			}
			else if($this->_objTurnTaker->getEquippedWeapon()->getStatDamage() == 'Intelligence'){
				$intDamage = $this->_objPlayerTeam->getPlayer()->takeDamage(round($this->_objPlayerTeam->getPlayer()->getModifiedMagicDamage()));	
			}
			$this->_arrCombatMessage["Action"][] = "You attack yourself for " . $intDamage . " damage under hypnosis. ";
		}
		
		public function playerStuck(){
			$this->_arrCombatMessage["Entity"][] = "Player";
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->decrementCooldowns();
			}
			$this->_strPrevTurn = "Player";
			$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime('Standard');
			
			$this->_arrCombatMessage["Action"][] = "You are glued to the ground by a sticky substance and cannot attack. ";
		}
		
		public function playerKnockedDown(){
			$this->_arrCombatMessage["Entity"][] = "Player";
			if($this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()){
				$this->_objPlayerTeam->getPlayer()->getClasses()->getCurrentClass()->getSkills()->decrementCooldowns();
			}
			$this->_strPrevTurn = "Player";
			$this->_arrWaitTimes["Player"] += $this->_objPlayerTeam->getPlayer()->getWaitTime('Standard');
			
			$this->_arrCombatMessage["Action"][] = "You are incapacitated and cannot attack. ";
		}
		
		public function enemyTurn(){
			$this->_arrCombatMessage["Entity"][] = $this->_strTurnTaker;
			if($this->_objTurnTaker->hasStatusEffect("Frozen")){
				$this->_strPrevTurn = "Opponent";
				$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
				$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " is frozen and cannot attack!";
				$this->_objTurnTaker->tickStatusEffect("Frozen");
			}
			else if($this->_objTurnTaker->hasStatusEffect("Knocked Down")){
				$this->_strPrevTurn = "Opponent";
				$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
				$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " is incapacitated and cannot attack!";
				$this->_objTurnTaker->tickStatusEffect("Knocked Down");
			}
			else{
				// load enemy AI
				$strAIName = $this->_objTurnTaker->getAIName();
				include_once "AI/" . $strAIName . ".php";
				$objAI = new $strAIName($this->_objTurnTaker, $this->_objEnemyTeam, $this->_objPlayerTeam);
				$strAction = $objAI->determineActionEnemy();
				$objTarget = $objAI->getTarget();
				
				if($strAction == 'Attack'){
					$this->enemyAttack($objTarget);
				}
				else if(strpos($strAction, 'Skill') !== false){
					include_once "Skills/" . $strAction . ".php";
					$objSkill = new $strAction();
					$intWaitTime = $objSkill->getWaitTime();
					$this->_strPrevTurn = "Opponent";
					$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime($intWaitTime);
					if($udfSkillReturn = $objTarget->hasStatusEffect("Parry Stance")){
						
						$strReturn = "";
						foreach($udfSkillReturn as $key => $strPartyFunc){
							$objTarget = $this->_objPlayerTeam->$strPartyFunc();
							include_once "Skills/SkillPunish.php";
							$strAction = 'SkillPunish';
							$objParrySkill = new $strAction();
							
							$strReturn .= $objParrySkill->playerParrySkill($objTarget, $this->_objTurnTaker, $objSkill);
						}
						$this->_arrCombatMessage["Action"][] = $strReturn;

					}
					else if($udfSkillReturn = $objTarget->hasStatusEffect("Decoy")){
						
						$strReturn = "";
						foreach($udfSkillReturn as $key => $strPartyFunc){
							$objTarget = $this->_objPlayerTeam->$strPartyFunc();
							include_once "Skills/SkillDecoy.php";
							$strAction = 'SkillDecoy';
							$objSkill = new $strAction();
							$strReturn .= $objSkill->playerParry($objTarget, $this->_objTurnTaker);
						}
						$this->_arrCombatMessage["Action"][] = $strReturn;
					}
					else{
						$this->_arrCombatMessage["Action"][] = $objSkill->castedByNPC($objTarget, $this->_objTurnTaker);
					}
				}
				else{
					$this->enemyWait();
				}
			}
		}
		
		public function enemyAttack($objTarget){
			$this->_strPrevTurn = "Opponent";
			$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
			
			$objRPGCombatHelper = new RPGCombatHelper();
			
			if($objTarget->hasStatusEffect("Parry Stance")){
				include_once "Skills/SkillPunish.php";
				$strAction = 'SkillPunish';
				$objSkill = new $strAction();
				$this->_arrCombatMessage["Action"][] = $objSkill->playerParry($objTarget, $this->_objTurnTaker);
			}
			else if($objTarget->hasStatusEffect("Decoy")){
				include_once "Skills/SkillDecoy.php";
				$strAction = 'SkillDecoy';
				$objSkill = new $strAction();
				$this->_arrCombatMessage["Action"][] = $objSkill->playerParry($objTarget, $this->_objTurnTaker);
			}
			else{
				$intDamage = $objTarget->takeDamage($objRPGCombatHelper->calculateDamage($this->_objTurnTaker, $objTarget));
			
				if(!$objRPGCombatHelper->getEvaded()){
					if($objRPGCombatHelper->getBlocked() && $objRPGCombatHelper->getCrit()){
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " lands a critical blow on " . $objTarget->getNPCName() . " but " . $objTarget->getNPCName() . " blocks it, sustaining " . $intDamage . " damage.";
					}
					else if($objRPGCombatHelper->getBlocked() && !$objRPGCombatHelper->getCrit()){
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " attacks " . $objTarget->getNPCName() . " and " . $objTarget->getNPCName() . " blocks the attack, sustaining " . $intDamage . " damage.";
					}
					else if(!$objRPGCombatHelper->getBlocked() && $objRPGCombatHelper->getCrit()){
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " lands a critical blow on " . $objTarget->getNPCName() . ", dealing " . $intDamage . " damage.";
					}
					else{
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " attacks " . $objTarget->getNPCName() . " for " . $intDamage . " damage.";
					}
				}
				else{
					$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " attacks " . $objTarget->getNPCName() . " but " . $objTarget->getNPCName() . " successfully evades the attack.";
				}
			}
		}
		
		public function enemyWait(){
			$this->_strPrevTurn = "Opponent";
			$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
			$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " stands still, doing absolutely nothing...";
		}
		
		public function partyTurn(){
			$this->_arrCombatMessage["Entity"][] = $this->_strTurnTaker;
			// load party member AI
			$strAIName = $this->_objTurnTaker->getAIName();
			include_once "AI/" . $strAIName . ".php";
			$objAI = new $strAIName($this->_objTurnTaker, $this->_objEnemyTeam, $this->_objPlayerTeam);
			$strAction = $objAI->determineActionParty();
			$objTarget = $objAI->getTarget();
			
			if($strAction == 'Attack'){
				$this->partyAttack($objTarget);
			}
			else if(strpos($strAction, 'Skill') !== false){
				include_once "Skills/" . $strAction . ".php";
				$objSkill = new $strAction();
				$intWaitTime = $objSkill->getWaitTime();
				$this->_strPrevTurn = "Party";
				$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime($intWaitTime);
				if($udfSkillReturn = $objTarget->hasStatusEffect("Parry Stance")){
					
					$strReturn = "";
					foreach($udfSkillReturn as $key => $strPartyFunc){
						
						$objTarget = $this->_objPlayerTeam->$strPartyFunc();
						include_once "Skills/SkillPunish.php";
						$strAction = 'SkillPunish';
						$objParrySkill = new $strAction();
						
						$strReturn .= $objParrySkill->playerParrySkill($objTarget, $this->_objTurnTaker, $objSkill);
					}
					$this->_arrCombatMessage["Action"][] = $strReturn;
				}
				else if($udfSkillReturn = $objTarget->hasStatusEffect("Decoy")){
					
					$strReturn = "";
					foreach($udfSkillReturn as $key => $strPartyFunc){
						
						$objTarget = $this->_objPlayerTeam->$strPartyFunc();
						include_once "Skills/SkillDecoy.php";
						$strAction = 'SkillDecoy';
						$objSkill = new $strAction();
						$strReturn .= $objSkill->playerParry($objTarget, $this->_objTurnTaker);
					}
					$this->_arrCombatMessage["Action"][] = $strReturn;
					
				}
				else{
					$this->_arrCombatMessage["Action"][] = $objSkill->castedByNPC($objTarget, $this->_objTurnTaker);
				}
				
				$objRPGCombatHelper = new RPGCombatHelper();
				$intDamage = $objRPGCombatHelper->calculateDamage($this->_objTurnTaker, $this->_objEnemyTeam->getLeader());
				
				if($intDamage && $this->_objTurnTaker->hasStatusEffect("Fat Counter")){
					$this->_arrCombatMessage["Action"][] = "Pulsating vines squeeze tight against " . $this->_objTurnTaker->getNPCName() . "'s body, causing them to gasp for air. The pressure appears to be further intensified, as their body expands outward, growing fatter by the second!";
					include_once "Skills/SkillSapWeight.php";
					$strAction = 'SkillSapWeight';
					$objSkill = new $strAction();
					$objSkill->addWeight($this->_objTurnTaker, $intDamage);
				}
			}
			else{
				$this->partyWait();
			}
		}
		
		public function partyAttack($objTarget){
			$this->_strPrevTurn = "Party";
			$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
			
			$objRPGCombatHelper = new RPGCombatHelper();

			if($objTarget->hasStatusEffect("Parry Stance")){
				include_once "Skills/SkillPunish.php";
				$strAction = 'SkillPunish';
				$objSkill = new $strAction();
				$this->_arrCombatMessage["Action"][] = $objSkill->playerParry($objTarget, $this->_objTurnTaker);
			}
			else if($objTarget->hasStatusEffect("Decoy")){
				include_once "Skills/SkillDecoy.php";
				$strAction = 'SkillDecoy';
				$objSkill = new $strAction();
				$this->_arrCombatMessage["Action"][] = $objSkill->playerParry($objTarget, $this->_objTurnTaker);
			}
			else{
				$intDamage = $objTarget->takeDamage($objRPGCombatHelper->calculateDamage($this->_objTurnTaker, $objTarget));
				
				if(!$objRPGCombatHelper->getEvaded()){
					if($objRPGCombatHelper->getBlocked() && $objRPGCombatHelper->getCrit()){
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " lands a critical blow on " . $objTarget->getNPCName() . " but " . $objTarget->getNPCName() . " blocks it, sustaining " . $intDamage . " damage.";
					}
					else if($objRPGCombatHelper->getBlocked() && !$objRPGCombatHelper->getCrit()){
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " attacks " . $objTarget->getNPCName() . " and " . $objTarget->getNPCName() . " blocks the attack, sustaining " . $intDamage . " damage.";
					}
					else if(!$objRPGCombatHelper->getBlocked() && $objRPGCombatHelper->getCrit()){
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " lands a critical blow on " . $objTarget->getNPCName() . ", dealing " . $intDamage . " damage.";
					}
					else{
						$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " attacks " . $objTarget->getNPCName() . " for " . $intDamage . " damage.";
					}
				}
				else{
					$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " attacks " . $objTarget->getNPCName() . " but " . $objTarget->getNPCName() . " successfully evades the attack.";
				}
			}
			
			if($intDamage && $this->_objTurnTaker->hasStatusEffect("Fat Counter")){
				$this->_arrCombatMessage["Action"][] = "Pulsating vines squeeze tight against " . $this->_objTurnTaker->getNPCName() . "'s body, causing them to gasp for air. The pressure appears to be further intensified, as their body expands outward, growing fatter by the second!";
				include_once "Skills/SkillSapWeight.php";
				$strAction = 'SkillSapWeight';
				$objSkill = new $strAction();
				$objSkill->addWeight($this->_objTurnTaker, $intDamage);
			}
		}
		
		public function partyFriendlyFire(){
			$this->_strPrevTurn = "Party";
			$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
			
			if($this->_objTurnTaker->getEquippedWeapon()->getStatDamage() === null || $this->_objTurnTaker->getEquippedWeapon()->getStatDamage() == 'Strength'){
				$intDamage = $this->_objTurnTaker->takeDamage(round($this->_objTurnTaker->getModifiedDamage()));
			}
			else if($this->_objTurnTaker->getEquippedWeapon()->getStatDamage() == 'Intelligence'){
				$intDamage = $this->_objTurnTaker->takeDamage(round($this->_objTurnTaker->getModifiedMagicDamage()));	
			}
			$this->_arrCombatMessage["Action"][] = " " . $this->_objTurnTaker->getNPCName() . " attacks themselves for " . $intDamage . " damage under hypnosis.";
		}
		
		public function partyWait(){
			$this->_strPrevTurn = "Party";
			$this->_arrWaitTimes[$this->_strTurnTaker] += $this->_objTurnTaker->getWaitTime('Standard');
			$this->_arrCombatMessage["Action"][] = $this->_objTurnTaker->getNPCName() . " stands still, doing absolutely nothing...";
		}
		
		public function getNextInLine(){
			$strTurnTaker = min(array_keys($this->_arrWaitTimes, min($this->_arrWaitTimes)));
			if($strTurnTaker == "Player"){
				$this->_objTurnTaker = $this->_objPlayerTeam->getPlayer();
			}
			else if($strTurnTaker == "PartyOne"){
				$this->_objTurnTaker = $this->_objPlayerTeam->getPartyOne();
			}
			else if($strTurnTaker == "PartyTwo"){
				$this->_objTurnTaker = $this->_objPlayerTeam->getPartyTwo();
			}
			else if($strTurnTaker == "Leader"){
				$this->_objTurnTaker = $this->_objEnemyTeam->getLeader();
			}
			else if($strTurnTaker == "EnemyOne"){
				$this->_objTurnTaker = $this->_objEnemyTeam->getEnemyOne();
			}
			else if($strTurnTaker == "EnemyTwo"){
				$this->_objTurnTaker = $this->_objEnemyTeam->getEnemyTwo();
			}
			return $strTurnTaker;
		}
		
		public function determineTarget($strTarget){
			if($strTarget == "Player"){
				$objTarget = $this->_objPlayerTeam->getPlayer();
			}
			else if($strTarget == "PartyOne"){
				$objTarget = $this->_objPlayerTeam->getPartyOne();
			}
			else if($strTarget == "PartyTwo"){
				$objTarget = $this->_objPlayerTeam->getPartyTwo();
			}
			else if($strTarget == "AllPlayer"){
				$objTarget = $this->_objPlayerTeam;
			}
			else if($strTarget == "Leader"){
				$objTarget = $this->_objEnemyTeam->getLeader();
			}
			else if($strTarget == "EnemyOne"){
				$objTarget = $this->_objEnemyTeam->getEnemyOne();
			}
			else if($strTarget == "EnemyTwo"){
				$objTarget = $this->_objEnemyTeam->getEnemyTwo();
			}
			else if($strTarget == "AllEnemy"){
				$objTarget = $this->_objEnemyTeam;
			}
			return $objTarget;
		}
		
		public function getPlayerTeam(){
			return $this->_objPlayerTeam;
		}
		
		public function getEnemyTeam(){
			return $this->_objEnemyTeam;
		}
		
		public function getCombatMessage($strIndex){
			return $this->_arrCombatMessage[$strIndex];
		}
		
		public function getCombatState(){
			return $this->_strCombatState;
		}
		
		public function getFirstTurn(){
			return $this->_strFirstTurn;
		}
		
		public function getNextTurn(){
			return $this->_strNextTurn;
		}
		
		public function getTurnCount(){
			return $this->_intTurn;
		}
		
		public function getWaitTimes(){
			return $this->_arrWaitTimes;
		}
		
		public function writeCombatMessage($strEntityName, $strMessage){
			$this->_arrCombatMessage["Entity"][] = $strEntityName;
			$this->_arrCombatMessage["Action"][] = $strMessage;
		}
		
		public function writeSystemMessage($strMessage){
			$this->_arrCombatMessage["System"][] = $strMessage;
		}
		
		public function getCombatStatusEffects(){
			return $this->_arrCombatStatusEffects;
		}
		
		public function addToCombatStatusEffects($objStatusEffect){
			$this->_arrCombatStatusEffects[] = $objStatusEffect;
		}
	}

?>