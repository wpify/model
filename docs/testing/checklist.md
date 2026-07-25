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

- [ ] `Manager::__construct( ...$dependencies )`
- [ ] `Manager::register_repository()`
- [ ] `Manager::get_model_repository()`
- [ ] `Manager::get_model_repository()` — throws `RepositoryNotFoundException` for an unregistered model
- [ ] `Manager::get_repository()`
- [ ] `Manager::get_repositories()`

### Model (abstract base)

- [ ] `Model::__construct()`
- [ ] `Model::source()`
- [ ] `Model::props()`
- [ ] `Model::manager()`
- [ ] `Model::reflection()`
- [ ] `Model::refresh()`
- [ ] `Model::__get()`
- [ ] `Model::__get()` — throws `PropertyNotDefinedException` for an undefined property
- [ ] `Model::__set()`
- [ ] `Model::__set()` — throws `ReadOnlyPropertyException` for a `#[ReadOnlyProperty]` property
- [ ] `Model::to_array()`
- [ ] `Model::getIterator()`
- [ ] `Model::offsetSet()`
- [ ] `Model::offsetExists()`
- [ ] `Model::offsetUnset()`
- [ ] `Model::offsetGet()`
- [ ] `Model::__isset()`
- [ ] `Model::__unset()`

### Repository (abstract base)

- [ ] `Repository::manager()`
- [ ] `Repository::manager()` — throws `RepositoryNotInitialized` when called before `register_repository()`
- [ ] `Repository::resolve_property()`
- [ ] `Repository::maybe_convert_to_type()`
- [ ] `Repository::create()` — base implementation (via any repository that does not override it, e.g. `TermRepository`)

### Attributes

- [ ] `AccessorObject::__construct()`
- [ ] `AccessorObject::get()`
- [ ] `AccessorObject::set()`
- [ ] `AliasOf::__construct()`
- [ ] `AliasOf::get()`
- [ ] `ChildPostsRelation::get()`
- [ ] `ChildPostsRelation::get()` — throws `RepositoryMethodNotImplementedException` when the repository lacks `find_child_posts_of()`
- [ ] `ChildTermsRelation::get()`
- [ ] `ChildTermsRelation::get()` — throws `RepositoryMethodNotImplementedException` when the repository lacks `find_child_terms_of()`
- [ ] `IdsRelation::__construct()`
- [ ] `IdsRelation::get()`
- [ ] `ManyToOneRelation::__construct()`
- [ ] `ManyToOneRelation::get()`
- [ ] `ManyToOneRelation::get()` — throws `RepositoryNotFoundException` when no repository handles the target model
- [ ] `Meta::__construct()`
- [ ] `Meta::get()`
- [ ] `Meta::set()`
- [ ] `PostTermRelation::__construct()`
- [ ] `PostTermRelation::get()`
- [ ] `PostTermRelation::persist()`
- [ ] `PostTermsRelation::__construct()`
- [ ] `PostTermsRelation::get()`
- [ ] `PostTermsRelation::persist()`
- [ ] `ReadOnlyProperty` — no methods; behavior covered by the `Model::__set()` failure-mode test above
- [ ] `SourceObject::__construct()`
- [ ] `SourceObject::get()`
- [ ] `TermPostsRelation::__construct()`
- [ ] `TermPostsRelation::get()`
- [ ] `TopLevelPostParentRelation::__construct()`
- [ ] `TopLevelPostParentRelation::get()`
- [ ] `TopLevelTermParentRelation::__construct()`
- [ ] `TopLevelTermParentRelation::get()`

### ModelException (base)

