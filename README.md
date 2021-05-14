# [WIP] WPify Model

Model and Repository library for WordPress

## Todo

* Add abstract and default models for:
  * Post
	* Category
	* Tag
  * Page
  * Attachment
  * Navigation Menu
  * Reusable Block
  * User
  * Comment
  * WooCommerce Product
	* Product Category
	* Product Tag
	* Product Variation
	* Product Visibility
  	* Product Attribute 
  * WooCommerce Order
	* Order Status
	* Order Refund
  * WooCommerce Coupon
* Add abstract and default repositories.
  * Repository for posts, taxonomies, users, menus
* Solve how to query related models without sacrificing speed (categories for post, posts for categories, etc).
* Data storage to prevent loading same data multiple times.
