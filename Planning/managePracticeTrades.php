<?php
$rotatingSecurityKey = "monkeybadgerorionswordthing";
$rotatingSecurityValue = 42;

if(!isset($_GET[$rotatingSecurityKey]) || ($_GET[$rotatingSecurityKey] != $rotatingSecurityValue)) {
  header("Location: /index.php");
}
?>

<?php session_start(); include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
  $errMessage = "";
  
  if(!isset($_SESSION['buy_high'])) {
    $_SESSION['buy_high'] = 1;
    $errMessage .= "Created buy_high variable<br />\n";
  } else {
    echo "Current Buy High Value: " . $_SESSION['buy_high'] . "<br />\n";
  }
  if(!isset($_SESSION['sell_low'])) {
    $_SESSION['sell_low'] = 1000 * $DIV_BY_AMOUNT;
    $errMessage .= "Created sell_low variable<br />\n";
  } else {
    echo "Current Sell Low Value: " . $_SESSION['sell_low'] . "<br />\n";
  }

echo "<br />\nERRORS: $errMessage";
?>
<!-- (array_shift removes first element, array_push puts one on end.

managePracticeTrades.php and related files

This is supposed to work very much like getMarketData.php TODO and even eventually be incorporated into one single
webDataManage.php that will be run continuously on the server, or on a client away from the server, but either way 24/7.

This goes through all of the pending trades, and performs the trades which qualify for completion.

There will be several parts to this. Is there any way to have some operations done client-side?
--Databases:
  +buy.dat: List of all sorted buy offers, ranging from highest offer first to lowest offer last
    ~Line structure: BUY_ID,PRACTICE_ACCOUNT_ID,BUY_AMOUNT,BUY_PRICE
  +sell.dat: List of all sorted sell offers, ranging from lowest offer first to highest offer last.
    ~Line structure: SELL_ID,PRACTICE_ACCOUNT_ID,SELL_AMOUNT,SELL_PRICE
  +buy_posted.dat: List of UNSORTED buy offers, put in as the user inputs them off-page. LAST LINE is always alone, only the next ID
  +sell_posted.dat: List of UNSORTED sell offers, put in as the user inputs them off-page. LAST LINE is always alone, only the next ID
  +latency.dat: List of operations performed and latency, for design purposes (If they get too long, like 45 seconds... problem)
    ~Line structure: INPUT_TIME,LATENCY
  +nextIdValues.dat: 2 values only, the next BUY ID and the next SELL ID, separated by a "\n"

--Server Variables:
  +arr_Bitcoin_Price: Array containing FIFO list of bitcoin price updates.
  +buy_high: Highest bitcoin buy offer existing in database.
  +sell_low: Lowest bitcoin sell offer existing in database.
  +operation_queue: Array containing FIFO list of operations to perform on-page.
  
Off-Page Flow:
  getMarketData.php: Update SERVER arr_Bitcoin_Price - add a bitcoin price to END of list when received from socket
                   : Upon bitcoin price update, look in operation_queue for a TRADE_CHECK - if none exists, add one to end.
  bitcoin_trade.php: When user creates a BUY trade, append it to end of buy_posted.dat. If doesn't already exist, add a BUY_SORT to operation_queue.
                   : When user creates a SELL trade, append it to end of sell_posted.dat. If doesn't already exist, add a SELL_SORT to operation_queue.
  
On-Page Flow (Javascript - sent to updateData.php via AJAX)
  1) Pop first value from operation_queue
    a) If NULL, pop again after 30 seconds (setTimeout(Execute_Next, 30000))
    b) If BUY_SORT or SELL_SORT, perform a BUY_SORT or SELL_SORT.
    c) If TRADE_CHECK, perform a TRADE_CHECK
    d) If BUY{id} or SELL{id}, perform a TRADE on Trade ID #id
    e) If CHECK_LATENCY{time}, perform a CHECK_LATENCY
  2) Upon user input, add a CHECK_LATENCY event.
    
  BUY_SORT and SELL_SORT) Input buy/sell.dat and buy_posted/sell_posted.dat, and perform a sort to put them in order.
    a) BUY is sorted from highest to lowest, SELL from lowest to highest.
    b) Export all information to buy.dat/sell.dat
    c) Export highest buy price to buy_high, and lowest sell price to sell_low.
  
  TRADE_CHECK) Check for any and all trades that need to be performed, and perform them! Do until arr_Bitcoin_Price is empty (don't include price updates that happen while checking for trades)
    a) If buy_high is lower than arr_Bitcoin_Price current price, skip (b)-(d)
    b) Go through buy.dat, and input all trades down to current price to an array, perform_buys.
    c) Add to operation_queue FIFO style TRADE{id} elements for each buy that needs to be performed.
    d) Update buy_high variable to reflect the new highest buy price.
    e) If sell_low is higher than arr_Bitcoin_Price current price, skip (f)-(h)
    f) Go through sell.dat, and input all trades up to current price to an array, perform_sells.
    g) Add to operation_queue FIFO style TRADE{id} elements for each sell that needs to be performed.
    h) Update sell_low variable to reflect the new lowest sell price.
    i) Move on to next arr_Bitcoin_Price price, repeat (a)-(h) until list is gone OR 30 seconds have elapsed.
    
  BUY{id} or SELL{id}) Perform trades that need to be done.
    a) Find trade in buy/sell.dat.
    b) Verify using SQL that the practice account has sufficient funds to perform trade. If not, remove trade and exit (with err message)
    c) Adjust amounts in SQL (add USD and remove BTC for sell, vice versa for buy)
    d) Remove the trade from buy/sell.dat
    
  CHECK_LATENCY{time}
    a) Add to latency.dat a line containing information about how long it took to get to this check (input time is in brackets)

