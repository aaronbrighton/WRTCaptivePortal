<?php
// Checks and string to see if it fits the pattern of an IP address.
function validIP($ip){
    if(preg_match("^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}^", $ip))
        return true;
    else
        return false;
} 

if (isset($_POST['submit']))
{
	// Form has been submitted.
	// MySQL connection information. 
	$mysql['host'] = 'localhost';
	$mysql['user'] = 'macs';
	$mysql['pass'] = '';
	$mysql['dbname'] = 'macs';
	
	// Get IP address of end-user and the usernamd and password they posted.
	$ip = $_SERVER['REMOTE_ADDR'];
	$username = strtoupper($_POST['username']);
	$password = $_POST['password'];
	
	// Include the LDAP class so that we can use it to authenticate against our Domain Controller.
	require_once('adLDAP.php');
	$adldap = new adLDAP();
	
	// Check if the username and password submitted the user, authenticated with active directory.
	if ($adldap->authenticate($username,$password) && (validIP($ip) == true))
	{
		// User has been authenticated, their username and password match with an AD user.
		// Connect to mysql database.
		mysql_connect($mysql['host'], $mysql['user'], $mysql['pass']);
		mysql_select_db($mysql['dbname']);
		
		$result = shell_exec("/usr/bin/sudo arp -a ".$ip); // Check ARP table for MAC address for this user.

		// Split the result up, so we can extract the MAC Address.
		$split = explode(':', $result);
		$seg1Len = strlen($split[0]);
		$split[0] = substr($split[0], ($seg1Len-2), $seg1Len);
		$split[5] = substr($split[5], 0, 2);
		
		// Store the mac address.
		$macAddress = implode(':', $split);
		
		// Query the database to see if this user already exists in the database given the mac address.
		$query = mysql_query("SELECT `id` FROM `active_clients` WHERE `mac_address` = '".mysql_real_escape_string($macAddress)."'") or die(mysql_error());

		// Let's save this data into the database...
		if (mysql_num_rows($query) == 1)
		{
			// MAC address already exists, in the database we need to update rather than delete.
			$query = mysql_query("UPDATE `active_clients` SET `active` = '1', `ip_address` = '".mysql_real_escape_string($ip)."' WHERE `mac_address` = '".$macAddress."'") or die(mysql_error());
		}
		else
		{
			// MAC address does not exist in the database, so let's insert a new record.
			$query = mysql_query("INSERT INTO `active_clients` (`id`, `ip_address`, `mac_address`, `username`, `time_authenticated`, `active`) VALUES ('', '".mysql_real_escape_string($ip)."', '".mysql_real_escape_string($macAddress)."', '".mysql_real_escape_string($username)."', '".time()."', '1')") or die(mysql_error());
		}
		
		// Set variable for success/redirect text to be displayed.
		$auth=2;
	}
	else if (validIP($ip) == false)
	{
		// Ip addres is invalid, this could mean someone attempted to inject this script...
		$auth=3;
	}
	else
	{
		// User has not been authenticatd, their username and or password do not match with an AD user.
		$auth=1;
	}
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Wireless Access Portal</title>
    </head>
    <body>
    	<form method="post" action="">
    	<table cellspacing="1" cellpadding="7" align="center" width="400">
        	<tr>
            	<td colspan="2" align="center"><b>Wireless Access Portal</b></td>
            </tr>
            <tr>
            	<td colspan="2" align="center">
                	<?php
					if ($auth==2)
					{
						// Authentication successfull, let's tell them and redirect.
					?>
                    <span style="color:#090">You have been authenticated, we are now redirecting you.</span><br /><br />
                    <meta http-equiv="refresh" content="5;url=<?php echo urldecode($_GET['redirect']); ?>" />
                    <?php
					}
					elseif ($auth==1)
					{
						// Authentication failed, let's tell them and well give them a second chance at life.
					?>
                    <span style="color:#F00">The username or password you entered is incorrect.</span><br /><br />
                    <?php
					}
					elseif ($auth==3)
					{
						// Authentication failed, let's tell them and well give them a second chance at life.
					?>
                    <span style="color:#F00">Due to the fact that your IP address is not valid, we cannot authenticate you.</span><br /><br />
                    <?php
					}
					?>
                	Please enter your active directory username and password in the form below to gain access to the wireless internet.
                </td>
            </tr>
            <tr>
            	<td align="center">Username</td>
                <td align="center"><input type="text" name="username" /></td>
            </tr>
            <tr>
            	<td align="center">Password</td>
                <td align="center"><input type="password" name="password" /></td>
            </tr>
            <tr>
            	<td colspan="2" align="center"><input type="submit" name="submit" value="Login!" /></td>
            </tr>
        </table>
        </form>
    </body>
</html>
