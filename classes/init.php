<?php 

					/**
					 * Brings in the necessary Classes
					 * @since 8/15/12
					 * @author Mat Lipe <mat@lipeimagination.info>
					 */
					
$simple_links_classes = array( 
						'wp-simple-links-post-type-tax',
						'wp-simple-links.class'
		                
							);

foreach( $simple_links_classes as $class ){
	require( $class . '.php' );
	
}

$simple_links_func = new Simple_Links();




if( is_admin() ){
	require( 'wp-simple-links.admin.class.php' );
	$simple_links_admin_func = new simple_links_admin();
}