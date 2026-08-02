=== MRN SEO Profiles ===
Contributors: mehran-mrn
Tags: seo, local seo, schema, sitemap, open graph
Requires at least: 6.6
Requires PHP: 8.1
Stable tag: 1.2.0
License: GPLv2 or later

Lightweight technical and local SEO with universal, automatic XML sitemaps.

== Description ==

Provides safe generic metadata and business schema on any WordPress site, with optional
specialized profiles for WDS Driving School and Zarsam Gold. Its Yoast-style XML sitemap
index automatically includes public posts, pages, products, custom post types, taxonomies,
and author archives, with pagination and last-modified timestamps. Legacy WDS integrations
remain available for the existing custom theme.

When Yoast SEO, Rank Math, All in One SEO, or SEOPress is active, this plugin pauses
its metadata, schema, and sitemap output to avoid duplicate SEO markup.

== Changelog ==

= 1.2.0 =

* Replace the fixed WDS-only sitemap with a universal Yoast-style sitemap index at /sitemap_index.xml.
* Generate paginated post type, taxonomy, and author sitemaps from WordPress core providers.
* Redirect /sitemap.xml and /wp-sitemap.xml to the canonical sitemap index and advertise it in robots.txt.
* Add safe generic defaults so unknown sites no longer receive WDS metadata, schema, or redirects.
* Preserve the existing WDS and Zarsam profiles and backward-compatible theme integration functions.

= 1.1.0 =

* Add a safe Zarsam Gold SEO profile with Persian metadata, official logo, JewelryStore schema, and WordPress sitemap integration.
* Keep WDS-only redirects and focused sitemap behavior isolated to the WDS host.

= 1.0.2 =

* Synchronize the canonical WDS address and map coordinates with Google Business Profile.
* Use valid LocalBusiness and EducationalOrganization schema types.
* Advertise only the canonical sitemap in robots.txt and remove stored review metrics.

= 1.0.1 =
* Pin the embedded map to the verified new-location coordinates.

= 1.0.0 =
* Initial release for the WDS production launch.
