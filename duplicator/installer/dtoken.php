<?php @error_reporting(0);
@require_once("../../../../wp-admin/admin.php");
global $wp_query;
$wp_query->set_404();
header("HTTP/1.1 404 Not Found", true, 404);
header("Status: 404 Not Found");
@include(get_template_directory() . "/404.php"); ?>