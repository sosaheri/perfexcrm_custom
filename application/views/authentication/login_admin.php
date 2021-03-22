<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('authentication/includes/head.php'); ?>
<body class="login_admin"<?php if(is_rtl()){ echo ' dir="rtl"'; } ?>>
 <div class="container">
  <div class="row">
   <div class="col-md-4 col-md-offset-4 col-sm-8 col-sm-offset-2 authentication-form-wrapper">
    <div class="company-logo">
      <?php get_company_logo(); ?>
    </div>
    <div class="mtop40 authentication-form">
      <h1><?php echo _l('admin_auth_login_heading'); ?></h1>
      <?php $this->load->view('authentication/includes/alerts'); ?>
      <?php echo form_open($this->uri->uri_string()); ?>
      <?php echo validation_errors('<div class="alert alert-danger text-center">', '</div>'); ?>
      <?php hooks()->do_action('after_admin_login_form_start'); ?>
      <div class="form-group">
        <label for="email" class="control-label"><?php echo _l('admin_auth_login_email'); ?></label>
        <input type="email" id="email" name="email" class="form-control" autofocus="1">
      </div>
      <div class="form-group">
        <label for="password" class="control-label"><?php echo _l('admin_auth_login_password'); ?></label>
        <input type="password" id="password" name="password" class="form-control"></div>
        <?php if(show_recaptcha()){ ?>
        <div class="g-recaptcha" data-sitekey="<?php echo get_option('recaptcha_site_key'); ?>"></div>
        <?php } ?>
        <div class="checkbox">
          <label for="remember">
           <input type="checkbox" id="remember" name="remember"> <?php echo _l('admin_auth_login_remember_me'); ?>
         </label>
       </div>
       <div class="form-group">
        <button type="submit" class="btn btn-info btn-block"><?php echo _l('admin_auth_login_button'); ?></button>
      </div>
      <div class="form-group">
        <a href="<?php echo admin_url('authentication/forgot_password'); ?>"><?php echo _l('admin_auth_login_fp'); ?></a>
      </div>

      <?php hooks()->do_action('before_admin_login_form_close'); ?>
      <?php echo form_close(); ?>
    </div>
  </div>
</div>
</div>
</body>
</html>
