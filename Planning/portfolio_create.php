<?php session_start(); include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
  // SQL for creation of a portfolio goes here. $OPEN_ACCOUNT_PRICE to open, $PRACTICE_BTC_PRICE per BTC after. TODO security
  
  // 1) Validate that information has been correctly received.
  $errMessage = "";
  if(!isset($_GET['pBTC'])) {
    $errMessage .= "No data received!\n";
  }
  
  if(!isset($_SESSION['user'])) {
    $errMessage .= "User is not logged in!\n";
  }
  
  // 2) Make sure that the user has sufficient BTC balance to create a new portfolio and buy requested BTC.
  //   But first, make sure that the input is a number.
  if((!is_numeric($_GET['pBTC'])) && ("" == $errMessage)) {
    $errMessage .= "Invalid user input: " . $_GET['pBTC'] . "\n";
  } else {
    $openingCost = $OPEN_ACCOUNT_PRICE + ($_GET['pBTC'] * $PRACTICE_BTC_PRICE);
    if($openingCost <= $_SESSION['user']['Total_Balance']) {
      if($openingCost <= $_SESSION['user']['Balance_NT']) {
        $_SESSION['user']['Balance_NT'] -= $openingCost;
        $_SESSION['user']['Total_Balance'] = $_SESSION['user']['Balance'] + $_SESSION['user']['Balance_NT'];
      } else {
        $openingCost -= $_SESSION['user']['Balance_NT'];
        $_SESSION['user']['Balance_NT'] = 0;
        $_SESSION['user']['Balance'] -= $openingCost;
        $_SESSION['user']['Total_Balance'] = $_SESSION['user']['Balance_NT'] + $_SESSION['user']['Balance'];
      }
    } else {
      $errMessage .= "Insufficient funds - $openingCost required, user only has " . $_SESSION['user']['Total_Balance'] . "\n";
    }
  }
  
  // 3) Sanatize input and update SQL (remove balance, create portfolio, link to account)
  if((!is_numeric($_SESSION['user']['Balance_NT'])) || (!is_numeric($_SESSION['user']['Balance']))) {
    $errMessage .= "Somehow you broke it?\n";
  } else {
    $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
    if(mysqli_connect_errno()) {
      $errMessage .= "SQL Connection Error: " . mysqli_connect_error();
    } else {
      $sql_query = "INSERT INTO tbPracticeAccounts (Shared, Balance_USD, Balance_BTC, ValueIncrease) ";
      $sql_query .= "VALUES (";
      if(isset($_GET['shared'])) $sql_query .= "'y'"; else $sql_query .= "'n'";
      $sql_query .= ", 0, " . round(($_GET['pBTC'] + $PRACTICE_INCLUDED_BTC) * $DIV_BY_AMOUNT, 2) . ", " . round(($_GET['pBTC'] + $PRACTICE_INCLUDED_BTC) * getCurrentBTCPrice(), 4) . ")";
      if(!mysqli_query($connection, $sql_query)) {
        $errMessage .= "Record Creation Fail: $sql_query failed because: " . mysqli_error($connection) . "\n";
      }
      
      $newID = mysqli_insert_id($connection);
      if(0 == $newID) {
        $errMessage .= "Record still somehow doesn't exist - if you see this message, PANIC.\n";
      } else {
        $sql_query = "UPDATE tbPracticeAccounts SET AcctID='$newID' WHERE PID='$newID'";
        if(!mysqli_query($connection, $sql_query)) {
          $errMessage .= "Record Update Fail: " . mysqli_error($connection) . "\n";
        }
        
        $userPracticeAcctIdList = "";
        foreach($_SESSION['user']['PracticeAcctIdList'] as $practiceAcctId) {
          $userPracticeAcctIdList .= $practiceAcctId . ",";
        }
        $userPracticeAcctIdList .= $newID;
      
        $sql_query = "UPDATE tbUserData SET PracticeAcctIdList='$userPracticeAcctIdList', ";
        $sql_query .= "Balance='" . round($_SESSION['user']['Balance'] * $DIV_BY_AMOUNT, 2) . "', ";
        $sql_query .= "Balance_NT='" . round($_SESSION['user']['Balance_NT'] * $DIV_BY_AMOUNT, 2) . "' WHERE UserID='";
        $sql_query .= $_SESSION['user']['ID'] . "'";
        if(!mysqli_query($connection, $sql_query)) {
          $errMessage .= "User Record Update Failed: " . mysqli_error($connection) . "\n";
        } else {
          $_SESSION['user']['PracticeAcctIdList'] = explode(",", $userPracticeAcctIdList);
          
          // Also, load the new practice account:
          $sql_query = "SELECT * FROM tbPracticeAccounts WHERE AcctID='$newID'";
          if($result = mysqli_query($connection, $sql_query)) {
            $row = mysqli_fetch_array($result);

            // Create session variables
            unset($_SESSION['user']['PracticeAcct'][$newID]);
            array($_SESSION['user']['PracticeAcct'][$newID]);
            $_SESSION['user']['PracticeAcct'][$newID]['Shared'] = $row['Shared'];
            $_SESSION['user']['PracticeAcct'][$newID]['Balance_USD'] = $row['Balance_USD'] / $DIV_BY_AMOUNT;
            $_SESSION['user']['PracticeAcct'][$newID]['Balance_BTC'] = $row['Balance_BTC'] / $DIV_BY_AMOUNT;
            $_SESSION['user']['PracticeAcct'][$newID]['Settings'] = $row['Settings'];
            $_SESSION['user']['PracticeAcct'][$newID]['History'] = $row['History'];
            $_SESSION['user']['PracticeAcct'][$newID]['ValueIncrease'] = $row['ValueIncrease'];
            $_SESSION['user']['PracticeAcct'][$newID]['Pending'] = $row['Pending'];
          } else {
            $errMessage .= "Failed at getting account $newID from database: " . mysqli_error($connection) . "\n";
          }
        }
      }
    }
  }
  
  // 4) Return success or fail.
  if("" == $errMessage) {
    header("Location: " . $_GET['Return_URL'] . "?success=true");
  }
?>

<!DOCTYPE html>
<html>
  <head>
    <title>BitWizard.com - Bitcoin Investment Training, Practice, and Planning</title>
    <link rel="stylesheet" type="text/css" href="/style.css">
  </head>
  <body>
    <div id="wrap">
      <div id="header"><?php include($_SERVER['DOCUMENT_ROOT'] . "/HEADER.php"); ?></div>
      <div id="nav">
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/NAVBAR.php"); ?>
      </div>
      <div id="main">
        
        <?php echo $errMessage; ?>
        
      </div>
      <div id="sidebar">
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/SIDEBAR.php"); ?>
      </div>
      <div id="footer">
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/FOOTER.php"); ?>
      </div>
    </div>
  </body>
</html>
