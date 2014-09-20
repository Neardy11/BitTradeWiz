<?php session_start(); ?>

<!DOCTYPE html>
<html>
  <head>
    <title>BitWizard.com - Bitcoin Investment Training, Practice, and Planning</title>
    <link rel="stylesheet" type="text/css" href="/style.css">
    <style>
      /* Put styling for the portfolio here */
      div.infoBox {
        background-color: grey;
        width: 100%;
        border: 3px;
        padding: 0px;
        margin: 2px;
      }
    </style>
    <!-- AJAX script here -->
    <script>

      function dataSend(var data) {
        // Initialize our xmlhttp object here:
        var xmlhttp;
        if(window.XMLHttpRequest) {
          xmlhttp = new XMLHttpRequest();
        } else {
          // For old IE versions
          xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
      
        xmlhttp.onreadystatechange = function () {
          if(4 == xmlhttp.readyState && 200 == xmlhttp.status) {
            // Actions to change on site HERE.
            if("success" == xmlhttp.responseText) {
              document.reload();
            } else {
              alert("Error Code: " + xmlhttp.responseText);
            }
          }
        }
        xmlhttp.open("GET", "portfolio_select.php?" + data, true);
        xmlhttp.send();
      }
    </script>
  </head>
  <body>
    <div id="wrap">
      <div id="header"><?php include_once($_SERVER['DOCUMENT_ROOT'] . "/HEADER.php"); ?></div>
      <div id="nav">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/NAVBAR.php"); ?>
      </div>
      <div id="main">
        <?php include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
          // 1) You'll need to be logged in.
          //  a) Check for that right now! If logged in, great! If not, show Portfolio 0.
          if(isset($_SESSION['user'])) {
            if(isset($_SESSION['user']['ActivePortfolio'])) {
              display_portfolio($_SESSION['user']['ActivePortfolio']['ID']);
            } else {
              select_portfolio();
              display_portfolio(0);
            }
          } else {
            select_portfolio(false);
            display_portfolio(0);
            echo "<br /><hr /><i><a href='/Account/account_main.php'>Log In</a> for access to practice trading</i>";
            echo "<br /><br /><i>Don't have an account? Don't worry! <a href='/Account/account_main.php'>Registration</a>\n";
            echo " is a one step process here, no email validation or anything like that. Just put in your bitcoin address\n";
            echo " and go! Do it now!<br />\n";
          }
          
          // 2) You'll need to have set up a practice portfolio, or set one up now. (NOTE: Portfolio 0 is always open, never changes)
          // FUNCTION: Select Portfolio
          //   Purpose: Prompt user for which portfolio he'd like to open, select it, store it as $_SESSION['user']['ActivePortfolio']
          //   Note: Must be one of user's portfolios. Give him a list of options.
          //   If none exist, include the "Portfolio_Create" form here. If some exist,
          //   just provide a link to the portfolio_create form.
          function select_portfolio($showRegForm=true) {
            // Check to see if the user has any portfolios:
            if(isset($_SESSION['user']['PracticeAcct'])) {
              echo "<form action='portfolio_select.php' method='post'>\n";
              echo "  Select account to use";
              if(isset($_SESSION['user']['ActivePortfolio'])) {
                echo " (currently using " . $_SESSION['user']['ActivePortfolio']['ID'] . ")";
              }
              echo ":<br />\n  <select name='acc_select' id='acc_select'>\n";
              foreach($_SESSION['user']['PracticeAcct'] as $key => $value) {
                echo "    <option value='$key'>$key ($" . number_format($value['Balance_USD'], 2) . ", " . number_format($value['Balance_BTC'], 2) . "BTC)</option>";
              }
              echo "  </select>\n";
              echo "  <input type='hidden' name='Return_URL' id='Return_URL' value='" . $_SERVER['REQUEST_URI'] . "'>\n";
              echo "  <input type='submit' value='Select'>\n";
              echo "</form>\n<br />";
            }
            // Button that leads to the portfolio create form
            if($showRegForm) {
              echo "<input type='button' value='Create New Portfolio' onclick=\"window.location='/Planning/portfolio_create_form.php';\">";
            }
          }
          
          // FUNCTION: Display Portfolio
          //   Purpose: Take input a portfolio ID, output the main content with portfolio data and actions.
          function display_portfolio($portfolioID) {
            // Still include the portfolio select bit, because really it's quite short.
            if($portfolioID != 0) select_portfolio();
 
            // As part of the portfolio, display:
            // 1) Graph (use the one from armain.php)
            echo "<iframe src=\"/graph.php\" width=\"700px\" height=\"350px\" style=\"padding: 0px; margin: 0px;\" seamless>Your browser does not support iframes!</iframe>\n";
            
            // 2) A buy AND sell tab, listed "amount, price, cost" with a button. Enter any two values, third will be calculated.
            if(isset($_SESSION['user']['ActivePortfolio'])) {
              echo "<div class='infoBox' id='BuySellTab'>\n";
              echo "  <table style='width: 100%;'>\n<tr>";
              echo "<td>USD Balance: $" . number_format($_SESSION['user']['ActivePortfolio']['Balance_USD'], 6) . "</td><td>Bitcoin Balance: " . number_format($_SESSION['user']['ActivePortfolio']['Balance_BTC'], 6) ."</td>";  // TODO: Update to reflect actual balances
              echo "</tr><tr>\n<td style='border: solid 1px black; width: 50%'>\n";
              echo "    <form action='/Planning/bitcoin_trade.php' method='post'>\n";
              echo "      <table><tr><td width='100%'>";
              echo "      <input type='hidden' name='action' value='buy'>\n";
              echo "      <input type='hidden' name='Return_URL' value='" . $_SERVER['REQUEST_URI'] . "'>\n";
              echo "      Amount BTC to buy:</td><td><input name='btcb_btc_amount' id='btcb_btc_amount' style='width: 100px;' type='text' value='0.01' onblur=\"document.getElementById('btcb_usd_cost').value=(document.getElementById('btcb_btc_amount').value * document.getElementById('btcb_btc_price').value).toPrecision(6);\">\n</td></tr><td>";
              echo "      Price Per BTC:</td><td><input name='btcb_btc_price' id='btcb_btc_price' type='text' style='width: 100px;' value='100.05' onblur=\"document.getElementById('btcb_usd_cost').value=(document.getElementById('btcb_btc_amount').value * document.getElementById('btcb_btc_price').value).toPrecision(6);\"></td><td>\n";
              echo "      <input type='submit' name='btcb_submit' value='Buy'></td></tr>\n<td>";
              echo "     Cost (in USD):</td><td><input name='btcb_usd_cost' id='btcb_usd_cost' type='text' style='width: 100px;' value='1.0005' onblur=\"document.getElementById('btcb_btc_amount').value=(document.getElementById('btcb_usd_cost').value / document.getElementById('btcb_btc_price').value).toPrecision(6);\"></td></tr>\n</table>";
              echo "    </form>";
              echo "  </td>\n<td style='border: solid 1px black'>";
              echo "    <form action='/Planning/bitcoin_trade.php' method='post'>\n";
              echo "      <table><tr><td width='100%'>";
              echo "      <input type='hidden' name='action' value='sell'>\n";
              echo "      <input type='hidden' name='Return_URL' value='" . $_SERVER['REQUEST_URI'] . "'>\n";
              echo "      Amount BTC to sell:</td><td><input name='btcs_btc_amount' id='btcs_btc_amount' style='width: 100px;' type='text' value='0.01' onblur=\"document.getElementById('btcs_usd_cost').value=(document.getElementById('btcs_btc_amount').value * document.getElementById('btcs_btc_price').value).toPrecision(6);\">\n</td></tr><td>";
              echo "      Price Per BTC:</td><td><input name='btcs_btc_price' id='btcs_btc_price' type='text' style='width: 100px;' value='100.05' onblur=\"document.getElementById('btcs_usd_cost').value=(document.getElementById('btcs_btc_amount').value * document.getElementById('btcs_btc_price').value).toPrecision(6);\"></td><td>\n";
              echo "      <input type='submit' name='btcs_submit' value='Sell'></td></tr>\n<td>";
              echo "     Gain (in USD):</td><td><input name='btcs_usd_cost' id='btcs_usd_cost' type='text' style='width: 100px;' value='1.0005' onblur=\"document.getElementById('btcs_btc_amount').value=(document.getElementById('btcs_usd_cost').value / document.getElementById('btcs_btc_price').value).toPrecision(6);\"></td></tr>\n</table>";
              echo "    </form>";
              echo "  </td>\n</tr>\n</table>\n";
              echo "</div>\n";
            }
            
            // 3) Pending transaction spot... use for pending transactions ( Transaction type, transaction value, transaction volume )
            if(isset($_SESSION['user']['ActivePortfolio'])) {
              echo "<div class='infoBox' id='PendingTransactions'>\n";
              echo "<!--Form for cancelling transactions--><form id='cancelForm' action='/Planning/bitcoin_trade.php' method='post'>\n";
              echo "<input type='hidden' name='action' value='cancel'>\n";
              echo "<input type='hidden' name='Return_URL' value='" . $_SERVER['REQUEST_URI'] . "'>\n";
              echo "<input type='hidden' name='ID_to_cancel' id='tcID' value=''>\n";
              echo "<input type='hidden' name='type' id='tcTYPE' value=''>\n</form>";
              echo "  <table style='width:100%'>\n";
              $pendingTransactions = explode(",", $_SESSION['user']['ActivePortfolio']['Pending']);
              $index = 1;
              foreach($pendingTransactions as $PendingTransaction) {
                if("B" == $PendingTransaction[0]) {
                  echo "<tr style='background-color: #E0FFFF;'><td>$index</td><td>"; $index++;
                  echo "Buy</td>\n";
                  if($tradeInfo = GetTradeInfo($_SERVER['DOCUMENT_ROOT'] . "/data/buy_posted.dat", substr($PendingTransaction, 1))) {
                    echo "<td>" . $tradeInfo[2] . " BTC x</td><td>$" . $tradeInfo[3] . "</td><td>$" . ($tradeInfo[2] * $tradeInfo[3]) . "</td>";
                    echo "<td><input type='button' value='Cancel Trade' onclick=\"document.getElementById('tcID').value='" . $tradeInfo[0] . "'; document.getElementById('tcTYPE').value='BUY'; document.getElementById('cancelForm').submit();\" /></td></tr>\n";
                  } else if($tradeInfo = GetTradeInfo($_SERVER['DOCUMENT_ROOT'] . "/data/buy.dat", substr($PendingTransaction, 1))) {
                    echo "<td>" . $tradeInfo[2] . " BTC x</td><td>$" . $tradeInfo[3] . "</td><td>$" . ($tradeInfo[2] * $tradeInfo[3]) . "</td>";
                    echo "<td><input type='button' value='Cancel Trade' onclick=\"document.getElementById('tcID').value='" . $tradeInfo[0] . "'; document.getElementById('tcTYPE').value='BUY'; document.getElementById('cancelForm').submit();\" /></td></tr>\n";
                  }
                } else if("S" == $PendingTransaction[0]) {
                  echo "<tr style='background-color: #FFFFE0;'><td>$index</td><td>"; $index++;
                  echo "Sell</td>\n";
                  if($tradeInfo = GetTradeInfo($_SERVER['DOCUMENT_ROOT'] . "/data/sell_posted.dat", substr($PendingTransaction, 1))) {
                    echo "<td>" . $tradeInfo[2] . " BTC x</td><td>$" . $tradeInfo[3] . "</td><td>$" . ($tradeInfo[2] * $tradeInfo[3]) . "</td>";
                    echo "<td><input type='button' value='Cancel Trade' onclick=\"document.getElementById('tcID').value='" . $tradeInfo[0] . "'; document.getElementById('tcTYPE').value='SELL'; document.getElementById('cancelForm').submit();\" /></td></tr>\n";
                  } else if($tradeInfo = GetTradeInfo($_SERVER['DOCUMENT_ROOT'] . "/data/sell.dat", substr($PendingTransaction, 1))) {
                    echo "<td>" . $tradeInfo[2] . " BTC x</td><td>$" . $tradeInfo[3] . "</td><td>$" . ($tradeInfo[2] * $tradeInfo[3]) . "</td>";
                    echo "<td><input type='button' value='Cancel Trade' onclick=\"document.getElementById('tcID').value='" . $tradeInfo[0] . "'; document.getElementById('tcTYPE').value='SELL'; document.getElementById('cancelForm').submit();\" /></td></tr>\n";
                  }
                } else {
                  echo "No trades in system.</td>\n";
                }
              }
              echo "</table>\n";
              echo "</div>\n";
            
              // 4) Pending / History section - display pending transactions FIRST, then a brief history of recent transactions.
              echo "<div class='infoBox' id='History'>\n";
              
              echo "  <p>Here's a history of all of your bitcoin transactions:</p>\n";
              $history = explode("-", $_SESSION['user']['ActivePortfolio']['History']);
              array_pop($history);
              echo "<table style='width: 100%'>\n";
              echo "<tr><td>Trade ID</td><td>Bitcoins in/out</td><td>USD in/out</td></tr>\n";
              foreach($history as $line) {
                $lineData = explode(",", $line);
                if("S" == $lineData[0][0]) {
                  // Sale
                  echo "<tr style='background-color: #FFFFC8;'><td>Sale #" . $lineData[0] . "</td><td>" . $lineData[1] . "</td><td>" . $lineData[2] . "</td></tr>";
                } else {
                  // Buy
                  echo "<tr style='background-color: #C8FFFF;'><td>Purchase #" . $lineData[0] . "</td><td>" . $lineData[1] . "</td><td>" . $lineData[2] . "</td></tr>";
                }
              }
              echo "</table>";
              echo "</div>\n";
            }
          }
          // 7) You'll want a non-invasive but visible indication of how much the computer predictions has made
          // 8) You'll want to include statistics (from willing users) on how much users on average have made.
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
