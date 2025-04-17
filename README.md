# AccessiTrans - ARIA Translator for WPML & Elementor

[![WordPress Compatible](https://img.shields.io/badge/WordPress-6.8-green.svg)](https://wordpress.org/)
[![Elementor Compatible](https://img.shields.io/badge/Elementor-3.28.3-red.svg)](https://elementor.com/)
[![WPML Compatible](https://img.shields.io/badge/WPML-4.7.3-blue.svg)](https://wpml.org/)
[![Version](https://img.shields.io/badge/Version-0.2.0-purple.svg)]()

WordPress plugin that allows translation of ARIA attributes in Elementor sites with WPML, improving accessibility in multilingual environments.

## Description

The **AccessiTrans - ARIA Translator for WPML & Elementor** plugin facilitates the translation of ARIA attributes in sites developed with Elementor and WPML, ensuring that accessibility information is available in all languages of your website.

### Compatible ARIA attributes

The plugin allows you to translate the following attributes:

* `aria-label`: To provide an accessible name for an element
* `aria-description`: To provide an accessible description
* `aria-roledescription`: To customize the role description of an element
* `aria-placeholder`: For placeholder text in input fields
* `aria-valuetext`: To provide textual representation of numeric values

### Capture Methods

The plugin offers several capture methods to ensure that all ARIA attributes are detected and made available for translation:

1. **Full HTML Capture**: Captures all HTML of the page to find ARIA attributes (highly effective but may affect performance)
2. **Elementor Content Filter**: Processes content generated by Elementor
3. **Elementor Template Processing**: Processes Elementor template data
4. **Individual Element Processing**: Processes each Elementor widget and element individually

These methods can be enabled or disabled from the plugin's settings page according to your site's needs.

### Translation Registration Formats

The plugin supports multiple formats for registering strings for translation:

1. **Direct Format**: Registers the literal value as the name
2. **Prefix Format**: Registers with the format `aria-attribute_value`
3. **Element ID Format**: Registers with a format that includes the element ID

**Important:** It is recommended to keep all three formats enabled for maximum robustness. Each format provides a different fallback method for accessing translations.

### Additional Features

* **Translation Retry Mechanism**: Automatically retries failed translations to improve success rate
* **Force Refresh Function**: Button to clear all caches and force update translations
* **Configurable Translation Priority**: Adjust the priority of translation filters
* **Debug Mode**: Detailed logging for troubleshooting

### Compatibility

Works with all types of Elementor content:

* Regular pages
* Templates
* Global sections
* Headers and footers
* Popups and other dynamic elements

**Tested with:**
* WordPress 6.8
* Elementor 3.28.3
* WPML Multilingual CMS 4.7.3
* WPML String Translation 3.3.2

## Installation

1. Download the `accessitrans-aria.zip` file from the GitHub releases page
2. Upload the files to the `/wp-content/plugins/accessitrans-aria/` directory of your WordPress installation or install directly through WordPress by uploading the ZIP file
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings → AccessiTrans to configure the plugin options
5. Configure ARIA attributes in Elementor (see usage instructions)

## Usage

### How to add ARIA attributes in Elementor

1. Edit any element in Elementor
2. Go to the "Advanced" tab
3. Find the "Custom Attributes" section
4. Add the ARIA attributes you want to translate

### Compatible formats

Elementor indicates: "Set custom attributes for the wrapper element. Each attribute in a separate line. Separate attribute key from the value using `|` character."

You can add ARIA attributes in two ways:

**Basic format (one attribute per line):**
```
aria-label|Text to translate
```

**Multiline format (multiple attributes):**
```
aria-label|Text to translate
aria-description|Another description
```

This will generate the corresponding HTML attributes in the frontend:
`aria-label="Text to translate" aria-description="Another description"`

### How to translate the attributes

1. Once you've added the attributes, save the page or template
2. Go to WPML → String Translation
3. Filter by one of the "AccessiTrans ARIA Attributes_XXX" contexts
4. Translate the strings as you would with any other text in WPML

**Note:** Each text might appear in multiple contexts with different identifiers. This is normal and part of the mechanism that ensures translations work in all content types and capture methods.

## Best Practices

For optimal performance and efficiency, follow these recommended practices:

1. **Enable all three registration formats**: This provides maximum robustness by offering multiple fallback methods
   * Direct format
   * Prefix format
   * Element ID format

2. **Use the "Force Refresh" function when needed**:
   * If a translation doesn't appear as expected
   * After making significant changes to your site
   * When adding new ARIA attributes to existing elements

3. **Only browse the site in the primary language** while generating strings for translation. This prevents the plugin from registering strings that may have already been translated through other systems.

4. **Disable capture methods after initial setup**:
   * Once you've captured all ARIA attributes for translation, consider disabling all capture methods
   * This will improve site performance and prevent additional strings from being registered in WPML
   * Re-enable the capture methods temporarily when you make changes to your site that include new ARIA attributes

### Practical examples

**For a menu button:**
```
aria-label|Open menu
```

**For a phone link:**
```
aria-label|Call customer service
```

**For an icon without text:**
```
aria-label|Send email
```

**For a slider:**
```
aria-label|Image gallery
aria-description|Navigate through product images
```

## Plugin Settings

The plugin comes with a settings page that allows you to configure the capture methods and other options:

### Capture Methods
* **Full HTML Capture**: Captures all HTML (most thorough but might affect performance)
* **Elementor Content Filter**: Processes content generated by Elementor
* **Process Elementor Templates**: Processes Elementor template data
* **Process Individual Elements**: Processes each Elementor widget individually

### Registration Formats
* **Direct Format**: Register the literal value as the name
* **Prefix Format**: Register with format aria-attribute_value
* **Element ID Format**: Register with format including element ID

### Advanced Settings
* **Retry Failed Translations**: Attempts to reapply translations that failed on first try
* **Translation Priority**: Priority of translation filters (higher values execute later)
* **Debug Mode**: Enables detailed event logging (stored in wp-content/debug-aria-wpml.log)
* **Capture for Admins Only**: Only processes full capture when an admin is logged in

## Internationalization

The plugin includes internationalization support, making it ready for translation into multiple languages. The translation files should be placed in the `/languages` directory.

## Contributions

Contributions are welcome. If you want to improve this plugin:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

Distributed under the GPL v2 or later license. See `LICENSE` for more information.

## Author

Developed by Mario Germán Almonte Moreno:

* Member of IAAP (International Association of Accessibility Professionals)
* CPWA Certified (CPACC and WAS)
* Professor in the Digital Accessibility Specialization Course (University of Lleida)
* 20 years of experience in digital and educational fields

Professional services:
* Web accessibility audits according to EN 301 549 (WCAG 2.2, ATAG 2.0)
* Training and consulting in Web Accessibility and eLearning
* Advice on implementing eLearning technologies

Contact:
* LinkedIn: [https://www.linkedin.com/in/marioalmonte/](https://www.linkedin.com/in/marioalmonte/)
* Website & Blog: [https://aprendizajeenred.es](https://aprendizajeenred.es)

---

[Documentación en español](https://github.com/marioalmonte/accessitrans-aria/blob/main/README_ES.md)