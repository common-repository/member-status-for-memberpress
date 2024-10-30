<?php

namespace Nakko\MeprMemberStatus\Dto;

use MeprUser;

/**
 * MemberPress Member status data transfer object
 */
class MemberStatusDto {
    public $user_id;
    public $full_name;
    public $is_active;
    public $enabled_products = array();

    public function __construct(MeprUser $meprUser, array $products) {
        $this->user_id = $meprUser->ID;
        $this->full_name = $meprUser->get_full_name();
        $this->is_active = $meprUser->is_active();
        foreach ($products as $product) {
            $this->enabled_products[] = new ProductDto($product, $meprUser);
        }
    }
}
