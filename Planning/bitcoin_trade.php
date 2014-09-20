<?php session_start(); include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");

// Error handling and debugging tools:
$errMessage = ""; $bContinue = true;

// Clean up all input...
$_POST['action'] = escape_string($_POST['action']);
$_POST['btcb_btc_price'] = escape_string($_POST['btcb_btc_price']);
$_POST['btcb_btc_amount'] = escape_string($_POST['btcb_btc_amount']);
$_POST['btcs_btc_amount'] = escape_string($_POST['btcs_btc_amount']);
$_POST['btcs_btc_price'] = escape_string($_POST['btcs_btc_price']);

// Buy:
if("buy" == $_POST['action']) {
  // Make sure that we have all required information before continuing...
  if((!isset($_POST['btcb_btc_amount'])) || (!isset($_POST['btcb_btc_price'])) || (!isset($_SESSION['user']['ActivePortfolio']['ID']))) {
    $errMessage .= "Not all required information is present.<br />\n";
    $bContinue = false;
  }
  
  //  -Make sure that the user, at time of posting, has enough funds to perform the transaction. DO NOT DEDUCT AT THIS POINT.
  if(!(($_POST['btcb_btc_amount'] * $_POST['btcb_btc_price']) < $_SESSION['user']['ActivePortfolio']['Balance_USD'])) {
//    $bContinue = false;
    $errMessage .= "Insufficient funds to complete BUY transaction!<br />\n";
  }
  
  //  -Post trade to buy_posted.dat
  if($bContinue) {
    $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/nextIdValues.dat";
    // The data we are going to write to a file...
    $dataToWrite = "";
    
    // Find next ID value... (alphanumeric, characters 0-9, a-z, A-Z)
    // -Find last ID value in buy_posted.dat. If DNE, use buy.dat.
    $fileContents = file_get_contents($fName);
    $tradeID = "";
    
    if("" == $fileContents) {
      // There seem to be no live trades at all! Our next ID is 0 then.
      $tradeID = "0";
    } else {
      // Oh, never mind. Get the last line, read the first value.
      $fileLines = explode("\n", $fileContents);
      $lastLine = $fileLines[0];
      
      // Get our ID, and put back into the file the next one.
      $tradeID = $lastLine;
      $putBack = generateIdValue($tradeID);
      file_put_contents($fName, $putBack . "\n" . $fileLines[1]);
      unset($fileLines);
    }
    
    $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat";
    
    $dataToWrite .= "$tradeID,";
    
    // Next, under what practice account is this?
    $dataToWrite .= ($_SESSION['user']['ActivePortfolio']['ID'] . ",");
    
    // How much are we buying again, and at what price?
    $dataToWrite .= ($_POST['btcb_btc_amount'] . "," . $_POST['btcb_btc_price'] . "\n");
    
    file_put_contents($fName, $dataToWrite, FILE_APPEND | LOCK_EX);
    
    //  -Post trade in format: "B#####" via SQL to practice account under "Pending"
    $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
    $sql_query = "SELECT Pending FROM tbPracticeAccounts WHERE AcctID='" . $_SESSION['user']['ActivePortfolio']['ID'] . "'";
    if($result = mysqli_query($connection, $sql_query)) {
      $row = mysqli_fetch_array($result);
      $updatedPendingTrades = "";
      if("" == $row['Pending']) {
        $updatedPendingTrades = "B$tradeID";
      } else {
        $updatedPendingTrades = $row['Pending'] . ",B$tradeID";
      }
      
      $sql_query = "UPDATE tbPracticeAccounts SET Pending='" . $updatedPendingTrades . "' WHERE AcctID='" . $_SESSION['user']['ActivePortfolio']['ID'] . "'";
      if($result = mysqli_query($connection, $sql_query)) {
        // Update SQL also.
        $_SESSION['user']['ActivePortfolio']['Pending'] = $updatedPendingTrades;
        $_SESSION['user']['PracticeAcct'][$_SESSION['user']['ActivePortfolio']['ID']]['Pending'] = $updatedPendingTrades;
      } else {
        $errMessage .= "SQL Connection Error in Updating Account<br />\n";
        $bContinue = false;
      }
    } else {
      $errMessage .= "SQL Connection Error in Selecting Account<br />\n";
      $bContinue = false;
    }
  }
  // Now, here, we perform our GLOBAL list operations - look in the OPERATION_QUEUE
  //  for a BUY_SORT event. If none exists, throw it on the end.
  if($bContinue) {
    if(!operationQueueSearch("BUY_SORT")) {
      operationQueuePush("BUY_SORT");
    }
    
    // Finally, if the user is offering to buy at greater than 104% of the current price, execute it right now.
    if($_POST['btcb_btc_price'] >= (1.04 * getCurrentBTCPrice())) {
      operationQueuePush("BUY{" . $tradeID . "}");
    }
  }
}

