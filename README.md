# Growella Table of Contents

This WordPress plugin automatically generates a Table of Contents for the current post.

> **Heads up!** This plugin is being developed in the open, with the eventual goal of both powering dynamic tables of contents on [Growella](https://growella.com) as well as being released on [WordPress.org](https://wordpress.org/plugins). That being said, the plugin is still very much under active development and may not be stable enough for production use!


## Usage

Insert a `[toc]` shortcode into your post content wherever you'd like the table of contents to appear. The plugin will automatically generate IDs for every heading in the post content, then build a list wherever you placed the shortcode.

For example, let's imagine you want to embed the table of contents _after_ an introductory paragraph. You have absolute control over the placement by moving the shortcode:

```
<h2>Introduction</h2>
...

[toc]

<h2>First topic</h2>
...

<h2>Second topic</h2>
...
```

For that example, the generated table of contents would look something like:

> * Introduction
> * First topic
> * Second topic

### Shortcode options

The `[toc]` shortcode has a number of available arguments, which can be used to control the generated table of contents on a per-post basis. If you would prefer to change the defaults on a global level, please [see the `growella_table_of_contents_shortcode_defaults` filter](#growella_table_of_contents_shortcode_defaults).

#### depth

> **Note:** This argument has not yet been implemented.

Control how deeply the generated table of contents is nested:

<dl>
<dt>-1</dt>
<dd>Show all found headings (based on the <code>$tags</code> argument) in a flat list. This is the default behavior.</dd>
<dt>0</dt>
<dd>Show only the top-level headings. For example, if a H2-level heading has an H3-level (or higher) heading below it, only the link for the H2 would be displayed in the table of contents.</dd>
<dt><code>$n</code></dt>
<dd>Nest sub-headings in the table of contents until the list gets <code>$n</code> levels deep.</dd>
</dl>


#### tags

A comma-separated list of tags that should be considered to be headings. Default is "h1,h2,h3".


##### Example

To restrict the table of contents to only `<h2>` tags in your content, you can use the following shortcode:

```
[toc tags="h2"]
```


#### title

A title to appear at the top of the table of contents. By default, this value is "Table of Contents". Passing an empty string ([or any other "false-y" value](http://php.net/manual/en/language.types.boolean.php#language.types.boolean.casting)) will prevent this heading from being created.


##### Example

Change the heading to "In this article:":

```
[toc title="In this article:"]
```

Alternatively, if you'd prefer to not show a heading, pass an empty string:

```
[toc title=""]
```


### Available filters

Growella Table of Contents exposes several filters for use with the [WordPress plugin API](https://codex.wordpress.org/Plugin_API).


#### growella_table_of_contents_shortcode_defaults

Modify default settings for the Growella Table of Contents `[toc]` shortcode.

<dl>
<dt>array $defaults</dt>
<dd>Default shortcode attributes.</dd>
</dl>

This filter is ideal if you'd like to override the global defaults to better suit your theme, letting editors still override the new defaults on a per-post basis when needed.

##### Example

Change the default title to "In this article":

```php
/**
 * Override default settings for Growella Table of Contents' [toc] shortcode.
 *
 * @param array $defaults Default shortcode attributes.
 * @return array The modified $defaults.
 */
function mytheme_override_toc_defaults( $defaults ) {
	$defaults['title'] = 'In this article';

	return $defaults;
}
add_filter( 'growella_table_of_contents_shortcode_defaults', 'mytheme_override_toc_defaults' );
```

#### growella_table_of_contents_render_shortcode

Filter the Growella Table of Contents just before returning the rendered shortcode output.

<dl>
<dt>string $output</dt>
<dd>The rendered table of contents.</dd>
<dt>array $atts</dt>
<dd>The shortcode attributes used to build the table of contents.</dd>
<dt>array $links</dt>
<dd>The links used to build the table of contents.</dd>
