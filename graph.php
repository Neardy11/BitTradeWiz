<?php session_start(); ?>

<html>
  <body onload='writeData();'>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
    // ------------GLOBAL VARIABLES-------------
    // An array with the first element being the name of the instruct, the rest values.
    var graphData = new Array();        // A 2-dimensional array holding all y-data sets.
    //-------------------------------------------
    
    //---KEYS--- (in order)
    // Time Data (assumed)
    // Price Data (PRICE)
    // Average Value Data (AVG)
    // Local Minima (LOCAL_MINIMA)
    // Local Maxima (LOCAL_MAXIMA)
    
    // First up, load our data sets:
    function loadDataSets(rawData) {
      console.log("Made it to the loadDataSets function");
      var linesArray = rawData.split("\n");
      
      // So, our first line is going to be an array with keys holding what data to load.
      //  For a guide, see the comment under ---KEYS---
      var keys = linesArray[0].split(",");
      for(var i = 0; i < keys.length; i++) {
        graphData[i] = new Array();
        graphData[i][0] = keys[i];
      }
      
      // Next: Go through the entire array, load the information into appropriate key (should be in order)
      for(var i = 1; i < linesArray.length; i++) {
        var lineData = linesArray[i].split(",");
        for(var j = 0; j < lineData.length; j++) {
          graphData[j][i] = parseFloat(lineData[j]);
        }
      }
      
      processData();
    }
    
    // This function is for the averages, standard deviations and such, and is included client-side for speed.
    function processData() {
      // Things to do in this function:
      // 0) "numVals" = DEBUG ONLY - number of values returned by parseData.php
      // 1) "avgVal" = average for timeframe (requires: price) (uses: total, numIndexes)
      // 2) "stdDev" = standard deviation for timeframe (requires: price) (uses: average, numIndexes)
      // 3) "currentPrice" = current price of bitcoin (requires: price) (uses: ___)
      // 4) "max_price_delta" = largest difference in prices (requires: price) (uses: min, max)
      // 5) "percent_price_delta" = percent difference (goes with max_price_delta) (requires: price) (uses: min, max)
      
      // numVals returned here
      document.getElementById("numVals").innerHTML = graphData[1].length-1;
      
      // avgVal calculated here
      var totalVal = 0; var numIndexes = 0; var avgVal = 0;
      var minimum = graphData[1][1]; var maximum = graphData[1][1];
      for(numIndexes = 0; numIndexes < graphData[1].length-1; numIndexes++) {
        totalVal += graphData[1][numIndexes+1];
        if(graphData[1][numIndexes+1] < minimum)
          minimum = graphData[1][numIndexes+1];
        if(graphData[1][numIndexes+1] > maximum)
          maximum = graphData[1][numIndexes+1];
      }
      avgVal = (totalVal / numIndexes);
      document.getElementById("avgVal").innerHTML = avgVal.toFixed(6);
      
      // stdDev calculated here
      var totalVariance = 0; var standardDeviation = 0;
      for(var i = 0; i < graphData[1].length-1; i++) {
        var lineVariance = Math.abs(graphData[1][i+1] - avgVal);
        totalVariance += (lineVariance * lineVariance);
      }
      standardDeviation = (totalVariance / (numIndexes - 1));
      standardDeviation = Math.sqrt(standardDeviation);
      document.getElementById("stdDev").innerHTML = standardDeviation.toFixed(6);
      
      // currentPrice fetched here
      document.getElementById("currentPrice").innerHTML = graphData[1][numIndexes].toFixed(6);
      
      // max_price_delta calculated here
      var max_price_delta = maximum - minimum;
      var percent_price_delta = (maximum - minimum) / minimum;
      document.getElementById("max_price_delta").innerHTML = max_price_delta.toFixed(4);
      document.getElementById("percent_price_delta").innerHTML = "(" + (percent_price_delta * 100).toFixed(4) + "%)";
    }
    
    // Load the Visualization API library and the piechart library.
    google.load('visualization', '1.0', {'packages':['corechart']});
    google.setOnLoadCallback(drawChart);
    // ... draw the chart...
    
    function drawChart() {
      if(graphData.length <= 1) {
        console.log("Error - no data to draw from! " + graphData.length);
        return;
      }
      
      // Create the data table
      var data = new google.visualization.DataTable();
      // Create our y-var set...
      for(var i = 0; i < graphData.length; i++) {
        data.addColumn('number', graphData[i][0]);
      }
      // Load data into our graph...
      for(var i = 1; i < graphData[0].length-1; i++) {
        var inputArray = new Array();
        // So, our input array is going to be from each of graphData [x] values, on the [y] level.
        for(var j = 0; j < graphData.length; j++) {
          inputArray[j] = parseFloat(graphData[j][i]);
        }
        data.addRows([inputArray]);
      }
      
      // Set chart options...
      var options = {'title':'Bitcoin (BTC) / United States Dollar (USD) Exchange Rate',
                     'width':670,
                     'height':300};
                     
      // Instantiate and draw our chart
      var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
      chart.draw(data, options);
    }
    </script>
       <input type='button' value='Load Data' onclick="writeData();">
        <div id="chart_div" style="width: 670; height: 300;"></div>
