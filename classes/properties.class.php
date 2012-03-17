<?php

//var_dump($_GET);

/**
 * Performs properties related all the tasks like add, delete, update etc.
 *
 * Here, admin_<method> represents a task to be performed by the site administrator.
 * _<method> is a private member function.
 *
 * @version 1.0
 * @since 1.0
 * @access public
 * @extends  classIlluminz   Base controller class of framework
 *
 */
class properties extends classIlluminz
{

    var $pagelayout = 'front';

    /**
     * Google Maps API URL
     *
     * @var <type>
     */
    var $googelMapApi = "";

    var $loggedInUserId = 0;


    /**
     * Execute before executing any method of this class
     */
    function beforeFilter()
    {
        $this->googelMapApi = "http://maps.google.com/maps?file=api&amp;v=2&amp;key=" . GOOGLE_MAP_KEY;
        // Administrator
        if ($this->params['prefix'] == 'admin') {
            $this->pagelayout = 'admin';
            // If Super admin authentication fails
            if (!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::ADMIN))) {
                referer(array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' => $this->params['pass']));
                redirect(array('class' => 'users', 'method' => 'admin_login'));
            }
        }
        // Landlord
        if ($this->params['prefix'] == 'dashboard') {
            $this->pagelayout = 'front';
            // If landlord authentication fails
            if (!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::LANDLORD))) {
                referer(array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' => $this->params['pass']));
                redirect(array('class' => 'users', 'method' => 'login'));
            }
        }
        // Member/Renter
        if ($this->params['prefix'] == 'member') {
            $this->pagelayout = 'front';
            // If member authentication fails
            if (!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::MEMBER))) {
                referer(array('class' => $this->params['class'], 'method' => $this->params['method'], 'pass' => $this->params['pass']));
                redirect(array('class' => 'users', 'method' => 'login'));
            }
        }

        if ($this->Session->checkSession($this->Session)) {
            $this->loggedInUserId = $this->Session->read('User.id');
        }
        // Add custom scripts
        if (in_array($this->params['method'], array('details', 'advancedListing', 'dashboard_index', 'dashboard_archives', 'dashboard_details', 'dashboard_favorites', 'member_favorites'))) {
            $this->Content->addScript($this->googelMapApi);
            $Include = new include_resource();
            $this->Content->addScript($Include->js("Tooltip.v2.js"));
            $this->Content->addScript($Include->js("globals.js"));
        }
    }

    /**
     * Site home page with search interface
     */
    function index()
    {
        $this->pageTitle = 'Home';
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
        $this->set('lease', $this->listLease());
    }

    /**
     * Show all properties list added by Superadmin
     *
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function admin_index($page = 1, $order_by = 'id', $order = 'DESC')
    {
        //var_dump($_POST); exit;
        $this->pageTitle = 'Properties List';
        $idString = "";
        $table = 'properties';
        $list = array();
        if (isset($this->Request->request['actions'])) {
            if (isset($this->Request->request['checkboxArray'])) {
                foreach ($this->Request->request['checkboxArray'] as $key => $value) {
                    if ($idString == "") {
                        $idString .= $key;
                    } else {
                        $idString .= ", " . $key;
                    }
                }
                if ($this->Request->request['actions'] == "Delete") {
                    $where = "id IN (" . $idString . ")";
                    $this->Database->mysqlDelete($table, $where, true, 'pr_id');
                }
                if ($this->Request->request['actions'] == "Activate") {
                    $fields = array("status");
                    $values = array(ACTIVE);
                    $where = "id IN (" . $idString . ")";
                    $this->Database->mysqlUpdate($table, $fields, $values, $where);
                }
                if ($this->Request->request['actions'] == "De-Activate") {
                    $fields = array("status");
                    $values = array(INACTIVE);
                    $where = "id IN (" . $idString . ")";
                    $this->Database->mysqlUpdate($table, $fields, $values, $where);
                }
            }
        }
        // $where = "user_id = '{$this->Session->read('User.id')}'";
        $pageData = $this->Database->paginatequery(array('id', 'title', 'modified', 'status', 'slug'), 'properties', $where, $order_by . " " . $order, "", "", $page, LIMIT);
        $list['records'] = $this->Database->mysqlfetchobjects($pageData['recordset']);
        if (isset($_POST['Search'])) {
            if ($_POST['city']) {
                $a = $_POST['city'];
            }
            else {
                $a = $_POST['parent_city'];
            }
            $result = mysql_query("SELECT p.id, p.title, p.modified, p.status, p.slug FROM properties p JOIN pr_unit_info pr ON p.id=pr.pr_id  WHERE pr.city='{$a}'");
            while ($data = mysql_fetch_object($result)) {
                $rows[] = $data;
            }
            if (!isset($rows)) {
                $this->Session->setFlash("<div class='flash-success'>No property found....</div>");
                redirect(array('class' => 'properties', 'method' => 'admin_index'));
            }
            $list['records'] = $rows;
        }
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
        $list['pages'] = $pageData['pages'];
        $list['page'] = $page;
        $list['order_by'] = $order_by;
        $list['order'] = $order;
        $this->set('list', $list);
    }

    /**
     * Add a new property as Publish
     *
     * @access Superadmin
     */
    function admin_add()
    {
        $this->_add();
    }

    /**
     * Add a new city and neighborhood for properties
     *
     */
    function admin_city()
    {
        $this->_city();

    }

    /**
     * A list of Cities and neighborhood's for properties
     *
     */
    function admin_cities()
    {
        $this->_cities();

    }

    function admin_editcity($a)
    {
        //var_dump($_POST);
        if (isset($_POST['save'])) {
            $id = $_POST['id'];
            $city = $_POST['city'];
            $parent_id = $_POST['Parent_city'];
            $where .= "id = '$id'";
            //var_dump($where);
            $this->Database->mysqlUpdate('city', array('name', 'parent_id'), array($city, $parent_id), $where);
            $this->Session->setFlash("<div class='flash-success'>City is updated successfully....</div>");
            redirect(array('class' => 'properties', 'method' => 'admin_cities'));
        }
        $result = mysql_query("SELECT * FROM city WHERE id='{$a}'");
        while ($data = mysql_fetch_object($result)) {
            $rows[] = $data;
        }
        $list = $rows[0];
        $this->set('list', $list);
    }

    function admin_deletecity($a)
    {
        $where .= "id = '$a'";
        $this->Database->mysqlDelete('city', $where);
        $this->Session->setFlash("<div class='flash-success'>City is deleted successfully....</div>");
        redirect(array('class' => 'properties', 'method' => 'admin_cities'));

    }

    /**
     * Edit an existing property
     *
     * @access Superadmin
     */
    function admin_edit($slug = '', $page = 1, $order_by = 'properties.id', $order = 'desc')
    {
        $this->_edit($slug, $page, $order_by, $order);
    }

    /**
     * Add a new property as Draft
     *
     * @access Superadmin
     */
    function admin_saveDraft()
    {
        $this->_saveDraft();
    }

    /**
     * Properties listing
     *
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function dashboard_index($page = 1, $order_by = 'properties.id', $order = 'DESC')
    {
//		die('OK');
        $this->pageTitle = 'My listings';
        $where = '(properties.status != ' . PropertyStatusConsts::SOLD;
        $where .= ' AND properties.status != ' . PropertyStatusConsts::EXPIRED . ')';
        $this->_propertiesListing($page, $order_by, $order, $where);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Properties archives listing
     *
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     */
    function dashboard_archives($page = 1, $order_by = 'properties.id', $order = 'DESC')
    {
        $this->pageTitle = 'My listing archives';
        $this->view = 'dashboard_index';
        $where = '(properties.status = ' . PropertyStatusConsts::SOLD;
        $where .= ' OR properties.status = ' . PropertyStatusConsts::EXPIRED . ')';
        $this->_propertiesListing($page, $order_by, $order, $where);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    private function _propertiesListing($page = 1, $order_by = 'properties.id', $order = 'DESC', $where = '', $onlyfav = false)
    {
        $list = array();
        $customFields = array();
        $having = '1';
        $this->set('rightSidebar', 'properties/dashboard_sidebar');
        $list['order_by'] = $order_by;
        $fields = array('properties', 'pr_unit_info', 'left pr_gallery');
        $fKeys = array(array(), array('properties.pr_id'), array('properties.pr_id'));
        if ($onlyfav) {
            $fields[] = 'user_property_favorites';
            $fKeys[] = array('properties.pr_id');
            $where .= ' AND userPropertyFavorites.user_id = ' . $this->Session->read('User.id');
        } else {
            $where .= ' AND properties.user_id = ' . $this->Session->read('User.id');
        }
        // Full text Search city, zip or keywords
        if (isset($this->Request->request['s']) && $this->Request->request['s']) {
            $this->Request->request['s'] = $this->Database->escape(trim($this->Request->request['s']));
            $fulltext = "(MATCH(prUnitInfo.city, prUnitInfo.zip, prUnitInfo.unit_street_address, properties.title, properties.description) AGAINST ('{$this->Request->request['s']}*' IN BOOLEAN MODE))";
            $customFields[] = $fulltext . " AS score";
            $where .= " AND $fulltext";
            $order_by = "score DESC, " . $order_by;
        }
        if (isset($this->Request->request['bed'])) {
            $rentFrom = $this->Database->escape($this->Request->request['from']);
            $rentTo = $this->Database->escape($this->Request->request['to']);
            $bed = $this->Database->escape($this->Request->request['bed']);
            $bath = $this->Database->escape($this->Request->request['bath']);
            if (is_numeric($rentFrom) && is_numeric($rentTo))
                $where .= " AND prUnitInfo.rent BETWEEN $rentFrom AND $rentTo";
            elseif (is_numeric($rentFrom))
                $where .= " AND prUnitInfo.rent >= '$rentFrom'";
            elseif (is_numeric($rentTo))
                $where .= " AND prUnitInfo.rent <= '$rentTo'";
            $where .= " AND prUnitInfo.bed = '$bed' AND prUnitInfo.bath = '$bath'";
        }
        // Advance search
        if (isset($this->Request->request['sqf_from'])) {
            $sqfFrom = $this->Database->escape($this->Request->request['sqf_from']);
            $sqfTo = $this->Database->escape($this->Request->request['sqf_to']);
            $leaseLength = $this->Database->escape($this->Request->request['lease_length']);
            if (is_numeric($sqfFrom) && is_numeric($sqfTo))
                $where .= " AND prUnitInfo.square_feet BETWEEN $sqfFrom AND $sqfTo";
            elseif (is_numeric($sqfFrom))
                $where .= " AND prUnitInfo.square_feet >= '$sqfFrom'";
            elseif (is_numeric($sqfTo))
                $where .= " AND prUnitInfo.square_feet <= '$sqfTo'";
            $where .= " AND properties.lease_length = '$leaseLength'";
        }
        // If property feature filter is selected
        if (isset($this->Request->request['pr_feature_id'])) {
            $fields[] = 'pr_features';
            $fKeys[] = array('properties.pr_id');
            $features = implode("','", $this->Request->request['pr_feature_id']);
            $where .= " AND prFeatures.pr_feature_id IN ('$features')";
            $customFields[] = 'COUNT(DISTINCT prFeatures.pr_feature_id) AS prFeaturesCnt';
            $having = 'prFeaturesCnt = ' . count($this->Request->request['pr_feature_id']);
        }
        // If property unit feature filter is selected
        if (isset($this->Request->request['pr_unit_feature_id'])) {
            $fields[] = 'pr_unit_features';
            $fKeys[] = array('properties.pr_id');
            $features = implode("','", $this->Request->request['pr_unit_feature_id']);
            $where .= " AND prUnitFeatures.pr_unit_feature_id IN ('$features')";
            $customFields[] = 'COUNT(DISTINCT prUnitFeatures.pr_unit_feature_id) AS prUnitFeaturesCnt';
            $having .= ' AND prUnitFeaturesCnt = ' . count($this->Request->request['pr_unit_feature_id']);
        }


        // Property listing sorting
        if (isset($this->Request->request['sort_type']) && $this->Request->request['sort_type']) {
            switch ($this->Request->request['sort_type']) {
                case 'most_recent':
                    $order = 'DESC';
                    break;
                case 'most_old':
                    $order = 'ASC';
                    break;
            }
        }
        //
        //$where.= " AND prGallery.type=1";
        $this->Database->customFields($customFields);
        $pageData = $this->Database->paginate($fields, $fKeys, $where, $order_by . " " . $order, "properties.id", $having, $page, LIMIT);
        //var_dump($this->Database->mysqlfetchassoc($pageData['recordset'])); exit;
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        $list['pages'] = $pageData['pages'];
        $list['page'] = $page;
        $list['order'] = $order;
        $this->set('list', $list);
        $this->set('totalrecords', $pageData['totalrecords']);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
        $this->Database->customFields();
    }

    /**
     * Add a new property as Publish
     *
     * @access Landlord
     */
    function dashboard_add()
    {

        $where = "user_id = " . $this->Session->read('User.id');
        $result = $this->Database->mysqlSelect(array('name', 'contact_email', 'website', 'phone'), 'users_profiles', $where, '', '', '', '');
        $row = $this->Database->mysqlfetchobject($result);
        $phone = $row->phone;
        $name = $row->name;
        $email = $row->contact_email;
        $site = $row->website;
        $phone = explode('-', $phone);
        if ($name) {
            $this->Request->request['contact_name'] = $name;
        }
        if ($email) {
            $this->Request->request['contact_email'] = $email;
            $this->Request->request['confirm_email'] = $email;
        }
        if ($site) {
            $this->Request->request['website'] = $site;
        }

        if ($phone) {
            $this->Request->request['phone1'] = isset($phone[0]) ? $phone[0] : '';
            $this->Request->request['phone2'] = isset($phone[1]) ? $phone[1] : '';
            $this->Request->request['phone3'] = isset($phone[2]) ? $phone[2] : '';
        }
        $this->Content->addScript('<script type="text/javascript"> var draft_url = "' . WWW_ROOT . '/dashboard/properties/saveDraft' . '"; </script>');
        $this->_add();
        $this->element = 'properties/new_property_form';
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Add a new property as Draft
     *
     * @access Landlord
     */
    function dashboard_saveDraft()
    {
        $this->_saveDraft();
    }

    /**
     * Edit an existing property
     *
     * @access Landlord
     */
    function dashboard_edit($slug = '', $page = 1, $order_by = 'properties.id', $order = 'desc')
    {
        $this->Content->addScript('<script type="text/javascript"> var draft_url = "' . WWW_ROOT . '/dashboard/properties/saveDraft' . '"; </script>');
        $this->_edit($slug, $page, $order_by, $order);
        $this->element = 'properties/new_property_form';
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Mark SOLD a property
     *
     * @param <type> $slug
     */
    function dashboard_sold($slug = '')
    {
        $where = "slug = '{$this->Database->escape($slug)}'";
        $where .= " AND user_id = '{$this->Session->read('User.id')}'";
        $this->Database->mysqlUpdate('properties', array('status', 'modified'), array(PropertyStatusConsts::SOLD, date('Y-m-d H:i:s')), $where);

        echo json_encode(array('<div class="flash-sold">Property has been sold and moved to archives successfully.</div>'));
        exit;
    }

    /**
     * Republish a property
     *
     * @param <type> $slug
     */
    function dashboard_republish($slug = '')
    {
        $where = "slug = '{$this->Database->escape($slug)}'";
        $where .= " AND user_id = '{$this->Session->read('User.id')}'";
        $this->Database->mysqlUpdate('properties', array('status', 'modified'), array(PropertyStatusConsts::PUBLISH, date('Y-m-d H:i:s')), $where);

        echo json_encode(array(''));
        exit;
    }

    /**
     * Create a duplicate copy of a property
     *
     * @param <type> $slug
     */
    function dashboard_duplicate($slug = '', $page = 1, $order_by = 'properties.id', $order = 'desc')
    {
        // Copy a property
        $sql = "INSERT INTO properties(title, description, lease_length, deposit_amount, application_fee, move_in_date, other1, other2, user_id, created, modified, status)
                    SELECT CONCAT('Copy of ', title), description, lease_length, deposit_amount, application_fee, move_in_date, other1, other2, user_id, NOW(), NOW(), " . PropertyStatusConsts::DRAFT . " FROM properties
                    WHERE slug = '{$this->Database->escape($slug)}' AND user_id = '{$this->Session->read('User.id')}'";
        $this->Database->mysqlQuery($sql);
        $pId = $this->Database->lastInsertId();
        if ($pId) {
            // Create a unique slug for the copied property
            $where = "id = '$pId'";
            $result = $this->Database->mysqlSelect(array('title'), 'properties', $where);
            $row = $this->Database->mysqlfetchobject($result);
            $slug1 = $this->Database->slug($row->title, 'properties');
            $this->Database->mysqlUpdate('properties', array('slug'), array($slug1), $where);

            $originalId = getId($slug, 'properties', $this);
            // Copy Unit details
            $sql = "INSERT INTO pr_unit_info(pr_id, pr_unit_type_id, unit_street_address, unit_no, city, state, zip, rent, deposit, unit_application_fee, unit_lease_length, bed, bath, square_feet)
                        SELECT $pId, pr_unit_type_id, unit_street_address, unit_no, city, state, zip, rent, deposit, unit_application_fee, unit_lease_length, bed, bath, square_feet FROM pr_unit_info
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property extra features
            $sql = "INSERT INTO pr_extra_features(pr_id, pr_feature)
                        SELECT $pId, pr_feature FROM pr_extra_features
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property predefined features
            $sql = "INSERT INTO pr_features(pr_id, pr_feature_id)
                        SELECT $pId, pr_feature_id FROM pr_features
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property unit extra features
            $sql = "INSERT INTO pr_unit_extra_features(pr_id, pr_unit_feature)
                        SELECT $pId, pr_unit_feature FROM pr_unit_extra_features
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property unit predefined features
            $sql = "INSERT INTO pr_unit_features(pr_id, pr_unit_feature_id)
                        SELECT $pId, pr_unit_feature_id FROM pr_unit_features
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property contact information
            $sql = "INSERT INTO pr_contact_info(pr_id, contact_name, phone, contact_email, street_address, website)
                        SELECT $pId, contact_name, phone, contact_email, street_address, website FROM pr_contact_info
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property search tags
            $sql = "INSERT INTO pr_search_tags(pr_id, tags)
                        SELECT $pId, tags FROM pr_search_tags
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            // Copy Property search tags
            $sql = "INSERT INTO pr_gallery(pr_id, user_id, file)
                        SELECT $pId, user_id, file FROM pr_gallery
                        WHERE pr_id = '$originalId'";
            $this->Database->mysqlQuery($sql);

            $this->Session->setFlash("<div class='flash-success'>Property has been copied successfully.</div>");
        } else {
            $this->Session->setFlash("<div class='flash-success'>Oops! Property has not been copied.</div>");
        }

        redirect(array('class' => 'properties', 'method' => 'dashboard_index', $page, $order_by, $order));
    }

    /**
     * View message details
     *
     * @param <type> $messageId
     */
    function dashboard_message($messageId = 0, $propertyId = 0, $updateStatus = true, $is_private = 0)
    {
        //var_dump ($propertyId); var_dump($is_private); exit;
        $this->set('is_private', $is_private);
        $this->set('messageId', $messageId);
        $where = "properties.id = '{$this->Database->escape($propertyId)}'";
        $result = $this->Database->assoc(array('properties', 'pr_unit_info', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, '', "properties.id");
        $rows = $this->Database->mysqlfetchassoc($result);
        if ($rows && $updateStatus) {
            $where = "id = '{$this->Database->alphaID($this->Database->escape($messageId), true, $this->loggedInUserId)}'";
            $where .= " AND pr_id = '{$this->Database->escape($propertyId)}'";
            $this->Database->mysqlUpdate('user_property_messages', array('flag'), array(MessageStatusConsts::READ), $where);
            $this->pageTitle = $rows[0]['properties.title'] . ' &laquo; Messages';
        }
        $this->set('properties', $rows);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
		$this->set('lease', $this->listLease());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Get all the updates regarding landload property listing
     * Alert to landlord for the properties close to expiration.
     */
    function dashboard_alerts()
    {
        $this->element = 'properties/dashboard_alerts';
        $where = "status != " . PropertyStatusConsts::EXPIRED;
        $where .= " AND user_id = '$this->loggedInUserId'";
        $having = "days_remain < " . UPDATE_EXPIRE_IN_DAYS;
        $result = $this->Database->mysqlSelect(array('slug', 'title', "DATEDIFF(expiry_date, '" . date('Y-m-d') . "') AS days_remain"), 'properties', $where, '', '', $having, '');
        $rows = $this->Database->mysqlfetchobjects($result);
        $this->set('alerts', $rows);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Add a new property as Publish
     */
    private function _add()
    {
        $this->pageTitle = 'Add a new property';
        if ($this->Request->request['save_property']) {
            if ($this->_validateNewPropertyForm()) {
                if ($this->Request->request['publish']) {
                    $originalId = $this->_saveProperty(PropertyStatusConsts::PUBLISH);
					$where = " id = '".$originalId['auto_saved_id']."'";
					$result1 = $this->Database->mysqlSelect(array('slug'), 'properties', $where);
					$rows = $this->Database->mysqlfetchassoc($result1);						
                    //$this->Session->setFlash("<div class='flash-success'>Property has been published successfully.</div>");
					$soical_url = WWW_ROOT."/properties/details/".$rows[0]['slug'];	
			$success = (('<div id="backlayout" style="display: block;">
			  <div id="lightbox" class="no-scroll" style="border-radius: 8px 8px 8px 8px; padding: 10px; position: absolute; z-index: 10000; width: 500px; height: auto; top: 100px; background: none repeat scroll 0% 0% rgba(82, 82, 82, 0.7); left: 245px;">
				<div id="lightbox-content">
				  <div style="color: rgb(255, 255, 255); font-family: tahoma; font-size: 14px; font-weight: bold; background: none repeat scroll 0% 0% rgb(115, 173, 221); padding: 5px; border-width: 1px 1px 0px; border-style: solid solid none; border-color: rgb(115, 173, 221) rgb(115, 173, 221) -moz-use-text-color; -moz-border-top-colors: none; -moz-border-right-colors: none; -moz-border-bottom-colors: none; -moz-border-left-colors: none; -moz-border-image: none;" id="lb-header"> <span title="Close" style="float: left;" class="lb-title">Property has been listed successfully</span> <span style="padding:0 2px; float: right; cursor: pointer;" id="lb-close" title="Close">X</span>
					<div style="clear: both;"></div>
				  </div>
				  <div style="background: none repeat scroll 0% 0% rgb(255, 255, 255); margin: 0pt; padding: 10px; border-right: 1px solid rgb(85, 85, 85); border-style: none solid solid; border-width: 1px; border-color: rgb(85, 85, 85);" id="lb-body">Congratulations on listing your apartment for rent!  Share your listing with your friends and followers on your favorite social networks to increase the visibility of your listing.<br />
				  <a name="fb_share" type="icon" share_url="'.$soical_url.'"></a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" 
					type="text/javascript"></script><br>
<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$soical_url.'" data-text="Check my property" data-count="none">Tweet</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script><br>					
<g:plusone annotation="inline" href="'.$soical_url.'"></g:plusone>
<script type="text/javascript">
  (function() {
    var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
    po.src = "https://apis.google.com/js/plusone.js";
    var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);
  })();
</script>			
				  </div>
				</div>
			  </div>
			</div>'));
            $this->Session->write('newmessage',$success);						
                } elseif ($this->Request->request['save']) {
                    $this->_saveProperty(PropertyStatusConsts::DRAFT);
                    $this->Session->setFlash("<div class='flash-success'>Property has been drafted successfully.</div>");
                } elseif ($this->Request->request['preview']) {
                    $previewID = $this->_saveProperty(PropertyStatusConsts::PREVIEW);
					$where = "status = " . PropertyStatusConsts::PREVIEW;
					$where .= " AND id = '".$previewID['auto_saved_id']."'";
					$result1 = $this->Database->mysqlSelect(array('slug'), 'properties', $where);
					$rows = $this->Database->mysqlfetchassoc($result1);				
					redirect(array('class' => 'properties', 'method' => 'preview',$rows[0]['slug']));
                }
                redirect(array('class' => 'properties', 'method' => $this->params['prefix'] . '_index'));
            }
        }
        // Normal media files upload
        if ($this->Request->request['ajaxUpload']) {
            $this->uploadPropertyMedia();
        }
        $this->set('unitTypes', $this->listUnitTypes());
        $this->set('states', $this->listStates());
        $this->set('sities', $this->listCities());
        $this->set('lease', $this->listLease());
        //$this->set('sities', $sities);
        $this->set('features', $this->listFeatures());
    }

    private function _city()
    {
        //var_dump($_POST); exit;
        If ($_POST["save"] == 'save') {
            $this->Database->mysqlInsert('city', array('name', 'parent_id'), array($_POST['city'], $_POST['Parent_city']));
        }
        $list['page'] = 1;
        $this->set('list', $list);
    }

    private function _cities()
    {
        //var_dump($_POST); exit;
        $list['page'] = 1;
        $this->set('list', $list);
    }

    /**
     * Add a new property as Draft
     */
    private function _saveDraft()
    {
        $response = array();
        if ($this->Request->request['save_property']) {
            if (!$this->Request->request['edit_property'])
                $status = PropertyStatusConsts::DRAFT;
            else
                $status = null;
            $response = $this->_saveProperty($status);
        }
        // Normal media files upload
        if ($this->Request->request['ajaxUpload']) {
            $this->uploadPropertyMedia();
        }
        if (isAjax()) {
            echo json_encode($response);
            exit;
        }
    }

    /**
     * Edit an existing property
     */
    private function _edit($slug = '', $page = 1, $order_by = 'properties.id', $order = 'desc')
    {
        $this->pageTitle = 'Edit property';
        if ($this->Request->request['save_property']) {
            if ($this->_validateNewPropertyForm(false)) {
                $status = null;
                if ($this->Request->request['publish']) {
                    //$this->_saveProperty(PropertyStatusConsts::PUBLISH);
                    //$this->Session->setFlash("<div class='flash-success'>Property has been updated successfully.</div>");
                    $originalId = $this->_saveProperty(PropertyStatusConsts::PUBLISH);
					$where = " id = '".$originalId['auto_saved_id']."'";
					$result1 = $this->Database->mysqlSelect(array('slug'), 'properties', $where);
					$rows = $this->Database->mysqlfetchassoc($result1);						
					$soical_url = WWW_ROOT."/properties/details/".$rows[0]['slug'];	
			$success = (('<div id="backlayout" style="display: block;">
			  <div id="lightbox" class="no-scroll" style="border-radius: 8px 8px 8px 8px; padding: 10px; position: absolute; z-index: 10000; width: 500px; height: auto; top: 100px; background: none repeat scroll 0% 0% rgba(82, 82, 82, 0.7); left: 245px;">
				<div id="lightbox-content">
				  <div style="color: rgb(255, 255, 255); font-family: tahoma; font-size: 14px; font-weight: bold; background: none repeat scroll 0% 0% rgb(115, 173, 221); padding: 5px; border-width: 1px 1px 0px; border-style: solid solid none; border-color: rgb(115, 173, 221) rgb(115, 173, 221) -moz-use-text-color; -moz-border-top-colors: none; -moz-border-right-colors: none; -moz-border-bottom-colors: none; -moz-border-left-colors: none; -moz-border-image: none;" id="lb-header"> <span title="Close" style="float: left;" class="lb-title">Property has been updated successfully.</span> <span style="padding:0 2px; float: right; cursor: pointer;" id="lb-close" title="Close">X</span>
					<div style="clear: both;"></div>
				  </div>
				  <div style="background: none repeat scroll 0% 0% rgb(255, 255, 255); margin: 0pt; padding: 10px; border-right: 1px solid rgb(85, 85, 85); border-style: none solid solid; border-width: 1px; border-color: rgb(85, 85, 85);" id="lb-body">Congratulations on listing your apartment for rent!  Share your listing with your friends and followers on your favorite social networks to increase the visibility of your listing.<br />
				  <a name="fb_share" type="icon" share_url="'.$soical_url.'"></a><script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" 
					type="text/javascript"></script><br>
					<a href="https://twitter.com/share" class="twitter-share-button" data-url="'.$soical_url.'" data-text="Check my property" data-count="none">Tweet</a>
					<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script><br>					
					<g:plusone annotation="inline" href="'.$soical_url.'"></g:plusone>
					<script type="text/javascript">
					  (function() {
						var po = document.createElement("script"); po.type = "text/javascript"; po.async = true;
						po.src = "https://apis.google.com/js/plusone.js";
						var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(po, s);
					  })();
					</script>			
				  </div>
				</div>
			  </div>
			</div>'));
            $this->Session->write('newmessage',$success);					
                } elseif ($this->Request->request['save']) {
                    $this->_saveProperty(PropertyStatusConsts::DRAFT);
                    $this->Session->setFlash("<div class='flash-success'>Property has been drafted successfully.</div>");
                } elseif ($this->Request->request['preview']) {
                    $previewID = $this->_saveProperty(PropertyStatusConsts::PREVIEW);
					$where = "status = " . PropertyStatusConsts::PREVIEW;
					$where .= " AND id = '".$previewID['auto_saved_id']."'";
					$result1 = $this->Database->mysqlSelect(array('slug'), 'properties', $where);
					$rows = $this->Database->mysqlfetchassoc($result1);				
					redirect(array('class' => 'properties', 'method' => 'preview',$rows[0]['slug']));
                }
                redirect(array('class' => 'properties', 'method' => $this->params['prefix'] . '_index', $page, $order_by, $order));
            }
        }
        // Normal media files upload
        if ($this->Request->request['ajaxUpload']) {
            $this->uploadPropertyMedia();
        }
        // Fetch property data
        //$where = "properties.slug = '{$this->Database->escape($slug)}'";
        $where = "properties.slug = '{$slug}'";
        if (!$this->Session->checkUserSession($this->Session, array(UserTypeConsts::ADMIN))) {
            $where .= " AND properties.user_id = '{$this->Session->read('User.id')}'";
        }
        $result = $this->Database->assoc(array('properties', 'left pr_unit_info', 'left pr_contact_info', 'left pr_search_tags'), array(array(), array('properties.pr_id'), array('properties.pr_id'), array('properties.pr_id')), $where);
        $rows = $this->Database->mysqlfetchrows($result);
        //var_dump($rows); exit;
        if ($rows) {
            $this->Request->request = $rows[0];
            $this->Request->request['properties.move_in_date'] = dateformat($this->Request->request['properties.move_in_date'], 'd/m/Y');
            $this->Request->request['properties.expiry_date'] = dateformat($this->Request->request['properties.expiry_date'], 'd/m/Y');
            $phone = explode('-', $this->Request->request['prContactInfo.phone']);
            if ($phone) {
                $this->Request->request['phone1'] = isset($phone[0]) ? $phone[0] : '';
                $this->Request->request['phone2'] = isset($phone[1]) ? $phone[1] : '';
                $this->Request->request['phone3'] = isset($phone[2]) ? $phone[2] : '';
            }
            $this->Request->request['auto_saved_id'] = $rows[0]['properties.id'];
            $where = "pr_id = '{$rows[0]['properties.id']}'";
            // Fetch property's predefined features
            $result = $this->Database->mysqlSelect(array('pr_feature_id', 'pr_feature_id AS feature_id'), 'pr_features', $where, null, null, null, null);
            $rows = $this->Database->mysqlfetchlist($result, 'pr_feature_id');
            $this->Request->request['pr_feature_id'] = $rows;
            // Fetch property's extra features
            $result = $this->Database->mysqlSelect(array('pr_feature'), 'pr_extra_features', $where, null, null, null, null);
            $rows = $this->Database->mysqlfetchlist($result, 'id', true);
            $this->Request->request['pr_feature'] = $rows;
            // Fetch unit predefined features
            $result = $this->Database->mysqlSelect(array('pr_unit_feature_id', 'pr_unit_feature_id AS feature_id'), 'pr_unit_features', $where, null, null, null, null);
            $rows = $this->Database->mysqlfetchlist($result, 'pr_unit_feature_id');
            $this->Request->request['pr_unit_feature_id'] = $rows;
            // Fetch property's extra features
            $result = $this->Database->mysqlSelect(array('pr_unit_feature'), 'pr_unit_extra_features', $where, null, null, null, null);
            $rows = $this->Database->mysqlfetchlist($result, 'id', true);
            $this->Request->request['pr_unit_feature'] = $rows;

            $this->Request->request['edit_property'] = 1;
        }
        //$result = $this->Database->mysqlSelect(array('name'), 'city', 'parent_id = 0');
        //$sities = $this->Database->mysqlfetchobjects($result);
        $result = $this->Database->mysqlSelect(array('city'), 'pr_unit_info', $where);
        $cityparent = $this->Database->mysqlfetchobjects($result);
        $city = $cityparent[0]->city;
        $where = "name = '{$cityparent[0]->city}'";
        $result = $this->Database->mysqlSelect(array('parent_id'), 'city', $where);
        $parentid = $this->Database->mysqlfetchobjects($result);
        If ($parentid[0]->parent_id != 0) {
            $neighborhood = $city;
            $where = "id = '{$parentid[0]->parent_id}'";
            $result = $this->Database->mysqlSelect(array('name'), 'city', $where);
            $citymain = $this->Database->mysqlfetchobjects($result);
            $city = $citymain[0]->name;
            $where = "parent_id = '{$parentid[0]->parent_id}'";
            $result = $this->Database->mysqlSelect(array('name'), 'city', $where);
            $neighborhoods = $this->Database->mysqlfetchobjects($result);
            $this->set('neighborhoods', $neighborhoods);
            $this->set('neighborhood', $neighborhood);
        }
        $this->set('unitTypes', $this->listUnitTypes());
        $this->set('states', $this->listStates());
        $this->set('sities', $this->listCities());
        $this->set('lease', $this->listLease());
        //$this->set('sities', $sities);
        $this->set('city', $city);
        $this->set('features', $this->listFeatures());
    }

    /**
     * Add a new property
     *
     * @param <type> $status
     * @return <type>
     */
    private function _saveProperty($status = null)
    {
        $response = array();
        $features = array();
        $where = '';
        $userId = $this->Session->read('User.id');
        if ($this->Request->request['auto_saved_id']) {
            $where = "id = '{$this->Database->escape($this->Request->request['auto_saved_id'])}'";
            $where .= " AND user_id = '$userId'";
        }
        $date = date('Y-m-d H:i:s');
        $fields = array(
            'title', 'description', 'lease_length', 'deposit_amount', 'application_fee', 'move_in_date', 'expiry_date', 'other1', 'other2', 'user_id', 'created', 'modified', 'youtube_id'
        );
        $values = array(
            $this->Request->request['title'],
            $this->Request->request['description'],
            $this->Request->request['lease_length'],
            $this->Request->request['deposit_amount'],
            $this->Request->request['application_fee'],
            convertDatepickerDate($this->Request->request['move_in_date']),
            convertDatepickerDate($this->Request->request['expiry_date']),
            $this->Request->request['other1'],
            $this->Request->request['other2'],
            $userId,
            $date,
            $date,
            $this->Request->request['youtube_id']
        );
        if ($status) {
            $fields[] = 'status';
            $values[] = $status;
        }
        $this->Database->mysqlInsert('properties',
                                     $fields,
                                     $values,
                                     $where
        );

        $response['auto_saved_id'] = $this->Database->lastInsertId();

        if ($this->Request->request['neighborhood'] != '') {
            $city = $this->Request->request['neighborhood'];
        }
        else {
            $city = $this->Request->request['city'];
        }


        // Save Unit details
        $where = "pr_id = '{$this->Database->escape($response['auto_saved_id'])}'";
        $this->Database->mysqlInsert('pr_unit_info', array(
                                                          'pr_id', 'pr_unit_type_id', 'unit_street_address', 'unit_no', 'city', 'state', 'zip', 'rent', 'bed', 'bath', 'square_feet'
                                                     ),
                                     array(
                                          $response['auto_saved_id'],
                                          $this->Request->request['pr_unit_type_id'],
                                          $this->Request->request['unit_street_address'],
                                          $this->Request->request['unit_no'],
                                          $city,
                                          $this->Request->request['state'],
                                          $this->Request->request['zip'],
                                          $this->Request->request['rent'],
                                          $this->Request->request['bed'],
                                          $this->Request->request['bath'],
                                          $this->Request->request['square_feet']
                                     ),
                                     $where
        );

        // Save Property extra features
        $this->Database->mysqlDelete('pr_extra_features', $where);
        foreach ($this->Request->request['pr_feature'] as $key => $feature) {
            if (trim($feature) != '') {
                $features[$key][] = $response['auto_saved_id'];
                $features[$key][] = $feature;
            }
        }
        if ($features) {
            $this->Database->mysqlInsertAll('pr_extra_features', array(
                                                                      'pr_id', 'pr_feature'
                                                                 ),
                                            $features
            );
        }

        // Save Property predefined features        
        if ($this->Request->request['pr_feature_id']) {
            $features = array();
            $this->Database->mysqlDelete('pr_features', $where);
            foreach ($this->Request->request['pr_feature_id'] as $key => $feature) {
                if (trim($feature) != '') {
                    $features[$key][] = $response['auto_saved_id'];
                    $features[$key][] = $feature;
                }
            }
            if ($features) {
                $this->Database->mysqlInsertAll('pr_features', array(
                                                                    'pr_id', 'pr_feature_id'
                                                               ),
                                                $features
                );
            }
        }

        // Save Property unit extra features
        $features = array();
        $this->Database->mysqlDelete('pr_unit_extra_features', $where);
        foreach ($this->Request->request['pr_unit_feature'] as $key => $feature) {
            if (trim($feature) != '') {
                $features[$key][] = $response['auto_saved_id'];
                $features[$key][] = $feature;
            }
        }
        if ($features) {
            $this->Database->mysqlInsertAll('pr_unit_extra_features', array(
                                                                           'pr_id', 'pr_unit_feature'
                                                                      ),
                                            $features
            );
        }

        // Save Property unit predefined features
        if ($this->Request->request['pr_unit_feature_id']) {
            $features = array();
            $this->Database->mysqlDelete('pr_unit_features', $where);
            foreach ($this->Request->request['pr_unit_feature_id'] as $key => $feature) {
                if (trim($feature) != '') {
                    $features[$key][] = $response['auto_saved_id'];
                    $features[$key][] = $feature;
                }
            }
            if ($features) {
                $this->Database->mysqlInsertAll('pr_unit_features', array(
                                                                         'pr_id', 'pr_unit_feature_id'
                                                                    ),
                                                $features
                );
            }
        }

        // Save Property contact information
        $this->Request->request['phone'] = $this->Request->request['phone1'] . '-' . $this->Request->request['phone2'] . '-' . $this->Request->request['phone3'];
		$perfer = '';
		if($this->Request->request['prefer'] == 'phone') {
			$prefer = 'phone';
		} elseif($this->Request->request['prefer'] == 'email') {
			$prefer = 'email';
		}		
        $this->Database->mysqlInsert('pr_contact_info', array(
                                                             'pr_id', 'contact_name', 'phone', 'contact_email', 'street_address', 'website', 'prefer'
                                                        ),
                                     array(
                                          $response['auto_saved_id'],
                                          $this->Request->request['contact_name'],
                                          $this->Request->request['phone'],
                                          $this->Request->request['contact_email'],
                                          $this->Request->request['street_address'],
                                          $this->Request->request['website'],
										  $prefer
                                     ),
                                     $where
        );

        // Save Property search tags
        if (trim($this->Request->request['tags']) != '') {
            $this->Database->mysqlInsert('pr_search_tags', array(
                                                                'pr_id', 'tags'
                                                           ),
                                         array(
                                              $response['auto_saved_id'],
                                              $this->Request->request['tags'],
                                         ),
                                         $where
            );
        }

        // Save property media files such as property photos / videos (if any)
        $pMedia = $this->Session->read('pmedia');
        if ($pMedia) {
            $files = array();
            //$this->Database->mysqlDelete('pr_gallery', $where);
            foreach ($pMedia as $key => $media) {
                $files[$key][] = $response['auto_saved_id'];
                $files[$key][] = $userId;
                $files[$key][] = $media;
            }
            if ($files) {
                $this->Database->mysqlInsertAll('pr_gallery', array(
                                                                   'pr_id', 'user_id', 'file'
                                                              ),
                                                $files
                );
            }
            $this->Session->delete('pmedia');
        }
	 $pMedia2 = $this->Session->read('pmedia2');
        if ($pMedia2) {
            $files = array();
            //$this->Database->mysqlDelete('pr_gallery', $where);
            foreach ($pMedia2 as $key => $media) {
                $files[$key][] = $response['auto_saved_id'];
                $files[$key][] = $userId;
                $files[$key][] = $media;
		$files[$key][] = '2';
            }
            if ($files) {
                $this->Database->mysqlInsertAll('pr_gallery', array(
                                                                   'pr_id', 'user_id', 'file','type'
                                                              ),
                                                $files
                );
            }
            $this->Session->delete('pmedia2');
        }

        return $response;
    }

    /**
     * Upload property photos / videos
     *
     * @access Loggedin Users
     */
    function uploadPropertyMedia()
    {
        if ($this->Session->checkSession($this->Session)) {
            if ($this->Request->request['pmedia']) {
                // create directory structure for uploads
                $dest = createDirectory(RESOURCES_ROOT, 'uploads', 'properties', 'gallery');
                // Upload product picture
                // pr($this->Request->request['pmedia']);
                $files = upload($this->Request->request['pmedia'], array('image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/pjpeg', 'video/x-flv', 'application/octet-stream'), $dest);
                if ($files) {
                    if ($this->Request->request['ajaxUpload'])
                        $this->Session->write('pmedia', $files);
                    else
                        $this->Session->writeArray('pmedia', $files);
                }
                if ($this->Request->request['ajaxUpload']) {
                    if ($files)
                        echo '<div style="clear:both;margin-top:10px;background:green;color:#fff"><b>Alert: Your images have been uploaded successfully.</b></div>';
                    else
                        echo 'Oops! Files not uploaded';
                }
            }else  if ($this->Request->request['pmedia2']) {
                // create directory structure for uploads
                $dest = createDirectory(RESOURCES_ROOT, 'uploads', 'properties', 'gallery');
                // Upload product picture
                // pr($this->Request->request['pmedia']);
                $files = upload($this->Request->request['pmedia2'], array('image/png', 'image/jpg', 'image/jpeg', 'image/gif', 'image/pjpeg', 'video/x-flv', 'application/octet-stream','application/pdf'), $dest);
                if ($files) {
                    if ($this->Request->request['ajaxUpload'])
                        $this->Session->write('pmedia2', $files);
                    else
                        $this->Session->writeArray('pmedia2', $files);
                }
                if ($this->Request->request['ajaxUpload']) {
                    if ($files)
                        echo '<div style="clear:both;margin-top:10px;background:green;color:#fff"><b>Alert: Your floor plan have been uploaded successfully.</b></div>';
                    else
                        echo 'Oops! Files not uploaded';
                }
            }
        }
        exit;
    }

    /**
     * Delete a property
     *
     * @param <type> $id
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     * @access  Superadmin
     */
    function admin_delete($id = 0, $page = 1, $order_by = 'id', $order = 'DESC')
    {
        $where = "id = '{$this->Database->escape($id)}'";
        $this->Database->mysqlDelete('properties', $where, true, 'pr_id');
        $this->Session->setFlash("<div class='flash-success'>Property has been deleted successfully.</div>");

        redirect(array('class' => 'properties', 'method' => 'admin_index', $page, $order_by, $order));
    }

    /**
     * Delete a property
     *
     * @param <type> $id
     * @param <type> $page
     * @param <type> $order_by
     * @param <type> $order
     * @access  Landlord
     */
    function dashboard_delete($slug = '', $page = 1, $order_by = 'id', $order = 'DESC')
    {
        $where = "slug = '{$this->Database->escape($slug)}'";
        $where .= " AND user_id = '$this->loggedInUserId'";
        $this->Database->mysqlDelete('properties', $where, true, 'pr_id');

        echo json_encode(array('<div class="flash-sold">Property has been deleted successfully.</div>'));
        exit;
    }

    /**
     * Remove a property from your favorites list
     *
     * @param <type> $favId
     */
    function dashboard_unfavorite($favId = 0)
    {
        $where = "id = '{$this->Database->escape($favId)}'";
        $where .= " AND user_id = '$this->loggedInUserId'";
        $this->Database->mysqlDelete('user_property_favorites', $where);

        echo json_encode(array('<div class="flash-sold">Property has been unfavorited successfully.</div>'));
        exit;
    }

    /**
     * Change property status Draft / Published
     *
     * @param <type> $id
     */
    function admin_toggleStatus($id = 0)
    {
        $where = "id = '{$this->Database->escape($id)}'";
        $result = $this->Database->mysqlSelect(array('status'), 'properties', $where);
        $row = $this->Database->mysqlfetchobject($result);
        if ($row->status == PropertyStatusConsts::PUBLISH) {
            $newStatus = PropertyStatusConsts::DRAFT;
            echo json_encode(array('Draft', '<div class="flash-success">Property has been drafted successfully.</div>'));
        } else {
            $newStatus = PropertyStatusConsts::PUBLISH;
            echo json_encode(array('Published', '<div class="flash-success">Property has been published successfully.</div>'));
        }
        $this->Database->mysqlUpdate('properties', array('status'), array($newStatus), $where);
        exit;
    }

    function ifrm()
    {
        $this->pagelayout = 'admin';
    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     * @access  Superadmin
     */
    function admin_gallery($propertyId = 0)
    {
        $this->_gallery($propertyId);
    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     * @access  Landlord
     */
    function dashboard_gallery($propertyId = 0,$type="0")
    {
        $this->_gallery($propertyId,$type);
    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     */
    private function _gallery($propertyId = 0,$type=0)
    {
        $this->element = 'properties/gallery';
        $rows = array();
        if ($propertyId) {
            $userId = $this->Session->read('User.id');
            $where = "pr_id = '{$this->Database->escape($propertyId)}'";
            $where .= " AND user_id = '$userId'";
	    if( $type)
			$where .= " AND type = '$type'";
		else
		      $where .= " AND type != '2'";
            $result = $this->Database->mysqlSelect(array('file', 'pr_id'), 'pr_gallery', $where, 'type DESC', null, null, null);
            $rows = $this->Database->mysqlfetchassoc($result);
        }
        $where = "pr_id = '{$this->Database->escape($propertyId)}'";
        $where .= " AND type = 1";
        $result = $this->Database->mysqlSelect(array('id'), 'pr_gallery', $where, null, null, null, null);
        $checked = $this->Database->mysqlfetchassoc($result);
        $where = "pr_id = '{$this->Database->escape($propertyId)}'";
        $where .= " AND type = 2";
        $result = $this->Database->mysqlSelect(array('id'), 'pr_gallery', $where, null, null, null, null);
        $plan = $this->Database->mysqlfetchassoc($result);
        $this->set('checked', $checked[0]['id']);
        $this->set('plan', $plan[0]['id']);
        $this->set('files', $rows);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     */
    function gallery($propertyId = 0, $json = false)
    {
        $this->element = 'properties/gallery';
        $rows = array();
        if ($propertyId) {
            $where = "pr_id = '{$this->Database->escape($propertyId)}'";
            $where .= " AND file REGEXP '[^\.flv]$'";
            $where .= " AND type != '2'";
            $result = $this->Database->mysqlSelect(array('file', 'pr_id'), 'pr_gallery', $where, 'type DESC', null, null, null);
            $rows = $this->Database->mysqlfetchassoc($result);
        }
        $this->set('files', $rows);
        if ($json) {
            $images = array();
            if ($rows) {
                $Include = new include_resource();
                foreach ($rows as $row) {
                    $images[] = $Include->showImage(WWW_ROOT . '/resources/uploads/properties/gallery/' . $row['file'], array('dims' => array('width' => 303, 'height' => 199)), true);
                }
            }
            echo json_encode($images);
            exit;
        }
    }

    /**
     * Property photos slideshow
     *
     * @param <type> $slug
     */
    function photos($slug = '')
    {
        $where = "slug = '{$this->Database->escape($slug)}'";
        $result = $this->Database->mysqlSelect(array('title', 'id'), 'properties', $where);
        $row = $this->Database->mysqlfetchobject($result);
        if ($row) {
            $this->gallery($row->id);
            $this->set('title', $row->title);
        }
        $this->element = 'properties/view_all';
    }

    /**
     * Show property video
     *
     * @param <type> $propertyId
     */
    function showVideo($propertySlug = 0)
    {
        $this->element = 'properties/video';
        $row = array();
        $row_youtube = array();
        if ($propertySlug) {
            $where = "p.slug = '{$this->Database->escape($propertySlug)}'";
            $where .= " AND file REGEXP '[\.flv]$'";
            $result = $this->Database->mysqlQuery("
                                                    SELECT title, file
                                                    FROM properties AS p
                                                    LEFT JOIN pr_gallery AS pg
                                                    ON p.id = pg.pr_id                                                    
                                                    WHERE $where
                                                    GROUP BY p.id"
            );
            $row = $this->Database->mysqlfetchobject($result);
            $where = "slug = '{$this->Database->escape($propertySlug)}'";
            $result = $this->Database->mysqlQuery("
                                                    SELECT title, youtube_id
                                                    FROM properties                                                 
                                                    WHERE $where"
            );
            $row_youtube = $this->Database->mysqlfetchobject($result);
        }
        $this->set('video', $row);
        $this->set('video_youtube', $row_youtube);
    }

    /**
     * Show floor plan
     *
     * @param <type> $propertyId
     */
    function showplan($propertySlug = 0)
    {
        //echo hi; exit;
        $this->element = 'properties/floorplan';
        $row = array();
        $row_youtube = array();
        if ($propertySlug) {
            $where = "p.slug = '{$this->Database->escape($propertySlug)}'";
            $where .= " AND type = '2'";
            $result = $this->Database->mysqlQuery("
                                                    SELECT title, file
                                                    FROM properties AS p
                                                    LEFT JOIN pr_gallery AS pg
                                                    ON p.id = pg.pr_id                                                    
                                                    WHERE $where
                                                    GROUP BY p.id"
            );
            $row = $this->Database->mysqlfetchobject($result);
        }
        $this->set('floorplan', $row);
    }

    function downloadplan($path){
		download_floor(base64_decode($path));
		exit;
    }

    /**
     * Property print page
     *
     * @param <type> $slug
     */
    function printListing($slug = '')
    {	
        $where = "properties.slug = '$slug'";
        $where .= " AND properties.status = " . PropertyStatusConsts::PUBLISH;
        $result = $this->Database->assoc(array('pr_unit_info', 'pr_gallery', 'pr_contact_info', 'properties'), array(array('properties.pr_id'), array('properties.pr_id'), array('properties.pr_id')), $where, '', "properties.id");
        $row = $this->Database->mysqlfetchassoc($result);
        if ($row) {
            $this->pageTitle = $row[0]['properties.title'];
            $this->metaDescription = $row[0]['properties.description'];
        } else {
            $this->pageTitle = 'Not found';
        }
        $this->set('property', $row);
        $this->set('unit_types', $this->listUnit_types());	
		$this->pagelayout = 'print';
    }
	
	/**
     * Property Flag Pop -Up
     *
     * @param <type> $slug
     */
	 
	 function dashboard_flagListing($slug = '')
	 {
	 	if($slug != '')
		$this->flagListing($slug);
	 }
	  function flagListing($slug = '')
    {	
//		echo "Every Thing is Fine:)" ;
             $this->element = 'properties/flaglisting';
        if (trim($this->Request->request['slug']) != '' && trim($this->Request->request['flag_desc']) != '') {
            
			
			$sql = "SELECT * From properties where slug = '".$this->Request->request['slug']."' LIMIT 1";
			$pr_detail = $this->Database->mysqlQuery($sql);			
			$rows = $this->Database->mysqlfetchassoc($pr_detail);
			
$sql = "INSERT INTO pr_flag(pr_id, user_id, flag_desc) Values(".$rows[0]['id'].",".$_SESSION['User']['id'].",'".$this->Request->request['flag_desc']."')";
            $this->Database->mysqlQuery($sql);
			   $this->Session->setFlash("<div class='flash-success'>Property Marked as Flag....</div>");
			   redirect(array('class' => 'properties', 'method' => 'details', $this->Request->request['slug']));
		            
        } else {
            $this->Session->setFlash("<div class='flash-error'>Description Can't Be Left Blank</div>");
			redirect(array('class' => 'properties', 'method' => 'details', $this->Request->request['slug']));
        }


    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     */
    function showMedia($propertyId = 0, $file = '')
    {
        $this->element = 'properties/gallery_item';
        $this->set('propertyId', $propertyId);
        $this->set('file', $file);
    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     * @access  Landlord
     */
    function dashboard_showMedia($propertyId = 0, $file = '')
    {
        $this->element = 'properties/gallery_item';
        $this->set('propertyId', $propertyId);
        $this->set('file', $file);
    }

    /**
     * Fetch all the media files of a property
     *
     * @param <type> $propertyId
     * @access  Superadmin
     */
    function admin_showMedia($propertyId = 0, $file = '')
    {
        $this->element = 'properties/gallery_item';
        $this->set('propertyId', $propertyId);
        $this->set('file', $file);
    }

    /**
     * Delete a property media file
     *
     * @param <type> $id
     */
    function removeMedia($id = 0)
    {
        $userId = $this->Session->read('User.id');
        $where = "id = '{$this->Database->escape($id)}'";
        $where .= " AND user_id = '$userId'";
        $this->Database->mysqlDelete('pr_gallery', $where);
        echo json_encode(array('removed'));
        exit;
    }

    /**
     * Main property image
     *
     * @param <type> $id
     */
    function mainimage($id = 0)
    {
        //var_dump($id); exit;
        $where = "id = '$id'";
        $result = $this->Database->mysqlSelect(array('pr_id'), 'pr_gallery', $where);
        $row = $this->Database->mysqlfetchobject($result);
        $pr_id = $row->pr_id;
        $wherepr_id = "pr_id = '$pr_id'";
        $this->Database->mysqlUpdate('pr_gallery', array('type'), array('0'), $wherepr_id);
        $this->Database->mysqlUpdate('pr_gallery', array('type'), array('1'), $where);
        exit;
    }

    /**
     * Floor Plan property image
     *
     * @param <type> $id
     */
    function floorplan($id = 0)
    {
        //var_dump($id); exit;
        $where = "id = '$id'";
        $result = $this->Database->mysqlSelect(array('pr_id'), 'pr_gallery', $where);
        $row = $this->Database->mysqlfetchobject($result);
        $pr_id = $row->pr_id;
        $wherepr_id = "pr_id = '$pr_id'";
        $wherepr_id .= " AND type = '2'";
        $this->Database->mysqlUpdate('pr_gallery', array('type'), array('0'), $wherepr_id);
        $this->Database->mysqlUpdate('pr_gallery', array('type'), array('2'), $where);
        exit;
    }

    /**
     * List of all the unit types
     *
     * @return Array
     */
    function listUnitTypes()
    {
        $result = $this->Database->mysqlSelect(array('unit_type'), 'unit_types', null, 'unit_type ASC', null, null, null);
        $rows = $this->Database->mysqlfetchlist($result);

        return $rows;
    }

    /**
     * List of all states of USA
     *
     * @return Array
     */
    function listStates()
    {
        $result = $this->Database->mysqlSelect(array('state_code', 'state'), 'states', null, 'state ASC', null, null, null);
        $rows = $this->Database->mysqlfetchlist($result, 'state_code');

        return $rows;
    }

    /**
     * List of all states of USA
     *
     * @return Array
     */
    function listCities()
    {
        $result = $this->Database->mysqlSelect(array('name', 'id'), 'city', 'parent_id = 0');
        $rows = $this->Database->mysqlfetchobjects($result);
        return $rows;
    }

	function listUnitFeatures()
	{
		$result = $this->Database->mysqlSelect(array('feature', 'id'), 'property_features', 'type = 1');
		$rows = $this->Database->mysqlfetchobjects($result);
		return $rows;
	}

	function listBuildingFeatures()
	{
		$result = $this->Database->mysqlSelect(array('feature', 'id'), 'property_features', 'type = 2');
		$rows = $this->Database->mysqlfetchobjects($result);
		return $rows;
	}

    /**
     * List of all states of USA
     *
     * @return Array
     */
    function listUnit_types()
    {
        $result = $this->Database->mysqlSelect(array('id', 'unit_type'), 'unit_types');
        $rows = $this->Database->mysqlfetchobjects($result);
        return $rows;
    }

    /**
     * List of lease
     *
     * @return Array
     */
    function listLease()
    {
        //$lease = array();
        $lease['6 month'] = '6 month';
        $lease['1 year'] = '1 year';
        $lease['2 years'] = '2 years';
        $lease['3+ years'] = '3+ years';
		$lease['Flexible'] = 'Flexible';
		$lease['Month to Month'] = 'Month to Month';		
        //var_dump($lease); exit;
        return $lease;
    }

    /**
     * List of all added Cities
     *
     * @return Array
     */
    function listSities()
    {
        $result = $this->Database->mysqlSelect(array('id', 'name'), 'city', null, 'name ASC', null, null, null);
        $rows = $this->Database->mysqlfetchlist($result, 'id');

        return $rows;
    }

    /**
     * List of all property features such as Dogs, Cats, Laundry etc.
     *
     * @return Array
     */
    function listFeatures()
    {
        $result = $this->Database->mysqlSelect(array('id', 'feature', 'on_image', 'off_image'), 'property_features', null, 'id ASC', null, null, null);
        $rows = $this->Database->mysqlfetchobjects($result);

        return $rows;
    }

    /**
     * Apartments advanced listing
     */
    function advancedListing($page = 1, $order_by = 'properties.id', $order = 'DESC')
    {
        $list = array();
		$global_search = array();
        $customFields = array();
        $having = '1';
        $this->pageTitle = 'Apartments advanced listing';
        $this->set('rightSidebar', 'properties/advanced_listing_sidebar');
        $where = "properties.status = " . PropertyStatusConsts::PUBLISH;
        $list['order_by'] = $order_by;
        // Normal search
        // Full text Search city, zip or keywords
		$s = ($this->Request->has('s') && trim($this->Request->request['s']) != 'Your search terms here..') ?  $this->Database->escape(trim($this->Request->request['s'])) : false;


        if ($s) {
			$global_search[] = array('s' => $s);
            $this->pageTitle = 'Search results &laquo; ' . $this->pageTitle;

            $fulltext = "(MATCH(prUnitInfo.city, prUnitInfo.zip, prUnitInfo.unit_street_address, properties.title, properties.description) AGAINST ('{$s}*' IN BOOLEAN MODE))";
            $customFields[] = $fulltext . " AS score";
            $where .= " AND $fulltext";
            $order_by = "score DESC, " . $order_by;
        }
//		else $this->Session->delete('GLOBAL_SEARCH');

        if (isset($this->Request->request['bed']) && $this->Request->request['bed']) {
            $this->pageTitle = 'Search results &laquo; ' . $this->pageTitle;
            $bed = $this->Database->escape($this->Request->request['bed']);
            $where .= " AND prUnitInfo.bed = '$bed'";
        }


        $rentFrom = $this->Request->has('from') ? $this->Database->escape($this->Request->request['from']) : false;
        $rentTo = $this->Request->has('to') ? $this->Database->escape($this->Request->request['to']) : false;
        if (is_numeric($rentFrom) && is_numeric($rentTo)) {
            $where .= " AND prUnitInfo.rent BETWEEN $rentFrom AND $rentTo";
        } elseif (is_numeric($rentFrom)) {
            $where .= " AND prUnitInfo.rent >= '$rentFrom'";
        } elseif (is_numeric($rentTo)) {
            $where .= " AND prUnitInfo.rent <= '$rentTo'";
        }


        if ($this->Request->has('bath') && $this->Request->request['bath']) {
            $bath = $this->Database->escape($this->Request->request['bath']);
            $where .= " AND prUnitInfo.bath = '$bath'";
        }




        $fields = array('properties', 'pr_unit_info', 'left pr_gallery');
        $fKeys = array(array(), array('properties.pr_id'), array('properties.pr_id'));



// Advance search
		$citys = ($this->Request->has('citys') && ($this->Request->request['citys'])) ? $this->Request->request['citys'] : array();
		$hoods = ($this->Request->has('hoods') && is_array($this->Request->request['hoods'])) ? $this->Request->request['hoods'] : array();
		$where_c = array();
		if (count($citys) && count($hoods)) {
			foreach ($citys as $key => $val) {
				if (isset($hoods[$key])) {
					$global_search[] = array('citys' => $val, 'hoods' => $hoods[$key]);
					$where_c[] = "'" . $this->Database->escape($hoods[$key]) . "'";
				}
			}
//			$where .= " AND prUnitInfo.city IN (" . implode(',', $where_c) . ")";
		}


		$parent_city = ($this->Request->has('city') && trim($this->Request->request['city']) != 'Search city, zip or keywords') ? $this->Database->escape(($this->Request->request['city'])) : false;
		if ($parent_city && ! count($where_c)) {
			$child_city = ($this->Request->has('hood') && is_array($this->Request->request['hood'])) ? $this->Request->request['hood'] : array();

			if (count($child_city)) {
				foreach ($child_city as $cc_key => $cc_val) {
					$global_search[] = array('citys' => $parent_city, 'hoods' => $cc_val);
					$where_c[] = "'" . $this->Database->escape($cc_val) . "'";
				}
			} else {
				$global_search[] = array('city' => $parent_city);

			}
		}

		if (count($where_c)) {
			$where .= " AND prUnitInfo.city IN (" . implode(',', $where_c) . ")";
		} else if ($parent_city) {
			$where .= " AND prUnitInfo.city = '" . $parent_city . "'";
		}


        if ($this->Request->has('lease_length')) {
            $leaseLength = $this->Database->escape($this->Request->request['lease_length']);
            $where .= " AND properties.lease_length = '$leaseLength'";
        }

        // If property feature filter is selected
		$u_feat = ($this->Request->has('pr_feature_id') && is_array($this->Request->request['pr_feature_id'])) ? $this->Request->request['pr_feature_id'] : array();

		if (count($u_feat)) {
			$fields[] = 'pr_features';
			$fKeys[] = array('properties.pr_id');

//			$u_feat = array_merge($b_feat, $u_feat);
			$where .= " AND prFeatures.pr_feature_id IN ('" . implode(', ', $u_feat) . "')";
		}


        // Property listing sorting
        if (isset($this->Request->request['sort_by']) && $this->Request->request['sort_by']) {
            switch ($this->Request->request['sort_by']) {
                case 'title':
                    $order_by = 'properties.title';
                    $list['order_by'] = $order_by;
                    break;
                case 'rent':
                    $order_by = 'prUnitInfo.rent';
                    $list['order_by'] = $order_by;
                    break;
                case 'newset':
                    $order_by = 'properties.created';
                    $list['order_by'] = $order_by;
					$order = 'DESC';
                    break;
                case 'price_low_to_high':
                    $order_by = 'prUnitInfo.rent';
                    $list['order_by'] = $order_by;
					$order = 'ASC';
                    break;
                case 'price_high_to_low':
                    $order_by = 'prUnitInfo.rent';
                    $list['order_by'] = $order_by;
					$order = 'DESC';
                    break;
                case 'atoz':
                    $order_by = 'properties.title';
                    $list['order_by'] = $order_by;
					$order = 'ASC';
                    break;		
                case 'ztoa':
                    $order_by = 'properties.title';
                    $list['order_by'] = $order_by;
					$order = 'DESC';
                    break;
                case 'most_viewed':
                    $order_by = 'properties.hit_count';
                    $list['order_by'] = $order_by;
					$order = 'DESC';
                    break;						
            }
        }

        $this->Database->customFields($customFields);
        $pageData = $this->Database->paginate($fields, $fKeys, $where, $order_by . " " . $order, "properties.id", $having, $page, PROPERTY_LIMIT);
        $list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
        $list['pages'] = $pageData['pages'];
        $list['page'] = $page;
        $list['order'] = $order;
		$this->set('global_search', $global_search);
        $this->set('list', $list);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
        $this->set('totalrecords', $pageData['totalrecords']);
        $this->Database->customFields();
    }

//	function getAdvancedList_Ajax ($page = 1, $order_by = 'properties.id', $order = 'DESC') {
//		$list = array('order_by' => 'properties.id');
//		$customFields = array();
//		$having = '1';
//		$where = "properties.status = " . PropertyStatusConsts::PUBLISH;
//		$list['order_by'] = $order_by;
//
//// Start Search
////		$global_search = $this->Session->read('GLOBAL_SEARCH');
//
//
//		$s = $this->Request->request['s'];
//
//		if (isset($s) && $s != trim('Search city, zip or keywords')) {
//			$s = $this->Database->escape(trim($s));
//
//			$fulltext = "(MATCH(prUnitInfo.city, prUnitInfo.zip, prUnitInfo.unit_street_address, properties.title, properties.description) AGAINST ('{$s}*' IN BOOLEAN MODE))";
//			$customFields[] = $fulltext . " AS score";
//			$where .= " AND $fulltext";
//			$order_by = "score DESC, " . $order_by;
//		}
//
//		if (isset($this->Request->request['bed']) && $this->Request->request['bed']) {
//			$this->pageTitle = 'Search results &laquo; ' . $this->pageTitle;
//			$bed = $this->Database->escape($this->Request->request['bed']);
//			$where .= " AND prUnitInfo.bed = '$bed'";
//		}
//
//		$rentFrom = $this->Request->has('from') ? $this->Database->escape($this->Request->request['from']) : false;
//		$rentTo = $this->Request->has('to') ? $this->Database->escape($this->Request->request['to']) : false;
//		if (is_numeric($rentFrom) && is_numeric($rentTo)) {
//			$where .= " AND prUnitInfo.rent BETWEEN $rentFrom AND $rentTo";
//		} elseif (is_numeric($rentFrom)) {
//			$where .= " AND prUnitInfo.rent >= '$rentFrom'";
//		} elseif (is_numeric($rentTo)) {
//			$where .= " AND prUnitInfo.rent <= '$rentTo'";
//		}
//
//		if ($this->Request->has('bath') && $this->Request->request['bath']) {
//			$bath = $this->Database->escape($this->Request->request['bath']);
//			$where .= " AND prUnitInfo.bath = '$bath'";
//		}
//
//		$fields = array('properties', 'pr_unit_info', 'left pr_gallery');
//		$fKeys = array(array(), array('properties.pr_id'), array('properties.pr_id'));
//
//		$u_feat = ($this->Request->has('pr_feature_id') && is_array($this->Request->request['pr_feature_id'])) ? $this->Request->request['pr_feature_id'] : array();
//		if (count($u_feat)) {
//			$fields[] = 'pr_features';
//			$fKeys[] = array('properties.pr_id');
//
//			//			$u_feat = array_merge($b_feat, $u_feat);
//			$where .= " AND prFeatures.pr_feature_id IN ('" . implode(', ', $u_feat) . "')";
//		}
//
//
//		if (isset($this->Request->request['sort_by']) && $this->Request->request['sort_by']) {
//			switch ($this->Request->request['sort_by']) {
//				case 'title':
//					$order_by = 'properties.title';
//					$list['order_by'] = $order_by;
//					break;
//				case 'rent':
//					$order_by = 'prUnitInfo.rent';
//					$list['order_by'] = $order_by;
//					break;
//				case 'ad_date':
//					$order_by = 'properties.created';
//					$list['order_by'] = $order_by;
//					break;
//				case 'price_low_to_high':
//					$order_by = 'prUnitInfo.rent';
//					$list['order_by'] = $order_by;
//					$order = 'ASC';
//					break;
//				case 'price_high_to_low':
//					$order_by = 'prUnitInfo.rent';
//					$list['order_by'] = $order_by;
//					$order = 'DESC';
//					break;
//				case 'atoz':
//					$order_by = 'properties.title';
//					$list['order_by'] = $order_by;
//					$order = 'ASC';
//					break;
//				case 'ztoa':
//					$order_by = 'properties.title';
//					$list['order_by'] = $order_by;
//					$order = 'DESC';
//					break;
//				case 'most_viewed':
//					$order_by = 'properties.hit_count';
//					$list['order_by'] = $order_by;
//					$order = 'DESC';
//					break;
//			}
//		}
//
//		$this->Database->customFields($customFields);
//		$pageData = $this->Database->paginate($fields, $fKeys, $where, $order_by . " " . $order, "properties.id", $having, $page, PROPERTY_LIMIT);
//		$list['records'] = $this->Database->mysqlfetchassoc($pageData['recordset']);
//		$list['pages'] = $pageData['pages'];
//		$list['page'] = $page;
//		$list['order'] = $order;
////		$this->set('list', $list);
////		$this->pagelayout('getAdvancedList_Ajax');
//
//		echo json_encode($list);
//		$this->Database->customFields();
//		exit;
//	}


	function getCityByWord_Ajax () {
		$result = array('error' => true, 'results' => array());

		$word = $this->Request->has('words') ? $this->Database->escape(trim($this->Request->request['words'])) : false;

		if ($word) {
			$query_res = $this->Database->mysqlRawquery("SELECT name, id FROM city WHERE name LIKE '%" . $word . "%' AND parent_id = 0");
			if ($this->Database->mysqlnumrows($query_res)) {
				$result = array('error' => false, 'results' => $this->Database->mysqlfetchassoc($query_res));
			}
		}

		echo json_encode($result);
		exit;
	}

	function getNeighborhoodByCityID_Ajax () {
		$result = array('error' => true, 'results' => array());

		$city_id = $this->Request->has('city') ? $this->Database->escape(trim($this->Request->request['city'])) : false;

		if ($city_id) {
			$query_res = $this->Database->mysqlRawquery("SELECT name, id FROM city WHERE parent_id = " . $city_id);
			if ($this->Database->mysqlnumrows($query_res)) {
				$result = array('error' => false, 'results' => $this->Database->mysqlfetchassoc($query_res));
			}
		}

		echo json_encode($result);
		exit;
	}
    /**
     * Property/Apartment details page
     *
     * @param <type> $slug
     */
    function details($slug = '')
    {
        $nextLink = null;
        $previousLink = null;
        //$slug = $this->Database->escape($slug);
        $where = "properties.slug = '$slug'";
        $where .= " AND properties.status = " . PropertyStatusConsts::PUBLISH;
        $result = $this->Database->assoc(array('pr_unit_info', 'pr_gallery','pr_search_tags', 'pr_contact_info', 'properties'), array(array('properties.pr_id'), array('properties.pr_id'),array('properties.pr_id'), array('properties.pr_id')), $where, '', "properties.id");
        $row = $this->Database->mysqlfetchassoc($result);
        if ($row) {
            $this->pageTitle = $row[0]['properties.title'];
            $this->metaDescription = $row[0]['properties.description'];
            // Previous property link
            $where = "id < " . $row[0]['properties.id'];
            $where .= " AND status = " . PropertyStatusConsts::PUBLISH;
            $result = $this->Database->mysqlSelect(array('slug'), 'properties', $where, 'id DESC', '', '', 1);
            $r = $this->Database->mysqlfetchobject($result);
            if ($r) {
                $previousLink = $r->slug;
            }
            // Next property link
            $where = "id > " . $row[0]['properties.id'];
            $where .= " AND status = " . PropertyStatusConsts::PUBLISH;
            $result = $this->Database->mysqlSelect(array('slug'), 'properties', $where, 'id ASC', '', '', 1);
            $r = $this->Database->mysqlfetchobject($result);
            if ($r) {
                $nextLink = $r->slug;
            }
            $this->set('previousLink', $previousLink);
            $this->set('nextLink', $nextLink);
            // Other listing -- Carousel script
            $Include = new include_resource();
            $this->Content->addScript($Include->js("jquery.jcarousel.min.js"));
			$sql = "UPDATE properties SET hit_count = (hit_count+1) WHERE slug = '".$slug."'";
			$this->Database->mysqlQuery($sql);	
			// Check Floorplan
			$floor = array();
			if ($slug) {
				$fwhere = "p.slug = '{$this->Database->escape($propertySlug)}'";
				$fwhere .= " AND type = '2'";
				$fresult = $this->Database->mysqlQuery("
														SELECT title, file
														FROM properties AS p
														LEFT JOIN pr_gallery AS pg
														ON p.id = pg.pr_id                                                    
														WHERE $where
														GROUP BY p.id"
				);
				$floor = $this->Database->mysqlfetchobject($fresult);
			}
			$this->set('floorplan1', $floor);	
			// Check Video
			$vid = array();
			$vid_youtube = array();
			if ($slug) {
				$vidwhere = "p.slug = '{$this->Database->escape($propertySlug)}'";
				$vidwhere .= " AND file REGEXP '[\.flv]$'";
				$vidresult = $this->Database->mysqlQuery("
														SELECT title, file
														FROM properties AS p
														LEFT JOIN pr_gallery AS pg
														ON p.id = pg.pr_id                                                    
														WHERE $where
														GROUP BY p.id"
				);
				$vid = $this->Database->mysqlfetchobject($vidresult);
				$vidwhere = "slug = '{$this->Database->escape($propertySlug)}'";
				$vidresult = $this->Database->mysqlQuery("
														SELECT title, youtube_id
														FROM properties                                                 
														WHERE $where"
				);
				$vid_youtube = $this->Database->mysqlfetchobject($vidresult);
			}
			$this->set('video1', $vid);
			$this->set('video_youtube1', $vid_youtube);							
        } else {
            $this->pageTitle = 'Not found';
        }
        $this->set('property', $row);
		$this->set('pr_tags', $row[0]['prSearchTags.tags']);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }
	
    /**
     * Property/Apartment preview page
     *
     * @param <type> $slug
     */
    function preview($slug = '')
    {
        $where = "properties.slug = '$slug'";
        $where .= " AND properties.status = '" . PropertyStatusConsts::PREVIEW."'"; 
        $result = $this->Database->assoc(array('pr_unit_info INNER', 'pr_gallery LEFT', 'pr_contact_info INNER', 'properties INNER'), array(array('properties.pr_id'), array('properties.pr_id'), array('properties.pr_id')), $where, '', "properties.id");
        $row = $this->Database->mysqlfetchassoc($result);
        if ($row) {
            $this->pageTitle = $row[0]['properties.title'];
            $this->metaDescription = $row[0]['properties.description'];
            // Other listing -- Carousel script
            $Include = new include_resource();
            $this->Content->addScript($Include->js("jquery.jcarousel.min.js"));
        } else {
            $this->pageTitle = 'Not found';
        }
        $this->set('property', $row);
        $this->set('city_advance_search', $this->listCities());
        $this->set('unit_types', $this->listUnit_types());
    }	

    /**
     * Property/Apartment details page
     *
     * @param <type> $slug
     */
    function dashboard_details($slug = '')
    {
        $this->view = 'details';
        $where = "properties.slug = '{$this->Database->escape($slug)}'";
        $where .= " AND properties.user_id = '$this->loggedInUserId'";
        $result = $this->Database->assoc(array('pr_unit_info', 'pr_gallery', 'pr_contact_info', 'properties'), array(array('properties.pr_id'), array('properties.pr_id'), array('properties.pr_id')), $where, '', "properties.id");
        $row = $this->Database->mysqlfetchassoc($result);
        if ($row) {
            $this->pageTitle = $row[0]['properties.title'];
            $this->metaDescription = $row[0]['properties.description'];
            // Other listing -- Carousel script
            $Include = new include_resource();
            $this->Content->addScript($Include->js("jquery.jcarousel.min.js"));
        } else {
            $this->pageTitle = 'Not found';
        }
        $this->set('property', $row);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Fetch other properties listing except $propertyid
     *
     * @param <type> $propertyid
     */
    function getOtherListing($propertyid = 0)
    {
        $totalPages = 0;
        $this->element = 'properties/other_listing_carousel';
        $where = "properties.status = " . PropertyStatusConsts::PUBLISH;
        $where .= " AND properties.id != '{$this->Database->escape($propertyid)}'";
        $where .= " AND prGallery.file REGEXP '[^\.flv]$'";
        $result = $this->Database->assoc(array('properties', 'pr_unit_info', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, 'properties.modified DESC', "properties.id", "", '', '');
        $list = $this->Database->mysqlfetchassoc($result);
        $this->set('list', $list);
        if ($list)
            $totalPages = ceil(count($list) / 7);
        $this->set('totalPages', $totalPages);
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * Send property link to a friend
     *
     * @param <type> $slug
     */
    function sendToFriend()
    {
        $this->element = 'properties/sendto_friend';
        if (trim($this->Request->request['slug']) != '') {
            if ($this->_validateSendToFriendForm()) {
                $form = new form();
                $link = $form->link(WWW_ROOT . '/properties/details/' . $this->Request->request['slug'], array('class' => 'properties', 'method' => 'details', $this->Request->request['slug']), array('title' => 'Click here to view link details'));
                $from = SITE_EMAIL;
                //$fromname = SITE_NAME;
                $fromname = $this->Request->request['first_name'] . ' ' . $this->Request->request['last_name'];
                $fromfirstname = $this->Request->request['first_name'] . ' ' . $this->Request->request['last_name'];
                $to = array($this->Request->request['email']);
                $url = array('class' => 'properties', 'method' => 'sendToFriendEmail');
                $subject = 'Your Friend sent you a link';
                $message = 'Contact user information:<br/>' . $link;
                if (trim($this->Request->request['message']) != '')
                    $message .= '<br/><br/><i>' . $this->Request->request['message'] . '</i>';
                email($from, $fromname, $to, $subject, '', '', '', $message);
                $this->Session->setFlash("<div class='flash-success'>Email has been sent successfully.</div>");
                redirect(array('class' => 'properties', 'method' => 'details', $this->Request->request['slug']));
            }
        } else {
            $this->Session->setFlash("<div class='flash-error'>Oops! Invalid Action</div>");
        }
    }

    /**
     * Add to my favorites property listing
     *
     * @param <type> $propId
     */
    function addToFavorite($prId = 0)
    {
        $response = array();
        if ($this->loggedInUserId) {
            $where = "user_id = '$this->loggedInUserId'";
            $where .= " AND pr_id = '$prId'";
            $result = $this->Database->mysqlSelect(array('id'), 'user_property_favorites', $where);
            $rows = $this->Database->mysqlnumrows($result);
            if ($rows) {
                $response['status'] = 'already_favorite';
            } else {
                $this->Database->mysqlInsert('user_property_favorites', array('pr_id', 'user_id', 'created'), array($prId, $this->loggedInUserId, date('Y-m-d H:i:s')));
                $id = $this->Database->lastInsertId();
                $where = "id = '$id'";
                $this->Database->mysqlUpdate('user_property_favorites', array('order_bit'), array($id), $where);
                $response['status'] = 'success';
            }
        } else {
            $response['status'] = 'unauthenticated';
        }
        echo json_encode($response);
        exit;
    }

    /**
     * show all the favorite property listings of the logged in user
     */
    function dashboard_favorites($page = 1, $order_by = 'order_bit', $order = 'DESC')
    {
        $order_by = 'userPropertyFavorites.' . $order_by;
        $this->pageTitle = 'My favorites';
        // Favorites listing
        $where = 'properties.status = ' . PropertyStatusConsts::PUBLISH;
        $this->_propertiesListing($page, $order_by, $order, $where, true);
        $this->view = 'dashboard_index';
        $this->set('city_advance_search', $this->listCities());
		$this->set('apartment_building_features', $this->listBuildingFeatures());
		$this->set('lease', $this->listLease());
		$this->set('apartment_unit_features', $this->listUnitFeatures());
        $this->set('unit_types', $this->listUnit_types());
    }

    /**
     * List all the saved search links
     */
    function dashboard_seachLinks()
    {
        $this->pageTitle = 'My saved search links';
        $this->set('rightSidebar', 'properties/dashboard_sidebar');
        $where = "user_id = '$this->loggedInUserId'";
        $result = $this->Database->mysqlSelect(array('*'), 'user_property_searchlinks', $where, 'id DESC', '', '', '');
        $rows = $this->Database->mysqlfetchobjects($result);
        $this->set('list', $rows);
    }

    /**
     * Delete a saved search link
     *
     * @param <type> $id
     * @access  Landlord
     */
    function dashboard_deleteSearchLink($id = 0)
    {
        $where = "id = '{$this->Database->escape($id)}'";
        $where .= " AND user_id = '$this->loggedInUserId'";
        $this->Database->mysqlDelete('user_property_searchlinks', $where);

        echo json_encode(array('<div class="flash-sold">Link has been deleted successfully.</div>'));
        exit;
    }

    /**
     * Save search link
     */
    public function saveSearch()
    {
        if ($this->loggedInUserId) {
            $this->element = 'properties/save_search_popup';
            if ($this->Request->request['save']) {
                $this->Database->mysqlInsert('user_property_searchlinks', array('link', 'title', 'user_id', 'created'), array($this->Request->request['link'], $this->Request->request['title'], $this->loggedInUserId, date('Y-m-d H:i:s')));
                $this->Session->setFlash('Search link has been saved successfully.');
            }
        } else {
            redirect(array('class' => 'users', 'method' => 'login'));
        }
    }

    /**
     * show all the favorite property listings of the logged in member/renter
     */
    function member_favorites($page = 1, $order_by = 'order_bit', $order = 'DESC')
    {
        $order_by = $order_by;
        $this->pageTitle = 'My favorites';
        // Favorites listing
        $where = 'properties.status = ' . PropertyStatusConsts::PUBLISH;
        $this->_propertiesListing($page, $order_by, $order, $where, true);
        $this->set('rightSidebar', 'properties/member_sidebar');
    }

    /**
     * Remove a property from your favorites list
     *
     * @param <type> $favId
     */
    function member_unfavorite($favId = 0)
    {
        $this->dashboard_unfavorite($favId);
    }

    /**
     * List all the saved search links
     */
    function member_seachLinks()
    {
        $this->dashboard_seachLinks();
        $this->set('rightSidebar', 'properties/member_sidebar');
        $this->view = 'dashboard_seachLinks';
    }

    /**
     * Delete a saved search link
     *
     * @param <type> $id
     * @access  Member/Renter
     */
    function member_deleteSearchLink($id = 0)
    {
        $where = "id = '{$this->Database->escape($id)}'";
        $where .= " AND user_id = '$this->loggedInUserId'";
        $this->Database->mysqlDelete('user_property_searchlinks', $where);

        echo json_encode(array('<div class="flash-sold">Link has been deleted successfully.</div>'));
        exit;
    }

    /**
     * Change favorite list order
     *
     * @param <type> $favId
     */
    function member_changeOrder($favId = 0, $page = 1, $order_by = 'order_bit', $order = 'DESC')
    {
        $favId = $this->Database->escape($favId);
        $where = "user_id = '$this->loggedInUserId'";
        $where1 = " AND id = '$favId'";
        $result = $this->Database->mysqlSelect(array('id', 'order_bit'), 'user_property_favorites', $where . $where1, $order_by . ' ' . $order, '', '', 1);
        $row = $this->Database->mysqlfetchobject($result);
        $order1 = $row->order_bit;
        if ($this->Request->request['move'] == 'up')
            $where1 = " AND order_bit > '$order1'";
        else
            $where1 = " AND order_bit < '$order1'";
        $result = $this->Database->mysqlSelect(array('id', 'order_bit'), 'user_property_favorites', $where . $where1, $order_by . ' ' . $order, '', '', 1);
        $row = $this->Database->mysqlfetchobject($result);
        if ($row) {
            $order2 = $row->order_bit;
            $where1 = " AND id = '$favId'";
            $this->Database->mysqlUpdate('user_property_favorites', array('order_bit'), array($order2), $where . $where1);
            $where1 = " AND id = '$row->id'";
            $this->Database->mysqlUpdate('user_property_favorites', array('order_bit'), array($order1), $where . $where1);
        }

        redirect(array('class' => 'properties', 'method' => 'member_favorites', $page, $order_by, $order));
    }

    /**
     * View message details
     *
     * @param <type> $messageId
     */
    function member_message($messageId = 0, $propertyId = 0, $updateStatus = true, $is_private = 0)
    {
        $this->dashboard_message($messageId, $propertyId, $updateStatus, $is_private);
        $this->view = 'dashboard_message';
    }

    /**
     * Show properties geolocation map
     *
     * @param <type> $data
     * @param <type> $width
     * @param <type> $height
     * @param <type> $zoom
     * @param <type> $tooltip
     */
    function showMap($data = array(), $width = 646, $height = 390, $zoom = 8, $tooltip = true, $controls = true)
    {
        $this->element = 'properties/map';
        if (!is_array($data))
            $data = array(array('loc' => $data));
        $this->set('data', $data);
        $this->set('width', $width);
        $this->set('height', $height);
        $this->set('zoom', $zoom);
        $this->set('tooltip', $tooltip);
        $this->set('controls', $controls);
    }

    /**
     * Fetch unit features/amenities of property
     *
     * @param   $propertyId
     */
    function getUnitFeatures($propertyId = 0)
    {
		$where = ' propertyFeatures.type = 1';
		if ($propertyId) {
			$where .= " AND prFeatures.pr_id = '{$this->Database->escape($propertyId)}'";
			$result = $this->Database->assoc(array('pr_features', 'right property_features'), array(array('propertyFeatures.pr_feature_id')), $where, '', null, null, null);
		} else
			$result = $this->Database->assoc(array('property_features'), array(), $where, '', null, null, null);
		$rows = $this->Database->mysqlfetchassoc($result);
		$this->set('features', $rows);
		$this->set('fieldName', 'pr_feature_id');
    }

    /**
     * Fetch property features/amenities
     *
     * @param   $propertyId
     */
    function getPropertyFeatures($propertyId = 0)
    {
        $where = ' propertyFeatures.type = 2';
        if ($propertyId) {
            $where .= " AND prFeatures.pr_id = '{$this->Database->escape($propertyId)}'";
            $result = $this->Database->assoc(array('pr_features', 'right property_features'), array(array('propertyFeatures.pr_feature_id')), $where, '', null, null, null);
        } else
            $result = $this->Database->assoc(array('property_features'), array(), $where, '', null, null, null);
        $rows = $this->Database->mysqlfetchassoc($result);

        $this->set('features', $rows);
        $this->set('fieldName', 'pr_feature_id');
    }

    function getlistCities()
    {
        $result = $this->Database->mysqlSelect(array('name'), 'city', 'parent_id = 0');
        $rows = $this->Database->mysqlfetchobjects($result);
        $this->set('cities', $rows);
        //return $rows;
    }

    /**
     * Fetch unit extra features/amenities of property
     *
     * @param   $propertyId
     */
    function getUnitExtraFeatures($propertyId = 0)
    {
        $where = '';
        if ($propertyId) {
            $where = "pr_id = '{$this->Database->escape($propertyId)}'";
        }
        $result = $this->Database->mysqlSelect(array('pr_unit_feature AS pr_feature'), 'pr_unit_extra_features', $where, 'pr_feature ASC', '', '', '');
        $rows = $this->Database->mysqlfetchassoc($result);
        $this->set('features', $rows);
    }

    /**
     * Fetch lease extra features/amenities of property
     *
     * @param   $propertyId
     */
    function getLeaseExtraFeatures($propertyId = 0)
    {
        $where = '';
        if ($propertyId) {
            $where = "id = '{$this->Database->escape($propertyId)}'";
        }
        $result = $this->Database->mysqlSelect(array('lease_length', 'deposit_amount', 'application_fee', 'move_in_date', 'other1', 'other2'), 'properties', $where, '', '', '', '');
        $row = $this->Database->mysqlfetchobject($result);
        $this->set('features', $row);
    }

    /**
     * Fetch property extra features/amenities
     *
     * @param   $propertyId
     */
    function getPropertyExtraFeatures($propertyId = 0)
    {
        $where = '';
        if ($propertyId) {
            $where = "pr_id = '{$this->Database->escape($propertyId)}'";
        }
        $result = $this->Database->mysqlSelect(array('pr_feature'), 'pr_extra_features', $where, 'pr_feature ASC', '', '', '');
        $rows = $this->Database->mysqlfetchassoc($result);
        $this->set('features', $rows);
    }

    /**
     * Fetch the recent added properties list
     *
     * @param <type> $limit
     */
    function getLatestPropertiesList($limit = 9)
    {
        $where = 'properties.status = ' . PropertyStatusConsts::PUBLISH;
        $result = $this->Database->assoc(array('properties', 'pr_unit_info', 'left pr_gallery'), array(array(), array('properties.pr_id'), array('properties.pr_id')), $where, 'properties.id DESC', "properties.id", '', $limit);
        $rows = $this->Database->mysqlfetchassoc($result);
        $this->set('list', $rows);
    }
	
	function ajaxcitylist($city=0)
	{
	$a=$city;
	$result = mysql_query("SELECT id FROM city WHERE name='{$a}'");
	while($data=mysql_fetch_object($result)) {
        $rows[]=$data;
        }
	$a=$rows[0]->id;
	$result = mysql_query("SELECT name FROM city WHERE parent_id='{$a}'");
	while($data=mysql_fetch_object($result)) {
                $rows[]=$data;
            }
	$this->set('rows', $rows);
	}

    private function _validateSendToFriendForm()
    {
        if (trim($this->Request->request['first_name'] == '')) {
            $this->errors['first_name'] = 'First name can not be empty.';
        }

        if (trim($this->Request->request['email'] == '')) {
            $this->errors['email'] = 'Email can not be empty.';
        } else {
            if (!validEmailAddress(trim($this->Request->request['email']))) {
                $this->errors['email'] = 'Email is not valid.';
            }
        }

        if (empty($this->errors))
            return true;

        return false;
    }
	
 private function _validateFlagListingForm()
    {
        if (trim($this->Request->request['flag_desc'] == '')) {
            $this->errors['flag_desc'] = 'Reason Can Not Be Left Blank.';
        }

         if (empty($this->errors))
            return true;

        return false;
    }

    private function _validateNewPropertyForm($new = true)
    {
        if (trim($this->Request->request['phone1']) == 'xxx')
            $this->Request->request['phone1'] = '';
        if (trim($this->Request->request['phone2']) == 'xxx')
            $this->Request->request['phone2'] = '';
        if (trim($this->Request->request['phone3']) == 'xxxx')
            $this->Request->request['phone3'] = '';
        if ($new && !isset($this->Request->request['agree']))
            $this->errors['agree'] = 'You must agree to our terms and conditions before publishing the property';
        if ($this->errors)
            return false;

        return true;
    }
}

?>