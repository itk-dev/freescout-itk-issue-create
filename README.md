# Create issue module for Freescout

## About
This module handles api connections and webhooks in relation to issue creation
in Freescout.
- Adds a To-do in Leantime through use of the Leantime API.
- Uses a Teams webhook to add notification in teams channel.
- Adds a note on the Freescout issue with link to the Leantime issue.

## Installation
- Clone this repo into the Modules directory of Freescout and name it
ItkIssueCreate.
```
git clone git@github.com:itk-dev/freescout-itk-issue-create.git ItkIssueCreate
```

### Configuration
A few variables needs to be set in Freescouts .env file
```
### Teams webhook ###
TEAMS_WEB_HOOK=

### Leantime ###
# The Leantime scheme & domain name
LEANTIME_URL=https://example.com

# A Leantime API key
LEANTIME_API_KEY=

# The project key of the project to add To-do's to.
LEANTIME_PROJECT_KEY=
```

### Finish

Dump autoload files and empty freescout cache.
```
idc exec phpfpm composer dumpautoload
idc exec phpfpm php artisan freescout:clear-cache
```

### Enable module
Log into freescout and go to: ```/modules/list``` to enable this module.

## Future development
- Implement proper string translation
- Implement configuration page
- Add tests
- Add code analysis with phpstan and github action
- Add artisan commands for testing connections and API