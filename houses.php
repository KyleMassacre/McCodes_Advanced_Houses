<?php
include_once('globals.php');
switch ($_GET['page'])
 {
   case 'move': move_house(); break;
   case 'move_out': move_out(); break;
   case 'estate': estate_agent(); break;
   case 'sell': sell_house(); break;
   case 'rentals': rental_market(); break;
   case 'rent': rent_house(); break;
   case 'your_rents': your_rents(); break;
   case 'upgrade': upgrade_house(); break;
   default: houses_index(); break;
 }
function houses_index()
 {
	echo '<p class="heading"><b>Your Houses</b></p>';
   global $ir, $dbh;
   $houses = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseOwner` = '%u' || `uhouseTenant` = '%u') AND `uhouseId` != '%d'", $ir['userid'], $ir['userid'], $ir['house']));
   echo '<table width="600">
   <tr>
   <td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=estate">Estate agent</a></td>
   <td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=your_rents">Your rentals</a></td>
   <td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=rentals">Rent a house</a></td>
   </tr>
   </table>
    
';
   if($ir['house'])
    {
      $fetch = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) WHERE (`uhouseId` = '%u')", $ir['house']));
      $h = mysql_fetch_assoc($fetch);
      echo '<b>Current house:</b>
    
    
      <table width="300" class="table">
      <tr>
      <th width="50%">Current house</th>
      <th width="50%">Move out</th>
      </tr>
      <tr>
      <td>'.stripslashes($h['hNAME']).'</td>
      <td><a href="'.$_SERVER['PHP_SELF'].'?page=move_out">Move out</a></td>
      </tr>
      </table>
    
';
    }
   echo '<table width="600" class="table">
   <tr>
   <th width="15%">House name</th>
   <th width="20%">Owner</th>
   <th width="25%">Tenant</th>
   <th width="15%">Will value</th>
   <th width="25%">Manage</th>
   </tr>';
   if(!mysql_num_rows($houses))
    {
      echo '<tr>
      <td colspan="5">You have no houses at this time, purchase one at the estate agent\'s.</td>
      </tr>';
    }
   while($r = mysql_fetch_assoc($houses))
    {
    	if ($r['uhouseTenant']) {
    		$sql = $dbh->prepare("SELECT username from users where userid=?");
    		$sql->bindParam(1, $r['uhouseTenant']);
    		$sql->execute();
    		$tenant_info = $sql->fetch();
    		$tenant = '<a href=viewuser.php?u='.$r['uhouseTenant'].'>'.stripslashes($tenant_info['username']).'</a><br>
    				'.$r['uhouseRTime'].' days left<br>
    				for $'.number_format($r['uhouseRent']).' a day';
    	} else {
    		$tenant = 'N/A';
    	}
      echo '<tr>
      <td>'.stripslashes($r['hNAME']).'</td>
      <td><a href=viewuser.php?u='.$r['userid'].'>'.stripslashes($r['username']).'</a></td>
      <td>'.$tenant.'</td>
      <td>'.number_format($r['uhouseMood']).' Will</td>
      <td><a href='.$_SERVER['PHP_SELF'].'?page=move&id='.$r['uhouseId'].'>Move in  </a>
    
      <p><a href='.$_SERVER['PHP_SELF'].'?page=sell&id='.$r['uhouseId'].'>Sell house  </a>
    
      <p><a href='.$_SERVER['PHP_SELF'].'?page=rent&id='.$r['uhouseId'].'>Rent house  </a>
    
      <p><a href='.$_SERVER['PHP_SELF'].'?page=upgrade&id='.$r['uhouseId'].'>Add upgrades  </a></td>
      </tr>';
    }
   echo '</table>';
 }
function move_house()
 {
   global $ir;
   $fetch = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseId` = '%u')", abs((int) $_GET['id'])));
   if(!isset($_GET['id']))
    {
      echo 'You did not select a house to move in to.';
    }
   else if(!mysql_num_rows($fetch))
    {
      echo 'You cannot move into a non-existant house.';
    }
   else
    {
      $r = mysql_fetch_assoc($fetch);
      if($r['uhouseOwner'] != $ir['userid'] AND $r['uhouseTenant'] != $ir['userid'])
       {
         echo 'You are not permitted to move into this house.';
       }
      else if($r['uhouseRTime'] AND $r['uhouseOwner'] == $ir['userid'])
       {
         echo 'You cannot move into a house while it is being rented to another member.';
       }
      else
       {
         mysql_query(sprintf("UPDATE `users` SET `house` = '%d', `maxwill` = '%d' WHERE (`userid` = '%u')", abs((int) $_GET['id']), $r['uhouseMood'], $ir['userid']));
         echo 'You have moved into the '.stripslashes($r['hNAME']).', You now have a maximum Will bar of '.number_format($r['uhouseMood']).'.';
       }
    }
 }
