<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['surveys/survey/(:num)/(:any)'] = 'participate/index/$1/$2';
