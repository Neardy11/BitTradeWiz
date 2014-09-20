<?php

// Here are our keys. Important to notice that they will be passed in $_POST[...] variables
//  under the same name - for instance, $_POST['PRICE'] will be passed when price is desired to be known.
//  Also note that "TIME" is always assumed included, as it is our independent variable.
array($dataOptions);
// $dataOptions[-1] = "TIME";   // UNIX timestamp of when this piece of data is logged.
$key = 0;
$dataOptions[$key++] = "PRICE";      // Price of bitcoin at this datapoint.
$dataOptions[$key++] = "AVG_LONG";        // Average of 1/4 range bitcoin transactions
$dataOptions[$key++] = "AVG_SHORT";  // Average of last 35 bitcoin transactions
$dataOptions[$key++] = "LOCAL_MINIMA";  // Local minima, and in between, a constant-sloped line.
$dataOptions[$key++] = "LOCAL_MAXIMA";  // Local maxima, and in between, a constant-sloped line.

// Receive an AJAX request, and send back appropriate information
//  Output: Bitcoin Value and Time Information

// Step One: Open File.
$dataFileName = $_SERVER['DOCUMENT_ROOT'] . "/database.dat";
$data = file_get_contents($dataFileName);

// Get the individual lines...
$dataLines = explode("\n", $data);

// Find our time frame start. Our end (for now) is always the current time.
$startAt = time() - $_POST['TIME'];

// Parse data containing lines into arrays containing data?
array($dataTimeStamp); array($dataPrice); array($dataVolume);
$j = 0;
for($i = 0; isset($dataLines[$i + 1]); $i++) {
  $lineData = explode("-", $dataLines[$i]);
  
  // If the time is still too early, skip this one.
  if($lineData[1] < $startAt) {
    continue;
  }
  
  $dataTimeStamp[$j] = $lineData[1];
  
  $dataPrice[$j] = $lineData[2] / 100000; $dataVolume[$j] = $lineData[3] / 100000000;
  $j++;
}

// Resolution - we have ALL of our values, do not return more than $res values
if(isset($_POST['RES'])) {
  $res = $_POST['RES'];
} else {
  $res = 900; // Change when you find a good balance between speed and quality
}
if(count($dataPrice) > $res) {
  // So, to return $res values, we need to...
  // 1) Find out what (totalValues / $res) is - giving us how many values per block
  $blockSize = (count($dataPrice) / $res);

  // 2) Sort the entire list of values into blocks (thus eliminating the "remainder" problem)
  array($blocks_price);
  array($blocks_time);
  array($blocks_volume);
  $nextBlockStart = 0;
  $blockCount = 0;
  while($nextBlockStart < count($dataPrice)) {
    $thisBlockSize = 0;
    $blockSum = 0;
    $blockTime = 0;
    for($i = $nextBlockStart; ($i < ($nextBlockStart + $blockSize)) && ($i < count($dataPrice)); $i++) {
      // Notice: This FOR loop allows for floats and integers to mix. This is why we're using blocks.
      $thisBlockSize++;
      $blockSum += $dataPrice[$i];
      $blockTime += $dataTimeStamp[$i];
      $blockVolume += $dataVolume[$i];
    }

    // 3) Find the average value for each of these blocks
    $blocks_price[$blockCount] = ($blockSum / $thisBlockSize);
    $blocks_time[$blockCount] = ($blockTime / $thisBlockSize);
    $blocks_volume[$blockCount++] = ($blockVolume / $thisBlockSize);
    
    $nextBlockStart += $blockSize;
  }
  
  // 4) Return this list in lieu of the old list.
  if(($blockCount >= ($res * 0.95)) && ($blockCount <= ($res * 1.05))) {
    $dataPrice = $blocks_price;
    $dataTimeStamp = $blocks_time;
    $dataVolume = $blocks_volume;
  } else {
    echo "NOPE WRONG FALSE ERROR CRAP DANG IT";
  }
}

