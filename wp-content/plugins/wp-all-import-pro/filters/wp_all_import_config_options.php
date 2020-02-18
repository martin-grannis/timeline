<?php

function pmxi_wp_all_import_config_options($option) {
    if ($option['cron_processing_time_limit'] == 120) {
        $option['cron_processing_time_limit'] = 59;
    }
    return $option;
}