function move_out()
 {
   global $ir;
   $fetch = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseId` = '%u')", $ir['house']));
   if(!mysql_num_rows($fetch))
    {
      echo 'You cannot move out of a non-existant house.';
    }
   else
    {
      $r = mysql_fetch_assoc($fetch);
      if($r['uhouseOwner'] != $ir['userid'] AND $r['uhouseTenant'] != $ir['userid'])
       {
         echo 'You are not permitted to move out of this house.';
       }
      else
       {
         mysql_query(sprintf("UPDATE `users` SET `house` = '0', `maxwill` = '100', `will` = '100' WHERE (`userid` = '%u')", $ir['userid']));
         echo 'You have moved out of the '.stripslashes($r['hNAME']).', You now have a maximum Will bar of 100.';
       }
    }
 }
function sell_house()
 {
   global $ir;
   $fetch = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseOwner` = '%u') AND (`uhouseId` = '%u')", $ir['userid'], abs((int) $_GET['id'])));
   if(!isset($_GET['id']))
    {
      echo 'You did not select a house to sell.';
    }
   else if(!mysql_num_rows($fetch))
    {
      echo 'You cannot attempt to sell a non-existant house.';
    }
   else
    {
      $r = mysql_fetch_assoc($fetch);
      if($r['uhouseOwner'] != $ir['userid'])
       {
         echo 'You do not own this house, so don\'t attempt to sell it.';
       }
      else if($r['uhouseTenant'])
       {
         echo 'You cannot sell a house while it is being rented to another member.';
       }
      else
       {
         mysql_query(sprintf("UPDATE `users` SET `money` = `money` + '%d' WHERE (`userid` = '%u')", $r['hPRICE'], $ir['userid']));
         mysql_query(sprintf("DELETE FROM `owned_houses` WHERE (`uhouseId` = '%u')", abs((int) $_GET['id'])));
         echo 'You have sold the '.stripslashes($r['hNAME']).' for a total of $'.number_format($r['hPRICE']).'.';
       }
    }
 }
