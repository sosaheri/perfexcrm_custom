<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Html purify the given HTML
 *
 * @param  string $content
 *
 * @return string
 */
function html_purify($content)
{
    if (hooks()->apply_filters('html_purify_content', true) === false) {
        return $content;
    }

    $CI = &get_instance();
    $CI->load->config('migration');

    $config = HTMLPurifier_HTML5Config::create(
        HTMLPurifier_HTML5Config::createDefault()
    );

    $config->set('HTML.DefinitionID', 'CustomHTML5');
    $config->set('HTML.DefinitionRev', $CI->config->item('migration_version'));

    // Disables cache
    if(ENVIRONMENT !== 'production'){
        $config->set('Cache.DefinitionImpl', null);
    }

    $config->set('HTML.SafeIframe', true);
    $config->set('Attr.AllowedFrameTargets', ['_blank']);
    $config->set('Core.EscapeNonASCIICharacters', true);
    $config->set('CSS.AllowTricky', true);

    // These config option disables the pixel checks and allows
    // specifiy e.q. widht="auto" or height="auto" for example on images
    $config->set('HTML.MaxImgLength', null);
    $config->set('CSS.MaxImgLength', null);

    //allow YouTube and Vimeo
    $regex = hooks()->apply_filters('html_purify_safe_iframe_regexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|player\.vimeo\.com/video/)%');

    $config->set('URI.SafeIframeRegexp', $regex);
    hooks()->apply_filters('html_purifier_config', $config);

    $def = $config->maybeGetRawHTMLDefinition();

    if ($def) {
        $def->addAttribute('p', 'pagebreak', 'Text');
        $def->addAttribute('div', 'align', 'Enum#left,right,center');
        $def->addAttribute('span', 'data-mention-id', 'Number');
        $def->addElement(
            'iframe',
            'Inline',
            'Flow',
            'Common',
            [
                'src'                   => 'URI#embedded',
                'width'                 => 'Length',
                'height'                => 'Length',
                'name'                  => 'ID',
                'scrolling'             => 'Enum#yes,no,auto',
                'frameborder'           => 'Enum#0,1',
                'allow'                 => 'Text',
                'allowfullscreen'       => 'Bool',
                'webkitallowfullscreen' => 'Bool',
                'mozallowfullscreen'    => 'Bool',
                'longdesc'              => 'URI',
                'marginheight'          => 'Pixels',
                'marginwidth'           => 'Pixels',
            ]
        );
    }

    $purifier = new HTMLPurifier($config);

    return $purifier->purify($content);
}

/**
 * Remove <br /> html tags from string to show in textarea with new linke
 * @param  string $text
 * @param  string $replace character to replace with
 * @return string formatted text
 */
function clear_textarea_breaks($text, $replace = '')
{
    $breaks = [
        '<br />',
        '<br>',
        '<br/>',
    ];

    $text = str_ireplace($breaks, $replace, $text);
    $text = trim($text);

    return $text;
}

/**
 * Equivalent function to nl2br php function but keeps the html if found and do not ruin the formatting
 * @param  string $string
 * @return string
 */
function nl2br_save_html($string)
{
    if (! preg_match('#</.*>#', $string)) { // avoid looping if no tags in the string.
        return nl2br($string);
    }

    $string = str_replace(["\r\n", "\r", "\n"], "\n", $string);

    $lines  = explode("\n", $string);
    $output = '';
    foreach ($lines as $line) {
        $line = rtrim($line);
        if (! preg_match('#</?[^/<>]*>$#', $line)) { // See if the line finished with has an html opening or closing tag
            $line .= '<br />';
        }
        $output .= $line . "\n";
    }

    return $output;
}

/**
 * Check for alerts
 * @return null
 */
