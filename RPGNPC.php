<?php

require_once "Database.php";
require_once "RPGNPCStats.php";
require_once "RPGSkill.php";
require_once "RPGItem.php";
require_once "RPGNPCGrowth.php";
include_once "constants.php";
include_once "common.php";

class RPGNPC{

	private $_intNPCInstanceID;
	private $_intRPGCharacterID;
	private $_strNPCName;
	private $_strShortName;
	private $_intCurrentHP;
	private $_objStats;
	private $_objGrowth;
	private $_intWeight;
	private $_intHeight;
	private $_intExperienceGiven;
	private $_intGoldDropMin;
	private $_intGoldDropMax;
	private $_strStartText;
	private $_strForceStartText;
	private $_strEndText;
	private $_strFleeText;
	private $_strFailFleeText;
	private $_strDefeatText;
	private $_blnHasStartEvent;
	private $_blnHasEndEvent;
	private $_strAIName;
	private $_intWeightGain;
	private $_arrSkillList;
	private $_arrStatusEffectList;
	private $_arrActiveSkillList;
	private $_objEquippedWeapon;
	private $_objEquippedArmour;
	private $_objEquippedTop;
	private $_objEquippedBottom;
	private $_objEquippedSecondary;
	private $_blnLastRoll;
	private $_dtmCreatedOn;
	private $_strCreatedBy;
	private $_dtmModifiedOn;
	private $_strModifiedBy;
	private $_intLevel;
	private $_intExperience;
	private $_intRelationshipLevel;
	private $_intRelationshipEXP;
	private $_intConversationLevel;
	private $_intCurrentHunger;
	private $_intHungerRate;
	private $_intDigestionRate;
	private $_intRequiredExperience;
	private $_intPartyMemberID;
	
	public function __construct($intNPCID = null, $intRPGCharacterID = null){
		if($intNPCID != null){
			$this->loadNPCInfo($intNPCID);
		}
		if($intRPGCharacterID != null){
			$this->_intRPGCharacterID = $intRPGCharacterID;
			$this->loadNPCInstanceInfo($intRPGCharacterID);
		}
	}
	
	private function populateVarFromRow($arrNPCInfo){
		$this->setNPCID($arrNPCInfo['intNPCID']);
		$this->setNPCName($arrNPCInfo['strNPCName']);
		$this->setShortName($arrNPCInfo['strShortName']);
		$this->setWeight($arrNPCInfo['intWeight']);
		$this->setHeight($arrNPCInfo['intHeight']);
		$this->setExperienceGiven($arrNPCInfo['intExperienceGiven']);
		$this->setGoldDropMin($arrNPCInfo['intGoldDropMin']);
		$this->setGoldDropMax($arrNPCInfo['intGoldDropMax']);
		$this->setStartText($arrNPCInfo['strStartText']);
		$this->setForceStartText($arrNPCInfo['strForceStartText']);
		$this->setEndText($arrNPCInfo['strEndText']);
		$this->setFleeText($arrNPCInfo['strFleeText']);
		$this->setFailFleeText($arrNPCInfo['strFailFleeText']);
		$this->setDefeatText($arrNPCInfo['strDefeatText']);
		$this->setHasStartEvent($arrNPCInfo['blnHasStartEvent']);
		$this->setHasEndEvent($arrNPCInfo['blnHasEndEvent']);
		$this->setAIName($arrNPCInfo['strAIName']);
		$this->setWeightGain($arrNPCInfo['intWeightGain']);
		$this->setCreatedOn($arrNPCInfo['dtmCreatedOn']);
		$this->setCreatedBy($arrNPCInfo['strCreatedBy']);
		$this->setModifiedOn($arrNPCInfo['dtmModifiedOn']);
		$this->setModifiedBy($arrNPCInfo['strModifiedBy']);
	}
	
	private function populateInstanceVarFromRow($arrNPCInfo){
		$this->setNPCInstanceID($arrNPCInfo['intNPCInstanceID']);
		$this->setLevel($arrNPCInfo['intLevel']);
		$this->setExperience($arrNPCInfo['intExperience']);
		$this->setRelationshipLevel($arrNPCInfo['intRelationshipLevel']);
		$this->setRelationshipEXP($arrNPCInfo['intRelationshipEXP']);
		$this->setConversationLevel($arrNPCInfo['intConversationLevel']);
		$this->setWeight($arrNPCInfo['dblWeight']);
		$this->setCurrentHunger($arrNPCInfo['intCurrentHunger']);
		$this->setHungerRate($arrNPCInfo['intHungerRate']);
		$this->setCurrentHP($arrNPCInfo['intCurrentHP']);
		$this->setDigestionRate($arrNPCInfo['intDigestionRate']);
	}
	
