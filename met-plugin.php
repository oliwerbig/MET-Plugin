<?php
/**
 * Plugin Name: MET Plugin
 * Description: WordPress plugin a MET Industry Kft. számára.
 * Version: 1.0.0
 * Author: MET Industry Kft.
 * Text Domain: met-plugin
 */

require_once __DIR__ . '/includes/class-met-plugin.php';

function run_met_plugin() {
	$plugin = new Met_Plugin();
}
run_met_plugin();