- [ ] `ModelException::__construct()` — via any thrown subclass
- [ ] `ModelException::get_wp_error()` — returns the `WP_Error` when one is attached (only `TermRepository::save()` passes one; see [#20](https://github.com/wpify/model/issues/20)) and `null` otherwise

## Coverage: posts, pages, and attachments ([#19](https://github.com/wpify/model/issues/19))

### PostRepository

- [ ] `PostRepository::model()`
- [ ] `PostRepository::get()`
- [ ] `PostRepository::post_types()`
- [ ] `PostRepository::create()`
- [ ] `PostRepository::create()` — throws `CouldNotSaveModelException` when the repository declares multiple post types
- [ ] `PostRepository::create()` — throws `CouldNotSaveModelException` when the repository declares no post types
- [ ] `PostRepository::save()`
- [ ] `PostRepository::save()` — throws `CouldNotSaveModelException` when `wp_insert_post`/`wp_update_post` returns `WP_Error`
- [ ] `PostRepository::delete()`
- [ ] `PostRepository::get_paginate_links()`
- [ ] `PostRepository::get_pagination()`
- [ ] `PostRepository::find()`
- [ ] `PostRepository::find_without_pagination()`
- [ ] `PostRepository::find_paginated()`
- [ ] `PostRepository::find_all()`
- [ ] `PostRepository::find_published()`
- [ ] `PostRepository::find_all_by_term()`
- [ ] `PostRepository::find_all_by_term()` — throws `IncorrectRepositoryException` when the term model's repository has no `taxonomy()` method
- [ ] `PostRepository::find_child_posts_of()`
- [ ] `PostRepository::find_by_ids()`
- [ ] `PostRepository::assign_post_to_term()`

### Post

- [ ] `Post::get_permalink()`
- [ ] `Post::get_featured_image_id()`
- [ ] `Post::persist_featured_image_id()`
- [ ] `Post::get_rendered_excerpt()`

### PageRepository

- [ ] `PageRepository::model()`
- [ ] `PageRepository::post_types()`

### Page

_No declared public methods — covered via `Post` and `PageRepository`._

### AttachmentRepository

- [ ] `AttachmentRepository::model()`
- [ ] `AttachmentRepository::post_types()`

### Attachment

- [ ] `Attachment::get_url()`

## Coverage: terms and taxonomies ([#20](https://github.com/wpify/model/issues/20))

### TermRepository

- [ ] `TermRepository::model()`
- [ ] `TermRepository::taxonomy()`
- [ ] `TermRepository::get()`
- [ ] `TermRepository::save()`
- [ ] `TermRepository::save()` — throws `CouldNotSaveModelException` (with the `WP_Error` attached, readable via `get_wp_error()`) when `wp_insert_term`/`wp_update_term` fails
- [ ] `TermRepository::delete()`
- [ ] `TermRepository::find()`
- [ ] `TermRepository::find_all()`
- [ ] `TermRepository::find_not_empty()`
- [ ] `TermRepository::find_children_of()`
- [ ] `TermRepository::find_terms_of_post()`
- [ ] `TermRepository::find_child_terms_of()`
- [ ] `TermRepository::find_by_ids()`

### Term

- [ ] `Term::get_permalink()`

### CategoryRepository

- [ ] `CategoryRepository::model()`
- [ ] `CategoryRepository::taxonomy()`

### Category

_No declared public methods — covered via `Term` and `CategoryRepository`._

### PostTagRepository

- [ ] `PostTagRepository::model()`
- [ ] `PostTagRepository::taxonomy()`

### PostTag

_No declared public methods — covered via `Term` and `PostTagRepository`._

## Coverage: users and comments ([#21](https://github.com/wpify/model/issues/21))

### UserRepository

- [ ] `UserRepository::model()`
- [ ] `UserRepository::get()`
- [ ] `UserRepository::get_current()`
- [ ] `UserRepository::save()`
- [ ] `UserRepository::save()` — throws `CouldNotSaveModelException` when `wp_insert_user`/`wp_update_user` returns `WP_Error`
- [ ] `UserRepository::delete()`
- [ ] `UserRepository::find()`
- [ ] `UserRepository::find_all()`
- [ ] `UserRepository::find_by_ids()`

### User

_No declared public methods — covered via `Model` and `UserRepository`._

### CommentRepository

- [ ] `CommentRepository::model()`
- [ ] `CommentRepository::get()`
- [ ] `CommentRepository::save()`
- [ ] `CommentRepository::save()` — throws `CouldNotSaveModelException` when `wp_insert_comment`/`wp_update_comment` fails
- [ ] `CommentRepository::delete()`
- [ ] `CommentRepository::find()`
- [ ] `CommentRepository::find_by_post_id()`
- [ ] `CommentRepository::find_by_ids()`
- [ ] `CommentRepository::find_all()`

### Comment

_No declared public methods — covered via `Model` and `CommentRepository`._

## Coverage: menus and menu items ([#22](https://github.com/wpify/model/issues/22))

Known related bug: [Menu error when not assigned](https://github.com/wpify/model/issues/1) — a test exposing
it gets skipped with that link, per the bug protocol.

### MenuRepository

- [ ] `MenuRepository::model()`
- [ ] `MenuRepository::taxonomy()`
- [ ] `MenuRepository::get()` — by id/slug and by theme menu location
- [ ] `MenuRepository::items()` — both `RETURN_OBJECTS` and flat return modes

### Menu

- [ ] `Menu::to_array()`

### MenuItemRepository

- [ ] `MenuItemRepository::model()`
- [ ] `MenuItemRepository::post_types()`

### MenuItem

- [ ] `MenuItem::get_is_current()`
- [ ] `MenuItem::get_is_highlighted()`

### Attributes

- [ ] `MenuItemsRelation::__construct()`
- [ ] `MenuItemsRelation::get()`
- [ ] `MenuItemsRelation::sort_items()`

## Coverage: custom table repository ([#23](https://github.com/wpify/model/issues/23))

### CustomTableRepository

- [ ] `CustomTableRepository::__construct()`
- [ ] `CustomTableRepository::primary_key()`
- [ ] `CustomTableRepository::primary_key()` — throws `PrimaryKeyException` when the model does not declare exactly one primary-key column
- [ ] `CustomTableRepository::prefixed_table_name()`
- [ ] `CustomTableRepository::migrate()`
- [ ] `CustomTableRepository::columns()`
- [ ] `CustomTableRepository::query_single()`
- [ ] `CustomTableRepository::get()`
- [ ] `CustomTableRepository::get()` — throws `PrimaryKeyException` when an array source lacks the primary key
- [ ] `CustomTableRepository::drop_table()`
- [ ] `CustomTableRepository::save()` — insert and update paths
- [ ] `CustomTableRepository::save()` — throws `SqlException` on a database error
- [ ] `CustomTableRepository::delete()`
- [ ] `CustomTableRepository::delete()` — throws `SqlException` on a database error
- [ ] `CustomTableRepository::find()`
- [ ] `CustomTableRepository::find()` — throws `SqlException` when an array condition does not have exactly two values
- [ ] `CustomTableRepository::find()` — throws `SqlException` on a database error
- [ ] `CustomTableRepository::find_by_ids()`
- [ ] `CustomTableRepository::find_all()`

### Attributes

- [ ] `Column::__construct()`
- [ ] `Column::create_column_sql()`
- [ ] `Column::get()`

## Coverage: WooCommerce products ([#24](https://github.com/wpify/model/issues/24))

### ProductRepository

- [ ] `ProductRepository::model()`
- [ ] `ProductRepository::get()`
- [ ] `ProductRepository::save()`
- [ ] `ProductRepository::save()` — throws `CouldNotSaveModelException` when the WooCommerce save fails
- [ ] `ProductRepository::delete()`
- [ ] `ProductRepository::find()`
- [ ] `ProductRepository::tax_query_filter()`
- [ ] `ProductRepository::find_all()`
- [ ] `ProductRepository::find_by_ids()`
- [ ] `ProductRepository::find_all_by_term()`
- [ ] `ProductRepository::find_all_by_term()` — throws `IncorrectRepositoryException` when the term model's repository has no `taxonomy()` method

### Product

- [ ] `Product::get_wc_product()`
- [ ] `Product::get_vat_rate()`

### ProductCatRepository

- [ ] `ProductCatRepository::model()`
- [ ] `ProductCatRepository::taxonomy()`

### ProductCat

_No declared public methods — covered via `Term` and `ProductCatRepository`._

## Coverage: WooCommerce orders ([#25](https://github.com/wpify/model/issues/25))

Orders are tested against HPOS only (env-flag escape hatch for legacy), per
[Research: WooCommerce in the wp-env test suite](https://github.com/wpify/model/issues/12).

### OrderRepository

- [ ] `OrderRepository::model()`
- [ ] `OrderRepository::get()`
- [ ] `OrderRepository::save()`
- [ ] `OrderRepository::save()` — throws `CouldNotSaveModelException` when the WooCommerce save fails
- [ ] `OrderRepository::delete()`
- [ ] `OrderRepository::find()`
- [ ] `OrderRepository::find_all()`
- [ ] `OrderRepository::find_by_ids()`

### Order

- [ ] `Order::get_wc_order()`
- [ ] `Order::get_weight()`
- [ ] `Order::has_shipping_method()`
- [ ] `Order::get_items()`

### OrderItemRepository

- [ ] `OrderItemRepository::model()`
- [ ] `OrderItemRepository::get()`
- [ ] `OrderItemRepository::create()`
- [ ] `OrderItemRepository::save()`
- [ ] `OrderItemRepository::save()` — throws `CouldNotSaveModelException` when the WooCommerce save fails
- [ ] `OrderItemRepository::delete()`
- [ ] `OrderItemRepository::find()`
- [ ] `OrderItemRepository::find_all()`
- [ ] `OrderItemRepository::find_by_ids()`

### OrderItem

- [ ] `OrderItem::get_wc_order_item()`
- [ ] `OrderItem::get_unit_price_tax_included()`
- [ ] `OrderItem::get_unit_price()`
- [ ] `OrderItem::get_unit_price_tax_excluded()`
- [ ] `OrderItem::get_vat_rate()`
- [ ] `OrderItem::get_vat_rate_id()`

### Order item subtypes

- [ ] `OrderItemLineRepository::model()`
- [ ] `OrderItemFeeRepository::model()`
- [ ] `OrderItemShippingRepository::model()`

`OrderItemLine`, `OrderItemFee`, and `OrderItemShipping` declare no public methods — covered via `OrderItem`
and their repositories.

### Attributes

- [ ] `OrderItemsRelation::__construct()`
- [ ] `OrderItemsRelation::get()`

## Coverage: multisite sites ([#26](https://github.com/wpify/model/issues/26))

Known related bug: [SiteRepository::save() uses comment and user functions](https://github.com/wpify/model/issues/28)
— affected tests get skipped with that link, per the bug protocol.

### SiteRepository

- [ ] `SiteRepository::model()`
- [ ] `SiteRepository::get()`
- [ ] `SiteRepository::save()`
- [ ] `SiteRepository::save()` — throws `CouldNotSaveModelException` when the underlying call returns `WP_Error`
- [ ] `SiteRepository::delete()`
- [ ] `SiteRepository::find()`
- [ ] `SiteRepository::find_all()`
- [ ] `SiteRepository::find_by_ids()`

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
