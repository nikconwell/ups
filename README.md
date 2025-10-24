# UPS

Recently added an Eaton UPS to my homelab. My UPS only has a USB/serial interface and not a network interface so in order to monitor this (with Observium) I needed to have snmpd query UPS values and present the OIDs for Observium to pick up.

See the various files below for what was done.

Eaton docs were found by googling "eaton ups snmp oids" which led me to <https://www.eaton.com/content/dam/eaton/products/backup-power-ups-surge-it-power-distribution/power-management-software-connectivity/connectups-e-web-snmp-device/Eaton-connect-UPS-web-snmp-user-guide-164201819.pdf.pdf>


# upsc

This was a bit fiddly to get working, but ultimately involved:

```bash
dnf install nut --enablerepo=epel
dnf install libusb1-devel
```

## Config files:
### /etc/ups/nut.conf
```text
MODE=standalone
```

### /etc/ups/ups.conf
Configuration for our UPS device. The output of ```nut-scanner -U``` will give this, which goes into /etc/ups/ups.conf:
```text
[nutdev-usb1]
        driver = "usbhid-ups"
        port = "auto"
        vendorid = "0463"
        productid = "FFFF"
        product = "Eaton 5SC"
        serial = "P138T15FNJ"
        vendor = "EATON"
        # bus = "002"
        # device = "004"
        # busport = "006"
```


### /etc/ups/upsmon.conf
Configuration for how we are monitoring our UPS device. See below ```upsd.users``` for the definition of the monuser account.
```text
MONITOR nutdev-usb1@localhost 1 monuser secret master
MINSUPPLIES 1
SHUTDOWNCMD "/usr/sbin/shutdown -h now"
NOTIFYFLAG ONLINE   SYSLOG+WALL
NOTIFYFLAG ONBATT   SYSLOG+WALL
NOTIFYFLAG LOWBATT  SYSLOG+WALL
NOTIFYFLAG FSD      SYSLOG+WALL
POLLFREQ 5
POLLFREQALERT 5
HOSTSYNC 15
DEADTIME 15
POWERDOWNFLAG /etc/killpower
RBWARNTIME 43200
NOCOMMWARNTIME 300
FINALDELAY 5
```

### /etc/ups/upsd.users
Definition for the 'monuser' account.
```text
[monuser]
    password = secret
    upsmon master
```


### Get USB device to show up:
```bash
ls -ladt /lib/udev/rules.d/62-nut-usbups.rules
udevadm control --reload
udevadm trigger
```


### And then finally start the service with:

```bash
systemctl enable --now nut.target
upsdrvctl start
```

### To see ups info:

```bash
upsc nutdev-usb1@localhost
```


# /etc/snmp/snmpd.conf

Once the UPS device is up and happy, we add update our SNMPD to provide the UPS info via OIDs.


In here I needed to specify that the Eaton OID (.1.3.6.1.4.1.534) is passed to and provided by the ups-nut.py program.
```text
pass_persist .1.3.6.1.4.1.534 /etc/snmp/ups-nut.py
```


# /etc/snmp/ups-nut.py

This runs the upsc command, parses the output and provides the OIDs to the snmpd via the pass_persist snmpd "API".
The nice thing about doing this with python is we can use the snmp_passpersist module which then lets us do things like:
```python
pp = snmp.PassPersist('.1.3.6.1.4.1.534')
```
and subsequently
```python
pp.add_int('2.4.0', battery.charge)
```

and PassPersist handles replying to snmpget and snmpwalk for the OIDs. It also
includes support (via ```pp.start(function,refresh_seconds)``` for long running child processes of snmpd that
can refresh values occasionally to make the SNMP queries more lightweight rather than running upsc on each query.




# config.php

Updates to /opt/observium/config.php needed to monitor the UPS. I suspect if I had a UPS with a network interface this may be discovered easily enough however since the UPS is connected to another server, which I also want to server monitor, it's not being discovered as a UPS and a server. So I needed to define specific sensors to monitor in the config.php and query the OIDs for what I wanted to keep track of.