function estate_agent()
 {
 	echo '<p class="heading"><b>Estate Agent</b></p>';
	echo '<table width="600">
		<tr>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=estate">Estate agent</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=your_rents">Your rentals</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=rentals">Rent a house</a></td>
		</tr>
		</table>';
   global $ir;
   if(isset($_GET['id']))
    {
      $house = mysql_query(sprintf("SELECT * FROM `houses` WHERE (`hID` = '%u')", abs((int) $_GET['id'])));
      $r = mysql_fetch_assoc($house);
      if(!mysql_num_rows($house))
       {
         echo 'You cannot attempt to purchase a non-existant house.';
       }
      else if($ir['money'] < $r['hPRICE'])
       {
         echo 'You cannot afford to purchase this house right now, come back another time.';
       }
      else
       {
         mysql_query(sprintf("UPDATE `users` SET `money` = `money` - '%d' WHERE (`userid` = '%u')", $r['hPRICE'], $ir['userid']));
         mysql_query(sprintf("INSERT INTO `owned_houses` (`uhouseId`, `uhouseOwner`, `uhouseHouse`, `uhouseMood`) VALUES ('NULL','%u', '%d', '%d')", $ir['userid'], $r['hID'], $r['hWILL']));
         echo 'You have purchased the '.stripslashes($r['hNAME']).' for a total of $'.number_format($r['hPRICE']).'!';
       }
    }
   else
    {
      $houses = mysql_query(sprintf("SELECT * FROM `houses` ORDER BY `hWILL` ASC"));
      echo '<table width="600" class="table">
      <tr>
      <th>House name</th>
      <th>Will value</th>
      <th>Cost</th>
      </tr>';
      while($r = mysql_fetch_assoc($houses))
       {
         echo '<tr>
         <td><a href='.$_SERVER['PHP_SELF'].'?page=estate&id='.$r['hID'].'>'.stripslashes($r['hNAME']).'</a></td>
         <td>'.number_format($r['hWILL']).' Will bar</td>
         <td>$'.number_format($r['hPRICE']).'</td>
         </tr>';
       }
      print '</table>';
    }
 }
