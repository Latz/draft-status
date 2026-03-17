# Draft Status Indexer

A WordPress plugin that helps you manage draft posts with completion tracking, priority levels, and due dates.

## The Problem

I was managing a blog with 47 draft posts. Some were half-written ideas, others were complete and just needed a final review before publishing. Every time I opened the Posts screen, I had to click into each draft to remember which ones were actually ready to go and which still needed work.

WordPress shows you when drafts were last modified, but that doesn't tell you anything about whether they're complete. A draft modified yesterday might be 10% done, while one from three weeks ago could be ready to publish. I needed a way to track this without leaving WordPress or using a separate project management tool.

So I built this plugin to solve that exact problem.

## What It Does

Draft Status Indexer adds three metadata fields to your draft posts:

- **Completion status**: Mark drafts as "Complete" (ready for review) or "Incomplete" (still in progress)
- **Priority levels**: Tag drafts with Urgent, High, Medium, or Low priority
- **Due dates**: Set target completion dates to track deadlines

Everything appears right in your WordPress admin—no external tools required. The posts list gets a new "Writing Status" column showing visual indicators for each draft's status, and you can filter and sort by any of these fields.

## Quick Start

```bash
# Clone into your WordPress plugins directory
cd wp-content/plugins/
git clone https://github.com/Latz/DraftStatusIndexer.git

# Or download and extract
cd wp-content/plugins/
wget https://github.com/Latz/DraftStatusIndexer/archive/refs/heads/main.zip
unzip main.zip
```

Then activate through WordPress Admin → Plugins.

No configuration needed. Start using it immediately by editing any draft post.

## Features

### Completion Status Tracking

When editing a draft, you'll see a new meta box in the sidebar with a checkbox: "Mark this draft as complete."

Check it when your draft is ready for review or publication. The posts list will show:
- ✓ **Complete** in green
- ✗ **Incomplete** in red

You can filter the posts list to show only complete or incomplete drafts, making it easy to find posts that need attention or are ready to publish.

### Priority Levels

Set priority on any draft using five levels: Urgent, High, Medium, Low, or None. Each priority gets a color-coded badge:

- **Urgent**: Red badge
- **High**: Orange badge
- **Medium**: Blue badge
- **Low**: Green badge

The posts list can be sorted by priority, and the dashboard widget (described below) automatically organizes drafts with urgent items at the top.

This is particularly useful when managing multiple drafts and you need to decide what to work on next. I added this feature when I realized I was mentally categorizing my drafts anyway—might as well make it explicit.

### Due Dates

Add a target completion date to any draft. The plugin shows visual indicators for:
- **Overdue** drafts (past the due date)
- **Due today** drafts
- **Due soon** drafts (within 7 days)

This helps with editorial calendar management and deadline tracking without needing a separate calendar tool.

### Dashboard Widget

The plugin adds a "Draft Posts Overview" widget to your WordPress dashboard showing all drafts at a glance:

- Incomplete drafts appear first, then complete ones
- Sorted by priority (urgent first) then modification date
- Shows each draft's title, priority badge, modification date, and due date if set
- Displays total counts: "Showing X incomplete and Y complete drafts"

It's designed to answer the question "What should I work on right now?" as soon as you log into WordPress.

### Accessibility (WCAG 2.1 Level AA)

The latest version includes full accessibility support:

- ARIA labels on all status indicators and interactive elements
- Screen reader descriptions for completion status, priority badges, and due dates
- Semantic HTML5 structure (using `<section>` elements with proper ARIA attributes)
- Keyboard navigation support

All status indicators include `role="status"` and descriptive `aria-label` attributes. For example, the dashboard widget's edit links announce "Edit incomplete draft: [Title], last modified [Date]" to screen reader users.

### REST API Support

The plugin registers meta fields with WordPress's REST API, making it compatible with:

- Gutenberg block editor
- Headless WordPress setups
- Custom admin interfaces
- Third-party tools that use the WordPress API

Meta fields exposed: `_draft_complete`, `_draft_priority`, `_draft_due_date`

## Technical Implementation

### Database Structure

The plugin stores three post meta fields:

```php
_draft_complete   // 'yes' or empty string
_draft_priority   // 'urgent', 'high', 'medium', 'low', 'none'
_draft_due_date   // YYYY-MM-DD format
```

Using the underscore prefix keeps these fields hidden from custom fields UI, and they're registered with the REST API for Gutenberg compatibility.

### Security

