<?php
/*
Plugin Name: Scode
Plugin URI: /
Description: Test plugin.
Version: 1.0.0
Author: MaksK
Author URI: /
License: GPLv2 or later
Text Domain: scode-mk
Domain Path: /language
*/
define('PLUGIN_NAME', 'scode-mk');

register_activation_hook( __FILE__, 'scode_activate' );
function scode_activate(){
	add_option('scode_mk_options', array());
}

register_deactivation_hook( __FILE__, 'scode_deactivate' );
function scode_deactivate() {

}

register_uninstall_hook( __FILE__, 'scode_uninstall' );
function scode_uninstall(){
	delete_option( 'scode_mk_options' );
}

add_action( 'admin_menu', 'register_my_page' );
function register_my_page(){
	add_menu_page( 
    __('Scode', PLUGIN_NAME),
    __('Scode', PLUGIN_NAME),
    'administrator',
    'scode-slug',
    'scode_function',
    'dashicons-admin-site',
    90
  );
}

add_action( 'plugins_loaded', 'scode_plugin_language' );
function scode_plugin_language() {
	load_plugin_textdomain( PLUGIN_NAME, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}


function scode_function() {

  $path = wp_upload_dir();
  $path = $path['basedir'].'/scode';
  $file_name = '/scode.csv';

  if (!empty($_POST['save'])) {
    unset($_POST['save']);

    $data = array();
    foreach($_POST as $name => $options) {
      foreach($options as $key => $option) {
        if(!empty($option)){
          $data[$key][$name] = $option;
        }
      }
    }

    if(update_option('scode_mk_options', wp_unslash($data))) {
      ?>
      <div class="notice notice-success inline">
        <p><?php esc_html_e( 'Saved!', PLUGIN_NAME ); ?></p>
      </div>
      <?php
    }
  } else if(isset($_POST['save_csv'])) {
    
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }
    $fp = fopen($path . $file_name, 'w');
    
    $options = ( get_option('scode_mk_options') );

    foreach ($options as $fields) {
      fputcsv($fp, $fields);
    }
  
    fclose($fp);

    ?>
    <div class="notice notice-info inline">
      <p><?php esc_html_e( 'File seved on the server', PLUGIN_NAME ); ?></p>
      <p>
        <a href="<?php echo $path . $file_name; ?>" download="scode.csv"><?php esc_html_e('Save csv', PLUGIN_NAME); ?></a>
      </p>
    </div>
    <?php
  } else if (isset($_POST['upload_csv'])) {

    if (move_uploaded_file($_FILES['file_csv']['tmp_name'], $path . $file_name)) {

        if( ($handle = fopen($path . $file_name, "r")) !== false) {
          $file_arr = array();
          while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $file_arr[] = array_combine(['name', 'trigger', 'content'], $data);
          }
          fclose($handle);

          if(update_option('scode_mk_options', wp_unslash($file_arr))) {
            ?>
            <div class="notice notice-success inline">
              <p><?php esc_html_e( 'Download csv.', PLUGIN_NAME ); ?></p>
            </div>
            <?php
          }
        }
    } else {
      ?>
        <div class="notice notice-error inline">
          <p><?php esc_html_e( 'Possible file upload attack', PLUGIN_NAME ); ?></p>
        </div>
      <?php
    }
  }

  ?>
  <style>
    fieldset.option:last-of-type {
      display: none;
    }
    input[type="submit"][name="save"] {
      float: right;
      margin-right: 20px;
    }
    .file-wrp {
      display: flex;
      justify-content: space-between;
    }
  </style>
  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

  <a class="button-secondary" id="duplicate" href="#" title="<?php esc_attr_e( 'Add new', PLUGIN_NAME ); ?>"><?php esc_attr_e( 'Add new', PLUGIN_NAME ); ?></a>
  <hr>

  <div class="file-wrp">
    <form action="" method="post">
      <input type="submit" class="button-primary" name="save_csv" value="<?php esc_attr_e( 'Save csv', PLUGIN_NAME); ?>">
    </form>
    <form action="" method="post" enctype="multipart/form-data">
      <input type="file" name="file_csv" id="">
      <input type="submit" class="button-primary" name="upload_csv" value="<?php esc_attr_e('Download', PLUGIN_NAME); ?>">
    </form>
  </div>

  <hr>
  
  <form action="" method="post">
    <div class="options-wrapp">
      <?php
        $options = get_option('scode_mk_options');
        $count = is_array($options) ? count($options) : 0;
        $i = 0;
        do {
      ?>
      <fieldset class="option">
        <legend><h2><?php esc_html_e('Options', PLUGIN_NAME) ?></h2></legend>
        <p>
          <label for=""><?php esc_html_e('Name', PLUGIN_NAME); ?>
            <input class="regular-text option-val" type="text" name="name[]"  id="" value="<?php if(isset($options[$i]['name'])) esc_attr_e($options[$i]['name'], PLUGIN_NAME); ?>">
          </label>
        </p>
        <p>
          <label for=""><?php esc_html_e('Trigger', PLUGIN_NAME); ?>
            <input id="trigger-<?php echo $i; ?>" class="regular-text option-val trigger" type="text" name="trigger[]"  value="">
          </label>          
        </p>
        <p>
          <label for=""><?php esc_html_e('Content', PLUGIN_NAME); ?>
          <?php 
            $content = isset($options[$i]['content']) ? $options[$i]['content'] : '';
            wp_editor($content, 'content_'.$i, ['textarea_name'=>'content[]', 'textarea_rows'=>5, 'teeny'=>'true']);
          ?>
          </label>
        </p>
        <p>
          <button onclick="remover(this); return false;" class="button button-small" style="background-color: #DC3232; color: #fff; border: none;"><?php esc_attr_e('Remove', PLUGIN_NAME); ?></button>
        </p>
      </fieldset>
      <?php
        $triggers = [];
        if(isset($options[$i]['trigger'])) {
          $triggers_id = explode(',', $options[$i]['trigger']);
          $pages = get_pages(['include' => $triggers_id]); 
          foreach($pages as $page) {
            $triggers[] = ['id' => $page->ID, 'name' => $page->post_title];
          }
        }
        $triggers = json_encode($triggers);
      ?>
      <script>
        jQuery(document).ready(function () {
          jQuery("#trigger-<?php echo $i; ?>").tokenInput('<?php echo get_home_url( null, '/wp-json/wp/v2/search?subtype=page&_fields=id,title' , 'https' ); ?>',
          {
            theme: "facebook",
            queryParam: "search",
            onResult: function (results) {
              jQuery.each(results, function (index, value) {
                results[index]['name'] = value.title;
              });
              return results;
            },
            <?php if(isset($options[$i]['trigger']))  echo 'prePopulate: ' . $triggers; ?>
          })
        });
      </script>
      <?php 
        $i++;
        } while($i < $count+1);
      ?>
    </div>

    <p>
      <input type="submit" name="save" value="<?php esc_attr_e('Save', PLUGIN_NAME); ?>" class="button-primary" >
    </p>
  </form>

    <script>
      jQuery(document).ready(function () {

        jQuery('#duplicate').click(function () {
          var option = jQuery('fieldset.option:last');
          jQuery('html, body').animate({
            scrollTop: jQuery(option).show().offset().top
          }, 1000);
          return false;
        })

      });
      
      function remover(e) {
        jQuery(e).parents('fieldset.option').remove();
      }

    </script>
  <?php

  
}

