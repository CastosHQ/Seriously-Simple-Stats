=== Seriously Simple Stats ===
Contributors: hlashbrooke
Tags: seriously simple podcasting, stats, statistics, listeners, analytics, podcast, podcasting, ssp, free, add-ons, extensions, addons
Requires at least: 4.4
Tested up to: 4.6
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Integrated analytics and stats tracking for Seriously Simple Podcasting.

== Description ==

> This plugin is an add-on for [Seriously Simple Podcasting](http://www.seriouslysimplepodcasting.com/) and requires at least **v1.13.1** of Seriously Simple Podcasting in order to work.

So you love using Seriously Simple Podcasting to broadcast your content? Well, now you can enjoy the plugin's simplicity while also gathering useful and extensive stats about how people are engaging with your inevitably brilliant content.

Seriously Simple Stats offers integrated analytics for your podcast, giving you access to incredibly useful information about who is listening to your podcast and how they are accessing it.

**Primary Features**

- Track listen counts for every episode
- View detailed stats for your podcast
- See a quick overview of your most recent stats at a glance
- Narrow stats down to view data specific to any episode or series
- Access stats for any selected date range
- Find out exactly which of your episodes are the most popular
- Find out how people are listening to your content
- Automatically detect crawlers/bots to prevent false stats

Check out the [screenshots](https://wordpress.org/plugins/seriously-simple-stats/screenshots/) for a better idea of what stats you can expect.

**How to contribute**

If you want to contribute to Seriously Simple Stats, you can [fork the GitHub repository](https://github.com/hlashbrooke/Seriously-Simple-Stats) - all pull requests will be reviewed and merged if they fit into the goals for the plugin.

== Installation ==

Installing "Seriously Simple Stats" can be done either by searching for "Seriously Simple Stats" via the "Plugins > Add New" screen in your WordPress dashboard, or by using the following steps:

1. Download the plugin via WordPress.org
1. Upload the ZIP file through the 'Plugins > Add New > Upload' screen in your WordPress dashboard
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. An example of the stats page showing all the available information.
2. The Episode Stats meta box on the episode edit screen.

== Frequently Asked Questions ==

= What version of Seriously Simple Podcasting does this plugin require? =

In order to use this plugin you need to have at least v1.13.1 of Seriously Simple Podcasting. If you do not have Seriously Simple Podcasting active or you are using a version older than v1.13.1 then this plugin will do nothing.

= I just installed this plugin, but I don't see any stats. What gives? =

Your podcast stats will only be gathered while this plugin is active, so if you have just installed it wait a few days and you will start to see your stats growing.

= What listening sources are tracked? =

Seriously Simple Stats tracks the following listening sources for your podcast:

- iTunes / iOS Podcasts
- Overcast
- Pocket Casts
- Direct downloads
- Audio player listens
- 'Play in new window' clicks

Any other listening sources will simply appear grouped as 'Other' in your stats. If there is another service that you think would be valuable to have tracked, then please [create a new issue on GitHub](https://github.com/hlashbrooke/Seriously-Simple-Stats/issues/new) to suggest it.

Note that Stitcher download stats are not currently possible to gather accurately due to the way that Stitcher offers up episode downloads to users.

= Does this plugin block crawlers or bots? =

Yes. This plugin uses the [Crawler Detect](https://github.com/JayBizzle/Crawler-Detect) library to make sure that crawlers/bots are not recorded as listens.

== Changelog ==

= 1.1 =
* 2016-09-07
* [NEW] Including Crawler Detect library to prevent crawlers/bots from bring recorded as actual listens
* [NEW] Checking for reverse proxies when fetching user's IP address for more accurate listenership stats (props [cgill27](https://github.com/cgill27))
* [TWEAK] Recording correct time for all stats (based on WordPress settings)

= 1.0.1 =
* 2016-07-21
* [FIX] Making sure that time-based stats respect WordPress' saved time zone instead of basing everything on UTC
* [FIX] Improving tracking of iTunes/iOS Podcasts app referrer (props [tbibby](https://github.com/tbibby))
* [FIX] Making sure that stats never show without labels
* [TWEAK] Improving localisation support
* [TWEAK] Generally improving referrer detection (props [tbibby](https://github.com/tbibby))

= 1.0 =
* 2015-11-16
* Initial release

== Upgrade Notice ==

= 1.1 =
* This version includes crawler/bot detection to prevent false stats, as well as a few additional tweaks to make stats tracking more reliable and accurate.