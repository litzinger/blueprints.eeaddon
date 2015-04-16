# Important
This add-on is provided as is, without warranty or support. I will accept pull requests for fixes or features, however, I will not be actively developing for this add-on. For more information on this please see https://boldminded.com/news/blueprints-is-no-longer-for-sale

# Requirements
ExpressionEngine 2.5+
Structure or Pages module

## Settings
After installing Blueprints go to Add-ons > Extensions and click the Settings link for Blueprints.

### Mode
This option puts Blueprints into one of two modes, Simple or Advanced. Advanced, e.g. taking over the native Publish Layout behavior in ExpressionEngine, lets you use Blueprints to its fullest capabilities. Simple mode lets you use Blueprints to manage the templates that are visible in the Structure and Pages template drop down menu. The normal behavior for these menus is to list all templates available, which can be confusing to a client. Advanced mode lets you define new names for each Publish Layout, as well as a preview thumbnail and template assigned to the layout.

### Enable Template Carousel?
This is a new feature to Blueprints 2.0. It presents the available Publish Layouts in a horizontal carousel if thumbnail preview images are defined for all Publish layouts.

### Publish Layout Name / Template / Thumbnail
This is where you can add a custom name to a Publish Layout, assign a template to it, and select a thumbnail image to represent it. If you are using the Assets module from Pixel & Tonic it will be used to select a thumbnail image, otherwise the native File Manager will be used. Both will add a small thumbnail to the settings page for preview, but when editing an entry the thumbnail will be presented as a 155px wide image. The recommended dimensions are 155px by 180px. The height is flexible and up to you, 180px is a recommended size.

### Detailed Template Visibility
This option lets you define exactly which templates are available in the template select menu and carousel. If a template is tied to a Publish Layout Name it will automatically show on the publish page. This option is usable when Blueprints is in either mode, but is more applicable to Simple mode.

If a green bullet point, like this •, is next to the Publish Layout Name it means that the template is directly assigned to a Publish Layout and choosing it show and activate a button labeled Load layout, which initiates a layout switch. If no bullet point is present you can still select that template as the active template for that entry, but no layout switching will be available.

### Layout Switching
Clicking Load Layout will initiate a Publish Layout change by creating an autosave entry within ExpressionEngine. This does require a page reload and will happen fairly quickly. The reason for the page reload is very deliberate. Layout Switching could have been made instant, similar to the Entry Type add-on, but I choose not to build it that way for one specific reason: required fields. Showing or hiding required fields, retaining the Publish Layout, and ensuring that required fields would remain required would be very difficult and be potentially very fragile behavior.

