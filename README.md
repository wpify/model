# Models for WordPress

This library provides a better access to data in WordPress. It is a wrapper around the WordPress API, which provides
a more object-oriented approach to the data. It also provides a way to define custom models for your data, for example
to define a model for a custom post type, custom taxonomy or custom database table.

## Benefits

* Object-oriented access to data.
* Easy to use.
* Easy to extend.
* Easy to define and use custom tables.
* Minimise the number of queries to the database.
* Uses modern PHP 8 features for better developer experience.

## Core concepts

The library uses a few types of objects:

* **Model**: A model is a class that contains data of one entity, e.g. single post, term or table row. Model has clearly defined properties and methods to access the data.
* **Repository**: A repository is a class that provides access to the data. Repository can retrieve data from the database, from the cache or from other sources.
* **Manager**: Manager keeps the list of registered models and repositories. It also provides a way to retrieve a model or repository by its name.

## Requirements

* PHP 8.0 or higher

## Installation

Install the library using composer:

```shell
composer require prototype/model
```

# Basic usage

First think you need to do is to initialize the Manager to have access to built-in models and repositories:

```php
use Wpify\Model\Manager;

$manager = new Manager();
```

With the manager you can use a built-in models and repositories. For example, to get all published posts
you can use the following code:

```php
$post_repository = $manager->get_repository( Wpify\Model\Post::class );
$posts           = $post_repository->find_published();
```

And then you can access the data using the model:

```php
foreach ( $posts as $post ) {
    echo $post->title;
}
```

You can also retrieve a single post by its ID or other keys:

```php
$post = $post_repository->get( 123 );
$post = $post_repository->get( 'article-slug' );
$post = $post_repository->get( 'https://myblog.con/article-url/' );
```

## Updating the model

To update the data, you simply update model's properties and then save the data by calling the `save()` method on the repository:

```php
$post->title = 'New title';

$post_repository->save( $post );

echo $post->title; // New title
```

## Creating new entries

To create a new entry, you need to create a new model via repository and then save it:

```php
$post = $post_repository->create( array(
    'title'   => 'New post',
    'content' => 'New content',
) );

$post_repository->save( $post );

echo $post->id; // 123
```

## Deleting the entry

To delete the entry, you need to call the `delete()` method on the repository:

```php
$post_repository->delete( $post );
```

# Custom models

You can define your own models to access the data in custom post types, custom taxonomies or custom database tables. 
All your custom models must extend the `Wpify\Model\Model` class or other models. Every custom model must have 
a repository, which must extend the `Wpify\Model\Repository` class or other repositories.

## Defining a custom model

In this example, we will define a model for a custom post type with some meta fields:

```php
use Wpify\Model\Post;
use Wpify\Model\Attributes;

class Book extends Post {
    #[Attributes\Meta]
    public string $isbn;
    
    #[Attributes\Meta]
    public string $author;
}
```

All properties have a definition in the form of a PHP attribute. You can use the following attributes:

* `Attributes\AliasOf` - defines an alias for another property.
* `Attributes\AccessorObject` - defines a getter and setter from the source object.
* `Attributes\ChildPostRelation` - defines a relation to a child post (for post models only).
* `Attributes\ChildTermRelation` - defines a relation to a child term (for term models only).
* `Attributes\Column` - defines a column in a custom table (for custom table models only).
* `Attributes\IdsRelation` - retrieves related posts by IDs.
* `Attributes\ManyToOneRelation` - retrieves related models by foreign model's ID in another property.
* `Attributes\Meta` - defines a meta field.
* `Attributes\OrderItemsRelation` - retrieves order items. 
* `Attributes\PostTermsRelation` - retrieves related terms by post ID. This attribute also persists the post-term relation.
* `Attributes\SourceObject` - defines a value in a source object for the model (e.g. WP_Post property).
* `Attributes\TermPostsRelation` - retrieves related posts of term (for term models only).

There are also attributes that can modify a behaviour of the propery:

* `Attributes\ReadOnlyProperty` - makes the property read-only.

Some of the attributes have additional parameters, that can be passed by named constructor arguments:

```php
#[Attributes\SourceObject( 'ID' )]
public int $id;

#[Attributes\Meta( '_isbn' )]
public string $isbn;

#[Attributes\Meta( meta_key: '_author' )]
public string $author;

#[Attributes\Column( type: Attributes\Column::VARCHAR, params: 1000, unique: true )]
public string $custom_column;
```

Most of the attributes have a default values, so you can omit some of the parameters.

## Defining a custom repository

To actually use the model, you need to define a repository for it. In this example, we will define a repository for the `Book` model:

```php
use Wpify\Model\PostRepository;

class BookRepository extends PostRepository {
    public function model() : string{
        return Book::class;
    }
    
    public function post_types() : array{
        array( 'book' );
    }
}
```

After repository creation, you need to register it in the manager:

```php
$manager->register_repository( BookRepository::class );
```

You can also register the repository in the constructor of the manager:

```php
$manager = new Manager( new BookRepository() );
```

If you use a PHP-DI, you can register the repository in the container:

```php
use DI;
use Wpify\Model\Manager;

$container_builder = new DI\ContainerBuilder();

$container_builder->addDefinitions( array(
	Manager::class => DI\create()->constructor(
		DI\get( BookRepository::class ),
	),
) );

$container = $container_builder->build();
$manager   = $container->get( Manager::class );
$book_repo = $manager->get_repository( BookRepository::class );
```

# Built-in models

The library provides a few built-in models and repositories. 
You can use them also to define your custom models by extending them.

* `Post` and `PostRepository` for posts.
* `Page` and `PageRepository` for pages.
* `Attachment` and `AttachmentRepository` for attachments.
* `Category` and `CategoryRepository` for categories.
* `PostTag` and `PostTagRepository` for post tags.
* `User` and `UserRepository` for users.
* `Comment` and `CommentRepository` for comments.
* `User` and `UserRepository` for users.
* `Menu` and `MenuRepository` for menus.
* `Site` and `SiteRepository` for sites.
* `Product` and `ProductRepository` for WooCommerce products.
* `Order` and `OrderRepository` for WooCommerce orders.
* `OrderItem` and `OrderItemRepository` for WooCommerce order items.
* `ProductCat` and `ProductCatRepository` for WooCommerce product categories.

# Custom table models

You can create and use custom table models. To do this, you need to define a repository for the model and a model itself.
The table is created automatically, so you don't need to handle it manually, but you can disable this behaviour.

Example:

```php
class MyModelRepository extends CustomTableRepository {
  public function model(): string {
    return MyModel::class;
  }

  public function table_name(): string {
    return 'my_model';
  }
}
```

You also need to define the model with column attributes:

```php
class MyModel extends Model {
  #[Column( type: Column::INT, auto_increment: true, primary_key: true )]
  public int $id = 0;

  #[Column( type: Column::VARCHAR, params: 255 )]
  public string $name = '';
}
```

The Column attribute accepts the following parameters:
- `name`:           The name of the column. If not specified, the name of the property will be used.
- `type`:           The type of the column. If not specified, the type will be inferred from the property type.
- `params`:         The parameters for the column type. For example, for VARCHAR, you can specify the length.
- `unsigned`:       Whether the column is unsigned. Default is false.
- `nullable`:       Whether the column is nullable. Default is false.
- `auto_increment`: Whether the column is auto-increment. Default is false.
- `primary_key`:    Whether the column is primary key. Default is false.
- `unique`:         Whether the column is unique. Default is false.

The model must contain exactly one primary key column.

Column can be one of the following types:
- `Column::TINYINT`   (`tinyint`)
- `Column::INT`       (`int`)
- `Column::BIGINT`    (`bigint`)
- `Column::BOOLEAN`   (`boolean`)
- `Column::DECIMAL`   (`decimal`)
- `Column::DATE`      (`date`)
- `Column::DATETIME`  (`datetime`)
- `Column::TIMESTAMP` (`timestamp`)
- `Column::TIME`      (`time`)
- `Column::CHAR`      (`char`)
- `Column::VARCHAR`   (`varchar`)
- `Column::BLOB`      (`blob`)
- `Column::TEXT`      (`text`)
- `Column::ENUM`      (`enum`)
- `Column::SET`       (`set`)
- `Column::JSON`      (`json`)

The repository will automatically create the table when the repository is used. If you want to disable automatic migrations,
you can pass `false` to the `auto_migrate` parameter in the constructor. You can then manually migrate the table by calling
the `migrate()` method in appropriate place, e.g. plugin activation hook, `admin_init` hook, etc.

```php
$repository = new MyModelRepository( auto_migrate: false );

$manager->register_repository( $repository );

add_action( 'admin_init', array( $repository, 'migrate' ) );
```

The table name is automatically prefixed with the WordPress table prefix. If you want to disable this, you can pass `false`
to the `use_prefix` parameter in the constructor.

```php
$repository = new MyModelRepository( use_prefix: false );

$manager->register_repository( $repository );
```

If you want to drop the database table, you can call the `drop_table()` method. This is useful when uninstalling the plugin.

```php
$repository = new MyModelRepository();

$manager->register_repository( $repository );

register_uninstall_hook( $main_php_file_path, array( $repository, 'drop_table' ) );
```

# TODO:

- better caching
- make generated documentation
- add tests
- implement other WooCommerce models:
  - VariableProduct
  - SimpleProduct
  - GroupedProduct
  - ExternalProduct
  - DownloadableProduct
  - ProductTag
  - ProductAttribute
  - ProductVariation
  - ShopCoupon
  - ShopWebhook
- implement WooCommerce Subscriptions models
- implement SQL query model