<!-----------Data Entering Fields Here.------------>
        <table style="border:2px solid black; width:100%;">
          <tr>
            <td>
              <input type="checkbox" id="b_price" name="ops" value="price" checked>Display Price<br />
            </td>
            <td>
              <input type="checkbox" id="b_avg_long_val" name="ops" value="avg_val" checked>Display Long Average Value<br />
            </td>
            <td>
              <input type="checkbox" id="b_avg_short_val" name="ops" value="avg_short" checked>Display Short Average Value<br />
            </td>
          </tr>
          <tr>
            <td>
              <input type="checkbox" id="b_local_min" value="local_min">Display Local Minima Line<br />
            </td>
            <td>
              <input type="checkbox" id="b_local_max" value="local_max">Display Local Maxima Line<br />
            </td>
          </tr>
          <tr>
            <td>
              Timeframe:
            </td>
            <td>
              <select name="timeframe" id="tf">
                <option value="1800">30 Minutes</option>
                <option value="7200" selected>2 Hours</option>
                <option value="43200">12 Hours</option>
                <option value="259200">3 Days</option>
                <option value="604800">1 Week</option>
                <option value="2635200">1 Month</option>
                <option value="7905600">3 Months</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>
              Resolution:
            </td>
            <td>
              <select name="resolution" id="res">
                <option value="50">Extra Low</option>
                <option value="150">Low</option>
                <option value="500" selected>Medium</option>
                <option value="1500">High</option>
                <option value="4500">Very High</option>
                <option value="9000">Very Very High</option>
                <option value="25000">Not enough pixels!</option>
              </select>
            </td>
          </tr>
        </table>
        
<!-------Numerical Statistics---------->
        <table>
          <tr>
            <td>
              Number of Entries (DEBUG ONLY):
            </td>
            <td id="numVals">
              ___
            </td>
          </tr>
          <tr>
            <td>
              Average Value:
            </td>
            <td id="avgVal">
              ___
            </td>
          </tr>
          <tr>
            <td>
              Standard Deviation:
            </td>
            <td id="stdDev">
              ___
            </td>
          </tr>
          <tr>
            <td>
              Current Price:
            </td>
            <td id="currentPrice">
              ___
            </td>
          </tr>
          <tr>
            <td>
              Maximum Price Delta:
            </td>
            <td id="max_price_delta">
              ___
            </td>
            <td id="percent_price_delta">
              (___)
            </td>
          </tr>
          <tr>
            <td>
              Maximum Profit Possible:
            </td>
            <td id="max_profit_possible">
              N/A - requires GOOD local min/max data.
            </td>
            <td id="mpp_trade_count">
              (N/A)
            </td>
          </tr>
        </table>
        <script type="text/javascript">
          // So at this point, you now have a toPass variable that needs to be fed to a PHP write script?
          var xmlhttp;
          if(window.XMLHttpRequest) {
            xmlhttp = new XMLHttpRequest();
          } else {
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
          }

          xmlhttp.onreadystatechange=function() {
            if (xmlhttp.readyState==4 && xmlhttp.status==200) {
              // We have a response!!! in xmlhttp.responseText
              // TODO: Add Error Handling
              loadDataSets(xmlhttp.responseText);
              drawChart();
              
              console.log(xmlhttp.responseText);
            }
          }

          function writeData() {
            // First, clear all global variables...
            graphData = new Array();
          
            xmlhttp.open("POST", "/Arbitrage/parseData.php", true);
            xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            var sendString = "EMPTY=very";
            if(true == document.getElementById("b_price").checked)
              sendString += "&PRICE=y";
            if(true == document.getElementById("b_avg_long_val").checked)
              sendString += "&AVG_LONG=y";
            if(true == document.getElementById("b_avg_short_val").checked)
              sendString += "&AVG_SHORT=y";
            if(true == document.getElementById("b_local_min").checked)
              sendString += "&LOCAL_MINIMA=y";
            if(true == document.getElementById("b_local_max").checked)
              sendString += "&LOCAL_MAXIMA=y";
            // Timeframe?
            sendString += ("&TIME=" + document.getElementById("tf").value);
            // Resolution?
            sendString += ("&RES=" + document.getElementById("res").value);
            xmlhttp.send(sendString);
          }
        </script>
      </div>
  </body>
</html>
