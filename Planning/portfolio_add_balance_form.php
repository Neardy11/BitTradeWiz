<?php
// portfolio_add_balance_form.php: the form for adding balance to the current portfolio.
session_start();
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
        
        <?php
        // Include for constants...
        include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
        
        // Display account balances, and have a list of portfolios, balances and values.
        //  Also display a warning that adding a BTC balance refreshes the "Value Increase", so cash that in BEFORE adding pBTC.
        echo "<table>\n";
        echo "  <tr>\n";
        echo "    <td>User Bitcoin Address:</td>\n";
        echo "    <td>" . $_SESSION['user']['ID'] . "</td>\n";
        echo "  </tr>\n";
        echo "  <tr>\n";
        echo "    <td>Total Balance (and Transferrable Balance)</td>\n";
        echo "    <td>" . $_SESSION['user']['Total_Balance'] . " (" . $_SESSION['user']['Balance'] . ")</td>\n";
        echo "  </tr>\n";
        echo "</table>\n";
        echo "<hr /><br />Portfolio List <br /><i>\n";
        echo "(WARNING: Adding BTC to a portfolio automatically wipes the Value Increase, make sure you cash in any positive amount before adding BTC).</i><br /><br />\n";
        echo "<table>\n";
        echo "  <tr>\n";
        echo "    <td style='width: 15%'><b>Account #</b></td>\n";
        echo "    <td style='width: 15%'><b>USD Balance</b></td>\n";
        echo "    <td style='width: 15%'><b>BTC Balance</b></td>\n";
        echo "    <td style='width: 15%'><b>Value Increase</b></td>\n";
        echo "  </tr>\n";
        foreach($_SESSION['user']['PracticeAcct'] as $key => $value) {
          echo "  <tr>\n";
          echo "    <td>$key</td>\n";
          echo "    <td>" . number_format($value['Balance_USD'], 2) . "</td>\n";
          echo "    <td>" . number_format($value['Balance_BTC'], 2) . "</td>\n";
          echo "    <td>" . number_format((($value['Value'] - $value['ValueIncrease']) / $value['ValueIncrease']) * 100, 2) . "%</td>\n";
          echo "  </tr>\n";
        }
        echo "</table>\n";
        
        // Now, provide them with a form, including a drop down list to select an account,
        //  a text field to put in how many BTC to add, another text field for how much Balance to use
        //  and a submit button. When one field is modified, the other one is updated accordingly.
        echo "<form action='portfolio_add_balance.php' method='post'>\n";
        echo "  Practice account to add funds to:&nbsp;&nbsp;&nbsp;&nbsp;\n  <select name='practiceAccountID' id='practiceAccountID'>\n";
        foreach($_SESSION['user']['PracticeAcct'] as $key => $value) {
          echo "    <option value='$key'>$key</option>\n";
        }
        echo "  </select><br />\n";
        echo "  <input type='hidden' id='returnURL' name='returnURL' value='" . $_SERVER['REQUEST_URI'] . "'>\n";
        echo "  Bitcoins to Add to Practice Account:<input type='text' name='btcToAdd' id='btcToAdd' value='0' onblur='document.getElementById(\"userCost\").value = document.getElementById(\"btcToAdd\").value * $PRACTICE_BTC_PRICE;'><br />\n";
        echo "  Cost to User Account:<input type='text' id='userCost' name='userCost' value='0' onblur='document.getElementById(\"btcToAdd\").value = document.getElementById(\"userCost\").value / $PRACTICE_BTC_PRICE;'><br />\n";
        echo "  <input type='submit' value='Submit'>\n";
        echo "</form>\n";
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
