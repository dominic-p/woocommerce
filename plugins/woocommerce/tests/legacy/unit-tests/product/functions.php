<?php
/**
 * Class Functions.
 * @package WooCommerce\Tests\Product
 * @since 2.3
 */

use Automattic\WooCommerce\Enums\ProductStatus;
use Automattic\WooCommerce\Enums\ProductTaxStatus;
use Automattic\WooCommerce\Enums\ProductType;
use Automattic\WooCommerce\Enums\CatalogVisibility;
use Automattic\WooCommerce\Enums\ProductStockStatus;

/**
 * WC_Tests_Product_Functions class.
 */
class WC_Tests_Product_Functions extends WC_Unit_Test_Case {

	/**
	 * Tests wc_get_products().
	 *
	 * @since 3.0.0
	 */
	public function test_wc_get_products() {
		$test_cat_1 = wp_insert_term( 'Testing 1', 'product_cat' );
		$test_tag_1 = wp_insert_term( 'Tag 1', 'product_tag' );
		$test_tag_2 = wp_insert_term( 'Tag 2', 'product_tag' );
		$term_cat_1 = get_term_by( 'id', $test_cat_1['term_id'], 'product_cat' );
		$term_tag_1 = get_term_by( 'id', $test_tag_1['term_id'], 'product_tag' );
		$term_tag_2 = get_term_by( 'id', $test_tag_2['term_id'], 'product_tag' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_tag_ids( array( $test_tag_1['term_id'] ) );
		$product->set_category_ids( array( $test_cat_1['term_id'] ) );
		$product->set_sku( 'GET TEST SKU SIMPLE' );
		$product->save();

		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_category_ids( array( $test_cat_1['term_id'] ) );
		$product_2->save();

		$external = WC_Helper_Product::create_simple_product();
		$external->set_category_ids( array( $test_cat_1['term_id'] ) );
		$external->set_sku( 'GET TEST SKU EXTERNAL' );
		$external->save();

		$external_2 = WC_Helper_Product::create_simple_product();
		$external_2->set_tag_ids( array( $test_tag_2['term_id'] ) );
		$external_2->save();

		$grouped = WC_Helper_Product::create_grouped_product();

		$variation = WC_Helper_Product::create_variation_product();
		$variation->set_tag_ids( array( $test_tag_1['term_id'] ) );
		$variation->save();

		$draft = WC_Helper_Product::create_simple_product();
		$draft->set_status( ProductStatus::DRAFT );
		$draft->save();

		$this->assertCount( 9, wc_get_products( array( 'return' => 'ids' ) ) );

		// Test status.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'status' => ProductStatus::DRAFT,
			)
		);
		$this->assertEquals( array( $draft->get_id() ), $products );

