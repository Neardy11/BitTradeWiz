<?php 
//  $bitAddress = "1LMZTbFCcyCs1rv7g9cfuXaZDctv9XjSKU";
  $bitAddress = "1ML4jCxcGL2KEzVCRabfkFMyphMkJF5376";
?>
<?php
if(isset($_SESSION['user'])) {
  if(!isset($_SESSION['user']['Balance'])) $_SESSION['user']['Balance'] = 0;
  echo "<p>Your Account Balance:<br />" . $_SESSION['user']['Total_Balance'] . " BTC<br /></p>";

  if(isset($_SESSION['user']['PageViews'])) {
    $_SESSION['user']['PageViews']++;
  } else {
    $_SESSION['user']['PageViews'] = 0;
  }
  echo "Page Views for this user: " . $_SESSION['user']['PageViews'] . "\n<br />";
}
?>

<b>Earn More BTC</b><br />
<a href="http://www.bitcoinget.com/?r=<?php echo $bitAddress; ?>" target="_blank">BitCoinGet.com</a> (earn BTC) <br />
<a href="http://www.bitvisitor.com/?ref=<?php echo $bitAddress; ?>" target="_blank">BitVisitor.com</a> (earn BTC) <br />
<a href="https://www.mtgox.com/" target="_blank">MtGox.com</a> (trade USD/BTC)<br />

<script type="text/javascript"><!--
google_ad_client = "ca-pub-4684638037289460";
/* BitWizards */
google_ad_slot = "2453485572";
google_ad_width = 160;
google_ad_height = 600;
//-->
</script>
<script type="text/javascript"
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
