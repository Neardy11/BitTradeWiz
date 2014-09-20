<?php
// TODO: Add check - is BTC address a valid bitcoin address?
// TODO: Data sanitation - make sure no unseemly values slip in for SQL or XSS injections
  if(isset($_SESSION['user']['ID'])) {
    // If a user is already logged in, just show them their information with a logout button.
    echo "<form id='logoutForm' action='/Account/logout.php' method='post'>\n";
    echo "<input type='hidden' name='returnURL' value='" . $_SERVER['REQUEST_URI'] . "'>\n";
    echo "Welcome,<br /><i style='font-size: 0.75em'>" . $_SESSION['user']['ID'] . "</i>!\n";
    echo "<input type='submit' value='Logout'><br />";
    echo "</form><br />";
  } else {
    if(isset($_GET['Address'])) {
      // User is not logged in, but account is password protected
      $cockblock = true;
      echo "<script>var clicked=false;</script>\n";
      echo "<form action='/Account/login.php' method='post'>";
      echo "<input type='hidden' name='BTC_Address' value='" . $_GET['Address'] . "'>";
      echo "<input type='hidden' name='returnURL' value='" . $_SERVER['REQUEST_URI'] . "'>"; // error: includes get value?
      echo "Please enter your password:<br />";
      if(true == $_GET['pw']) {
        //... and the user guessed it wrong.
        echo "Oh, and try to get it right this time.<br />";
      }
      echo "<input type='password' name='password' value=''>";
      echo "<input type='submit' value='Login'>";
    } else {
      // Redirected to this form, but the address wasn't recognized by login.php
      echo "<script>var clicked=false;</script>\n";
      echo "<form action='/Account/login.php' method='post'>";
      if(true == $_GET['adr']) {
        echo "Bitcoin address not registered! <a href='/Account/account_main.php'>Register?</a><br />\n";
      }
      echo "<input type='text' id='textField1' name='BTC_Address' value='Bitcoin Address' style='width:220px;' onclick=\"if(clicked!=true) document.getElementById('textField1').value=''; clicked=true;\"><br />";
      echo "<input type='hidden' name='returnURL' value='" . $_SERVER['REQUEST_URI'] . "'>";
      echo "<input type='submit' value='Login'>";
      echo "</form><br />";
    }
  }
?>