// So, now our data is loaded. Spit it back out to the requesting page:
//----------------Spitting Function - at top include required math stuff---------------
$minValue = $dataPrice[0]; // Used in computing MIN_BACON
$totalValue = 0; // Used in computing averages.
$functionalTotalValue = 0; // Used in computing averages.
$shortWindowValue = 0;  // Used in computing short average.
//------------------------------------------------------------------------------------
// Send across our key holding what values we're sending...
echo "TIME";
foreach($dataOptions as $key) {
  if($_POST[$key]=='y')
    echo "," . $key;
}
echo "\n";

// Now, if we have selected to put in local minima or maxima, we'll also need to
//  find those before we even run this function...
if('y' == $_POST['LOCAL_MINIMA'] || 'y' == $_POST['LOCAL_MAXIMA']) {
  // Go through the entire data set! Use deltas: -/+ means minima, +/- means maxima
  array($localMinima); array($localMaxima); array($iMin);
  $iMin = 0; $iMax = 0;
  for($i = 1; $i < (count($dataPrice) - 1); $i++) {
    if((($dataPrice[$i-1] - $dataPrice[$i]) > 0) && (($dataPrice[$i+1] - $dataPrice[$i]) >= 0)) {
      // Local Minima Found
      while($iMin < $i) {
        $localMinima[$iMin++] = $dataPrice[$i];
      }
    } else if((($dataPrice[$i-1] - $dataPrice[$i]) <= 0) && (($dataPrice[$i+1] - $dataPrice[$i]) < 0)) {
      // Local Maxima Found
      while($iMax < $i) {
        $localMaxima[$iMax++] = $dataPrice[$i];
      }
    }
  }
  
  // Fill in remainder of the values...
  for($i = count($localMinima); $i < count($dataPrice); $i++) {
    // WARNING: This assumes that at least one local minima has been found.
    $localMinima[$i] = $localMinima[$i - 1];
  }
  
  for($i = count($localMaxima); $i < count($dataPrice); $i++) {
    // WARNING: This assumes that at least one local maxima has been found.
    $localMaxima[$i] = $localMaxima[$i - 1];
  }
  // Later, you'll want to actually smooth this out. That's for V1.2.0
}

// Spit out the rest of our information!
$quarterValues = intval((count($dataPrice)) / 4);
for($i = 0; isset($dataPrice[$i]); $i++) {
  // Return format: "Time, Other operations in order of define above"

  // Time Data - holds the number of seconds since first trade in period.
  $timeData = $dataTimeStamp[$i] - $dataTimeStamp[0];
  if($dataPrice[$i] < $minValue)
    $minValue = $dataPrice[$i];

  if($_POST['AVG_LONG']=='y') {
    $functionalTotalValue += $dataPrice[$i];
    if($i >= $quarterValues) {
      $functionalTotalValue -= $dataPrice[$i - $quarterValues];
    }
  }
  if($_POST['AVG_SHORT']=='y') {
    $shortWindowValue += $dataPrice[$i];
    if($i >= 15) {
      $shortWindowValue -= $dataPrice[$i - 35];
    }
  }
  
  // Output info:
  echo $timeData;
  if($_POST['PRICE']=='y') {
    echo "," . $dataPrice[$i];
  }
  if($_POST['AVG_LONG']=='y') {
    if($i < $quarterValues) {  
      echo "," . ($functionalTotalValue / ($i+1));
    } else {
      echo "," . ($functionalTotalValue / $quarterValues);
    }
  }
  if($_POST['AVG_SHORT']=='y') {
    if($i < 35) {
      echo "," . ($shortWindowValue / ($i + 1));
    } else {
      echo "," . ($shortWindowValue / 35);
    }
  }
  if($_POST['LOCAL_MINIMA']=='y') {
    echo "," . $localMinima[$i];
  }
  if($_POST['LOCAL_MAXIMA']=='y') {
    echo "," . $localMaxima[$i];
  }
  echo "\n";
}
?>
