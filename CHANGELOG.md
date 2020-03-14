## Changelog

#### 3.5
- New: Added support for Gutenburg
- New: Added option to display earned points/achievement/ranks with list/grid view
- New: Added option to disable credly email
- New: Added option to display titles from earned achievement widgets
- New: Added new filters
- Fix: Fixed Woocommerce and screen options CSS conflict
- Fix: Fixed multiselect script conflict
- Fix: UI Tweaks

#### 3.4
- New: Added option to update achievement post type and steps slugs
- New: Added option to approve/deny nominations/submissions in bulk
- New: Added new shortcode to display earned achievements for logged in user
- New: Added new shortcode to display earned points for logged in user
- New: Added new shortcode to display earned ranks for logged in user
- New: Added new filters
- Fix: Fixed site loading issue
- Fix: Displayed approved/denied submissions/nominations on front-end
- Fix: Fixed duplicate submission/nomination awarding issue
- Fix: Fixed email notification issue

#### 3.3
- New: Set default point type as point type id parameter in badgeos_get_points_by_type()
- New: Added post attributes for BadgeOS Rank Type posts
- New: Added option to award/revoke achievements in bulk
- New: Added option to award/delete points in bulk
- New: Added option to award/revoke ranks in bulk
- New: Added option to display the system information on tools page
- New: Deleted user data on uninstall
- Fix: Revamp rank award process
- Fix: Updated rank count for users on revoking their earned rank
- Fix: Updated labels for BadgeOS point types
- Fix: Removed select2 js dependency from badgeos-shortcode-embed-js
- Fix: Renamed BadgeOS get_parent_id function to avoid conflicts
- Fix: Removed steps post type from metabox achievement array
- Fix: Revamp function badgeos_user_deserves_rank_step_count_callback()
- Fix: Removed slug fields from Points and Rank types
- Fix: Fixed add-ons licensing issues
- Fix: Hide earned achievements on front-end if show to users is not allowed
- Fix: Revamp BadgeOS shortcode embeder code to avoid select2 version conflicts
- Fix: Fixed widget notification issues for deleted ranks and achievements
- Fix: Fixed deduct point trigger issue
- Fix: Altered ranks and achievements table structure

#### 3.2
- New: Added option to update the existing achievements with regards the point types
- New: Added default thumbnail for the rank post
- Fix: Fixed conflict with scormcloud plugin
- Fix: Fixed the point and rank type pages 404 issue

#### 3.1
- New: Added option to upgrade db from plugin setting page
- New: An email will be sent to admin on completing the db upgrade
- Fix: Changed db upgrade query to work as background process
- Fix: Fixed the BadgeOS shortcodes popup layout issues

#### 3.0
- New: Added option to create multiple point types
- New: Added option to award/deduct points on completing steps
- New: Display users' earned points with types on user profile page
- New: Added option to create multiple rank types
- New: Displayed users' earned ranks on user profile page
- New: Added option to select point type when awarding points with achievements/ranks
- New: Saved users' earned achievement to custom db table rather than saving in meta data
- New: Added option to transfer users' earned badges meta data to custom db table by clicking on a button
- New: Added new actions hooks to extend the user achievement section
- New: Added two new triggers. "Daily Visit" and "Register to the website"
- Fix: Fixed conflict of BadgeOS with WooCommerce Membership add-on
- Fix: Improved log entry message texts

#### 2.4 
- New: Added action hook to run when revoking badges
- Fix: Fixed any/all achievement trigger count issue
- Fix: Improved logic for displaying load more button
- Fix: Fixed the select2 js conflict
- Fix: Sanitized nomination and submission posts action fields
- Fix: Removed approved/denied submissions/nominations from frontend
- Fix: Removed steps from user profile page

#### 2.3
- Fix: Fixed BadgeOS user capabilities issues
- Fix: Improved meta box field value sanitization

#### 2.2
- Fix: Fixed CMB2 notification issue on plugin activation

#### 2.0
- Major Release: This is the major release of BadgeOS in which we have upgraded the Custom Meta boxes script in the plugin along with other new features and bug fixes. Please visit plugin's page first for guidance.
- New: Multiple achievement listing shortcode on same page
- New: Upgraded cmb script
- New: Added trigger info in user achievement meta
- New: Added option to delete earned badges in bulk from user profile page.
- New: Added new trigger to delete/revoke user points when user is not logged to the site since X days.
- New: Added option to add negative value to the points so that user points can be deducted too on completing the selected events.
- Fix: Specific post comment trigger issue
- Fix: Deleted related step post type on deleting badges
- Fix: Dependant achievement loop issue
- Fix: All achievement trigger badges is awarding as any achievement
- Fix: Infinite loop issue for any achievement of type
- Fix: Permalink issue on the user profile page when actual badge is deleted
- Fix: Upgraded sequential step logic

