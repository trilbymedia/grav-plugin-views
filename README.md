# Views Plugin

The **Views** Plugin is for [Grav CMS](http://github.com/getgrav/grav) version 1.6+. This is a simple views count tracking plugin.  You can use it several ways, but by default it will automatically track all site page requests and use the **page route** as the identifying key.  There is no limiting, tracking, or refresh detection, it simply tracks the number of times a page has been loaded.

## Installation

Installing the Views plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

It has a requirement of the Grav **Database** plugin as it stores the views in a simple, file-based sqlite database file.  This will automatically be installed if you use GPM.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install views

## Requirements

Other than standard Grav requirements, this plugin requires the **Database** plugin, which in turn has some system requirements:

* **SQLite3** Database
* **PHP pdo** Extension
* **PHP pdo_sqlite** Driver

| PHP by default comes with **PDO** and the vast majority of linux-based systems already come with SQLite.  

## Configuration

Before configuring this plugin, you should copy the `user/plugins/views/views.yaml` to `user/config/plugins/views.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
autotrack: true    
```

The configuration options are as follows:

* `enabled` - enable or disable the plugin instantly
* `autotrack` - by default, views will track all pages using the `onPageInitialized` event, disable this to track manually

## Usage

The default behavior is for the **view** plugin to track all page requests and keep a running total of how many times the pages have been hit.  You can change this behavior by first _disabling_ the `autotrack:` configuration option, then using the Twig function to track a page hit, or if you want to track via another plugin, you can use a simple PHP command.

### Manual Twig Tracking

To track via Twig, you can use the default `track_views(id)` twig function, but an `id` is required.  For example, to track the current page from a twig template:

```twig
{% do track_views(page.route) %}
```

### Manual PHP Tracking

To track via PHP, you can use the default `views` object with a required `id` attribute.  For example, to track the current page from a PHP file:

```php
Grav::instance()['views']->track($page->route());
```



