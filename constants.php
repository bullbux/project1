<?php
define('DEBUG', 0);

// Show prefix in URL i.e., http://www.yourdomain.com/admin/users/manage...
$prefix = array(
            'admin',
            'member',
            'dashboard',
            'renter',
            'landlord'
        );
// Default class

define('DEFAULT_CLASS','properties');
// Default method
define('DEFAULT_METHOD','index');
// Default common layout
define('DEFAULT_LAYOUT','default');

// Want DB Access?
define('DB_ACCESS', true);
//define('DB_USER', 'wwwmydr_apart');
//define('DB_PASSWORD', 'wwwmydr_apart@12');
define('DB_USER', 'acadev2_dev');
define('DB_PASSWORD', 'dev@2012');
define('DB_HOST', 'localhost');
define('DB_NAME', 'acadev2_appartmentdev');
 
// DB Record Id encryption key to make your data more secure
define('ID_ENCRYPTION_KEY', '$@jfds!67#EDds');

// DB configuration ==> array('<table_name>'=>'<field_name>')
$slugs = array(
    'properties' => 'title',
    'faq_categories' => 'category'
);

// Maximum number of records per page.
define('LIMIT','20');
define('PROPERTY_LIMIT','5');

define('WEBSITE_NAME','apartmentsforrent.com');
define('PROJECT_NAME','Apartments for rent');
define('ROOT_DIR_NAME','');

/**
 * All user type constants
 */
class SiteConsts{
    // Currency Symbol 
    const CURRENCY = '$';
}

/**
 * User Status
 */
class UserStatusConsts{
    const ACTIVE = '1';
    const INACTIVE = '0';
}

/**
 * All user type constants
 */
class UserTypeConsts{
    const ADMIN = 1;
    const LANDLORD = 2;
    const MEMBER = 3;
}

/**
 * All user type constants
 */
class UnitInfoConsts{
    const LEASE_LENTH = 5;
    const MAX_BED = 5;
    const MAX_BATH = 5;
}

/**
 * Property Status
 */
class PropertyStatusConsts{
    const PREVIEW = 4;
	const EXPIRED = 4;
    const SOLD = 3;
    const DRAFT = 2;
    const PUBLISH = 1;
    const UNPUBLISH = 0;
}

/**
 * Message Flag
 */
class MessageStatusConsts{
    const UNREAD = 0;
    const READ = 1;
}

/**
 * Email Templates
 */
class EmailTemplatesConsts{
    const ACTIVATION_EMAIL = 1;
}
?>