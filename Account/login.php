<!-- Input: Login info. Output: well, re-direct to page sent from after signing login credentials. -->

<?php session_start(); require($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");

  // outMessage is used to send fail information to the user. Mostly for debugging.
  global $outMessage;
  $outMessage = "";
  $bContinue = true;
  $bPassword = false;
  if(isset($_POST['BTC_Address']) && isset($_POST['returnURL'])) {
    // 1) Check for BTC existance. If exists, move on. If not, return with error: Not registered.
    $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
    $sql_query = "SELECT * FROM tbUserData WHERE UserID='" . escape_string($_POST['BTC_Address']) . "'";
    if($result = mysqli_query($connection, $sql_query)) {
      $row = mysqli_fetch_array($result);
      if(isset($row)) { // Caution: assumes there is only one result. Which there should be.
        $bContinue = true;
        if($row['Password'] != "") {
          $bPassword = true;
        } else {
          // 3) On success, create session variables, return success.
          unset($_SESSION['user']);
          array($_SESSION['user']);
          $_SESSION['user']['ID'] = $row['UserID'];
          $_SESSION['user']['Total_Balance'] = ($row['Balance'] + $row['Balance_NT']) / $DIV_BY_AMOUNT;
          $_SESSION['user']['Balance'] = $row['Balance'] / $DIV_BY_AMOUNT;
          $_SESSION['user']['Balance_NT'] = $row['Balance_NT'] / $DIV_BY_AMOUNT;
          $_SESSION['user']['Incentives'] = $row['Incentives'];
          $_SESSION['user']['History'] = $row['History'];
          $_SESSION['user']['PracticeAcctIdList'] = explode(",", $row['PracticeAcctIdList']);
          
          // Don't forget to get practice account information below:
        }
      } else {
        $bContinue = false;
                    header("Location: " . strstr($_POST['returnURL'] . "?", "?", true) . "?adr=true");
      }
    } else {
      $outMessage .= "Failed at reading from user list: " . mysqli_error($connection) . "\n";
      $bContinue = false;
    }
    
    // 2) Check for password. If it doesn't exist, move on. If it does exist, ask for it at whatever site sent you.
    if($bPassword && $bContinue) {
      if(isset($_POST['password'])) {
        // Continue
        $sql_query = "SELECT * FROM tbUserData WHERE UserID='" . escape_string($_POST['BTC_Address']) . "' AND Password='";
        $sql_query .= md5(escape_string($_POST['password'])) . "'";
        if($result = mysqli_query($connection, $sql_query)) {
          $row = mysqli_fetch_array($result);
          if(isset($row)) {
            // 3) On success, create session variables, return success.
            unset($_SESSION['user']);
            array($_SESSION['user']);
            $_SESSION['user']['ID'] = $row['UserID'];
            $_SESSION['user']['Total_Balance'] = ($row['Balance'] + $row['Balance_NT']) / $DIV_BY_AMOUNT;
            $_SESSION['user']['Balance'] = $row['Balance'] / $DIV_BY_AMOUNT;
            $_SESSION['user']['Balance_NT'] = $row['Balance_NT'] / $DIV_BY_AMOUNT;
            $_SESSION['user']['Incentives'] = $row['Incentives'];
            $_SESSION['user']['History'] = $row['History'];
            $_SESSION['user']['PracticeAcctIdList'] = explode(",", $row['PracticeAcctIdList']);
            
            // If there are practice accounts, load them into session information too below.
          } else {
            $bContinue = false;
            header("Location: " . strstr($_POST['returnURL'] . "?", "?", true) . "?Address=" . $_POST['BTC_Address'] . "&pw=false");
          }
        } else {
          $outMessage .= "Failed at fetching password: " . mysqli_error($connection) . "\n";
          $bContinue = false;
        }
      } else {
        // Well, re-direct BACK and ask for the password!
        header("Location: " . $_POST['returnURL'] . "?Address=" . $_POST['BTC_Address']);
        $outMessage .= "Ask for a password puh-weeese?\n";
        $bContinue = false;
      }
    }
    
    // 3) If at least one practice account existed, load all of them.
    if($_SESSION['user']['PracticeAcctIdList'] != "") {
      array($_SESSION['user']['PracticeAcct']);
      foreach($_SESSION['user']['PracticeAcctIdList'] as $key => $value) {
        // The '0' account is never loaded here.
        if($value == 0) continue;
        
        // As for the others, load them!
        $sql_query = "SELECT * FROM tbPracticeAccounts WHERE AcctID='$value'";
        if($result = mysqli_query($connection, $sql_query)) {
          $row = mysqli_fetch_array($result);

          // Create session variables
          unset($_SESSION['user']['PracticeAcct'][$value]);
          array($_SESSION['user']['PracticeAcct'][$value]);
          $_SESSION['user']['PracticeAcct'][$value]['Shared'] = $row['Shared'];
          $_SESSION['user']['PracticeAcct'][$value]['Balance_USD'] = $row['Balance_USD'] / $DIV_BY_AMOUNT;
          $_SESSION['user']['PracticeAcct'][$value]['Balance_BTC'] = $row['Balance_BTC'] / $DIV_BY_AMOUNT;
          $_SESSION['user']['PracticeAcct'][$value]['Settings'] = $row['Settings'];
          $_SESSION['user']['PracticeAcct'][$value]['Pending'] = $row['Pending'];
          $_SESSION['user']['PracticeAcct'][$value]['History'] = $row['History'];
          $_SESSION['user']['PracticeAcct'][$value]['ValueIncrease'] = $row['ValueIncrease'];
          $_SESSION['user']['PracticeAcct'][$value]['Value'] = $_SESSION['user']['PracticeAcct'][$value]['Balance_USD'] + ($_SESSION['user']['PracticeAcct'][$value]['Balance_BTC'] * getCurrentBTCPrice());
        } else {
          $outMessate .= "Failed at getting account $value from database: " . mysqli_error($connection) . "\n";
          $bContinue = false;
        }
      }
    }
    
    // 4) On success, create session variables, return success.
    if($bContinue) {
      $returnURL = $_POST['returnURL'] . "?";
      header("Location: " . strstr($returnURL, "?", true));
    } else {
      // Do something?
    }
  } else {
    $outMessage .= "Login Failed Somehow!\n";
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
        
        <?php echo $outMessage; ?>
        
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