	private function loadNPCInfo($intNPCID){
		$objDB = new Database();
		$arrNPCInfo = array();
			$strSQL = "SELECT *
						FROM tblnpc
							LEFT JOIN tblnpcbattletext
								USING (intNPCID)
							WHERE intNPCID = " . $objDB->quote($intNPCID);
			$rsResult = $objDB->query($strSQL);
			while ($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
				$arrNPCInfo['intNPCID'] = $arrRow['intNPCID'];
				$arrNPCInfo['strNPCName'] = $arrRow['strNPCName'];
				$arrNPCInfo['strShortName'] = $arrRow['strShortName'];
				$arrNPCInfo['intWeight'] = $arrRow['intWeight'];
				$arrNPCInfo['intHeight'] = $arrRow['intHeight'];
				$arrNPCInfo['intExperienceGiven'] = $arrRow['intExperienceGiven'];
				$arrNPCInfo['intGoldDropMin'] = $arrRow['intGoldDropMin'];
				$arrNPCInfo['intGoldDropMax'] = $arrRow['intGoldDropMax'];
				$arrNPCInfo['strStartText'] = $arrRow['strStartText'];
				$arrNPCInfo['strForceStartText'] = $arrRow['strForceStartText'];
				$arrNPCInfo['strEndText'] = $arrRow['strEndText'];
				$arrNPCInfo['strFleeText'] = $arrRow['strFleeText'];
				$arrNPCInfo['strFailFleeText'] = $arrRow['strFailFleeText'];
				$arrNPCInfo['strDefeatText'] = $arrRow['strDefeatText'];
				$arrNPCInfo['blnHasStartEvent'] = $arrRow['blnHasStartEvent'];
				$arrNPCInfo['blnHasEndEvent'] = $arrRow['blnHasEndEvent'];
				$arrNPCInfo['strAIName'] = $arrRow['strAIName'];
				$arrNPCInfo['intWeightGain'] = $arrRow['intWeightGain'];
				$arrNPCInfo['dtmCreatedOn'] = $arrRow['dtmCreatedOn'];
				$arrNPCInfo['strCreatedBy'] = $arrRow['strCreatedBy'];
				$arrNPCInfo['dtmModifiedOn'] = $arrRow['dtmModifiedOn'];
				$arrNPCInfo['strModifiedBy'] = $arrRow['strModifiedBy'];
			}
		$this->populateVarFromRow($arrNPCInfo);
		$this->loadSkills();	
		$this->_objEquippedArmour = $this->loadEquippedArmour();
		$this->_objEquippedTop = $this->loadEquippedTop();
		$this->_objEquippedBottom = $this->loadEquippedBottom();
		$this->_objEquippedWeapon = $this->loadEquippedWeapon();
		$this->_objEquippedSecondary = $this->loadEquippedSecondary();
		$this->_objStats = new RPGNPCStats($intNPCID);
		$this->_objStats->loadBaseStats();
		$this->setCurrentHP($this->getModifiedMaxHP());
		$this->setCurrentHunger(0);
		$this->_arrStatusEffectList = array();
	}
	
