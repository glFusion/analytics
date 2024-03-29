# Analytics Plugin for glFusion

This plugin provides a method to implement one or more web analytics
providers to track user activity. The following trackers are currently supported:
- Google Analytics (https://analytics.google.com)
- Matomo (https://matomo.org)
- Open Web Analytics (https://www.openwebanalytics.com)
- Smartlook (https://smartlook.com)
- StatCounter (https://statcounter.com). Only the invisible counter is supported.

Once a module is installed and configured it will automatically include the tracking
code between the HTML "head" tags.

The main tracking code is in the templates/trackers directory so you may customize it
as needed.

## Installation
Installation is accomplished by using the glFusion automated plugin installer.

## Tracker Module Configuration
Visit your site's Command and Control section, select "Analytics", and click the Plus icon
to install one or more modules. Each module has its own set of configuration values
that must be set before the module can be used.

Click the Edit icon for the installed module(s) to update the configuration items required.

For trackers that support custom data fields you can use autotags to capture additional information.

For trackers that require you to enter the tracking URL, do not add a trailing slash.

## Global Configuration
### Main Settings
#### Track Admin Actions?
Select `Yes` to have hits made by administrators tracked.

#### Track Admin Pages?
Select "True" to have hits to administrative pages (under the "/admin/" url prefix) tracked.
The default is to omit admin pages from tracking. If `Track Admin Actions` is `No` then this
setting has no effect.

#### Parse Autotags?
If you use autotags in the custom fields, select "True" to enable autotag parsing.
The default for this setting is "False" to improve performance.

Keep in mind that the rendered autotag value will be visible in the page source code.

#### IP Addresses to Block
To prevent tracking your internal usage and skewing statistics, enter one or more
IP addresses which will not include the tracking codes.

