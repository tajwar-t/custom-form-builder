# Custom Form Builder for WordPress

A lightweight, intuitive WordPress plugin that lets you create and manage custom forms with a simple drag-and-drop interface. Perfect for contact forms, surveys, registration forms, and more!

## Features

✨ **Easy Form Creation** - Build forms in minutes with an intuitive admin interface  
📝 **Multiple Field Types** - Text, email, textarea, dropdown, radio buttons, and checkboxes  
💾 **Submission Management** - View all form submissions in your WordPress admin  
🎨 **Clean Design** - Modern, responsive forms that look great on any device  
🔒 **Secure** - Built with WordPress security best practices  
⚡ **Lightweight** - No bloat, just the essentials

## Installation

1. Download all plugin files
2. Create a folder called `custom-form-builder` in `/wp-content/plugins/`
3. Upload all files to this folder:
   - `custom-form-builder.php`
   - `admin-script.js`
   - `frontend-script.js`
   - `admin-style.css`
   - `frontend-style.css`
4. Go to **Plugins** in your WordPress admin
5. Activate **Custom Form Builder**

## Quick Start

### Creating Your First Form

1. Go to **Form Builder** in your WordPress admin menu
2. Enter a form name (e.g., "Contact Form")
3. Click **Create Form**
4. Add fields:
   - Select field type (text, email, dropdown, etc.)
   - Enter field label
   - For dropdowns/radio/checkboxes, you'll be prompted to enter options
   - Click **Add Field**
5. Click **Save Form** when done

### Adding Forms to Your Site

After creating a form, you'll see a shortcode like `[custom_form id="1"]`

**Add to Pages/Posts:**

- Copy the shortcode
- Paste it into any page or post editor
- Publish!

**Add to Templates (PHP):**

```php
<?php echo do_shortcode('[custom_form id="1"]'); ?>
```

**Add to Widgets:**

- Use a "Custom HTML" or "Shortcode" widget
- Paste your shortcode

## Field Types

| Field Type        | Description                  | Use Case                         |
| ----------------- | ---------------------------- | -------------------------------- |
| **Text**          | Single-line text input       | Names, titles, short answers     |
| **Email**         | Email input with validation  | Contact information              |
| **Textarea**      | Multi-line text input        | Messages, comments, descriptions |
| **Dropdown**      | Select from multiple options | Country selection, categories    |
| **Radio Buttons** | Choose one option            | Yes/No, gender, preferences      |
| **Checkboxes**    | Choose multiple options      | Interests, services, agreements  |

## Managing Forms

### Editing Forms

1. Go to **Form Builder**
2. Click **Edit** next to any form
3. Add, remove, or modify fields
4. Click **Save Form**

### Deleting Forms

1. Go to **Form Builder**
2. Click **Delete** next to the form
3. Confirm deletion (this also deletes all submissions)

### Viewing Submissions

1. Go to **Form Builder → Submissions**
2. See all form submissions with timestamps
3. View submitted data for each entry

## Customization

### Styling the Forms

You can customize the form appearance by editing `frontend-style.css` or adding custom CSS to your theme:

```css
/* Change submit button color */
.cfb-submit {
  background: #your-color !important;
}

/* Modify field styles */
.cfb-field input {
  border-radius: 8px;
}
```

### Advanced Customization

**Modify Success Message:**
Edit line 239 in `custom-form-builder.php`:

```php
wp_send_json_success(['message' => 'Your custom message here!']);
```

**Add Email Notifications:**
Add this to the `handle_submission` method in `custom-form-builder.php`:

```php
$admin_email = get_option('admin_email');
$subject = 'New Form Submission';
$message = print_r($sanitized_data, true);
wp_mail($admin_email, $subject, $message);
```

## File Structure

```
custom-form-builder/
├── custom-form-builder.php    # Main plugin file
├── admin-script.js             # Admin interface logic
├── frontend-script.js          # Form submission handling
├── admin-style.css             # Admin styling
├── frontend-style.css          # Form styling
└── README.md                   # This file
```

## Database Tables

The plugin creates two tables:

- `wp_cfb_forms` - Stores form configurations
- `wp_cfb_submissions` - Stores form submissions

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Troubleshooting

**Forms not saving:**

- Check that you have admin privileges
- Verify database tables were created (check with phpMyAdmin)

**Shortcode not working:**

- Make sure the plugin is activated
- Verify the form ID is correct
- Check for JavaScript errors in browser console

**Submissions not appearing:**

- Clear browser cache
- Check WordPress debug logs
- Verify AJAX is working (check network tab in browser dev tools)

## Security Features

✓ Nonce verification for all AJAX requests  
✓ Capability checks (only admins can manage forms)  
✓ Data sanitization on input  
✓ Escaped output to prevent XSS  
✓ Prepared SQL statements to prevent injection

## Future Enhancements

Potential features for future versions:

- Email notifications
- File upload fields
- Form duplication
- Export submissions to CSV
- Conditional logic
- Spam protection (CAPTCHA)
- Form analytics

## Support

For issues, questions, or feature requests, please create an issue on the project repository or contact the developer.

## License

This plugin is open source and free to use in personal and commercial projects.

## Credits

Developed with ❤️ for the WordPress community

---

**Version:** 1.0.0  
**Last Updated:** January 2026