	private function loadNPCInstanceInfo($intRPGCharacterID){
		$objDB = new Database();
		$arrNPCInfo = array();
		$strSQL = "SELECT *
					FROM tblnpcinstance
						WHERE intNPCID = " . $objDB->quote($this->_intNPCID) . "
							AND intRPGCharacterID = " . $objDB->quote($intRPGCharacterID);
		$rsResult = $objDB->query($strSQL);
		if(!$rsResult){
			$this->setNPCInstanceID(0);
		}
		else{	
			while ($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
				$arrNPCInfo['intLevel'] = $arrRow['intLevel'];
				$arrNPCInfo['intExperience'] = $arrRow['intExperience'];
				$arrNPCInfo['intRelationshipLevel'] = $arrRow['intRelationshipLevel'];
				$arrNPCInfo['intRelationshipEXP'] = $arrRow['intRelationshipEXP'];
				$arrNPCInfo['intConversationLevel'] = $arrRow['intConversationLevel'];
				$arrNPCInfo['dblWeight'] = $arrRow['dblWeight'];
				$arrNPCInfo['intCurrentHunger'] = $arrRow['intCurrentHunger'];
				$arrNPCInfo['intHungerRate'] = $arrRow['intHungerRate'];
				$arrNPCInfo['intCurrentHP'] = $arrRow['intCurrentHP'];
				$arrNPCInfo['intDigestionRate'] = $arrRow['intDigestionRate'];
				$arrNPCInfo['intNPCInstanceID'] = $arrRow['intNPCInstanceID'];
			}
			$this->populateInstanceVarFromRow($arrNPCInfo);	
			$this->_objStats->setRPGCharacterID($intRPGCharacterID);
			$this->_objStats->loadBaseInstanceStats();
			$this->_objGrowth = new RPGNPCGrowth($this->_intNPCID);
			$this->_intRequiredExperience = $this->loadRequiredExperience();
		}
	}
	
	public function save(){
		$objDB = new Database();
		$strSQL = "UPDATE tblnpcinstance
					SET dblWeight = " . $objDB->quote($this->_intWeight) . ",
						intExperience = " . $objDB->quote($this->_intExperience) . ",
						intLevel = " . $objDB->quote($this->_intLevel) . ",
						intConversationLevel = " . $objDB->quote($this->_intConversationLevel) . ",
						intRelationshipLevel = " . $objDB->quote($this->_intRelationshipLevel) . ",
						intRelationshipEXP = " . $objDB->quote($this->_intRelationshipEXP) . ",
						intCurrentHunger = " . $objDB->quote($this->_intCurrentHunger) . ",
						intHungerRate = " . $objDB->quote($this->_intHungerRate) . ",
						intDigestionRate = " . $objDB->quote($this->_intDigestionRate) . ",
						intCurrentHP = " . $objDB->quote($this->_intCurrentHP) . "
						WHERE intRPGCharacterID = " . $objDB->quote($this->_intRPGCharacterID) . "
							AND intNPCID = " . $objDB->quote($this->_intNPCID);
		$objDB->query($strSQL);
		$this->_objStats->save();
	}
	
	public function loadSkills(){
		$objDB = new Database();
		$this->_arrSkillList = array();
		$strSQL = "SELECT intSkillID, strSkillType, strName, intReqLevel
					FROM tblnpcskillxr
						INNER JOIN tblskill
							USING (intSkillID)
						WHERE intNPCID = " . $objDB->quote($this->getNPCID());
		$rsResult = $objDB->query($strSQL);
		while($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
			$objRPGSkill = new RPGSkill($arrRow['intSkillID']);
			$objRPGSkill->setRequiredLevel($arrRow['intReqLevel']);
			$this->_arrSkillList[$arrRow['strSkillType']][$arrRow['strName']] = $objRPGSkill;
		}
	}
	
	public function getStatusEffectList(){
		return $this->_arrStatusEffectList;
	}
	
	public function loadActiveSkillList(){
		$this->_arrActiveSkillList = $this->_arrSkillList;
		foreach($this->_arrActiveSkillList as $strSkillType => $arrSkillList){
			foreach($arrSkillList as $key => $objSkill){
				$this->_arrActiveSkillList[$strSkillType][$key]->setCurrentCooldown($this->_arrActiveSkillList[$strSkillType][$key]->getPreCooldown());
			}
		}
	}
	
	public function addToStatusEffects($strStatusEffectName){
		$objStatusEffect = new RPGStatusEffect($strStatusEffectName);
		$objStatusEffect->setTimeRemaining($objStatusEffect->getDuration());
		if($objStatusEffect->getStatName() != NULL && !$objStatusEffect->getIncremental()){
			$this->_objStats->setStatusEffectStats($objStatusEffect->getStatName(), $objStatusEffect->getStatChangeMax(), $objStatusEffect->getStatusEffectName());
		}
		$this->_arrStatusEffectList[$strStatusEffectName] = $objStatusEffect;
	}
	
	public function removeFromStatusEffects($strStatusEffectName){
		if($this->_arrStatusEffectList[$strStatusEffectName]->getStatName() != NULL && !$this->_arrStatusEffectList[$strStatusEffectName]->getIncremental()){
			$this->_objStats->setStatusEffectStats($this->_arrStatusEffectList[$strStatusEffectName]->getStatName(), 0, $this->_arrStatusEffectList[$strStatusEffectName]->getStatusEffectName());
		}
		unset($this->_arrStatusEffectList[$strStatusEffectName]);
	}
	
