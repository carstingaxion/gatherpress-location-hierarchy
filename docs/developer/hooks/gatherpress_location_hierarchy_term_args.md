# gatherpress_location_hierarchy_term_args


Filter term arguments before creating the term.

Allows modification of term attributes before insertion.

Provides extensibility point for customizing term creation.
For example, this can be used to ensure country terms use country codes
as slugs instead of transliterated country names.

Passes array of term data including name, slug, parent, level,
and full location context. Filters can modify any attribute except taxonomy.


Term arguments array.

}

## Example

```php
add_filter( 'gatherpress_location_hierarchy_term_args', function( $args ) {
    // Countries use country code as slug
    if ( 2 === $args['level'] && ! empty( $args['location']['country_code'] ) ) {
        $args['slug'] = $args['location']['country_code'];
    }
    return $args;
} );
```

The Term arguments, that are not well-parseable by extract-wp-hooks. (https://github.com/akirk/extract-wp-hooks/issues/23)

## Parameters

- *`array`* `$args` {

## Files

- [includes/classes/class-builder.php:333](https://github.com/carstingaxion/gatherpress-location-hierarchy/blob/main/includes/classes/class-builder.php#L333)
```php
apply_filters(
			'gatherpress_location_hierarchy_term_args',
			array(
				'name'     => $name,
				'slug'     => $slug,
				'parent'   => $parent_id,
				'taxonomy' => $taxonomy,
				'level'    => $level,
				'location' => $location,
// ...
```



[← All Hooks](Hooks)
