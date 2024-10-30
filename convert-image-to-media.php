<?php

/*
Plugin Name: Convert image to media
Plugin URI: http://electronic-life.net
Description: Looks in wp-content/uploads folder, for image files and convert them to wordpress media
Version: 0.4.5
Author: Rene Skou (rsj@juhlsen.com)
Author URI: http://electronic-life.net
 */

class Convert_Image_To_Media {

	//Constructors
	function Convert_To_Media() {
		$this->__construct();
	}
	function __construct() {
        define( 'RSJ_PLUGIN_PATH', plugin_dir_path(__FILE__) );
        add_action('admin_menu',array(&$this, 'citm_admin_menu'));
	}

    function citm_admin_menu()
    {
        add_submenu_page('tools.php','Convert image to media','CITM','manage_options','citm',array(&$this,'citm_options_page'));
    }

    function citm_options_page()
    {

        /*
         * Variables
         */
        global $_wp_additional_image_sizes;
        $sizes = array();
        $thumbnail_size = null;
        $medium_size = null;
        $large_size = null;
        $url = wp_upload_dir();
        $upload_url = $url['baseurl'];
        $upload_dir = $url['basedir'];
        $files = array();
        $image_dir = array();
        $is_posted = false;
        $status_message = "";

        /*
         * Getting default image sizes from wordpres, it is not 100% if the user usess add_image_size() because the function is not saved as an option.
         * I use the function to not show images that are process to the 3 image sizes.
         */
        foreach( get_intermediate_image_sizes() as $s ){
            $sizes[ $s ] = array();
            if( in_array( $s, array( 'thumbnail', 'medium', 'large' ) ) ){
                $sizes[ $s ]['width'] = get_option( $s . '_size_w' );
                $sizes[ $s ]['height'] = get_option( $s . '_size_h' );
            }else{
                if( isset( $_wp_additional_image_sizes ) && isset( $_wp_additional_image_sizes[ $s ] ) )
                    $sizes[ $s ] = array( $_wp_additional_image_sizes[ $s ]['width'], $_wp_additional_image_sizes[ $s ]['height'], );
            }
        }


        //setting the image size for each default size.
        $thumbnail_size = $sizes['thumbnail']['width'].'x'.$sizes['thumbnail']['height'];
        $medium_size = $sizes['medium']['width'].'x'.$sizes['medium']['height'];
        $large_size = $sizes['large']['width'].'x'.$sizes['large']['height'];

        //check if page has been posted to.
        if(isset($_POST['action']))
        {
            $filenames = $_POST;

            foreach($filenames as $filename)
            {
                if($filename != "submitted" && $filename != "all")
                {
                    $wp_upload_dir = wp_upload_dir();
                    $new_file_path = $wp_upload_dir['path'];
                    $old_file_path = $filename;
                    $image_name = basename($filename);
                    $move_file_result = rename($old_file_path,$new_file_path.$image_name);

                    if($move_file_result)
                    {

                        $filename = $new_file_path.$image_name;
                        $wp_filetype = wp_check_filetype(basename($filename), null );
                        $attachment = array(
                           'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ),
                           'post_mime_type' => $wp_filetype['type'],
                           'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                           'post_content' => '',
                           'post_status' => 'inherit'
                        );
                        $attach_id = wp_insert_attachment( $attachment, $filename);

                        // you must first include the image.php file
                        // for the function wp_generate_attachment_metadata() to work
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
                        $result = wp_update_attachment_metadata( $attach_id, $attach_data );
                        
                        if($result)
                        {
                            $is_posted = true;
                            $status_message = 'Images was succesfully converted, you can see them in your <a href="/wp-admin/upload.php" title="go to media library">media library</a>.';
                            
                        }else{
                            $is_posted = true;
                            $status_message = 'Convertion failed please check media to se which images failed.';
                        }
                        
                    }
                }
            }
        }
        
        //if form is not submitted
        /*
         * TODO
         * Come up with a more genenic way of finding images based on extension, try to look ar preg_replace or someting (done) :)
         * Update support for lower or upper case and jpg,png,gif and jpeg (done). :)
         * Move file into the right directory before processing it.(done):)
         */
        foreach (glob($upload_dir."/*.{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF}",GLOB_BRACE) as $file) {
            if( strpos($file,$thumbnail_size) !== FALSE || strpos($file,$medium_size) !== FALSE || strpos($file,$large_size) !== FALSE )
            {
                // do nothing if true
            }else{
                array_push($files, basename($file));
                array_push($image_dir, $file);

            }

        }
        
        ?>
<div class='wrap'>
    <div class="icon32" id="icon-tools">
        <br />
    </div>
    <h2>Convert image to media</h2>
    <?php if($is_posted) : ?>
    <div id="message" class="updated below-h2"><p><?php echo $status_message ?></p></div>
    <?php endif; ?>
    <script>
    jQuery(document).ready(function(){
    
    jQuery(document).on('change','input[name="check_all"]',function() {
    jQuery('.idRow').prop("checked" , this.checked);
    });
    });
    </script>
    <form action="" method="post" enctype="multipart/form-data">
        <p class='submit'>
            <button name="action" type="submit" id="submit" class="button-primary" value="submitted">
                <?php _e("Convert images") ?>
            </button>

        </p>
        <table class="widefat">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" name="check_all" value="all" /></th>
                    <th></th>
                    <th>File</th>

                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-cb check-column"><input type="checkbox" name="check_all" value="all" /></th>
                    <th></th>
                    <th>File</th>

                </tr>
            </tfoot>
            <tbody>

                <?php
        for($i = 0; $i < count($files); ++$i )
        {
            $file_ex = pathinfo($files[$i],PATHINFO_EXTENSION);
            $file_name = pathinfo( $files[$i],PATHINFO_FILENAME);
            echo '<tr><th scope="row" class="check-column"><input class="idRow" type="checkbox" name="'.$files[$i].'" value="'.$image_dir[$i].'"/></th><td class="column-icon media-icon"><img src="'.$upload_url.'/'.$files[$i].'" width="60" height="60" alt="'.$files[$i].'"/></td><td class="title column-title"><strong>'.$file_name.'</strong><p>'.$file_ex.'</p></td></tr>';
        }

                ?>

            </tbody>
        </table>
        <p class='submit'>
            <button name="action" type="submit" id="submit" class="button-primary" value="submitted">
                <?php _e("Convert images") ?>
            </button>

        </p>
    </form>
</div>
<?php
    }

} //end class
//Instantiate
$object = new Convert_Image_To_Media();
?>
