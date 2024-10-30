<?php

namespace Nakko\MeprMemberStatus;

use MeprProduct;
use MeprRule;
use MeprUser;
use Nakko\MeprMemberStatus\Dto\ProductDto;

/**
 * Extends the WordPress post objects with additional, MemberPress related fields.
 */
class MemberPressFieldExtension {
    const CACHE_GROUP = "mepr";
    const CACHE_DURATION = 60; // 1 Minute
    const POST_PRODUCTS_CACHE_KEY = "post:%s:products";
    const USER_PRODUCT_DTO_CACHE_KEY = "user:%s:product:%s:dto";
    const USER_POST_LOCK_CACHE_KEY = "user:%s:post:%s:is_locked";
    const WPRM_PARENT_POST_CACHE_KEY = "post:%s:parent";
    const WPRM_PARENT_POST_META_KEY = "wprm_parent_post_id";
    const POST_REST_API_FIELD_NAME = 'memberpress';

    private static $instance;

    /**
     * @var CacheHelper
     */
    private $cache;

    /**
     * Initialize only once
     * @return void
     */
    public static function init() {
        if (!self::$instance instanceof MemberStatusApi) {
            self::$instance = new MemberPressFieldExtension();
            self::$instance->register_rest_fields();
        }
    }

    private function __construct() {
        $this->cache = new CacheHelper();
    }

    /**
     * Determines if the MemberPress plugin is enabled
     * @return bool
     */
    private function is_memberpress_enabled(): bool {
        return class_exists('MeprUtils')
            && class_exists('MeprProduct')
            && class_exists('MeprRule');
    }

    /**
     * Fetch a list of supported post types with the page or post capability type and which are exposed in the rest api
     * @return array
     */
    private function get_supported_post_types(): array {
        $post_types = get_post_types(array("show_in_rest" => true, "capability_type" => 'post'), 'objects');
        $page_types = get_post_types(array("show_in_rest" => true, "capability_type" => 'page'), 'objects');
        return array_merge($page_types, $post_types);
    }

    /**
     * Register REST API response field hooks for post types.
     * @return void
     */
    private function register_rest_fields() {
        if ($this->is_memberpress_enabled()) {
            $types = $this->get_supported_post_types();
            foreach ($types as $type) {
                /**
                 * Handle WordPress Recipe Maker post types differently if they are not public.
                 */
                if (str_starts_with($type->name, 'wprm_')) {
                    register_rest_field($type->name, self::POST_REST_API_FIELD_NAME, array('get_callback' => array($this, 'mepr_post_data_wprm')));
                } else {
                    register_rest_field($type->name, self::POST_REST_API_FIELD_NAME, array('get_callback' => array($this, 'mepr_post_data')));
                }
            }
        }
    }

    /**
     * Adds the "memberpress" field to the REST API response object
     * using the parent post set by WordPress Recipe Maker meta variable
     * @callback
     * @param $object
     * @return array
     */
    public function mepr_post_data_wprm($object) {
        $post_id = $this->wprm_parent_post_id($object['id']);
        return $this->create_memberpress_field($post_id);
    }

    /**
     * Adds the "memberpress" field to the REST API response object
     * @callback
     * @param $object
     * @return array
     */
    public function mepr_post_data($object) {
        return $this->create_memberpress_field($object['id']);
    }

    /**
     * Gets the parent post id for a WordPress Recipe Maker recipe.
     * @param $post_id
     * @return int
     */
    private function wprm_parent_post_id($post_id): int {
        $key = sprintf(self::WPRM_PARENT_POST_CACHE_KEY, $post_id);
        return $this->cache->cache_get_set($key, self::CACHE_GROUP, self::CACHE_DURATION,
            function () use ($post_id) {
                $parent_post_id = get_post_meta($post_id, self::WPRM_PARENT_POST_META_KEY, true);
                return empty($parent_post_id) ? $post_id : $parent_post_id;
            }
        );
    }

    /**
     * Determines if the post is locked by MemberPress rules
     * @param $post_id
     * @return bool
     */
    private function is_locked_post($post_id): bool {
        $key = sprintf(self::USER_POST_LOCK_CACHE_KEY, get_current_user_id(), $post_id);
        return (bool)$this->cache->cache_get_set($key, self::CACHE_GROUP, self::CACHE_DURATION,
            function () use ($post_id) {
                $post = get_post($post_id);
                return MeprRule::is_locked($post);
            }
        );
    }

    /**
     * Fetches the active membership(s)/product(s) to required to view a post
     * @param $post
     * @return array
     */
    private function products_for_post($post): array {
        $access_list = MeprRule::get_access_list($post);
        $products = array();
        foreach ($access_list as $access_key => $access_values) {
            if ($access_key == "membership") {
                foreach ($access_values as $product_id) {
                    $product = new MeprProduct($product_id);
                    $products[] = $product;
                }
            }
        }
        return $products;
    }

    /**
     * Fetches a list of MemberPress productIds required to view a post
     * @param $post_id
     * @return array
     */
    private function product_ids_for_post_id($post_id): array {
        $key = sprintf(self::POST_PRODUCTS_CACHE_KEY, $post_id);
        return $this->cache->cache_get_set($key, self::CACHE_GROUP, self::CACHE_DURATION,
            function () use ($post_id) {
                $post = get_post($post_id);
                $products = $this->products_for_post($post);
                $product_ids = array();
                foreach ($products as $product) {
                    $product_ids[] = $product->ID;
                }
                return $product_ids;
            }
        );
    }

    /**
     * Fetches MemberPress product data and generates a curated ProductDto Object;
     *
     * @param $product_id
     * @return ProductDto
     */
    private function get_product_dto($product_id): ProductDto {
        $user_id = get_current_user_id();
        $key = sprintf(self::USER_PRODUCT_DTO_CACHE_KEY, $user_id, $product_id);
        return $this->cache->cache_get_set($key, self::CACHE_GROUP, self::CACHE_DURATION,
            function () use ($user_id, $product_id) {
                $product = new MeprProduct($product_id);
                if ($user_id > 0) {
                    $user = new MeprUser($user_id);
                    return new ProductDto($product, $user);
                }
                return new ProductDto($product);
            }
        );
    }

    private function feature_include_products(): bool {
        return (bool)get_option(MEPR_MEMBER_STATUS_OPTION_PREFIX . "feature-include-products", true);
    }

    /**
     * Creates the value for the MemberPress response field
     * @param $post_id
     * @return array
     */
    private function create_memberpress_field($post_id): array {
        $data = array();
        $is_locked = $this->is_locked_post($post_id);
        $data['is_locked'] = $is_locked;

        if ($this->feature_include_products()) {
            $product_ids = $this->product_ids_for_post_id($post_id);
            foreach ($product_ids as $product_id) {
                $data['products'][] = $this->get_product_dto($product_id);
            }
        }

        return $data;
    }

}