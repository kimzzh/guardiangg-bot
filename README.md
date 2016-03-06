# guardiangg-bot
[![Deployment status from DeployBot](https://slavikus.deploybot.com/badge/13023224041108/66629.svg)](http://deploybot.com)

This is a quick & dirty bot for Telegram to interface with excellent Guardian.GG website and API and quickly report fireteams and Elo status for Destiny game by Bungie.

## Setup

1. Copy `config.dist.inc.php` into `config.inc.php`
2. Obtain all needed API keys from Telegram, Bungie.Net and Google Analytics and insert them into `config.inc.php`
3. Edit the rest of `config.inc.php` to match your tastes.
4. Upload all the files to the server of your liking. It *must* support HTTPS.
5. Hop onto your uploaded script `index.php?secret=123`, where *123* is the secret word you've chosen as `ADMIN_PASSWORD` in `config.inc.php`
6. Follow the instructions (coming soon).

## Commands Supported

* */about* - Information about the bot
* */elo* - Elo ratings for a given PSN or XBL nickname taken from [Guardian.GG](https://guardian.gg)
* */too* - Elo ratings, K/D values and other stats for last known fireteam in Trials of Osiris
* */skirmish* - Elo ratings, K/D values and other stats for last known fireteam in Skirmish mode

## Credits & Thanks

Thank you to the creators of [Guardian.GG](https://guardian.gg). Without them, this bot would be totally useless. Please consider [donating](http://guardian.gg/en/faq) to the authors.

