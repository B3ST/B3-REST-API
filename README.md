# B3 REST API Extensions

This plugin extends the [WP-API](https://github.com/WP-API/WP-API) in order to retrieve data not yet handled by the official plugin, such as settings, menus and sidebars.

[![Build Status](https://scrutinizer-ci.com/g/B3ST/B3-REST-API/badges/build.png?b=master)](https://scrutinizer-ci.com/g/B3ST/B3-REST-API/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/B3ST/B3-REST-API/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/B3ST/B3-REST-API/?branch=master)

## Warning

**The official WP API is undergoing substantial changes and may break compatibility with B3 at any time.**

It is recommended that you either use [the version on WordPress.org](https://wordpress.org/plugins/json-rest-api/) or at the very least stick to the [master branch](https://github.com/WP-API/WP-API) in order to minimize issues.

Also, please bear in mind that B3 is a work in progress and can't (yet) be considered ready for production use. Do so at your own risk.

## Installation

1. Install the official [WP-API](https://wordpress.org/plugins/json-rest-api/) plugin and activate it.
2. Clone the `B3-REST-API` repository into the _plugins_ folder of your WordPress install.
3. Navigate to _Plugins_ in the WordPress Admin, look for "B3 REST API Extensions" and activate it.
4. As with the WP-API plugin, the B3 REST API Extensions require pretty permalinks to be enabled.

## Endpoints

The B3 REST API Extensions plugin enables the following additional endpoints.

To make it easier to tell B3 extensions apart from the other endpoints, all of our additions are prepended by `b3:`.

### Breaking changes in `develop`

We're adapting our API controllers to the new format proposed by the WP-API team and introducing the `b3` namespace, and as a result endpoints will undergo a few (mostly minor) changes.

The tables below should give you an indication of how things are going to work in the future.

### Comments

We provide an alternative implementation of the Comments resource that allows retrieving comments without having to know which post they belong to.

|        | `master`                       | `develop`                   |
| ------ | ------------------------------ | --------------------------- |
| `GET`  | `/b3:comments/<id>`            | `/b3/comments/<id>`         |
| `GET`  | `/b3:comments/<id>/b3:replies` | `/b3/comments/<id>/replies` |
| `POST` | `/b3:comments/<id>/b3:replies` | `/b3/comments/<id>/replies` |
| `GET`  | `/media/<id>/b3:replies`       | `/b3/media/<id>/replies`    |
| `POST` | `/media/<id>/b3:replies`       | `/b3/media/<id>/replies`    |
| `GET`  | `/pages/<id>/b3:replies`       | `/b3/pages/<id>/replies`    |
| `POST` | `/pages/<id>/b3:replies`       | `/b3/pages/<id>/replies`    |
| `GET`  | `/posts/<id>/b3:replies`       | `/b3/posts/<id>/replies`    |
| `POST` | `/posts/<id>/b3:replies`       | `/b3/posts/<id>/replies`    |

### Media

This endpoint provides a way to fetch a media attachment by its slug.

|        | `master`                       | `develop`                   |
| ------ | ------------------------------ | --------------------------- |
| `GET`  | `/media/b3:slug:<slug>`        | `/b3/media/slug/<slug>`     |

### Posts

This endpoint provides a way to fetch a post by its slug.

|        | `master`                       | `develop`                   |
| ------ | ------------------------------ | --------------------------- |
| `GET`  | `/posts/b3:slug:<slug>`        | `/b3/posts/slug/<slug>`     |

### Menus

We provide endpoints to fetch all Menus registered by the theme as well as the menu items configured in the WordPress Admin.

|        | `master`                       | `develop`                   |
| ------ | ------------------------------ | --------------------------- |
| `GET`  | `/b3:menus`                    | `/b3/menus`                 |
| `GET`  | `/b3:menus/<location>`         | `/b3/menus/<location>`      |

### Sidebars

Similar to Menus, we allow fetching all widget areas registered by the theme as well as their widgets and widget content.

|        | `master`                       | `develop`                   |
| ------ | ------------------------------ | --------------------------- |
| `GET`  | `/b3:sidebars`                 | `/b3/sidebars`              |
| `GET`  | `/b3:sidebars/<index>`         | `/b3/sidebars/<index>`      |

### Settings

Finally, this plugin exposes WordPress settings through the API.  It wraps the `get_bloginfo()` function but exposes a few additional site options as well as the pretty permalinks table.

|        | `master`                       | `develop`                   |
| ------ | ------------------------------ | --------------------------- |
| `GET`  | `/b3:settings`                 | `/b3/settings`              |
| `GET`  | `/b3:settings/<option>`        | `/b3/settings/<option>`     |

