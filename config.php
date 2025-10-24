// Details from https://docs.observium.org/statics/#examples-by-vendor
// Refresh after initial add with:
// /opt/observium/discovery.php -h splunge.splunge -m sensors -d

// Our Eaton UPS only has USB/serial and not a network interface
// so we run upsc on the connected host as part of snmpd and then use
// the same(ish) OIDS from the official Eaton UPS devices with a network
// interface and official MIB.

// Googled "eaton ups snmp oids"
// https://www.eaton.com/content/dam/eaton/products/backup-power-ups-surge-it-power-distribution/power-management-software-connectivity/connectups-e-web-snmp-device/Eaton-connect-UPS-web-snmp-user-guide-164201819.pdf.pdf



//
// Battery percentages, use the 'load' class which gives us percentage charts.
//
$config['sensors']['static'][] = [
    'device_id' => '5',                           // Target device ID, aka our system that has the UPS attached
    'class' => 'load',                            // Sensor class (temperature, voltage, current, etc.)
    'oid' => '.1.3.6.1.4.1.534.2.4.0',            // SNMP OID to poll
    'descr' => 'UPS Battery Percentage',          // Human description
    'multiplier' => 1,                            // Scale factor (value * multiplier)
    'limit_low' => 20,                            // Optional: low limit (alert)
    'limit_low_warn' => 40                        // Optional: low warning limit
];
$config['sensors']['static'][] = [
    'device_id' => '5',
    'class' => 'load',
    'oid' => '.1.3.6.1.4.1.534.4.1.0',
    'descr' => 'UPS Load Percentage',
    'multiplier' => 1,
    'limit' => 90,
    'limit_warn' => 80
];

//
// For device runtime, I tried counters but they focused on delta/rate changes whereas this
// value is fairly static. It may be worth evaluating other classes as the system 'uptime'
// displays time values rather nicely. As it stands now this chart will be something like
// ~8000 seconds which is not the most helpful.
//
$config['sensors']['static'][] = [
    'device_id' => '5',
    'class' => 'runtime',
    'oid' => '.1.3.6.1.4.1.534.2.1.0',
    'descr' => 'Estimated Runtime',
    'multiplier' => 1,
    'counter_unit' => 'seconds'
];



//
// Voltage and current have specific sensor classes. SNMP is integer only so we take the upsc
// voltage of 122.7 and multiply by 100 to get 12270 and the upsc current of 0.40 and multiply
// by 100 to get 40. We need to accomodate for that here and multiply by .01 to get back to the
// original units
//
$config['sensors']['static'][] = [
    'device_id' => '5',
    'class' => 'voltage',
    'oid' => '.1.3.6.1.4.1.534.3.4.1.2.0',
    'descr' => 'UPS input.voltage',
    'multiplier' => .01
];
$config['sensors']['static'][] = [
    'device_id' => '5',
    'class' => 'voltage',
    'oid' => '.1.3.6.1.4.1.534.4.4.1.2.0',
    'descr' => 'UPS output.voltage',
    'multiplier' => .01
];
$config['sensors']['static'][] = [
    'device_id' => '5',
    'class' => 'current',
    'oid' => '.1.3.6.1.4.1.534.3.4.1.3.0',
    'descr' => 'UPS input.current',
    'multiplier' => .01
];
$config['sensors']['static'][] = [
    'device_id' => '5',
    'class' => 'current',
    'oid' => '.1.3.6.1.4.1.534.4.4.1.3.0',
    'descr' => 'UPS output.current',
    'multiplier' => .01
];
