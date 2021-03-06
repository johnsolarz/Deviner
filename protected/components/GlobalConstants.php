<?php
/*Defines Gus application-wide constants.*/
class GlobalConstants {
	
	
	const DUE_DATE_DEFAULT = 0;
	
	
	//Screen printing setup fee -> see cost calculator
	const SETUP_FEE_AMOUNT_DEFAULT = 30;
	const SETUP_FEE_AMOUNT_WAIVED = 0;
	const SETUP_FEE_QUANTITY_DEFAULT = 1;
		
	//The score is a time estimate, in minutes, of how long the job will take
	const BASE_JOB_SCORE = 30;
	
	//24*60*60*7
	const SECONDS_IN_WEEK = 604800;
	//60*60*24
	const SECONDS_IN_DAY = 86400;
	
	
}