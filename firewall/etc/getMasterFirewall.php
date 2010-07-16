#!/usr/bin/php
<?php
// MySQL Connection Information
$mysql['host'] = '192.168.1.23';
$mysql['user'] = 'macs';
$mysql['pass'] = '';
$mysql['dbname'] = 'macs';

// Loop continuisly (This script will always be running.)
while (true)
{
	// Connect the MySQL and select the database.
    mysql_connect($mysql['host'], $mysql['user'], $mysql['pass']);
    mysql_select_db($mysql['dbname']);

	// Query the database for the clients that have been authenticated or have been unauthenticated.
    $query = mysql_query("SELECT `mac_address`,`active` FROM `active_clients`");

	// Loop through each row.
    while ($row = mysql_fetch_array($query))
    {
        if ($row['active'] == 1)
        {
			// The user is curerntly authenticated, let's check if they are authenticated in our firewall.
            if ($macs[$row['mac_address']] == 1)
            {
                // User has been unauthenticated in our firewall, so let's allow them through.
                $macs[$row['mac_address']] = 2;
                shell_exec("iptables -t nat -I PREROUTING -i br0 -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
                shell_exec("iptables -I FORWARD -i br0 -p tcp -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
                shell_exec("iptables -I FORWARD -i br0 -p udp -m mac --mac-source ".$row['mac_address']." -j ACCEPT");

            }
            elseif ($macs[$row['mac_address']] == 2)
            {
                // Mac has been already authenticated, so let's do nothing...

            }
            else
            {
                // Mac has not been already authenticated or even authenticated at all..., so let's allow them through.
                $macs[$row['mac_address']] = 2;
                shell_exec("iptables -t nat -I PREROUTING -i br0 -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
                shell_exec("iptables -I FORWARD -i br0 -p tcp -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
                shell_exec("iptables -I FORWARD -i br0 -p udp -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
             }
        }
        else
        {
			// User is not authenticated, so let's verify our firewall says the same.
            if ($macs[$row['mac_address']] == 2)
            {
                // User is currently authenticated, so let's remove them from the firewall so they will be required to reauthenticate.
                $macs[$row['mac_address']] = 1;
                shell_exec("iptables -t nat -D PREROUTING -i br0 -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
                shell_exec("iptables -D FORWARD -i br0 -p tcp -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
                shell_exec("iptables -D FORWARD -i br0 -p udp -m mac --mac-source ".$row['mac_address']." -j ACCEPT");
            }
            elseif ($macs[$row['mac_address']] == 1)
            {
                // User is currently unauthenticated, let's leave em that way.
            }
            else
            {
                // User is current unauthenticated, so we shouldnt need to do anything?
            }
        }
	}

	// Clear up any resources used by the query, and close the mysql connection, and we will take a 3 second break before doing it all over again.
    mysql_free_result($query);
    mysql_close();
    sleep(3);
}
?>