	public function hasStatusEffect($strStatusEffectName){
		if(array_key_exists($strStatusEffectName, $this->_arrStatusEffectList)){
			return true;
		}
		else{
			return false;
		}
	}
	
	public function tickStatusEffects($intTicks = 1){
		for($i=0;$i<$intTicks;$i++){
			foreach($this->_arrStatusEffectList as $key => $objStatusEffect){
				if(!$this->_arrStatusEffectList[$key]->getInfinite()){
					$this->_arrStatusEffectList[$key]->tickStatusEffect();
					if($this->_arrStatusEffectList[$key]->getTimeRemaining() <= 0){
						$this->removeFromStatusEffects($key);
						break;
					}
				}
				$strStatName = $this->_arrStatusEffectList[$key]->getStatName();
				if($strStatName !== null){
					$intStatMin = $this->_arrStatusEffectList[$key]->getStatChangeMin();
					$intStatMax = $this->_arrStatusEffectList[$key]->getStatChangeMax();
					$intStatChange = mt_rand($intStatMin, $intStatMax);
					$strFunctionNameSet = "set" . $strStatName;
					$strFunctionNameGet = "get" . $strStatName;
					if($this->_arrStatusEffectList[$key]->getIncremental()){
						$this->$strFunctionNameSet($this->$strFunctionNameGet() + $intStatChange);
					}
				}
			}		
		}
	}
	
	public function tickStatusEffect($strStatusEffectName){
		$this->_arrStatusEffectList[$strStatusEffectName]->tickStatusEffect();
		if($this->_arrStatusEffectList[$strStatusEffectName]->getTimeRemaining() <= 0){
			$this->removeFromStatusEffects($strStatusEffectName);
		}
	}
	
	public function takeDamage($intDamage){
		$intDamage = max(0, $intDamage);
		$this->setCurrentHP($this->getCurrentHP() - $intDamage);
		return $intDamage;
	}
	
	public function isDead(){
		return intval($this->getCurrentHP()) <= 0 ? 1 : 0;
	}
	
	public function getCurrentHP(){
		return $this->_intCurrentHP;
	}
	
	public function setCurrentHP($intCurrentHP){
		$this->_intCurrentHP = $intCurrentHP;
	}
	