add_shortcode( 'scode', 'scode_show' );
function scode_show($attr) {
  $options = get_option('scode_mk_options');
  $scode_name = $attr['scode_name'];
  $key = array_search($scode_name, array_column($options, 'name'));
  if($key !== FALSE) {
    if ( class_exists('WPGlobus') ) {
      return WPGlobus_Core::text_filter( $options[$key]['content'], WPGlobus::Config()->language );
    }else {
      return $options[$key]['content'];
    }
  }
}

// add keywords from yoast
add_action('wp_head', 'my_keywords', 2);
function my_keywords(){	
	$keywords = get_post_meta(get_the_ID(), '_yoast_wpseo_focuskw', true);
	if(!empty($keywords)) {
		$keywords = trim(str_replace('|', ', ', $keywords));
		echo '<meta name="keywords" content="'.$keywords.'" />';
	}
}

add_action('admin_enqueue_scripts', function(){
  // 'https://loopj.com/jquery-tokeninput/src/jquery.tokeninput.js';
  wp_enqueue_script( 'trigger-tags', plugin_dir_url( __FILE__ ) . 'assets/jquery.tokeninput.js', array('jquery'), '1.0' );
  wp_enqueue_style( 'trigger-tags', plugin_dir_url( __FILE__ ) . 'assets/token-input-facebook.css', false, '1.0.0' );
  wp_enqueue_style( 'trigger-tags', plugin_dir_url( __FILE__ ) . 'assets/token-input.css', false, '1.0.0' );
});
