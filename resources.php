<?php session_start(); $bitAddress = "1ML4jCxcGL2KEzVCRabfkFMyphMkJF5376"; ?>

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
        <p>
          <i>&nbsp;&nbsp;&nbsp;&nbsp;This list is not intended to be considered exhaustive - instead, it is
          a list to be used as a starting point in finding resources for earning, trading, and spending
          bitcoins. Services with affiliate programs have BitTrade donation address included in URL.</i>
        </p>
        <table style='width: 100%; border: 1px solid black;'>
          <tr>
            <td width='20%'><b>Bitcoin Resource</b></td>
            <td width='22%'><b>Bitcoin Website</b></td>
            <td width='58%'><b>Description</b></td>
          </tr>
          <tr>
            <td>Bitcoin Get</td>
            <td><a href="http://www.bitcoinget.com/?r=<?php echo $bitAddress; ?>">www.bitcoinget.com</a></td>
            <td>
              Watch videos for BTC. Highly reccomend for a beginning Bitcoiner.
            </td>
          </tr>
          <tr>
            <td>Mt. Gox</td>
            <td><a href="https://www.mtgox.com/">www.mtgox.com</a></td>
            <td>
              Bitcoin trading service. Takes a 0.6% fee per transaction.<br />
              Very good, but transferring money can be tricky. I use it<br />
              hand-in-hand with coinbase.
            </td>
          </tr>
          <tr>
            <td>CoinBase</td>
            <td><a href="https://coinbase.com/?r=51cc6bb28952d315fe00000f">www.coinbase.com</a></td>
            <td>
              Coinbase is a online bitcoin wallet, which you can also use to buy/sell BTC via
              bank transfer. I highly reccomend it.
            </td>
          </tr>
          <tr>
            <td>50 BTC</td>
            <td><a href="https://50btc.com/">www.50btc.com</a></td>
            <td>
              50 BTC is a bitcoin mining pool. Back in the day, this was one of the primary
              ways that bitcoins were earned. Now, it's a lot harder so it's good fun, but
              hardly profitable unless you drop money on specialized hardware.
            </td>
          </tr>
          
        </table>

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
