<?php
	
class SmallWeightLoss{
	
	public function __construct(){
		
	}

	public function useItem($objRPGCharacter){
		$objRPGCharacter->setWeight($objRPGCharacter->getWeight() * 0.9);
	}
}

?>