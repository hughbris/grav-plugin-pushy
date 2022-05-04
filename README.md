# Pushy Plugin

The **Pushy** Plugin is an extension for [Grav CMS](https://github.com/getgrav/grav). Publish ("push") changes to your production environment from your editing (or development or other) environment Admin dashboard.

This plugin uses Git and is heavily inspired by the [GitSync plugin](https://github.com/trilbymedia/grav-plugin-git-sync). Unlike GitSync, however, there is a lot more manual setup by the developer, less (IMHO) scary magic, and more control.

<!-- TODO: feature comparson table -->

The primary use case for Pushy is so that non-technical content editors can edit (primarily) pages on another Grav instance using Grav Admin, and then push their changes from there to the production server instance. This is all tracked by Git and easy to revert.

The advantages of pushing from an editing environment are:

* it provides a safe sandbox, you won't break production;
* it opens up the possibility of multiple content editors;
* there is no need to install the Admin plugin on your production Grav instance.

I took Git out of the plugin name and user interface because content editors and other non-technical users don't care.

## Installation

Installing the Pushy plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin.

### GPM Installation (Preferred)

> This option will be available if/when this plugin is mature enough to be accepted into the official Grav plugin repository.
<!--
To install the plugin via the [GPM](https://learn.getgrav.org/cli-console/grav-cli-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install pushy

This will install the Pushy plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/pushy`.
-->

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `pushy`. You can find these files on [GitHub](https://github.com/hughbris/grav-plugin-pushy) or via [GetGrav.org](https://getgrav.org/downloads/plugins).

You should now have all the plugin files under

    /your/site/grav/user/plugins/pushy
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/hughbris/grav-plugin-pushy/blob/main/blueprints.yaml).

### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/pushy/pushy.yaml` to `user/config/plugins/pushy.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

Note that if you use the Admin Plugin, a file with your configuration named pushy.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

> Because this plugin requires a developer to set it up, creating Admin blueprints is a low priority enhancement. Please edit the YAML instead.

## Usage

**Describe how to use the plugin.**

## Credits

* [GitSync plugin](https://github.com/trilbymedia/grav-plugin-git-sync) from Trilby Media (mostly @woofz I think) for inspiration and some code
* @pamtbaau for assistance with some obscure undocumented Admin techniques that had me stumped

## To Do

- [ ] Switch the Save page button label to 'Save Draft' and stage the edit to the git index on save - this allows git edits to be attributed to the current user reliably, but seems messy with unstaging some changes especially for renames + edits
- [ ] Allow user selection of changes to commit/publish with checkboxes - possibly even an equivalent to `git add -p`
- [ ] Show newly created files within new folders to be clearer - Git currently only shows folders and this could be confusing for new pages (is there a Git option for this??)
- [ ] Remove folder prefixes from previews of changes if possible
- [ ] Flex these items out into proper GH issues
