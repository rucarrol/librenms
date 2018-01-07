<?php

use LibreNMS\Config;

if (Config::get('enable_pseudowires') && $device['os'] == 'junos') {
    
    // Get existing LSPs, avoid inserting a duplicate entry 
    $lsps = dbFetchRows('SELECT lsp_name FROM `lsp` WHERE `device_id` = ?', array($device['device_id']));
    $lsps = array_column($lsps, 'lsp_name');
    print_r($lsps);


    $pws = snmpwalk_cache_oid($device, 'mplsLspName', array(), 'MPLS-MIB');
    $pws = snmpwalk_cache_oid($device, 'mplsLspFrom', $pws, 'MPLS-MIB');
    $pws = snmpwalk_cache_oid($device, 'mplsLspTo', $pws, 'MPLS-MIB');
    $pws = snmpwalk_cache_oid($device, 'mplsPathBandwidth', $pws, 'MPLS-MIB');

    foreach ($pws as $pw_id => $pw) {
        // Get junk data sometimes, skip if no name 
        if ( empty($pw['mplsLspName']) ) { 
            continue;
        }
        $lspName =  preg_replace("/\.+$/", "", $pw['mplsLspName']);
        // If we have the LSP already, avoid inserting a duplicate. DB has CONSTRAINT to make lsp name and device id unique, however lets avoid unnessicary DB read/write
        if (in_array($lspName, $lsps)) {
            continue;
        }
        $dbs = array(
                   'device_id'  => $device['device_id'],
                   'lsp_name'   => $lspName,
                   'lsp_from'   => $pw['mplsLspFrom'],
                   'lsp_to'     => $pw['mplsLspTo'],
                   'bandwidth'  => $pw['mplsPathBandwidth'],
              );
        $lsp_id = dbInsert($dbs, 'lsp');
    }//end foreach

    echo "\n";
} //end if

unset($pws, $dbs, $lsp_id);