// Sell:
else if("sell" == $_POST['action']) {
  // Make sure that we have all required information before continuing...
  if((!isset($_POST['btcs_btc_amount'])) || (!isset($_POST['btcs_btc_price'])) || (!isset($_SESSION['user']['ActivePortfolio']['ID']))) {
    $errMessage .= "Not all required information is present.<br />\n";
    $bContinue = false;
  }
  
  //  -Make sure that the user, at time of posting, has enough funds to perform the transaction. DO NOT DEDUCT AT THIS POINT.
  if(!(($_POST['btcs_btc_amount']) < $_SESSION['user']['ActivePortfolio']['Balance_BTC'])) {
//    $bContinue = false;
    $errMessage .= "Insufficient funds to complete SELL transaction!<br />\n";
  }
  
  //  -Post trade to sell_posted.dat
  if($bContinue) {
    $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/nextIdValues.dat";
    // The data we are going to write to a file...
    $dataToWrite = "";
    
    // Find next ID value... (alphanumeric, characters 0-9, a-z, A-Z)
    // -Find last ID value in buy_posted.dat. If DNE, use buy.dat.
    $fileContents = file_get_contents($fName);
    $tradeID = "";
    
    if("" == $fileContents) {
      // There seem to be no live trades at all! Our next ID is 0 then.
      $tradeID = "0";
    } else {
      // Oh, never mind. Get the last line, read the first value.
      $fileLines = explode("\n", $fileContents);
      $lastLine = $fileLines[1];
      
      // Get our ID, and put back into the file the next one.
      $tradeID = $lastLine;
      $putBack = generateIdValue($tradeID);
      file_put_contents($fName, $fileLines[0] . "\n" . $putBack);
      unset($fileLines);
    }
    
    $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat";  

    $dataToWrite .= "$tradeID,";
    
    // Next, under what practice account is this?
    $dataToWrite .= ($_SESSION['user']['ActivePortfolio']['ID'] . ",");
    
    // How much are we selling again, and at what price?
    $dataToWrite .= ($_POST['btcs_btc_amount'] . "," . $_POST['btcs_btc_price'] . "\n");
    
    file_put_contents($fName, $dataToWrite, FILE_APPEND | LOCK_EX);
    
    //  -Post trade in format: "S#####" via SQL to practice account under "Pending"
    $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
    $sql_query = "SELECT Pending FROM tbPracticeAccounts WHERE AcctID='" . $_SESSION['user']['ActivePortfolio']['ID'] . "'";
    if($result = mysqli_query($connection, $sql_query)) {
      $row = mysqli_fetch_array($result);
      $updatedPendingTrades = "";
      if("" == $row['Pending']) {
        $updatedPendingTrades = "S$tradeID";
      } else {
        $updatedPendingTrades = $row['Pending'] . ",S$tradeID";
      }
      
      $sql_query = "UPDATE tbPracticeAccounts SET Pending='" . $updatedPendingTrades . "' WHERE AcctID='" . $_SESSION['user']['ActivePortfolio']['ID'] . "'";
      if($result = mysqli_query($connection, $sql_query)) {
        // Update SQL also.
        $_SESSION['user']['ActivePortfolio']['Pending'] = $updatedPendingTrades;
        $_SESSION['user']['PracticeAcct'][$_SESSION['user']['ActivePortfolio']['ID']]['Pending'] = $updatedPendingTrades;
      } else {
        $errMessage .= "SQL Connection Error in Updating Account<br />\n";
        $bContinue = false;
      }
    } else {
      $errMessage .= "SQL Connection Error in Selecting Account<br />\n";
      $bContinue = false;
    }
  }
  
  // Now, here, we perform our GLOBAL list operations - look in the OPERATION_QUEUE
  //  for a SELL_SORT event. If none exists, throw it on the end.
  if($bContinue) {
    if(!operationQueueSearch("SELL_SORT")) {
      operationQueuePush("SELL_SORT");
    }
    
    // Finally, if the user is offering to sell at less than 96% of the current price, execute it right now.
    if($_POST['btcs_btc_price'] <= (0.96 * getCurrentBTCPrice())) {
      operationQueuePush("SELL{" . $tradeID . "}");
    }
  }
}

