# Roundcube plugin remove-attachments

Roundcube plugin to add an option to remove one or all attachments from a message.
The original code is from Philip Weir.

Stable versions of this plugin are available from the [Roundcube plugin repository][rcplugrepo] or the [releases section][releases] of the GitHub repository.


## Requirements

None.


## Installation

#### Install with composer

1. Go to your Roundcube root directory.

2. Run `$ composer require dsoares/removeattachments`.

#### Manual Installation

1. Place this folder named `removeattachments` under your Rouncdube `plugins/` folder. The folder's name must be `removeattachments`.

2. Enable the removeattachments plugin within the main Roundcube configuration file `config/config.inc.php`.


## License

This plugin is released under the [GNU General Public License Version 3+][gpl].

## Contact

Comments and suggestions are welcome!

Email: [Diana Soares][dsoares]

[rcplugrepo]: https://plugins.roundcube.net/packages/dsoares/removeattachments
[releases]: https://github.com/dsoares/roundcube-removeattachments/releases
[gpl]: https://www.gnu.org/licenses/gpl.html
[dsoares]: mailto:diana.soares@gmail.com
