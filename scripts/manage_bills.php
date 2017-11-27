#!/usr/bin/env php
<?php

$init_modules = array();
require realpath(__DIR__ . '/..') . '/includes/init.php';

/** Bill management tool
    Todo:
      - Actually create a bill
      - Option to empty a bill
      - Probably tons of bug fixes and safety checks.
    Note:
      - Current, this cannot create a new bill. To do this, you need to use the GUI.
**/

// Find the correct bill, exit if we get anything other than 1 result.
function list_bills($bill_name)
{
    $bill = dbFetchRows("SELECT `bill_id`,`bill_name` FROM `bills` WHERE `bill_name` LIKE ?", array("$bill_name"));
    if (count($bill) != 1) {
        echo("Did not find exactly 1 bill, exiting\n");
        echo("Query:".$bill."\n");
        exit(1);
    } else {
        echo("Found bill {$bill[0]['bill_name']} ({$bill[0]['bill_id']})\n");
    }
    return $bill[0]['bill_id'];
}

// This will get an array of devices we are interested in from the CLI glob
function get_devices($host_glob, $nameType)
{
    return dbFetchRows("SELECT `device_id`,`hostname`,`sysName` FROM `devices` WHERE `".$nameType."` LIKE ?", array("%$host_glob%"));
}

// This will flush bill ports if -r is set on cli
function flush_bill($id)
{
    echo("Removing ports from bill ID $id\n");
    return dbDelete('bill_ports', '`bill_id` = ?', array($id));
}


function add_ports_to_bill($devs, $intf_glob, $id)
{
    // Abort mission if no bill id is passed.
    if (empty($id)) {
        echo ("No bill ID passed, exiting...\n");
        exit(1);
    }

    // Expected interface glob:
    echo("Interface glob: $intf_glob\n");
    $device_ids = array_column($devs, "device_id");
    $ids = implode(",", $device_ids);

    // Find the devices which match the list of IDS and also the interface glob
    $query = "SELECT ports.port_id,ports.ifName,ports.ifAlias FROM ports INNER JOIN devices ON ports.device_id = devices.device_id WHERE ifType = 'ethernetCsmacd' AND ports.ifAlias LIKE '%$intf_glob%' AND ports.device_id in ($ids)";
    echo("Query: $query\n");
    foreach (dbFetchRows($query) as $ports) {
        echo("Inserting {$ports['ifName']} ({$ports['ifAlias']}) into bill $id\n");
        $insert = array (
            'bill_id' => $id,
            'port_id' => $ports['port_id'],
            'bill_port_autoadded' => '1'
        );
        dbInsert($insert, 'bill_ports');
    }
    return true;
}

/** Setup options:
    l - bill_name - bill glob
    c - circuit_id - interface glob
    s - sysName - device glob
    h - hostname - device glob
    f - flush - boolean
**/

$options = getopt('b:s:h:i:f');

if (!empty($options['s'])) {
    $host_glob = str_replace('*', '%', mres($options['s']));
    $nameType = "sysName";
}
if (!empty($options['h'])) {
    $host_glob = str_replace('*', '%', mres($options['h']));
    $nameType = "hostname";
}
if (empty($options['s']) && empty($options['h'])) {
    echo "Please set -s or -h\n";
} else if (!empty($options['s']) && !empty($options['h'])) {
    echo "Please set either -s or -h, not both\n";
}

$bill_name = str_replace('*', '%', mres($options['b']));
$intf_glob = str_replace('*', '%', mres($options['i']));

if (empty($bill_name) or !(empty($options['h']) and empty($options['s']) ) {
    echo "Usage:\n";
    echo "-b <bill name glob>   Bill name to match\n";
    echo "-s <sysName glob>     sysName to match (Cannot be used with -h)\n";
    echo "-h <hostname glob>    Hostname to match (Cannot be used with -s)\n";
    echo "-i <Interface description glob>   Interface description to match\n";
    echo "-f Flush all ports from a bill before adding adding ports\n";
    echo "Example:\n";
    echo "If I wanted to add all interfaces containing the description Telia to a bill called 'My Lovely Transit Provider'\n";
    echo "php manage_bills.php -l 'My Lovely Transit Provider' -d all -c Telia";
    echo "\n";
    exit;
}

if ($bill_name == 'all') {
    $bill_name = '%';
}
if ($intf_glob == 'all') {
    $intf_glob = '%';
}
if ($host_glob == 'all') {
    $host_glob = '%';
}
if (isset($options['f'])) {
    $flush = true;
} else {
    $flush = false;
}

$id = list_bills($bill_name);

$devices = get_devices($host_glob, $nameType);

if (empty($devices)) {
    echo "No devices found\n";
    exit(1);
}

if ($flush) {
    $flush_ret = flush_bill($id);
}

$ret = add_ports_to_bill($devices, $intf_glob, $id);
