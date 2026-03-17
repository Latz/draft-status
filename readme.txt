=== Draft Status ===
Contributors: yourwordpressusername
Tags: draft, posts, writing, status, productivity
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track and sort your draft posts by completion status. See published posts clearly marked in blue with visual indicators.

== Description ==

Draft Status helps content creators and site administrators manage their writing workflow more efficiently. This plugin adds a "Writing Status" column to the WordPress posts list and a sidebar meta box on the post editor, providing clear visual indicators of your content status.

= Key Features =

* **Visual Status Indicators**: Instantly see which posts are published, complete drafts, or incomplete drafts
* **Published Posts Highlighting**: Published posts are clearly marked with a blue dot (●) and "Published" label in blue
* **Draft Completion Tracking**: Mark draft posts as complete or incomplete to track your writing progress
* **Sortable Column**: Click the "Writing Status" column header to sort posts by their completion status
* **Sidebar Meta Box**: Quick status indicator in the post editor sidebar
* **Clean Interface**: Integrates seamlessly with WordPress admin design

= How It Works =

**For Published Posts:**
* Posts with "Published" status automatically display "● Published" in blue
* This appears in both the posts list column and the post editor sidebar
* No manual marking required

**For Draft Posts:**
* A checkbox appears in the post editor sidebar: "Mark this draft as complete"
* Check the box when you've finished writing to mark it as complete
* Complete drafts show "✓ Complete" in green
* Incomplete drafts show "✗ Incomplete" in red
* Sort your drafts to prioritize incomplete work

= Use Cases =

* **Content Teams**: Coordinate multiple writers and track which drafts need attention
* **Bloggers**: Manage your editorial calendar and see which posts are ready to publish
* **Site Administrators**: Get a quick overview of content status across your site
* **Freelance Writers**: Track your progress on client work

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Draft Status"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin through the Plugins menu

= After Activation =

1. Go to Posts > All Posts to see the new "Writing Status" column
2. Edit any draft post to see the "Completion Status" meta box in the sidebar
3. Check the box to mark drafts as complete
4. Published posts will automatically show as "● Published" in blue

== Frequently Asked Questions ==

= Does this plugin work with custom post types? =

Currently, the plugin only works with standard WordPress posts. Support for custom post types may be added in future versions.

= Can I change the colors of the status indicators? =

The current version uses fixed colors that match WordPress admin design standards. Future versions may include customization options.

= What happens when I publish a draft that's marked as complete? =

When you publish a post, it will automatically show "● Published" in blue, regardless of its previous completion status. The completion status only applies to drafts.

= Does this affect the front-end of my site? =

No, this plugin only adds functionality to the WordPress admin area. It has no impact on your site's front-end appearance or performance.

= Can I sort posts by writing status? =

Yes! Click on the "Writing Status" column header in the posts list to sort by status.

= Will this work with the Block Editor (Gutenberg)? =

Yes, the plugin works with both the Classic Editor and the Block Editor (Gutenberg).

== Screenshots ==

1. Writing Status column in the posts list showing published, complete, and incomplete posts
2. Completion Status meta box in the post editor sidebar for draft posts
3. Published post editor showing blue "Published" indicator
4. Sortable Writing Status column in action

== Changelog ==

= 1.0.0 =
* Initial release
* Added Writing Status column to posts list
* Added Completion Status meta box to post editor
* Published posts display with blue dot and "Published" label
* Draft posts can be marked as complete or incomplete
* Sortable status column
* Color-coded visual indicators (blue for published, green for complete, red for incomplete)

== Upgrade Notice ==

= 1.0.0 =
Initial release of Draft Status.

== Additional Information ==

= Support =

For support, please visit the plugin's support forum on WordPress.org or contact us through our website.

= Privacy Policy =

This plugin does not collect, store, or transmit any user data. All completion status information is stored locally in your WordPress database as post meta data.

= Contributing =

We welcome contributions! Visit our GitHub repository to submit issues or pull requests.

== Technical Details ==

= Database Storage =

The plugin stores draft completion status as post meta data using the key `_draft_complete`. This data is only relevant for draft posts and does not affect published posts.

= Filters and Actions =

The plugin uses standard WordPress hooks:
* `manage_posts_columns` - Adds the Writing Status column
* `manage_posts_custom_column` - Displays column content
* `manage_edit-post_sortable_columns` - Makes column sortable
* `pre_get_posts` - Handles sorting logic
* `add_meta_boxes` - Adds the sidebar meta box
* `save_post` - Saves completion status

= Performance =

The plugin is lightweight and has minimal impact on performance. It only loads in the WordPress admin area and uses standard WordPress functions for all operations.
