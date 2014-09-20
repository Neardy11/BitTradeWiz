<?php
  require($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
  session_start();
  
  if(!isset($_SESSION['user']))
    die("ERROR - user is not logged in!");
    
  if(!isset($_POST['Return_URL']))
    die("ERROR - No return address given!");
  
  // For security reasons, we can't just dump all of our $_SESSION['user'] information
  //    into our SQL database, though that would be easier. Just change the POSTed variables.
  // 1) Create our update array, fill with relevant $_POST values. Don't use foreach though.
  array($toUpdate);
  if(isset($_POST['Password']))
    $toUpdate['Password'] = "'" . escape_string($_POST['Password']) . "'";
  if(isset($_POST['Balance']))
    $toUpdate['Balance'] = $_POST['Balance'];
  if(isset($_POST['Balance_NT']))
    $toUpdate['Balance_NT'] = $_POST['Balance_NT'];
  if(isset($_POST['History']))
    $toUpdate['History'] = "'" . $_POST['History'] . "'";
  if(isset($_POST['PracticeAcctIdList']))
    $toUpdate['PracticeAcctIdList'] = "'" . $_POST['PracticeAcctIdList'] . "'";
  if(isset($_POST['Incentives']))
    $toUpdate['Incentives'] = "'" . $_POST['Incentives'] . "'";
    
  // 2) Create our SQL connection, update values.
  $connection = mysqli_connect($LOCALHOST, $USER, $SQL_PASSWORD, $DEFAULT_DB);
  $sql_query = "SELECT * FROM tbUserData WHERE UserID='";
  $sql_query .= escape_string($_SESSION['user']['ID']);
  $sql_query .= "'";
  if($result = mysqli_query($connection, $sql_query)) {
    // Update values here.
    $bErr = false;
    foreach($toUpdate as $key => $value) {
      $sql_query = "UPDATE tbUserData SET $key=$value WHERE UserID='" . escape_string($_SESSION['user']['ID']) . "'";
      if(!($result = mysqli_query($connection, $sql_query))) {
        echo "Cannot update database: " . mysqli_error($connection);
        $bErr = true;
      }
      
      // Balances are stored diferently - make that adjustment quick.
      if(($key != "Balance") && ($key != "Balance_NT"))
        $_SESSION['user'][$key] = $value;
      else {
        $_SESSION['user'][$key] = ($value / $DIV_BY_AMOUNT);
        $_SESSION['user']['Total_Balance'] = $_SESSION['user']['Balance'] + $_SESSION['user']['Balance_NT'];
      }
    }
    if(!$bErr)
      header("Location: " . $_POST['Return_URL']);
  } else {
    echo "Cannot retrieve information: " . mysqli_error($connection);
    echo "\n<br />Account: " . escape_string($_SESSION['user']['ID']);
    echo "\n<br />Query: " . $sql_query;
    echo "\n<br />Database? " . $DEFAULT_DB;
  }
  
  // 3) Close our SQL connection. Easy as pie.
  mysqli_close($connection);
?>
