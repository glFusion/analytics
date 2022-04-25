# Analytics Plugin for glFusion

This plugin provides a method to implement one or more web analytics
providers to track user activity. The following trackers are currently supported:
- Google Analytics
- Matomo
- Open Web Analytics

Once a module is installed and configured it will automatically include the tracking
code between the HTML "head" tags.

The main tracking code is in the templates/trackers directory so you may customize it
as needed.

## Installation
Installation is accomplished by using the glFusion automated plugin installer.

## Configuration
Visit your site's Command and Control section, select "Analytics", and click the Plus icon
to install one or more modules. Each module has its own set of configuration values
that must be set before the module can be used.

Click the Edit icon for the installed module(s) to update the configuration items required.

For trackers that support custom data fields you can use autotags to capture information
from the current user.
