## /sage

This is a [slash command](https://api.slack.com/slash-commands) written in PHP for Slack that interfaces with the [Go Continuous Delivery](https://www.go.cd/) server.

This command allows you fetch some basic info from Go like pipeline build status.

### Supported Commands

The current supported commands are:

* `/sage agents` Output a list of all configured agents and basic information like name, operating system and disk space.
* `/sage status <pipeline>` Show the success or failure state of all stages and jobs within this pipeline
* `/sage search <string>` Search the dashboard for any pipeline matching this regex and show build status

### Installation

* Clone this repo onto your web server
* Run `composer install`
* Copy the sample config `cp config/config.local.php.example config/config.local.php`
* Edit the local config to include your Go server URL & username/password
* [Configure a slash command](https://my.slack.com/services/new/slash-commands) on your Slack team and have it point to the `/slack` end point.
