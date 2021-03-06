<?php
  session_start();
  include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
  
  // Input: $_POST['rBTC_Address'] := a bitcoin address to register
  //        $_POST['returnURL'] := the URL to return to on success.
  //        $_POST['rPassword'] := password for user account (OPTIONAL)
  
  function escape_string2($toEscape) {
  
    $remove_chars = array('"', '-', '.', '+', '=', '@', ';', '*', '\\', '\'', '%', '<', '>', '/', '\n', '$', '!', '#', '^', '&', '*', '(', ')', '~', '`', '[', ']', '{', '}', '|', ':', '?'); 
    
    for($i = 0; $i < count($remove_chars); $i++) {
      $toEscape = str_replace($remove_chars[$i], '', $toEscape);
    }
    
    for($i = 0; $i < count($escape_chars); $i++) {
      $toEscape = str_replace($escape_chars[$i], '\\' . $escape_chars[$i], $toEscape);
    }
    
    return $toEscape;
  }
  
  function alphanum_check($input) {
    $acceptableSet = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    for($i = 0; isset($acceptableSet[$i]); $i++) {
      $input = str_replace($acceptableSet[$i], '', $input);
    }
    
    if(strlen($input) > 0) {
      return false;
    } else {
      return true;
    }
  }
  
  function BTCA_structure_check($input) {
    return ((strlen($input) <= 34) && (strlen($input) >= 20));
  }
  
  $b_continue = true;
  $errMessage = "";
  // 1) Check all values to make sure they are sanitized (SQL/XSS cleanse them)
  $BTC_Address_Clean = escape_string2($_POST['rBTC_Address']);
  $password_Clean = escape_string2($_POST['rPassword']);
  
  // 2) Check BTC address for validity
  // BTC Address:
  if(!((alphanum_check($BTC_Address_Clean)) && (BTCA_structure_check($BTC_Address_Clean))))
    $b_continue = false;
 
  // 3) Check to make sure that the BTC address isn't already registered
  if($b_continue) {
    $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
    $sql_query = "SELECT * FROM tbUserData WHERE UserID='$BTC_Address_Clean'";
    if($result = mysqli_query($connection, $sql_query)) {
      $row = mysqli_fetch_array($result);
      if(isset($row)) {
        $errMessage .= "You've already registered under this address!<br />\n";
        $b_continue = false;
      }
    } else {
      $errMessage .= "Problem: " . mysqli_error($connection) . "<br />\n";
      $b_continue = false;
    }
  }
  
  // 4) Encrypt the password
  if($b_continue) {
    if($password_Clean != "") {
      $passToSend = md5($password_Clean);
    } else {
      $passToSend = "";
    }
  }
  
  // 5) Update SQL Databases
  if($b_continue) {
    $sql_query = "INSERT INTO tbUserData (UserID, Password, Balance, Balance_NT, PracticeAcctIdList, Incentives) ";
    $sql_query .= "VALUES ('$BTC_Address_Clean', '$passToSend', 0, 1000000, '0', 'HALFMATCH')";
    if(mysqli_query($connection, $sql_query)) {
      $variable = 4;
    } else {
      $errMessage .= ("Failure Registering: " . mysqli_error($connection) . "<br />\n");
      $b_continue = false;
    }
  }

  // 6) Upon success, login and send to account main. On fail, describe le fail.
  if($b_continue) {
    unset($_SESSION['user']);
    array($_SESSION['user']);
    
    $_SESSION['user']['ID'] = $BTC_Address_Clean;
    $_SESSION['user']['Total_Balance'] = (1000000 / $DIV_BY_AMOUNT);
    $_SESSION['user']['Balance'] = (0 / $DIV_BY_AMOUNT);
    $_SESSION['user']['Balance_NT'] = (1000000 / $DIV_BY_AMOUNT);
    $_SESSION['user']['Incentives'] = "HALFMATCH";
    $_SESSION['user']['History'] = "";
    $_SESSION['user']['PracticeAcctIdList'] = "";

    header("Location: /Account/account_main.php");
  } else {
    echo "FAILED TO REGISTER. Error Messaging:<br />\n";
    echo $errMessage;
  }
  
?>