function rental_market()
 {
 	echo '<p class="heading"><b>Rental Market</b></p>';
 	echo '<table width="600">
		<tr>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=estate">Estate agent</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=your_rents">Your rentals</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=rentals">Rent a house</a></td>
		</tr>
		</table>';
   global $ir, $dbh;
   if(isset($_GET['id']))
    {
      $houses = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `users` ON (`userid` = `uhouseTenant`) WHERE `uhouseTenant` > '0'"));
      $house = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseId` = '%u')", abs((int) $_GET['id'])));
      $r = mysql_fetch_assoc($house);
      if(!mysql_num_rows($house))
       {
         echo 'You cannot rent a house that does not exist.';
       }
      else if($ir['money'] < $owned_houses['uhouseRent']*$owned_houses['uhouseRTime'])
       {
         echo 'You cannot afford this house.';
       }
      else if($ir['money'] > $owned_houses['uhouseRent']*$owned_houses['uhouseRTime'])
       {
       	$sql = $dbh->prepare("update users set will=will+? where userid=?");;
       	$sql->bindParam(1, $r['uhouseMood']);
       	$sql->bindParam(2, $ir['userid']);
       	$sql->execute();
     mysql_query(sprintf("UPDATE `users` SET `money` = `money` - '%d' WHERE `userid` = '%u'", $r['uhouseRent'], $r['uhouseTenant']));
         mysql_query(sprintf("UPDATE `owned_houses` SET `uhouseTenant` = '%d' WHERE (`uhouseId` = '%u')", $ir['userid'], abs((int) $_GET['id'])));
         echo 'You are now renting the '.stripslashes($r['hNAME']).' for a total of $'.number_format($r['uhouseRent']).' each night!';
       }
    }
   else
    {
      $houses = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE `uhouseTenant` = '0' AND `uhouseRent` > '0' ORDER BY `uhouseRent` ASC"));
      echo '<table width="600" class="table">
      <tr>
      <th>House name</th>
      <th>Owner</th>
      <th>Will value</th>
      <th>Cost each night</th>
      <th>Rental time</th>
      <th>Manage</th>
      </tr>';
      while($r = mysql_fetch_assoc($houses))
       {
         echo '<tr>
         <td>'.stripslashes($r['hNAME']).'</td>
         <td><a href=viewuser.php?id='.$r['userid'].'
         >'.stripslashes($r['username']).'</a></td>
         <td>'.number_format($r['uhouseMood']).' Will bar</td>
         <td>$'.number_format($r['uhouseRent']).'</td>
         <td>'.number_format($r['uhouseRTime']).' nights</td>
         <td><a href='.$_SERVER['PHP_SELF'].'?page=rentals&id='.$r['uhouseId'].'>Rent house</a></td>
         </tr>';
       }
      print '</table>';
    }
 }
function rent_house()
 {
 	echo '<p class="heading"><b>Rent Your House</b></p>';
 	echo '<table width="600">
		<tr>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=estate">Estate agent</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=your_rents">Your rentals</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=rentals">Rent a house</a></td>
		</tr>
		</table>';
   global $ir;
   $fetch = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseOwner` = '%u') AND (`uhouseId` = '%u')", $ir['userid'], abs((int) $_GET['id'])));
   if(!isset($_GET['id']))
    {
      echo 'You did not select a house to rent out to members.';
    }
   else if(!mysql_num_rows($fetch))
    {
      echo 'You cannot attempt to rent out a non-existant house.';
    }
   else
    {
      $r = mysql_fetch_assoc($fetch);
      if($r['uhouseOwner'] != $ir['userid'])
       {
         echo 'You do not own this house, so don\'t attempt to rent it out to people.';
       }
      else if($r['uhouseTenant'])
       {
         echo 'You cannot rent out a house while it is being rented to another member.';
       }
      else
       {
         if(isset($_POST['time']) AND isset($_POST['cost']))
          {
            mysql_query(sprintf("UPDATE `owned_houses` SET `uhouseRent` = '%d', `uhouseRTime` = '%d' WHERE (`uhouseId` = '%u')", abs((int) $_POST['cost']), abs((int) $_POST['time']), abs((int) $_GET['id'])));
            echo 'You have added the '.stripslashes($r['hNAME']).' the the rental market at a cost of $'.number_format($_POST['cost']).' per night.';
          }
         else
          {
            echo '<form action="'.$_SERVER['PHP_SELF'].'?page=rent&id='.$_GET['id'].'" method="post">
            <table width="600">
            <tr>
            <td><b>Amount of nights:</b></td> 
            <td><input type="text" name="time" value="30" /></td>
            </tr>
            <tr>
            <td><b>Cost per nights:</b></td> 
            <td><input type="text" name="cost" value="250" /></td>
            </tr>
            <tr>
            <td colspan="2" align="center"><input type="submit" value="Submit rental" /></td>
            </tr>
            </table>
            </form>';
          }
       }
    }
 }
 
 function your_rents()
 {
 	echo '<p class="heading"><b>Your Current Rentals</b></p>';
 	echo '<table width="600">
		<tr>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=estate">Estate agent</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=your_rents">Your rentals</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=rentals">Rent a house</a></td>
		</tr>
		</table>';
 	global $ir, $dbh;
 		error_reporting(E_ALL);
 		$sql = $dbh->prepare("SELECT * FROM owned_houses
 				LEFT JOIN houses ON (hID=uhouseHouse)
 				LEFT JOIN users ON (userid=uhouseOwner)
 				WHERE uhouseTenant=?");
 		$sql->bindParam(1, $ir['userid']);
 		$sql->execute();
 		echo '<table width="600" class="table">
      <tr>
      <th>House name</th>
      <th>Owner</th>
      <th>Will value</th>
      <th>Cost each night</th>
      <th>Nights left</th>
      </tr>';
 		while($r = $sql->fetch())
 		{
 			echo '<tr>
         <td>'.stripslashes($r['hNAME']).'</td>
         <td><a href=viewuser.php?id='.$r['userid'].'>'.stripslashes($r['username']).'</a></td>
         <td>'.number_format($r['uhouseMood']).' Will bar</td>
         <td>$'.number_format($r['uhouseRent']).'</td>
         <td>'.number_format($r['uhouseRTime']).' nights</td>
         </tr>';
 		}
 		print '</table>';
 }
 
function upgrade_house()
 {
 	echo '<p class="heading"><b>House Upgrades</b></p>';
 	echo '<table width="600">
		<tr>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=estate">Estate agent</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=your_rents">Your rentals</a></td>
		<td width="33%" align="center"><a href="'.$_SERVER['PHP_SELF'].'?page=rentals">Rent a house</a></td>
		</tr>
		</table>';
   global $ir;
   if(!isset($_POST['id']))
    {
      if(isset($_GET['id']))
       {
         echo '<form action="'.$_SERVER['PHP_SELF'].'?page=upgrade&id='.$_GET['id'].'" method="post" name="upgrades">
         <table class="table" width="600">
         <tr>
         <th width="45%">Upgrade name</th>
         <th width="25%">Will gain</th>
         <th width="25%">Cost</th>
         <th width="5%"></th>
         </tr>';
         $fetch = mysql_query("SELECT * FROM `house_upgrades` ORDER BY `upgradeMood` ASC");
         while ($r = mysql_fetch_assoc($fetch)) 
          {
            echo '<tr>
            <td>'.stripslashes($r['upgradeName']).'</td>
            <td>'.number_format($r['upgradeMood']).' Will</td>
            <td>$'.number_format($r['upgradeCost']).'</td>
            <td><input type="radio" name="id" value="'.$r['upgradeId'].'" onClick="document.upgrades.submit();" /></td>
            </tr>'; 
          }
         echo '</table>';
       }
      else
       {
         echo 'You did not select a house to add upgrades to.';
       }
    }
   else
    {
      $upgrade = mysql_query(sprintf("SELECT * FROM `house_upgrades` WHERE `upgradeId` = '%u'", abs((int) $_POST['id'])));
      if(!mysql_num_rows($upgrade))
       {
         echo 'This upgrade does not exist at this time, if this problem continues report it to staff.';
       }
      else if(!isset($_GET['id']))
       {
         echo 'You did not select a house to add upgrades to.';
       }
      else
       {
         $house = mysql_query(sprintf("SELECT * FROM `owned_houses` LEFT JOIN `houses` ON (`hID` = `uhouseHouse`) LEFT JOIN `users` ON (`userid` = `uhouseOwner`) WHERE (`uhouseId` = '%u')", abs((int) $_GET['id'])));
         $h = mysql_fetch_assoc($house);
         $r = mysql_fetch_array($upgrade);
         if(!mysql_num_rows($house))
          {
            echo 'You cannot add upgrades to a non-existant house.';
          }
         else if($h['uhouseOwner'] != $ir['userid'])
          {
            echo 'You are not permitted to add upgrades to this house.';
          }
         else if($ir['money'] < $r['upgradeCost'])
          {
            echo 'You do not have enough cash to purchase this upgrade right now.';
          }
         else
          {
            $check = mysql_query(sprintf("SELECT * FROM `owned_upgrades` WHERE (`ownupHouse` = '%u') AND (`ownupUpgrade` = '%d')", abs((int) $_GET['id']), abs((int) $_POST['id'])));
            if(mysql_num_rows($check))
             {
               echo 'This house has this upgrade at this time, it cannot be bought again.';
             }
            else
             {
               mysql_query(sprintf("UPDATE `users` SET `money` = `money` - '%d' WHERE `userid` = '%u'", $r['upgradeCost'], $ir['userid']));
               mysql_query(sprintf("UPDATE `owned_houses` SET `uhouseMood` = `uhouseMood` + '%d' WHERE `uhouseId` = '%u'", $r['upgradeMood'], abs((int) $_GET['id'])));
               mysql_query(sprintf("INSERT INTO `owned_upgrades` (`ownupId`, `ownupHouse`, `ownupUpgrade`) VALUES ('NULL','%u', '%d')", abs((int) $_GET['id']), abs((int) $_POST['id'])));
               echo 'You have purchased the '.stripslashes($r['upgradeName']).' for $'.number_format($r['upgradeCost']).'.';
             }
          }
       }
    }
 }
$h->endpage();
?>