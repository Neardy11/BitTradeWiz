<?php session_start(); include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");

  // This file is not ever meant to be seen on it's own. Purely for use with AJAX
  
  // All of the helper functions to help manage this website's grand trading system :)
  
  // Request: Get Next Operation.
  // Summary: Return the ID of the next operation to perform, and remove that operation from the queue.
  if("get_next_operation" == $_POST['request']) {
    echo operationQueuePull();
  }
  
  // Fill the queue - for debugging only. Fill the queue with every possible option.
  else if("fill_queue" == $_POST['request']) {
    operationQueuePush("SELL_SORT");
    operationQueuePush("BUY_SORT");
    echo "success";
  }
  
  // Add a latency check event - used for deciding when the web server needs an update.
  else if("check_latency" == $_POST['request']) {
    operationQueuePush("LATENCY_CHECK{" . time() . "}");
    echo "success";
  }
  
  // LATENCY_CHECK: Easy enough. Take the additional value ($_POST['data']), subtract it from now, record the difference.
  else if("LATENCY_CHECK" == $_POST['request']) {
    $timeDifference = 0; $startTime = 0;
    if(isset($_POST['data'])) {
      $startTime = $_POST['data'];
      $timeDifference = time() - $_POST['data'];
    } else {
      $startTime = -1;
      $timeDifference = 4;
    }
    
    // Record our newly found time difference...
    $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/latency.dat";
    $dataToWrite = date("d-m-y h:i:s", $startTime) . "," . date("i:s", $timeDifference) . "\n";
    file_put_contents($fName, $dataToWrite, FILE_APPEND | LOCK_EX);
    
    echo "success";
  }
  
  // BUY_SORT: Sort all buy elements from high to low.
  else if("BUY_SORT" == $_POST['request']) {
    // Input everything into two arrays, merge sort them!
    $buyFile = $_SERVER['DOCUMENT_ROOT'] . "/data/buy.dat";
    $buyTemp = $_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat";
    $outFile = $_SERVER['DOCUMENT_ROOT'] . "/data/buy.tmp";
    
    $fileData1 = file_get_contents($buyFile);
    $fileData2 = file_get_contents($buyTemp);
    
    $dataLines1 = explode("\n", $fileData1);
    $dataLines2 = explode("\n", $fileData2);
    
    array_pop($dataLines1); array_pop($dataLines2);
    
    $dataLines = array_merge($dataLines1, $dataLines2);
    $lineValues = array();
    
    foreach($dataLines as $index => $line) {
      $lineData = explode(",", $line);
      array_push($lineValues, $lineData[3]);
    }
    
    arsort($lineValues);
    
    foreach($lineValues as $index => $line) {
      // This operation will return them sorted high to low - so use it?
      echo "$index: $line<br />";
      file_put_contents($outFile, $dataLines[$index] . "\n", FILE_APPEND | LOCK_EX);
    }

    // Replace the old file...
    file_put_contents($buyTemp, "");
    unlink($buyFile);
    rename($outFile, $buyFile);
    
    // And, finally, our session buy_high is the highest bid!
    $_SESSION['buy_high'] = array_shift($lineValues);
  }
  
  // SELL_SORT: Sort all sell elements from low to high.
  else if("SELL_SORT" == $_POST['request']) {
    // Input everything into two arrays, merge sort them!
    $sellFile = $_SERVER['DOCUMENT_ROOT'] . "/data/sell.dat";
    $sellTemp = $_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat";
    $outFile = $_SERVER['DOCUMENT_ROOT'] . "/data/sell.tmp";
    
    $fileData1 = file_get_contents($sellFile);
    $fileData2 = file_get_contents($sellTemp);
    
    $dataLines1 = explode("\n", $fileData1);
    $dataLines2 = explode("\n", $fileData2);
    
    array_pop($dataLines1); array_pop($dataLines2);
    
    $dataLines = array_merge($dataLines1, $dataLines2);
    $lineValues = array();
    
    foreach($dataLines as $index => $line) {
      $lineData = explode(",", $line);
      array_push($lineValues, $lineData[3]);
    }
    
    asort($lineValues);
    
    foreach($lineValues as $index => $line) {
      // This operation will return them sorted low to high - so use it?
      echo "$index: $line<br />";
      file_put_contents($outFile, $dataLines[$index] . "\n", FILE_APPEND | LOCK_EX);
    }

    // Replace the old file...
    file_put_contents($sellTemp, "");
    unlink($sellFile);
    rename($outFile, $sellFile);
    
    // And, finally, our session sell_low is the lowest offer!
    $_SESSION['sell_low'] = array_shift($lineValues);
  }
  
  // Trade Check: Check for existing trades!
  else if("TRADE_CHECK" == $_POST['request']) {    
    // Find our exit time - 5 seconds from now.
    $exitTime = time() + 5; $bRunning = true;
    $buyList = array();  // This is an array containing the buys that have been added.
    $sellList = array();
    $tradeCounter = 0;
    $output = "";
    
    // While loop - continue running until we've run out of arr_bitcoin_prices OR until 30 seconds have elapsed.
    while($bRunning) {
      if(time() >= $exitTime) $bRunning = false; // Checked and is working.
      
      // Get our next value to process:
      $nextValue = btcPricePull();
      $timeRemaining = time() - $exitTime;
      if($nextValue == NULL) {
        $bRunning = false;
        break;
      } else {
        echo "$timeRemaining: $nextValue<br />\n";
      }
      
      // Go into our buy loop... if it's worth it!
      if($_SESSION['buy_high'] > $nextValue) {
        // Load our buy file...
        $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/buy.dat";
        $fileData = file_get_contents($fName);
        $fileRows = explode("\n", $fileData);
        array_pop($fileRows);  // Don't forget to leave behind the inevitable empty last line.
      
        $nextLine = array_shift($fileRows);
        $nextLineData = explode(",", $nextLine);
        
        while(($nextLineData[3] >= $nextValue) && ($nextLine != NULL)) {
          // Make sure buy hasn't already been processed, and if not, add it to the list to add...
          if(!in_array($nextLineData[0], $buyList)) array_push($buyList, $nextLineData[0]);

          // Now, just keep pulling them off until the offer is below the price.
          $nextLine = array_shift($fileRows);
          $nextLineData = explode(",", $nextLine);
        }
        
        //  Update our current buy high to the new high - the first one not to make the cut.
        if($nextLine != NULL) {
          $_SESSION['buy_high'] = $nextLineData[3];
        } else {
          $_SESSION['buy_high'] = -99999;
        }
      }
      
      if($_SESSION['sell_low'] < $nextValue) {
        // Load our buy file...
        $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/sell.dat";
        $fileData = file_get_contents($fName);
        $fileRows = explode("\n", $fileData);
        array_pop($fileRows);  // Don't forget to leave behind the inevitable empty last line.
       
        $nextLine = array_shift($fileRows);
        $nextLineData = explode(",", $nextLine);
        
        while(($nextLineData[3] <= $nextValue) && ($nextLine != NULL)) {
          // Make sure buy hasn't already been processed, and if not, add it to the list to add...
          if(!in_array($nextLineData[0], $sellList)) array_push($sellList, $nextLineData[0]);

          // Now, just keep pulling them off until the offer is below the price.
          $nextLine = array_shift($fileRows);
          $nextLineData = explode(",", $nextLine);
        }
        
        // Update our sell_low to reflect the new low price: the first one to NOT make the cut.
        if($nextLine != NULL) {
          $_SESSION['sell_low'] = $nextLineData[3];
        } else {
          $_SESSION['sell_low'] = 999999;
        }
      }
    }

    // Ask the computer to perform all of the requested buys...
    foreach($buyList as $buy) {
      operationQueuePush("BUY{" . $buy . "}");
      $tradeCounter++;
    }
    
    // Ask the computer to perform all of the requested sales...
    foreach($sellList as $sell) {
      operationQueuePush("SELL{" . $sell . "}");
      $tradeCounter++;
    }
    
    echo "Time elapsed: " . (time() - $exitTime + 5) . "<br />\n";
    echo "Trades Added: $tradeCounter<br />\n";
  }
  
  // BUY: Perform a purchase
  else if("BUY" == $_POST['request']) {
  
    // bContinue - set at false at any point to abort with message. Check before proceeding to next step.
    $bContinue = true;
    
    // Make sure that we've been given an ID to perform an action on...
    if(!isset($_POST['data'])) {
      echo "No buy ID given - abort<br />\n";
      $bContinue = false;
    }
    
    // Find the trade in buy.dat
    if($bContinue) {
      $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/buy.dat";
      $fileLines = explode("\n", file_get_contents($fName));
      array_pop($fileLines);

      $index = false;
      foreach($fileLines as $i => $line) {
        $lineData = explode(",", $line);
        if($_POST['data'] == $lineData[0]) {
          // Found our line, store the line's array key...
          $index = $i;
        }
      }
    }
    
    // Now we may have a line number. If not, exit with error. If so, let's get SQL dirty...
    if((isset($index)) && ($bContinue)) {
      // Parse our data into an array...
      $lineData = explode(",", $fileLines[$index]);
      echo "Works up to here...<br />\n";
      
      // Open practice account referenced - get balance information and trade information. Those will be modified and re-written.
      $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
      $sql_query = "SELECT Balance_USD, Balance_BTC, Pending, History FROM tbPracticeAccounts WHERE AcctID='" . $lineData[1] . "'";
      $result = mysqli_query($connection, $sql_query);
      
      $bal_usd = false; $tradeID = false;
      $bal_btc = false; $tradePrice = false;
      $acct_id = false; $btc_amount = false;
      $pending = false; $history = false;
      while($row = mysqli_fetch_array($result)) {
        $bal_usd = $row['Balance_USD'] / $DIV_BY_AMOUNT;
        $bal_btc = $row['Balance_BTC'] / $DIV_BY_AMOUNT;
        $history = $row['History'];
        $acct_id = $lineData[1];
        $pending = explode(",", $row['Pending']);
        $tradeID = "B" . $lineData[0];
        $tradePrice = $lineData[2] * $lineData[3];
        $btc_amount = $lineData[2];
      }
      
      // Now do our checks: Make sure that the trade is valid.
      // First check: Does the trade exist? If not, the user deleted it after a BUY_SORT event.
      if(!in_array($tradeID, $pending)) {
        echo "User has erased trade - abort, but with success.<br />\n";
        $bContinue = false;
      }
      
      // Second check: Does the user have sufficient funds to perform the BUY event?
      $bSufficientFunds = true;
      if((!$bal_usd) || ($bal_usd < $tradePrice)) {
        echo "User has not sufficient funds to complete this trade! Abort, but w/ success<br />\n";
        
        $bSufficientFunds = false;
      }
      
      // Now: Update amounts, update SQL table.
      if($bContinue) {
        if($bSufficientFunds) {
          // Store the new balance amounts here, but only if the user has enough money to deal with it. If not, they stay the same.
          $bal_usd -= $tradePrice;
          $bal_btc += $btc_amount;
        }
        $newPending = array();
        foreach($pending as $value) {
          if($tradeID != $value) {
            array_push($newPending, $value);
          } else {
            if($bSufficientFunds) {
              // Put it in our history array, but only if the trade actually went through.
              $history .= "$tradeID,$btc_amount,$tradePrice-";
              
              $historyData = explode("-", $history);
              array_pop($historyData);
              if(count($historyData) >= 10) {
                array_shift($historyData);
                $history = "";
                foreach($historyData as $line) {
                  $history .= "$line-";
                }
              }
            }
          }
        }
        
        // Update to pending...
        $pending = array_pop($newPending);
        foreach($newPending as $value) {
          $pending .= ("," . array_pop($newPending));
        }
        
        // Now, update SQL table.
        $sql_query = "UPDATE tbPracticeAccounts SET Balance_USD=" . round($bal_usd * $DIV_BY_AMOUNT, 2) . ", Balance_BTC=" . round($bal_btc * $DIV_BY_AMOUNT, 2) . ", Pending='$pending', History='$history' WHERE AcctID='$acct_id'";
        echo "SQL Query: $sql_query<br />\n";
        if(mysqli_query($connection, $sql_query)) {
          echo "Success!<br />\n";
        }
      }
      
       // And finally, remove from buy.dat
      file_put_contents($fName, "");
      foreach($fileLines as $i => $line) {
        $lineData = explode(",", $line);
        if($_POST['data'] == $lineData[0]) {
          // Found our line, store the line's array key...
          $index = $i;
        } else {
          file_put_contents($fName, "$line\n", FILE_APPEND | LOCK_EX);
        }
      } 
    } else {
      $bContinue = false;
      echo "Could not find buy in database!<br />\n";
    }
  }
  
  // SELL: Perform a purchase
  else if("SELL" == $_POST['request']) {
    // bContinue - set at false at any point to abort with message. Check before proceeding to next step.
    $bContinue = true;
    
    // Make sure that we've been given an ID to perform an action on...
    if(!isset($_POST['data'])) {
      echo "No sell ID given - abort<br />\n";
      $bContinue = false;
    }
    
    // Find the trade in buy.dat
    if($bContinue) {
      $fName = $_SERVER['DOCUMENT_ROOT'] . "/data/sell.dat";
      $fileLines = explode("\n", file_get_contents($fName));
      array_pop($fileLines);

      $index = false;
      foreach($fileLines as $i => $line) {
        $lineData = explode(",", $line);
        if($_POST['data'] == $lineData[0]) {
          // Found our line, store the line's array key...
          $index = $i;
        }
      }
    }
    
    // Now we may have a line number. If not, exit with error. If so, let's get SQL dirty...
    if((isset($index)) && ($bContinue)) {
      // Parse our data into an array...
      $lineData = explode(",", $fileLines[$index]);
      echo "Works up to here...<br />\n";
      
      // Open practice account referenced - get balance information and trade information. Those will be modified and re-written.
      $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
      $sql_query = "SELECT Balance_USD, Balance_BTC, Pending, History FROM tbPracticeAccounts WHERE AcctID='" . $lineData[1] . "'";
      $result = mysqli_query($connection, $sql_query);
      
      $bal_usd = false; $tradeID = false;
      $bal_btc = false; $tradePrice = false;
      $acct_id = false; $btc_amount = false;
      $pending = false; $history = false;
      while($row = mysqli_fetch_array($result)) {
        $bal_usd = $row['Balance_USD'] / $DIV_BY_AMOUNT;
        $bal_btc = $row['Balance_BTC'] / $DIV_BY_AMOUNT;
        $acct_id = $lineData[1];
        $pending = explode(",", $row['Pending']);
        $tradeID = "S" . $lineData[0];
        $tradePrice = $lineData[3];
        $btc_amount = $lineData[2];
        $history = $row['History'];
      }
      
      // Now do our checks: Make sure that the trade is valid.
      // First check: Does the trade exist? If not, the user deleted it after a BUY_SORT event.
      if(!in_array($tradeID, $pending)) {
        echo "User has erased trade - abort, but with success.<br />\n";
        $bContinue = false;
      }
      
      // Second check: Does the user have sufficient funds to perform the BUY event?
      $bSufficientFunds = true;
      if((!$bal_btc) || ($bal_btc < $btc_amount)) {
        echo "User has not sufficient funds to complete this trade! Abort, but w/ success<br />\n";
        $bSufficientFunds = false;  // Reinstate when you actually launch this sucker, with sell too.
      }
      
      // Now: Update amounts, update SQL table.
      if($bContinue) {
        if($bSufficientFunds) {
          $bal_btc -= $btc_amount;
          $bal_usd += ($btc_amount * $tradePrice);
        }
        $newPending = array();
        foreach($pending as $value) {
          if($tradeID != $value) {
            array_push($newPending, $value);
          } else {
            if($bSufficientFunds) {
              // Put it in our history array.             
              $history .= ("$tradeID,$btc_amount," . ($tradePrice * $btc_amount) . "-");
             
              $historyData = explode("-", $history);
              array_pop($historyData);
              
              if(count($historyData) >= 10) {
                array_shift($historyData);
                $history = "";
                foreach($historyData as $line) {
                  $history .= "$line-";
                }
              }
            }
          }
        }
        
        // Update to pending...
        $pending = array_pop($newPending);
        foreach($newPending as $value) {
          $pending .= ("," . array_pop($newPending));
        }
        
        // Now, update SQL table.
        $sql_query = "UPDATE tbPracticeAccounts SET Balance_USD=" . round($bal_usd * $DIV_BY_AMOUNT, 2) . ", Balance_BTC=" . round($bal_btc * $DIV_BY_AMOUNT, 2) . ", Pending='$pending', History='$history' WHERE AcctID='$acct_id'";
        echo "SQL Query: $sql_query<br />\n";
        if(mysqli_query($connection, $sql_query)) {
          echo "Success!<br />\n";
        }
      }  
      
      // And finally, remove from sell.dat
      file_put_contents($fName, "");
      foreach($fileLines as $i => $line) {
        $lineData = explode(",", $line);
        if($_POST['data'] == $lineData[0]) {
          // Found our line, store the line's array key...
          $index = $i;
        } else {
          file_put_contents($fName, "$line\n", FILE_APPEND | LOCK_EX);
        }
      } 
    } else {
      $bContinue = false;
      echo "Could not find sell in database!<br />\n";
    }
  }
  
  // The Default Case: Return an error message.
  else {
    echo "ERR_NULL";
  }
?>
