<table width="100%">
	<tr>
		<td align="left" width="70">
			<h1>Protected Embeds</h1>
			A drop-in replacement for WordPress.com protected embeds
		</td>
		<td align="right" width="20%">
			
		</td>
	</tr>
	<tr>
		<td>
			A <strong><a href="https://hmn.md/">Human Made</a></strong> project. Maintained by @joehoyle & @roborourke.
		</td>
		<td align="center">
			<img src="https://hmn.md/content/themes/hmnmd/assets/images/hm-logo.svg" width="100" />
		</td>
	</tr>
</table>


### Installation

1. Install the plugin as normal.
2. Define `PROTECTED_EMBEDS_DOMAIN` in your `wp-config.php` as another 
  domain that points to the same WordPress site. For example 
  `myembeds.com`.
  
```php
define( 'PROTECTED_EMBEDS_DOMAIN', 'myembeds.com' );
```

### Dealing with early redirects

In WordPress multisite with a domain mapping solution such as
[Mercator](https://github.com/humanmade/Mercator) running you may find 
requests to your embed domain get redirected too early and adding the 
domain as a site on the network will negate the benefits of a separate
domain if you have SSO enabled. You can work around it by adding the 
following to your `sunrise.php` file:

```php
// Create a dummy site object pointing the protected embeds domain
// to the primary site
add_filter( 'pre_get_site_by_path', function( $site, $domain, $path ) {
	if ( PROTECTED_EMBEDS_DOMAIN === $domain ) {
		$site          = new stdClass;
		$site->id      = 1;
		$site->blog_id = 1;
		$site->site_id = 1;
		$site->domain  = $domain;
		$site->path    = $path;
		$site->public  = 1;
	}
	return $site;
}, 10, 3 );
```
