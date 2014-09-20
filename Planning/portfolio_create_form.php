<?php session_start(); ?>

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
        
        <?php
          // Include for constants...
          include($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
          
          // Display non-transferrable and total balances
          echo "<table>\n<tr>\n<td>Total Account Balance:</td>\n<td>" . $_SESSION['user']['Total_Balance'] . "</td>\n</tr>\n";
          echo "<tr>\n<td>Non-Transferrable Balance:</td>\n<td>" . $_SESSION['user']['Balance_NT'] . "</td>\n</tr>\n";
          echo "<tr>\n<td>Cost to open account (with included $PRACTICE_INCLUDED_BTC practice BTC):</td>\n<td>$OPEN_ACCOUNT_PRICE BTC</td>\n</tr>\n";
          echo "<tr>\n<td>Cost per practice BTC (after included $PRACTICE_INCLUDED_BTC pBTC):</td>\n<td>$PRACTICE_BTC_PRICE BTC</td>\n</tr>\n</table>";
          echo "<br />\n";
          
          // Have field for how many BTC / USD to add to the portfolio
          echo "<form action='portfolio_create.php' method='get'>\n";
          echo "<input type='hidden' name='Return_URL' value='" . $_SERVER['REQUEST_URI'] . "'>";
          echo "Additional pBTC to purchase: <input type='textbox' name='pBTC' value='0'><br />\n";
          echo "<input type='checkbox' name='shared'>Make this a 'shared' portfolio?<br />";
          echo "<input type='submit' value='Create Account'>";
          echo "</form>\n";
          
          // Display total cost of opening the portfolio (including cost of purchasing BTC/USD)
          
          // Button to actually open the portfolio
        ?>
        
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
