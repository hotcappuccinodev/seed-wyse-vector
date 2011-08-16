# Laravel Change Log

## Version 1.6.0

- Moved **system/db/manager.php** to **system/db.php**. Updated alias appropriately.
- Unspecified optional parameters will be removed from URLs generated using route names.
- Fixed bug in Config::set that prevented it from digging deep into arrays.