<?php

include_once "RPGCombatHelper.php";
	
class SkillSapWeight{
	
	public function __construct(){
		
	}

	public function castedByPlayer($objPlayer, $objNPC){
		
	}
	
	public function castedByNPC($objPlayer, $objNPC){

		$intSapRoll = mt_rand(0, 100);
		
		if($intSapRoll >= round($objPlayer->getStatusEffectResistance() - $objNPC->getStatusEffectSuccessRate())){
			$strReturnText = $objNPC->getNPCName() . " extends its pulsating vines at " . $objPlayer->getNPCName() . ", entangling them.";
			
			$objPlayer->addToStatusEffects("Fat Counter");
		}
		else{
			$strReturnText = $objNPC->getNPCName() . " extends its pulsating vines but " . $objPlayer->getNPCName() . " dodges them.";
		}
		return $strReturnText;
	}
	
	public function addWeight($objPlayer, $intDamage){
		$objPlayer->setWeight($objPlayer->getWeight() + round($intDamage / 3));
	}
	
	public function getWaitTime(){
		return 10;
	}
	
	public function getSkillSubType(){
		return "Debuff Ranged";
	}
}

?>