	public function loadEquippedArmour(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:%'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objArmour = new RPGItem($arrRow['intItemID']);
			return $objArmour;
		}
		else{
			$objArmour = new RPGItem();
			$objArmour->setWaitTime(0);
			return $objArmour;
		}
	}
	
	public function loadEquippedTop(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:Top'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objArmour = new RPGItem($arrRow['intItemID']);
			return $objArmour;
		}
		else{
			$objArmour = new RPGItem();
			$objArmour->setWaitTime(0);
			return $objArmour;
		}
	}
	
	public function loadEquippedBottom(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Armour:Bottom'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objArmour = new RPGItem($arrRow['intItemID']);
			return $objArmour;
		}
		else{
			$objArmour = new RPGItem();
			$objArmour->setWaitTime(0);
			return $objArmour;
		}
	}
	
	public function loadEquippedWeapon(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Weapon:%'
						AND (strHandType = 'Primary' OR strHandType = 'Both')
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objWeapon = new RPGItem($arrRow['intItemID']);
			return $objWeapon;
		}
		else{
			$objWeapon = new RPGItem();
			$objWeapon->setWaitTime(0);
			return $objWeapon;
		}
	}
	
	public function loadEquippedSecondary(){
		$objDB = new Database();
		$strSQL = "SELECT intItemID
					FROM tblitem
						INNER JOIN tblnpcitemxr
							USING (intItemID)
					WHERE strItemType LIKE 'Weapon:%'
						AND strHandType = 'Secondary'
						AND intNPCID = " . $objDB->quote($this->getNPCID()) . "
						AND blnEquipped = 1";
		$rsResult = $objDB->query($strSQL);
		if($rsResult->rowCount() > 0){
			$arrRow = $rsResult->fetch(PDO::FETCH_ASSOC);
			$objSecondary = new RPGItem($arrRow['intItemID']);
			return $objSecondary;
		}
		else{
			$objSecondary = new RPGItem();
			$objSecondary->setWaitTime(0);
			return $objSecondary;
		}
	}
	
	public function getRandomDrops(){
		$objDB = new Database();
		$arrDrops = array();
		$strSQL = "SELECT intItemID, strItemName, strItemType, intDropRating
					FROM tblnpcitemxr
						INNER JOIN tblitem
						USING(intItemID)
					WHERE blnDropped = 1
						AND intNPCID = " . $objDB->quote($this->getNPCID());
		$rsResult = $objDB->query($strSQL);
		while($arrRow = $rsResult->fetch(PDO::FETCH_ASSOC)){
			$intRand = mt_rand(0, 10000);
			if($intRand <= $arrRow['intDropRating']){
				$arrDrops[$arrRow['intItemID']] = $arrRow['strItemName'];
			}
		}
		return $arrDrops;
	}
	
	public function getEnemyBMI(){
		return ($this->getWeight() / dblLBS_PER_KG) / pow($this->getHeight() / 100, 2);
	}
	
	public function EnemyGainWeight($intAmount){
		$this->setWeight($this->getWeight() + $intAmount);
	}
	
	public function getModifiedMaxHP(){
		return round($this->_objStats->getBaseStats()['intMaxHP'] + ($this->_objStats->getCombinedStats('intVitality') / 2));
	}
	
	public function getModifiedStrength(){
		return $this->_objStats->getCombinedStats('intStrength');
	}
	
	public function getModifiedIntelligence(){
		return $this->_objStats->getCombinedStats('intIntelligence');
	}
	
	public function getModifiedVitality(){
		return $this->_objStats->getCombinedStats('intVitality');
	}
	
	public function getModifiedAgility(){
		return $this->_objStats->getCombinedStats('intAgility');
	}
	
	public function getModifiedWillpower(){
		return $this->_objStats->getCombinedStats('intWillpower');
	}
	
	public function getModifiedDexterity(){
		return $this->_objStats->getCombinedStats('intDexterity');
	}
	
	public function getModifiedDamage(){
		return round(($this->_objStats->getCombinedStats('intStrength') / 3) + $this->getEquippedWeapon()->getDamage());
	}
	
	public function getModifiedMagicDamage(){
		return round(($this->_objStats->getCombinedStats('intIntelligence') / 3) + $this->getEquippedWeapon()->getMagicDamage());
	}
	
	public function getModifiedDefence(){
		return round(($this->_objStats->getCombinedStats('intVitality') / 4) + $this->getEquippedArmour()->getDefence() + $this->getEquippedSecondary()->getDefence());
	}
	
	public function getModifiedMagicDefence(){
		return round(($this->_objStats->getCombinedStats('intIntelligence') / 4) + $this->getEquippedArmour()->getMagicDefence() + $this->getEquippedSecondary()->getMagicDefence());
	}
	
	public function getModifiedBlockRate(){
		return round($this->_objStats->getCombinedStatsSecondary('intBlockRate'));
	}
	
	public function getModifiedBlock(){
		return min((0.5 + ($this->_objStats->getCombinedStatsSecondary('intBlockReduction') / 100)), 1.0);
	}
	
	public function getStatusEffectResistance(){
		return round($this->_objStats->getCombinedStats('intWillpower') * 2);
	}
	
	public function getStatusEffectSuccessRate(){
		return round($this->_objStats->getCombinedStats('intWillpower') * 1);
	}
	
	public function getModifiedCritRate(){
		return round(pow(log($this->_objStats->getCombinedStats('intDexterity'), 2), 2.1));
	}
	
	public function getAdditionalDamage(){
		return round($this->_objStats->getCombinedStats('intWillpower') / 4);
	}
	
	public function getModifiedCritDamage(){
		return 1.5;
	}
	
	public function getModifiedCritResistance(){
		return 0;
	}
	
	public function getModifiedFleeRate(){
		return round($this->_objStats->getCombinedStats('intAgility') / 4);
	}
	
	public function getModifiedFleeResistance(){
		return round($this->_objStats->getCombinedStatsSecondary('intFleeResistance'));
	}
	
	public function getModifiedEvasion(){
		return round($this->_objStats->getCombinedStats('intAgility') + $this->_objStats->getCombinedStatsSecondary('intEvasion'));
	}
	
	public function getModifiedPierceRate(){
		return round($this->_objStats->getCombinedStatsSecondary('intPierce'));
	}
	
	public function getModifiedAccuracy(){
		return round($this->_objStats->getCombinedStats('intDexterity') + $this->_objStats->getCombinedStatsSecondary('intAccuracy'));
	}
	
	public function getWaitTime($udfWaitType){
		$intGearWait = $this->_objEquippedWeapon->getWaitTime() + $this->_objEquippedSecondary->getWaitTime() + $this->_objEquippedArmour->getWaitTime() + $this->_objEquippedTop->getWaitTime() + $this->_objEquippedBottom->getWaitTime();
		if($udfWaitType == 'Standard'){
			// standard attack
			return round(250 + $intGearWait - ($this->_objStats->getCombinedStats('intAgility') / 2) + (250 * $this->getImmobilityFactor()));
		}
		else{
			// skills will add on or decrease wait time by some amount defined by udfWaitType variable
			return round(250 + $udfWaitType + $intGearWait - ($this->_objStats->getCombinedStats('intAgility') / 2) + (250 * $this->getImmobilityFactor()));
		}
	}
	
	public function getStats(){
		return $this->_objStats;
	}
	
	public function getNPCID(){
		return $this->_intNPCID;
	}
	
	public function setNPCID($intNPCID){
		$this->_intNPCID = $intNPCID;
	}
	
	public function getNPCInstanceID(){
		return $this->_intNPCInstanceID;
	}
	
	public function setNPCInstanceID($intNPCInstanceID){
		$this->_intNPCInstanceID = $intNPCInstanceID;
	}
	
	public function getNPCName(){
		return $this->_strNPCName;
	}
	
	public function setNPCName($strNPCName){
		$this->_strNPCName = $strNPCName;
	}
	
	public function getShortName(){
		return $this->_strShortName;
	}
	
	public function setShortName($strShortName){
		$this->_strShortName = $strShortName;
	}
	
	public function getWeight(){
		return $this->_intWeight;
	}
	
	public function setWeight($intWeight){
		$this->_intWeight = $intWeight;
	}
	
	public function getEquippedArmour(){
		return $this->_objEquippedArmour;
	}
	
	public function setEquippedArmour($objArmour){
		$this->_objEquippedArmour = $objArmour;
	}
	
	public function getEquippedWeapon(){
		return $this->_objEquippedWeapon;
	}
	
	public function setEquippedWeapon($objWeapon){
		$this->_objEquippedWeapon = $objWeapon;
	}
	
	public function getEquippedSecondary(){
		return $this->_objEquippedSecondary;
	}
	
	public function setEquippedSecondary($objSecondary){
		$this->_objEquippedSecondary = $objSecondary;
	}
	
	public function getHeight(){
		return $this->_intHeight;
	}
	
	public function setHeight($intHeight){
		$this->_intHeight = $intHeight;
	}
	
	public function getExperienceGiven(){
		return $this->_intExperienceGiven;
	}
	
	public function setExperienceGiven($intExperienceGiven){
		$this->_intExperienceGiven = $intExperienceGiven;
	}
	
	public function getGoldDropMin(){
		return $this->_intGoldDropMin;
	}
	
	public function setGoldDropMin($intGoldDropMin){
		$this->_intGoldDropMin = $intGoldDropMin;
	}
	
	public function getGoldDropMax(){
		return $this->_intGoldDropMax;
	}
	
	public function setGoldDropMax($intGoldDropMax){
		$this->_intGoldDropMax = $intGoldDropMax;
	}
	
	public function getStartText(){
		return $this->_strStartText;
	}
	
	public function setStartText($strStartText){
		$this->_strStartText = $strStartText;
	}
	
	public function getForceStartText(){
		return $this->_strForceStartText;
	}
	
	public function setForceStartText($strForceStartText){
		$this->_strForceStartText = $strForceStartText;
	}
	
	public function getEndText(){
		return $this->_strEndText;
	}
	
	public function setEndText($strEndText){
		$this->_strEndText = $strEndText;
	}
	
	public function getFleeText(){
		return $this->_strFleeText;
	}
	
	public function setFleeText($strFleeText){
		$this->_strFleeText = $strFleeText;
	}
	
	public function getFailFleeText(){
		return $this->_strFailFleeText;
	}
	
	public function setFailFleeText($strFailFleeText){
		$this->_strFailFleeText = $strFailFleeText;
	}
	
	public function getDefeatText(){
		return $this->_strDefeatText;
	}
	
	public function setDefeatText($strDefeatText){
		$this->_strDefeatText = $strDefeatText;
	}
	
	public function getHasStartEvent(){
		return $this->_blnHasStartEvent;
	}
	
	public function setHasStartEvent($blnHasStartEvent){
		$this->_blnHasStartEvent = $blnHasStartEvent;
	}
	
	public function getHasEndEvent(){
		return $this->_blnHasEndEvent;
	}
	
	public function setHasEndEvent($blnHasEndEvent){
		$this->_blnHasEndEvent = $blnHasEndEvent;
	}
	
	public function getAIName(){
		return $this->_strAIName;
	}
	
	public function setAIName($strAIName){
		$this->_strAIName = $strAIName;
	}
	
	public function getWeightGain(){
		return $this->_intWeightGain;
	}
	
	public function setWeightGain($intWeightGain){
		$this->_intWeightGain = $intWeightGain;
	}
	
	public function getCreatedOn(){
		return $this->_dtmCreatedOn;
	}
	
	public function setCreatedOn($dtmCreatedOn){
		$this->_dtmCreatedOn = $dtmCreatedOn;
	}
	
	public function getCreatedBy(){
		return $this->_strCreatedBy;
	}
	
	public function setCreatedBy($strCreatedBy){
		$this->_strCreatedBy = $strCreatedBy;
	}
	
	public function getModifiedOn(){
		return $this->_dtmModifiedOn;
	}
	
	public function setModifiedOn($dtmModifiedOn){
		$this->_dtmModifiedOn = $dtmModifiedOn;
	}
	
	public function getModifiedBy(){
		return $this->_strModifiedBy;
	}
	
	public function setModifiedBy($strModifiedBy){
		$this->_strModifiedBy = $strModifiedBy;
	}
	
	public function getImmobilityFactor(){
		return max(0, ((($this->getBMI() / 40) / 10) - (($this->_objStats->getCombinedStats('intStrength') / 4) / 100)));
	}
	
	public function getBMI(){
		return ($this->getWeight() / dblLBS_PER_KG) / pow($this->getHeight() / 100, 2);
	}
	
	public function getHeightInFeet(){
		$dblFeet = $this->getHeight() / dblCM_PER_FOOT;
		$whole = floor($dblFeet);
		$fraction = $dblFeet - $whole;
		$intInches = round($fraction * intFEET_PER_INCH);
		if($intInches == 12){
			$whole++;
			$intInches = 0;
		}
		return strval($whole) . "'" . strval($intInches) . "\"";
	}
	
	public function getSkillList($strSkillType){
		return $this->_arrSkillList[$strSkillType];
	}
	
	public function getActiveSkillList($strSkillType){
		return (array_key_exists($strSkillType, $this->_arrActiveSkillList) ? $this->_arrActiveSkillList[$strSkillType] : false);
	}
	
	public function getActiveSkill($strSkillType, $strSkillName){
		return $this->_arrActiveSkillList[$strSkillType][$strSkillName];
	}
	
	public function getLastRoll(){
		return $this->_blnLastRoll;
	}
	
	public function setLastRoll($blnRoll){
		$this->_blnLastRoll = $blnRoll;
	}
	
	public function getLevel(){
		return $this->_intLevel;
	}
	
	public function setLevel($intLevel){
		$this->_intLevel = $intLevel;
	}
	
	public function getExperience(){
		return $this->_intExperience;
	}
	
	public function setExperience($intExperience){
		$this->_intExperience = $intExperience;
	}
	
	public function getRelationshipLevel(){
		return $this->_intRelationshipLevel;
	}
	
	public function setRelationshipLevel($intRelationshipLevel){
		$this->_intRelationshipLevel = $intRelationshipLevel;
	}
	
	public function getRelationshipEXP(){
		return $this->_intRelationshipEXP;
	}
	
	public function setRelationshipEXP($intRelationshipEXP){
		$this->_intRelationshipEXP = $intRelationshipEXP;
	}
	
	public function getConversationLevel(){
		return $this->_intConversationLevel;
	}
	
	public function setConversationLevel($intConversationLevel){
		$this->_intConversationLevel = $intConversationLevel;
	}
	
	public function getCurrentHunger(){
		return $this->_intCurrentHunger;
	}
	
	public function setCurrentHunger($intCurrentHunger){
		$this->_intCurrentHunger = $intCurrentHunger;
	}
	
	public function getHungerRate(){
		return $this->_intHungerRate;
	}
	
	public function setHungerRate($intHungerRate){
		$this->_intHungerRate = $intHungerRate;
	}
	
	public function getDigestionRate(){
		return $this->_intDigestionRate;
	}
	
	public function setDigestionRate($intDigestionRate){
		$this->_intDigestionRate = $intDigestionRate;
	}
	
	public function getMaxHunger(){
		return $this->_intMaxHunger;
	}
	
	public function setMaxHunger($intMaxHunger){
		$this->_intMaxHunger = $intMaxHunger;
	}
	
	public function gainExperience($intExpGain){
		if($this->getLevel() != 80){
			$this->_intExperience += $intExpGain;
		}
		if($this->_intExperience >= $this->_intRequiredExperience){
			$this->levelUp();
		}
	}
	
	public function levelUp(){
		$intExpDiff = $this->_intExperience - $this->loadRequiredExperience(); 
		$this->_intLevel++;
		$this->setExperience(0);
		$this->_intRequiredExperience = $this->loadRequiredExperience();
		$this->setCurrentHP($this->getModifiedMaxHP());
		$this->_objStats->applyGrowth($this->_objGrowth);
		$this->save();
		$this->gainExperience($intExpDiff);
	}
	
	public function loadRequiredExperience(){
		return pow(($this->_intLevel + 1) * 2, 2) * 100;
	}
	
	public function forceEatItemDeadly($intItemID){
		$objItem = new RPGItem($intItemID);
		$this->healHP($objItem->getHPHeal());
		$this->_intWeight += round($objItem->getCalories() / 3500);
		if($this->_intCurrentHunger + $objItem->getFullness() >= $this->getStats()->getCombinedStatsSecondary('intMaxHunger') * 2){
			$this->setCurrentHP(0);
			$this->_intCurrentHunger = 0;
		}
		else{
			$this->_intCurrentHunger = $this->_intCurrentHunger + $objItem->getFullness();
		}
	}
	
	public function forceEatItem($intItemID){
		$objItem = new RPGItem($intItemID);
		$this->healHP($objItem->getHPHeal());
		$this->_intCurrentHunger = min(($this->getStats()->getCombinedStatsSecondary('intMaxHunger') * 2), ($this->_intCurrentHunger + $objItem->getFullness()));
		$this->_intWeight += round($objItem->getCalories() / 3500);
	}
	
	public function forceEatItemMulti($intItemID, $intAmount){
		for($i=0;$i<$intAmount;$i++){
			$objItem = new RPGItem($intItemID);
			$this->healHP($objItem->getHPHeal());
			$this->_intCurrentHunger = min(($this->getStats()->getCombinedStatsSecondary('intMaxHunger') * 2), ($this->_intCurrentHunger + $objItem->getFullness()));
			$this->_intWeight += round($objItem->getCalories() / 3500);
		}
	}
	
	public function healHP($intHPHeal){
		$this->setCurrentHP(min($this->getModifiedMaxHP(), ($this->getCurrentHP() + $intHPHeal)));
	}
	
	public function stuffCharacter($intFullness, $intWeight, $intHPHeal){
		$this->setCurrentHunger(min(($this->getStats()->getCombinedStatsSecondary('intMaxHunger') * 2), $this->getCurrentHunger() + $intFullness));
		$this->setWeight($this->getWeight() + $intWeight);
		$this->healHP($intHPHeal);
	}
	
	public function stuffCharacterDeadly($intFullness, $intWeight){
		if($this->_intCurrentHunger + $intFullness >= $this->getStats()->getCombinedStatsSecondary('intMaxHunger') * 2){
			$this->setCurrentHP(0);
			$this->_intCurrentHunger = 0;
		}
		else{
			$this->_intCurrentHunger = $this->_intCurrentHunger + $intFullness;
		}
		$this->setWeight($this->getWeight() + $intWeight);
	}
	
	public function getPartyMemberID(){
		return $this->_intPartyMemberID;
	}
	
	public function setPartyMemberID($intPartyMemberID){
		$this->_intPartyMemberID = $intPartyMemberID;
	}
	
	public function tickHunger(){
		$this->_intCurrentHunger = max(0, $this->_intCurrentHunger - $this->_intHungerRate);
	}
}

?>