<?php
namespace Inc;

use Slim\Slim;
use \Model\Comment;
use \Model\FileUpload;
use \Model\Property;

class Helper extends \Twig_Extension {
    public $Constants;

    public function __construct() {
        $this->app = Slim::getInstance('default');
        $this->config = include 'config.php';
    }

    public function getName() {
        return 'slim';
    }

    public function getFunctions() {
        return array(
            new \Twig_SimpleFunction('format_website_link', array($this, 'format_website_link')),
            new \Twig_SimpleFunction('url', array($this, 'url')),

            new \Twig_SimpleFunction('__', array($this, '__')),

            //Custom HTML functions for FA images, CSS/JS injection
            new \Twig_SimpleFunction('fa', array($this, 'fa'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('css', array($this, 'css'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('js', array($this, 'js'), ['is_safe' => ['html']]),

            //Pagination
            new \Twig_SimpleFunction('paginate', array($this, 'paginate')),
            new \Twig_SimpleFunction('paginate_results_count', array($this, 'paginate_results_count')),

            //Users
            new \Twig_SimpleFunction('get_user_fullname', array($this, 'get_user_fullname'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('get_user_setting', array($this, 'get_user_setting'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('get_user_setting_form', array($this, 'get_user_setting_form'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('is_admin', array($this, 'is_admin')),

            //New values from last week
            new \Twig_SimpleFunction('get_new_last_week', array($this, 'get_new_last_week')),

            new \Twig_SimpleFunction('strtoupper', array($this, 'strtoupper')),
            new \Twig_SimpleFunction('strtolower', array($this, 'strtolower')),
            new \Twig_SimpleFunction('isProductionServer', array($this, 'isProductionServer')),
            new \Twig_SimpleFunction('isDevelopmentServer', array($this, 'isDevelopmentServer')),
            new \Twig_SimpleFunction('get_max_filesize', array($this, 'get_max_filesize')),

            new \Twig_SimpleFunction('icon', array($this, 'icon'), ['is_safe' => ['html']]),

            //Flash messages
            new \Twig_SimpleFunction('flashSuccess', array($this, 'flashSuccess')),
            new \Twig_SimpleFunction('flashWarning', array($this, 'flashWarning')),
            new \Twig_SimpleFunction('flashNote', array($this, 'flashNote')),
            new \Twig_SimpleFunction('flashError', array($this, 'flashError')),
            new \Twig_SimpleFunction('flash', array($this, 'flash')),

            //Mysql log
            new \Twig_SimpleFunction('mysql_log', array($this, 'mysql_log')),

            //Orders
            new \Twig_SimpleFunction('get_order_status', array($this, 'get_order_status')),

            //Date and time
            new \Twig_SimpleFunction('get_date', array($this, 'get_date')),
            new \Twig_SimpleFunction('get_time', array($this, 'get_time')),
            new \Twig_SimpleFunction('get_datetime', array($this, 'get_datetime')),
            new \Twig_SimpleFunction('get_eventdatetime', array($this, 'get_eventdatetime'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('get_event_user_datetime', array($this, 'get_event_user_datetime'), ['is_safe' => ['html']]),

            //Property
            new \Twig_SimpleFunction('get_property_datatype', array($this, 'get_property_datatype')),
            new \Twig_SimpleFunction('property_is_fileupload', array($this, 'property_is_fileupload')),
            new \Twig_SimpleFunction('property_show_uploads', array($this, 'property_show_uploads'), ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('property_show_element_value', array($this, 'property_show_element_value'), ['is_safe' => ['html']]),

            //File size
            new \Twig_SimpleFunction('format_filesize', array($this, 'format_filesize'), ['is_safe' => ['html']]),
            
            //Forms
            new \Twig_SimpleFunction('form_error', array($this, 'form_error')),
            new \Twig_SimpleFunction('get_comments_type', array($this, 'get_comments_type')),

            //Site settings
            new \Twig_SimpleFunction('get_site_title', array($this, 'get_site_title')),
            //Elements
            new \Twig_SimpleFunction('format_element_property', array($this, 'format_element_property')),
            new \Twig_SimpleFunction('form_buttons', array($this, 'form_buttons')),
        );
    }

    /**
     * Gets date in human readable format
     *
     * @param  stdClass $patient: Patient instance
     * @param  boolean $today if set to true and date is today, "Today" will be returned, or "Yesterday" if date represents yesterday
     * @return string date in human readable format
     */
    public function get_date($datestring, $today = false) {
        if (!$datestring) {
            return '';
        }
        $date = (new \DateTime($datestring))->format('d.m.Y');
        if ($today) {
            $today = (new \DateTime('now'))->format('d.m.Y');
            $yesterday = (new \DateTime('yesterday'))->format('d.m.Y');

            if ($today == $date) {
                return __('Today');
            }
            if ($yesterday == $date) {
                return __('Yesterday');
            }
        }
        return $date;
    }

    public function get_time($timestring) {
        if (!$timestring) {
            return '';
        }
        return (new \DateTime($timestring))->format('H:i:s');
    }

    public function get_datetime($datetimestring) {
        if (!$datetimestring) {
            return '';
        }
        return (new \DateTime($datetimestring))->format('d.m.Y' . ' ' . 'H:i:s');
    }

    //Returns event date time
    //Created at: 2 minutes ago, etc
    public function get_eventdatetime($datetimestring) {
        return $this->app->get_eventdatetime($datetimestring);
    }

    //Returns event date time and user name
    //Created at: 2 minutes ago, etc
    public function get_event_user_datetime($user, $date) {
        return $this->app->get_event_user_datetime($user, $date);
    }

    //Get setting for specific user
    public function get_user_setting($setting = null, $user = null) {
        return $this->app->get_user_setting($setting, $user);
    }

    //Shows form html for setting
    public function get_user_setting_form($setting = null, $user = null) {
        $value = $this->get_user_setting($setting, $user);

        //Create output
        $out =  '<input type="hidden" name="settings[' . $setting . ']" value="0" />';
        $out .= '<input type="checkbox" name="settings[' . $setting . ']" value="1" class="form-control"' . ($value == '1' ? ' checked="checked"' : '') . ' />';

        return $out;
    }

    /**
     * Gets day name for specifiec date.
     * If dayname is today, "Today" is returned, or if "Yesterday", "Yesterday is returned"
     *
     *
     */
    public function get_date_dayname($datestring) {
        $date = (new \DateTime($datestring));
        $today = (new \DateTime('now'))->format('d.m.Y');
        $yesterday = (new \DateTime('yesterday'))->format('d.m.Y');

        //Go to string with date
        $dstr = $date->format('d.m.Y');
        if ($today == $dstr) {
            return __('Today');
        }
        if ($yesterday == $dstr) {
            return __('Yesterday');
        }

        return __($date->format('l'));
    }

    /**
     * Translates given text
     * 
     * @param  string $text: String to translate
     * @return string translated text if exists or original if there is no available translation
     */
    public function __($text) {
        return __($text);
    }

    /**
     * Checks if current user is nurse
     *
     * @param  stdClass $doctor: Doctor/Nurse to check. If empty, current user will be used
     * @return bool status if it is nurse
     */
    public function is_nurse($doctor = false) {
        if (!$doctor) {
            $doctor = $this->app->user;
        }

        if ($doctor->UserRole == $this->Constants->ROLE_NURSE) {
            return true;
        }
        return false;
    }

    /**
     * Checks if current user is doctor
     *
     * @param  stdClass $doctor: Doctor/Nurse to check. If empty, current user will be used
     * @return bool status if it is doctor
     */
    public function is_doctor($doctor = false) {
        if (!$doctor) {
            $doctor = Slim::getInstance('default')->user;
        }

        if ($doctor->UserRole == $this->Constants->ROLE_DOCTOR) {
            return true;
        }
        return false;
    }


    /**
     * Formats link from string to anchor link 
     *
     * @param  string $link: Link to format
     * @param  array $parameters: List of key => value pairs for "a" tag html attributes
     * @return string Formatted anchor link
     */
    public function format_website_link($link, $parameters = array()) {
        $link = str_replace(array('http://', 'https://'), '', $link);

        $out = '<a href="http://' . $link . '"';
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $out .= ' ' . $key . '="' . $value . '"';
            }
        }
        $out .= '>' . $link . '</a>';

        return $out;
    }

    /**
     * Paginate function for view
     *
     * This function calculates how many page links should show and outputs it as HTML RAW
     */
    public function paginate() {
        if (!isset($this->app->Paginate)) {
            return;
        }

        $pages = $this->app->Constants->PAGINATE_NUM_OF_PAGES;
        $paginate = $this->app->Paginate;

        $number_of_pages = ceil($paginate['Total'] / $paginate['PageSize']);
        if (!$number_of_pages) {
            return;
        }

        $current_page = $paginate['PageNumber'];
        if ($current_page > $number_of_pages) {
            $current_page = 1;
        }

        //Start at starting point
        $left = $right = $current_page;
        $leftOut = $rightOut = 0;

        while (1) {
            if ($right < $number_of_pages) {
                $right++;
                $rightOut++;
            }

            if ($left > 1) {
                $left--;
                $leftOut++;
            }

            if (
                ($leftOut + $rightOut) >= $pages || 
                ($left == 1 && $right == $number_of_pages)
            ) {
                break;
            }
        }

        //Page 1 is always active
        $numbers = [1];

        //If number '2' is not in array, add '...'
        if ($left > 2) {
            $numbers[] = '...';
        }
        for ($i = $left; $i <= $right; $i++) {
            if ($i != 1) {
                $numbers[] = $i;
            }
        }
        //If number before last number is not in array, add '...'
        if (!in_array($number_of_pages - 1, $numbers) && $number_of_pages > 1) {
            $numbers[] = '...';
        }
        //Add last page if not already
        if (!in_array($number_of_pages, $numbers)) {
            $numbers[] = $number_of_pages;
        }

        $out = '';
        $last = $numbers[0];

        //Get query of all items in $_GET for pagination
        $outl = $this->__getPaginateHTTPGet();

        //Format numbers
        if (!empty($numbers)) {
            $out .= '<ul class="pagination pagination_main">';
        
            foreach ($numbers as $i) {
                $text = $i;
                /*
                if ($i == 1) {
                    $text = __('First page');
                } else if ($i == $number_of_pages) {
                    $text = __('Last page');
                }
                */
                if ($i == $current_page) {
                    $out .= '<li class="active"><a href="#">' . $text . '</a></li>';
                } else if ( $i == '...') {
                    $out .= '<li class="disabled"><a href="#">' . $text . '</a></li>';
                } else {
                    $out .= '<li><a href="' . $outl . 'page=' . $i . '">' . $text . '</a></li>';
                }
            }
        }

        if (!empty($out)) {
            $out .= '</ul>';
        }

        //Add loading element at the end
        $out .= '<div class="pagination_loading" style="display: none;">' . __('Loading, please wait..') . '</div>';
 
        return $out;
    }

    //Shows text with number of paginate items
    public function paginate_results_count() {
        if (!isset($this->app->Paginate)) {
            return;
        }

        //Get start value
        $start = $this->app->Paginate['PageSize'] * ($this->app->Paginate['PageNumber'] - 1) + 1;
        //Get end value
        $end = $this->app->Paginate['PageSize'] * ($this->app->Paginate['PageNumber']);

        //Check if last page is not full
        if ($end > $this->app->Paginate['Total']) {
            $end = $this->app->Paginate['Total'];
        }

        //Format text
        $out = '<span>' . sprintf(__('%d-%d of %d'), $start, $end, $this->app->Paginate['Total']) . '</span>';

        //Get number of pages
        if ($this->app->Paginate['Total'] > $this->app->Paginate['PageSize']) {
            //We have enough elements to scroll between

            //Get base link
            $baselink = $this->__getPaginateHTTPGet();

            //Format output
            $out .= '<ul class="pagination paginate_results_prev_next">';

            //Check if we can go to lower page
            if ($this->app->Paginate['PageNumber'] > 1) {
                $out .= '<li><a class="paginate_prev_next" href="' . $baselink . 'page=' . ($this->app->Paginate['PageNumber'] - 1) . '">' . __('<') . '</a></li>'; 
            }

            //Check if we can go to upper page
            if ($this->app->Paginate['PageNumber'] < $this->app->Paginate['PagesNumber']) {
                $out .= '<li><a class="paginate_prev_next" href="' . $baselink . 'page=' . ($this->app->Paginate['PageNumber'] + 1) . '">' . __('>') . '</a></li>'; 
            }

            $out .= '</ul>';
        }

        return $out;
    }

    //Returns string in uppercase
    public function strtoupper($str) {
        return strtoupper($str);
    }

    //Returns string in lowercase
    public function strtolower($str) {
        return strtolower($str);
    }

    //Returns true if web is hosted on production server
    public function isProductionServer() {
        return $this->app->isProductionServer();
    }

    //Returns true if web is hosted on development server
    public function isDevelopmentServer() {
        return $this->app->isDevelopmentServer();
    }

    //Get max file size for upload
    public function get_max_filesize() {
        return ini_get('upload_max_filesize');
    }

    //Displays flash success message
    public function flashSuccess() {
        return $this->flash('success');
    }

    //Displays flash success message
    public function flashWarning() {
        return $this->flash('warning');
    }

    //Displays flash note message
    public function flashNote() {
        return $this->flash('note');
    }

    //Displays flash error message
    public function flashError() {
        return $this->flash('error');
    }

    //Displays flash all messages
    public function flash($type = 'all') {
        if ($type == 'all') {
            $types = ['success', 'warning', 'info', 'danger'];
        } else {
            $types = [$type];
        }

        //Get "now" data from environment
        $flashData = $this->app->environment['slim.flash']->getMessages();

        $out = '';
        foreach ($types as $type) {
            if (is_array($flashData) && isset($flashData[$type])) {
                $flashText = $flashData[$type];
            } else if (isset($_SESSION['slim.flash'][$type])) {
                $flashText = $_SESSION['slim.flash'][$type];
            }

            if (isset($flashText)) {
                $out .= '<div class="storage-alert alert alert-' . $type . '" role="alert">';
                $fa = '';
                switch ($type) {
                    case 'success': 
                        $fa = 'check'; 
                        break;
                    case 'danger':
                    case 'warning': 
                        $fa = 'exclamation-triangle'; 
                        break;
                    case 'info': 
                        $fa = 'info';
                    break;
                }
                $out .= $this->fa($fa) . ' ' . $flashText;
                $out .= '<div class="pull-right"><span class="close">' . $this->fa('close') . '</span></div>';
                $out .= '</div>';

                unset($_SESSION['slim.flash'][$type]);
                unset($flashText);
            }
        }

        return $out;
    }

    //Get new from last week
    public function get_new_last_week($obj, $field, $fieldNew, $fieldOld = '', $returnNumber = false) {
        $cnt = $obj->{$field};
        $fieldNew = $obj->{$fieldNew};
        if ($fieldOld != '' && isset($obj->{$fieldOld})) {
            $fieldOld = $obj->{$fieldOld};
        } else {
            $fieldOld = 0;
        }

        //Difference in 1 week
        $weekDiff = $fieldNew - $fieldOld;

        //Get difference
        $diff = $cnt - $weekDiff;

        //Get percentage
        $percent = 0;
        if ($cnt != 0) {
            $percent = $diff == 0 ? 100 : number_format($weekDiff * 100 / $diff, 1);
        } else if ($weekDiff < 0) {
            $percent = -100;
        }

        //Output values
        if ($percent > 0) {
            $out = '<i class="green">' . $this->fa('angle-up') . $percent . '%</i>';
        } else if ($percent < 0) {
            $out = '<i class="red">' . $this->fa('angle-down') . $percent . '%</i>';
        } else {
            $out = $percent . '%';
        }
        if ($returnNumber) {
            return $out;
        }
        return sprintf(__('%s from last week'), $out);
    }

    //Return true if admin
    public function is_admin() {
        return $this->app->isAdmin();
    }

    //Order status
    public function get_order_status($order) {
        switch ($order['Elementorder']->status) {
            case '0': return __('Canceled');
            case '1': return __('Open');
            case '2': return __('Ordered');
            default: return __('Unknown');
        }
    }

    //Returns user full name
    public function get_user_fullname($user) {
        return $this->app->get_user_fullname($user);
    }

    //Data type format
    public function get_property_datatype($property) {
        switch ($property->data_type) {
            case 1: return __('String');
            case 2: return __('Number');
            case 3: return __('File upload');
            default: return __('Unknown');
        }
    }

    //Checks if property is file uploader
    public function property_is_fileupload($property) {
        return $property->data_type == Property::TYPE_FILEUPLOAD;
    }

    //Show uploads for property
    public function property_show_uploads($data, $hidden_id = '', $type = 'edit', $property = null) {
        if (empty($data)) {
            return '';
        }
        if (is_string($data)) {
            $data = explode(',', $data);
            foreach ($data as $k => $v) {
                if ($v == '') {
                    unset($data[$k]);
                }
            }
        }
        if (!is_array($data)) {
            return;
        }

        //Get files
        $files = FileUpload::getFiles($data);
        if (empty($files)) {
            return '';
        }

        $out = '';
        //Dropdown view
        if ($type == 'dropdown') {
            $out .= '<div class="dropdown">
                        <button id="" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="btn btn-primary btn-xs">
                            ' . $property->name . ' ' . $this->fa('files-o') . ' <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-files" aria-labelledby="">';
        }

        foreach ($files as $file) {
            if ($type != 'dropdown') {
                //Create output
                $out .= '<div class="uploaded-file" data-file-id="' . $file['FileUpload']->id . '">';
                
                //Name
                $out .= '   <div class="row">'; 
            }

            if ($type == 'edit') {
                $out .= '       <div class="col-md-3">' . __('File name') . '</div>';
                $out .= '       <div class="col-md-9">';
                $out .= '           <a href="' . $this->url('download_file', ['file' => $file['FileUpload']->id]) . '"><span class="bold">' . $file['FileUpload']->name . '</span></a>';
                $out .= '           <span class="uploaded-file-remove" data-hidden-id="' . $hidden_id . '" data-file-id="' . $file['FileUpload']->id . '">' . $this->fa('trash') . '</span>';
                $out .= '       </div>';
            } else if ($type == 'dropdown') {
                $out .= '<li>';
                $out .= '<a href="' . $this->url('download_file', ['file' => $file['FileUpload']->id]) . '"><span class="bold">' . $file['FileUpload']->name . '</span> <span class="filesize">(' . $this->format_filesize($file['FileUpload']->size) . ')</span></a>';
                $out .= '</li>';
            } else {
                $out .= '<div class="col-md-12">';
                $out .= '<a href="' . $this->url('download_file', ['file' => $file['FileUpload']->id]) . '"><span class="bold">' . $file['FileUpload']->name . ' (' . $this->format_filesize($file['FileUpload']->size) . ')</span></a>';
                $out .= '</div>';
            }

            //Close first row
            if ($type != 'dropdown') {
                $out .= '</div>';
            }

            //Size
            if ($type == 'edit') {
                $out .= '   <div class="row">';
                $out .= '       <div class="col-md-3">' . __('File size') . '</div>';
                $out .= '       <div class="col-md-9"><span class="bold">' . $this->format_filesize($file['FileUpload']->size) . '</span></div>';
                $out .= '   </div>';
            }

            //Close .uploaded-file
            if ($type != 'dropdown') {
                $out .= '</div>';
            }
        }

        //Dropdown view
        if ($type == 'dropdown'){
            $out .= '</ul>
                </div>';
        }

        return $out;
    }

    public function property_show_element_value($p) {
        if ($this->property_is_fileupload($p)) {
            return $this->property_show_uploads($p->ElementProperty->property_value, '', 'dropdown', $p);
        }

        //Parse URL if exists
        $regex = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
        if (preg_match($regex, $p->ElementProperty->property_value, $url)) {
            return  preg_replace($regex, '<a href="' . $url[0] . '" target="_new">' . __('Open URL') . '</a>', $p->ElementProperty->property_value);
        }
        return $p->ElementProperty->property_value;
    }

    //Formats file size
    public function format_filesize($bytes) { 
        $units = array('B', 'kB', 'MB', 'GB', 'TB'); 
        $bytes = max($bytes, 0);

        //Get units
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1);

        //Format bytes
        $bytes /= pow(1024, $pow);

        //Output formatted data
        return round($bytes) . ' ' . $units[$pow];
    }

    /**
     * Creates URL from named route
     *  
     * @param string $namedRoute: Name of route
     * @param array $params: Array of parameters for route
     * @param $useCurrentParams: set to true to use current named parameters for URL if there is no already set one
     */
    public function url($namedRoute, $params = array(), $useCurrentParams = true) {
        $routeParams = [];
        if ($useCurrentParams && !is_null($this->app->router()->getCurrentRoute())) {
            $routeParams = $this->app->router()->getCurrentRoute()->getParams();
        }
        $link = $this->app->urlFor($namedRoute, array_merge($routeParams, $params));

        return $link;
    }

    /**
     * Inserts beaufitul font icons
     * 
     * @param string $name: Font name
     * @param string $text: Text to be placed between element, default to null and not needed
     * @return Beaufitul font
     */
    public function fa($name, $text = null) {
        return $this->app->fa($name, $text);
    }


    public function css($files) {
        if (!is_array($files)) {
            $files = [$files];
        }

        //Get function arguments
        $args = func_get_args();
        array_shift($args);
        foreach ($args as $a) {
            $files = array_merge($files, (array)$a);
        }

        //Load files
        $ret = '';
        foreach ($files as $f) {
            $link = null;
            //Check for external
            if (strpos($f, 'http://') === false && stripos($f, 'https://') !== false) {
                $link = $f;
            } else {
                //Remove .css from the end if exists
                if (strtolower(substr($f, -4)) == '.css') {
                    $f = substr($f, 0, strlen($f) - 4);
                }

                //Check who has priority
                if ($this->isProductionServer()) {
                    $opts = [$f . '.min.css', $f . '.css'];
                } else {
                    $opts = [$f . '.css', $f . '.min.css'];
                }

                //Go throught options
                foreach ($opts as $o) {
                    //Check if file exists
                    if (file_exists($this->app->config['templates']['path'] . 'css' . DIRECTORY_SEPARATOR . $o)) {
                        $link = $this->app->config['slim']['template_prefix'] . 'css/' . $o;
                        break;
                    }
                }
            }
            //Add link if valid
            if ($link !== null) {
                $ret .= '<link rel="stylesheet" href="' . $link . '" type="text/css" property="stylesheet" />' . "\n";
            }
        }

        return $ret;
    }

    public function js($files) {
        if (!is_array($files)) {
            $files = [$files];
        }

        //Get function arguments
        $args = func_get_args();
        array_shift($args);
        foreach ($args as $a) {
            $files = array_merge($files, (array)$a);
        }

        //Load files
        $ret = '';
        foreach ($files as $f) {
            $link = null;
            //Check for external
            if (strpos($f, 'http://') === false && stripos($f, 'https://') !== false) {
                $link = $f;
            } else {
                //Remove .css from the end if exists
                if (strtolower(substr($f, -4)) == '.css') {
                    $f = substr($f, 0, strlen($f) - 4);
                }

                //Check who has priority
                if ($this->isProductionServer()) {
                    $opts = [$f . '.min.js', $f . '.js'];
                } else {
                    $opts = [$f . '.js', $f . '.min.js'];
                }

                //Go throught options
                foreach ($opts as $o) {
                    //Check if file exists
                    if (file_exists($this->app->config['templates']['path'] . 'js' . DIRECTORY_SEPARATOR . $o)) {
                        $link = $this->app->config['slim']['template_prefix'] . 'js/' . $o;
                        break;
                    }
                }
            }
            //Add link if valid
            if ($link !== null) {
                $ret .= '<script type="text/javascript" src="' . $link . '"></script>' . "\n";
            }
        }
        return $ret;
    }


    /**
     * Places icon image
     *
     * @param string $name: Icon name
     * @param array $options: HTML attributes for IMG tag
     * @return string image HTML tag
     */
    public function icon($name, $opts = []) {
        $filenames = [
            'icon_' . $name . '.svg',
            'icon_' . $name . '.png'
        ];
        $fullName = '';
        foreach ($filenames as $f) {
            $path = $this->app->config['templates']['path'] . 'img' . DS . $f;
            if (file_exists($path)) {
                $fullName = $f;
                break;
            }
        }
        $options = array_merge(
            ['alt' => ucfirst($name)],
            $opts,
            ['src' => $this->app->config['slim']['template_prefix'] . 'img/' . $fullName]
        );

        //Format parameters
        $p = '';
        foreach ($options as $k => $v) {
            $p .= ' ' . $k . '="' . $v . '"';
        }

        //Return image
        return '<img' . $p . ' />';
    }

    //Returns form error if exists for field
    public function form_error($fieldName) {
        $errors = $this->app->validationErrors;
        
        if (isset($errors[$fieldName])) {
            return '<span class="form-error">' . $errors[$fieldName][0] . '</span>';
        }

        return false;
    }

    //Outputs buttons for forms
    public function form_buttons($type = 'add') {
        $out = '<div class="row form-row form-action-row">
        <div class="col-md-12">';
        if ($type == 'edit') {
            $out .= '<button type="submit" name="apply" class="btn btn-success" data-loading-text="' . __('Applying..') . '">' . $this->fa('check') . ' ' . __('Apply') . '</button>';
        }
        $out .= '<button type="submit" name="submit" class="btn btn-success" data-loading-text="' . __('Saving..') . '">' . $this->fa('save') . ' ' . __('Save') . '</button>';
        if ($type == 'add') {
            $out .= '<button type="submit" name="saveandnew" class="btn btn-success" data-loading-text="' . __('Saving..') . '">' . $this->fa('save') . ' ' .  __('Save and new') . '</button>';
        }
        $out .= '</div>
            </div>';

        return $out;
    }

    //Outputs log of MYSQL calls
    public function mysql_log() {
        //In case of production server, do not display MYSQL log
        if (!$this->isDevelopmentServer()) {
            return false;
        }

        //Log all queries
        $ret = '<table class="table-mysql table table-hover table-condensed">';
        $ret .= '<thead><tr><th class="table_td_nr">#</th><th>Query</th><th>Num rows</th><th>File</th><th>Line</th><th>Class</th></tr></thead><tbody>';
        foreach ($this->app->db->queries_log as $key => $log) {
            $ret .= '<tr><td>' . ($key + 1) . '</td><td>' . $log['query'] . '</td><td>' . $log['num_rows'] . '</td><td>' . $log['file'] . '</td><td>' . $log['line'] . '</td><td>' . $log['class'] . '</td></tr>';
        }
        $ret .= '</tbody></table>';

        return $ret;
    }

    //Returns number for model name
    public function get_comments_type($modelName) {
        return $this->app->get_comments_type($modelName);
    }

    //Returns site title
    public function get_site_title() {
        return $this->app->config['site']['title'];
    }

    //Gets string from already formatted elemnts from get method except 'page' is removed
    private function __getPaginateHTTPGet() {
        $outl = '?';
        if (!empty($_GET)) {
            if (isset($_GET['page'])) {
                unset($_GET['page']);
            }
            $outl .= http_build_query($_GET);
            if (strlen($outl) > 1) {
                $outl .= '&';
            }
        }

        return $outl;
    }
}
