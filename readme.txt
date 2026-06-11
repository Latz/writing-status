=== Writing Status ===
Contributors: yourwordpressusername
Tags: draft, posts, writing, status, productivity
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.7.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track and sort your draft posts by completion status. See published posts clearly marked in blue with visual indicators.

== Description ==

Writing Status helps content creators and site administrators manage their writing workflow more efficiently. This plugin adds a "Writing Status" column to the WordPress posts list and a sidebar meta box on the post editor, providing clear visual indicators of your content status.

= Key Features =

* **Visual Status Indicators**: Instantly see which posts are published, complete drafts, or incomplete drafts
* **Published Posts Highlighting**: Published posts are clearly marked with a blue dot (●) and "Published" label in blue
* **Draft Completion Tracking**: Mark draft posts as complete or incomplete to track your writing progress
* **Priority Levels**: Assign Low, Medium, High, or Urgent priority to any draft
* **Due Dates**: Set a due date per post; overdue and soon-due dates are highlighted automatically
* **Filter by Status or Priority**: Dropdowns above the posts list let you filter by completion and priority
* **Sortable Column**: Click the "Writing Status" column header to sort posts by status and priority
* **Bulk Edit Support**: Set completion, priority, and due date across multiple posts at once from the Bulk Edit panel
* **Dashboard Widget**: See incomplete and complete drafts at a glance from the WordPress dashboard
* **Unsaved Changes Warning**: The post editor warns you before navigating away with unsaved meta box changes
* **Clean Interface**: Integrates seamlessly with WordPress admin design

= How It Works =

**For Published Posts:**
* Posts with "Published" status automatically display "● Published" in blue
* This appears in both the posts list column and the post editor sidebar
* No manual marking required

**For Draft Posts:**
* A toggle button appears in the post editor meta box: mark the draft complete or incomplete
* Optionally set a priority (Low / Medium / High / Urgent) and a due date
* Complete drafts show "✓ Complete" in green; incomplete drafts show "✗ Incomplete" in red
* Overdue and soon-due dates are highlighted automatically
* Sort your drafts to prioritize incomplete work
* Use the Bulk Edit panel to update completion, priority, or due date across many posts at once
* The editor warns you if you try to navigate away without saving meta box changes

= Use Cases =

* **Content Teams**: Coordinate multiple writers and track which drafts need attention
* **Bloggers**: Manage your editorial calendar and see which posts are ready to publish
* **Site Administrators**: Get a quick overview of content status across your site
* **Freelance Writers**: Track your progress on client work

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "Writing Status"
4. Click "Install Now" and then "Activate"

= Manual Installation =

1. Download the plugin ZIP file
2. Log in to your WordPress admin panel
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Activate the plugin through the Plugins menu

= After Activation =

1. Go to Posts > All Posts to see the new "Writing Status" column
2. Edit any draft post to see the "Completion Status" meta box
3. Toggle the button to mark the draft complete or incomplete, and optionally set a priority and due date
4. Published posts automatically show as "● Published" in blue
5. Use the filter dropdowns above the posts list to narrow by completion or priority
6. Select multiple posts and open Bulk Edit to update Writing Status fields in one action
7. Check the dashboard widget for a live summary of incomplete and complete drafts

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

Yes! Click on the "Writing Status" column header in the posts list to sort by status. Posts are ordered by priority first, then completion.

= Can I update multiple posts at once? =

Yes. Select multiple posts in the posts list, open the Bulk Edit panel, and the Writing Status section lets you set completion, priority, and due date for all selected posts in one action. Leave a field at "— No Change —" to leave it untouched.

= Will this work with the Block Editor (Gutenberg)? =

Yes, the plugin works with both the Classic Editor and the Block Editor (Gutenberg). The editor warns you before navigating away if you have unsaved meta box changes.

== Screenshots ==

