# B3 REST API Extensions

This plugin extends the [WP-API](https://github.com/WP-API/WP-API) in order to retrieve data not yet handled by the official plugin, such as settings, menus and sidebars.

This plugin is under active development and should not be considered for production environments.

## Installation

1. Install the official [WP-API](https://wordpress.org/plugins/json-rest-api/) plugin and activate it.
2. Clone the `B3-REST-API` repository into the _plugins_ folder of your WordPress install.
3. Navigate to _Plugins_ in the WordPress Admin, look for "B3 REST API Extensions" and activate it.
4. As with the WP-API plugin, the B3 REST API Extensions require pretty permalinks to be enabled.

## Endpoints

The B3 REST API Extensions plugin enables the following additional endpoints.

To make it easier to tell B3 extensions apart from the other endpoints, all of our additions are prepended by `b3:`.

### Comments

We provide an alternative implementation of the Comments resource that allows retrieving comments without having to know which post they belong to.

* `GET` `/b3:comments/<id>`
* `GET` `/b3:comments/<id>/b3:replies`
* `POST` `/b3:comments/<id>/b3:replies`
* `GET` `/pages/<id>/b3:replies`
* `POST` `/pages/<id>/b3:replies`
* `GET` `/posts/<id>/b3:replies`
* `POST` `/posts/<id>/b3:replies`

### Posts

This endpoint provides a way to fetch a post by its slug.

* `GET` `/posts/b3:slug:<slug>`

### Menus

We provide endpoints to fetch all Menus registered by the theme as well as the menu items configured in the WordPress Admin.

* `GET` `/b3:menus`
* `GET` `/b3:menus/<location>`

### Sidebars

Similar to Menus, we allow fetching all widget areas registered by the theme as well as their widgets and widget content.

* `GET` `/b3:sidebars`
* `GET` `/b3:sidebars/<index>`

### Settings

Finally, this plugin exposes WordPress settings through the API.  It wraps the `get_bloginfo()` function but exposes a few additional site options as well as the pretty permalinks table.

* `GET` `/b3:settings`
* `GET` `/b3:settings/<option>`
