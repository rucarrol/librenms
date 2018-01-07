<?php

$sql = 'SELECT * FROM `lsp` AS L, `devices` AS D WHERE D.device_id = ' . $device['device_id'] . ' AND L.device_id = D.device_id';

$lsps = dbFetchRows($sql);
$i = 0;

foreach ($lsps as $lsp) {
    $rrd_filename = rrd_name($device['hostname'], 'mpls_lsp-' . $lsp['lsp_name']);

    if (rrdtool_check_rrd_exists($rrd_filename)) {
        $descr = $lsp['lsp_name'];

        $rrd_list[$i]['filename'] = $rrd_filename;
        $rrd_list[$i]['descr']    = $descr;
        $rrd_list[$i]['ds']       = 'mplsLspOctets';
        $rrd_list[$i]['area']     = 1;
        $i++;
    }
}

$unit_text = 'mplsLspOctets %';

$units       = '';
$total_units = '%';
$colours     = 'mixed';

$scale_min = '0';
$scale_max = '100';

$nototal = 1;

require 'includes/graphs/generic_multi_line.inc.php';
