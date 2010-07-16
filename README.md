Description
===========

Two parts of operational code are presented here.  One set (in the authServer directory) which runs the captive portal login page and authentication mechanism that runs against an LDAP (AD) server.  The second set of code (in the firewall directory) runs on a OpenWRT or DD-WRT enabled router.  In testing the infamous WRT54G was used.

Components
==========

Linux Server

 - Running Lighttpd + PHP + MySQL (captive portal)

Active Directory (or other LDAP enabled server)

 - User database to permit access to Wireless Network

Linux Powered Integrated Wireless Router or Access Point

 - Needs to be able to run PHP script locally and ebtables/iptables

How to use
==========

When implemented correctly, this allows one implement a wireless network with a captive portal that authenticates end-users against an LDAP backend before permitting network access.  An alternative to 802.1x from a time when captive portals were more common.