1. Writing Status column in the posts list showing published, complete, and incomplete posts
2. Completion Status meta box in the post editor sidebar for draft posts
3. Published post editor showing blue "Published" indicator
4. Sortable Writing Status column in action

== Changelog ==

= 1.7.0 =
* Renamed plugin from "Draft Status" to "Writing Status"
* Renamed database meta keys: `_draft_complete` → `_writing_complete`, `_draft_due_date` → `_writing_due_date`, `_draft_priority` → `_writing_priority`
* Migration runs automatically on plugin (re)activation to rename existing post meta rows
* Added overdue drafts admin notice on Posts list and Dashboard (cached, no performance impact)
* Fixed unsaved-changes warning persisting after saving in Gutenberg

= 1.6.0 =
* Added Writing Status fields to the Bulk Edit panel (completion, priority, due date)
* "— No Change —" sentinel ensures unmodified fields are never overwritten during bulk edits

= 1.5.0 =
* Unsaved changes warning: editor prompts before navigation if meta box changes are pending
* Warning integrates with Gutenberg's built-in "unsaved changes" system via wp.data
* Refactored rendering logic into a separate WritingStatusRenderer class

= 1.4.0 =
* Added priority levels: Low, Medium, High, Urgent
* Added per-post due dates with automatic overdue / soon-due highlighting
* Added filter dropdowns to the posts list for completion and priority
* Added dashboard widget showing incomplete and complete draft counts
* Sort order now considers priority alongside completion status

= 1.2.0 =
* Registered meta fields with REST API support for Gutenberg and headless usage

= 1.0.0 =
* Initial release
* Added Writing Status column to posts list
* Added Completion Status meta box to post editor
* Published posts display with blue dot and "Published" label
* Draft posts can be marked as complete or incomplete
* Sortable status column
* Color-coded visual indicators (blue for published, green for complete, red for incomplete)

== Upgrade Notice ==

= 1.7.0 =
Renames the plugin and its database meta keys. After updating, deactivate and reactivate the plugin once so the migration can rename existing post meta rows. No data is lost.

= 1.6.0 =
Adds Bulk Edit support for Writing Status fields. No database changes required.

= 1.0.0 =
Initial release of Writing Status.

== Additional Information ==

= Support =

For support, please visit the plugin's support forum on WordPress.org or contact us through our website.

= Privacy Policy =

This plugin does not collect, store, or transmit any user data. All completion status information is stored locally in your WordPress database as post meta data.

= Contributing =

We welcome contributions! Visit our GitHub repository to submit issues or pull requests.

== Technical Details ==

= Database Storage =

The plugin stores Writing Status data as post meta:

* `_writing_complete` — `yes` or `no`
* `_writing_priority` — `none`, `low`, `medium`, `high`, or `urgent`
* `_writing_due_date` — date string in `YYYY-MM-DD` format, or absent if not set

All keys are prefixed with an underscore so they are hidden from the custom fields UI. All three fields are registered with REST API support.

= Filters and Actions =

The plugin uses standard WordPress hooks:
* `manage_posts_columns` - Adds the Writing Status column
* `manage_posts_custom_column` - Displays column content
* `manage_edit-post_sortable_columns` - Makes column sortable
* `pre_get_posts` - Handles sorting and filter logic
* `add_meta_boxes` - Adds the post editor meta box
* `save_post` - Saves meta box and bulk edit values
* `restrict_manage_posts` - Adds filter dropdowns to the posts list
* `parse_query` - Applies completion and priority filters
* `bulk_edit_custom_box` - Renders Writing Status fields in the Bulk Edit panel
* `wp_dashboard_setup` - Registers the dashboard widget
* `init` - Registers meta fields for REST API support
* `register_activation_hook` - Migrates legacy `_draft_*` meta keys to `_writing_*` on activation

= Performance =

The plugin is lightweight and has minimal impact on performance. It only loads in the WordPress admin area and uses standard WordPress functions for all operations.
