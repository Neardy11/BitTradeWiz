<?php
//--------------------------sql_includes.php----------------------------\\
//                                                                      \\
//  This file contains all of the basic includes for use with the SQL   \\
// database system - my_sqli login information, sanitation functions,   \\
// all of it. Include in every file that uses SQL.                      \\
//----------------------------------------------------------------------\\


// This is the amount to divide by when handling BTC numbers - they are WHOLE in DB, NOT outside.
$DIV_BY_AMOUNT = 10000000;

$LOCALHOST = "localhost";
$USER = "root";
$SQL_PASSWORD = "";
$DEFAULT_DB = "dbUsers";

$PRACTICE_BTC_PRICE = 0.001;
$OPEN_ACCOUNT_PRICE = 0.02;
$PRACTICE_INCLUDED_BTC = 2;
$UNSHARED_INCENTIVE = 0.00005;
$SHARED_INCENTIVE   = 0.00015;
$INCREASE_INCENTIVE = 0.001;

// Escape_String
//   input: toEscape (a string to escape)
//   output: the input string, stripped of any non-
//      alphanumeric characters in remove_chars set
//   Dangerous assumptions: This does not simply remove
//      all alphanumeric characters, UTC-16 chars can
//      easily slip through. Functional but dangerous.
function escape_string($toEscape) {

  $remove_chars = array('"', '-', '+', '=', '@', ';', '*', '\\', '\'', '%', '<', '>', '/', '\n', '$', '!', '#', '^', '&', '*', '(', ')', '~', '`', '[', ']', '{', '}', '|', ':', '?'); 

  for($i = 0; $i < count($remove_chars); $i++) {
    $toEscape = str_replace($remove_chars[$i], '', $toEscape);
  }

  for($i = 0; $i < count($escape_chars); $i++) {
    $toEscape = str_replace($escape_chars[$i], '\\' . $escape_chars[$i], $toEscape);
  }

  return $toEscape;
}

// GetTradeInfo
//  input: fName (file name), tradeID (trade ID)
//  output: In the file listed under fName, find the trade marked
//    by tradeID and return the 4 data points in an array (trade ID,
//    practice accout ID, bitcoin quantity, bitcoin price)
//  Dangerous assumptions: An impostor file is not used (same structure)
function GetTradeInfo($fName, $tradeID) {
  // May be buggy: comparison is an equality operation, make sure we're only fed IDs!
  $file_contents = file_get_contents($fName);
  
  if($file_contents == "") return false;
  
  $rows = explode("\n", $file_contents);
  
  for($i = 0; $i < count($rows); $i++) {
    $rowData = explode(",", $rows[$i]);
    
    if($rowData[0] == $tradeID) {
      return $rowData;
    }
  }
  
  return false;
}

// CompareIdValues
// input: id1, id2
// output: +1 if id1 is bigger, 0 if same, -1 if id2 is bigger
function compareIdValues($id1, $id2) {
  $charSet = "0123456789abcdefghijklmnopqrstuvwxyzACDEFGHIJKLMNOPQRTUVWXYZ";
  
  // First off, the one with longer length is bigger. So:
  if(strlen($id1) > strlen($id2)) {
    return 1;
  } else if(strlen($id1) < strlen($id2)) {
    return -1;
  } else {
    // Identical lengths. So, go through until the end.
    // At each character, the one that comes later in the list is bigger, so return that one as the bigger ID
    for($i = 0; $i < strlen($id1); $i++) {
      if(strpos($id1[$i], $charSet) < strpos($id1[$i], $charSet)) {
        return -1;
      } else if(strpos($id1[$i], $charSet) < strpos($id1[$i], $charSet)) {
        return 1;
      }
    }
    
    // Well, we made it all the way to the end, and neither one is bigger. So, they're the same.
    return 0;
  }
}

// Generating next ID values for buy and sell operations:
//   Inputs an ID value, adds one and outputs as next ID value.
function generateIdValue($input) {
  // Of course, the null case:
  if("" == $input) {
    return '0';
  }
  
  // Our ID character array set:
  // Last and first values are same as a hack - if value goes up one and is equal to the first value, recurse.
  // Notice that B and S are missing - those are to indicate "Buy" and "Sell"
  $charSet = "0123456789abcdefghijklmnopqrstuvwxyzACDEFGHIJKLMNOPQRTUVWXYZ";

  // -Input in reverse order into a string. Then, recursively:
  // -First character in string goes up one value. If it becomes
  //   the last element, do the same thing on the next element...
  $reverse = strrev($input);
  $i = 0; $continue = true;
  while($continue) {
    $continue = false;
    if(substr($charSet, -1, 1) == $reverse[$i]) {
      // Set our character to the first value, 0... And continue.
      $reverse[$i++] = $charSet[0];
      $continue = true;
    } else if(isset($reverse[$i])) {
      // Set our character to the next value in charSet...
      $reverse[$i] = $charSet[strpos($charSet, $reverse[$i]) + 1];
    } else {
      // Aww, adding a new digit! Shouldn't happen very often. At all. Like, bust out the champagne here.
      $reverse[$i] = $charSet[0];
    }
  }
  
  // Output our new value:
  return strrev($reverse);
}

// Push an item into operation_queue
function operationQueuePush($toAdd) {
  file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/data/operation_queue.dat", $toAdd . "\n", FILE_APPEND | LOCK_EX);
}

// Look for an item in operation_queue
function operationQueueSearch($search) {
  $file_contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/data/operation_queue.dat");
  if("" == $file_contents) {
    return false;
  } else {
    if(false === strpos($file_contents, $search)) {
      return false;
    } else {
      return true;
    }
  }
}

// Pull an item out of operation_queue
function operationQueuePull() {
  $file_contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/data/operation_queue.dat");
  if("" == $file_contents) {
    return false;
  } else {
    $file_lines = explode("\n", $file_contents);
    $toReturn = array_shift($file_lines);
    array_pop($file_lines);
    unset($file_contents);
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/data/operation_queue.dat", "", LOCK_EX);
    foreach($file_lines as $data) {
      file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/data/operation_queue.dat", $data . "\n", FILE_APPEND | LOCK_EX);
    }
    return $toReturn;
  }
}

// Push an item into btcPrice
function btcPricePush($toAdd) {
  file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/data/btcPrice.dat", $toAdd . "\n", FILE_APPEND | LOCK_EX);
}

// Pull an item out of btcPrice
function btcPricePull() {
  $file_contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/data/btcPrice.dat");
  if("" == $file_contents) {
    return false;
  } else {
    $file_lines = explode("\n", $file_contents);
    $toReturn = array_shift($file_lines);
    array_pop($file_lines);
    unset($file_contents);
    file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/data/btcPrice.dat", "", LOCK_EX);
    foreach($file_lines as $data) {
      file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/data/btcPrice.dat", $data . "\n", FILE_APPEND | LOCK_EX);
    }
    return $toReturn;
  }
}

function getCurrentBTCPrice() {
  $file_contents = file_get_contents($_SERVER['DOCUMENT_ROOT'] . "/database.dat");
  $file_lines = explode("\n", $file_contents);
  array_pop($file_lines);
  $priceLine = array_pop($file_lines);
  unset($file_contents);
  unset($file_lines);
  
  $lineData = explode("-", $priceLine);
  return ($lineData[2] / 100000);
}
?>
