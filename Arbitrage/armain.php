<?php session_start(); ?>

<html>
  <head>
    <title>Strictly Chart Testing</title>
    <link rel="stylesheet" type="text/css" href="/style.css">
  </head>
  <body>
    <div id="wrap">
      <div id="header"><?php include($_SERVER['DOCUMENT_ROOT'] . "/HEADER.php"); ?></div>
      <div id="nav"><?php include($_SERVER['DOCUMENT_ROOT'] . "/NAVBAR.php"); ?></div>
      <div id="main">
        <!-- Nice and easy. Simply include the graph from the other page. -->
        <iframe src="/graph.php" width="100%" height="100%" seamless>Your browser does not support iframes!</iframe>
      </div>
      <div id="sidebar"><?php include($_SERVER['DOCUMENT_ROOT'] . "/SIDEBAR.php"); ?></div>
      <div id="footer"><?php include($_SERVER['DOCUMENT_ROOT'] . "/FOOTER.php"); ?></div>
    </div>
  </body>
</html>
