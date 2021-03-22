<?php

defined('BASEPATH') or exit('No direct script access allowed');

require APPPATH . 'third_party/MX/Lang.php';

class App_Lang extends MX_Lang
{
    /**
     * List of module translations
     *
     * @var array
     */
    // public $moduleLanguage = [];

    /**
     * Load a language file
     *
     * @param	mixed	$langfile	Language file name
     * @param	string	$idiom		Language name (english, etc.)
     * @param	bool	$return		Whether to return the loaded array of translations
     * @param 	bool	$add_suffix	Whether to add suffix to $langfile
     * @param 	string	$alt_path	Alternative path to look for the language file
     *
     * @return	void|string[]	Array containing translations, if $return is set to TRUE
     */
    public function load($langfile, $lang = '', $return = false, $add_suffix = true, $alt_path = '', $_module = '')
    {
        if (is_array($langfile)) {
            foreach ($langfile as $_lang) {
                $this->load($_lang);
            }

            return $this->language;
        }

        $deft_lang = CI::$APP->config->item('language');
        $idiom     = ($lang == '') ? $deft_lang : $lang;

        if (in_array($langfile . '_lang' . EXT, $this->is_loaded, true)) {
            return $this->language;
        }

        $_module or $_module    = CI::$APP->router->fetch_module();
        list($path, $_langfile) = Modules::find($langfile . '_lang', $_module, 'language/' . $idiom . '/');

        if ($path === false) {
            if ($lang = parent::load($langfile, $lang, $return, $add_suffix, $alt_path)) {
                return $lang;
            }
        } else {
            if ($lang = Modules::load_file($_langfile, $path, 'lang')) {
                if ($return) {
                    return $lang;
                }
                $this->language = array_merge($this->language, $lang);
                $this->is_loaded[]    = $langfile . '_lang' . EXT;
                unset($lang);
            }
        }

        return $this->language;
    }

    /**
     * Language line
     *
     * Fetches a single line of text from the language array
     *
     * @param   string  $line       Language line key
     * @param   bool    $log_errors Whether to log an error message if the line is not found
     * @return  string  Translation
     */
    public function line($line, $log_errors = true)
    {
        $value = isset($this->language[$line]) ? $this->language[$line] : false;

     /*   if ($value === false) {
            $value = isset($this->moduleLanguage[$line]) ? $this->moduleLanguage[$line] : false;
        }*/

        // Because killer robots like unicorns!
        if ($value === false && $log_errors === true) {
            log_message('error', 'Could not find the language line "' . $line . '"');
        }

        return $value;
    }
}
