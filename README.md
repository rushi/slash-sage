## Slash Sage

This is a [Slash command](https://api.slack.com/slash-commands) for Slack interfaces with the [Go Continuous Delivery](https://www.go.cd/) server.

This command allows you some basic interactions

### Supported Commands

The current supported commands are:

* `/sage agents` Output a list of all configured agents and basic information like name, operating system and disk space.
* `/sage status <pipeline>` Show the success or failure state of all stages and jobs within this pipeline
