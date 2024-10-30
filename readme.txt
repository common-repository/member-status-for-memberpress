=== Member status API for MemberPress ===
Contributors: nakkowp, j3roen
Tags: memberpress, recipemaker, wprm, rest, api
Requires at least: 4.7
Tested up to: 6.5
Stable tag: 1.1.3
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This plugin extends the WordPress REST API with MemberPress membership and product data within the current user context.

== Description ==

This plugin allows the MemberPress membership status of the current authenticated user to be exposed via the Wordpress REST API:

GET /wp-json/mp-member-status/v1/me

Example response:

    {
      "user_id": 2,
      "full_name": "John Doe",
      "is_active": true,
      "enabled_products": [
        {
          "id": 10,
          "title": "Premium Membership",
          "name": "premium-membership",
          "url": "https://example.com/register/premium-membership/",
        }
      ]
    }

Additionally, MemberPress access information is added to all post endpoints, the post content or access itself is not modified by this plugin:

Example post response excerpt:

    memberpress: {
                   "is_locked": true,
                   "products": [
                     {
                       "id": 10,
                       "title": "Premium Membership",
                       "name": "premium-membership",
                       "url": "https://example.com/register/premium-membership/",
                       "can_you_buy_me": true,
                       "is_subscribed": false
                     }
                   ]
    },


== Features ==

- Creates a REST API endpoint to fetch the MemberPress membership status of the current user.
- Allows to list the active products for the current user.
- Adds the Memberpress "is_locked" status field to all available post types in the REST API
- Adds a list of possible Memberpress Products required to unlock posts.
- Support for WordPress Recipe Maker's and MemberPress rules set for the parent Post.

== Installation ==

Install the MpMemberStatus plugin and activate it. No further configuration is necessary.