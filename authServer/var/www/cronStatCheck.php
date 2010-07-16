#!/usr/bin/php
<?php
// MySQL Connection Information.
$mysql['host'] = 'localhost';
$mysql['user'] = 'macs';
$mysql['pass'] = '';
$mysql['dbname'] = 'macs';

// 5 Minutes
$maxSessionTime = 5*60;

// Connect to the MySQL database.
mysql_connect($mysql['host'], $mysql['user'], $mysql['pass']) or die(mysql_error());
mysql_select_db($mysql['dbname']) or die(mysql_error());

// For all active clients.
$query = mysql_query("SELECT `ip_address`,`mac_address`,`time_authenticated` FROM `active_clients` WHERE `active` = '1'") or die(mysql_error());

// Loop through the rows.
while ($row = mysql_fetch_array($query))
{
	// Loop through all active clients, let's check and see if they're session has expired.
	if ((time()-$row['time_authenticated']) > $maxSessionTime)
	{
		// Session has surpassed time limit, let's check if the computer is still up.
		$ping = shell_exec("ping -c 1 ".$row['ip_address']);
		if (substr_count($ping, '64 bytes from '.$row['ip_address']))
		{
			// A computer is up at the given IP Address, let's verify it has the same MAC address.
			$result = shell_exec("/usr/bin/sudo arp -a ".$row['ip_address']); // Check ARP table for MAC address for this user.

			// Split the result up, so we can extract the MAC Address.
			$split = explode(':', $result);
			$seg1Len = strlen($split[0]);
			$split[0] = substr($split[0], ($seg1Len-2), $seg1Len);
			$split[5] = substr($split[5], 0, 2);
			
			// Store the mac address.
			$macAddress = implode(':', $split);
			
			if ($macAddress == $row['mac_address'])
			{
				// Mac address is the same, so this user is still active, so let's simply update the time of authentication to now.
				mysql_query("UPDATE `active_clients` SET `time_authenticated` = '".mysql_real_escape_string(time())."' WHERE `mac_address` = '".mysql_real_escape_string($row['mac_address'])."'") or die(mysql_error());
			}
			else
			{
				 // Mac address is not the same, so our client has left, let's unathenticated them.
				 mysql_query("UPDATE `active_clients` SET `active` = '0' WHERE `mac_address` = '".mysql_real_escape_string($row['mac_address'])."'") or die(mysql_error());
			}
		}
		else
		{
			// A computer is not up at the given IP Addres, or a firewall is blocking our ping, or ip changed... (Oh Well, their loss.)
			// Set this user to not active.
			mysql_query("UPDATE `active_clients` SET `active` = '0' WHERE `mac_address` = '".mysql_real_escape_string($row['mac_address'])."'") or die(mysql_error());
		}
	}
}
?>
