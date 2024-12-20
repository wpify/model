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
	 * Product Price.
	 */
	#[AccessorObject]
	public float $price;

	/**
	 * Regular Price.
	 */
	#[AccessorObject]
	public float $regular_price;

	/**
	 * Sale Price.
	 */
	#[AccessorObject]
	public float $sale_price;

	/**
	 * Manage stock.
	 */
	#[AccessorObject]
	public bool $manage_stock;

	/**
	 * Stock Status.
	 */
	#[AccessorObject]
	public string $stock_status;

	/**
	 * Catalog Visibility.
	 */
	#[AccessorObject]
	public string $catalog_visibility;

	/**
	 * Short Description
	 */
	#[AccessorObject]
	public string $short_description;

	/**
	 * Description
	 */
	#[AccessorObject]
	public string $description;

	/**
	 * Source Product.
	 */
	#[ReadOnlyProperty]
	public WC_Product|null $wc_product = null;

	/**
	 * The product's permalink.
	 */
	#[AccessorObject]
	#[ReadOnlyProperty]
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
	 * Gallery Image IDs.
	 */
	#[AccessorObject]
	public array $gallery_image_ids = array();

	/**
	 * Is product featured
	 */
	#[AccessorObject( key: 'featured' )]
	public bool $is_featured = false;

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

	/**
	 * Product VAT rate
	 * @param string $country_code
	 * @return float|null
	 */
	public function get_vat_rate(string $country_code) : ?float
	{
		$vat_rate = null;
		$product = $this->get_wc_product();
		if ($product->is_taxable()) {
			$vat_rates_data = \WC_Tax::find_rates(array('country' => $country_code, 'tax_class' => $product->get_tax_class()));
			if (!empty($vat_rates_data)) {
				$vat_rate = \reset($vat_rates_data)['rate'];
			}
			if (!$vat_rate) {
				$product_vat = (int) wc_get_price_including_tax($product) - (int) wc_get_price_excluding_tax($product);
				$vat_rate = \round($product_vat / ((int) wc_get_price_including_tax($product) / 100));
			}
		}
		return $vat_rate;
	}
}
