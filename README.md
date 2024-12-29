# Daikin SSO Processing Module

This repository contains a Magento 2 module developed to demonstrate technical expertise during job interviews. The module showcases various aspects of Magento 2 module development, including custom helpers, plugins, observers, and SAML (Security Assertion Markup Language) integration.

## Module Purpose
The primary purpose of this module is to illustrate skills in:
- Developing Magento 2 modules
- Handling SAML2-based Single Sign-On (SSO)
- Customizing customer group assignments

The original single-sign-on module could map customers to customer groups based on configurations in the system settings. However, the SAML data from Daikin required additional parameters to map customers to the correct groups effectively.

## Repository Structure
Since this repository is tailored for simplicity, it only includes the contents of the `DaikinSsoProcessing` directory. Typically, this module would reside under `app/code/Jvdh/DaikinSsoProcessing` in a Magento 2 project.

### Included Components
1. **Helper Classes**
   - `Helper/Data.php`: Contains reusable methods for SSO processing, such as customer group mapping and module configuration checks.

2. **Observers**
   - `Observer/Saml2CustomerSuccessfullyUpdated.php`: Handles SAML2 events, such as updating customer-related data when an SSO process succeeds.

3. **Plugins**
   - `Plugin/Controller/Saml2/ACS.php`: Extends the functionality of the ACS (Assertion Consumer Service) controller to assign customer groups based on SAML attributes.

## Installation
To add this module to a Magento 2 project:

1. Clone the repository into the appropriate location within your Magento project:
   ```bash
   git clone <repository_url> app/code/Jvdh/DaikinSsoProcessing
   ```

2. Run Magento setup commands:
   ```bash
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

3. Verify that the module is enabled:
   ```bash
   php bin/magento module:status Jvdh_DaikinSsoProcessing
   ```

## Configuration
The module relies on specific configurations and environment variables for seamless operation:

1. **Environment Variables**
   - `DAIKIN_EMAIL_DOMAINS`: Comma-separated list of email domains for mapping customers to specific groups.
   - `DAIKIN_ADMIN_EMAILS`: Comma-separated list of admin email addresses for special handling.

2. **Magento Admin Settings**
   - Navigate to `Stores > Configuration > Foobar > SAML2 Customer` to ensure the module is enabled for Daikin integration.

## Key Features
- **SAML2 Integration**: Leverages SAML2 protocol for secure Single Sign-On.
- **Dynamic Customer Group Assignment**: Resolves and assigns customer groups based on predefined mappings and SAML attributes.
- **Session and Quote Handling**: Updates customer session and quote data to reflect group changes.
- **Error Handling**: Provides user-friendly error messages for missing or incorrect configurations.

## Notes
- This module is a technical demonstration and not intended for production use.
- Ensure proper configurations and security practices when integrating SAML in live environments.

## License
This project is licensed under the MIT License. Feel free to use and modify it as needed.

## Author
Developed by me.

## Contact
For any questions or further discussions, please feel free to contact me via [your email or LinkedIn profile].

