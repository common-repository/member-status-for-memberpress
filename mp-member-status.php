<?php
/*
 * Plugin Name: Member status API for MemberPress
 * Plugin URI: https://www.nakko.com/plugins?plugin=mp-member-status
 * Description: This plugin extends the WordPress REST API with MemberPress membership and product data.
 * Version: 1.1.3
 * Author: Nakko
 * Author URI: https://www.nakko.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: mepr-member-status
 * Domain Path: /languages
 */

use Nakko\MeprMemberStatus\MemberPressFieldExtension;
use Nakko\MeprMemberStatus\MemberStatusApi;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

define("MEPR_MEMBER_STATUS_PLUGIN_PATH", plugin_dir_path(__FILE__));
define("MEPR_MEMBER_STATUS_OPTION_PREFIX", "mepr-member-status_");

require_once(MEPR_MEMBER_STATUS_PLUGIN_PATH . 'autoload.php');

add_action('rest_api_init', function () {
    MemberStatusApi::init();
    MemberPressFieldExtension::init();
});