#!/usr/bin/env python3.12


# In snmpd.conf:
# pass_persist .1.3.6.1.4.1.534 /etc/snmp/ups-nut.py

# Run snmpd with debug logging:
# snmpd -f -Lo -Ducd-snmp/pass

# Test with:
# snmpget  -v2c -c public -On localhost .1.3.6.1.4.1.534.2.1.0
# snmpget  -v2c -c public -On localhost .1.3.6.1.4.1.534.3.4.1.2.0
# snmpwalk -v2c -c public -On localhost .1.3.6.1.4.1.534

import re
import subprocess
import snmp_passpersist as snmp
# https://github.com/nagius/snmp_passpersist
import sys


#error_log_file = open('/tmp/snmpoutput', 'w')
#sys.stderr = error_log_file

# Official Eaton OID as per
# https://www.eaton.com/content/dam/eaton/products/backup-power-ups-surge-it-power-distribution/power-management-software-connectivity/connectups-e-web-snmp-device/Eaton-connect-UPS-web-snmp-user-guide-164201819.pdf.pdf
oidprefix = '.1.3.6.1.4.1.534'


#
# OIDs we want to serve up, and their data source from the upsc command.
#

# Integer values
oids_int = {
    "ups.load":        "4.1.0",
    "battery.charge":  "2.4.0",
    "battery.runtime": "2.1.0",
}

# Floating point values
# Note, these we (*100) to make integer since SNMP only does INTs and then on the
# receiving end we need to *.01 to get back to floating point for monitoring/alerting.
oids_gau = {
    "input.voltage":   "3.4.1.2.0",
    "output.voltage":  "4.4.1.2.0",
    "input.current":   "3.4.1.3.0",
    "output.current":  "4.4.1.3.0",
}

# String/text values
oids_str = {
    "ups.status":     "9.9.9.9",
}




def update():

    #
    # Query status of UPS
    #

    command = ['/bin/upsc','nutdev-usb1@localhost']
    result = subprocess.run(command, stdout=subprocess.PIPE, stderr=subprocess.PIPE)

    #
    # Iterate status output, building MIB values
    # Example:
    #  battery.runtime: 8414
    #  ups.load: 3
    #  etc.
    #

    for line in result.stdout.decode('utf-8').splitlines():

        key, value = line.split(':',1)
        key = key.strip()
        value = value.strip()


        if key in oids_int:
            pp.add_int(oids_int[key], value)
#            sys.stderr.write(f'{key} = {value}\n')
        if key in oids_gau:
            pp.add_gau(oids_gau[key], float(value)*100)
#            sys.stderr.write(f'{key} = {float(value)*100}\n')
        if key in oids_str:
            pp.add_str(oids_str[key], value)
#            sys.stderr.write(f'{key} = {value}\n')





#
# Main code
#

pp = snmp.PassPersist(oidprefix)
pp.start(update,60)
