<?php
/**
 * Handles address geocoding via Nominatim OpenStreetMap API.
 *
 * @package GatherPressLocationHierarchy
 */

declare(strict_types=1);

namespace GatherPress_Location_Hierarchy;

/**
 * Geocoder class using Singleton pattern.
 *
 * Handles address geocoding via Nominatim OpenStreetMap API with caching.
 *
 * Geocoding is rate-limited and slow - caching prevents repeated API calls for
 * the same address. Nominatim provides free, open-source geocoding that returns detailed
 * address components (country, state, city, street, house_number) needed for hierarchy building.
 * Special handling for German-speaking regions accounts for different administrative structures.
 *
 * 
 * - Uses WordPress transients for 1-hour caching (balance between freshness and API load)
 * - Generates cache keys from address MD5 hash
 * - Parses Nominatim's addressdetails response into standardized location array
 * - Maps country codes to translated continent names using WordPress i18n
 * - Handles regional variations (e.g., "state" vs "region" vs "province")
 * - Extracts street and house_number for complete address hierarchy
 * - Sends site language to API for localized results
 *
 * @since 0.1.0
 */
class Geocoder {
	
	/**
	 * Single instance.
	 *
	 * Singleton prevents multiple instances with potentially different configurations
	 * and ensures consistent caching behavior across the request.
	 *
	 * @since 0.1.0
	 * @var Geocoder|null
	 */
	private static $instance = null;
	
	/**
	 * Nominatim API endpoint.
	 *
	 * Base URL for OpenStreetMap's Nominatim geocoding service.
	 *
	 * Nominatim is free, open-source, and returns detailed address components.
	 * The /search endpoint accepts address queries and returns coordinates + detailed location data.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $api_endpoint = 'https://nominatim.openstreetmap.org/search';
	
	/**
	 * Cache duration in seconds (1 hour).
	 *
	 * How long to cache geocoding results in WordPress transients.
	 *
	 * Balance between data freshness and API load reduction:
	 * - Too short: Excessive API calls, possible rate limiting
	 * - Too long: Outdated results if addresses change
	 * - 1 hour: Good compromise for venue addresses (rarely change within an hour)
	 *
	 * @since 0.1.0
	 * @var int
	 */
	private $cache_duration = 3600;
	
