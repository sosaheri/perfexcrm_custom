<?php

namespace app\services\messages;

defined('BASEPATH') or exit('No direct script access allowed');

use app\services\messages\AbstractMessage;

class IsBaseUrlChangeRequired extends AbstractMessage
{
    protected $alertClass = 'warning';

    public function isVisible()
    {
        return is_https() && startsWith(site_url(), 'http://');
    }

    public function getMessage()
    { ?>
        <h4><b>Base URL Config Warning</b></h4>
        <hr class="hr-10" />
        <p>
         The application is served over SSL with <b>HTTPS</b> Url but the configured base url does not start with <code>https://</code>
        <br />
        <br />
        <b>To avoid any issues</b>, via FTP/cPanel navigate to <code>application/config/app-config.php</code> and change the <code>APP_BASE_URL</code> config from <code><?php echo site_url(); ?></code> to <code><?php echo rtrim(str_replace('http://', 'https://', site_url()), '/') .'/'; ?></code>
     </p>
     <?php
    }
}