		// Test type.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'type'   => ProductType::VARIATION,
			)
		);
		$this->assertCount( 6, $products );

		// Test parent.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'type'   => ProductType::VARIATION,
				'parent' => $variation->get_id(),
			)
		);
		$this->assertCount( 6, $products );

		// Test parent_exclude.
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'type'           => ProductType::VARIATION,
				'parent_exclude' => array( $variation->get_id() ),
			)
		);
		$this->assertCount( 0, $products );

		// Test skus.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'sku'    => 'GET TEST SKU',
			)
		);
		$this->assertCount( 2, $products );
		$this->assertContains( $product->get_id(), $products );
		$this->assertContains( $external->get_id(), $products );

		// Test categories.
		$products = wc_get_products(
			array(
				'return'   => 'ids',
				'category' => array( $term_cat_1->slug ),
			)
		);
		$this->assertCount( 3, $products );

		// Test tags.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'tag'    => array( $term_tag_1->slug ),
			)
		);
		$this->assertCount( 2, $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'tag'    => array( $term_tag_2->slug ),
			)
		);
		$this->assertCount( 1, $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'tag'    => array( $term_tag_1->slug, $term_tag_2->slug ),
			)
		);
		$this->assertCount( 3, $products );

		// Test limit.
		$products = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 5,
			)
		);
		$this->assertCount( 5, $products );

		// Test offset.
		$products        = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
			)
		);
		$products_offset = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
				'offset' => 2,
			)
		);
		$this->assertCount( 2, $products );
		$this->assertCount( 2, $products_offset );
		$this->assertNotEquals( $products, $products_offset );

		// Test page.
		$products_page_1 = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
			)
		);
		$products_page_2 = wc_get_products(
			array(
				'return' => 'ids',
				'limit'  => 2,
				'page'   => 2,
			)
		);
		$this->assertCount( 2, $products_page_1 );
		$this->assertCount( 2, $products_page_2 );
		$this->assertNotEquals( $products_page_1, $products_page_2 );

		// Test exclude.
		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'limit'   => 200,
				'exclude' => array( $product->get_id() ),
			)
		);
		$this->assertNotContains( $product->get_id(), $products );

		// Test include.
		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'include' => array( $product->get_id() ),
			)
		);
		$this->assertContains( $product->get_id(), $products );

		// Test order and orderby.
		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'order'   => 'ASC',
				'orderby' => 'ID',
				'limit'   => 2,
			)
		);
		$this->assertEquals( array( $product->get_id(), $product_2->get_id() ), $products );

		// Test paginate.
		$products = wc_get_products( array( 'paginate' => true ) );
		$this->assertGreaterThan( 0, $products->total );
		$this->assertGreaterThan( 0, $products->max_num_pages );
		$this->assertNotEmpty( $products->products );

		$product->delete( true );
		$product_2->delete( true );
		$external->delete( true );
		$external_2->delete( true );
		$grouped->delete( true );
		$draft->delete( true );
		$variation->delete( true );
	}

	/**
	 * @testdox We can search for products by category slugs and category IDs.
	 */
	public function test_searching_products_by_category() {
		$cat1      = wp_insert_term( 'Cat One', 'product_cat' );
		$cat1_term = get_term_by( 'id', $cat1['term_id'], 'product_cat' );
		$cat2      = wp_insert_term( 'Cat Two', 'product_cat' );
		$cat3      = wp_insert_term( 'Cat Three', 'product_cat' );

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_name( 'Product 1' );
		$product1->set_category_ids( array( $cat1['term_id'] ) );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_name( 'Product 2' );
		$product2->set_category_ids( array( $cat2['term_id'], $cat3['term_id'] ) );
		$product2->save();

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_name( 'Product 3' );
		$product3->save();

		// Search by category slug.
		$products = wc_get_products(
			array(
				'category' => $cat1_term->slug,
			)
		);
		$this->assertCount( 1, $products );
		$this->assertEquals( $product1->get_id(), $products[0]->get_id() );

		// Search by category ID.
		$products = wc_get_products(
			array(
				'product_category_id' => $cat2['term_id'],
			)
		);
		$this->assertCount( 1, $products );
		$this->assertEquals( $product2->get_id(), $products[0]->get_id() );

		// Search by multiple category IDs.
		$products = wc_get_products(
			array(
				'product_category_id' => array( $cat2['term_id'], $cat3['term_id'] ),
			)
		);
		$this->assertCount( 1, $products );
		$this->assertEquals( $product2->get_id(), $products[0]->get_id() );
	}

	/**
	 * @testdox We can search for products by tag slugs and tag IDs.
	 */
	public function test_searching_products_by_tag() {
		$tag1      = wp_insert_term( 'Tag One', 'product_tag' );
		$tag1_term = get_term_by( 'id', $tag1['term_id'], 'product_tag' );
		$tag2      = wp_insert_term( 'Tag Two', 'product_tag' );
		$tag3      = wp_insert_term( 'Tag Three', 'product_tag' );

		$product1 = WC_Helper_Product::create_simple_product();
		$product1->set_name( 'Product 1' );
		$product1->set_tag_ids( array( $tag1['term_id'] ) );
		$product1->save();

		$product2 = WC_Helper_Product::create_simple_product();
		$product2->set_name( 'Product 2' );
		$product2->set_tag_ids( array( $tag2['term_id'], $tag3['term_id'] ) );
		$product2->save();

		$product3 = WC_Helper_Product::create_simple_product();
		$product3->set_name( 'Product 3' );
		$product3->save();

		// Search by tag slug.
		$products = wc_get_products(
			array(
				'tag' => $tag1_term->slug,
			)
		);
		$this->assertCount( 1, $products );
		$this->assertEquals( $product1->get_id(), $products[0]->get_id() );

		// Search by tag ID.
		$products = wc_get_products(
			array(
				'product_tag_id' => $tag2['term_id'],
			)
		);
		$this->assertCount( 1, $products );
		$this->assertEquals( $product2->get_id(), $products[0]->get_id() );

		// Search by multiple tag IDs.
		$products = wc_get_products(
			array(
				'product_tag_id' => array( $tag2['term_id'], $tag3['term_id'] ),
			)
		);
		$this->assertCount( 1, $products );
		$this->assertEquals( $product2->get_id(), $products[0]->get_id() );
	}

	/**
	 * Tests wc_get_products() with dimension parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_dimensions() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_width( '12.5' );
		$product_1->set_height( '5' );
		$product_1->set_weight( '11.4' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_width( '10' );
		$product_2->set_height( '5' );
		$product_2->set_weight( '15' );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'width'  => 12.5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'height' => 5.0,
			)
		);
		sort( $products );
		$this->assertEquals( array( $product_1->get_id(), $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'weight' => 15,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with price parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_price() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_regular_price( '12.5' );
		$product_1->set_price( '12.5' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_regular_price( '14' );
		$product_2->set_sale_price( '12.5' );
		$product_2->set_price( '12.5' );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'        => 'ids',
				'regular_price' => 12.5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'sale_price' => 12.5,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return' => 'ids',
				'price'  => 12.5,
			)
		);
		sort( $products );
		$this->assertEquals( array( $product_1->get_id(), $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with total_sales parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_total_sales() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_total_sales( 4 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_total_sales( 2 );
		$product_2->save();

		$product_3 = new WC_Product_Simple();
		$product_3->save();

		$products = wc_get_products(
			array(
				'return'      => 'ids',
				'total_sales' => 4,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
		$product_3->delete( true );
	}

	/**
	 * Tests wc_get_products() with boolean parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_booleans() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_virtual( true );
		$product_1->set_downloadable( true );
		$product_1->set_featured( true );
		$product_1->set_sold_individually( true );
		$product_1->set_backorders( 'no' );
		$product_1->set_manage_stock( false );
		$product_1->set_reviews_allowed( true );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_virtual( false );
		$product_2->set_downloadable( false );
		$product_2->set_featured( false );
		$product_2->set_sold_individually( false );
		$product_2->set_backorders( 'notify' );
		$product_2->set_manage_stock( true );
		$product_2->set_reviews_allowed( false );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'virtual' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'  => 'ids',
				'virtual' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'downloadable' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'   => 'ids',
				'featured' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'   => 'ids',
				'featured' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'            => 'ids',
				'sold_individually' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'backorders' => 'notify',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'manage_stock' => true,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'reviews_allowed' => true,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'reviews_allowed' => false,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with visibility parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_visibility() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_catalog_visibility( CatalogVisibility::VISIBLE );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_catalog_visibility( CatalogVisibility::HIDDEN );
		$product_2->save();

		$product_3 = new WC_Product_Simple();
		$product_3->set_catalog_visibility( CatalogVisibility::SEARCH );
		$product_3->save();

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'visibility' => CatalogVisibility::VISIBLE,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'visibility' => CatalogVisibility::HIDDEN,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'visibility' => CatalogVisibility::SEARCH,
			)
		);
		sort( $products );
		$this->assertEquals( array( $product_1->get_id(), $product_3->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
		$product_3->delete( true );
	}

	/**
	 * Tests wc_get_products() with stock parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_stock() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_manage_stock( true );
		$product_1->set_stock_status( ProductStockStatus::IN_STOCK );
		$product_1->set_stock_quantity( 5 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_manage_stock( true );
		$product_2->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product_2->set_stock_quantity( 0 );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'stock_quantity' => 5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'stock_quantity' => 0,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'stock_status' => ProductStockStatus::OUT_OF_STOCK,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with tax parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_tax() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_tax_status( ProductTaxStatus::TAXABLE );
		$product_1->set_tax_class( 'reduced-rate' );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_tax_status( ProductTaxStatus::NONE );
		$product_2->set_tax_class( 'standard' );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'tax_status' => ProductTaxStatus::TAXABLE,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'     => 'ids',
				'tax_status' => ProductTaxStatus::NONE,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'    => 'ids',
				'tax_class' => 'reduced-rate',
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with shipping parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_shipping_class() {
		$shipping_class_1 = wp_insert_term( 'Bulky', 'product_shipping_class' );
		$shipping_class_2 = wp_insert_term( 'Standard', 'product_shipping_class' );

		$product_1 = new WC_Product_Simple();
		$product_1->set_shipping_class_id( $shipping_class_1['term_id'] );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_shipping_class_id( $shipping_class_2['term_id'] );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'shipping_class' => 'bulky',
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'shipping_class' => 'standard',
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Tests wc_get_products() with download parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_download() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_downloadable( true );
		$product_1->set_download_limit( 5 );
		$product_1->set_download_expiry( 90 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_downloadable( true );
		$product_2->set_download_limit( -1 );
		$product_2->set_download_expiry( -1 );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'download_limit' => 5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'download_limit' => -1,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'download_expiry' => 90,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'          => 'ids',
				'download_expiry' => -1,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );
	}

	/**
	 * Tests wc_get_products() with reviews parameters.
	 *
	 * @since 3.2.0
	 */
	public function test_wc_get_products_reviews() {
		$product_1 = new WC_Product_Simple();
		$product_1->set_average_rating( 5.0 );
		$product_1->set_review_count( 5 );
		$product_1->save();

		$product_2 = new WC_Product_Simple();
		$product_2->set_average_rating( 3.0 );
		$product_2->set_review_count( 1 );
		$product_2->save();

		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'average_rating' => 5.0,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'         => 'ids',
				'average_rating' => 3.0,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'review_count' => 5,
			)
		);
		$this->assertEquals( array( $product_1->get_id() ), $products );
		$products = wc_get_products(
			array(
				'return'       => 'ids',
				'review_count' => 1,
			)
		);
		$this->assertEquals( array( $product_2->get_id() ), $products );

		$product_1->delete( true );
		$product_2->delete( true );
	}

	/**
	 * Test wc_get_product().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product() {
		$product = WC_Helper_Product::create_simple_product();

		$product_copy = wc_get_product( $product->get_id() );

		$this->assertEquals( $product->get_id(), $product_copy->get_id() );
	}

	/**
	 * Test wc_get_product_object().
	 *
	 * @since 3.9.0
	 */
	public function test_wc_get_product_object() {
		$this->assertInstanceOf( 'WC_Product_Simple', wc_get_product_object( ProductType::SIMPLE ) );
		$this->assertInstanceOf( 'WC_Product_Grouped', wc_get_product_object( ProductType::GROUPED ) );
		$this->assertInstanceOf( 'WC_Product_External', wc_get_product_object( ProductType::EXTERNAL ) );
		$this->assertInstanceOf( 'WC_Product_Variable', wc_get_product_object( ProductType::VARIABLE ) );
		$this->assertInstanceOf( 'WC_Product_Variation', wc_get_product_object( ProductType::VARIATION ) );

		// Test incorrect type.
		$this->assertInstanceOf( 'WC_Product_Simple', wc_get_product_object( 'foo+bar' ) );
	}

	/**
	 * Test wc_update_product_stock().
	 *
	 * @since 2.3
	 */
	public function test_wc_update_product_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->save();

		wc_update_product_stock( $product->get_id(), 5 );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 5, $product->get_stock_quantity() );
	}

	/**
	 * Test: test_wc_update_product_stock_increase_decrease.
	 */
	public function test_wc_update_product_stock_increase_decrease() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_manage_stock( true );
		$product->save();
		wc_update_product_stock( $product->get_id(), 5 );

		$new_value = wc_update_product_stock( $product->get_id(), 1, 'increase' );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 6, $product->get_stock_quantity() );
		$this->assertEquals( 6, $new_value );

		$new_value = wc_update_product_stock( $product->get_id(), 1, 'decrease' );

		$product = new WC_Product_Simple( $product->get_id() );
		$this->assertEquals( 5, $product->get_stock_quantity() );
		$this->assertEquals( 5, $new_value );
	}

	/**
	 * Test: test_wc_update_product_stock_should_return_false_if_invalid_product.
	 */
	public function test_wc_update_product_stock_should_return_false_if_invalid_product() {
		$this->assertFalse( wc_update_product_stock( 1 ) );
	}

	/**
	 * Test: test_wc_update_product_stock_should_return_stock_quantity_if_no_stock_quantity_given.
	 */
	public function test_wc_update_product_stock_should_return_stock_quantity_if_no_stock_quantity_given() {
		$stock_quantity = 5;
		$product        = WC_Helper_Product::create_simple_product();
		$product->set_stock_quantity( $stock_quantity );
		$product->set_manage_stock( true );
		$product->save();

		$this->assertEquals( $stock_quantity, wc_update_product_stock( $product ) );
	}

	/**
	 * Test: test_wc_update_product_stock_should_return_null_if_not_managing_stock.
	 */
	public function test_wc_update_product_stock_should_return_null_if_not_managing_stock() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_stock_quantity( 5 );
		$product->save();

		$this->assertNull( wc_update_product_stock( $product, 3 ) );
	}

	/**
	 * Test wc_update_product_stock_status().
	 */
	public function test_wc_update_product_stock_status_should_change_stock_status() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( ProductStockStatus::IN_STOCK, $product->get_stock_status() );

		wc_update_product_stock_status( $product->get_id(), ProductStockStatus::OUT_OF_STOCK );
		$product = wc_get_product( $product->get_id() );

		$this->assertEquals( ProductStockStatus::OUT_OF_STOCK, $product->get_stock_status() );
	}

	/**
	 * Test wc_delete_product_transients().
	 *
	 * @since 2.4
	 */
	public function test_wc_delete_product_transients() {
		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product->save();

		wc_get_product_ids_on_sale();  // Creates the transient for on sale products.
		wc_get_featured_product_ids(); // Creates the transient for featured products.

		wc_delete_product_transients();

		$this->assertFalse( get_transient( 'wc_products_onsale' ) );
		$this->assertFalse( get_transient( 'wc_featured_products' ) );
	}

	/**
	 * Test wc_get_product_ids_on_sale().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_product_ids_on_sale() {
		$this->assertEquals( array(), wc_get_product_ids_on_sale() );

		delete_transient( 'wc_products_onsale' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_regular_price( 10 );
		$product->set_sale_price( 5 );
		$product->save();

		$this->assertEquals( array( $product->get_id() ), wc_get_product_ids_on_sale() );
	}

	/**
	 * Test wc_get_featured_product_ids().
	 *
	 * @since 2.4
	 */
	public function test_wc_get_featured_product_ids() {
		$this->assertEquals( array(), wc_get_featured_product_ids() );

		delete_transient( 'wc_featured_products' );

		$product = WC_Helper_Product::create_simple_product();
		$product->set_featured( true );
		$product->save();

		$this->assertEquals( array( $product->get_id() ), wc_get_featured_product_ids() );
	}

	/**
	 * Test wc_placeholder_img().
	 *
	 * @since 2.4
	 */
	public function test_wc_placeholder_img() {
		$this->assertTrue( (bool) strstr( wc_placeholder_img(), wc_placeholder_img_src() ) );

		// Test custom class attribute is honoured.
		$attr = array( 'class' => 'custom-class' );
		$this->assertStringContainsString( 'class="custom-class"', wc_placeholder_img( 'woocommerce_thumbnail', $attr ) );
	}

	/**
	 * Test wc_get_product_types().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_types() {
		$product_types = (array) apply_filters(
			'product_type_selector',
			array(
				ProductType::SIMPLE   => 'Simple product',
				ProductType::GROUPED  => 'Grouped product',
				ProductType::EXTERNAL => 'External/Affiliate product',
				ProductType::VARIABLE => 'Variable product',
			)
		);

		$this->assertEquals( $product_types, wc_get_product_types() );
	}

	public function test_wc_product_has_unique_sku() {
		$this->expectException( WC_Data_Exception::class );

		$product_1 = WC_Helper_Product::create_simple_product();

		$this->assertTrue( wc_product_has_unique_sku( $product_1->get_id(), $product_1->get_sku() ) );

		$product_2 = WC_Helper_Product::create_simple_product();
		$product_2->set_sku( $product_1->get_sku() );
	}

	/**
	 * Test wc_get_product_id_by_sku().
	 *
	 * @since 2.3
	 */
	public function test_wc_get_product_id_by_sku() {
		$product = WC_Helper_Product::create_simple_product();

		$this->assertEquals( $product->get_id(), wc_get_product_id_by_sku( $product->get_sku() ) );
	}

	/**
	 * Test wc_get_min_max_price_meta_query()
	 *
	 * @expectedDeprecated wc_get_min_max_price_meta_query()
	 */
	public function test_wc_get_min_max_price_meta_query() {
		$meta_query = wc_get_min_max_price_meta_query(
			array(
				'min_price' => 10,
				'max_price' => 100,
			)
		);

		$this->assertEquals(
			array(
				'key'     => '_price',
				'value'   => array( 10, 100 ),
				'compare' => 'BETWEEN',
				'type'    => 'DECIMAL(10,2)',
			),
			$meta_query
		);
	}

	/**
	 * Test wc_product_force_unique_sku
	 *
	 * @since 3.0.0
	 */
	public function test_wc_product_force_unique_sku() {
		$product_1 = WC_Helper_Product::create_simple_product();
		$product_2 = WC_Helper_Product::create_simple_product();
		$product_3 = WC_Helper_Product::create_simple_product();
		$product_4 = WC_Helper_Product::create_simple_product();

		$product_1->set_sku( 'some-custom-sku' );
		$product_2->set_sku( 'another-custom-sku' );
		$product_3->set_sku( 'another-custom-sku-1' );
		$product_4->set_sku( 'some-custom-sku' );

		$product_1_id = $product_1->save();
		$product_2_id = $product_2->save();
		$product_3_id = $product_3->save();
		$product_4_id = $product_4->save();

		wc_product_force_unique_sku( $product_4_id );
		$product_4 = wc_get_product( $product_4_id );
		$this->assertEquals( $product_4->get_sku( 'edit' ), 'some-custom-sku-1' );

		$product_1->delete( true );
		$product_2->delete( true );
		$product_3->delete( true );
		$product_4->delete( true );
	}

	/**
	 * Test wc_is_attribute_in_product_name
	 *
	 * @since 3.0.2
	 */
	public function test_wc_is_attribute_in_product_name() {
		$this->assertTrue( wc_is_attribute_in_product_name( 'L', 'Product &ndash; L' ) );
		$this->assertTrue( wc_is_attribute_in_product_name( 'Two Words', 'Product &ndash; L, Two Words' ) );
		$this->assertTrue( wc_is_attribute_in_product_name( 'Blue', 'Product &ndash; The Cool One &ndash; Blue, Large' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'L', 'Product' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'L', 'Product L Thing' ) );
		$this->assertFalse( wc_is_attribute_in_product_name( 'Blue', 'Product &ndash; Large, Blueish' ) );
	}

	/**
	 * Test: test_wc_get_attachment_image_attributes.
	 */
	public function test_wc_get_attachment_image_attributes() {
		$image_attr = array(
			'src'    => 'https://wc.local/wp-content/uploads/2018/02/single-1-250x250.jpg',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => 'https://wc.local/wp-content/uploads/2018/02/single-1-250x250.jpg 250w, https://wc.local/wp-content/uploads/2018/02/single-1-350x350.jpg 350w, https://wc.local/wp-content/uploads/2018/02/single-1-150x150.jpg 150w, https://wc.local/wp-content/uploads/2018/02/single-1-300x300.jpg 300w, https://wc.local/wp-content/uploads/2018/02/single-1-768x768.jpg 768w, https://wc.local/wp-content/uploads/2018/02/single-1-100x100.jpg 100w, https://wc.local/wp-content/uploads/2018/02/single-1.jpg 800w',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		// Test regular image attr.
		$this->assertEquals( $image_attr, wc_get_attachment_image_attributes( $image_attr ) );

		$image_attr = array(
			'src'    => '',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => '',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		// Test blank src image attr, this is used in lazy loading.
		$this->assertEquals( $image_attr, wc_get_attachment_image_attributes( $image_attr ) );

		$image_attr    = array(
			'src'    => 'https://wc.local/wp-content/woocommerce_uploads/my-image.jpg',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => 'https://wc.local/wp-content/woocommerce_uploads/my-image-250x250.jpg 250w, https://wc.local/wp-content/woocommerce_uploads/my-image-350x350 350w',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		$expected_attr = array(
			'src'    => WC()->plugin_url() . '/assets/images/placeholder.webp',
			'class'  => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail',
			'alt'    => '',
			'srcset' => '',
			'sizes'  => '(max-width: 250px) 100vw, 250px',
		);
		// Test image hosted in woocommerce_uploads which is not allowed, think shops selling photos.
		$this->assertEquals( $expected_attr, wc_get_attachment_image_attributes( $image_attr ) );

		unset( $image_attr, $expected_attr );
	}

	/**
	 * Test wc_get_product_stock_status_options().
	 *
	 * @since 3.6.0
	 */
	public function test_wc_get_product_stock_status_options() {
		$status_options = (array) apply_filters(
			'woocommerce_product_stock_status_options',
			array(
				ProductStockStatus::IN_STOCK     => 'In stock',
				ProductStockStatus::OUT_OF_STOCK => 'Out of stock',
				ProductStockStatus::ON_BACKORDER => 'On backorder',
			)
		);

		$this->assertEquals( $status_options, wc_get_product_stock_status_options() );
	}

	/**
	 * Tests `wc_get_price_to_display()` and its `display_context` argument.
	 */
	public function test_wc_get_price_to_display() {
		// Enable taxes.
		update_option( 'woocommerce_calc_taxes', 'yes' );
		update_option( 'woocommerce_prices_include_tax', 'no' );

		$customer_location = WC_Tax::get_tax_location();

		$tax_rate = array(
			'tax_rate_country'  => $customer_location[0],
			'tax_rate_state'    => '',
			'tax_rate'          => '20.0000',
			'tax_rate_name'     => 'VAT',
			'tax_rate_priority' => '1',
			'tax_rate_compound' => '0',
			'tax_rate_shipping' => '1',
			'tax_rate_order'    => '1',
			'tax_rate_class'    => '',
		);

		WC_Tax::_insert_tax_rate( $tax_rate );

		$product = new WC_Product_Simple();

		$product->set_regular_price( '100' );

		// Display price included taxes at shop and cart.
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price'           => 100,
				'qty'             => 1,
				'display_context' => 'shop',
			)
		);

		$this->assertEquals( 120, $price_shop );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price'           => 100,
				'qty'             => 1,
				'display_context' => 'cart',
			)
		);

		$this->assertEquals( 120, $price_shop );

		// Display price included taxes only at shop.
		update_option( 'woocommerce_tax_display_cart', 'excl' );
		update_option( 'woocommerce_tax_display_shop', 'incl' );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price' => 100,
				'qty'   => 1,
			)
		);

		$this->assertEquals( 120, $price_shop );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price'           => 100,
				'qty'             => 1,
				'display_context' => 'cart',
			)
		);

		$this->assertEquals( 100, $price_shop );

		// Display price included taxes only at cart.
		update_option( 'woocommerce_tax_display_cart', 'incl' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price'           => 100,
				'qty'             => 1,
				'display_context' => 'shop',
			)
		);

		$this->assertEquals( 100, $price_shop );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price'           => 100,
				'qty'             => 1,
				'display_context' => 'cart',
			)
		);

		$this->assertEquals( 120, $price_shop );

		// Display price excluded taxes at shop and cart.
		update_option( 'woocommerce_tax_display_cart', 'excl' );
		update_option( 'woocommerce_tax_display_shop', 'excl' );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price' => 100,
				'qty'   => 1,
			)
		);

		$this->assertEquals( 100, $price_shop );

		$price_shop = wc_get_price_to_display(
			$product,
			array(
				'price'           => 100,
				'qty'             => 1,
				'display_context' => 'cart',
			)
		);

		$this->assertEquals( 100, $price_shop );
	}
}