// Cancel:
else if("cancel" == $_POST['action']) {
  // Make sure required data exists
  if((!isset($_POST['type'])) || (!isset($_POST['ID_to_cancel']))) {
    $errMessage .= "Cancel operation failed - missing required data!<br />\n";
    $bContinue = false;
  } else if("" == $_POST['ID_to_cancel']) {
    $errMessage .= "Cancel operation failed - data not correctly sent!<br />\n";
    $bContinue = false;
  }
  
  // Set variables depending on if it is a BUY or SELL transaction we are cancelling
  if($bContinue) {
    if("SELL" == $_POST['type']) {
      // Set array of file names for sell databases
      $fNames = array($_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat", $_SERVER['DOCUMENT_ROOT'] . "/data/sell.dat");
      $prefix = "S";
    } else if("BUY" == $_POST['type']) {
      // Set array of file names for buy databases
      $fNames = array($_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat", $_SERVER['DOCUMENT_ROOT'] . "/data/buy.dat");
      $prefix = "B";
    } else {
      $errMessage .= "Unexpected type given. Abort!<br />\n";
      $bContinue = false;
    }
  }
  
  //  -Find trade in buy_posted.dat, buy.dat, sell.dat, or sell_posted.dat and remove it.
  if($bContinue) {
    // Check each file given for the IDs, if they exist, carry on.
    $fName = "";
    foreach($fNames as $check) {
      if(false != GetTradeInfo($check, $_POST['ID_to_cancel'])) {
        $fName = $check;
      }
    }
    
    // ID wasn't found, abort.
    if("" == $fName) {
      $bContinue = false;
      $errMessage .= "Could not find specified ID in specified type.<br />\n";
    }
  }
    
    // Remove it from our trades queue - but only if it's still in buy/sell_posted.dat
    //    Reasoning: -if it's already in buy/sell.dat, the algorithm is running fairly well, and
    //    the cancelation wasn't performed too quickly after the posting. So, the computer will
    //    get to it and when it checks for the ID in the PracticeAccount queue and sees it missing,
    //    it will skip the trade then. Also, removing it from buy/sell.dat messes up statistics
    //    gathered from there, etc., etc.
    if("S" == $prefix) {
      // Open file, remove entry.
      // Do this by opening a temporary file, and writing in every line EXCEPT the one containing the desired ID.
      // Do this without checking for the ID - checking for the ID only opens the file and takes memory too. O(1) either way.
      $inFile = fopen($_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat", "r+");
      $outFile = fopen($_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.tmp", "w");
      flock($inFile, LOCK_SH);
      flock($outFile, LOCK_EX);
      
      while(false !== ($nextLine = fgets($inFile))) {
        // Process and write here
        $dataArray = explode(",", $nextLine);
        if($_POST['ID_to_cancel'] != $dataArray[0]) {
          fwrite($outFile, $nextLine);
        }
      }
      
      flock($inFile, LOCK_UN);
      flock($outFile, LOCK_UN);
      
      fclose($inFile);
      fclose($outFile);
      
      unlink($_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat");
      rename($_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.tmp", $_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat");
    } else {
      // Open file, remove entry.
      // Do this by opening a temporary file, and writing in every line EXCEPT the one containing the desired ID.
      // Do this without checking for the ID - checking for the ID only opens the file and takes memory too. O(1) either way.
      $inFile = fopen($_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat", "r+");
      $outFile = fopen($_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.tmp", "w");
      flock($inFile, LOCK_SH);
      flock($outFile, LOCK_EX);
      
      while(false !== ($nextLine = fgets($inFile))) {
        // Process and write here
        $dataArray = explode(",", $nextLine);
        if($_POST['ID_to_cancel'] != $dataArray[0]) {
          fwrite($outFile, $nextLine);
        }
      }
      
      flock($inFile, LOCK_UN);
      flock($outFile, LOCK_UN);
      
      fclose($inFile);
      fclose($outFile);
      
      unlink($_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat");
      rename($_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.tmp", $_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat");
    }
    
    if($bContinue) {
    //  -Remove trade in format: "(B/S)#####" via SQL to practice account under "Pending"
    $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
    $sql_query = "SELECT Pending FROM tbPracticeAccounts WHERE AcctID='" . $_SESSION['user']['ActivePortfolio']['ID'] . "'";
    if($result = mysqli_query($connection, $sql_query)) {
      $row = mysqli_fetch_array($result);
      $updatedPendingTrades = "";
      if("" == $row['Pending']) {
        $bContinue = false;
        $errMessage .= "Trade doesn't actually exist!<br />\n";
      } else {
        $updatedPendingTrades = str_replace($prefix . $_POST['ID_to_cancel'], "", $row['Pending']);
        $updatedPendingTrades = str_replace(",,", ",", $updatedPendingTrades);
        if("," == $updatedPendingTrades[0]) {
          $updatedPendingTrades = substr($updatedPendingTrades, 1);
        }
      }
      
      $sql_query = "UPDATE tbPracticeAccounts SET Pending='" . $updatedPendingTrades . "' WHERE AcctID='" . $_SESSION['user']['ActivePortfolio']['ID'] . "'";
      if($result = mysqli_query($connection, $sql_query)) {
        // Update SQL also.
        $_SESSION['user']['ActivePortfolio']['Pending'] = $updatedPendingTrades;
        $_SESSION['user']['PracticeAcct'][$_SESSION['user']['ActivePortfolio']['ID']]['Pending'] = $updatedPendingTrades;
      } else {
        $errMessage .= "SQL Connection Error in Updating Account<br />\n";
        $bContinue = false;
      }
    } else {
      $errMessage .= "SQL Connection Error in Selecting Account<br />\n";
      $bContinue = false;
    }
  }
}

// Redeem ValueIncrease
else if("redeem" == $_POST['action']) {
  $key = escape_string($_POST['portfolioID']);
  
  // Compare current USD value to old value:
  $_SESSION['user']['PracticeAcct'][$key]['Value'] = $_SESSION['user']['PracticeAcct'][$key]['Balance_USD'] + ($_SESSION['user']['PracticeAcct'][$key]['Balance_BTC'] * getCurrentBTCPrice());
  $currentValue = $_SESSION['user']['PracticeAcct'][$key]['Value'];
  $oldValue = $_SESSION['user']['PracticeAcct'][$key]['ValueIncrease'];
  $percentDelta = (($currentValue - $oldValue) / $oldValue) * 100;
  
  echo "Active Portfolio Data:<br />\n";
  foreach($_SESSION['user']['PracticeAcct'][$key] as $tkey => $data) {
    echo "$tkey: $data<br />\n";
  }
  echo "Data so far gathered:<br />\nOld Value: $oldValue<br />\nCurrent Value: $currentValue<br />\nPercent Delta: $percentDelta%<br />\n";
  
  // Now, what to do? If the percentage is zero or negative, set to zero and exit.
  if(0 >= $percentDelta) {
    $btcGive = 0;
  }
  
  // If it is positive, calculate how much BTC to add to account and cash it in!
  else {
    $btcGive = 0;
    // If the account is shared, they get more benefits.
    if("y" == $_SESSION['user']['PracticeAcct'][$key]['Shared']) {
      $btcGive += ($SHARED_INCENTIVE * ($percentDelta / 10));
    }
    
    // If not, they get benefits but reduced.
    else {
      $btcGive += ($UNSHARED_INCENTIVE * ($percentDelta / 10));
    }
  }
  
  // Update SQL information... Get most current information, then add balance, then put back into SQL.
  $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
  if($result = mysqli_query($connection, "SELECT Balance FROM tbUserData WHERE UserID='" . $_SESSION['user']['ID'] . "'")) {
    $row = mysqli_fetch_array($result);
    $newBalance = $row['Balance'] + ($btcGive * $DIV_BY_AMOUNT);
    
    if($result = mysqli_query($connection, "UPDATE tbUserData SET Balance='" . round($newBalance, 2) . "' WHERE UserID='" . $_SESSION['user']['ID'] . "'")) {
      $_SESSION['user']['Balance'] = $newBalance / $DIV_BY_AMOUNT;
      $_SESSION['user']['Total_Balance'] = ($newBalance + $_SESSION['user']['Balance_NT']) / $DIV_BY_AMOUNT;
    } else {
      $errMessage .= "SQL Error in Updating User Balance<br />\n";
      $bContinue = false;
    }
    
    if($result = mysqli_query($connection, "UPDATE tbPracticeAccounts SET ValueIncrease='" . round($currentValue, 4) . "' WHERE AcctID='" . $key . "'")) {
      $_SESSION['user']['PracticeAcct'][$key]['ValueIncrease'] = round($currentValue, 4);
      unset($_SESSION['user']['ActivePortfolio']);
    } else {
      $errMessage .= "SQL Error in Updating Practice Account<br />\n";
      $bContinue = false;
    }
    
    echo "To Give: $btcGive<br />\nOld Balance: "  . $row['Balance'] . "<br />\nNew Value: $newBalance<br />\n";
  } else {
    $errMessage .= "SQL Error in Getting Balance<br />\n";
    echo "SQL ERROR<br />\n";
    $bContinue = false;
  }
}

// None of above options
else {
  $bContinue = false;
  $errMessage .= "Page seems to have been loaded from an invalid source. Please exit this page.\n<br />";
}

if($bContinue) {
  header("Location: " . $_POST['Return_URL']);
} else {
  echo "$errMessage";
}
?>