#### 1.4.11

- Fix: Attachment on post Submissions
- Fix: Display notification on setting page
- Fix: Nomination admin email notification

#### 1.4.10

- New: Option to toggle badgeos log entries. See the option at Settings page.
- New: Option to "Delete All Log Entries" from database. See the option at Log Entries page.
- Fix: Uploaded attachment not being saved
- Fix: Compatibility with PHP 7.x (Tested upto PHP v7.2)
- Fix: Retrieve uploaded attachment regardless of post type
- Tweak: Defensive code checks (Thanks @jonmoore)

#### 1.4.9.1

- Typo: Updated version in the class property

#### 1.4.9

- Fix: Compatibility with PHP 7.x (Tested upto PHP v7.1)
- Fix: Frontend submission html formatting
- Fix: PHP warnings on frontend submission
- Fix: Added Sanity check for $comment_data variable which prevents PHP notices and warnings
- New: Configurable option to delete plugin data on uninstall
- New: 50% Portuguese (Brazilian) translation

#### 1.4.8.3

- Fixed: Woocommerce compatibility issue fixed by upgrading to Select2 Version 4.
- Updated: Upgraded Select2 from Version 2 to Version 4, modified Shortcode implementation accordingly.

#### 1.4.8.2

- Fixed: Hidden Badges issue where hidden badges were getting displayed though they are set hidden
- Fixed: Disabled filter button to fix the issue where multiple search results displayed when the filter submit button is clicked repeatedly
- Fixed: Maximum earnings for Achievement earned through completing steps did not earn points more than once
- Updated: Increased Max-length to 3 for the number of times an achievement step needs to be performed to earn an achievement by completing steps

#### 1.4.8.1

- Fixed: BadgeOS 1.4.8 CKEDItor script blocked other scripts in queue from executing
- Updated: Set CKEditor to Standard version
- Updated: CKEditor CDN URL for SSL

#### 1.4.8

- Added: Save Draft feature for achievement submission and comments
- Added: CK Editor – Rich Text Editor for all front end text area
- Added: Meta Box added for attachment on a specific submission
- Added: Meta Box added for attachment in comments
- Fixed: All Achievement Type auto submission success message not getting displayed properly
- Fixed: Completion steps – All Achievement of Type Badges not getting updated as Achieved when the dependent achievements are completed
- Fixed: Maximum Earned Achievment bug for unlimited earning
- Fixed: Removed confirm submission popup on refresh while re-submitting achievement

#### 1.4.7

- Fixed: Remove empty() check that prevented point updates from being logged.
- Fixed: Corrected incorrect usage of PHP time() function.

#### 1.4.6

- Fixed: PHP variable typo introduced in 1.4.5.
- Fixed: + marks introduced in Earned User Achievement Widget form output.
- Fixed: Amended query statement preparation for multisite-based functionality.
- Fixed: Minor code cleanup in Earned User Achievement Widget.

#### 1.4.5

- Fixed: Prevent false positives on "users who have earned achievement" listings.
- Fixed: Prevent potential empty array of achievements.
- Fixed: Added unique ID to single achievement shortcode output.
- Fixed: esc_attr() on some attributes.
- Fixed: Prevent submission and nomination columns from showing outside of submission and nomination pages.
- Fixed: Pass user ID to badgeos_maybe_award_achievement_to_user call inside badgeos_update_user_points().
- Fixed: Prevent steps from being listed in achievement type list in Steps UI.
- Fixed: Prevent media library "litter" with default thumbs up graphic being added multiple times. Now will check for existing copy of graphic before downloading new.
- Updated: Provided PHP5 compatible widget constructors in preparation of WordPress 4.3 changes.

#### 1.4.4

- Improved checks to prevent achievement type switching when editing achievement types.
- Added a check to ensure multisite is active before calling multisite functions.
- Added internationalization strings and updated pot file.
- Inline documentation improvements.

#### 1.4.3

- Fix issue with user scores being zero'd out when they save their profile in WP Admin.
- Added French translation files. Credit: http://extremraym.com
- Made it possible for admins to award achievements, that can be earned multiple times, to users via the User's admin profile
- Misc code tweaks.

#### 1.4.2

- Updated: Achievement Types now support menu ordering.
- Fixed: Eliminated a fatal error in the nomination saving process.
- Fixed: Updated submission manager role setting to correctly show the selected value.
- Fixed: Additional hardening for achievement-type migration so that it doesn't happen prematurely due to autosave.

