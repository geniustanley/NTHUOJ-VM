<?php
require_once("interfaceFunc.php");


/*
 * Receive request and information from dispature
 * Get files from dispature
 * Call judge.sh to run the submission code
 * Parse log file to get the result and run time
 * Return result and run time to dispature
 * Clean judgeFile folder
 * Clean cache files regularly
 */

	$CONFIG_FILE = "/tmp/nthuoj.config";

	/********************************************************************************/
	/* Read config file */
	/********************************************************************************/

	if($fp = fopen($CONFIG_FILE, "r")){
		fgets($fp, 128);
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($IP_ADDR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($RETURN_PAGE) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($SOURCE_DIR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($SPE_JUDGE_DIR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($PAR_JUDGE_DIR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($DATA_DIR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($ERR_MSG_DIR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($JUDGE_EXE) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($LOG_FILE) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($DEBUG_MODE) = $result;
		fgets($fp, 128);
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($JUDGE_FILE_DIR) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($ERR_MSG_FILE) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($RESULT_FILE) = $result;
		$result = fscanf($fp, "%*s %*s %s\n");
		list ($JUDGE_CONFIG_FILE) = $result;
		fclose($fp);
	}
	else{
		$errFlag = 1;
		writeLog("Can not read config file");
	}


	if($DEBUG_MODE){

		echo "SOURCE_DIR = ".$SOURCE_DIR."\n";
		echo "SPE_JUDGE_DIR = ".$SPE_JUDGE_DIR."\n";
		echo "PAR_JUDGE_DIR = ".$PAR_JUDGE_DIR."\n";
		echo "DATA_DIR = ".$DATA_DIR."\n";
		echo "JUDGE_FILE_DIR = ".$JUDGE_FILE_DIR."\n";
		echo "ERR_MSG_DIR = ".$ERR_MSG_DIR."\n";

		echo "LOG_FILE = ".$LOG_FILE."\n";
		echo "JUDGE_CONFIG_FILE = ".$JUDGE_CONFIG_FILE."\n";
		echo "RESULT_FILE = ".$RESULT_FILE."\n";
		echo "ERR_MSG_FILE = ".$ERR_MSG_FILE."\n";

		echo "JUDGE_EXE = ".$JUDGE_EXE."\n";

		echo "IP_ADDR = ".$IP_ADDR."\n";
		echo "RETURN_PATE = ".$RETURN_PAGE."\n";
	}


	/********************************************************************************/
	/* NTHU OJ start */
	/********************************************************************************/

	$userCode = "code.";
	$speJudgeCode = "speJudge.";
	$parJudgeCode = "parJudge.";

	$errFlag = 0;

	writeLog("==========interface start==========");




	/********************************************************************************/
	/* Receive request and information */
	/********************************************************************************/

	writeLog("Receiving information from dispatcher...");

	/*
	$errFlag |= recvInfo('sid', $SID);
	$errFlag |= recvInfo('pid', $PID);
	$errFlag |= recvInfo('tid', $TID);
	$errFlag |= recvInfo('codeLanType', $CODE_LANGUAGE_TYPE);
	$errFlag |= recvInfo('timeLimit', $TIME_LIMIT);
	$errFlag |= recvInfo('memoryLimit', $MEMORY_LIMIT);
	$errFlag |= recvInfo('judgeType', $JUDGE_TYPE);
	$errFlag |= recvInfo('judgeLanType', $JUDGE_LAN_TYPE);
	$errFlag |= recvInfo('machineName', $MACHINE_NAME);
	*/

	$SID = 1;
	$PID = 1;
	$TID = [1, 2];
	$CODE_LANGUAGE_TYPE = "C";
	$TIME_LIMIT = [1, 2];
	$MEMORY_LIMIT = [32, 32];
	$JUDGE_TYPE = "NORMAL";
	$JUDGE_LAN_TYPE = "C";
	$MACHINE_NAME = "1";

	$CASE_NUMBER = count($TID);

	if($errFlag){
		judgeError();
		writeLog("==========interface end==========");
		return;
	}
    if(!strcmp($CODE_LANGUAGE_TYPE,"C"))
        $CODE_LANGUAGE_TYPE = 'c';
    else
        $CODE_LANGUAGE_TYPE = 'cpp';
	$userCode = $userCode.$CODE_LANGUAGE_TYPE;
	
	writeLog("Recvive information end");



	/********************************************************************************/
	/* Move files to judge folder */
	/********************************************************************************/

	writeLog("Moving file to judge folder...");

	// Move source code
	writeLog("Moving source code...");
	$srcFile = $SOURCE_DIR.$SID.".".$CODE_LANGUAGE_TYPE;
	$tarFile = $JUDGE_FILE_DIR.$userCode;
	$errFlag |= moveFile($srcFile, $tarFile);
	echo $JUDGE_FILE_DIR."\n";

	// Move testcases
	writeLog("Moving testcases...");
	for ($i = 1; $i <= $CASE_NUMBER; $i++){
		// input
		$srcFile = $DATA_DIR.$TID[$i-1].".in";
		$tarFile = $JUDGE_FILE_DIR."testdata/in".$i;
		$errFlag |= moveFile($srcFile, $tarFile);

		// output
		$srcFile = $DATA_DIR.$TID[$i-1].".out";
		$tarFile = $JUDGE_FILE_DIR."testdata/out".$i;
		$errFlag |= moveFile($srcFile, $tarFile);
	}

	// Move special judge code
	if (!strcmp($JUDGE_TYPE, "SPECIAL")){
		writeLog("Moving special judge code...");
		$srcFile = $SPE_JUDGE_DIR.$PID.".".$JUDGE_LAN_TYPE;
		$tarFile = $JUDGE_FILE_DIR.$speJudgeCode.".".$JUDGE_LAN_TYPE;
		$errFlag |= moveFile($srcFile, $tarFile);
	}
	// Move Paritla judge code
	if (!strcmp($JUDGE_TYPE, "PARTIAL")){
		writeLog("Moving partial judge code...");
		$srcFile = $PAR_JUDGE_DIR.$PID.".".$JUDGE_LAN_TYPE;
		$tarFile = $JUDGE_FILE_DIR.$parJudgeCode.".".$JUDGE_LAN_TYPE;
		$errFlag |= moveFile($srcFile, $tarFile);
	}

	if($errFlag){
		judgeError();
		writeLog("==========interface end==========");
		return;
	}

	writeLog("Move files end");



	/********************************************************************************/
	/* Call judge.sh */
	/********************************************************************************/


	writeLog("Calling judge...");

	$codePath = $JUDGE_FILE_DIR.$userCode;
	$testdataDir = $JUDGE_FILE_DIR."testdata";

	// Write time limits and memory limits into judgeConfig file
	if($fp = fopen($JUDGE_CONFIG_FILE, "w")){
		for($i = 0; $i < $CASE_NUMBER; $i++)
			fprintf($fp, "%s\n%s\n", $TIME_LIMIT[$i], $MEMORY_LIMIT[$i]);
		fclose($fp);

		if($DEBUG_MODE) echo "\n".shell_exec("cat $JUDGE_CONFIG_FILE")."\n";
	}

	$cmd = $JUDGE_EXE." ".$codePath." ".$CODE_LANGUAGE_TYPE." ".$CASE_NUMBER." ".$testdataDir." ".$testdataDir." ".$JUDGE_TYPE;

	// Check special/parital judge or not
	if (!strcmp($JUDGE_TYPE, "SPECIAL")){
		$speJudgeCodePath = $JUDGE_FILE_DIR.$speJudgeCode.$JUDGE_LAN_TYPE;
		$cmd = $cmd." ".$JUDGE_TYPE." ".$speJudgeCodePath;
	}
	else if (!strcmp($JUDGE_TYPE, "PARTIAL")){
		$parJudgeCodePath = $JUDGE_FILE_DIR.$parJudgeCode.$JUDGE_LAN_TYPE;
		$cmd = $cmd." ".$JUDGE_TYPE." ".$parJudgeCodePath;
	}

	if($DEBUG_MODE) echo $cmd."\n";

	shell_exec($cmd);

	if(!file_exists($RESULT_FILE)){
		$errFlag = 1;
		writeLog("judge die");
	}

	if($errFlag){
		judgeError();
		writeLog("==========interface end==========");
		return;
	}

	writeLog("Call judge end");
 
 
 
	/********************************************************************************/
	/* Parse result file */
	/********************************************************************************/
/*
	writeLog("Parsing result...");

	if($fp = fopen($RESULT_FILE, "r")){
		while ($result = fscanf($fp, "%s\t%s\t%s\n")){
			list ($v, $runTime[], $memoryAmt[]) = $result; // time in ms, memory amount in MB
			$errFlag |= getVerdict($v, $verdict[], $errMsg[]);
		}
		fclose($fp);
	}
	else{
		$errFlag = 1;
		writeLog("Can not open judge result file");
	}

	if($errFlag){
		judgeError();
		writeLog("==========interface end==========");
		return;
	}

	if($DEBUG_MODE){
		print_r($verdict); echo "<br>";
		print_r($runTime); echo "<br>";
		print_r($memoryAmt); echo "<br>";
		print_r($errMsg); echo "<br>";
	}

	writeLog("Parse result file end");
 
*/ 
 
	/********************************************************************************/
	/* Return result, run time, and memory amount */
	/********************************************************************************/
/*
	writeLog("Returning result to dispatcher...");
	returnResult($verdict, $runTime, $memoryAmt, $errMsg);
	writeLog("Return result to dispatcher end");

*/ 
 
	/********************************************************************************/
	/* Clean judgeFile folder */
	/********************************************************************************/
/*
	writeLog("Cleaning judgeFile folder...");
	clearDirectory($JUDGE_FILE_DIR);
	writeLog("Clean judgeFile folder end");



	writeLog("==========interface end==========");
*/
?>