function app_js_alerts()
{
    $CI         = &get_instance();
    $alertclass = get_alert_class();

    // Available only for admin area
    if ($CI->session->has_userdata('system-popup')) {
        echo '<script>';
        echo '$(function(){
            if(typeof("system_popup") != undefined) {
                var popupData = {};
                popupData.message = ' . json_encode(app_happy_text($CI->session->userdata('system-popup'))) . ';
                system_popup(popupData);
            }
        });';
        echo '</script>';
    }

    if ($alertclass == '') {
        return;
    }

    $alert_message = '';
    $alert         = $CI->session->flashdata('message-' . $alertclass);
    if (is_array($alert)) {
        foreach ($alert as $alert_data) {
            $alert_message .= '<span>' . $alert_data . '</span><br />';
        }
    } else {
        $alert_message .= $alert;
    }
    echo '<script>';
    echo '$(function(){
            alert_float("' . $alertclass . '","' . $alert_message . '");
        });';
    echo '</script>';
}

/**
 * External form common footer, eq. leads form, tickets form
 * @param  mixed $form form from database
 * @return mixed
 */
function app_external_form_footer($form)
{
    $date_format = get_option('dateformat');

    $date_format = explode('|', $date_format);

    $date_format = $date_format[0];

    $locale_key = get_locale_key($form->language);

    $assetsGroup = 'external-form';

    $CI = &get_instance();

    $CI->app_scripts->add('jquery-js', 'assets/plugins/jquery/jquery.min.js', $assetsGroup);

    $CI->app_scripts->add('bootstrap-js', 'assets/plugins/bootstrap/js/bootstrap.min.js', $assetsGroup);

    add_jquery_validation_js_assets($assetsGroup);

    add_moment_js_assets($assetsGroup);

    add_bootstrap_select_js_assets($assetsGroup);

    $CI->app_scripts->add('datetimepicker-js', 'assets/plugins/datetimepicker/jquery.datetimepicker.full.min.js', $assetsGroup);

    $CI->app_scripts->add('colorpicker-js', 'assets/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js', $assetsGroup);

    $CI->app_scripts->add('common-js', 'assets/builds/common.js', $assetsGroup); ?>

    <script>
       var app = {};
       app.options = {};
       app.lang = {};
       app.options.date_format = '<?php echo $date_format; ?>';
       app.options.time_format = '<?php echo get_option('time_format'); ?>';
       app.options.calendar_first_day = '<?php echo get_option('calendar_first_day '); ?>';
       app.lang.file_exceeds_max_filesize = "<?php echo _l('ticket_form_validation_file_size', bytesToSize('', file_upload_max_size())); ?>";
       app.lang.validation_extension_not_allowed = "<?php echo _l('validation_extension_not_allowed'); ?>";
   </script>

   <?php echo app_compile_scripts($assetsGroup); ?>

   <script>
    $(function(){

        $('body').tooltip({
             selector: '[data-toggle="tooltip"]'
        });

        appColorPicker();
        appDatepicker();
        appSelectPicker($('select'));
    });
</script>
<?php
}

/**
 * External forms common header, eq ticket form, lead form
 * @param  mixed $form form from database
 * @return mixed
 */
function app_external_form_header($form)
{
    $CI          = &get_instance();
    $assetsGroup = 'external-form';

    add_favicon_link_asset($assetsGroup);

    $CI->app_css->add('reset-css', 'assets/css/reset.min.css', $assetsGroup);

    $CI->app_css->add('roboto-css', 'assets/plugins/roboto/roboto.css', $assetsGroup);
    $CI->app_css->add('bootstrap-css', 'assets/plugins/bootstrap/css/bootstrap.min.css', $assetsGroup);

    if (is_rtl()) {
        $CI->app_css->add('bootstrap-rtl-css', 'assets/plugins/bootstrap-arabic/css/bootstrap-arabic.min.css', $assetsGroup);
    }

    $CI->app_css->add('datetimepicker-css', 'assets/plugins/datetimepicker/jquery.datetimepicker.min.css', $assetsGroup);
    $CI->app_css->add('colorpicker-css', 'assets/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css', $assetsGroup);
    $CI->app_css->add('fontawesome-css', 'assets/plugins/font-awesome/css/font-awesome.min.css', $assetsGroup);
    $CI->app_css->add('bootstrap-select-css', 'assets/plugins/bootstrap-select/css/bootstrap-select.min.css', $assetsGroup);
    $CI->app_css->add('forms-css', base_url($CI->app_css->core_file('assets/css', 'forms.css')) . '?v=' . $CI->app_css->core_version(), $assetsGroup);

    if (file_exists(FCPATH . 'assets/css/custom.css')) {
        $CI->app_css->add('custom-css', base_url('assets/css/custom.css'), $assetsGroup);
    }

    echo app_compile_css($assetsGroup);

    if (show_recaptcha() && $form->recaptcha == 1) {
        echo "<script src='https://www.google.com/recaptcha/api.js'></script>" . PHP_EOL;
    } ?>
    <script>
        var cfh_popover_templates = {};
        window.addEventListener('load',function(){
            custom_fields_hyperlink();
        });
    </script>
    <?php
    echo get_custom_fields_hyperlink_js_function();
    hooks()->do_action('app_external_form_head');
}

