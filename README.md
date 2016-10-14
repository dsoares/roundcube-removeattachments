Roundcube Webmail RemoveAttachments
===================================

This plugin adds an option to remove one or all attachments from a message.
The original code is from Philip Weir.

Stable versions of RemoveAttachments are available from the [Roundcube plugin repository][rcplugrepo] or the [releases section][releases] of the GitHub repository.


Requirements
------------

None.

Installation with composer
----------------------------------------

Add the plugin to your `composer.json` file:

    "require": {
        (...)
        "dsoares/removeattachments": "~0.1"
    }

And run `$ composer update [--your-options]`.

Manual Installation
----------------------------------------

1. Place this folder under your Rouncdube `plugins/` folder. The folder's name must be `removeattachments`.
1. Enable the removeattachments plugin within the main Roundcube configuration file `config/config.inc.php`.

*Note: When downloading the plugin from GitHub you will need to create a
directory called removeattachments and place the files in there,
ignoring the root directory in the downloaded archive directory in the
downloaded archive.*

License
----------------------------------------

This plugin is released under the [GNU General Public License Version 3+][gpl].

Contact
----------------------------------------

Comments and suggestions are welcome!

Email: [Diana Soares][dsoares]

[rcplugrepo]: http://plugins.roundcube.net/packages/dsoares/removeattachments
[releases]: http://github.com/JohnDoh/Roundcube-Plugin-RemoveAttachments/releases
[gpl]: http://www.gnu.org/licenses/gpl.html
[dsoares]: mailto:diana.soares@gmail.com
