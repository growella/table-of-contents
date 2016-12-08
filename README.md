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

#### tags

A comma-separated list of tags that should be considered to be headings. Default is "h1,h2,h3".

##### Example

To restrict the table of contents to only `<h2>` tags in your content, you can use the following shortcode:

```
[toc tags="h2"]
```