-->

<!DOCTYPE html>
<html>
  <head>
    <title>BitcoinTrader Management System 1.0.0</title>
  </head>
  <body>
    <script>
    
      function AJAX_Request(type, extra_data) {
        // We'll need to use AJAX to get our information
        // Pretty much, request from dataMunch.php that we get the next bit from the operation_queue.
        var xmlhttp;
        if(window.XMLHttpRequest) {
          xmlhttp = new XMLHttpRequest();
        } else {
          xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
        xmlhttp.open("POST","dataMunch.php",true);
        xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        
        switch(type) {
        case "Get_Next_Operation":
          xmlhttp.send("request=get_next_operation");
          break;
        case "Fill_Queue":
          xmlhttp.send("request=fill_queue");
          break;
        case "Check_Latency":
          xmlhttp.send("request=check_latency");
          break;
        
        // Here are our actual operations...
        case "LATENCY_CHECK":
          xmlhttp.send("request=LATENCY_CHECK&data=" + extra_data);
          break;
        case "BUY_SORT":
          xmlhttp.send("request=BUY_SORT");
          break;
        case "SELL_SORT":
          xmlhttp.send("request=SELL_SORT");
          break;
        case "TRADE_CHECK":
          xmlhttp.send("request=TRADE_CHECK");
          break;
        case "BUY":
          xmlhttp.send("request=BUY&data=" + extra_data);
          break;
        case "SELL":
          xmlhttp.send("request=SELL&data=" + extra_data);
          break;
        default:
          xmlhttp.send("request=null");
          break;
        }
        
        xmlhttp.onreadystatechange = function() {
          HandleResponse(type, xmlhttp.readyState, xmlhttp.responseText);
        }
      }
      
      // This function handles the XML response
      function HandleResponse(request_type, status, value) {
        if(4 == status) {
          switch(request_type) {
          case "Get_Next_Operation":
            // You've here been in value fed a new operation - execute it!
            document.getElementById("outLabel").innerHTML = "NEXT OPERATION: " + value;
            Execute_Operation(value);
            break;
          case "Fill_Queue":
            if("success" != value) {
              document.getElementById("outLabel").innerHTML = "Failed to fill the queue!";
            } else {
              document.getElementById("outLabel").innerHTML = "Queue fill success!";
            }
            break;
          case "Check_Latency":
            if("success" != value) {
              document.getElementById("outLabel").innerHTML = "Failed to add a check_latency event!";
            } else {
              document.getElementById("outLabel").innerHTML = "Check_latency event successfully added!";
            }
            break;
          case "LATENCY_CHECK":
            if("success" != value) {
              document.getElementById("outLabel").innerHTML = "Failed to perform latency check";
            } else {
              document.getElementById("outLabel").innerHTML = "Latency check success!";
            }
            break;
          case "BUY_SORT":
            document.getElementById("outLabel").innerHTML = "BUY_SORT\n" + value;
            break;
          case "SELL_SORT":
            document.getElementById("outLabel").innerHTML = "SELL_SORT\n" + value;
            break;
          case "TRADE_CHECK":
            document.getElementById("outLabel").innerHTML = "TRADE_CHECK\n" + value;
            break;
          case "BUY":
            document.getElementById("outLabel").innerHTML = "BUY\n" + value;
            break;
          case "SELL":
            document.getElementById("outLabel").innerHTML = "SELL\n" + value;
            break;
          default:
            document.getElementById("outLabel").innerHTML = "SWITCH BROKEN";
            break;
          }
        }
      }
      
      // Name: Execute Operation
      // Summary: Input an operation name, and perform said operation.
      function Execute_Operation(operation_name) {
        // Can't use a switch statement here, because some variables are passed.
        if(("NULL" == operation_name) || ("" == operation_name)) {
          document.getElementById('outLabel').innerHTML += 'WAIT\n';
        } else if ("SELL_SORT" == operation_name) {
          AJAX_Request('SELL_SORT');
        } else if ("BUY_SORT" == operation_name) {
          AJAX_Request('BUY_SORT');
        } else if ("TRADE_CHECK" == operation_name) {
          AJAX_Request('TRADE_CHECK');
        } else if (-1 != operation_name.indexOf("BUY{")) {
          var id = operation_name.substring(operation_name.indexOf("{"));
          var id = id.replace("{", "");
          var id = id.replace("}", "");
          AJAX_Request('BUY', id);
        } else if (-1 != operation_name.indexOf("SELL{")) {
          var id = operation_name.substring(operation_name.indexOf("{"));
          var id = id.replace("{", "");
          var id = id.replace("}", "");
          AJAX_Request('SELL', id);
        } else if (-1 != operation_name.indexOf("LATENCY_CHECK{")) {
          // Check the latency here... Send our function with the time given in the operation, too.
          var id = operation_name.substring(operation_name.indexOf("{"));
          var id = id.replace("{", "");
          var id = id.replace("}", "");
          
          AJAX_Request('LATENCY_CHECK', id);
        } else {
          alert("Error happened! We don't know what to execute!");
        }
      }
      
      // Now this bit is our running queue
      function ProcessLoop() {
        document.getElementById("bRunning").value = true;
        var clear = setInterval(function(){AJAX_Request('Get_Next_Operation'); if(document.getElementById("bRunning").value=="false") {clearInterval(clear); alert("STOPPED");}}, 2500);
      }
    </script>
    <input type='button' id='bRunning' value='START GOING' onclick="ProcessLoop();" />
    <input type='button' value='Fill Operation Queue' onclick="AJAX_Request('Fill_Queue');" />
    <input type='button' value='Check Latency' onclick="AJAX_Request('Check_Latency');" />
    <input type='button' id='Start' value='END GOING' onclick="document.getElementById('bRunning').value = 'false';" />
    <p id='outLabel'>This is where what's going on is put.</p>
  </body>
</html>
