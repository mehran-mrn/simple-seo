=== MRN SEO ===
Contributors: mehranmarandi
Tags: seo, local seo, schema, sitemap, redirects, open graph
Requires at least: 6.6
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 2.1.1
License: GPLv2 or later

Lightweight, site-agnostic technical and local SEO for WordPress.

== Description ==

MRN SEO provides configurable titles, descriptions, canonical URLs, Open Graph metadata,
local business schema, FAQ schema, redirect rules, crawler controls, and a Yoast-compatible
XML sitemap index. All organization identity and site-specific behavior is stored in WordPress
settings. The plugin contains no theme, hostname, customer, industry, or website profiles.

== Configuration ==

Open Settings > MRN SEO. Configure business identity, schema type, social images, homepage
metadata, page-specific metadata, redirects, indexing rules, and sitemap exclusions.

Page metadata format: slug :: SEO title :: meta description

Redirect format: old-path | destination | 301

FAQ schema format: question | answer

The Import and export section downloads every supported settings key, including explicit
empty values. Import validates the document format and replaces the current SEO settings.

== Changelog ==

= 2.1.1 =
* Exclude tag and category taxonomies from XML sitemaps when their archive noindex setting is enabled.

= 2.1.0 =
* Add complete JSON settings export with explicit empty values for every supported key.
* Add validated JSON settings import from the MRN SEO administration screen.

= 2.0.0 =
* Remove all embedded customer, theme, hostname, language, image, metadata, sitemap, and redirect profiles.
* Rename the product and public API to MRN SEO.
* Make all site-specific behavior editable from the WordPress settings screen.