#### 1.4.1

- Fixed: Eliminated a critical bug that could cause all posts to be migrated to a brand new achievement type on publish.

#### 1.4.0

- Added: BadgeOS Shortcode Embedder – Easily add any shortcode to any content area with a few clicks.
- Added: BadgeOS Shortcode registration API – Easily add support for new BadgeOS-related shortcodes and modify existing shortcodes with automatic support for the BOS Shortcode Embedder and help page.
- Added: Submission Manager Role selector – allow users to moderate submissions without granting them full access to BadgeOS administration.
- Added: Submission Notification Admin Settings – Specify an unlimited number of email addresses in a comma-separated list for submission admin notifications.
- Added: Submission Notifications to users who have made a submission.
- Added: Several new submission notification types – new submission, new comment, and submission status change
- Added: User Email Notification Setting – Users can opt-out of email notifications in the profile editor.
- Added: Trigger for commenting on specific posts.
- Added: Many hooks for modifying shortcodes, submission lists, and more.
- Added: Baseline support for renaming achievement types, including the auto-migrating all achievements (and user earnings) from original achievement type to the new.
- Added: Helper functions for checking if user meets BOS management roles.
- Updated: Revised trigger for commenting on posts to only award when comment is approved.
- Updated: Submission status can now be altered on the front-end from approved to denied or back again.
- Updated: Admin area for editing a submission now uses the same approve/deny buttons as the rest of the site.
- Updated: Submission Lists are now highly customizable via WP hooks. The search input, filter inputs, and even the results can be altered programatically.
- Updated: Many, many internationalization enhancements. Help us release BadgeOS in your language!
- Updated: BadgeOS management role selector is hidden on the settings page to non-admins.
- Updated: Badgeos management role selector now excludes contributor and subscriber roles.
- Fixed: Rewrite rules automatically flush when a new achievement type is added (or an existing is renamed).
- Fixed: BadgeOS settings can now be modified by the minimum selected management role.
- Fixed: Prevent users from repeatedly earning auto-approved submissions.
- Fixed: Submissions List status filter now indicates the displayed status on page load.
- Fixed: Lots of other minor bugs.

#### 1.3.5

- Fixed: Eliminated an error when attempting to use Credly Badge Builder over SSL
- Fixed: Eliminated some PHP warnings

#### 1.3.4

- Updated: Upgraded bundled CMB library to 1.0.8.
- Updated: Upgraded bundled posts-to-posts library to 1.6.3-alpha.
- Updated: Corrected several outdated PHPDoc comments.
- Updated: Added quotes to all shortcode examples for clarity.
- Fixed: Properly award "all achievements of type" step trigger when triggered.
- Fixed: Prevent awarding a user's triggered triggers to current admin.
- Fixed: Prevent awarding a user's triggered triggers on the incorrect site (in multisite).
- Fixed: Prevent users from incorrectly resubmitting earned achievements to Credly.
- Fixed: Pass explicit user ID on "send to credly" AJAX calls.
- Fixed: Minor CSS tweaks to BadgeOS user profile fields.

#### 1.3.3

