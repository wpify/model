# Public API test coverage checklist

This file is the done bar for [Full test coverage for wpify/model](https://github.com/wpify/model/issues/10).
Every public method declared in `src/` gets a checkbox: at least one happy-path test, plus a test for each
listed failure mode. The final sweep ([#27](https://github.com/wpify/model/issues/27)) audits this file —
check an item only when its test exists and passes (or is skipped with a linked bug issue, per the map's
bug protocol).

Grouping follows the coverage tickets on the map. Inherited-but-not-overridden methods carry no separate
checkbox — they are covered by the declaring class. Interfaces (`src/Interfaces/`) declare no testable code
of their own; their methods appear under the implementing classes. Trivial promoted-property constructors
are listed and get checked off by whichever test exercises the class.

Attribute classes live under the ticket that can naturally exercise them: `Column` under the custom-table
ticket, `MenuItemsRelation` under menus, `OrderItemsRelation` under WooCommerce orders, everything else
under the core ticket.

## Coverage: Model base, Manager, and attributes ([#18](https://github.com/wpify/model/issues/18))

### Manager

- [x] `Manager::__construct( ...$dependencies )`
- [x] `Manager::register_repository()`
- [x] `Manager::get_model_repository()`
- [x] `Manager::get_model_repository()` — throws `RepositoryNotFoundException` for an unregistered model
- [x] `Manager::get_repository()`
- [x] `Manager::get_repositories()`

### Model (abstract base)

- [x] `Model::__construct()`
- [x] `Model::source()`
- [x] `Model::props()`
- [x] `Model::manager()`
- [x] `Model::reflection()`
- [x] `Model::refresh()`
- [x] `Model::__get()`
- [x] `Model::__get()` — throws `PropertyNotDefinedException` for an undefined property
- [x] `Model::__set()`
- [x] `Model::__set()` — throws `ReadOnlyPropertyException` for a `#[ReadOnlyProperty]` property
- [x] `Model::to_array()`
- [x] `Model::getIterator()`
- [x] `Model::offsetSet()`
- [x] `Model::offsetExists()`
- [x] `Model::offsetUnset()`
- [x] `Model::offsetGet()`
- [x] `Model::__isset()`
- [x] `Model::__unset()`

### Repository (abstract base)

- [x] `Repository::manager()`
- [x] `Repository::manager()` — throws `RepositoryNotInitialized` when called before `register_repository()`
- [x] `Repository::resolve_property()`
- [x] `Repository::maybe_convert_to_type()`
- [x] `Repository::create()` — base implementation (via any repository that does not override it, e.g. `TermRepository`)

### Attributes

- [x] `AccessorObject::__construct()`
- [x] `AccessorObject::get()`
- [x] `AccessorObject::set()`
- [x] `AliasOf::__construct()`
- [x] `AliasOf::get()`
- [x] `ChildPostsRelation::get()`
- [x] `ChildPostsRelation::get()` — throws `RepositoryMethodNotImplementedException` when the repository lacks `find_child_posts_of()`
- [x] `ChildTermsRelation::get()`
- [x] `ChildTermsRelation::get()` — throws `RepositoryMethodNotImplementedException` when the repository lacks `find_child_terms_of()`
- [x] `IdsRelation::__construct()`
- [x] `IdsRelation::get()`
- [x] `ManyToOneRelation::__construct()`
- [x] `ManyToOneRelation::get()`
- [x] `ManyToOneRelation::get()` — throws `RepositoryNotFoundException` when no repository handles the target model
- [x] `Meta::__construct()`
- [x] `Meta::get()`
- [x] `Meta::set()`
- [x] `PostTermRelation::__construct()`
- [x] `PostTermRelation::get()`
- [x] `PostTermRelation::persist()`
- [x] `PostTermsRelation::__construct()`
- [x] `PostTermsRelation::get()`
- [x] `PostTermsRelation::persist()`
- [x] `ReadOnlyProperty` — no methods; behavior covered by the `Model::__set()` failure-mode test above
- [x] `SourceObject::__construct()`
- [x] `SourceObject::get()`
- [x] `TermPostsRelation::__construct()`
- [x] `TermPostsRelation::get()`
- [x] `TopLevelPostParentRelation::__construct()`
- [x] `TopLevelPostParentRelation::get()`
- [x] `TopLevelTermParentRelation::__construct()`
- [x] `TopLevelTermParentRelation::get()`

### ModelException (base)

- [x] `ModelException::__construct()` — via any thrown subclass
- [x] `ModelException::get_wp_error()` — returns the `WP_Error` when one is attached (only `TermRepository::save()` passes one; see [#20](https://github.com/wpify/model/issues/20)) and `null` otherwise

## Coverage: posts, pages, and attachments ([#19](https://github.com/wpify/model/issues/19))

### PostRepository

- [x] `PostRepository::model()`
- [x] `PostRepository::get()`
- [x] `PostRepository::post_types()`
- [x] `PostRepository::create()`
- [x] `PostRepository::create()` — throws `CouldNotSaveModelException` when the repository declares multiple post types
- [x] `PostRepository::create()` — throws `CouldNotSaveModelException` when the repository declares no post types
- [x] `PostRepository::save()`
- [x] `PostRepository::save()` — throws `CouldNotSaveModelException` when `wp_insert_post`/`wp_update_post` returns `WP_Error`
- [x] `PostRepository::delete()`
- [x] `PostRepository::get_paginate_links()`
- [x] `PostRepository::get_pagination()`
- [x] `PostRepository::find()`
- [x] `PostRepository::find_without_pagination()`
- [x] `PostRepository::find_paginated()`
- [x] `PostRepository::find_all()`
- [x] `PostRepository::find_published()`
- [x] `PostRepository::find_all_by_term()`
- [x] `PostRepository::find_all_by_term()` — throws `IncorrectRepositoryException` when the term model's repository has no `taxonomy()` method
- [x] `PostRepository::find_child_posts_of()`
- [x] `PostRepository::find_by_ids()`
- [x] `PostRepository::assign_post_to_term()`

### Post

- [x] `Post::get_permalink()`
- [x] `Post::get_featured_image_id()`
- [x] `Post::persist_featured_image_id()`
- [x] `Post::get_rendered_excerpt()`

### PageRepository

- [x] `PageRepository::model()`
- [x] `PageRepository::post_types()`

### Page

_No declared public methods — covered via `Post` and `PageRepository`._

### AttachmentRepository

- [x] `AttachmentRepository::model()`
- [x] `AttachmentRepository::post_types()`

### Attachment

- [x] `Attachment::get_url()`

## Coverage: terms and taxonomies ([#20](https://github.com/wpify/model/issues/20))

### TermRepository

- [x] `TermRepository::model()`
- [x] `TermRepository::taxonomy()`
- [x] `TermRepository::get()`
- [x] `TermRepository::save()`
- [x] `TermRepository::save()` — throws `CouldNotSaveModelException` (with the `WP_Error` attached, readable via `get_wp_error()`) when `wp_insert_term`/`wp_update_term` fails
- [x] `TermRepository::delete()`
- [x] `TermRepository::find()`
- [x] `TermRepository::find_all()`
- [x] `TermRepository::find_not_empty()`
- [x] `TermRepository::find_children_of()`
- [x] `TermRepository::find_terms_of_post()`
- [x] `TermRepository::find_child_terms_of()`
- [x] `TermRepository::find_by_ids()`

### Term

- [x] `Term::get_permalink()`

### CategoryRepository

- [x] `CategoryRepository::model()`
- [x] `CategoryRepository::taxonomy()`

### Category

_No declared public methods — covered via `Term` and `CategoryRepository`._

### PostTagRepository

- [x] `PostTagRepository::model()`
- [x] `PostTagRepository::taxonomy()`

### PostTag

_No declared public methods — covered via `Term` and `PostTagRepository`._

## Coverage: users and comments ([#21](https://github.com/wpify/model/issues/21))

### UserRepository

- [x] `UserRepository::model()`
- [x] `UserRepository::get()`
- [x] `UserRepository::get_current()`
- [x] `UserRepository::save()`
- [x] `UserRepository::save()` — throws `CouldNotSaveModelException` when `wp_insert_user`/`wp_update_user` returns `WP_Error`
- [x] `UserRepository::delete()`
- [x] `UserRepository::find()`
- [x] `UserRepository::find_all()`
- [x] `UserRepository::find_by_ids()`

### User

_No declared public methods — covered via `Model` and `UserRepository`._

### CommentRepository

- [x] `CommentRepository::model()`
- [x] `CommentRepository::get()`
- [x] `CommentRepository::save()`
- [x] `CommentRepository::save()` — throws `CouldNotSaveModelException` when `wp_insert_comment`/`wp_update_comment` fails
- [x] `CommentRepository::delete()`
- [x] `CommentRepository::find()`
- [x] `CommentRepository::find_by_post_id()`
- [x] `CommentRepository::find_by_ids()`
- [x] `CommentRepository::find_all()`

### Comment

_No declared public methods — covered via `Model` and `CommentRepository`._

## Coverage: menus and menu items ([#22](https://github.com/wpify/model/issues/22))

Known related bug: [Menu error when not assigned](https://github.com/wpify/model/issues/1) — a test exposing
it gets skipped with that link, per the bug protocol.

### MenuRepository

- [x] `MenuRepository::model()`
- [x] `MenuRepository::taxonomy()`
- [x] `MenuRepository::get()` — by id/slug and by theme menu location
- [x] `MenuRepository::items()` — both `RETURN_OBJECTS` and flat return modes

### Menu

- [x] `Menu::to_array()`

### MenuItemRepository

- [x] `MenuItemRepository::model()`
- [x] `MenuItemRepository::post_types()`

### MenuItem

- [x] `MenuItem::get_is_current()`
- [x] `MenuItem::get_is_highlighted()`

### Attributes

- [x] `MenuItemsRelation::__construct()`
- [x] `MenuItemsRelation::get()`
- [x] `MenuItemsRelation::sort_items()`

## Coverage: custom table repository ([#23](https://github.com/wpify/model/issues/23))

### CustomTableRepository

- [x] `CustomTableRepository::__construct()`
- [x] `CustomTableRepository::primary_key()`
- [x] `CustomTableRepository::primary_key()` — throws `PrimaryKeyException` when the model does not declare exactly one primary-key column
- [x] `CustomTableRepository::prefixed_table_name()`
- [x] `CustomTableRepository::migrate()`
- [x] `CustomTableRepository::columns()`
- [x] `CustomTableRepository::query_single()`
- [x] `CustomTableRepository::get()`
- [x] `CustomTableRepository::get()` — throws `PrimaryKeyException` when an array source lacks the primary key
- [x] `CustomTableRepository::drop_table()`
- [x] `CustomTableRepository::save()` — insert and update paths
- [x] `CustomTableRepository::save()` — throws `SqlException` on a database error
- [x] `CustomTableRepository::delete()`
- [x] `CustomTableRepository::delete()` — throws `SqlException` on a database error
- [x] `CustomTableRepository::find()`
- [x] `CustomTableRepository::find()` — throws `SqlException` when an array condition does not have exactly two values
- [x] `CustomTableRepository::find()` — throws `SqlException` on a database error
- [x] `CustomTableRepository::find_by_ids()`
- [x] `CustomTableRepository::find_all()`

### Attributes

- [x] `Column::__construct()`
- [x] `Column::create_column_sql()`
- [x] `Column::get()`

## Coverage: WooCommerce products ([#24](https://github.com/wpify/model/issues/24))

### ProductRepository

- [x] `ProductRepository::model()`
- [x] `ProductRepository::get()`
- [x] `ProductRepository::save()`
- [ ] `ProductRepository::save()` — throws `CouldNotSaveModelException` when the WooCommerce save fails — **unreachable branch**: `WC_Product::save()` never returns `WP_Error` (WC throws `WC_Data_Exception` instead); flagged for the final sweep
- [x] `ProductRepository::delete()`
- [x] `ProductRepository::find()`
- [x] `ProductRepository::tax_query_filter()`
- [x] `ProductRepository::find_all()`
- [x] `ProductRepository::find_by_ids()`
- [x] `ProductRepository::find_all_by_term()`
- [x] `ProductRepository::find_all_by_term()` — throws `IncorrectRepositoryException` when the term model's repository has no `taxonomy()` method

### Product

- [x] `Product::get_wc_product()`
- [x] `Product::get_vat_rate()`

### ProductCatRepository

- [x] `ProductCatRepository::model()`
- [x] `ProductCatRepository::taxonomy()`

### ProductCat

_No declared public methods — covered via `Term` and `ProductCatRepository`._

## Coverage: WooCommerce orders ([#25](https://github.com/wpify/model/issues/25))

Orders are tested against HPOS only (env-flag escape hatch for legacy), per
[Research: WooCommerce in the wp-env test suite](https://github.com/wpify/model/issues/12).

### OrderRepository

- [x] `OrderRepository::model()`
- [x] `OrderRepository::get()`
- [x] `OrderRepository::save()`
- [ ] `OrderRepository::save()` — throws `CouldNotSaveModelException` when the WooCommerce save fails — **unreachable branch**: `WC_Order::save()` never returns `WP_Error`
- [x] `OrderRepository::delete()`
- [x] `OrderRepository::find()`
- [x] `OrderRepository::find_all()`
- [x] `OrderRepository::find_by_ids()`

### Order

- [x] `Order::get_wc_order()`
- [x] `Order::get_weight()`
- [x] `Order::has_shipping_method()`
- [x] `Order::get_items()`

### OrderItemRepository

- [x] `OrderItemRepository::model()`
- [x] `OrderItemRepository::get()`
- [x] `OrderItemRepository::create()`
- [x] `OrderItemRepository::save()`
- [ ] `OrderItemRepository::save()` — throws `CouldNotSaveModelException` when the WooCommerce save fails — **unreachable branch**: `WC_Order_Item::save()` never returns `WP_Error`
- [x] `OrderItemRepository::delete()`
- [x] `OrderItemRepository::find()`
- [x] `OrderItemRepository::find_all()`
- [x] `OrderItemRepository::find_by_ids()`

### OrderItem

- [x] `OrderItem::get_wc_order_item()`
- [x] `OrderItem::get_unit_price_tax_included()`
- [x] `OrderItem::get_unit_price()`
- [x] `OrderItem::get_unit_price_tax_excluded()`
- [x] `OrderItem::get_vat_rate()`
- [x] `OrderItem::get_vat_rate_id()`

### Order item subtypes

- [x] `OrderItemLineRepository::model()`
- [x] `OrderItemFeeRepository::model()`
- [x] `OrderItemShippingRepository::model()`

`OrderItemLine`, `OrderItemFee`, and `OrderItemShipping` declare no public methods — covered via `OrderItem`
and their repositories.

### Attributes

- [x] `OrderItemsRelation::__construct()`
- [x] `OrderItemsRelation::get()`

## Coverage: multisite sites ([#26](https://github.com/wpify/model/issues/26))

Known related bug: [SiteRepository::save() uses comment and user functions](https://github.com/wpify/model/issues/28)
— affected tests get skipped with that link, per the bug protocol.

### SiteRepository

- [x] `SiteRepository::model()`
- [x] `SiteRepository::get()`
- [ ] `SiteRepository::save()` — **blocked by bug** [#28](https://github.com/wpify/model/issues/28); test skipped with link
- [ ] `SiteRepository::save()` — throws `CouldNotSaveModelException` when the underlying call returns `WP_Error` — **blocked by bug** [#28](https://github.com/wpify/model/issues/28)
- [x] `SiteRepository::delete()`
- [x] `SiteRepository::find()`
- [x] `SiteRepository::find_all()`
- [x] `SiteRepository::find_by_ids()`

### Site

_No declared public methods — covered via `Model` and `SiteRepository`._

## Exceptions cross-reference

Every `Exceptions` class with its documented failure mode(s) and the ticket whose tests exercise it.

| Exception | Thrown by | Covered under |
| --- | --- | --- |
| `ModelException` (base) | base of all below; `WP_Error` carried only by `TermRepository::save()` | [#18](https://github.com/wpify/model/issues/18), [#20](https://github.com/wpify/model/issues/20) |
| `CouldNotSaveModelException` | `save()` in Post/Term/User/Comment/Site/Product/Order/OrderItem repositories; `PostRepository::create()` | [#19](https://github.com/wpify/model/issues/19)–[#21](https://github.com/wpify/model/issues/21), [#24](https://github.com/wpify/model/issues/24)–[#26](https://github.com/wpify/model/issues/26) |
| `IncorrectRepositoryException` | `PostRepository::find_all_by_term()`, `ProductRepository::find_all_by_term()` | [#19](https://github.com/wpify/model/issues/19), [#24](https://github.com/wpify/model/issues/24) |
| `KeyNotFoundException` | **never thrown in `src/`** — no test possible; flag in the final sweep | [#27](https://github.com/wpify/model/issues/27) |
| `ModelNotFoundException` | **never thrown in `src/`** — no test possible; flag in the final sweep | [#27](https://github.com/wpify/model/issues/27) |
| `PrimaryKeyException` | `CustomTableRepository::primary_key()`, `CustomTableRepository::get()` | [#23](https://github.com/wpify/model/issues/23) |
| `PropertyNotDefinedException` | `Model::__get()` | [#18](https://github.com/wpify/model/issues/18) |
| `ReadOnlyPropertyException` | `Model::__set()` | [#18](https://github.com/wpify/model/issues/18) |
| `RepositoryMethodNotImplementedException` | `ChildPostsRelation::get()`, `ChildTermsRelation::get()` | [#18](https://github.com/wpify/model/issues/18) |
| `RepositoryNotFoundException` | `Manager::get_model_repository()`, `ManyToOneRelation::get()` | [#18](https://github.com/wpify/model/issues/18) |
| `RepositoryNotInitialized` | `Repository::manager()` | [#18](https://github.com/wpify/model/issues/18) |
| `SqlException` | `CustomTableRepository::save()`/`delete()`/`find()` | [#23](https://github.com/wpify/model/issues/23) |

## Audit result (2026-07-26)

Suite status: **167 single-site tests + 7 multisite tests, 439+ assertions, 0 failures** (11 tests skipped,
each linking a filed bug). Run locally via the docker-less fallback (`vendor/bin/phpunit`,
`vendor/bin/phpunit -c phpunit-multisite.xml`); the wp-env path runs in CI.

The 5 unchecked items above are all accounted for:

- 3 × WooCommerce `save()` failure modes are **unreachable dead branches** — WC's `save()` throws
  `WC_Data_Exception` and never returns `WP_Error`, so the guarded branch cannot fire.
- 2 × `SiteRepository::save()` items are **blocked by bug** [#28](https://github.com/wpify/model/issues/28).

Bugs filed during coverage work (exposing tests are skipped with links, per the bug protocol):
[#28](https://github.com/wpify/model/issues/28), [#29](https://github.com/wpify/model/issues/29),
[#30](https://github.com/wpify/model/issues/30), [#31](https://github.com/wpify/model/issues/31),
[#32](https://github.com/wpify/model/issues/32), [#33](https://github.com/wpify/model/issues/33),
[#34](https://github.com/wpify/model/issues/34), [#35](https://github.com/wpify/model/issues/35),
[#36](https://github.com/wpify/model/issues/36), [#37](https://github.com/wpify/model/issues/37),
[#38](https://github.com/wpify/model/issues/38), [#39](https://github.com/wpify/model/issues/39),
[#40](https://github.com/wpify/model/issues/40), [#41](https://github.com/wpify/model/issues/41),
plus pre-existing [#1](https://github.com/wpify/model/issues/1).

Dead code flagged for cleanup (no test possible): `KeyNotFoundException` and `ModelNotFoundException`
are never thrown; `Manager.php` imports nonexistent `Factories\DefaultStorageFactory` and
`Interfaces\StorageFactoryInterface`.
