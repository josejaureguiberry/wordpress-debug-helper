# Wordpress Debug Helper

This plugin was meant to be used by developers, support staff and advanced users for debugging purposes.
Due to security reasons, it is recommended to uninstall the plugin when it is not used.

# Features

  - Print and log debug data in production environment without affecting end user experience.
  - Search string through project files when no SSH access is available.


# Usage
Use the following actions in your code where you want to add the debug trace:
**Log data to *my-errors.log* file**
```php
do_action('debugger_write_log', $message, $identifier, $print_stack);
```
**Print recursive data into screen**
This feature will work only if the query parameter **debug_mode** is present and set to **true**.
Example: https://example.com/?debug_mode=1
```php
do_action('debugger_var_dump', $message, $identifier, $print_stack, $die);
```
**In order to Search string in project files you need to add the following parameter to the URL:**
`search_in_folder={folder_name}|{needle}|{mode}`