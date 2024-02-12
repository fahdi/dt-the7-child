# Dynamic Membership Assignment for WP All Import and PMP

## Description

The Dynamic Membership Assignment plugin automates the process of assigning membership levels to posts within WordPress,
leveraging data imported via CSV files. This plugin integrates with WP All Import and Paid Memberships Pro (PMP), adding
an administrative page for manual bulk processing of membership assignments. It is designed to enhance site management
efficiency by dynamically assigning access based on predefined criteria.

## Features

- **Automatic Membership Assignment:** Dynamically assigns membership levels based on imported CSV data.
- **Integration:** Works seamlessly with WP All Import and Paid Memberships Pro.
- **Admin Page for Bulk Processing:** Provides an administrative interface for manually initiating bulk membership
  assignments.
- **Security:** Includes checks to prevent direct file access, ensuring the plugin is only executed within the WordPress
  environment.
- **Flexibility:** Allows for case-insensitive and partial matching of membership criteria, including disaster types and
  geographic locations.

## Installation

1. **Download the Plugin:** Obtain the plugin files and unzip them if necessary.
2. **Upload to WordPress:**
    - Via FTP: Upload the `dynamic-membership-assigner` folder to the `/wp-content/plugins/` directory on your server.
    - Via WordPress Dashboard: Navigate to Plugins > Add New > Upload Plugin, and select the plugin's zip file.
3. **Activate the Plugin:** Go to the 'Plugins' menu in WordPress and activate the Dynamic Membership Assignment plugin.

## Usage

Upon activation, the plugin automatically integrates with WP All Import and Paid Memberships Pro to listen for import
events. Membership levels are then assigned based on the criteria specified in the imported CSV data.

### Manual Bulk Processing

For manual initiation of the membership assignment process:

1. Navigate to the 'Membership Assignment' page in the admin menu.
2. Click the 'Start Assignment' button.
3. Monitor the process through updates displayed in the 'Membership Assignment Status' section.

## Technical Details

### Key Functions

- `dma_add_admin_menu()`: Adds the plugin's page to the WordPress admin menu.
- `dma_membership_assignment_page()`: Renders the admin page HTML, including the 'Start Assignment' button.
- `dma_start_assignment_ajax()`: Handles AJAX requests to initiate the membership assignment process.
- `dma_process_assignments()`: Executes the batch processing of posts for membership assignment.
- `assign_memberships_to_lead_post()`: Assigns membership levels to individual posts based on imported data.
- `get_nationwide_all_access_level_id()`: Retrieves the ID for the 'Nationwide All-Access' membership level.
- `determine_membership_levels()`: Determines appropriate membership levels for each post, including partial and
  case-insensitive matching.
- `assign_membership_to_post()`: Associates posts with determined membership levels in the database.

### Security Measures

The plugin includes a direct access check (`defined('ABSPATH')`) to ensure it cannot be executed outside of the
WordPress environment, enhancing security by preventing unintended access.

## Support

For support, questions, or further information, please contact [info@fahadmurtaza.com](mailto:info@fahadmurtaza.com).

## License

This plugin is licensed under the GPL2, which allows free use, modification, and distribution of the software. For more
details, visit [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html).

## Acknowledgements

Special thanks to the WordPress community and the developers of WP All Import and Paid Memberships Pro for their support
and contributions to this project.