	/**
	 * Get singleton instance.
	 *
	 * @since 0.1.0
	 * @return Geocoder The singleton instance.
	 */
	public static function get_instance(): Geocoder {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	/**
	 * Constructor.
	 *
	 * Private constructor to enforce singleton pattern.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}
	
	/**
	 * Get country to continent mapping.
	 *
	 * Maps ISO 3166-1 alpha-2 country codes to translated continent names.
	 *
	 * Nominatim API doesn't return continent information, so we must derive it
	 * from country codes. Using WordPress's default translation domain (__()) ensures
	 * continent names match WordPress core's translations, maintaining consistency.
	 *
	 * Returns comprehensive array covering ~200 countries organized by continent.
	 * Uses lowercase country codes to match Nominatim's response format. Includes:
	 * - All European countries (including special regions like Gibraltar, Vatican)
	 * - North/South American countries
	 * - Asian countries (including Middle East)
	 * - African countries
	 * - Oceanic nations
	 * - Antarctica (for completeness)
	 *
	 * Example usage:
	 * $mapping = $this->get_country_continents();
	 * $continent = $mapping['de'] ?? __( 'Unknown' ); // Returns "Europe" (translated)
	 *
	 * @since 0.1.0
	 * @return array<string, string> Mapping of lowercase country codes to translated continent names.
	 */
	private function get_country_continents(): array {
		return array(
			'de' => __( 'Europe', 'default' ),
			'at' => __( 'Europe', 'default' ),
			'ch' => __( 'Europe', 'default' ),
			'fr' => __( 'Europe', 'default' ),
			'it' => __( 'Europe', 'default' ),
			'es' => __( 'Europe', 'default' ),
			'pt' => __( 'Europe', 'default' ),
			'uk' => __( 'Europe', 'default' ),
			'gb' => __( 'Europe', 'default' ),
			'ie' => __( 'Europe', 'default' ),
			'nl' => __( 'Europe', 'default' ),
			'be' => __( 'Europe', 'default' ),
			'lu' => __( 'Europe', 'default' ),
			'se' => __( 'Europe', 'default' ),
			'no' => __( 'Europe', 'default' ),
			'dk' => __( 'Europe', 'default' ),
			'fi' => __( 'Europe', 'default' ),
			'pl' => __( 'Europe', 'default' ),
			'cz' => __( 'Europe', 'default' ),
			'sk' => __( 'Europe', 'default' ),
			'hu' => __( 'Europe', 'default' ),
			'ro' => __( 'Europe', 'default' ),
			'bg' => __( 'Europe', 'default' ),
			'gr' => __( 'Europe', 'default' ),
			'hr' => __( 'Europe', 'default' ),
			'si' => __( 'Europe', 'default' ),
			'rs' => __( 'Europe', 'default' ),
			'ba' => __( 'Europe', 'default' ),
			'me' => __( 'Europe', 'default' ),
			'mk' => __( 'Europe', 'default' ),
			'al' => __( 'Europe', 'default' ),
			'tr' => __( 'Europe', 'default' ),
			'ru' => __( 'Europe', 'default' ),
			'ua' => __( 'Europe', 'default' ),
			'by' => __( 'Europe', 'default' ),
			'md' => __( 'Europe', 'default' ),
			'ee' => __( 'Europe', 'default' ),
			'lv' => __( 'Europe', 'default' ),
			'lt' => __( 'Europe', 'default' ),
			'is' => __( 'Europe', 'default' ),

			'us' => __( 'North America', 'default' ),
			'ca' => __( 'North America', 'default' ),
			'mx' => __( 'North America', 'default' ),
			'gt' => __( 'North America', 'default' ),
			'bz' => __( 'North America', 'default' ),
			'sv' => __( 'North America', 'default' ),
			'hn' => __( 'North America', 'default' ),
			'ni' => __( 'North America', 'default' ),
			'cr' => __( 'North America', 'default' ),
			'pa' => __( 'North America', 'default' ),
			'cu' => __( 'North America', 'default' ),
			'jm' => __( 'North America', 'default' ),
			'ht' => __( 'North America', 'default' ),
			'do' => __( 'North America', 'default' ),
			'pr' => __( 'North America', 'default' ),

			'br' => __( 'South America', 'default' ),
			'ar' => __( 'South America', 'default' ),
			'cl' => __( 'South America', 'default' ),
			'co' => __( 'South America', 'default' ),
			'pe' => __( 'South America', 'default' ),
			've' => __( 'South America', 'default' ),
			'ec' => __( 'South America', 'default' ),
			'bo' => __( 'South America', 'default' ),
			'py' => __( 'South America', 'default' ),
			'uy' => __( 'South America', 'default' ),
			'gy' => __( 'South America', 'default' ),
			'sr' => __( 'South America', 'default' ),

			'cn' => __( 'Asia', 'default' ),
			'jp' => __( 'Asia', 'default' ),
			'in' => __( 'Asia', 'default' ),
			'id' => __( 'Asia', 'default' ),
			'pk' => __( 'Asia', 'default' ),
			'bd' => __( 'Asia', 'default' ),
			'ph' => __( 'Asia', 'default' ),
			'vn' => __( 'Asia', 'default' ),
			'th' => __( 'Asia', 'default' ),
			'mm' => __( 'Asia', 'default' ),
			'kr' => __( 'Asia', 'default' ),
			'af' => __( 'Asia', 'default' ),
			'kp' => __( 'Asia', 'default' ),
			'tw' => __( 'Asia', 'default' ),
			'my' => __( 'Asia', 'default' ),
			'np' => __( 'Asia', 'default' ),
			'lk' => __( 'Asia', 'default' ),
			'kh' => __( 'Asia', 'default' ),
			'la' => __( 'Asia', 'default' ),
			'sg' => __( 'Asia', 'default' ),
			'mn' => __( 'Asia', 'default' ),
			'bt' => __( 'Asia', 'default' ),
			'mv' => __( 'Asia', 'default' ),
			'bn' => __( 'Asia', 'default' ),
			'il' => __( 'Asia', 'default' ),
			'jo' => __( 'Asia', 'default' ),
			'lb' => __( 'Asia', 'default' ),
			'sy' => __( 'Asia', 'default' ),
			'iq' => __( 'Asia', 'default' ),
			'ir' => __( 'Asia', 'default' ),
			'sa' => __( 'Asia', 'default' ),
			'ye' => __( 'Asia', 'default' ),
			'om' => __( 'Asia', 'default' ),
			'ae' => __( 'Asia', 'default' ),
			'qa' => __( 'Asia', 'default' ),
			'kw' => __( 'Asia', 'default' ),
			'bh' => __( 'Asia', 'default' ),
			'am' => __( 'Asia', 'default' ),
			'az' => __( 'Asia', 'default' ),
			'ge' => __( 'Asia', 'default' ),
			'kz' => __( 'Asia', 'default' ),
			'uz' => __( 'Asia', 'default' ),
			'tm' => __( 'Asia', 'default' ),
			'kg' => __( 'Asia', 'default' ),
			'tj' => __( 'Asia', 'default' ),

			'ng' => __( 'Africa', 'default' ),
			'et' => __( 'Africa', 'default' ),
			'eg' => __( 'Africa', 'default' ),
			'cd' => __( 'Africa', 'default' ),
			'za' => __( 'Africa', 'default' ),
			'tz' => __( 'Africa', 'default' ),
			'ke' => __( 'Africa', 'default' ),
			'ug' => __( 'Africa', 'default' ),
			'dz' => __( 'Africa', 'default' ),
			'sd' => __( 'Africa', 'default' ),
			'ma' => __( 'Africa', 'default' ),
			'ao' => __( 'Africa', 'default' ),
			'gh' => __( 'Africa', 'default' ),
			'mz' => __( 'Africa', 'default' ),
			'mg' => __( 'Africa', 'default' ),
			'cm' => __( 'Africa', 'default' ),
			'ci' => __( 'Africa', 'default' ),
			'ne' => __( 'Africa', 'default' ),
			'bf' => __( 'Africa', 'default' ),
			'ml' => __( 'Africa', 'default' ),
			'mw' => __( 'Africa', 'default' ),
			'zm' => __( 'Africa', 'default' ),
			'so' => __( 'Africa', 'default' ),
			'sn' => __( 'Africa', 'default' ),
			'td' => __( 'Africa', 'default' ),
			'zw' => __( 'Africa', 'default' ),
			'gn' => __( 'Africa', 'default' ),
			'rw' => __( 'Africa', 'default' ),
			'bj' => __( 'Africa', 'default' ),
			'tn' => __( 'Africa', 'default' ),
			'bi' => __( 'Africa', 'default' ),
			'ss' => __( 'Africa', 'default' ),
			'tg' => __( 'Africa', 'default' ),
			'sl' => __( 'Africa', 'default' ),
			'ly' => __( 'Africa', 'default' ),
			'lr' => __( 'Africa', 'default' ),
			'mr' => __( 'Africa', 'default' ),
			'cf' => __( 'Africa', 'default' ),
			'er' => __( 'Africa', 'default' ),
			'gm' => __( 'Africa', 'default' ),
			'bw' => __( 'Africa', 'default' ),
			'ga' => __( 'Africa', 'default' ),
			'gw' => __( 'Africa', 'default' ),
			'mu' => __( 'Africa', 'default' ),
			'sz' => __( 'Africa', 'default' ),
			'dj' => __( 'Africa', 'default' ),
			'gq' => __( 'Africa', 'default' ),
			'km' => __( 'Africa', 'default' ),

			'au' => __( 'Oceania', 'default' ),
			'pg' => __( 'Oceania', 'default' ),
			'nz' => __( 'Oceania', 'default' ),
			'fj' => __( 'Oceania', 'default' ),
			'sb' => __( 'Oceania', 'default' ),
			'nc' => __( 'Oceania', 'default' ),
			'pf' => __( 'Oceania', 'default' ),
			'vu' => __( 'Oceania', 'default' ),
			'ws' => __( 'Oceania', 'default' ),
			'ki' => __( 'Oceania', 'default' ),
			'fm' => __( 'Oceania', 'default' ),
			'to' => __( 'Oceania', 'default' ),
			'pw' => __( 'Oceania', 'default' ),
			'mh' => __( 'Oceania', 'default' ),
			'nr' => __( 'Oceania', 'default' ),
			'tv' => __( 'Oceania', 'default' ),

			'aq' => __( 'Antarctica', 'default' ),
		);
	}
	
	/**
	 * Geocode an address.
	 *
	 * Converts a text address to geographic coordinates and location components.
	 *
	 * Need structured location data (continent, country, state, city, street, street_number)
	 * to build the taxonomy hierarchy. Caching prevents hitting API rate limits and improves performance.
	 * Site language is sent to API to get localized place names.
	 *
	 * **How:**
	 * 1. Sanitizes input address
	 * 2. Generates cache key from MD5 hash (prevents cache collision for similar addresses)
	 * 3. Checks transient cache (1-hour expiration)
	 * 4. If cache miss, queries Nominatim API with:
	 *    - q: Address query
	 *    - format: json (structured response)
	 *    - addressdetails: 1 (include address components)
	 *    - limit: 1 (only need best match)
	 *    - accept-language: WordPress site language (for localized results)
	 * 5. Parses response via parse_location_data()
	 * 6. Caches successful result
	 * 7. Returns location array or false on failure
	 *
	 * Example API response:
	 * [{
	 *   "address": {
	 *     "house_number": "1",
	 *     "road": "Marienplatz",
	 *     "city": "Munich",
	 *     "state": "Bavaria",
	 *     "country": "Germany",
	 *     "country_code": "de"
	 *   }
	 * }]
	 *
	 * Example return value:
	 * [
	 *   'continent' => 'Europe',
	 *   'country' => 'Germany',
	 *   'country_code' => 'de',
	 *   'state' => 'Bavaria',
	 *   'city' => 'Munich',
	 *   'street' => 'Marienplatz',
	 *   'street_number' => '1'
	 * ]
	 *
	 * @since 0.1.0
	 * @param string $address Full address to geocode (e.g., "Marienplatz 1, 80331 Munich, Germany").
	 * @return array<string, string>|false Location data array or false on failure.
	 */
	public function geocode( string $address ) {
		$address   = sanitize_text_field( $address );
		$cache_key = 'gpvh_geocode_' . md5( $address );
		
		/**
		 * We can be sure, because this is the result of $this->parse_location_data(), 
		 * which only returns array<string, string> or false, 
		 * but only gets saved as transient if it's array<string, string>.
		 *
		 * @var array<string, string>|false $cached
		 */
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}
		
		// Get WordPress site language in format that Nominatim accepts (e.g., 'de', 'en', 'fr').
		$site_locale = get_locale();
		// Convert locale like 'de_DE' to language code 'de'.
		$language = explode( '_', $site_locale )[0];
		
		$response = wp_remote_get(
			add_query_arg(
				array(
					'q'               => $address,
					'format'          => 'json',
					'addressdetails'  => '1',
					'limit'           => '1',
					'accept-language' => $language,
					// 'polygon_geojson' => 1,
					'email'           => get_bloginfo( 'admin_email' ), // Nominatim requires an email for identification.
				),
				$this->api_endpoint
			),
			array(
				'timeout' => 3,
				'headers' => array(
					'User-Agent' => 'GatherPress Location Hierarchy WordPress Plugin',
				),
			)
		);
		
		if ( is_wp_error( $response ) ) {
			error_log( 'GatherPress Location Hierarchy: Geocoding API error - ' . $response->get_error_message() );
			return false;
		}
		
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );
		
