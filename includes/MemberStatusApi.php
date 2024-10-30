<?php

namespace Nakko\MeprMemberStatus;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

use MeprProduct;
use MeprUtils;

use Nakko\MeprMemberStatus\Dto\MemberStatusDto;

/**
 * Extends the WordPress API with additional functionality.
 */
class MemberStatusApi {

    const V1_NAMESPACE = 'mp-member-status/v1';

    private static $instance;

    /**
     * Initialize only once
     * @return void
     */
    public static function init() {
        if (!self::$instance instanceof MemberStatusApi) {
            self::$instance = new MemberStatusApi();
            self::$instance->register_rest_routes();
        }
    }

    private function __construct() {}

    /**
     * Determines if the MemberPress plugin is enabled
     * @return bool
     */
    private function is_memberpress_enabled(): bool {
        return class_exists('MeprUtils')
            && class_exists('MeprProduct')
            && class_exists('MeprUser');
    }


    /**
     * Registers routes and extends the WordPress REST API
     * @return void
     */
    private function register_rest_routes() {
        if ($this->is_memberpress_enabled()) {
            register_rest_route(self::V1_NAMESPACE, '/me', array(
                'callback' => array($this, 'get_current_user_info'),
                'methods' => WP_REST_Server::READABLE,
                'args' => array(),
                'permission_callback' => array('MeprUtils', 'is_logged_in_and_a_subscriber'),
            ));
        }
    }

    /**
     * MemberPress current user information request handler
     * @callback
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function get_current_user_info(WP_REST_Request $request): WP_REST_Response {
        $meprUser = MeprUtils::get_currentuserinfo();
        $products = array();
        foreach ($meprUser->active_product_subscriptions() as $product_id) {
            $products[] = new MeprProduct($product_id);
        }
        $memberStatus = new MemberStatusDto($meprUser, $products);
        return new WP_REST_Response($memberStatus, 200);
    }

}