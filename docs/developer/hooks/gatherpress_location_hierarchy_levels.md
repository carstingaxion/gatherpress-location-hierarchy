# gatherpress_location_hierarchy_levels


Filter the allowed hierarchy levels.

Allows filtering which levels are saved and displayed, providing flexibility
for different use cases (e.g., only continent to city, or only city to street number).

Level mapping:
- 1 = Continent
- 2 = Country  
- 3 = State
- 4 = City
- 5 = Street
- 6 = Street Number

Common configurations:
- Continent only: Levels 1-1
- Country through City: Levels 2-4
- City and Street: Levels 4-5
- Full hierarchy: Levels 1-6 (plugin default)

## Example

```php
add_filter( 'gatherpress_location_hierarchy_levels', function() {
    return [2, 4]; // Only Country, State, City
} );
```

## Parameters

- *`array`* `$levels` Array with [min_level, max_level] integers.

## Files

- [includes/classes/class-setup.php:221](https://github.com/carstingaxion/gatherpress-location-hierarchy/blob/main/includes/classes/class-setup.php#L221)
```php
apply_filters( 'gatherpress_location_hierarchy_levels', array( 1, 6 ) )
```



[← All Hooks](Hooks)
