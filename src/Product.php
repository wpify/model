<?php

declare( strict_types=1 );

namespace Wpify\Model;

use WC_Product;
use Wpify\Model\Attributes\AccessorObject;
use Wpify\Model\Attributes\PostTermsRelation;
use Wpify\Model\Attributes\ReadOnlyProperty;
use Wpify\Model\Attributes\ManyToOneRelation;

class Product extends Model {
	/**
	 * Product ID.
	 */
	#[AccessorObject]
	public int $id = 0;

	/**
	 * Product Parent ID.
	 */
	#[AccessorObject]
	public int $parent_id = 0;

	/**
	 * Product Type.
	 */
	#[AccessorObject]
	public string $type = '';

	/**
	 * Product Name.
	 */
	#[AccessorObject]
	public string $name = '';

	/**
	 * Product Stock Quantity.
	 */
	#[AccessorObject]
	public int $stock_quantity = 0;

	/**
	 * Product Stock Quantity.
	 */
	#[AccessorObject]
	public bool $is_in_stock;

	/**
	 * Product Stock Quantity.
	 */
	#[AccessorObject]
	public float $price;

	/**
	 * The post's author.
	 */
	#[ReadOnlyProperty]
	public WC_Product|null $wc_product = null;

	/**
	 * The product's permalink.
	 */
	#[AccessorObject]
	public string $permalink = '';

	/**
	 * The post's slug.
	 */
	#[AccessorObject]
	public string $slug = '';

	/**
	 * Product Category IDs.
	 */
	#[AccessorObject]
	public array $category_ids = array();

	/**
	 * Product SKU.
	 */
	#[AccessorObject]
	public string $sku = '';
	
	/**
	 * Featured image ID.
	 */
	#[AccessorObject]
	public int $image_id = 0;

	/**
	 * Featured image.
	 */
	#[ReadOnlyProperty]
	#[ManyToOneRelation( 'featured_image_id' )]
	public ?Attachment $featured_image = null;

	#[PostTermsRelation( target_entity: ProductCat::class )]
	public array $categories = array();


	/**
	 * Get WC Product.
	 *
	 * @return WC_Product|null
	 */
	public function get_wc_product(): WC_Product|null {
		return $this->source();
	}
}
