<!DOCTYPE html>

<!--By the way... this is ONLY to be run on SECURE COMPUTERS.
      As it stands right now, this is a HACKERS DREAM. Super un-secure!
      
      You may want to consider re-writing it somehow. When you know how
      to do that. Which hopefully will be soon...
      
      Or, just have this file only accessable to server, and have it (the
      server) running this program all the time. As per original plan.-->

<html>
  <head>
    <title>Market Data Upload Page</title>
    <link rel="stylesheet" type="text/css" href="/style.css">
  </head>
  <body>
    <div id="wrap">
      <div id="header"><?php include($_SERVER['DOCUMENT_ROOT'] . "/HEADER.php"); ?></div>
      <div id="nav">
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/NAVBAR.php"); ?>
      </div>
      <div id="main">
        <h1>Market Data Collector</h1>
        <p>TODO: Only one connection at a time, and only on secure and trusted connections</p>
        <p>Also, hide save.php</p>
        <a href="#" onclick="enabled=true; console.log('Enabled');">Enable</a>
        <a href="#" onclick="enabled=false; console.log('Disabled');">Disable</a>
        <a href="#" onclick="cleanup(); console.log('Cleaning Up Database');">Cleanup Database</a>
        <table>
          <tr>
            <td>
              Last Message:
            </td>
            <td id="message">
              NULL
            </td>
            <td id="time">
              NULL
            </td>
          </tr>
          <tr>
            <td>
              Trades Logged:
            </td>
            <td id="trades_logged">
              0
            </td>
          </tr>
        </table>
        <script src="https://socketio.mtgox.com/socket.io/socket.io.js"></script>
        <script>
        var conn = io.connect('https://socketio.mtgox.com/mtgox');

        var enabled = true;
        var toPass = "";

        function onConnect() {
          console.log("Connection Success!");
        }
        
        // Have a queue here...
        var tradeTime = new Array();
        var tradePrice = new Array();
        var tradesLogged = 0;
        function onReceive(data) {
          // TODO: This really needs to be handled in a queue, instead of passing like such. You loose trades this way.
          // Other Things:
          var d = new Date();
          if(data.op == "private" && enabled == true) {
            if(data.private == "ticker") {
              //toPass += "TICK-" + d.getHours() + ":" + d.getMinutes() + ":" + d.getSeconds() + "-" + data.ticker.last.value_int;
              //console.log("Ticker Value: " + data.ticker.last.value_int);
              //writeData();
            } else if(data.private == "trade") {
              // Check: is mixed_currency part of the trade? if so, flag and don't process.
              if((data.trade.properties.indexOf("mixed_currency") == -1) && (data.trade.price_currency == "USD")) {
                toPass += "TRADE-" + data.trade.date + "-" + data.trade.price_int + "-" + data.trade.amount_int;
                console.log(toPass + " --- PROPERTIES: " + data.trade.price_currency + data.trade.trade_type + "\n");
                document.getElementById("message").innerHTML = toPass;
                var d = new Date();
                tradesLogged++;
                document.getElementById("time").innerHTML = d.toUTCString();
                document.getElementById("trades_logged").innerHTML = tradesLogged;
                writeData();
              } else {
                console.log("Ha-HA! Rogue value detected! Price: " + data.trade.price_int + " " + data.trade.price_currency + "\n");
              }
            }
          }
        }

        conn.on('connect', onConnect());
        conn.on('message', function (data) {onReceive(data);});

        // So at this point, you now have a toPass variable that needs to be fed to a PHP write script?
        var xmlhttp;
        if(window.XMLHttpRequest) {
          xmlhttp = new XMLHttpRequest();
        } else {
          xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }

        xmlhttp.onreadystatechange=function() {
          if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            console.log("Return State Returned: " + xmlhttp.responseText);
          }
        }

        function writeData() {
          xmlhttp.open("POST", "save.php", true);
          xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xmlhttp.send("dataSend=true&value=" + toPass);
          toPass = "";
        }
        
        function cleanup() {
          xmlhttp.open("POST", "save.php", true);
          xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xmlhttp.send("dataSend=cleanup");
        }

        </script>
        
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