- Added: Spanish language translation.
- Fixed: Eliminated a recursion issue with the badgeos_award_achievement action hook.
- Fixed: Users can send new submissions if previous submisson was denied or approved (until they've reached the maximum earnings for the achievement).
- Fixed: Corrected sort order on front-end step output.
- Fixed: Updated Earned Achievements Widget handling for achievements with spaces and special characters.

#### 1.3.2

- Fixed: Small issue with Credly Badge Builder API headers.

#### 1.3.1

- Fixed: Achievement step stort order (in admin).
- Fixed: Prevent duplicate stock achievement thumbnails per achievement type.
- Fixed: Bug with Credly Badge Builder API connection.

#### 1.3.0

- Added: Credly Badge Builder – Build your own unique badges directly from the post editor (requires Credly account).
- Added: badgeos_is_achievement() to check if a given $post or $post_id is a BadgeOS achievement.
- Added: New [credly_assertion_page] shortcode for Credly Pro users.
- Added: Specify a custom message to include with Credly notification emails
- Updated: badgeos_award_achievement_to_user() now checks that the passed $acheivement_id is a real achievement.
- Updated: New achievements now have a default max earning of 1 (blank for infinite).
- Updated: BadgeOS Help page now links to Github, instead of just mentioning it.
- Updated: Detailed "Credly Sharing" options are only visible on the achievement editor when achievement is set to "send to credly".
- Updated: "Featured Image" text now says "Achievement Image" when working with an achievement post.
- Fixed: Added variable type check to badgeos_get_user_achievements() to prevent PHP warning.
- Fixed: Earned achievements will no longer show "Send to Credly" when setting is disabled.

#### 1.2.0

- Added: "Add-ons" menu now has a catalog of new plugins to extend BadgeOS in exciting ways.
- Added: Introduced a suite of "user activity" functions for tracking a user's active achievements.
- Updated: Add-Ons admin page now dynamically pulls all available add-ons directly from BadgeOS.org
- Updated: Improved support for WP Multisite installations
- Updated: Earned Achievements widget now sorts achievements with newest-earned first.
- Updated: Earned Achievements widget now supports displaying all OR specific achievement types.
- Updated: [badgeos_achievements_list] shortcode now supports multiple achievement types, using either type="all" or by separating the achievement names with a comma, like: type="badge,quest,level".
- Updated: [badgeos_achievements_list] shortcode now supports "orderby" and "order" parameters so you can control how achievements are sorted.
- Updated: BadgeOS Log Entry functions are now filterable and can be overridden (more on this in 1.3).
- Updated: Added hooks to Help/Support page so add-ons can include their own content.
- Updated: We now set a default thumbnail for new achievements and achievement types.
- Updated: We now display a warning on the Achievement Type editor if a title exceeds 20 characters.
- Updated: Removed some redundant checks in the rules-engine to make process more performant.
- Updated: Relocated a few functions to make codebase easier to navigate.
- Fixed: We now hide the container for an earned achievement's congratulations text if there is no congratulations text.
- Fixed: The Earned Achievements widget and the Send to Credly functionality sanely fall-back to the parent achievement's thumbnail if the given achievement doesn't have one set.
- Fixed: Eliminated a bug with the "Add Media" functionality due to a conflict with the Canvas theme by WooThemes.
- Fixed: Cleared out many minor, but annoying, PHP warnings

#### 1.1.0

- Added: New triggers for publishing new posts and pages
- Added: [badgeos_achievement] shortcode to display a single achievement on any post/page, see BadgeOS Help/Support for parameter details
- Added: [badgeos_submissions] shortcode to show a filterable/searchable list of submissions, see BadgeOS Help/Support for parameter details
- Added: [badgeos_nominations] shortcode to show a filterable/searchable list of nominations, see BadgeOS Help/Support for parameter details
- Added: New meta box showing attachments for a Submission in the admin dashboard
- Added: Earned achievement message on an achievement single page which shows if a user has earned the achievement
- Added: New widget to display the Credly Credit Issuer badge
- Updated: Achievements widget with option to display user's total points
- Updated: [badgeos_submission] shortcode to accept achievement_id parameter
- Updated: [badgeos_nomination] shortcode to accept achievement_id parameter
- Updated: New filter for controlling whether or not a user is allowed to spring a trigger
- Fixed: Nomination user select field from displaying twice on the page
- Fixed: Nomination listing page from displaying the wrong status for the nomination
- Fixed: Nomination form/listing now displays a user's submitted nomination and limits them to submitting a single nomination

#### 1.0.3

- Updated: Only show "People Who Have Earned This" when there is at least one earner
- Updated: Localization strings have been updated throughout for easier translating
- Updated: Submissions and Nominations were originally publicly searchable, they are now private
- Fixed: Prevent earning an achievement more times than "max earnings" allows
- Fixed: Prevent earning steps with no parents
- Fixed: Prevent earning unpublished, private or trashed achievements
- Fixed: Inability to earn "any [achievement type]" steps
- Fixed: Inability to earn "all [achievement type]" steps
- Fixed: Filter for "Completed Achievements" (would sometimes show ALL achievements if user had earned none)
- Fixed: A PHP warning that would sometimes appear when manually awarding an achievement via user profile editor
- Fixed: A number of minor behind-the-scenes bugs that annoyed our PHP developers

#### 1.0.2

- New: Added "Display users who have earned achievement" option to achievements
- Updated: BadgeOS Add-Ons admin page now shows current BadgeOS add-ons
- Updated: [badgeos_achievements_list] shortcode now shows send to Credly link on earned achievements.

#### 1.0.1

- Fix: The "Award an Achievement" section on the User Profile page now grabs the appropriate custom post type slugs.
- Fix: Updated an incorrectly named function.
- Fix: Stop completed filter from showing all achievements if no achievements have been completed
- Updated: Achievement display and awarding UI improvement.
- Updated: [badgeos_achievements_list] shortcode now supports show_filter and show_search attributes

#### 1.0.0

- BadgeOS says "hello world", earns "Hello World" badge.
