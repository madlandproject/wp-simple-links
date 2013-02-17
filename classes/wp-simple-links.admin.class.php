<?php 



                     /**
                      * Methods for the Admin Area of Simple Links
                      * @since 10.10.12
                      * @author Mat Lipe
                      * @uses called by init.php
                      * @uses $simple_links_admin_func
                      */

if( !class_exists( 'simple_links_admin' ) ){
class simple_links_admin{
	
	public $cap_for_settings; //The capabilites need to see settings page
	
	
	//The addtional fields from the settings page
    public $addtional_fields = array();
    
    //The checkboxes from the settings page used in the settings_save function update this to auto save the new checkboxes
    private $settings_checkboxes = array( 'sl-remove-links','sl-replace-widgets','sl-hide-ordering','sl-show-settings' );
	
	
	
	function __construct(){
		
		//Remove the Wordpress Links from admin menu
		add_action( 'admin_menu', array( $this, 'remove_links' ) );
	
		//Set the array for additional fields
		$this->additional_fields = json_decode( get_option('link_additional_fields'), true );
	

		//Add the jquery
		add_action( 'admin_print_scripts', array( $this, 'admin_scripts') );
		add_action( 'admin_print_styles', array( $this, 'admin_style' ) );
		if( isset( $_GET['page'] ) && $_GET['page']=='simple-link-settings' ){
			//Bring in the scripts for the setting page only
			add_action( 'admin_print_scripts', array( $this, 'settings_scripts' ) );
		}
		
		
		//Image uploader mod
		add_action( 'admin_head-media-upload-popup', array( $this, 'upload_mod') );
		
		//The LInk Ordering page
		add_action( 'admin_menu', array( $this, 'sub_menu' ) );
		
		//The Meta Boxes
		add_action( 'admin_menu', array( $this, 'meta_boxes'));
		
		
		//Add the function to an ajax request to sort the links in the list
		add_action('wp_ajax_simple_links_sort_children', array( $this, 'ajax_sort') );
		
		//Ajax request to import links
		add_action('wp_ajax_simple_links_import_links', array( $this, 'import_links') );
		
		
		//Add Contextual help to the necessary screens
		add_action( "load-simple_link_page_simple-link-settings", array( $this, 'help' ) );
		add_action( "load-{$GLOBALS['pagenow']}", array( $this, 'help' ) );
		
		
		//Add the shortcode button the MCE editor
		add_action( 'init', array( $this, 'mce_button' ) );
		
		
		//Add the filter to the Links Post list
		add_action('restrict_manage_posts', array( $this, 'posts_list_cat_filter') );
		add_filter('request', array( $this, 'post_list_query_filter') );

		
		//Post List Columns Mod
		add_filter( 'manage_simple_link_posts_columns', array( $this, 'post_list_columns' ) );
		add_filter( 'manage_simple_link_posts_custom_column', array( $this, 'post_list_columns_output'), 0, 2 );
	
	}
	
	
	
	
	/**
	 * Adds the output to the custom post list columns created by post_list_columns()
	 * @param string $column column name
	 * @param int $postID
	 * @since 8/29/12
	 * @uses called by __construct()
	 */
	function post_list_columns_output( $column, $postID ){
		
		switch ($column){
			case 'web_address':
                   echo get_post_meta( $postID, 'web_address', true );
				break;
			case 'category':
				global $simple_links_func;
				
				$cats = $simple_links_func->get_link_categories( $postID );
				if( is_array( $cats ) ){
				    echo implode(' , ', $cats );
				}
				break;
		}
	}
	
	/**
	 * Adds the web address and the categories the the links list
	 * @param array $default existing columns
	 * @since 8/21/12
	 * @uses Called with __construct();
	 */
	function post_list_columns( $defaults ){
		
		//get checkbox and title
		$output = array_slice ( $defaults, 0, 2 );
		
		//Add a new column and label it
		$output['web_address' ] = __('Web Address','wp-simple-links');
		$output['category']     = __('Link Categories','wp-simple-links');
		
		//Add the rest of the default back onto the array
		$output = array_merge ( $output, array_slice( $defaults , 2 ) );
		
		
		//return
		return $output;
	
		
	}
	
	
	
	
	/**
	 * Update the query request to match the slug of the link category in the links list
	 * @param array $request the query request so far
	 * @return array the full request
	 * @since 8/20/12
	 * @uses called by __construct
	 */
	function post_list_query_filter($request ){
		global $pagenow;
		if (is_admin() && $pagenow == 'edit.php' && isset($request['post_type']) && $request['post_type']=='simple_link') {
			//Changes this to the slug version because the query requires slugs
			$request['simple_link_category'] = get_term($request['simple_link_category'],'simple_link_category')->slug;
		}
		return $request;
	}
	
	
	/**
	 * Creates a drop down list of the link categories to filter the links by in the posts list
	 * @since 8/20/12
	 * @return null
	 * @uses called by __construct
	 */
	function posts_list_cat_filter(){
		global $typenow;
		global $wp_query;
		if ($typenow=='simple_link') {
			$taxonomy = 'simple_link_category';
			$taxonomy_obj = get_taxonomy($taxonomy);
			wp_dropdown_categories(array(
					'show_option_all' =>  sprintf( __('Show All %s','wp-simple-links'), $taxonomy_obj->label),
					'taxonomy'        =>  'simple_link_category',
					'name'            =>  'simple_link_category',
					'orderby'         =>  'name',
					'selected'        =>  $_GET['simple_link_category'],
					'hierarchical'    =>  true,
					'depth'           =>  3,
					'show_count'      =>  true, 
					'hide_empty'      =>  true, 
					));
		}
		
	}
	
	
	
	/**
	 * Generates all contextual help screens for this plugin
	 * @since 12.15.12
	 * @uses Called at load by __construct
	 */
	function help(){
	
		//echo $screen->id;
		//print_r( $screen );
		
		$shortcode_help = array(
						'id'   			 => 'wp-simple-links-shortcode' ,
						'title'          => 'Simple Links Shortcode',
						'content'   	 => '<h5>'.__('You Can add a Simple Links List anywhere on the site by using the shortcode'). '[wp-simple-links]</h5>
												<strong>Supported Options:</strong><br>
												category   = '.__('"Comma separated list of Link Category Names" - defaults to all'). '<br>
												orderby    = '.__('"title or random" - defaults to link order'). '<br>
												order      = '.__('"DESC or ASC" - defaults to ASC'). '<br>
												count      = '.__('"Number of links to show"'). '<br>
												show_image = '.__('"true or false" - to show the link\'s image or not'). '<br>
												image_size = '.__('"Any size built into Wordpress or your theme" - default to thumbnail'). '<br>
												remove_line_break = "true or false" - '.__('Remove Line Break Between Images and Links'). ' - default to false<br>
												fields     = '.__('"Comma separated list of the Link\'s Additional Fields to show"'). '<br>
												description = '.__('"true or false" - to show the description - defaults to false'). '<br>
												separator   = '.__('"Any characters to display between fields and description" - defaults to "-"'). '<br>
				                                id          = '.__('"An optional id for the outputed list'). '"
												<br>
												e.g. [wp-simple-links show_image="true" image_size="medium" count="12"]<br>
												<em>'.__('Look for the puzzle button on the post and page content editors for a form that generates the shortcode for you'). '</em>'
				);
		
		//help for the widgets
		$widget_help = array(
				'id'   			 => 'wp-simple-links-widget' ,
				'title'          => 'Simple Links Widget',
				'content'   	 => '<h5>'.__('You May Add as Many Simple Links Widgets as You Would Like to Your Widget Areas'). '</h5>
				                    <strong>'.__('Widget Options'). ':</strong><br>
				                Categories       = "'.__('Select with link categories to pull from'). '"<br>
				                Order Links By   = "'.__('The Order in Which the Links will Display" - defaults to link order'). '<br>
				                Order            = "'.__('The Order in which the links will Display'). '"<br>
				                Show Description = "'.__('Display the Link\'s Description'). '<br>
				                Number of LInks  = "'.__('Number of links to show'). '"<br>
				                Show Image       = "'.__('Check the box to display the Link\'s Image'). '<br>
				                Image Size       = "'.__('The Size of Image to Show if the previous box is checked'). '<br>
				                Include Additional Fields     = "'.__('Display values from the Link\'s Additional Fields'). '"<br>
				                Field Separator  = "'.__('And characters to display between fields and description" - defaults to '). '"-"<br>'
				);
		
		
		
		//The screen we are on
		$screen = get_current_screen();
		

		//Each page will have different help content
		switch ($screen->id){
			
			case 'widgets': $screen->add_help_tab( $widget_help);
			break;
			
			//Normal Pages and posts and widgets - The shortcode help
			case 'page':
			case 'post':
			
				$screen->add_help_tab( $shortcode_help);
			break;
	
			//The settings page
			case 'simple_link_page_simple-link-settings':
	
				$screen->add_help_tab( array(
				'id'   			 => 'wordpress-links' ,
				'title'          => 'Wordpress Links',
				'content'   	 => '<p>'.__('If they haven\'t already, Wordpress will be deprecating the built in links functionality').'.<br>
				                        '.__('These settings take care of cleaning up the Built In Links right now'). '<br>
				                        '.__('By Checking "Remove the Built in Links", the Links menu will disappear along with the add new Admin Bar link'). '. <br>
				                        '.__('By Checking "Replace Link Widgets with Simple Link Replica widgets, the Wordpress Link Widgets will automatically'). 'be replaced with widgets labeled "Simple Links Replica". All existing "links" widgets will remain in place and uneffected by deprececation. They will simply have a new title'). '.<br>
				                       '.__(' Pressing the "Import Links" button will automatically copy the Wordpress Links into Simple Links. Keep in mind if you press this button twice it will copy the links twice and you will have duplicates'). '.</p>'
				 );
	
	
				$screen->add_help_tab( array(
						'id'   => 'additional_fields' ,
						'title'       => __('Additional Fields', 'wp-simple-links'),
						'content'    => '<p>'.__('You have the ability to add an unlimited number of additional fields to the links by click the "Add Another" button').'. <br>
						                    '.__('Once you save your changes, these fields will show up on a each link\'s editing page').'. <br>
				                            '.__('You will have the ability to select any of these fields to display using the Simple Links widgets').'. <br>
				                            '.__('Each widget gets it\'s own list of ALL the additional fields, so you may display different fields in different widget areas').'. <br>
				                            '.__('These fields will also be avaible by using the shortcode. For instance, if you wanted to display a field titled "author" and a field titled "notes" you shortcode would look something like this').'
				                            <br>[wp-simple-links fields="author,notes" ]</p>',
				) );
	
	
				$screen->add_help_tab( array(
						'id'   			 => 'permissions' ,
						'title'          => __('Permissions', 'wp-simple-links'),
						'content'   	 => '<p><strong>'.__('This is where you decided how much access editors will have').'</strong><br>
						'.__('"Hide Link Ordering from Editors", will prevent editors from using the drag and drop ordering page. They will still be able to change the order on the individual Link editing Pages').'.<br>
						'.__('"Show Simple Link Settings to Editors" will allow editors to access the screen you are on right now without restriction').'.</p>',
				) );
	
				$screen->add_help_tab( array(
						'id'      =>  'crockpot-recipe',
						'title'   => 'Crock-Pot Recipe',
						'content' => '<p>For folks out the like me that rarely have time to leave the computer and cook, a Crock-Pot meal is a great way
						to have food hot and ready to eat.
						</p>
						<p><strong>Here is one of my favorites recipes "Carne Rellenos"</strong><br>
						1 can (4 ounces) whole green chilies, drained<br>
						4 ounces cream cheese, softened<br>
						1 flank steak (about 2 pounds)<br>
						1.5 cups salsa verde<br>
						Slit whole chiles open on one side with sharp knife; stuff with cream cheese. Open steak flat on sheet of waxed paper; score
						steak and turn over. Lay stuffed chiles across unscored side of steak. Roll up and tie with kitchen string. Place steak in Crock Pot
						;pour in salsa. Cover; cook on LOW 6 to 8 hours or on HIGH 3 to 4 hours or until done. Remove stead and cut into 6 pieces. Serve
						with sauce.</p>'
				)	);
				
				$screen->add_help_tab( $shortcode_help );
	
	
	
	
	
				//Add Sidebar Content to SEtting Page
				$screen->set_help_sidebar(
						__( '<p>'.__('These Sections will give your a brief description of what each group of settings does.
								Feel free to start a thread on the support forums if you would like additional help items covered in this section').'.</p>') );
	
				break;
	
	
	
		}
	
		//print_r( $screen );
	
		//self::check_current_screen();
	
	}
	
	


	
	/**
	 * Adds the button to the editor for the shorcode
	 * @since 8/19/12
	 * @uses called by init in __construct()
	 * @package mce
	 * @uses There are a couple methods that had to be called from outside the 
	 */
	function mce_button(){

		add_filter( "mce_external_plugins", array( $this, 'button_js') );
		add_filter( 'mce_buttons_2', array( $this, 'button') );
		
	}
	
	/**
	 * Attached the plugins js to the mce button
	 * @since 8/19/12
	 * @uses called by mce_button()
	 */
	function button_js( $plugins ){
		$plugins['simpleLinks'] = SIMPLE_LINKS_JS_DIR. 'editor_plugin.js';
		return $plugins ;
		
	}
	
	/**
	 * Adds an MCE button to the editor for the shortcode
	 * @since 8/19/12
	 * @uses called by mce_button()
	 */
	function button( $buttons ){
		
		array_push( $buttons, "|", "simpleLinks" ); //Add the button to the array with a separator first
		return $buttons;
		
	}
	
	
	
	
	/**
	 * Creates an Admin Flag to let new uses know where the menu is
	 * @since 8/19/12
	 * @uses called in the admin_scripts function
	 */
	function pointer_flag(){
		$handle = 'wp-simple-links-flag'; 
		

		// Get the list of dismissed pointers for the user
		$dismissed = explode( ',' , (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
		
		// Check whether our pointer has been dismissed
		if ( in_array( $handle , $dismissed ) ) {
			return;
		}
		
		//This is the content that will be displayed
		$pointer_content  = '<h3>Simple Links</h3>' ;
		$pointer_content .= '<p>'.__('Manage your Links Here. Enjoy','wp-simple-links').'! </p>' ;
		
		
		?>
		<script type= "text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
		
		//The element to point to
		$( '#menu-posts-simple_link').pointer({
		content: ' <?php echo $pointer_content; ?>',
			position: {
				edge: 'left', 
				align: 'center'
			},
			close: function() {
				jQuery.post( ajaxurl, {
					pointer: '<?php echo $handle; ?>',
					action: 'dismiss-wp-pointer'
				});
			}
		}).pointer( 'open');
		});
		//]]>
		</script>
		<?php
	
		
	}
	
	
	/**
	 * Removes all traces of the Wordpress Built in Links from the Admin
	 * @since 8/19/12
	 * @uses called by construct
	 */
	function remove_links(){
		
		//Escape hatch
		if( !get_option('sl-remove-links', false ) ) return;
		
	    	
	    //Remove the menu page
	    remove_menu_page('link-manager.php');
	    	
	    //Remove the Add link in the admin bar
	    /** Changed to different structure on 8/31/12 **/
	    add_action( 'wp_before_admin_bar_render', array( $this, 'remove_new_link_menu') );
		
	}
	
	/**
	 * Remove original links manager link
	 * @since 8/31/12
	 * @uses called by remove_links();
	 */
	function remove_new_link_menu() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'new-link' );
	
	}
	

	
	
	/**
	 * Imports the Wordpress links into this custom post type
	 * @since 8/19/12
	 * @uses called using ajax
	 */
	function import_links(){
		
		check_ajax_referer( 'simple_links_import_links' ); //Match this to the nonce created on the url
		//Add the categories from the links
		$old_link_cats = get_categories('type=link');
		if( is_array( $old_link_cats ) ){
			foreach( $old_link_cats as $cat ){
				if( !term_exists($cat->name, 'simple_link_category') ){
					wp_insert_term($cat->name, 'simple_link_category');
				}
			}
		}
		
		
		//Import Each link
		foreach( get_bookmarks() as $link ){
			
			$post = array(
  					'post_name' => $link->link_name,
  					'post_status' => 'publish',
  					'post_title' => $link->link_name,
  					'post_type' => 'simple_link'  				
			); 
			
			//Create the new post
			$id = wp_insert_post( $post );
			
			//Update Existing post data
			update_post_meta( $id, 'description', $link->link_description );
			update_post_meta( $id, 'target', $link->link_target );
			update_post_meta( $id, 'web_address', $link->link_url );
			
			
			//Put the post in the old categories
			$terms = get_the_terms($link->link_id, 'link_category');
			if( is_array( $terms ) ){
				foreach( $terms as $term ){
					wp_set_object_terms( $id, $term->slug, 'simple_link_category', true );
			       // print_r( $term );
				}
			}
			
		}
		
	}
	
	
	
	
	/**
	 * Create the submenus
	 * @since 8/27/12
	 * @uses This has built in filters to change the permissions of the link ordering and settings
	 * @uses to change the permissions outside of the dashboard settings setup the filters here
	 * 
	 */
	function sub_menu(){
	
		//Use the setting page options to change permissions for the link ordering
		//A filter to change the permissions required to order links
		if( get_option('sl-hide-ordering',false) ){
			$cap_for_ordering = apply_filters('simple-link-ordering-cap','manage_options');
		} else {
			$cap_for_ordering = apply_filters('simple-link-ordering-cap','edit_posts');
		}
	
		//Use the settings page to change the permission for the setting menu
		//This permission may also be used in other place to determine if user can use things
		//A Filter may be used here as well
		if( !get_option('sl-show-settings',false) ){
			$this->cap_for_settings = apply_filters('simple-link-settings-cap','manage_options');
		} else {
			$this->cap_for_settings = apply_filters('simple-link-settings-cap','edit_posts');
		}
		
		
		
		//The link ordering page
		add_submenu_page( 'edit.php?post_type=simple_link','simple-link-ordering',__('Link Ordering','wp-simple-links'),$cap_for_ordering,'simple-link-ordering', array( $this, 'link_ordering_page' ) );
		
		
		//The Settings Page
		add_submenu_page( 'edit.php?post_type=simple_link','simple-link-settings',__('Settings','wp-simple-links'),$this->cap_for_settings,'simple-link-settings', array( $this, 'settings' ) );
	
	
	
	}
	
	
	
	/**
	 * The js for just the settings page
	 * prevents conflicts later keeping it separate
	 * @since 8/18/12
	 * @package Settings Page
	 */
	function settings_scripts(){
		
		wp_enqueue_script('common');
		wp_enqueue_script('wp-lists');
		wp_enqueue_script('postbox');
		
		wp_enqueue_script(
				'simple_links_settings_script',
				SIMPLE_LINKS_JS_DIR . 'simple_links_settings.js',
				array('jquery'),  //The scripts this depends on
				'1.0.0'     //The Version of your script
		
		);
	}
	

	

	

	
	
	/**
	 * The meta box output for the wordpress links section of the settings page
	 * @since 8/19/12
	 * @uses called by add_meta_box
	 * @package Settings Page
	 */
	function wordpress_links(){
		?>
		<h4><?php _e('These Settings Will Effect the built in Wordpress Links','wp-simple-links');?></h4>
		<ul>
			<li><?php _e('Remove Wordpress Built in Links','wp-simple-links');?>: <input type="checkbox" name="sl-remove-links" <?php checked(get_option('sl-remove-links')); ?> value="1" />
				<?php simple_links_questions('SL-remove-links'); ?>
			 </li>
			<li><?php _e('Replace Link Widgets with "Simple Links Replica" widgets','wp-simple-links');?>: <input type="checkbox" name="sl-replace-widgets" 
				<?php checked(get_option('sl-replace-widgets')); ?> value="1" />
			    <?php simple_links_questions('SL-replace-widgets'); ?>
			</li>
			<li><br>
			     <div class="updated" id="import-links-success" style="display:none"><p><?php _e('The links have been imported Successfully','wp-simple-links');?></p></div>
			     <?php submit_button(__('Import Links','wp-simple-links'),'secondary','sl-import-links', false); echo ' &nbsp; ';  simple_links_questions('SL-import-links');?>
			</li>
		</ul>
	<?php 	
	}
	
	
	/**
	 * Creates the custom meta boxes
	 * @since 8/19/12
	 * @package Settings Page
	 */
	function meta_boxes(){
	
	
		//For the Settings Additional Fields
		add_meta_box('sl-additional-fields', __('Additional Fields','wp-simple-links'), array( $this, 'additional_fields' ), 'sl-settings-boxes','advanced','core');
		//For the Settings Wordpress Links
		add_meta_box('sl-wordpress-links', __('Wordpress Links','wp-simple-links'), array( $this, 'wordpress_links' ), 'sl-settings-boxes','advanced','core');
		//For the Settings Permissions
		add_meta_box('sl-permissions', __('Permissions','wp-simple-links'), array( $this, 'permissions' ), 'sl-settings-boxes','advanced','core');

	}
	
	/**
	 * The output of the Permissions box in the settings page
	 * @since 8/19/12
	 * @package Settings Page
	 */
	function permissions(){
		?>
		<h4><?php _e('These Settings Will Effect Access to this Plugins Features','wp-simple-links');?></h4>
		<ul>
			<li><?php _e('Hide Link Ordering From Editors','wp-simple-links');?>: <input type="checkbox" name="sl-hide-ordering" <?php checked(get_option('sl-hide-ordering')); ?> value="1" />
			<?php simple_links_questions('SL-hide-ordering'); ?>
			</li>
			<li><?php _e('Show Simple Link Settings to Editors','wp-simple-links');?>: <input type="checkbox" name="sl-show-settings"
				<?php checked(get_option('sl-show-settings')); ?> value="1" />
				<?php simple_links_questions('SL-show-settings'); ?>
			</li>
		
		</ul>
		
		<?php 
	}
	
	

	

	
	
	/**
	 * Create the settings Page
	 * @since 8/19/12
	 * @package Settings Page
	 */
	function settings(){
	 
        //If it came from the right place Update the settings
		if( isset( $_POST['SL-setting-submit'] ) && wp_verify_nonce($_POST['SL-settings'], plugin_basename(__FILE__)) ){
			self::settings_save();
		}
		
	
		echo '<div class="wrap">';
			screen_icon('simple_link');
			?><h2><?php _e('Simple Links Settings','wp-simple-links');?></h2>
			    <em><?php _e('Be sure to see the help menu for descriptions','wp-simple-links');?></em>
			
			   <form action="#" method="post">
			
	       		<?php 	 
	       		//Verification for this form
	       		wp_nonce_field( plugin_basename(__FILE__), 'SL-settings', true, true );
	       		
	       		
				//Allow storing of the open and closed box states
				wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
				<?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
				<div id="poststuff" class="metabox-holder  has-right-sidebar">
					<div id="side-info-column" class="inner-sidebar">
		
                  		<?php //do_meta_boxes('SL-additional-fields','advanced', null ); ?>
					</div>
					
					
					<div id="post-body" class="has-sidebar">
						<div id="post-body-content" class="has-sidebar-content">
	
				    		<?php do_meta_boxes('sl-settings-boxes','advanced', null ); ?>
				
				      </div>
					</div>
				
					
				<br class="clear"/>

				</div><!-- end poststuff --><?php 

			
			submit_button(null,'primary','SL-setting-submit');
		?>
			</form>
		</div><!-- End .wrap -->
	<?php 
	}
	
	/**
	 * The Additional_fields Meta box
	 * @since 8/29/12
	 * @uses called by the add_meta_box function
	 */
	function additional_fields(){
	
	 ?><h4><?php _e('These Fields Will Be Available on All Link\'s Edit Screen, Widgets, and Shortcodes','wp-simple-links');?>.</h4><?php
	 
	 //Set the array for additional fields
	 $this->additional_fields = json_decode( get_option('link_additional_fields'), true );

	 if( is_array( $this->additional_fields ) ){
	 	foreach( $this->additional_fields as $field ){
	 		printf( 'Field Name: <input type="text" name="link_additional_field[]" value="%s"><br>', $field );
	 	}
	 }
	
	
	
	//The new field
	?>
	<?php _e('Field Name','wp-simple-links');?>: <input type="text" name="link_additional_field[] value=""><br>
	
	<!-- Placeholder for JQuery -->
	<span id="link-extra-field" style="display:none">Field Name: <input type="text" name="link_additional_field[] value=""><br></span>
	<span id="link-additional-placeholder"></span>
	<?php
	submit_button(__('Add Another','wp-simple-links'),'secondary', 'simple-link-additional');
	
	}
	
	
	/**
	 * Saves the values from the settings page
	 * @since 8/18/12
	 * @package Settings Page
	 * @uses Called from above the setting's page form
	 */
	function settings_save(){
		
		/*
		 * Saves the checkboxes, update the classes var to add new ones
		 */
		foreach( $this->settings_checkboxes as $checkbox ){
		  if( isset( $_POST[$checkbox] ) ){
		     update_option( $checkbox, $_POST[$checkbox] );
		  } else {
		  	update_option( $checkbox, 0);
		  }
		}
		
	 
	      
	  //The additional Fields Settings
	  if( isset( $_POST['link_additional_field'] ) ){
	     $fields = array_filter( $_POST['link_additional_field'] );
	     
	     update_option('link_additional_fields', json_encode($fields) );
	  }
	  
	  
	 ?>
	 	<div class="updated"><p><?php _e('The Settings have been Updated!','wp-simple-links');?></p></div>
	 <?php 
	 
	 
	 
	}
	
	
	
	
	
	
	/**
	 * The link Ordering Page
	 * @since 9/11/12
	 */
	function link_ordering_page(){
		echo '<div class="wrap">';
			screen_icon('themes');
			echo '<h2>'.__('Keeping Your Links in Order','wp-simple-links').'!</h2>';
		
		
			//Create the Dropdown for by Category Sorting
			$all_cats = get_terms('simple_link_category');
			if( is_array( $all_cats ) ){ 
				?>
				<h3><?php _e('Select a Link Category to Sort Links in that Category Only ( optional )','wp-simple-links');?></br></h3>
					<select id="SL-sort-cat">
						<option value="Xall-catsX"><?php _e('All Categories','wp-simple-links');?></option>
				
				<?php 
				foreach( $all_cats as $cat ){
					printf( '<option value="%s">%s</option>', $cat->slug, $cat->name );
				}
			
				echo '</select> <br> <br>';
			
			} else {
				?>
				<h3><?php _e('To Sort by Link Categories, you must Add Some Links to them','wp-simple-links');?>.<br>
					<a href="/wp-admin/edit-tags.php?taxonomy=simple_link_category&post_type=simple_link"><?php _e('Follow Me','wp-simple-links');?></a>
				</h3>
				<?php 
			}
		
		
		
			//Retrieve all the links
			$links = get_posts( 'post_type=simple_link&orderby=menu_order&order=ASC&numberposts=200' );
		
			echo '<ul class="draggable-children" id="SL-drag-ordering">';
		
			#-- Create the items list
			foreach( $links as $link ){
				$cats = '';
			
				//All Cats Assigned to this
				$all_assigned_cats = get_the_terms($link->ID, 'simple_link_category');
				if( !is_array( $all_assigned_cats ) ) {
					$all_assigned_cats = array();
				}
			
				//Create a sting of cats assigned to this link
				foreach( $all_assigned_cats as $cat ){
					$cats .= ' ' . strtolower(str_replace(' ', '-', $cat->name)); 
				}
			
			
		?>
		           <li id="postID-<?php echo $link->ID; ?>" class="<?php echo $cats; ?>">
                    	<div class="menu-item-handle">
		            		<span class="item-title"><?php echo $link->post_title ?></span> 
		            	</div>
		            </li>
		            
		     <?php 
		 }
		            	
	   echo '</ul></div><!-- End .wrap -->';
	}
	
	
	
	/**
	 * Changes the image uploader to be more user friendly
	 * @since 8/15/12
	 */
	function upload_mod(){
		$change = false;
	
		//Check to see if the uploader should be changed or not
		if( isset( $_GET['type'] ) && $_GET['type'] == 'image' ){
			if( isset( $_GET['post_type']) && $_GET['post_type'] == 'simple_links' ){
				$change = true;
			}
		}
	
		if( $change ) {
			?>
						<style type= "text/css">
						#media-upload-header #sidemenu li #tab-type_url,#media-upload-header #sidemenu li #tab-gallery,#media-items tr.url,#media-items tr .align,#media-items tr.image_alt,#media-items tr.post_title,#media-items tr .image-size,#media-items tr.post_excerpt,#media-items tr.post_content,#media-items tr.image_alt p,#media-items table thead input.button,#media-items table thead img.imgedit-wait-spin,#media-items tr.align,#media-items tr.image-size{display:none}#media-items tr.submit a.wp-post-thumbnail{border:1px solid #666;padding:3px 8px;border-radius:15px;text-decoration:none;color:#dddd;background-image:url(/wp-admin/images/white-grad.png)}
						</style>
						<script type="text/javascript">
						(function($){
							$(document).ready( function (){
								$( '#media-items' ).bind( 'DOMNodeInserted', function(){
									var thumbnailLink = $( ".savesend .wp-post-thumbnail" );
									thumbnailLink.html( 'Use This Image' );
									thumbnailLink.addClass( 'target-link' );
									$( 'input[value="Insert into Post"]' ).hide();
								});
							});
				
						})(jQuery);
						</script >
					<?php
				  }
		}
		
		
		
		/**
		 * Admin stylesheet
		 */
		function admin_style(){
			wp_enqueue_style(
			apply_filters( 'simple_links_admin_style' , 'simple_links_admin_style' ), //The name of the style
			SIMPLE_LINKS_CSS_DIR . 'simple.links.admin.css'
					);

		}
		
		/**
		 * Add the jquery to the admin
		 */
		function admin_scripts( $post){

           $url = array( 'sortURL' => esc_url(wp_nonce_url( admin_url('admin-ajax.php?action=simple_links_sort_children' ), "simple_links_sort_children")),
           				 'importLinksURL' => esc_url(wp_nonce_url( admin_url('admin-ajax.php?action=simple_links_import_links' ), "simple_links_import_links")) );

			//Add the sortable script
			wp_enqueue_script( 'jquery-ui-sortable' );
			
			//For the Pointer Flag
			add_action( 'admin_print_footer_scripts', array( $this, 'pointer_flag') );
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			

			wp_enqueue_script(
			'simple_admin_script', 
			SIMPLE_LINKS_JS_DIR . 'simple_links_admin.js',
			array('jquery' ),  //The scripts this depends on
			'1.0.0'     //The Version of your script
		
			);
			
			
			//add and object of the above array to use in the this script
			wp_localize_script ( 'simple_admin_script', 'SLajaxURL' , $url) ;
			
		}
		
		/**
		 * Edits the menu_order in the database for links
		 * @since 8/28/12
		 * @return null
		 *
		 */
		function ajax_sort(){
    
			//print_r( $_POST['postID'] );
			check_ajax_referer( 'simple_links_sort_children' ); //Match this to the nonce created on the url
			global $wpdb;
			foreach( $_POST['postID'] as $order => $postID ){
				$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->posts SET menu_order = %d WHERE ID = %d", $order, $postID ) );
			}
			die();
		
		}
		
		/**
		 * View the info of the current admin screen
		 * @uses for development purposes only
		 */
		function check_current_screen(){
			add_action( 'admin_notices', array( $this, 'check_current_screen' ));
			if( !is_admin() ) return;
			global $current_screen;
			//print_r( $current_screen );
		}
	
}
}