- Nonce verification on all form submissions
- Capability checks (only users with `edit_post` permission can modify status)
- Input sanitization using `sanitize_text_field()` and validation against allowed values
- Output escaping with `esc_html()`, `esc_attr()`, and `esc_url()`

### Performance

The plugin uses WordPress's standard query modifications rather than custom queries. Sorting and filtering work through `pre_get_posts` hooks with proper meta query clauses, which means they leverage existing database indexes.

The dashboard widget limits queries to draft posts only and includes pagination (showing up to 50 drafts). Admin styles are only enqueued on relevant pages.

### Internationalization

All user-facing strings use the `draft-status-indexer` text domain and are translation-ready. The `/languages/` directory contains POT files for translators.

## Usage Examples

### Basic Workflow

1. Write a new post and save it as a draft
2. In the sidebar meta box, leave "Mark this draft as complete" unchecked
3. Set a priority (if desired) and due date
4. When you finish writing, check the completion box
5. Go to Posts → All Posts and filter by "Complete" to see all drafts ready for publication

### Editorial Team Scenario

If you're coordinating multiple writers:

1. Writers mark their drafts as complete when ready for review
2. Editors filter the posts list to show only complete drafts
3. Use priority levels to coordinate which posts to review first
4. The dashboard widget gives editors a quick overview when they log in

### Content Calendar Management

1. Create draft posts for upcoming content
2. Set due dates based on your editorial calendar
3. Set priority levels based on importance or traffic potential
4. The dashboard widget shows overdue drafts at a glance
5. Sort the posts list by due date to see what's coming up

## Browser Compatibility

Tested with:
- Chrome 120+
- Firefox 121+
- Safari 17+
- Edge 120+

Works with WordPress 5.0+ (tested up to 6.4).

## Comparison with Alternatives

There are WordPress plugins for project management (like WP Project Manager) and editorial calendars (like Edit Flow or PublishPress), but they're comprehensive solutions with team features, complex workflows, and often paid tiers.

Draft Status Indexer does one thing: it helps you track which drafts are done and which aren't. No user roles, no Gantt charts, no subscription. If you need full editorial workflow management, those plugins are better choices. If you just need to mark your drafts as complete and see that status at a glance, this plugin is simpler.

Think of it as the difference between a todo app and a full project management suite.

## Contributing

Contributions are welcome! The codebase follows WordPress coding standards.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/Latz/DraftStatusIndexer.git
cd DraftStatusIndexer

# Install in a local WordPress instance
ln -s $(pwd) /path/to/wordpress/wp-content/plugins/DraftStatusIndexer
```

### Coding Standards

- Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- Use meaningful variable names and add inline documentation
- Include translation functions for all user-facing strings
- Test with Gutenberg and Classic Editor

### Submitting Changes

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/your-feature`)
3. Commit your changes with clear messages
4. Push to your fork
5. Open a pull request with a description of what you've changed and why

### Areas for Contribution

Some ideas if you're looking for something to work on:

- **Custom post type support**: Currently works only with posts
- **Bulk actions**: Mark multiple drafts as complete/incomplete at once
- **Email notifications**: Alert when drafts are overdue
- **Export functionality**: Export draft status reports to CSV
- **Additional filters**: More granular filtering options in the posts list

## Changelog

### Version 1.4.0 (Current)
- Added ARIA attributes and semantic HTML for WCAG 2.1 Level AA compliance
- Improved screen reader support with descriptive labels
- Enhanced keyboard navigation

### Version 1.3.0
- Added REST API support for Gutenberg compatibility
- Registered meta fields for headless WordPress setups

### Version 1.2.0
- Added dashboard widget showing draft overview
- Implemented priority-based sorting
- Added due date tracking

### Version 1.1.0
- Added priority levels (Urgent, High, Medium, Low, None)
- Added priority filtering and sorting
- Improved performance with optimized queries

### Version 1.0.0
- Initial release
- Basic completion status tracking
- Sortable status column in posts list
- Meta box in post editor

## License

GPL v2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).

This plugin is free software. You can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation.

## Credits

Created by [Latz](https://elektroelch.de)

If you find this plugin useful, consider starring the repository or contributing a feature.

## Support

- **Issues**: [GitHub Issues](https://github.com/Latz/DraftStatusIndexer/issues)
- **Feature Requests**: Open an issue with the "enhancement" label
- **Questions**: Check existing issues or open a new one

## Links

- **GitHub**: https://github.com/Latz/DraftStatusIndexer
- **Author Website**: https://elektroelch.de
- **WordPress Plugin Directory**: Coming soon
