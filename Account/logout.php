<?php session_start(); ?>
<?php
  // Pretty much just unset the $_SESSION['user'] array, which holds... other arrays.
  global $outMessage;
  $outMessage = "";
  if(isset($_SESSION['user']) && isset($_POST['returnURL'])) {
    unset($_SESSION['user']);
    header("Location: " . $_POST['returnURL']);
  } else {
    $outMessage .= "Logout Failed Somehow!\n";
  }
?>

<html>
  <?php echo $outMessage;?>
</html>