		if ( empty( $data ) || ! is_array( $data ) ) {
			error_log( 'GatherPress Location Hierarchy: Invalid API response for address: ' . $address );
			return false;
		}

		// Only use a subset of the result.
		$data = $data[0];
		
		/**
		 * We can be sure, because this is the result of the NOMINATIM API,
		 * which always returns an array of results, if any.
		 * 
		 * @see https://nominatim.org/release-docs/develop/api/Search/
		 * @var array<string, string> $data
		 */
		$location = $this->parse_location_data( $data );
		
		if ( $location ) {
			set_transient( $cache_key, $location, $this->cache_duration );
		}
		
		return $location;
	}
	
	/**
	 * Parse location data from API response.
	 *
	 * Extracts and normalizes location components from Nominatim API response.
	 *
	 * Nominatim's response structure varies by country and address type. Need to:
	 * 1. Handle different field names (state vs region vs province, road vs street)
	 * 2. Add continent information (not in API response)
	 * 3. Normalize to consistent structure for hierarchy building
	 * 4. Account for German-speaking regions' unique administrative structure
	 * 5. Handle city-states like Berlin where state and city are identical
	 * 6. Extract street and house_number for complete address hierarchy
	 *
	 * **How:**
	 * 1. Validates 'address' field exists in response
	 * 2. Extracts country_code and uses it to lookup continent
	 * 3. For German-speaking regions (DE, AT, CH, LU):
	 *    - Uses 'state' field if present (Bundesland/Canton)
	 *    - For city-states (e.g., Berlin where state is missing):
	 *      - Uses city name as state to maintain hierarchy consistency
	 *      - Uses suburb (with fallback to borough) as city
	 *      - This prevents duplicate entries and creates proper hierarchy:
	 *        Europe > Germany > Berlin > Prenzlauer Berg > Street > Number
	 * 4. For other countries:
	 *    - Falls back through state > region > province (handles naming variations)
	 * 5. Extracts city from city > town > village > county (urban to rural fallback)
	 * 6. Extracts street from road > street > pedestrian (common field names)
	 * 7. Extracts house_number for street number
	 * 8. Sanitizes all values
	 * 9. Filters out empty values with array_filter()
	 *
	 * Example input (Berlin - city-state):
	 * [
	 *   'address' => [
	 *     'house_number' => '81-84',
	 *     'road' => 'Greifswalder Straße',
	 *     'suburb' => 'Prenzlauer Berg',
	 *     'borough' => 'Pankow',
	 *     'city' => 'Berlin',
	 *     // Note: NO 'state' field for Berlin
	 *     'country' => 'Germany',
	 *     'country_code' => 'de'
	 *   ]
	 * ]
	 *
	 * Example output (Berlin - city-state):
	 * [
	 *   'continent' => 'Europe',
	 *   'country' => 'Germany',
	 *   'country_code' => 'de',
	 *   'state' => 'Berlin',              // City name used as state
	 *   'city' => 'Prenzlauer Berg',      // Suburb used as city (avoids duplication)
	 *   'street' => 'Greifswalder Straße',
	 *   'street_number' => '81-84'
	 * ]
	 *
	 * Example input (Munich - has separate state):
	 * [
	 *   'address' => [
	 *     'house_number' => '1',
	 *     'road' => 'Marienplatz',
	 *     'city' => 'Munich',
	 *     'state' => 'Bavaria',   // Separate state field present
	 *     'country' => 'Germany',
	 *     'country_code' => 'de'
	 *   ]
	 * ]
	 *
	 * Example output (Munich - normal case):
	 * [
	 *   'continent' => 'Europe',
	 *   'country' => 'Germany',
	 *   'country_code' => 'de',
	 *   'state' => 'Bavaria',
	 *   'city' => 'Munich',
	 *   'street' => 'Marienplatz',
	 *   'street_number' => '1'
	 * ]
	 *
	 * @since 0.1.0
	 * @param array<string, string> $data API response data (first result from Nominatim).
	 * @return array<string, string>|false Parsed location data or false on failure.
	 */
	private function parse_location_data( array $data ) {
		if ( empty( $data['address'] ) || ! is_array( $data['address'] ) ) {
			return false;
		}
		
		$address      = $data['address'];
		$country_code = $address['country_code'] ?? '';
		// $country_code = strtolower( sanitize_text_field( $country_code ) );
		
		// Get continent from country code using translated names.
		$country_continents = $this->get_country_continents();
		$continent          = $country_continents[ $country_code ] ?? __( 'Unknown', 'default' );
		
		$location = array(
			'continent'     => $continent,
			'country'       => sanitize_text_field( $address['country'] ?? '' ),
			'country_code'  => $country_code,
			'state'         => '',
			'city'          => '',
			'street'        => '',
			'street_number' => '',
		);
		
		$german_regions   = array( 'de', 'at', 'ch', 'lu' );
		$is_german_region = in_array( $country_code, $german_regions, true );
		
		if ( $is_german_region ) {
			// For German-speaking regions, try to get state field.
			$state_value = sanitize_text_field( $address['state'] ?? '' );
			
			// If state is missing, we're dealing with a city-state (like Berlin).
			if ( empty( $state_value ) ) {
				// Get the city name for the state level.
				$city_name = sanitize_text_field(
					$address['city'] ?? $address['town'] ?? $address['village'] ?? ''
				);
				
				if ( ! empty( $city_name ) ) {
					// Use city name as state.
					$location['state'] = $city_name;
					
					// Use city_district (or suburb or borough as fallback) as city to avoid duplication.
					$location['city'] = sanitize_text_field(
						$address['city_district'] ?? $address['suburb'] ?? $address['borough'] ?? ''
					);
				}
			} else {
				// Normal case: state exists separately from city.
				$location['state'] = $state_value;
				
				// Extract city normally.
				$location['city'] = sanitize_text_field(
					$address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? ''
				);
			}
		} else {
			// Non-German regions: use standard fallback chain.
			$location['state'] = sanitize_text_field( 
				$address['state'] ?? $address['region'] ?? $address['province'] ?? '' 
			);
			
			// Extract city normally.
			$location['city'] = sanitize_text_field(
				$address['city'] ?? $address['town'] ?? $address['village'] ?? $address['county'] ?? ''
			);
		}
		
		// Extract street name (road is most common, but also check street and pedestrian).
		$location['street'] = sanitize_text_field(
			$address['road'] ?? $address['street'] ?? $address['pedestrian'] ?? ''
		);
		
		// Extract house/street number.
		$location['street_number'] = sanitize_text_field(
			$address['house_number'] ?? ''
		);
		
		return array_filter( $location );
	}
}
