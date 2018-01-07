<?php

use LibreNMS\RRD\RrdDefinition;

echo "Starting LSP polling\n";

// We're only interested in name, packets/bits and path bandwidth
$pws = array();
$pws = snmpwalk_cache_oid($device, 'mplsLspName', $pws, 'MPLS-MIB');
$pws = snmpwalk_cache_oid($device, 'mplsLspOctets', $pws, 'MPLS-MIB');
$pws = snmpwalk_cache_oid($device, 'mplsLspPackets', $pws, 'MPLS-MIB');
$pws = snmpwalk_cache_oid($device, 'mplsPathBandwidth', $pws, 'MPLS-MIB');

// For every result we get, create an RRD based on filename and update it 
foreach ($pws as $pw) {
    echo '.';

    $rrd_def = RrdDefinition::make()
        ->addDataset('mplsLspOctets', 'COUNTER', 0, 10000000000)
        ->addDataset('mplsLspPackets', 'COUNTER', 0, 10000000000)
        ->addDataset('mplsPathBandwidth', 'GAUGE', 0, 10000000000);

    // JunOS returns LSP names padded out with '.'. No idea why.
    $lspName =  preg_replace("/\.+$/", "", $pw['mplsLspName']);
    $rrd_name = array('lsp-' . $lspName, $lspName, 0);

    $fields = array(
        'mplsLspOctets'         => $pw['mplsLspOctets'],
        'mplsLspPackets'        => $pw['mplsLspPackets'],
        'mplsPathBandwidth'       => $pw['mplsPathBandwidth'],
    );
    // I HAVE NO IDEA WHAT THIS DOES
    $tags = compact('rrd_def');
    data_update($device, 'mpls_lsp-' . $lspName, $tags, $fields);
    $graphs['mpls_lsp'] = true;
}//end foreach

echo "\n";

unset($pws, $rrd_def,$tags,$fields);
