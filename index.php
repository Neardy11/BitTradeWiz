<?php session_start(); include_once($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php"); ?>

<!DOCTYPE html>
<html>
  <head>
    <title>BitWizard.com - Bitcoin Investment Training, Practice, and Planning</title>
    <link rel="stylesheet" type="text/css" href="./style.css">
  </head>
  <body>
    <div id="wrap">
      <div id="header"><?php include($_SERVER['DOCUMENT_ROOT'] . "/HEADER.php"); ?></div>
      <div id="nav">
        <?php include($_SERVER['DOCUMENT_ROOT'] . "/NAVBAR.php"); ?>
      </div>
      <div id="main">
        <h2>BitWizard.com - Your Personal Bitcoin Investment Wizard</h2>
        <h3>Running as <?php echo `whoami`; ?></h3><br />
        <h3>Current Bitcoin Price: <?php echo getCurrentBTCPrice(); ?></h3><br />
        <p>Image of Site in Use HERE</p>
        <p>The BitWizard is here to help you with all of your bitcoin investing needs,
           including:</p>
        <ol>
          <li>Bitcoin Price Charts and Analysis Tools</li>
          <li>Investment Strategy Descriptions</li>
          <li>Practice Investing Environment</li>
          <li>Investment Strategy Wizard (tools, calculators, suggestions, etc.)</li>
        </ol>
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
