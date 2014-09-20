<?php session_start(); ?>
<!--FAQ.php - this is where all of the users frequently asked questions go.-->
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
        <h1 id="q_UTB">What's all this about an 'non-transferrable balance'?</h1>
        <p>A lot of the features of this website require a user balance, and thus 'cost money.' However,
        the idea of this website is not to extort or charge you for using it's services, I can remain quite
        afloat without that. So for a lot of things I will give out account credit - for example, at registration
        every account receives 0.1 BTC (which, at the time of the creation of this site, was $10 USD). But I
        can't have you just taking that money and running with it! It's for use <a href="#q_CMN">creating
        practice accounts</a>. All bitcoins rewarded on site are transferrable, and can be withdrawn at any
        time so long as you've matched a certain minimum balance.</p>
        
        <h1 id="q_CMN">How come practice accounts cost 0.02 BTC to open? Why do I have to pay for practice money?</h1>
        <p>Well, that's what the <a href="#q_UTB">free account bitcoins</a> are for - you can either create
        five practice accounts, each with a minimum balance to try out different investment strategies,
        or you can open one or two to go "all-in" with a lot of practice money. Really, you should never have
        to pay for anything on this site for full functionality. The costs serve as incentives so that you do not
        open a billion practice accounts, load them each chuck full of money and miss the whole purpose of this
        site. After all, the whole reason I made this sucker was so that you could learn to make money on your
        own, and then go out and do it!</p>
      
        <h1 id="q_PW1">Why are passwords not required on accounts?</h1>
        <p>Due to the nature of Bitcoins, none of the information here is very sensitive, so in theory
        you shouldn't need a password. This is because a <a href="http://www.catb.org/jargon/html/C/cracker.html" target="_blank">
        cracker</a> has little/no gain by <a href="http://www.catb.org/jargon/html/C/cracker.html" target="_blank">
        cracking</a> your account. However, passwords can be set at any time (during or after
        registration) to protect against unwanted users, like malicious roomates or meddling children,
        and will be asked for after entering your bitcoin address. <b>Passwords are encrypted</b>,
        and <b>I still have protected this site</b> against hackers.</p>
        
        <h1 id="q_vai">What is the 'Value Increase' for?</h1>
        <p>In the development of this website, one thing I found quite helpful for myself to monitor was
        how much money I had actually made performing trades - this is recorded by the 'Value Increase' field.
        This also is part of BitWizard's <a href='#' target="_blank">incentive program</a>, where you can make money on this
        site! After you've pulled a 10% profit (10% value increase), you can at any time cash in your accumulated
        increase for real BTC, which you can then turn around and spend or invest in the <a href='https://www.mtgox.com' target="_blank">
        real world</a>. The amount you make will vary, but you can make much more if your practice account information
        is <a href='#q_sha'>shared</a>.</p>
        
        <h1 id='q_sha'>What does it mean if my practice account is 'Shared'?</h1>
        <p>This means that you are releasing the historical data of your practice trades and their amounts with
        BitWizards.com. No user information (bitcoin address, etc.) is stored with that. This information is
        only used for research purposes - identifying successful strategies, trading trends, etc. If the universe
        decides to throw me one giant bone, it will be run through a neural network to try and find patterns of
        successful traders. Don't worry, if that happens, any findings will be posted on this site.</p>
        
        <h1 id='q_irt'>What are the current Value Increase Incentive rates?</h1>
        <p>10% is worth <?php include($_SERVER['DOCUMENT_ROOT'] . "/sql_includes.php");
        printf ("%05.6f in a 'Shared' account, and %05.6f BTC in an unshared one.", $SHARED_INCENTIVE, $UNSHARED_INCENTIVE);
        echo " Negative amounts don't result in any detriment to your account balance, so it would be a good";
        echo " idea to redeem\nany negative Value Increase value seen.\n";
         ?>
        </p>
        
        <h1 id='q_ltm'>Why do the trades take a long time, even if I post them at market price?</h1>
        <p>This website attempts to simulate to a fair degree the actions of the actual market - so unless you post
        a buy well above or a sell well below the market price, the system will wait until an actual trade occurs
        before completing your trade. If you post at least 10% above or below, the trade will complete instantly.</p>
        
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