/**
 * Get company logo from uploads folder
 * @param  string $uri        uri to append in the url
 * @param  string $href_class additional href class
 * @param  string $type       dark logo or light logo
 * @return mixed             string
 */
function get_company_logo($uri = '', $href_class = '', $type = '')
{
    $company_logo = get_option('company_logo' . ($type == 'dark' ? '_dark' : ''));
    $company_name = get_option('companyname');

    if ($uri == '') {
        $logoURL = site_url();
    } else {
        $logoURL = site_url($uri);
    }

    $logoURL = hooks()->apply_filters('logo_href', $logoURL);

    if ($company_logo != '') {
        $logo = '<a href="' . $logoURL . '" class="logo img-responsive' . ($href_class != '' ? ' ' . $href_class : '') . '">
        <img src="' . base_url('uploads/company/' . $company_logo) . '" class="img-responsive" alt="' . html_escape($company_name) . '">
        </a>';
    } elseif ($company_name != '') {
        $logo = '<a href="' . $logoURL . '" class="' . $href_class . ' logo logo-text">' . $company_name . '</a>';
    } else {
        $logo = '';
    }


    $logo = hooks()->apply_filters('company_logo', $logo);

    echo $logo;
}
/**
 * Output company logo dark
 * @param  string $uri        uri to append to url
 * @param  string $href_class additional class for href
 * @return string
 */
function get_dark_company_logo($uri = '', $href_class = '')
{
    if (get_option('company_logo_dark') == '') {
        return get_company_logo($uri, $href_class);
    }

    return get_company_logo($uri, $href_class, 'dark');
}
/**
 * Strip tags
 * @param  string $html string to strip tags
 * @return string
 */
function _strip_tags($html)
{
    return strip_tags($html, '<br>,<em>,<p>,<ul>,<ol>,<li>,<h4><h3><h2><h1>,<pre>,<code>,<a>,<img>,<strong>,<b>,<blockquote>,<table>,<thead>,<th>,<tr>,<td>,<tbody>,<tfoot>');
}

function _inject_no_index()
{
    echo '<meta name="robots" content="noindex">' . PHP_EOL;
}

/**
 * Generate small icon button / font awesome
 * @param  string $url        href url
 * @param  string $type       icon type
 * @param  string $class      button class
 * @param  array  $attributes additional attributes
 * @return string
 */
function icon_btn($url = '', $type = '', $class = 'btn-default', $attributes = [])
{
    $_url = '#';
    if (_startsWith($url, 'http')) {
        $_url = $url;
    } elseif ($url !== '#') {
        $_url = admin_url($url);
    }

    return '<a href="' . $_url . '" class="btn ' . $class . ' btn-icon"' . _attributes_to_string($attributes) . '>
    <i class="fa fa-' . $type . '"></i>
    </a>';
}

if (!function_exists('remove_emojis')) {
    function remove_emojis($string)
    {
        return preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F415}](?:\x{200D}\x{1F9BA})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BD})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9AF})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}-\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6D5}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6FA}\x{1F7E0}-\x{1F7EB}\x{1F90D}-\x{1F93A}\x{1F93C}-\x{1F945}\x{1F947}-\x{1F971}\x{1F973}-\x{1F976}\x{1F97A}-\x{1F9A2}\x{1F9A5}-\x{1F9AA}\x{1F9AE}-\x{1F9CA}\x{1F9CD}-\x{1F9FF}\x{1FA70}-\x{1FA73}\x{1FA78}-\x{1FA7A}\x{1FA80}-\x{1FA82}\x{1FA90}-\x{1FA95}]/u', '', $string);
    }
}
