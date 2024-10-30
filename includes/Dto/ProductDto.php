<?php

namespace Nakko\MeprMemberStatus\Dto;

use MeprProduct;
use MeprUser;

/**
 * MemberPress Product data transfer object
 */
class ProductDto {

    public $id;
    public $title;
    public $name;
    public $url;
    public $group_url;
    public $can_you_buy_me;
    public $is_subscribed;

    public function __construct(MeprProduct $meprProduct, MeprUser $meprUser = null) {
        $this->name = $meprProduct->post_name;
        $this->id = $meprProduct->ID;
        $this->title = $meprProduct->post_title;
        $this->url = $meprProduct->url();
        $this->group_url = $meprProduct->group_url();
        $this->can_you_buy_me = $meprProduct->can_you_buy_me();
        if(isset($meprUser)) {
            $this->is_subscribed = $meprUser->is_already_subscribed_to($meprProduct->ID);
        }
    }

}